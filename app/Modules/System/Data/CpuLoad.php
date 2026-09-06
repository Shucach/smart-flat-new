<?php

namespace App\Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class CpuLoad implements Arrayable
{
    /**
     * @param  array{0: float, 1: float, 2: float}  $loadAverage
     */
    public function __construct(
        public float $usagePercent,
        public int $cores,
        public array $loadAverage,
        public ?float $temperatureCelsius,
    ) {}

    /**
     * @param  array{usagePercent: float, cores: int, loadAverage: array{0: float, 1: float, 2: float}, temperatureCelsius: float|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            usagePercent: (float) $data['usagePercent'],
            cores: (int) $data['cores'],
            loadAverage: [
                (float) $data['loadAverage'][0],
                (float) $data['loadAverage'][1],
                (float) $data['loadAverage'][2],
            ],
            temperatureCelsius: $data['temperatureCelsius'] === null ? null : (float) $data['temperatureCelsius'],
        );
    }

    /**
     * @return array{usagePercent: float, cores: int, loadAverage: array{0: float, 1: float, 2: float}, temperatureCelsius: float|null}
     */
    public function toArray(): array
    {
        return [
            'usagePercent' => round($this->usagePercent, 1),
            'cores' => $this->cores,
            'loadAverage' => [
                round($this->loadAverage[0], 2),
                round($this->loadAverage[1], 2),
                round($this->loadAverage[2], 2),
            ],
            'temperatureCelsius' => $this->temperatureCelsius === null ? null : round($this->temperatureCelsius, 1),
        ];
    }
}
