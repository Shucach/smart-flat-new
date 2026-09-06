<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

it('grants no permission to a user without a role', function () {
    $user = User::factory()->create();

    expect($user->permissions())->toBe([])
        ->and($user->hasPermission(Permission::MediaView))->toBeFalse()
        ->and($user->isAdministrator())->toBeFalse();
});

it('merges the permissions of every role without duplicates', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->withPermissions([Permission::MediaView, Permission::FrameView])->create());
    $user->roles()->attach(Role::factory()->withPermissions([Permission::FrameView, Permission::SystemView])->create());

    expect($user->refresh()->permissions())->toBe(['media.view', 'frame.view', 'system.view']);
});

it('grants every permission through the super role', function () {
    $user = administrator();

    expect($user->permissions())->toBe(Permission::values())
        ->and($user->isAdministrator())->toBeTrue();
});

it('answers the gate for every permission the role grants', function (Permission $permission) {
    $granted = userWithPermissions($permission);
    $denied = User::factory()->create();

    expect($granted->can($permission->value))->toBeTrue()
        ->and($denied->can($permission->value))->toBeFalse()
        ->and(administrator()->can($permission->value))->toBeTrue();
})->with(Permission::cases());

it('accepts a permission enum or its string value', function () {
    $user = userWithPermissions(Permission::MediaDelete);

    expect($user->hasPermission(Permission::MediaDelete))->toBeTrue()
        ->and($user->hasPermission('media.delete'))->toBeTrue()
        ->and($user->hasPermission('media.view'))->toBeFalse();
});
