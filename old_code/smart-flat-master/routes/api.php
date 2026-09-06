<?php

use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SmartFrameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', ApiLoginController::class);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    // Route::post('server/reboot', [ServerController::class, 'reboot']);
    // Route::post('server/shut-down', [ServerController::class, 'shutDown']);
    Route::post('server/get-system-load', [ServerController::class, 'getSystemLoad']);
    Route::post('server/disk-info', [ServerController::class, 'getDiskInfo']);

    Route::post('media/page', [MediaController::class, 'getFolder']);
    Route::post('media/download', [MediaController::class, 'setDownload']);
    Route::post('media/download/list', [MediaController::class, 'getDownloads']);
    Route::post('media/download/delete', [MediaController::class, 'deleteDownload']);
    Route::post('media/download/stop', [MediaController::class, 'stopDownload']);
    Route::post('media/download/run', [MediaController::class, 'runDownload']);
    Route::post('media/delete', [MediaController::class, 'delete']);

    Route::get('smart-frame/list', [SmartFrameController::class, 'getListImages']);
    Route::post('smart-frame/delete', [SmartFrameController::class, 'deleteImages']);
    Route::post('smart-frame/save', [SmartFrameController::class, 'saveFile']);

    // Route::post('smart-frame/reboot', [SmartFrameController::class, 'reboot']);
    // Route::post('smart-frame/shut-down', [SmartFrameController::class, 'shutDown']);
});
