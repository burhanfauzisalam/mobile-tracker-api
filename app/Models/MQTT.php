<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MQTT extends Model
{
    /**
     * Explicitly map to the mqtt_conn table that stores MQTT connection metadata.
     */
    protected $table = 'mqtt_conn';

    /**
     * Allow mass-assignment for all columns since this model is read-only in our API.
     */
    protected $guarded = [];
}
