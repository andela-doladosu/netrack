<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    protected $fillable = [
        'network_provider', 'user_location', 'ping_time', 'upload_speed',
        'download_speed', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
