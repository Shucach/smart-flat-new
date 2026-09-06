<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('label')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'permissions' => $role->grantedPermissions(),
                'usersCount' => (int) $role->users_count,
            ])
            ->all();

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::query()->create([
            'name' => $request->string('name')->toString(),
            'label' => $request->string('label')->toString(),
            'permissions' => $request->validated('permissions', []),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Роль створено.']);

        return to_route('admin.roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'label' => $request->string('label')->toString(),
            'permissions' => $role->isSuperRole()
                ? Permission::values()
                : $request->validated('permissions', []),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Роль оновлено.']);

        return to_route('admin.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->isSuperRole()) {
            return back()->withErrors(['role' => 'Роль адміністратора видалити не можна.']);
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Роль видалено.']);

        return to_route('admin.roles.index');
    }

    /**
     * @return array<int, array{group: string, items: array<int, array{value: string, label: string}>}>
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $groups[$permission->group()][] = ['value' => $permission->value, 'label' => $permission->label()];
        }

        return array_map(
            static fn (string $group, array $items): array => ['group' => $group, 'items' => $items],
            array_keys($groups),
            $groups,
        );
    }
}
