<?php

namespace App\Http\Requests\Torrent;

use App\Modules\Torrent\Data\TorrentLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTorrentLimitsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'downloadKilobytes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'uploadKilobytes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'seedRatio' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'seedForever' => ['boolean'],
            'honorsSessionLimits' => ['boolean'],
        ];
    }

    public function limits(): TorrentLimits
    {
        $seedForever = $this->boolean('seedForever');

        return new TorrentLimits(
            downloadKilobytes: $this->nullableInteger('downloadKilobytes'),
            uploadKilobytes: $this->nullableInteger('uploadKilobytes'),
            seedRatio: $seedForever || $this->input('seedRatio') === null
                ? null
                : (float) $this->input('seedRatio'),
            seedForever: $seedForever,
            honorsSessionLimits: $this->boolean('honorsSessionLimits'),
        );
    }

    private function nullableInteger(string $key): ?int
    {
        return $this->input($key) === null ? null : $this->integer($key);
    }
}
