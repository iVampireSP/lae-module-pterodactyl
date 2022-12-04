<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;

class RecountServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重新统计服务器数量';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('重新统计服务器数量');

        Location::all()->each(function ($location) {
            $location->servers = $location->hosts()->count();

            $this->warn("{$location->name} 服务器数量: {$location->servers}");

            $location->save();
        });


        $this->info('重新统计服务器数量完成');

        return 0;
    }
}
