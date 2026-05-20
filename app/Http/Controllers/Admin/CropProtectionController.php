<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CropProtectionController extends Controller
{
public function index(Request $request)
{
    $user = Auth::user();
     
    $siteId = $user->site_id;
    $role = $user->role;
 
    $selectedSiteId = $request->get('site_id', $siteId);
    $block = $request->get('block_name');
    $keyword = $request->get('search');

    // Get site list
    $sites = ($role == 1)
        ? DB::table('master_sites')->pluck('site_name', 'id')
        : DB::table('master_sites')->where('id', $siteId)->pluck('site_name', 'id');
    $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');

    // Get all machines and tractors
    $allMachines = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
    $allTractors = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();

    // Get distinct blocks
         // Block dropdown
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
    // Build query
    $query = DB::table('crop_protection')
        ->leftJoin('blocks', 'crop_protection.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'crop_protection.plot_name', '=', 'master_plots.id')
        ->leftJoin('master_chemical', 'crop_protection.chemical_id', '=', 'master_chemical.id')
        ->leftJoin('manpower_type', 'crop_protection.manpower_type', '=', 'manpower_type.id')
        ->select('crop_protection.*', 'master_chemical.chemical_name', 'manpower_type.type as manpower_type',
        'blocks.block_name as block_name',
        'master_plots.plot_name as plot_name',
        'master_plots.area as area');

    // Site filter
    if ($role == 1 && $request->filled('site_id')) {
        $query->where('crop_protection.site_id', $selectedSiteId);
    } elseif ($role != 1) {
        $query->where('crop_protection.site_id', $siteId);
    }

    // Block filter
    if ($block) {
        $query->where('crop_protection.block_name', $block);
    }

    // Search filter
    if ($keyword) {
        $query->where(function ($sub) use ($keyword) {
            $sub->where('crop_protection.plot_name', 'like', "%$keyword%")
                ->orWhere('crop_protection.application_method', 'like', "%$keyword%")
                ->orWhere('crop_protection.company', 'like', "%$keyword%")
                ->orWhere('crop_protection.dose', 'like', "%$keyword%")
                ->orWhere('crop_protection.application_stage_source', 'like', "%$keyword%");
        });
    }
     if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'crop_protection.site_id')
                  ->whereColumn('s.block_id', 'crop_protection.block_name')
                  ->whereColumn('s.plot_id', 'crop_protection.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('crop_protection.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'crop_protection.site_id')
                  ->whereColumn('s.block_id', 'crop_protection.block_name')
                  ->whereColumn('s.plot_id', 'crop_protection.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('crop_protection.date BETWEEN s.start_date AND s.end_date');
            });
        }

    $query = $query->orderBy('crop_protection.date', 'desc')->paginate(10)->appends($request->all());
 
    return view('crop_protection.index', compact(
        'query',
        'blocks',
        'block',
        'keyword',
        'allMachines',
        'allTractors',
        'sites',
        'selectedSiteId',
        'role',
        'financialYears'
    ));
}

}
