<?php

use App\Enums\Permission;

test('every permission has a Ukrainian label and a group', function (Permission $permission) {
    expect($permission->label())->not->toBeEmpty()
        ->and($permission->group())->not->toBeEmpty();
})->with(Permission::cases());

test('the permission values are the contract shared with the frontend', function () {
    expect(Permission::values())->toBe([
        'media.view',
        'media.delete',
        'frame.view',
        'frame.upload',
        'frame.delete',
        'frame.restart',
        'system.view',
        'system.power',
        'users.manage',
    ]);
});
