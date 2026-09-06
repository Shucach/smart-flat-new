<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string value
 */
class Security extends Model
{
    const STATUS_NIGHT = 0;

    const STATUS_EVENT = 1;

    const STATUS_OFF = 2;

    const STATUS_ON = 3;

    const STATUS_WATCHER = 4;

    protected $fillable = [
        'name',
        'value',
    ];
}
