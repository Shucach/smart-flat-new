<?php

namespace App\Modules\Frame\Services;

use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Data\FrameImage;
use App\Modules\Frame\Data\FramePage;
use App\Modules\Frame\Exceptions\FrameException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final readonly class HttpFrameGateway implements FrameGateway
{
    public function __construct(
        private string $host,
        private string $key,
        private int $timeout = 15,
    ) {}

    public function list(int $page, int $perPage): FramePage
    {
        $payload = $this->send(fn (PendingRequest $request) => $request->get(
            $this->endpoint('list-images'),
            ['page' => $page, 'prePage' => $perPage],
        ));

        $images = [];

        foreach ($payload['images'] ?? [] as $image) {
            if (! is_array($image) || ! isset($image['name'], $image['file'])) {
                continue;
            }

            $images[] = new FrameImage((string) $image['name'], (string) $image['file']);
        }

        $pagination = is_array($payload['pagination'] ?? null) ? $payload['pagination'] : [];
        $lastPage = max(1, (int) ($pagination['maxPage'] ?? 1));
        $currentPage = max(1, (int) ($pagination['page'] ?? $page));

        return new FramePage(
            images: $images,
            page: $currentPage,
            perPage: $perPage,
            lastPage: $lastPage,
            total: $this->estimateTotal($pagination, $currentPage, $lastPage, $perPage, count($images)),
        );
    }

    public function upload(string $absolutePath, string $originalName): void
    {
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            throw FrameException::unavailable("файл [{$absolutePath}] недоступний");
        }

        $this->send(fn (PendingRequest $request) => $request
            ->attach('images', $contents, $originalName)
            ->post($this->endpoint('upload-images')));
    }

    /**
     * The names travel as a JSON body: a form-encoded array reaches the frame as
     * `images[0]`, `images[1]`, which its `images` field never picks up.
     */
    public function delete(array $names): void
    {
        $this->send(fn (PendingRequest $request) => $request
            ->asJson()
            ->post($this->endpoint('delete-images'), ['images' => array_values($names)]));
    }

    /**
     * The frame API reports the page count only, so the total is derived from
     * the number of images on the last page when that page is the one loaded.
     *
     * @param  array<string, mixed>  $pagination
     */
    private function estimateTotal(array $pagination, int $page, int $lastPage, int $perPage, int $loaded): int
    {
        if (isset($pagination['total'])) {
            return (int) $pagination['total'];
        }

        if ($page >= $lastPage) {
            return ($lastPage - 1) * $perPage + $loaded;
        }

        return $lastPage * $perPage;
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     * @return array<string, mixed>
     */
    private function send(callable $callback): array
    {
        if ($this->host === '' || $this->key === '') {
            throw FrameException::notConfigured();
        }

        try {
            $response = $callback(
                Http::withHeaders(['Authorization' => $this->key])
                    ->connectTimeout(min(5, $this->timeout))
                    ->timeout($this->timeout)
            )->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw FrameException::unavailable($exception->getMessage());
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw FrameException::unavailable('несподівана відповідь');
        }

        if (($body['success'] ?? false) !== true) {
            throw FrameException::unavailable((string) ($body['message'] ?? 'невідома помилка'));
        }

        return is_array($body['data'] ?? null) ? $body['data'] : [];
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->host, '/').'/api/v1/'.$path;
    }
}
