<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'device_id',
        'user',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'bearing',
        'battery',
        'tracked_at',
        'payload',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'bearing' => 'float',
        'battery' => 'integer',
        'tracked_at' => 'datetime',
        'payload' => 'array',
    ];
}
