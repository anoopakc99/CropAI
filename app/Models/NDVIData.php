<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NDVIData extends Model
{
    use HasFactory;

    protected $table = 'ndvi_data';

    protected $fillable = [
        'scene_id',
        'view_id',
        'date',
        'site_id',
        'plot_id',
        'ndvi_value',   // kept for backward compatibility
        'status',

        // EOS API statistics fields
        'average',
        'min',
        'max',
        'std',
        'variance',
        'median',
        'q1',
        'q3',
        'p10',
        'p90',
        'cloud_coverage',
    ];
}
