<?php

namespace App\Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class HostInfo implements Arrayable
{
    public function __construct(
        public string $name,
        public string $os,
        public int $uptimeSeconds,
        public Carbon $bootedAt,
    ) {}

    /**
     * @param  array{name: string, os: string, uptimeSeconds: int, bootedAt: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            os: (string) $data['os'],
            uptimeSeconds: (int) $data['uptimeSeconds'],
            bootedAt: Carbon::parse($data['bootedAt']),
        );
    }

    /**
     * @return array{name: string, os: string, uptimeSeconds: int, bootedAt: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'os' => $this->os,
            'uptimeSeconds' => $this->uptimeSeconds,
            'bootedAt' => $this->bootedAt->toIso8601String(),
        ];
    }
}
