<?php

namespace App\Http\Controllers;

use App\Classes\AjaxResponse;
use App\Services\SmartFrameService;
use Illuminate\Http\Request;

class SmartFrameController extends Controller
{
    public function getListImages(Request $request, SmartFrameService $frameService)
    {
        $page = $request->get('page', 1);
        $prePage = $request->get('prePage', 3);

        [$data, $error] = $frameService->getListImages($page, $prePage);
        if ($error) {
            return AjaxResponse::failed()->setMessage($error);
        }

        return AjaxResponse::success()->setData($data['data']);
    }

    public function deleteImages(Request $request, SmartFrameService $frameService)
    {
        try {
            $request->validate([
                'names.*' => 'required|string',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        [$data, $error] = $frameService->deleteImages($request->input('names'));

        if ($error) {
            return AjaxResponse::failed()->setMessage($error);
        }

        return AjaxResponse::success()->setData($data);
    }

    public function saveFile(Request $request, SmartFrameService $frameService)
    {
        $files = $request->file('images');

        $data = [];
        if ($files) {
            [$data, $error] = $frameService->saveImages($files);

            if ($error) {
                return AjaxResponse::failed()->setMessage($error);
            }
        }

        return AjaxResponse::success()->setData($data);
    }

    public function reboot(SmartFrameService $frameService)
    {
        // TODO: later
    }

    public function shutDown(SmartFrameService $frameService)
    {
        // TODO: later
    }
}
