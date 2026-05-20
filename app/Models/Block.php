<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'blocks';
    protected $fillable = ['site_id', 'block_name'];

    public function masterPlots()
    {
        return $this->hasMany(MasterPlot::class, 'block_id');
    }
}
