<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Media\Contracts\MediaLibrary;
use App\Modules\Media\Data\MediaEntry;
use App\Modules\Media\Exceptions\MediaPathException;
use App\Modules\System\Contracts\SystemMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        SystemMetrics $metrics,
        MediaLibrary $library,
        FrameGateway $frame,
    ): Response {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'snapshot' => $user?->can(Permission::SystemView->value)
                ? $metrics->snapshot()->toArray()
                : null,
            'counters' => [
                'media' => $user?->can(Permission::MediaView->value) ? $this->mediaCounters($library) : null,
                'frame' => $user?->can(Permission::FrameView->value) ? $this->frameCounters($frame) : null,
                'users' => $user?->can(Permission::UsersManage->value) ? $this->userCounters() : null,
            ],
        ]);
    }

    /**
     * @return array{directories: int, files: int}|null
     */
    private function mediaCounters(MediaLibrary $library): ?array
    {
        try {
            $entries = $library->list('')->entries;
        } catch (MediaPathException) {
            return null;
        }

        $directories = count(array_filter($entries, static fn (MediaEntry $entry): bool => $entry->isDirectory));

        return [
            'directories' => $directories,
            'files' => count($entries) - $directories,
        ];
    }

    /**
     * @return array{total: int}|null
     */
    private function frameCounters(FrameGateway $frame): ?array
    {
        try {
            return ['total' => $frame->list(1, 1)->total];
        } catch (FrameException) {
            return null;
        }
    }

    /**
     * @return array{total: int, roles: int}
     */
    private function userCounters(): array
    {
        return [
            'total' => User::query()->count(),
            'roles' => Role::query()->count(),
        ];
    }
}
