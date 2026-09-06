<?php

namespace App\Services;

final class ServerService
{
    /**
     * @return array
     */
    public function getSystemLoad()
    {
        $arrayData = [
            'cpu' => 2,
            'mem' => 3,
        ];

        exec('/usr/bin/top -b -n 1', $info);

        $result = [];
        if (! $info) {
            return $result;
        }

        foreach ($arrayData as $index => $value) {
            $td = explode(':', $info[$value]);
            $rd = explode(',', $td[1]);

            // CPU
            if ($value == 2) {
                $result['cpu'] = [
                    'use' => (float) $rd[0],
                ];
            }

            // Mem
            // 2 - used
            // 3 - buffer
            if ($value == 3) {
                $result['mem'] = [
                    'total' => (float) $rd[0],
                    'used' => (float) $rd[2],
                ];
            }
        }

        return $result;
    }

    public function getDiskInfo(array $path = [])
    {
        $defPath = [
            'sda1' => '/dev/sda1',
            'sdb3' => '/dev/sdb3',
        ];
        $path = ! $path ? $defPath : $path;

        $resultRaw = [];
        explode("\n", exec('df -h', $info));
        foreach ($info as $index => $value) {
            if ($index == 0) {
                continue;
            }
            $resultRaw[] = array_values(array_diff(explode(' ', $value), ['']));
        }

        // Build info
        $res = [];
        foreach ($path as $name => $pat) {
            foreach ($resultRaw as $searchArr) {
                if (in_array($pat, $searchArr)) {
                    $res[$name] = [
                        'size' => $searchArr[1],
                        'used' => $searchArr[2],
                        'avail' => $searchArr[3],
                        'use%' => $searchArr[4],
                    ];
                }
            }
        }

        return $res;
    }
}
