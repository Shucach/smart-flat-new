<?php

namespace App\Media;

use App\Models\MediaDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TorrentDownload extends AbstractMedia
{
    /**
     * @return string
     */
    public function setDownload(Request $request)
    {
        $rawFolderPath = $request->input('folder');
        $pathFolder = $this->basePath.$rawFolderPath;

        // Зберігаємо файл.
        $fulName = $request->file('torrent')->getClientOriginalName();
        $pathToTorrent = $pathFolder.$fulName;

        if (move_uploaded_file($_FILES['torrent']['tmp_name'], $pathToTorrent)) {
            // TODO: перевірити документацію на наявність демона та видалення торрент закачки.
            $command = 'aria2c --dir='.$pathFolder.' --torrent-file="'.$pathToTorrent.'" --allow-overwrite=true > "'.$pathFolder.$fulName.'.log" 2>&1 & echo $!';
            try {
                exec($command, $result);
                $data = [
                    'process_id' => (int) $result[0],
                    'torrent_name' => $fulName,
                    'folder_path' => $rawFolderPath,
                    'loaded_percent' => 0,
                ];
                MediaDownload::saveTorrent($data);
            } catch (\Exception $e) {
                logger()->error($e);
            }
        } else {
            return 'Помилка завантаження';
        }

        return '';
    }

    /**
     * Показуємо всі закачки. Активні\неактивні на роздачі
     */
    public function getAllDownloads(): array
    {
        $items = MediaDownload::query()->get();

        /** @var MediaDownload $item */
        $downloads = [];
        foreach ($items as $item) {
            $downloads[] = [
                'id' => $item->id,
                'process_id' => $item->process_id,
                'torrent_name' => $item->torrent_name,
                'folder_path' => $item->folder_path,
                'loaded_percent' => $item->loaded_percent,
            ];
        }

        return $downloads;
    }

    /**
     * @return string
     */
    public function stopTorrent(int $id): void
    {
        /** @var MediaDownload $item */
        $item = MediaDownload::query()->where('id', $id)->firstOrFail();

        $command = 'kill -9 '.$item->process_id;
        try {
            exec($command, $result, $return);
            $this->deleteFile($item->folder_path.$item->torrent_name.'.log');
        } catch (\Exception $e) {
            logger()->error($e);
        }

        DB::table('media_downloads')
            ->where('id', $item->id)
            ->update(['process_id' => 0]);
    }

    public function runTorrent(int $id): void
    {
        /** @var MediaDownload $item */
        $item = MediaDownload::query()->where('id', $id)->firstOrFail();

        $pathFolder = $this->basePath.$item->folder_path;
        $fulName = $item->torrent_name;
        $pathToTorrent = $pathFolder.$fulName;

        $command = 'aria2c --dir='.$pathFolder.' --torrent-file="'.$pathToTorrent.'" --allow-overwrite=true > "'.$pathFolder.$fulName.'.log" 2>&1 & echo $!';

        try {
            exec($command, $result);
            DB::table('media_downloads')
                ->where('id', $item->id)
                ->update(['process_id' => (int) $result[0]]);
        } catch (\Exception $e) {
            logger()->error($e);
        }
    }

    public function clearDownload(int $id): string
    {
        /** @var MediaDownload $item */
        $item = MediaDownload::query()
            ->where('id', $id)->firstOrFail();

        $results = '';
        if ($item->process_id) {
            $command = 'kill -9 '.$item->process_id;
            try {
                // Процес
                // Якщо $return == 0 в теорії все нормально пройшло
                exec($command, $result, $return);
                if ($return == 0) {
                    $results = "Процес: OK.\n";
                } else {
                    $results = "Процес: FALSE.\n";
                }
            } catch (\Exception $e) {
                $results = "Процес: FALSE.\n";
            }
        }

        // Прибираємо сміття

        // Torrent файл
        $tr = $this->deleteFile($item->folder_path.$item->torrent_name);
        if ($tr == 0) {
            $results .= "Torrent файл: OK. \n";
        } else {
            $results .= "Torrent файл: FALSE. \n";
        }

        // Лог завантаження
        $tl = $this->deleteFile($item->folder_path.$item->torrent_name.'.log');
        if ($tl == 0) {
            $results .= "Log файл: OK. \n";
        } else {
            $results .= "Log файл: FALSE. \n";
        }

        $item->delete();

        return $results;
    }

    /**
     * Перевіряємо статуси закачок та пишемо в БД якщо це скачування
     *
     * @return void
     */
    public function checkDownloadsStatus()
    {
        $items = MediaDownload::query()
            ->where('process_id', '>', 0)
            ->where('loaded_percent', '<', 100)
            ->get();

        /** @var MediaDownload $item */
        foreach ($items as $item) {
            try {
                // check if process still exist
                if (! file_exists("/proc/$item->process_id")) {
                    DB::table('media_downloads')
                        ->where('id', $item->id)
                        ->update(['process_id' => 0]);
                }

                $fullPath = $this->basePath.$item->folder_path.$item->torrent_name;

                $loaded_percent = $this->logParser($fullPath.'.log');
                DB::table('media_downloads')
                    ->where('id', $item->id)
                    ->update(['loaded_percent' => $loaded_percent]);
            } catch (\Exception $e) {
                logger()->error($e);
            }
        }
    }

    /**
     * Знаходимо крайнє значення рівня завантаження файла
     * і повертаємо в %
     */
    private function logParser(string $url): int
    {
        if (! file_exists($url)) {
            return 0;
        }
        $content = file_get_contents($url);
        preg_match_all('/\[#(.+?)\]/', $content, $matches);
        if (isset($matches[1])) {
            $valueEnd = end($matches[1]);
            if (strripos($valueEnd, 'GiB(')) {
                $dataArray = explode(' ', $valueEnd);
                if (isset($dataArray[1])) {
                    preg_match('/\((.+?)\)/', $dataArray[1], $percent);
                    if (isset($percent[1])) {
                        return (int) $percent[1];
                    }
                }
            } elseif (strripos($valueEnd, 'SEED(')) {
                // Старт сідування - свідчить про закінчення завантаження
                return 100;
            }

            return 0;
        }

        return 0;
    }
}
