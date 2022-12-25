<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Models\Host;
use App\Jobs\ServerJob;
use App\Models\Location;
// use Illuminate\Support\Str;
use App\Models\WingsNestEgg;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PanelController;
use App\Jobs\UpdateServerJob;
use Illuminate\Support\Facades\Log;

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
            'memory' => 'required|integer|min:128|max:40960',
            'disk' => 'required|integer|min:512|max:40960',
            'cpu_limit' => 'required|integer|min:100|max:1200',
            'databases' => 'required|integer|max:5',
            'backups' => 'required|integer|max:50',
        ]);

        $location = Location::findOrFail($request->location_id);

        // 预留主机位置
        $host = $this->http->post('/hosts', [
            'name' => $request->name, // 主机名称，如果为 null 则随机生成。
            'user_id' => $request->user_id, // 给指定用户创建主机
            'price' => 0.01, // 预留 0.01 用于验证用户的余额
            'status' => 'pending', // 初始状态
        ]);


        $host_response = $host->json();

        if ($host->successful()) {
            $host_id = $host_response['id'];
        } else {
            Log::error('创建主机失败', $host_response);
            return $this->error($host_response);
        }

        $egg = WingsNestEgg::where('egg_id', $request->egg_id)->where('found', 1)->firstOrFail();
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
                'cpu' => (int) $request->cpu_limit,
                'disk' => (int) $request->disk,
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
            // 'external_id' => (string) $host_id,
        ];

        dispatch(new ServerJob('create', $host_id, $request->user_id, $server_data, $request->all()));

        return $this->created($request->all());
    }

    public function show(Request $request, Host $host)
    {
        $this->isUser($host);

        $panel = new PanelController();


        $server = $panel->server($host->server_id);

        if (!$server) {
            return $this->error('服务器不存在。');
        }

        $host->load('egg');
        $host->server = $server['attributes'];

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
        if ($request->has('egg_id')) {
            $request->validate([
                'egg_id' => 'required|integer',
            ]);
        }


        if ($request->has('cpu_limit')) {
            $request->validate([
                'cpu_limit' => 'required|integer|min:100|max:1200',
            ]);
        }

        if ($request->has('memory')) {
            $request->validate([
                'memory' => 'required|integer|min:1024|max:16384',
            ]);
        }

        if ($request->has('disk')) {
            $request->validate([
                'disk' => 'required|integer|min:512|max:40960',
            ]);
        }

        if ($request->has('allocations')) {
            $request->validate([
                'allocations' => 'required|integer|max:10',
            ]);
        }

        if ($request->has('databases')) {
            $request->validate([
                'databases' => 'required|integer|max:5',
            ]);
        }

        if ($request->has('backups')) {
            $request->validate([
                'backups' => 'required|integer|max:50',
            ]);
        }

        $task = $this->http->post('/tasks', [
            'title' => '挂起',
            'host_id' => $host->host_id,
            'status' => 'pending',
        ])->json();
        $task_id = $task['id'] ?? false;

        dispatch(new UpdateServerJob($request->toArray(), $host->id, $task_id));

        if ($request->has('name')) {
            // 检测 name 是否为空
            if (empty($request['name'])) {
                return $this->error('名称不能为空。');
            }


            $this->http->patch('/hosts/' . $host->host_id, [
                'name' => $request->name,
            ]);


            $this->http->patch('/tasks/' . $task_id, [
                'title' => '名称修改完成。',
                'status' => 'done',
            ]);
        }


        return $this->updated('正在更新...');
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

        $host->load('location');


        $task = $this->http->post('/tasks', [
            'title' => '正在删除...',
            'host_id' => $host->host_id,
            'status' => 'processing',
        ])->json();

        // dd($task);

        // 寻找服务器的逻辑
        $task_id = $task['id'] ?? false;

        // if (!$task_id) {
        //     return $this->error('任务创建失败。');
        // }

        // 禁止删除 pending
        if ($host->status === 'pending') {
            return $this->http->patch('/tasks/' . $task_id, [
                'title' => '无法删除服务器，因为服务器状态为 pending。',
            ]);
        }

        $this->http->patch('/tasks/' . $task_id, [
            'title' => '从远程服务器删除...',
        ]);

        try {
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->http->patch('/tasks/' . $task_id, [
                'title' => '删除失败。',
                'status' => 'failed',
            ]);
        }

        return $this->deleted($host);
    }


    public function isUser(Host $host)
    {
        if ($host->user_id != auth()->id()) {
            abort(403, '您无权访问此主机。');
        }
    }


    public function server(Host $host, $path)
    {
        $this->isUser($host);

        return $this->api_server($host, $path);
    }

    public function server_detail(Host $host)
    {
        $this->isUser($host);

        return $this->api_server_detail($host);
    }



    public function api_server(Host $host, $path)
    {
        // get request method
        $method = request()->method();

        // get request data
        $data = request()->all();

        $path = 'servers/' . $host->identifier . '/' . $path;

        $result = $this->panel->{$method}($path, $data)->json();

        return $this->success($result);
    }

    public function api_server_detail(Host $host)
    {
        $result = $this->panel->get('servers/' . $host->identifier)->json();

        unset($result['attributes']['server_owner']);

        return $this->success($result);
    }
}
