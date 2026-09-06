<?php

namespace App\Media;

abstract class AbstractMedia
{
    protected string $basePath = '/media/nfs/';

    /**
     * @return \Exception|int
     */
    public function deleteFolder(string $folderPath)
    {
        $fullPath = $this->buildFullPath($folderPath);

        $command = 'rm -R "'.$fullPath.'"';
        try {
            exec($command, $result, $return);

            return $return;
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @return \Exception|int
     */
    public function deleteFile(string $filePath)
    {
        $fullPath = $this->buildFullPath($filePath);

        $command = 'rm "'.$fullPath.'"';
        try {
            exec($command, $result, $return);

            return $return;
        } catch (\Exception $e) {
            logger()->error($e);

            return $e;
        }
    }

    /**
     * @return bool
     */
    public function delete($path)
    {
        $fulPath = realpath($this->buildFullPath($path));

        if (is_dir($fulPath)) {
            $tr = $this->deleteFolder($path);
        } else {
            $tr = $this->deleteFile($path);
        }

        if ($tr == 0) {
            return true;
        } else {
            return false;
        }
    }

    private function buildFullPath(string $path): string
    {
        $fullPath = $this->basePath.$path;

        return str_replace('//', '/', $fullPath);
    }
}
