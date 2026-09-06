<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int process_id
 * @property string torrent_name
 * @property string folder_path
 * @property int loaded_percent
 */
class MediaDownload extends Model
{
    protected $fillable = [
        'process_id',
        'torrent_name',
        'folder_path',
        'loaded_percent',
    ];

    public static function saveTorrent(array $data)
    {
        $item = new self;
        $item->fill($data);
        $item->save();
    }
}
