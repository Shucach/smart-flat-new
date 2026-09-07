<?php

namespace App\Http\Requests\Torrent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTorrentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer', 'min:1'],
            'deleteData' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Оберіть щонайменше один торент.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        /** @var array<int, int> $ids */
        $ids = $this->validated()['ids'];

        return array_values(array_map(intval(...), $ids));
    }

    /**
     * Whether the downloaded files go with the torrent.
     */
    public function deletesData(): bool
    {
        return $this->boolean('deleteData');
    }
}
