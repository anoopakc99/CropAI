<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPlot extends Model
{
    protected $table = 'master_plots';
    protected $fillable = ['block_id', 'plot_name'];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }
}
