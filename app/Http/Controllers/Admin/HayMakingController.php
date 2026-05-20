<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HayMakingController extends Controller
{
  
 
public function index(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $siteId = $user->site_id;
    
    //1. Load all sites (for admin dropdown)
    $sites = DB::table('master_sites')->pluck('site_name', 'id');
    
    //2. Determine selected site
    $selectedSiteId = $request->input('site_id');
    if ($role != 1) {
        $selectedSiteId = $siteId;
    }
    
    // 3. Grab distinct blocks for dropdown filter from hay_making table
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
    
    // 4. Update total_yield_mt for all hay_making records using sequential logic
    $this->updateTotalYieldMt($selectedSiteId);
    
    // 5. Get Current Remaining Yield per seed_name from harvest_store_manage 
    // (product_id = 1 for harvested products, matching by seed_name and site)
    $currentRemainingYieldsQuery = DB::table('harvest_store_manage')
        ->select('seed_id', DB::raw('SUM(yield_mt) as current_remaining_yield'))
        ->where('site_id', $selectedSiteId)
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
  //  dd($currentRemainingYields);
    // 6. Main query - Get data from hay_making table
    $q = DB::table('hay_making as h')
        ->leftJoin('blocks', 'h.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'h.plot_name', '=', 'master_plots.id')
        ->leftJoin('users', 'h.user_id', '=', 'users.id')
        ->leftJoin('manpower_type', 'h.manpower_type', '=', 'manpower_type.id')
        ->leftJoin('master_seed as ms', 'h.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'h.*', 
            'users.name as user_name',
            'manpower_type.type as manpower_type',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            'blocks.block_name as block_name',
            'master_plots.plot_name as plot_name',
            'master_plots.area as area'
        );
    
    if (!empty($selectedSiteId)) {
        $q->where('h.site_id', $selectedSiteId);
    }
    
    // 7. Filters
    if ($request->filled('block_name')) {
        $q->where('h.block_name', $request->block_name);
    }
    
    if ($request->filled('search')) {
        $term = '%' . $request->search . '%';
        $q->where(function ($sub) use ($term) {
            $sub->where('h.plot_name', 'like', $term)
                ->orWhere('h.block_name', 'like', $term)
                ->orWhere('h.seed_name', 'like', $term)
                ->orWhere('h.hay_making_method', 'like', $term)
                ->orWhere('users.name', 'like', $term);
        });
    }
    
    // Session filter - FIXED: Using correct query variable $q instead of $query
    if ($request->filled('season_name')) {
            $q->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'h.site_id')
                  ->whereColumn('s.block_id', 'h.block_name')
                  ->whereColumn('s.plot_id', 'h.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('h.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $q->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'h.site_id')
                  ->whereColumn('s.block_id', 'h.block_name')
                  ->whereColumn('s.plot_id', 'h.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('h.date BETWEEN s.start_date AND s.end_date');
            });
        }
    
    $haymakings = $q->orderBy('h.harvest_date', 'desc')
        ->paginate(10)
        ->appends($request->only(['block_name', 'search', 'site_id', 'session']));
    
    // 9. Load machines and tractors
    $allMachines = DB::table('master_machine')->pluck('machine_name', 'id');
    $allTractors = DB::table('master_tractors')->pluck('tractor_name', 'id');
    
    // 10. Process display fields and calculate yield metrics
    foreach ($haymakings as $haymaking) {
        
        // Calculate yield metrics based on seed_name matching
        $seedName = $haymaking->seed_id;
        
        // NEW LOGIC: Total Available Yield from hay_making table total_yield_mt
        $haymaking->total_available_yield = $haymaking->total_yield_mt ?? 0;
        
        // Required Yield MT (from hay_making table yield_mt)
        $haymaking->required_yield_mt = $haymaking->yield_mt ?? 0;
        
        // Adjusted Yield MT (Required Yield MT से 80% कम, मतलब 20% बचे)
        $haymaking->adjusted_yield_mt = $haymaking->required_yield_mt * 0.2;
        
        // NEW LOGIC: Remaining Yield MT from current harvest_store_manage yield_mt
        $haymaking->remaining_yield_mt = $currentRemainingYields[$seedName] ?? 0;
        
        // Handle machine names
        if (!empty($haymaking->machine_id)) {
            $machineIds = explode(',', $haymaking->machine_id);
            $haymaking->machine_names_display = collect($machineIds)->map(function($id) use ($allMachines) {
                return $allMachines[trim($id)] ?? null;
            })->filter()->implode(', ') ?: '-';
        } else {
            $haymaking->machine_names_display = '-';
        }
        
        // Handle tractor names
        if (!empty($haymaking->tractor_id)) {
            $tractorIds = explode(',', $haymaking->tractor_id);
            $haymaking->tractor_names_display = collect($tractorIds)->map(function($id) use ($allTractors) {
                return $allTractors[trim($id)] ?? null;
            })->filter()->implode(', ') ?: '-';
        } else {
            $haymaking->tractor_names_display = '-';
        }
        
        // Handle major maintenance JSON
        $haymaking->parsed_major_maintenance = [];
        if ($haymaking->major_maintenance) {
            $decoded = json_decode($haymaking->major_maintenance, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $haymaking->parsed_major_maintenance = $decoded;
            }
        }
        
        // Map harvest_date from hay_making table
        $haymaking->harvest_date = $haymaking->harvest_date;
    }
 //   dd($haymakings);
    // Handle AJAX requests
    if ($request->ajax()) {
        return view('haymaking.partials.table', compact('haymakings'))->render();
    }
    
    // Return view
    return view('haymaking.index', compact(
        'blocks',
        'haymakings',
        'sites',
        'selectedSiteId',
        'role',
        'financialYears'
    ));
}

/**
 * Update total_yield_mt in hay_making table
 * Logic: total_yield_mt should show available yield at the time of each activity
 */
private function updateTotalYieldMt($selectedSiteId = null)
{
    // Get all hay_making records ordered by date to calculate sequential remaining yields
    $haymakingQuery = DB::table('hay_making')
        ->select('*')
        ->orderBy('harvest_date', 'asc')
        ->orderBy('id', 'asc');
    
    if (!empty($selectedSiteId)) {
        $haymakingQuery->where('site_id', $selectedSiteId);
    }
    
    $haymakingRecords = $haymakingQuery->get();
    
    // Group by seed_name and site_id to process each combination separately
    $groupedRecords = $haymakingRecords->groupBy(function($item) {
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
        $totalUsedInActivities = DB::table('hay_making')
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
            DB::table('hay_making')
                ->where('id', $record->id)
                ->update(['total_yield_mt' => $availableAtTimeOfActivity]);
            
            // Add current activity's usage to cumulative
            $cumulativeUsed += $record->yield_mt;
        }
    }
}


}    
