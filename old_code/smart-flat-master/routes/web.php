<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [IndexController::class, 'index'])->name('index');

Route::group(['middleware' => 'auth.api'], function () {
    Route::post('/send/all/info', [SecurityController::class, 'sendAll'])->name('tg_send_all');
    Route::get('/security/options', [SecurityController::class, 'getSecurityOptions'])->name('tg_status_system');
});
