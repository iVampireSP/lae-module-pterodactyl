<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\PanelController;

class HostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $hosts = Host::with('user')->simplePaginate(100);

        $count = Host::count();

        return view('hosts.index', ['hosts' => $hosts, 'count' => $count]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
        $request->validate([
            'status' => 'sometimes|in:stopped,running,suspended,error,cost',
            'managed_price' => 'sometimes|numeric',
        ]);

        // if status is cost
        if ($request->status == 'cost') {
            $this->http->patch('hosts/' . $host->host_id, [
                'cost_once' => $host->price,
            ]);
            return back()->with('success', '已发送扣费请求。');
        }


        $this->http->patch('hosts/' . $host->host_id, [
            'status' => $request->status,
        ]);

        return back()->with('success', '正在执行对应的操作，操作将不会立即生效，因为他需要进行同步。');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Host $host
     * @return \Illuminate\Http\Response
     */
    public function destroy(Host $host)
    {
        // 销毁前的逻辑

        $panel = new PanelController();

        $host->load('location');

        $panel->deleteServer($host->server_id);

        $host->location->decrement('servers');

        $host->delete();

        $this->http->delete('/hosts/' . $host->host_id);

        // $HostController = new Remote\Functions\HostController();
        // $HostController->destroy($host);

        return back()->with('success', '删除成功。');
    }


    public function import(Request $request)
    {
        // 从 面板 导入已有服务器


        $request->validate([
            'server_id' => 'required|int',
        ]);


        $panel = new PanelController();

        $server = $panel->server($request->server_id);


        $host = Host::where('server_id', $request->server_id)->first();

        if ($host) {
            return back()->with('error', '服务器已经存在。');
        }

        $location_id = $server['attributes']['relationships']['location']['attributes']['id'];

        if (Location::where('location_id', $location_id)->doesntExist()) {
            return back()->with('error', '服务器所在的节点不存在。');
        }


        $last_name = $server['attributes']['relationships']['user']['attributes']['last_name'];
        $user_email = $server['attributes']['relationships']['user']['attributes']['email'];

        $user = User::where('email', $user_email)->first();

        if (!$user) {
            $user = User::create([
                'email' => $user_email,
                'name' => $last_name,
            ]);

            return back()->with('error', '服务器所属的用户不存在。');
        }


        $server_name = $server['attributes']['name'];

        $host = $this->http->post('/hosts', [
            'name' => $server_name,
            'user_id' => $user->id, // 给指定用户创建主机
            'price' => 0, // 计算的价格
            'status' => 'pending', // 初始状态
        ])->json();


        $task = $this->http->post('/tasks', [
            'title' => '正在导入服务器...',
            'host_id' => $host['data']['id'],
            'status' => 'processing',
        ])->json();
        $task_id = $task['data']['id'];


        Host::create([
            'name' => $server_name,
            'egg_id' => $server['attributes']['egg'],
            'cpu_limit' =>
            $server['attributes']['limits']['cpu'],
            'memory' =>
            $server['attributes']['limits']['memory'],
            'disk' =>
            $server['attributes']['limits']['disk'],
            'databases' => $server['attributes']['feature_limits']['databases'],
            'backups' =>
            $server['attributes']['feature_limits']['backups'],
            'allocations' =>
            $server['attributes']['feature_limits']['allocations'],
            'server_id'
            =>
            $server['attributes']['id'],
            'ip' => $server['attributes']['relationships']['allocations']['data'][0]['attributes']['ip'],
            'port'
            => $server['attributes']['relationships']['allocations']['data'][0]['attributes']['port'],
            'user_id' => $server['attributes']['user'],
            'status' => 'running',
            'host_id' => $host['data']['id'],
            'location_id' => $location_id,
        ]);


        $this->http->patch('/tasks/' . $task_id, [
            'title' => '导入服务器成功。',
            'status' => 'success',
        ]);


        $this->http->patch('/hosts/' . $host['data']['id'], [
            'status' => 'running',
        ]);


        return back()->with('success', '导入成功。');
    }
}
