<?php

namespace App\Console\Commands;

use App\Models\Host;
use Illuminate\Console\Command;

class UpdatePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hosts:update-all-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新主机价格';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Host::chunk(100, function ($hosts) {
            $i = 0;
            foreach ($hosts as $host) {
                $old_price = $host->price;

                $host->price = $host->calcPrice();

                if ($old_price !== $host->price) {
                    $this->info('正在更新价格:' . $host->id . ', 新的价格为: ' . $host->price);

                    $i++;
                    $host->save();
                }
            }

            $this->info('更新完成, 一共更新了 ' . $i . ' 个主机的价格');

        });


        return 0;
    }
}
