<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            ...$this->profileRules($user->id),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists(Role::class, 'id')],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function roleIds(): array
    {
        /** @var array<int, int|string> $roles */
        $roles = $this->validated()['roles'] ?? [];

        return array_map(intval(...), array_values($roles));
    }
}
