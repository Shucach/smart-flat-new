<?php

namespace App\Http\Requests\Frame;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FrameDestroyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'names' => ['required', 'array', 'max:100'],
            'names.*' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+$/', 'not_regex:/\.\./'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'names.required' => 'Оберіть щонайменше одне зображення.',
            'names.*.regex' => 'Некоректна назва зображення.',
            'names.*.not_regex' => 'Некоректна назва зображення.',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        /** @var array<int, string> $names */
        $names = $this->validated()['names'];

        return array_values($names);
    }
}
