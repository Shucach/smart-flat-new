<?php

namespace App\Media;

final class Films extends AbstractMedia
{
    public function getFolder($path = ''): array
    {
        $dir = realpath($this->basePath.$path);
        if (! $dir) {
            return [];
        }
        $dh = scandir($dir);

        $return = [];
        if ($path && $path != '/') {
            $arrP = explode('/', trim($path, '/'));
            if (count($arrP) > 1) {
                $arrP = array_slice($arrP, 0, -1);
                $pathBack = implode('/', $arrP);
            } else {
                $pathBack = '/';
            }
            $return[] = [
                'name' => '<- Назад',
                'path' => $pathBack,
                'is_folder' => true,
                'is_file' => false,
                'is_can_delete' => false,
            ];
        }

        foreach ($dh as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (str_ends_with($item, '.torrent') || str_ends_with($item, '.log') || str_ends_with($item, '.aria2')) {
                continue;
            }

            if (is_dir($dir.'/'.$item)) {
                $return[] = [
                    'name' => $item,
                    'path' => $path.'/'.$item,
                    'is_folder' => true,
                    'is_file' => false,
                    'is_can_delete' => true,
                ];
            } else {
                $return[] = [
                    'name' => $item,
                    'path' => $path.'/'.$item,
                    'is_folder' => false,
                    'is_file' => true,
                    'is_can_delete' => true,
                ];
            }
        }

        return $return;
    }
}
