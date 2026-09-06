<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Role::query()->updateOrCreate(
            ['name' => Role::ADMIN],
            [
                'label' => 'Адміністратор',
                'permissions' => Permission::values(),
            ],
        );

        Role::query()->updateOrCreate(
            ['name' => Role::VIEWER],
            [
                'label' => 'Спостерігач',
                'permissions' => [
                    Permission::MediaView->value,
                    Permission::FrameView->value,
                    Permission::SystemView->value,
                ],
            ],
        );
    }
}
