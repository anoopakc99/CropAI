<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LandPreparationController extends Controller
{
 
public function index(Request $request)
{
    $user = Auth::user();
    $siteId = $user->site_id;
    $role = $user->role;
    
    // Determine selected site (Admin vs Non-admin)
    $selectedSiteId = $role == 1 ? $request->get('site_id') : $siteId;
    
    $sites = $role == 1 ? DB::table('master_sites')->pluck('site_name', 'id') : DB::table('master_sites')->pluck('site_name', 'id');
    
     if ($role == 1) {
        // Admin: get all blocks from master_land
        $blocks = DB::table('master_land')
            ->join('blocks as b', 'master_land.block_name', '=', 'b.id')
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    } else {
        $blocks = DB::table('master_land as ml')
            ->join('blocks as b', 'ml.block_name', '=', 'b.id')
            ->where('ml.site_id', $selectedSiteId)
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    }
    
    $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');
    
    $seasonList = DB::table('seasons')
    ->select(
        DB::raw('MIN(id) as id'), 
        'name'
    )
    ->when($request->filled('block_name'), function ($q) use ($request) {
        $q->where('block_id', $request->block_name);
    })
    ->when($request->filled('year'), function ($q) use ($request) {
        $q->where('year', $request->year);
    })
    ->groupBy('name')    
    ->orderBy('name')
    ->get();
    
    // Main query
    $query = DB::table('land_prepration as l')
        ->leftJoin('blocks', 'l.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'l.plot_name', '=', 'master_plots.id')
        ->leftJoin('master_soil_condition as sc', 'l.soil_condition_id', '=', 'sc.id')
        ->leftJoin('users as u', 'l.user_id', '=', 'u.id')
        ->leftJoin('manpower_type', 'l.manpower_type', '=', 'manpower_type.id')
        ->select(
            'l.*',
            'sc.soil_condition',
            'u.name as user_name',
            'manpower_type.type as manpower_type',
            'blocks.block_name as block_name',
            'master_plots.plot_name as plot_name',
            'master_plots.area as area'
        );
    
    // Apply site filter
    if ($role != 1 || $selectedSiteId) {
        $query->where('l.site_id', $selectedSiteId);
    }
    
    // Apply block filter
    if ($request->filled('block_name')) {
        $query->where('l.block_name', $request->block_name);
    }
    
    // Apply search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('l.plot_name', 'like', "%$search%")
              ->orWhere('l.area', 'like', "%$search%")
              ->orWhere('u.name', 'like', "%$search%");
        });
    }

    if ($request->filled('season_id')) {
                $query->where('l.season_id', $request->season_id);
            }
         if ($request->filled('year')) {
                $query->whereIn('l.season_id', function ($q) use ($request) {
                    $q->select('id')
                      ->from('seasons')
                      ->where('year', $request->year);
                });
            }
   
    // Get paginated result
    $data = $query->orderBy('l.date', 'desc')
                  ->paginate(10)
                  ->appends($request->all());
        
    // Machine and Tractor Names
    foreach ($data as $item) {
        $machineIds = array_filter(explode(',', $item->machine_id));
        $machines = DB::table('master_machine')
                      ->whereIn('id', $machineIds)
                      ->pluck('machine_name')
                      ->toArray();
        $item->machine_names = implode(', ', $machines);
        
        $tractorIds = array_filter(explode(',', $item->tractor_id));
        $tractors = DB::table('master_tractors')
                      ->whereIn('id', $tractorIds)
                      ->pluck('tractor_name')
                      ->toArray();
        $item->tractor_names = implode(', ', $tractors);
    }
    
    return view('land_preparation.index', compact('data', 'blocks', 'sites', 'selectedSiteId', 'role', 'financialYears',
            'seasonList'));
}
}
