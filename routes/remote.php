<?php

use App\Http\Controllers\Remote;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Remote\Functions;



/**
 * 远程路由 Remote
 * 这里的路由都会暴露给用户和平台，并且您也必须确保它们都经过 'Remote' 中间件，否则这些路由将不安全。
 *
 */


Route::get('/remote', [Remote\RemoteController::class, 'index']);
Route::post('/fast-login', [Remote\RemoteController::class, 'login']);

Route::apiResource('work-orders', Remote\WorkOrder\WorkOrderController::class);
Route::apiResource('work-orders.replies', Remote\WorkOrder\ReplyController::class);
Route::apiResource('hosts', Remote\HostController::class)->only(['show', 'update', 'destroy']);


// 注意，以下路由都是暴露给用户的，并且必须经过 'Remote' 中间件，否则这些路由将不安全。

/**
 * Export functions
 * 导出函数，提供给用户访问。
 * 请求方式将会透传, 您定义了什么请求方式，在前端中就应该使用哪种类型的请求方式。
 */

// 当前模块的函数。服务器启停，创建，销毁，都需要进过这里。
Route::group(['prefix' => '/functions', 'as' => 'functions.'], function () {
    Route::apiResource('hosts', Functions\HostController::class);
    Route::get('nests', [Functions\NestController::class, 'nests']);
    Route::get('locations', Functions\LocationController::class);
    Route::patch('account', [Functions\AccountController::class, 'update']);
    Route::match(['get', 'post'], 'calculate', [App\Http\Controllers\Remote\HostController::class, 'calc']);

    // 匹配所有
    Route::get('hosts/{host}/server', [Functions\HostController::class, 'server_detail']);
    Route::any('hosts/{host}/server/{path}', [Functions\HostController::class, 'server'])->where('path', '.*');
});

// 导出函数。用于给其它集成模块调用。做到模块之间相互交换信息或控制。
Route::group(['prefix' => '/exports', 'as' => 'exports.'], function () {
    Route::get('hosts', [Functions\HostController::class, 'api_hosts']);
    Route::get('hosts/{host}/server', [Functions\HostController::class, 'api_server_detail']);
    Route::any('hosts/{host}/server/{path}', [Functions\HostController::class, 'api_server'])->where('path', '.*');
});
