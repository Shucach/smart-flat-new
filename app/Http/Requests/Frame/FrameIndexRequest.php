<?php

namespace App\Http\Requests\Frame;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FrameIndexRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 12;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function page(): int
    {
        return max(1, (int) $this->integer('page', 1));
    }

    public function perPage(): int
    {
        return (int) $this->integer('perPage', self::DEFAULT_PER_PAGE);
    }
}
