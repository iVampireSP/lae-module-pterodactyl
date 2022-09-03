<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\PanelController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\ConnectionException;

class CheckNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        $panel = new PanelController();
        $panel_nodes = $panel->nodes();

        $nodes = [];
        foreach ($panel_nodes['data'] as $node) {
            $node = $node['attributes'];
            $location = $node['relationships']['location']['attributes']['long'];

            if (!$node['public']) {
                continue;
            }

            $nodes[$node['id']]['created_at'] = $node['created_at'];


            $nodes[$node['id']]['name'] = $location . ' # ' . $node['name'];

            $url = $node['scheme'] . '://' . $node['fqdn'] . ':' . $node['daemon_listen'];

            if ($node['maintenance_mode']) {
                $nodes[$node['id']]['status'] = 'maintenance';
            } else {
                try {
                    Http::get($url, [
                        'Accept' => 'Application/vnd.pterodactyl.v1+json',
                    ]);
                    $nodes[$node['id']]['status'] = 'up';
                } catch (ConnectionException) {
                    $nodes[$node['id']]['status'] = 'down';
                }
            }
        }

        Cache::put('nodes_status', $nodes, 600);
    }
}
