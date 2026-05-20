<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoilMoisture extends Model
{
    protected $table = 'soil_moistures';

    protected $fillable = [
        'scene_id',
        'view_id',
        'site_id',
        'plot_id',
        'date',
        'q1',
        'q3',
        'max',
        'min',
        'p10',
        'p90',
        'std',
        'median',
        'average',
        'variance'
    ];

    protected $casts = [
        'date' => 'date',
        'q1' => 'float',
        'q3' => 'float',
        'max' => 'float',
        'min' => 'float',
        'p10' => 'float',
        'p90' => 'float',
        'std' => 'float',
        'median' => 'float',
        'average' => 'float',
        'variance' => 'float'
    ];
}
