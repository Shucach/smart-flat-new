<?php

namespace App\Http\Requests\Frame;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FrameStoreRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Оберіть щонайменше одне зображення.',
            'images.*.image' => 'Завантажувати можна лише зображення.',
            'images.*.mimes' => 'Підтримуються формати JPG, PNG та WEBP.',
            'images.*.max' => 'Розмір зображення не може перевищувати 20 МБ.',
        ];
    }
}
