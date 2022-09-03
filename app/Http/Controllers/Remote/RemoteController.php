<?php

namespace App\Http\Controllers\Remote;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

// use Illuminate\Http\Request;

class RemoteController extends Controller
{
    // invoke
    public function __invoke()
    {
        $data = [
            'remote' => [
                'name' => config('remote.module_name'),
            ],
            'servers' => Cache::get('nodes_status'),
        ];

        return $this->success($data);
    }
}
