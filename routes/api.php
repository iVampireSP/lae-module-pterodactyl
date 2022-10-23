<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Remote\Functions\HostController;

Route::get('/user', UserController::class);
Route::get('hosts/{host}/server', [HostController::class, 'api_server_detail']);
Route::any('hosts/{host}/server/{path}', [HostController::class, 'api_server'])->where('path', '.*');
