<?php

namespace App\Modules\System\Services;

use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Data\CpuLoad;
use App\Modules\System\Data\HostInfo;
use App\Modules\System\Data\SystemSnapshot;
use App\Modules\System\Support\CommandRunner;
use App\Modules\System\Support\DfParser;
use App\Modules\System\Support\PsCpuParser;
use App\Modules\System\Support\VmStatParser;
use Illuminate\Support\Carbon;

/**
 * The macOS counterpart of the Linux metrics, used for local development.
 */
final readonly class DarwinSystemMetrics implements SystemMetrics
{
    /**
     * @param  array<string, string>  $diskLabels
     */
    public function __construct(
        private CommandRunner $commands,
        private VmStatParser $vmStat,
        private PsCpuParser $psCpu,
        private DfParser $df,
        private array $diskLabels = [],
    ) {}

    public function snapshot(): SystemSnapshot
    {
        $cores = max(1, (int) trim($this->commands->run('sysctl -n hw.ncpu')));
        $bootedAt = $this->bootedAt();
        $loadAverage = sys_getloadavg() ?: [0.0, 0.0, 0.0];
        [$swapTotal, $swapUsed] = $this->swap();

        return new SystemSnapshot(
            host: new HostInfo(
                name: gethostname() ?: 'localhost',
                os: $this->operatingSystem(),
                uptimeSeconds: max(0, (int) Carbon::now()->diffInSeconds($bootedAt, absolute: true)),
                bootedAt: $bootedAt,
            ),
            cpu: new CpuLoad(
                usagePercent: $this->psCpu->usagePercent($this->commands->run('ps -A -o %cpu='), $cores),
                cores: $cores,
                loadAverage: [(float) $loadAverage[0], (float) $loadAverage[1], (float) $loadAverage[2]],
                temperatureCelsius: null,
            ),
            memory: $this->vmStat->parse(
                $this->commands->run('vm_stat'),
                (int) trim($this->commands->run('sysctl -n hw.memsize')),
                $swapTotal,
                $swapUsed,
            ),
            disks: $this->df->parse($this->commands->run('df -kP'), $this->diskLabels),
            capturedAt: Carbon::now(),
        );
    }

    private function operatingSystem(): string
    {
        $name = trim($this->commands->run('sw_vers -productName'));
        $version = trim($this->commands->run('sw_vers -productVersion'));

        return trim($name.' '.$version) ?: PHP_OS;
    }

    private function bootedAt(): Carbon
    {
        if (preg_match('/sec = (\d+)/', $this->commands->run('sysctl -n kern.boottime'), $matches) === 1) {
            return Carbon::createFromTimestamp((int) $matches[1]);
        }

        return Carbon::now();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function swap(): array
    {
        $output = $this->commands->run('sysctl -n vm.swapusage');

        if (preg_match('/total = ([\d.]+)M\s+used = ([\d.]+)M/', $output, $matches) !== 1) {
            return [0, 0];
        }

        return [(int) ((float) $matches[1] * 1024 * 1024), (int) ((float) $matches[2] * 1024 * 1024)];
    }
}
