<?php

namespace App\Modules\System\Services;

use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Data\CpuLoad;
use App\Modules\System\Data\HostInfo;
use App\Modules\System\Data\SystemSnapshot;
use App\Modules\System\Support\CommandRunner;
use App\Modules\System\Support\DfParser;
use App\Modules\System\Support\ProcMeminfoParser;
use App\Modules\System\Support\ProcStatParser;
use Illuminate\Support\Carbon;

final readonly class LinuxSystemMetrics implements SystemMetrics
{
    private const string PROC_STAT = '/proc/stat';

    private const string PROC_MEMINFO = '/proc/meminfo';

    private const string PROC_UPTIME = '/proc/uptime';

    private const string THERMAL_ZONE = '/sys/class/thermal/thermal_zone0/temp';

    private const int SAMPLE_INTERVAL_MICROSECONDS = 120_000;

    /**
     * @param  array<string, string>  $diskLabels
     */
    public function __construct(
        private CommandRunner $commands,
        private ProcStatParser $procStat,
        private ProcMeminfoParser $procMeminfo,
        private DfParser $df,
        private array $diskLabels = [],
    ) {}

    public function snapshot(): SystemSnapshot
    {
        $firstSample = $this->read(self::PROC_STAT);
        usleep(self::SAMPLE_INTERVAL_MICROSECONDS);
        $secondSample = $this->read(self::PROC_STAT);

        $uptimeSeconds = (int) (float) strtok($this->read(self::PROC_UPTIME), ' ');
        $loadAverage = sys_getloadavg() ?: [0.0, 0.0, 0.0];

        return new SystemSnapshot(
            host: new HostInfo(
                name: gethostname() ?: 'localhost',
                os: trim($this->commands->run('uname -sr')) ?: PHP_OS,
                uptimeSeconds: $uptimeSeconds,
                bootedAt: Carbon::now()->subSeconds($uptimeSeconds),
            ),
            cpu: new CpuLoad(
                usagePercent: $this->procStat->usagePercent($firstSample, $secondSample),
                cores: $this->procStat->coreCount($secondSample),
                loadAverage: [(float) $loadAverage[0], (float) $loadAverage[1], (float) $loadAverage[2]],
                temperatureCelsius: $this->temperature(),
            ),
            memory: $this->procMeminfo->parse($this->read(self::PROC_MEMINFO)),
            disks: $this->df->parse($this->commands->run('df -kP'), $this->diskLabels),
            capturedAt: Carbon::now(),
        );
    }

    private function temperature(): ?float
    {
        $raw = trim($this->read(self::THERMAL_ZONE));

        if (! is_numeric($raw)) {
            return null;
        }

        return (float) $raw / 1000;
    }

    private function read(string $path): string
    {
        if (! is_readable($path)) {
            return '';
        }

        return (string) @file_get_contents($path);
    }
}
