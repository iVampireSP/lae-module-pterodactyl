<?php

namespace App\Jobs;

use App\Models\Host;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\PanelController;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RefreshHostJob implements ShouldQueue
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
        $panel = new PanelController();

        Host::where('status', 'pending')->chunk(100, function ($hosts) use ($panel) {
            foreach ($hosts as $host) {
                try {
                    $server = $panel->server($host->server_id);
                } catch (Exception) {
                    continue;
                }

                $suspended = $server['attributes']['suspended'];

                if ($suspended) {
                    $host->status = 'suspended';
                } else {
                    $host->status = 'running';
                }

                $host->save();
            }
        });
    }
}
