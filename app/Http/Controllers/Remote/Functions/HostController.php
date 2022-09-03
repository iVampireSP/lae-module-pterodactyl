<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Models\Host;
use App\Jobs\ServerJob;
use App\Models\Location;
use Illuminate\Support\Str;
use App\Models\WingsNestEgg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PanelController;

class HostController extends Controller
{
    public function index()
    {
        $hosts = Host::thisUser()->with('egg', function ($query) {
            $query->select(['egg_id', 'name']);
        })->get();
        return $this->success($hosts);
    }

    public function store(Request $request)
    {
        // $panel = new PanelController();
        $request->validate([
            'name' => 'required|string',
            'egg_id' => 'required|integer',
            'location_id' => 'required|integer',
            'allocations' => 'required|integer|max:10|min:1',
            'memory' => 'required|integer|min:128',
            'disks' => 'required|integer|min:1024',
            'cpu' => 'required|integer|min:100|max:1200',
            'databases' => 'required|integer|max:5',
            'backups' => 'required|integer|max:50',
        ]);

        $location = Location::findOrFail($request->location_id);

        // 预留主机位置
        $host = $this->http->post('/hosts', [
            'name' => $request->name, // 主机名称，如果为 null 则随机生成。
            'user_id' => $request->user_id, // 给指定用户创建主机
            'price' => 10, // 计算的价格
            'status' => 'pending', // 初始状态
        ])->json();

        $host_id = $host['data']['id'];

        $egg = WingsNestEgg::where('egg_id', $request->egg_id)->firstOrFail();
        $nest_id = $egg->nest_id;

        $server_data = [
            'name' => $request->name,
            // 'user' => $user['id'],
            'nest' => $nest_id,
            'egg' => $egg->egg_id,
            'docker_image' => $egg->docker_image,
            'startup' => $egg->startup,
            'oom_disabled' => false,
            'limits' => [
                'memory' => (int) $request->memory,
                'swap' => (int) 1024,
                'io' => 500,
                'cpu' => (int) $request->cpu,
                'disk' => (int) $request->disks,
            ],
            'feature_limits' => [
                'databases' => $request->databases ? (int) $request->databases : null,
                'allocations' => (int) $request->allocations,
                'backups' => (int) $request->backups,
            ],
            'deploy' => [
                'locations' => [(int) $location->location_id],
                'dedicated_ip' => false,
                'port_range' => []
            ],
            // 'environment' => $request->environment,
            'start_on_completion' => true,
            'external_id' => (string) $host_id,
        ];

        dispatch(new ServerJob('create', $host_id, $request->user_id, $server_data, $request->all()));

        return $this->created($request->all());
    }

    public function show(Request $request, Host $host)
    {
        $this->isUser($host);

        $host->load('egg');

        return $this->success($host);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Host $host
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Host $host)
    {
        // 排除 request 中的一些参数
        $request_only = $request->except(['id', 'user_id', 'host_id', 'price', 'managed_price', 'suspended_at', 'created_at', 'updated_at', 'status']);


        $panel = new PanelController();

        $update = [];
        $startup = [];

        // get current server
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


        if ($request->has('egg_id')) {
            $request->validate([
                'egg_id' => 'required|integer',
            ]);

            $egg = WingsNestEgg::find($request->egg_id);

            $request_only['docker_image'] = $egg->docker_image;
            $request_only['egg_id'] = $egg->id;

            $egg->environment = json_decode($egg->environment);
            $startup['environment'] = [];
            foreach ($egg->environment as $env) {
                $env = $env['attributes'];
                $startup['environment'][$env['env_variable']] = $env['default_value'];
            }

            $startup['skip_scripts'] = false;
            $startup['startup'] = $egg->startup;
            $startup['egg'] = $egg->egg_id;
            $startup['image'] = $egg->docker_image;

            $panel->updateServerStartup($host->server_id, $startup);
        }

        if ($request->has('cpu')) {
            $request->validate([
                'cpu' => 'required|integer|min:100|max:1200',
            ]);


            $update['cpu'] = $request->cpu;
        }

        if ($request->has('memory')) {
            $request->validate([
                'memory' => 'required|integer|min:1024|max:16384',
            ]);

            $update['memory'] = $request->memory;
        }

        if ($request->has('disk')) {
            $request->validate([
                'disk' => 'required|integer|min:1024|max:40960',
            ]);

            if ($request->disk < $host->disk) {
                return $this->error('磁盘空间无法减少。');
            }

            $update['disk'] = $request->disk;
        }

        if ($request->has('allocations')) {
            $request->validate([
                'allocations' => 'required|integer|max:10',
            ]);

            $update['feature_limits']['allocations'] = $request->allocations;
        }

        if ($request->has('databases')) {
            $request->validate([
                'databases' => 'required|integer|max:5',
            ]);

            $update['feature_limits']['databases'] = $request->databases;
        }

        if ($request->has('backups')) {
            $request->validate([
                'backups' => 'required|integer|max:50',
            ]);

            $update['feature_limits']['backups'] = $request->backups;
        }


        // dd($update);


        // 如果请求中没有状态操作，则更新其他字段，比如 name 等。
        // 更新时要注意一些安全问题，比如 user_id 不能被用户更新。
        // 这些我们在此函数一开始就检查了。

        // 此时，你可以通知云平台，主机已经更新。但是也请注意安全。

        // if has name
        if ($request->has('name')) {
            $this->http->patch('/hosts/' . $host->host_id, [
                'name' => $request->name,
            ]);
        }

        $panel->updateServerBuild($host->server_id, $update);

        $host->update($request_only);
        return $this->success($host);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Host $host
     * @return \Illuminate\Http\Response
     */
    public function destroy(Host $host)
    {
        // 具体删除逻辑
        $panel = new PanelController();


        $task = $this->http->post('/tasks', [
            'title' => '正在删除...',
            'host_id' => $host->host_id,
            'status' => 'processing',
        ])->json();

        // dd($task);

        // 寻找服务器的逻辑
        $task_id = $task['data']['id'];

        $this->http->patch('/tasks/' . $task_id, [
            'title' => '从远程服务器删除...',
        ]);

        $panel->deleteServer($host->server_id);

        $this->http->patch('/tasks/' . $task_id, [
            'title' => '从我们的数据库中删除...',
        ]);

        $host->delete();

        // 告诉云端，此主机已被删除。
        $this->http->delete('/hosts/' . $host->host_id);

        // 完成任务
        $this->http->patch('/tasks/' . $task_id, [
            'title' => '删除成功。',
            'status' => 'success',
        ]);

        return $this->deleted($host);
    }


    public function isUser(Host $host)
    {
        // return $host->user_id == Auth::id();

        if (request('user_id') !== null) {
            if ($host->user_id != request('user_id')) {
                abort(403);
            }
        }
    }
}
