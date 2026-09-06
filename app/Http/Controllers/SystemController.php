<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\System\PowerRequest;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Exceptions\PowerException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(Request $request, SystemMetrics $metrics): Response
    {
        return Inertia::render('system/Index', [
            'snapshot' => $metrics->snapshot()->toArray(),
            'canPower' => $request->user()?->can(Permission::SystemPower->value) ?? false,
        ]);
    }

    public function power(PowerRequest $request, PowerManager $power): RedirectResponse
    {
        $action = $request->action();

        try {
            $power->perform($action);
        } catch (PowerException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $action->label().': команду надіслано.']);

        return back();
    }
}
