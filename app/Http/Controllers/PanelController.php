<?php

namespace App\Http\Controllers;

use App\Exceptions\PanelException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PanelController extends Controller
{
    protected $http, $url, $panel;

    public function __construct()
    {
        $this->url = config('panel.url') . '/api/application';
        $this->http = Http::withToken(config('panel.key'))->withHeaders([
            'Accept' => 'Application/vnd.pterodactyl.v1+json',
        ]);
        $this->panel = Http::panel()->asForm();
    }


    // Users
    public function users()
    {
        return $this->get('/users');
    }

    // Locations
    public function locations()
    {
        return $this->get('/locations');
    }

    // createLocation
    public function createLocation($short, $name)
    {
        return $this->post('/locations', [
            'short' => $short,
            'long' => $name,
        ]);
    }

    public function deleteLocation($id)
    {
        return $this->delete('/locations/' . $id);
    }

    // Nodes
    public function nodes()
    {
        return $this->get('/nodes?include=location');
    }

    public function node($id, $cache = true)
    {
        $cache_key = 'wings_node_' . $id;
        if ($cache) {
            if (Cache::has($cache_key)) {
                return Cache::get($cache_key);
            } else {
                return $this->get('/nodes/' . $id);
            }
        } else {
            Cache::put($cache_key, $this->get('/nodes/' . $id), 600);
            return Cache::get($cache_key);
        }
    }

    public function nodeStatus($id)
    {
        // $node = WingsNode::find($id);
        $node = $this->get('/nodes/' . $id);
        dd($node);
        $get_url = 'https://' . $node->fqdn . ':' . $node->daemon_listen . '/api/system';
        return (object)Http::withToken($node->token)->get($get_url)->json() ?? false;
    }

    // user
    public function user($id, $cache = true)
    {
        $cache_key = 'wings_user_' . $id;
        if ($cache) {
            if (Cache::has($cache_key)) {
                return Cache::get($cache_key);
            } else {
                Cache::put($cache_key, $this->get('/users/external/' . $id), 600);
            }
        }
        return Cache::get($cache_key);
    }

    public function getUserByEmail($email)
    {
        return $this->get('/users?filter[email]=' . $email);
    }


    public function createUser($data)
    {
        return $this->post('/users', $data);
    }

    public function deleteUser($id)
    {
        return $this->delete('/users/' . $id);
    }

    public function updateUser($id, $data)
    {
        return $this->patch('/users/' . $id, $data);
    }

    // Nests
    public function nests($page = 0)
    {
        return $this->get('/nests' . '?page=' . $page);
    }

    public function nest($id)
    {
        return $this->get('/nests/' . $id);
    }

    public function eggs($id)
    {
        return $this->get('/nests/' . $id . '/eggs?include=variables');
    }

    public function egg($nest_id, $egg_id)
    {
        return $this->get('/nests/' . $nest_id . '/eggs/' . $egg_id);
    }

    public function eggVar($nest_id, $egg_id)
    {
        return $this->get('/nests/' . $nest_id . '/eggs/' . $egg_id . '?include=variables');
    }

    // Allocation
    public function allocations($node_id, $page = 1)
    {
        return $this->get('/nodes/' . $node_id . '/allocations?include=server&page=' . $page);
    }

    public function createAllocation($node_id, $data)
    {
        return $this->post('/nodes/' . $node_id . '/allocations', $data);
    }

    public function deleteAllocation($node_id, $allocation_id)
    {
        return $this->delete('/nodes/' . $node_id . '/allocations/' . $allocation_id);
    }

    // Server
    // public function servers()
    // {
    // }

    public function server($id)
    {
        return $this->get('/servers/' . $id . '?include=allocations,databases,user,location,nest,egg');
    }

    public function createServer($data)
    {
        return $this->post('/servers?include=allocations', $data);
    }

    public function deleteServer($id)
    {
        return $this->delete('/servers/' . $id);
    }

    public function deleteServerForce($id)
    {
        return $this->delete('/servers/' . $id . '/force');
    }

    public function suspendServer($id)
    {
        return $this->post('/servers/' . $id . '/suspend');
    }

    public function unsuspendServer($id)
    {
        return $this->post('/servers/' . $id . '/unsuspend');
    }

    public function reinstallServer($id)
    {
        return $this->post('/servers/' . $id . '/reinstall');
    }

    public function updateServerDetails($server_id, $data)
    {
        return $this->patch('/servers/' . $server_id . '/details', $data);
    }

    public function updateServerStartup($server_id, $data)
    {
        return $this->patch('/servers/' . $server_id . '/startup', $data);
    }

    public function updateServerBuild($server_id, $data)
    {
        return $this->patch('/servers/' . $server_id . '/build', $data);
    }

    public function get($url, $data = null)
    {
        $response = $this->http->get($this->url . $url, $data);
        $response->throw();

        if ($response->failed()) {
            throw new PanelException('Failed to get server');
        } else {
            return $response->json() ?? false;
        }
    }

    public function post($url, $data = null)
    {

        $response = $this->http->post($this->url . $url, $data);
        $response->throw();
        if ($response->failed()) {
            throw new PanelException('Failed post data to server');
        } else {
            return $response->json();
        }
    }

    public function patch($url, $data = null)
    {
        $response = $this->http->patch($this->url . $url, $data);
        $response->throw();
        if ($response->failed()) {
            throw new PanelException('Failed to update server');
        } else {
            return $response->json();
        }
    }

    public function delete($url, $data = null)
    {
        $response = $this->http->delete($this->url . $url, $data)->throw();

        // // if 404
        // if ($response->status() == 404) {
        //     return true;
        // }

        // $response->throw();
        // if ($response->failed()) {
        //     throw new PanelException('Failed to delete server');
        // } else {
        //     return true;
        // }
    }


    public function detail($identifier)
    {
        return $this->panel->get('/servers/' . $identifier)->json();
    }

    public function websocket($identifier)
    {
        return $this->panel->get('/servers/' . $identifier . '/websocket')->json();
    }

    public function resources($identifier)
    {
        return $this->panel->get('/servers/' . $identifier . '/resources')->json();
    }
}
