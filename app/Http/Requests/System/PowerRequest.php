<?php

namespace App\Http\Requests\System;

use App\Enums\PowerAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PowerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(PowerAction::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Оберіть дію.',
        ];
    }

    public function action(): PowerAction
    {
        return $this->enum('action', PowerAction::class);
    }
}
