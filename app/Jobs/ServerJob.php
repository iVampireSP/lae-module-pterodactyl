<?php

namespace App\Jobs;

use Exception;
use App\Models\Host;
use App\Models\Location;
use App\Models\WingsNest;
use Illuminate\Support\Str;
use Overtrue\Pinyin\Pinyin;
use App\Models\WingsNestEgg;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\PanelController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type, $data, $cloud_host_id, $user_id, $request, $http;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $cloud_host_id, $user_id, $data, $request)
    {
        $this->type = $type;
        $this->data = $data;
        $this->cloud_host_id = $cloud_host_id;
        $this->user_id = $user_id;
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->http = Http::remote('remote')->asForm();

        $data = $this->data;
        $host_id = $this->cloud_host_id;

        $panel = new PanelController();

        $task = $this->http->post('/tasks', [
            'title' => '我们正在准备...',
            'host_id' => $this->cloud_host_id,
            'status' => 'processing',
        ])->json();
        $task_id = $task['id'];

        switch ($this->type) {
            case 'create':
                $this->http->patch('/tasks/' . $task_id, [
                    'title' => '正在查找或创建您的账户。',
                ]);

                // 创建
                try {
                    $user = $panel->getUserByEmail($this->request['user']['email']);

                    Log::debug("get user", ['user' => $user]);

                    if (count($user['data']) == 0) {

                        // 如果名称包含中文
                        $name = $this->request['user']['name'];
                        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $name) > 0) {
                            $old_name = $name;
                            $pinyin = new Pinyin();
                            $name = $pinyin->permalink($name, '');

                            Log::debug('包含中文', ['name' => $old_name, 'pinyin' => $name]);
                        }


                        // 将空格替换为下划线
                        $name = str_replace(' ', '_', $name);

                        $name = Str::lower($name);

                        $user = $panel->createUser([
                            'username' => $name,
                            'email' => $this->request['user']['email'],
                            'first_name' => Str::random(3),
                            'last_name' => Str::random(5),
                        ]);

                        Log::debug("create user", ['user' => $user]);

                        $user_id = $user['attributes']['id'];
                    } else {
                        Log::debug("get user by email", ['user' => $user]);

                        $user_id = $user['data'][0]['attributes']['id'];
                    }
                } catch (Exception $e) {
                    $this->http->patch('/tasks/' . $task_id, [
                        'title' => '创建用户失败: ' . $e->getMessage(),
                        'status' => 'failed',
                    ]);

                    $this->http->delete('/hosts/' . $host_id);

                    Log::error('unable choose/create user', [$e->getMessage()]);

                    return;
                }

                $data['user'] = $user_id;

                // 检查 egg_id 是否存在
                $egg = WingsNestEgg::where('egg_id', $data['egg'])->first();
                if (is_null($egg)) {
                    $this->http->patch('/tasks/' . $task_id, [
                        'title' => '找不到对应的 Egg, 我们将撤销更改。',
                        'status' => 'failed',
                    ]);

                    $this->http->delete('/hosts/' . $host_id);

                    return false;
                }

                $this->http->patch('/tasks/' . $task_id, [
                    'title' => '正在检查镜像',
                ]);


                // Log::debug('检查镜像', $data['docker_image']);
                // Log::debug('检查镜像', $egg->docker_images);

                // Log::debug(typeof($egg->docker_images));


                $data['docker_image'] = $egg->docker_image;

                $data['startup'] = $egg->startup;

                $this->http->patch('/tasks/' . $task_id, [
                    'title' => '正在初始化默认环境变量。',
                ]);


                if (!is_array($egg->environment)) {
                    $egg->environment = json_decode($egg->environment);
                }

                $data['environment'] = [];
                foreach ($egg->environment as $env) {
                    $env = $env['attributes'];
                    $data['environment'][$env['env_variable']] = $env['default_value'];
                }

                $this->http->patch('/tasks/' . $task_id, [
                    'title' => '创建您的服务器中。',
                ]);

                try {
                    $result = $panel->createServer($data);

                    Log::debug('createServer', ['result' => $result]);
                } catch (Exception $e) {
                    Log::error($e->getMessage());

                    $this->http->patch('/tasks/' . $task_id, [
                        'title' => '创建服务器失败, 我们将撤销更改。',
                        'status' => 'failed',
                    ]);

                    $this->http->delete('/hosts/' . $host_id);

                    return false;
                }

                $nest = WingsNest::find($egg->nest_id);
                $location = Location::where('location_id', $data['deploy']['locations'][0])->first();

                $location->increment('servers');
                $egg->increment('servers');
                $nest->increment('servers');

                $result_ip = $result['attributes']['relationships']['allocations']['data'][0]['attributes']['alias'] ?? $result['attributes']['relationships']['allocations']['data'][0]['attributes']['ip'];

                Host::create([
                    'name' => $data['name'],
                    'identifier' => $result['attributes']['identifier'],
                    'egg_id' => $data['egg'],
                    'cpu_limit' => $data['limits']['cpu'],
                    'memory' => $data['limits']['memory'],
                    'disk' => $data['limits']['disk'],
                    'databases' => $data['feature_limits']['databases'] ?? 0,
                    'backups' => $data['feature_limits']['backups'],
                    'allocations' =>
                    $data['feature_limits']['allocations'],
                    'server_id'
                    => $result['attributes']['id'],
                    'ip' => $result_ip,
                    'port'
                    => $result['attributes']['relationships']['allocations']['data'][0]['attributes']['port'],
                    'user_id' => $this->request['user_id'],
                    'status' => 'running',
                    'host_id' => $this->cloud_host_id,
                    'location_id' => $this->request['location_id'],
                ]);


                $this->http->patch('/tasks/' . $task_id, [
                    'title' => '服务器创建完成。',
                    'status' => 'success',
                ]);

                $this->http->patch('/hosts/' . $host_id, [
                    'status' => 'running',
                ]);

                break;
        }
    }
}
