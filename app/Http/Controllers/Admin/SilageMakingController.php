<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class SilageMakingController extends Controller
{
 
public function index(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $siteId = $user->site_id;
    
    // 1. Load all sites (for admin dropdown)
    $sites = DB::table('master_sites')->pluck('site_name', 'id');
    
    // 2. Determine selected site
    $selectedSiteId = $request->input('site_id');
    if ($role != 1) {
        $selectedSiteId = $siteId;
    }
    
     if ($role == 1) {
        // Admin: get all blocks from master_land
        $blocks = DB::table('master_land')
            ->join('blocks as b', 'master_land.block_name', '=', 'b.id')
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    } else {
        // Other roles: get blocks only for user's site from harvest_store_manage
        $blocks = DB::table('master_land as ml')
            ->join('blocks as b', 'ml.block_name', '=', 'b.id')
            ->where('ml.site_id', $selectedSiteId)
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    }
    
    // 4. Update total_yield_mt for all silage_making records using same logic as hay_making
    $this->updateSilageTotalYieldMt($selectedSiteId);
    
    // 5. Get Current Remaining Yield per seed_name from harvest_store_manage 
    // (product_id = 1 for harvested products, matching by seed_name and site)
    $currentRemainingYieldsQuery = DB::table('harvest_store_manage')
        ->select('seed_id', DB::raw('SUM(yield_mt) as current_remaining_yield'))
        ->where('product_id', 1) // Only harvested products
        ->groupBy('seed_id');
    
    if (!empty($selectedSiteId)) {
        $currentRemainingYieldsQuery->where('site_id', $selectedSiteId);
    }
    $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');
    
    $currentRemainingYields = $currentRemainingYieldsQuery->pluck('current_remaining_yield', 'seed_id');
    
    // 6. Main query - Get data from silage_making table
    $q = DB::table('silage_making as sm')
        ->leftJoin('blocks', 'sm.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'sm.plot_name', '=', 'master_plots.id')
        ->leftJoin('master_seed as ms', 'sm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->leftJoin('users', 'sm.user_id', '=', 'users.id')
        ->leftJoin('manpower_type', 'sm.manpower_type', '=', 'manpower_type.id')
        ->select(
            'sm.*', 
            'users.name as user_name',
             'manpower_type.type as manpower_type',
             DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
             'blocks.block_name as block_name',
             'master_plots.plot_name as plot_name',
             'master_plots.area as area');
    
    if (!empty($selectedSiteId)) {
        $q->where('sm.site_id', $selectedSiteId);
    }
    
    // 7. Filters
    if ($request->filled('block_name')) {
        $q->where('sm.block_name', $request->block_name);
    }
    
    // SEASION FILTER
      if ($request->filled('season_name')) {
            $q->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'sm.site_id')
                  ->whereColumn('s.block_id', 'sm.block_name')
                  ->whereColumn('s.plot_id', 'sm.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('sm.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $q->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'sm.site_id')
                  ->whereColumn('s.block_id', 'sm.block_name')
                  ->whereColumn('s.plot_id', 'sm.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('sm.date BETWEEN s.start_date AND s.end_date');
            });
        }
    
    if ($request->filled('search')) {
        $term = '%' . $request->search . '%';
        $q->where(function ($sub) use ($term) {
            $sub->where('sm.plot_name', 'like', $term)
                ->orWhere('sm.block_name', 'like', $term)
                ->orWhere('sm.seed_name', 'like', $term)
                ->orWhere('sm.silage_making_method', 'like', $term)
                ->orWhere('users.name', 'like', $term);
        });
    }
    
    // 8. Paginate
    $silagemakings = $q->orderBy('sm.harvest_date', 'desc')
        ->paginate(10)
        ->appends($request->only(['block_name', 'search', 'site_id', 'session']));
    
    // 9. Load machines and tractors
    $allMachines = DB::table('master_machine')->pluck('machine_name', 'id');
    $allTractors = DB::table('master_tractors')->pluck('tractor_name', 'id');
    
    // 10. Process display fields and calculate yield metrics
    foreach ($silagemakings as $silagemaking) {
        
        // Calculate yield metrics based on seed_name matching
        $seedName = $silagemaking->seed_id;
        
        $silagemaking->total_available_yield = $silagemaking->total_yield_mt ?? 0;
        
        $silagemaking->required_yield_mt = $silagemaking->yield_mt ?? 0;
        
        $silagemaking->adjusted_yield_mt = $silagemaking->required_yield_mt * 0.8;
        
        $silagemaking->remaining_yield_mt = $currentRemainingYields[$seedName] ?? 0;
        
        // Handle machine names
        if (!empty($silagemaking->machine_id)) {
            $machineIds = explode(',', $silagemaking->machine_id);
            $silagemaking->machine_names_display = collect($machineIds)->map(function($id) use ($allMachines) {
                return $allMachines[trim($id)] ?? null;
            })->filter()->implode(', ') ?: '-';
        } else {
            $silagemaking->machine_names_display = '-';
        }
        
        // Handle tractor names
        if (!empty($silagemaking->tractor_id)) {
            $tractorIds = explode(',', $silagemaking->tractor_id);
            $silagemaking->tractor_names_display = collect($tractorIds)->map(function($id) use ($allTractors) {
                return $allTractors[trim($id)] ?? null;
            })->filter()->implode(', ') ?: '-';
        } else {
            $silagemaking->tractor_names_display = '-';
        }
        
        // Handle major maintenance JSON
        $silagemaking->parsed_major_maintenance = [];
        if ($silagemaking->major_maintenance) {
            $decoded = json_decode($silagemaking->major_maintenance, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $silagemaking->parsed_major_maintenance = $decoded;
            }
        }
        
        // Map harvest_date from silage_making table
        $silagemaking->harvest_date = $silagemaking->harvest_date;
    }
    
    // Handle AJAX requests
    if ($request->ajax()) {
        return view('silagemaking.partials.table', compact('silagemakings'))->render();
    }
    
    // eturn view
    return view('silagemaking.index', compact(
        'blocks',
        'silagemakings',
        'sites',
        'selectedSiteId',
        'role',
        'financialYears'
    ));
}

/**
 * Update total_yield_mt in silage_making table
 * Logic: total_yield_mt should show available yield at the time of each activity
 */
private function updateSilageTotalYieldMt($selectedSiteId = null)
{
    // Get all silage_making records ordered by date to calculate sequential remaining yields
    $silageQuery = DB::table('silage_making')
        ->select('*')
        ->orderBy('harvest_date', 'asc')
        ->orderBy('id', 'asc');
    
    if (!empty($selectedSiteId)) {
        $silageQuery->where('site_id', $selectedSiteId);
    }
    
    $silageRecords = $silageQuery->get();
    
    // Group by seed_name and site_id to process each combination separately
    $groupedRecords = $silageRecords->groupBy(function($item) {
        return $item->seed_name . '_' . $item->site_id;
    });
    
    foreach ($groupedRecords as $key => $records) {
        $firstRecord = $records->first();
        $seedName = $firstRecord->seed_id;
        $siteId = $firstRecord->site_id;
        
        // Get original harvest yield (before any activities)
        $originalYield = DB::table('harvest_store_manage')
            ->where('seed_id', $seedName)
            ->where('site_id', $siteId)
            ->where('product_id', 1) // Only harvested products
            ->sum('yield_mt');
        
        // Get total used in all activities to calculate original yield
        $totalUsedInActivities = DB::table('silage_making')
            ->where('seed_id', $seedName)
            ->where('site_id', $siteId)
            ->sum('yield_mt');
        
        // Calculate original yield (current available + total used)
        $originalTotalYield = $originalYield + $totalUsedInActivities;
        
        // Process each record sequentially to calculate available yield at time of activity
        $cumulativeUsed = 0;
        
        foreach ($records as $record) {
            // Available yield at the time of this activity = original - cumulative used before this activity
            $availableAtTimeOfActivity = $originalTotalYield - $cumulativeUsed;
            
            // Update this record with the available yield at time of activity
            DB::table('silage_making')
                ->where('id', $record->id)
                ->update(['total_yield_mt' => $availableAtTimeOfActivity]);
            
            // Add current activity's usage to cumulative
            $cumulativeUsed += $record->yield_mt;
        }
    }
}


}
