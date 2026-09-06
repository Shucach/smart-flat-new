<?php

namespace App\Http\Controllers;

use App\Classes\AjaxResponse;
use App\Media\Films;
use App\Media\TorrentDownload;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    // Torrent download
    public function setDownload(Request $request, TorrentDownload $download)
    {
        try {
            $request->validate([
                'folder' => 'required|string',
                'torrent' => 'required|mimes:torrent',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        $download->setDownload($request);

        try {
            $activeDownloads = $download->getAllDownloads();
        } catch (\Exception $e) {
            logger()->error($e);

            return AjaxResponse::failed()->setMessage('Помилка скачування');
        }

        return AjaxResponse::success()->setData([
            'downloads' => $activeDownloads,
        ]);
    }

    public function stopDownload(Request $request, TorrentDownload $download)
    {
        try {
            $validData = $request->validate([
                'id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        $download->stopTorrent($validData['id']);
        $activeDownloads = $download->getAllDownloads();

        return AjaxResponse::success()->setData([
            'downloads' => $activeDownloads,
        ]);
    }

    public function runDownload(Request $request, TorrentDownload $download)
    {
        try {
            $validData = $request->validate([
                'id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        $download->runTorrent($validData['id']);
        $activeDownloads = $download->getAllDownloads();

        return AjaxResponse::success()->setData([
            'downloads' => $activeDownloads,
        ]);
    }

    public function getDownloads(TorrentDownload $download)
    {
        try {
            $activeDownloads = $download->getAllDownloads();
        } catch (\Exception $e) {
            logger()->error($e);

            return AjaxResponse::failed();
        }

        return AjaxResponse::success()->setData([
            'downloads' => $activeDownloads,
        ]);
    }

    public function deleteDownload(Request $request, TorrentDownload $download)
    {
        try {
            $validData = $request->validate([
                'id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        // clearDownload
        $result = $download->clearDownload($validData['id']);
        $activeDownloads = $download->getAllDownloads();

        return AjaxResponse::success()->setData([
            'result' => $result,
            'downloads' => $activeDownloads,
        ]);

    }

    // Films
    public function getFolder(Request $request, Films $films)
    {
        try {
            $validData = $request->validate([
                'path' => 'string',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        try {
            $path = ! empty($validData['path']) ? $validData['path'] : '';
            $folder = $films->getFolder($path);
        } catch (\Exception $e) {
            logger()->error($e);

            return AjaxResponse::failed();
        }

        return AjaxResponse::success()->setData([
            'folders' => $folder,
        ]);
    }

    public function delete(Request $request, Films $films)
    {
        try {
            $validData = $request->validate([
                'path' => 'required|string',
            ]);
        } catch (\Exception $e) {
            return AjaxResponse::failed()->setMessage('Validate error');
        }

        try {
            $folder = $films->delete($validData['path']);
        } catch (\Exception $e) {
            logger()->error($e);

            return AjaxResponse::failed();
        }

        if ($folder) {
            return AjaxResponse::success();
        }

        return AjaxResponse::failed();
    }
}
