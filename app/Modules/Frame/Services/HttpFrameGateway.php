<?php

namespace App\Modules\Frame\Services;

use App\Enums\FrameUploadKind;
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
    private const int RESTART_TIMEOUT = 60;

    /**
     * A clip is megabytes rather than kilobytes, and the frame reads it back with
     * ffprobe and lifts a poster frame out of it before answering -- on a board
     * that takes its time. The ordinary timeout is nowhere near enough.
     */
    private const int UPLOAD_TIMEOUT = 300;

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

            $images[] = new FrameImage(
                (string) $image['name'],
                (string) $image['file'],
                ($image['type'] ?? null) === 'video' ? FrameUploadKind::Video : FrameUploadKind::Image,
            );
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

    /**
     * The frame checks a file before it keeps it -- a clip it could not decode is
     * refused with a reason rather than stored as a black rectangle -- so the
     * reply is read for both the stored name and that reason.
     */
    public function upload(string $absolutePath, string $originalName): string
    {
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            throw FrameException::unavailable("файл [{$absolutePath}] недоступний");
        }

        $data = $this->send(
            fn (PendingRequest $request) => $request
                ->attach('images', $contents, $originalName)
                ->post($this->endpoint('upload-images')),
            timeout: max($this->timeout, self::UPLOAD_TIMEOUT),
        );

        $reason = $this->rejectionReason($data);

        if ($reason !== null) {
            throw FrameException::rejected($reason);
        }

        $stored = is_array($data['images'] ?? null) ? array_values($data['images']) : [];

        if ($stored === []) {
            throw FrameException::unavailable('рамка не повернула назву збереженого файлу');
        }

        return (string) $stored[0];
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
     * The panel reads its picture list once, when the player starts, so an
     * upload or a deletion only reaches the screen after this call. The frame
     * stops the player and retries it up to five times, which takes far longer
     * than an ordinary request, hence the widened timeout.
     */
    public function restartSlideshow(): void
    {
        $this->send(
            fn (PendingRequest $request) => $request->post($this->endpoint('restart-slideshow')),
            timeout: max($this->timeout, self::RESTART_TIMEOUT),
        );
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
    private function send(callable $callback, ?int $timeout = null): array
    {
        if ($this->host === '' || $this->key === '') {
            throw FrameException::notConfigured();
        }

        $timeout ??= $this->timeout;

        try {
            $response = $callback(
                Http::withHeaders(['Authorization' => $this->key])
                    ->connectTimeout(min(5, $timeout))
                    ->timeout($timeout)
            )->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw FrameException::unavailable($exception->getMessage());
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw FrameException::unavailable('несподівана відповідь');
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        if (($body['success'] ?? false) !== true) {
            $reason = $this->rejectionReason($data);

            throw $reason !== null
                ? FrameException::rejected($reason)
                : FrameException::unavailable((string) ($body['message'] ?? 'невідома помилка'));
        }

        return $data;
    }

    /**
     * Returns why the frame refused the file, when it says so.
     *
     * @param  array<string, mixed>  $data
     */
    private function rejectionReason(array $data): ?string
    {
        foreach (is_array($data['rejected'] ?? null) ? $data['rejected'] : [] as $rejection) {
            if (is_array($rejection) && isset($rejection['reason'])) {
                return (string) $rejection['reason'];
            }
        }

        return null;
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->host, '/').'/api/v1/'.$path;
    }
}
