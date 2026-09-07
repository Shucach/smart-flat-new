<?php

namespace App\Http\Requests\Torrent;

use App\Enums\TorrentAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TorrentActionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(TorrentAction::class)],
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Оберіть дію.',
            'ids.required' => 'Оберіть щонайменше один торент.',
        ];
    }

    public function action(): TorrentAction
    {
        return $this->enum('action', TorrentAction::class);
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
}
