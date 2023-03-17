<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PanelController;
use App\Models\Host;

class UpdateID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重新获取所有服务器的 ID';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $panel = new PanelController();

        Host::where('identifier', null)->chunk(100, function ($hosts) use ($panel) {
            foreach ($hosts as $host) {
                $this->info('正在更新服务器 ID: ' . $host->id);
                $server = $panel->server($host->server_id);

                $identifier = $server['attributes']['identifier'];

                $this->info('Identifier: ' . $identifier);
                $host->identifier = $identifier;


                $panel->updateServerDetails(
                    $host->server_id,
                    [
                        'external_id' => $host->host_id,
                    ]
                );

                $host->save();
            }
        });

        return 0;
    }
}
