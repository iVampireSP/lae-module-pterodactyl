<?php

namespace App\Http\Controllers\Remote;

use App\Models\Host;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PanelController;

class HostController extends Controller
{
    public function show(Host $host)
    {
        return $this->success($host);
    }

    public function update(Request $request)
    {
        //
        $panel = new PanelController();
        $host = Host::where('host_id', $request->route('host'))->firstOrFail();

        switch ($request->status) {
            case 'running':

                if ($host->status == 'running') {
                    return;
                }

                $this->http->post('/tasks', [
                    'title' => '正在解除暂停。',
                    'host_id' => $host->host_id,
                    'status' => 'done',
                ])->json();

                $panel->unsuspendServer($host->server_id);

                $host->status = 'running';
                $host->save();

                return $this->success($host);

                break;

            case 'suspended':

                // 如果主机被暂停，则代表主机进入待删除状态。
                // 这个操作不能被用户调用，所以要判断是否是平台调用。

                // 执行暂停操作，然后标记为暂停状态

                // 检测是不是平台调用

                if ($host->status == 'suspended') {
                    return;
                }

                $host->update($request->all());

                $panel->suspendServer($host->server_id);


                // 执行一系列暂停操作

                $this->http->post('/tasks', [
                    'title' => '服务器已暂停。',
                    'host_id' => $host->host_id,
                    'status' => 'done',
                ])->json();

                break;

            case 'error':
                $host->update($request->all());

                break;
        }

        $host->update($request->all());

        return $this->updated($host);
    }

    public function destroy(Request $request)
    {
        // 如果你想要拥有自己的一套删除逻辑，可以不处理这个。返回 false 即可。
        // return false;

        $host = Host::where('host_id', $request->route('host'))->firstOrFail();
        // 或者执行 Functions/HostController.php 中的 destroy 方法。

        if ($host->status === 'pending') {
            return $this->error('主机正在创建中，无法删除。');
        }

        $HostController = new Functions\HostController();

        return $HostController->destroy($host);
    }

    public function calc(Request $request)
    {
        $request->validate([
            'egg_id' => 'required|integer',
            'location_id' => 'required|integer',
            'allocations' => 'required|integer|max:10|min:1',
            'memory' => 'required|integer|min:128|max:65536',
            'disk' => 'required|integer|min:512|max:65536',
            'cpu_limit' => 'required|integer|min:100|max:1200',
            'databases' => 'required|integer|max:20',
            'backups' => 'required|integer|max:50',
        ]);

        return $this->success([
            'price' => $this->calcPrice($request->all()),
        ]);
    }

    public function calcPrice(array $requests)
    {
        $location = Location::findOrFail($requests['location_id']);
        $price = 0;
        $price += $location->price;

        $price += bcdiv($requests['cpu_limit'], 100) * $location->cpu_price;

        // bc
        $price += bcdiv($requests['memory'], 1024) *
            $location->memory_price;


        $price += bcdiv($requests['disk'], 1024) *
            $location->disk_price;

        $price += $requests['backups'] *
            $location->backup_price;

        $price += $requests['allocations'] *
            $location->allocation_price;

        $price += $requests['databases'] *
            $location->database_price;


        $price = round($price, 8);

        $price = match ($requests['billing_cycle'] ?? '') {
            'monthly', 'dynamic' => $price,
            'quarterly' => bcmul($price, 3),
            'semi-annually' => bcmul($price, 6),
            'annually' => bcmul($price, 12),
            'biennially' => bcmul($price, 24),
            'triennially' => bcmul($price, 36),
            default => $price,
        };


        return $price;
    }
}
