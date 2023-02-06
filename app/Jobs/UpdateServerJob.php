<?php

namespace App\Jobs;

use App\Models\Host;
use Illuminate\Support\Arr;
use App\Models\WingsNestEgg;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\PanelController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $request;
    public int $host_id;
    public string $task_id;
    protected $http;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $request, $host_id, string $task_id)
    {
        //
        $this->request = $request;
        $this->host_id = $host_id;
        $this->task_id = $task_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $request = $this->request;
        $host = Host::find($this->host_id);
        $task_id = $this->task_id;
        $this->http = Http::remote()->asForm();



        // 排除 request 中的一些参数

        $request_only = Arr::except($request, ['id', 'user_id', 'host_id', 'identifier', 'price', 'managed_price', 'suspended_at', 'created_at', 'updated_at', 'status']);

        $panel = new PanelController();

        $update = [];
        $startup = [];

        // get current server
        Log::debug("获取服务器信息", $host->toArray());
        $server = $panel->server($host->server_id);

        $update['allocation'] = $server['attributes']['allocation'];
        $update['swap'] = $server['attributes']['limits']['swap'];
        $update['memory'] = $server['attributes']['limits']['memory'];
        $update['cpu'] = $server['attributes']['limits']['cpu'];
        $update['io'] = $server['attributes']['limits']['io'];
        $update['disk'] = $server['attributes']['limits']['disk'];

        $update['feature_limits']['allocations'] = $server['attributes']['feature_limits']['allocations'];
        $update['feature_limits']['databases'] =
            $server['attributes']['feature_limits']['databases'];
        $update['feature_limits']['backups'] =
            $server['attributes']['feature_limits']['backups'];


        if ($request['egg_id'] && $request['egg_id'] != $host->egg_id) {
            $egg = WingsNestEgg::where('egg_id', $request['egg_id'])->firstOrFail();

            $request_only['docker_image'] = $egg->docker_image;
            $request_only['egg_id'] = $egg->egg_id;

            if (!is_array($egg->environment)) {
                $egg->environment = json_decode($egg->environment);
            }
            $startup['environment'] = [];
            foreach ($egg->environment as $env) {
                $env = $env['attributes'];
                $startup['environment'][$env['env_variable']] = $env['default_value'];
            }

            $startup['skip_scripts'] = false;
            $startup['startup'] = $egg->startup;
            $startup['egg'] = $egg->egg_id;
            $startup['image'] = $egg->docker_image;

            Log::debug("lae task: 更新服务器启动设置", [$task_id]);
            $this->http->patch('/tasks/' . $task_id, [
                'title' => '正在更新服务器启动配置...',
                'status' => 'processing',
            ]);


            Log::debug("更新服务器启动设置", $startup);
            $panel->updateServerStartup($host->server_id, $startup);
        }

        if ($request['cpu_limit']) {
            $update['cpu'] = $request['cpu_limit'];
        }

        if ($request['memory']) {
            $update['memory'] = $request['memory'];
        }

        if ($request['disk']) {

            // if ($request->disk < $host->disk) {
            //     return $this->error('磁盘空间无法减少。');
            // }

            $update['disk'] = $request['disk'];
        }

        if ($request['allocations']) {

            $server_allocations_count = count($server['attributes']['relationships']['allocations']['data']);

            if ($request['allocations'] < $server_allocations_count) {
                return $this->http->patch('/tasks/' . $task_id, [
                    'title' => '分配的 端口 数量无法减少。除非您删除一些端口。',
                    'status' => 'failed',
                ]);
            }

            $update['feature_limits']['allocations'] = $request['allocations'];
        }

        if ($request['databases']) {

            $server_databases_count = count($server['attributes']['relationships']['databases']['data']);

            if ($request['databases'] < $server_databases_count) {
                Log::debug("lae task: 数据库数量无法减少。除非您删除一些数据库。", [$task_id]);
                return $this->http->patch('/tasks/' . $task_id, [
                    'title' => '数据库数量无法减少。除非您删除一些数据库。',
                    'status' => 'failed',
                ]);
            }


            $update['feature_limits']['databases'] = $request['databases'];
        }

        if ($request['backups']) {
            $update['feature_limits']['backups'] = $request['backups'];
        }

        Log::debug("lae task: 正在更新服务器限制...", [$task_id]);
        $this->http->patch('/tasks/' . $task_id, [
            'title' => '正在更新服务器限制...',
            'status' => 'processing',
        ]);

        Log::debug("更新服务器 build...", $update);
        $panel->updateServerBuild($host->server_id, $update);

        $host->update($request_only);

        if ($task_id) {
            $this->http->patch('/tasks/' . $task_id, [
                'title' => '更改已应用。',
                'status' => 'success',
            ]);
        }
    }
}
