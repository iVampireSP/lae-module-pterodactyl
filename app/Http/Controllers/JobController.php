<?php

namespace App\Http\Controllers;

use App\Models\WingsNest;
use App\Models\WingsNode;
use App\Models\WingsNestEgg;
use Illuminate\Http\Request;
use App\Jobs\RefreshWingNodeJob;
use Illuminate\Routing\Controller;
use App\Http\Controllers\PanelController;
use Illuminate\Contracts\Support\Renderable;

class JobController extends Controller
{

    // Refresh Nests and Eggs
    public static function refresh_nests()
    {
        $panel = new PanelController();
        $nests = (object)$panel->nests();
        if (!$nests) {
            return 0;
        }
        $arr = self::search($nests);

        $ids = [];
        foreach ($arr as $a) {
            $wingsNest = new WingsNest();
            $attr = $a['attributes'];
            $nest_id = $attr['id'];
            $create_data = [
                'nest_id' => $nest_id,
                'author' => $attr['author'],
                'name' => $attr['name'],
                'description' => $attr['description'],
            ];
            $wingsNest_where = $wingsNest->where('nest_id', $nest_id);
            if ($wingsNest_where->exists()) {
                $ids[] = $nest_id;
                $create_data['found'] = 1;
                $wingsNest_where->update($create_data);
            } else {
                $ids[] = $nest_id;
                $wingsNest->create($create_data);
            }
        }

        WingsNest::whereNotIn('nest_id', $ids)->update(['found' => 0]);

        return $arr;
    }

    public static function refresh_eggs()
    {
        $panel = new PanelController();

        WingsNest::chunk(100, function ($nests) use ($panel) {
            $egg_ids = [];

            foreach ($nests as $nest) {
                // Refresh Eggs
                // Search for eggs in nest
                $eggs = $panel->eggs($nest->nest_id);
                if (!$eggs) {
                    continue;
                }
                foreach ($eggs['data'] as $egg) {
                    $wingsNestEgg = new WingsNestEgg();
                    $egg = $egg['attributes'];

                    $egg_data = [
                        'name' => $egg['name'],
                        'nest_id' => $egg['nest'],
                        'author' => $egg['author'],
                        'description' => $egg['description'],
                        'docker_image' => $egg['docker_image'],
                        'docker_images' => json_encode($egg['docker_images']),
                        'startup' => $egg['startup'],
                        'egg_id' => $egg['id'],
                        'environment' => json_encode($egg['relationships']['variables']['data']),
                    ];

                    $wingsNestEgg_where = $wingsNestEgg->where('egg_id', $egg['id']);
                    if ($wingsNestEgg_where->exists()) {
                        $egg_ids[] = $egg['id'];
                        $egg_data['found'] = 1;
                        $wingsNestEgg_where->update($egg_data);
                    } else {
                        $egg_ids[] = $egg['id'];
                        $wingsNestEgg->create($egg_data);
                    }
                }
            }

            // Update Egg found column
            WingsNestEgg::whereNotIn('egg_id', $egg_ids)->update(['found' => 0]);
        });

        return true;
    }

    public static function updateCountColumn()
    {
        //  Refresh nests counts
        WingsNest::chunk(100, function ($nests) {
            foreach ($nests as $nest) {
                $nest->eggs = WingsNestEgg::where('nest_id', $nest->id)->count();
                $nest->save();
            }
        });
    }

    public static function search($data)
    {
        $data = (object)$data;
        $next_page = 1;
        $is_continue = true;
        $arr = [];
        do {
            $total_page = $data->meta['pagination']['total_pages'];
            if ($next_page == $total_page) {
                $is_continue = false;
            } else {
                $next_page = $data->meta['pagination']['current_page'] + 1;
            }

            foreach ($data->data as $d) {
                array_push($arr, $d);
            }
        } while ($is_continue);

        return $arr;
    }
}
