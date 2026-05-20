<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostIrrigationController extends Controller
{
    public function index(Request $request)
    {
        // Get all unique block names for dropdown
        $blocks = DB::table('post_irrigation')
            ->select('block_name')
            ->distinct()
            ->orderBy('block_name')
            ->get();

        // Get selected block from request or use the first one
        $selectedBlock = $request->input('block', $blocks->first()->block_name ?? null);
        
        // Search term
        $search = $request->input('search', '');

        // Query with filters
        $query = DB::table('post_irrigation')
            ->leftJoin('master_irrigation_types', 'post_irrigation.irrigation_type_id', '=', 'master_irrigation_types.id')
            ->leftJoin('water_sources', 'post_irrigation.water_source_id', '=', 'water_sources.id')
            ->select(
                'post_irrigation.id',
                'post_irrigation.block_name',
                'post_irrigation.plot_name',
                'post_irrigation.area_acre',
                'post_irrigation.irrigation_no',
                'master_irrigation_types.type_name as irrigation_type',
                'post_irrigation.irrigation_date',
                'water_sources.name as water_source',
                'post_irrigation.capacity_lph as capacity',
                'post_irrigation.day_ofter_swowing', 
                'post_irrigation.electricity_units',
                'post_irrigation.electricity_cost',
                'post_irrigation.hours_used as consumption_time',
                DB::raw('CONCAT(post_irrigation.unskilled_count + post_irrigation.semi_skilled_1_count + post_irrigation.semi_skilled_2_count) as man_power'),
                'post_irrigation.major_maintenance'
            );

        // Apply block filter if selected
        if ($selectedBlock) {
            $query->where('post_irrigation.block_name', $selectedBlock);
        }

        // Apply search if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('post_irrigation.plot_name', 'like', "%$search%")
                    ->orWhere('post_irrigation.irrigation_no', 'like', "%$search%")
                    ->orWhere('post_irrigation.major_maintenance', 'like', "%$search%");
            });
        }

        // Get paginated results
        $irrigationData = $query->orderBy('post_irrigation.irrigation_date', 'desc')
            ->paginate(10);

        return view('post-irrigation.index', compact('irrigationData', 'blocks', 'selectedBlock', 'search'));
    }
}