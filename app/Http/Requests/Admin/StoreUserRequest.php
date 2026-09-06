<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
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
