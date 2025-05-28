<?php

namespace App\Models;

use Core\System\Model;

class StreamLog extends Model
{
    protected $table = 'stream_logs';

    protected $fillable = [
        'stream_key',
        'action_name',
        'source_ip',
    ];
}