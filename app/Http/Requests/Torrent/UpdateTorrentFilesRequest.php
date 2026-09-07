<?php

namespace App\Http\Requests\Torrent;

use App\Enums\TorrentFilePriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTorrentFilesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'indexes' => ['required', 'array', 'max:10000'],
            'indexes.*' => ['integer', 'min:0'],
            'wanted' => ['required_without:priority', 'nullable', 'boolean'],
            'priority' => ['required_without:wanted', 'nullable', Rule::enum(TorrentFilePriority::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'indexes.required' => 'Оберіть щонайменше один файл.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function indexes(): array
    {
        /** @var array<int, int> $indexes */
        $indexes = $this->validated()['indexes'];

        return array_values(array_map(intval(...), $indexes));
    }

    /**
     * Null when the request only changes the priority.
     */
    public function wanted(): ?bool
    {
        return $this->input('wanted') === null ? null : $this->boolean('wanted');
    }

    public function priority(): ?TorrentFilePriority
    {
        return $this->input('priority') === null
            ? null
            : $this->enum('priority', TorrentFilePriority::class);
    }
}
