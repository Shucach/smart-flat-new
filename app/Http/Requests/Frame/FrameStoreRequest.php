<?php

namespace App\Http\Requests\Frame;

use App\Enums\FrameUploadKind;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class FrameStoreRequest extends FormRequest
{
    private const int IMAGE_KILOBYTES = 20480;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,m4v,mov', $this->withinSizeLimit(...)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Оберіть щонайменше один файл.',
            'images.max' => 'За один раз можна завантажити не більше 20 файлів.',
            'images.*.file' => 'Не вдалося прочитати файл.',
            'images.*.mimes' => 'Підтримуються фото JPG, PNG, WEBP та відео MP4, MOV, M4V.',
        ];
    }

    /**
     * A photograph and a clip do not weigh the same: one ceiling for both would
     * either turn away every phone video or wave through a 200 MB "photo".
     */
    private function withinSizeLimit(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $isVideo = FrameUploadKind::forUpload($value) === FrameUploadKind::Video;
        $limit = $isVideo ? $this->videoKilobytes() : self::IMAGE_KILOBYTES;

        if ($value->getSize() > $limit * 1024) {
            $fail(sprintf(
                $isVideo ? 'Розмір відео не може перевищувати %d МБ.' : 'Розмір зображення не може перевищувати %d МБ.',
                (int) round($limit / 1024),
            ));
        }
    }

    private function videoKilobytes(): int
    {
        return (int) config('smartflat.frame.video.max_upload_kilobytes', 204800);
    }
}
