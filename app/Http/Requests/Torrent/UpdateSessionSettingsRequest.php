<?php

namespace App\Http\Requests\Torrent;

use App\Modules\Torrent\Data\SessionSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionSettingsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'startAddedTorrents' => ['boolean'],
            'speedLimitDown' => ['required', 'integer', 'min:0', 'max:1000000'],
            'speedLimitDownEnabled' => ['boolean'],
            'speedLimitUp' => ['required', 'integer', 'min:0', 'max:1000000'],
            'speedLimitUpEnabled' => ['boolean'],
            'altSpeedDown' => ['required', 'integer', 'min:0', 'max:1000000'],
            'altSpeedUp' => ['required', 'integer', 'min:0', 'max:1000000'],
            'altSpeedEnabled' => ['boolean'],
            'downloadQueueSize' => ['required', 'integer', 'min:1', 'max:100'],
            'downloadQueueEnabled' => ['boolean'],
            'seedQueueSize' => ['required', 'integer', 'min:1', 'max:100'],
            'seedQueueEnabled' => ['boolean'],
            'seedRatioLimit' => ['required', 'numeric', 'min:0', 'max:1000'],
            'seedRatioLimited' => ['boolean'],
            'peerLimitGlobal' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * The download directory and the peer port are shown but never written from
     * here: both belong to how the daemon is deployed, not to how it is used.
     */
    public function settings(): SessionSettings
    {
        return new SessionSettings(
            downloadDir: '',
            startAddedTorrents: $this->boolean('startAddedTorrents'),
            speedLimitDown: $this->integer('speedLimitDown'),
            speedLimitDownEnabled: $this->boolean('speedLimitDownEnabled'),
            speedLimitUp: $this->integer('speedLimitUp'),
            speedLimitUpEnabled: $this->boolean('speedLimitUpEnabled'),
            altSpeedDown: $this->integer('altSpeedDown'),
            altSpeedUp: $this->integer('altSpeedUp'),
            altSpeedEnabled: $this->boolean('altSpeedEnabled'),
            downloadQueueSize: $this->integer('downloadQueueSize'),
            downloadQueueEnabled: $this->boolean('downloadQueueEnabled'),
            seedQueueSize: $this->integer('seedQueueSize'),
            seedQueueEnabled: $this->boolean('seedQueueEnabled'),
            seedRatioLimit: (float) $this->input('seedRatioLimit'),
            seedRatioLimited: $this->boolean('seedRatioLimited'),
            peerLimitGlobal: $this->integer('peerLimitGlobal'),
            peerPort: 0,
        );
    }
}
