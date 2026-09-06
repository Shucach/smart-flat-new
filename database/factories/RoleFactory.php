<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'name' => Str::slug($label),
            'label' => Str::title($label),
            'permissions' => [],
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::ADMIN,
            'label' => 'Адміністратор',
            'permissions' => Permission::values(),
        ]);
    }

    /**
     * @param  array<int, Permission>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes): array => [
            'permissions' => array_map(static fn (Permission $permission): string => $permission->value, $permissions),
        ]);
    }
}
