<?php

namespace App\Providers;

use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Services\HttpFrameGateway;
use App\Modules\Frame\Services\NullFrameGateway;
use App\Modules\Media\Contracts\MediaLibrary;
use App\Modules\Media\Services\LocalMediaLibrary;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Services\CachedSystemMetrics;
use App\Modules\System\Services\DarwinSystemMetrics;
use App\Modules\System\Services\LinuxSystemMetrics;
use App\Modules\System\Services\NullPowerManager;
use App\Modules\System\Services\NullSystemMetrics;
use App\Modules\System\Services\ShellPowerManager;
use App\Modules\System\Support\CommandRunner;
use App\Modules\System\Support\DfParser;
use App\Modules\System\Support\ProcMeminfoParser;
use App\Modules\System\Support\ProcStatParser;
use App\Modules\System\Support\PsCpuParser;
use App\Modules\System\Support\VmStatParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class SmartFlatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMedia();
        $this->registerFrame();
        $this->registerSystem();
    }

    private function registerMedia(): void
    {
        $this->app->singleton(MediaLibrary::class, fn (Application $app): MediaLibrary => new LocalMediaLibrary(
            $app->make(Filesystem::class),
            (string) config('smartflat.media.root'),
            (array) config('smartflat.media.ignored_extensions', []),
        ));
    }

    private function registerFrame(): void
    {
        $this->app->singleton(FrameGateway::class, function (): FrameGateway {
            if (config('smartflat.frame.driver') === 'http') {
                return new HttpFrameGateway(
                    (string) config('smartflat.frame.host'),
                    (string) config('smartflat.frame.key'),
                    (int) config('smartflat.frame.timeout', 15),
                );
            }

            return new NullFrameGateway;
        });
    }

    private function registerSystem(): void
    {
        $this->app->singleton(SystemMetrics::class, fn (Application $app): SystemMetrics => new CachedSystemMetrics(
            $this->resolveSystemMetrics($app),
            Cache::store(),
        ));

        $this->app->singleton(PowerManager::class, function (): PowerManager {
            if (config('smartflat.system.power_driver') !== 'shell') {
                return new NullPowerManager;
            }

            return new ShellPowerManager([
                'reboot' => (string) config('smartflat.system.reboot_command'),
                'shutdown' => (string) config('smartflat.system.shutdown_command'),
            ]);
        });
    }

    private function resolveSystemMetrics(Application $app): SystemMetrics
    {
        $driver = (string) config('smartflat.system.driver', 'auto');

        if ($driver === 'auto') {
            $driver = match (PHP_OS_FAMILY) {
                'Linux' => 'linux',
                'Darwin' => 'darwin',
                default => 'null',
            };
        }

        /** @var array<string, string> $labels */
        $labels = (array) config('smartflat.system.disk_labels', []);

        return match ($driver) {
            'linux' => new LinuxSystemMetrics(
                $app->make(CommandRunner::class),
                $app->make(ProcStatParser::class),
                $app->make(ProcMeminfoParser::class),
                $app->make(DfParser::class),
                $labels,
            ),
            'darwin' => new DarwinSystemMetrics(
                $app->make(CommandRunner::class),
                $app->make(VmStatParser::class),
                $app->make(PsCpuParser::class),
                $app->make(DfParser::class),
                $labels,
            ),
            default => new NullSystemMetrics,
        };
    }
}
