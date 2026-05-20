<?php

// app/Models/WeatherLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherLog extends Model
{
    protected $fillable = [
        'site_id','city_name', 'lat', 'lon', 'temp', 'feels_like', 'temp_min', 'temp_max',
        'humidity', 'pressure', 'wind_speed', 'wind_deg', 'clouds', 'precipitation','source',
        'weather_main', 'weather_desc', 'sunrise', 'sunset', 'recorded_at'
    ];
}
