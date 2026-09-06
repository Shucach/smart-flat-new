<?php

namespace App\Http\Controllers;

use App\Classes\AjaxResponse;
use App\Services\ServerService;

class ServerController extends Controller
{
    public function reboot()
    {
        $commandServo = escapeshellcmd('python '.base_path('/python/live/server/reboot.py'));
        shell_exec($commandServo);

        return 'ok';
    }

    public function shutDown()
    {
        $commandServo = escapeshellcmd('python '.base_path('/python/live/server/shut_down.py'));
        shell_exec($commandServo);

        return 'ok';
    }

    public function getSystemLoad(ServerService $serverService): AjaxResponse
    {
        $res = $serverService->getSystemLoad();

        return AjaxResponse::success()->setData(compact('res'));
    }

    public function getDiskInfo(ServerService $serverService): AjaxResponse
    {
        $res = $serverService->getDiskInfo();

        return AjaxResponse::success()->setData(compact('res'));
    }
}
