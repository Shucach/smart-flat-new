<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('roles:id,name,label')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles
                    ->map(fn (Role $role): array => ['id' => $role->id, 'name' => $role->name, 'label' => $role->label])
                    ->values()
                    ->all(),
                'createdAt' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Create', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));
        $user->roles()->sync($request->roleIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Користувача створено.']);

        return to_route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        $user->load('roles:id,name,label');

        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('id')->all(),
                'createdAt' => $user->created_at?->toIso8601String(),
            ],
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if (filled($request->validated('password'))) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();
        $user->roles()->sync($request->roleIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Користувача оновлено.']);

        return to_route('admin.users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->withErrors(['user' => 'Не можна видалити власний обліковий запис.']);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Користувача видалено.']);

        return to_route('admin.users.index');
    }

    /**
     * @return array<int, array{id: int, name: string, label: string}>
     */
    private function roles(): array
    {
        return Role::query()
            ->orderBy('label')
            ->get(['id', 'name', 'label'])
            ->map(fn (Role $role): array => ['id' => $role->id, 'name' => $role->name, 'label' => $role->label])
            ->all();
    }
}
