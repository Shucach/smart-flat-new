<?php

namespace App\Http\Requests\Torrent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreTorrentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required_without:file', 'nullable', 'string', 'max:8192', 'regex:/^(magnet:\?|https?:\/\/)/i'],
            'file' => ['required_without:url', 'nullable', 'file', 'max:'.$this->maxFileKilobytes()],
            'downloadDir' => ['nullable', 'string', 'max:1024'],
            'paused' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required_without' => 'Вкажіть магнет-посилання або оберіть .torrent файл.',
            'url.regex' => 'Посилання має починатися з magnet:? або http(s)://',
            'file.required_without' => 'Вкажіть магнет-посилання або оберіть .torrent файл.',
            'file.max' => 'Файл завеликий.',
        ];
    }

    public function url(): ?string
    {
        $url = trim((string) $this->input('url', ''));

        return $url === '' ? null : $url;
    }

    public function torrentFile(): ?UploadedFile
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function downloadDir(): ?string
    {
        $directory = trim((string) $this->input('downloadDir', ''));

        return $directory === '' ? null : $directory;
    }

    public function paused(): bool
    {
        return $this->boolean('paused');
    }

    private function maxFileKilobytes(): int
    {
        return (int) config('smartflat.torrent.max_file_kilobytes', 5120);
    }
}
