<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FrameController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('media', [MediaController::class, 'index'])
        ->middleware('can:'.Permission::MediaView->value)
        ->name('media.index');

    Route::delete('media', [MediaController::class, 'destroy'])
        ->middleware('can:'.Permission::MediaDelete->value)
        ->name('media.destroy');

    Route::get('frame', [FrameController::class, 'index'])
        ->middleware('can:'.Permission::FrameView->value)
        ->name('frame.index');

    Route::post('frame', [FrameController::class, 'store'])
        ->middleware('can:'.Permission::FrameUpload->value)
        ->name('frame.store');

    Route::delete('frame', [FrameController::class, 'destroy'])
        ->middleware('can:'.Permission::FrameDelete->value)
        ->name('frame.destroy');

    Route::post('frame/restart', [FrameController::class, 'restart'])
        ->middleware('can:'.Permission::FrameRestart->value)
        ->name('frame.restart');

    Route::get('system', [SystemController::class, 'index'])
        ->middleware('can:'.Permission::SystemView->value)
        ->name('system.index');

    Route::post('system/power', [SystemController::class, 'power'])
        ->middleware('can:'.Permission::SystemPower->value)
        ->name('system.power');

    Route::middleware('can:'.Permission::UsersManage->value)
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });
});
