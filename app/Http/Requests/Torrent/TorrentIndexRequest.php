<?php

namespace App\Http\Requests\Torrent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TorrentIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selected' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * The torrent whose detail panel is open, when one is.
     */
    public function selected(): ?int
    {
        $selected = $this->integer('selected');

        return $selected > 0 ? $selected : null;
    }
}
