<?php

namespace App\Models;

use Core\System\Model;

class StreamKey extends Model
{
    protected $table = 'stream_keys';

    protected $fillable = [
        'user',
        'stream_key',
        'active',
    ];
}