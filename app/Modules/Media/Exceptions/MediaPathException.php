<?php

namespace App\Modules\Media\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class MediaPathException extends RuntimeException
{
    public static function outsideRoot(string $path): self
    {
        return new self("Шлях [{$path}] знаходиться за межами медіасховища.");
    }

    public static function missing(string $path): self
    {
        return new self("Шлях [{$path}] не існує.");
    }

    public static function rootIsProtected(): self
    {
        return new self('Корінь медіасховища видалити не можна.');
    }

    public function render(Request $request): Response
    {
        return response($this->getMessage(), Response::HTTP_FORBIDDEN);
    }
}
