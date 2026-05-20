<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InterCultureController extends Controller
{
public function index(Request $request)
    {
        $user = Auth::user();
        $defaultSiteId = $user->site_id;
        $role = $user->role;
       
        $selectedSiteId = $request->get('site_id', $defaultSiteId);

        $sites = ($role == 1)
            ? DB::table('master_sites')->pluck('site_name', 'id')
            : DB::table('master_sites')->where('id', $defaultSiteId)->pluck('site_name', 'id');
            
            

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
        $block = $request->get('block_name');

        $machines = DB::table('master_machine')->pluck('machine_name', 'id');
        $tractors = DB::table('master_tractors')->pluck('tractor_name', 'id');
         $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->where('site_id', $defaultSiteId)
        ->orderBy('year', 'desc')
        
        ->pluck('year');
        $query = DB::table('inter_culture')
            ->leftJoin('blocks', 'inter_culture.block_name', '=', 'blocks.id')
            ->leftJoin('master_plots', 'inter_culture.plot_name', '=', 'master_plots.id')
            ->leftJoin('users', 'inter_culture.user_id', '=', 'users.id')
            ->leftJoin('manpower_type', 'inter_culture.manpower_type', '=', 'manpower_type.id')
            ->select('inter_culture.*', 'users.name as user_name','manpower_type.type as manpower_type',
            'blocks.block_name as block_name',
             'master_plots.plot_name as plot_name',
             'master_plots.area as area');

        if ($role == 1 && $request->filled('site_id')) {
            $query->where('inter_culture.site_id', $selectedSiteId);
        } else {
            $query->where('inter_culture.site_id', $defaultSiteId);
        }

        if ($block) {
            $query->where('inter_culture.block_name', $block);
        }
        if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'inter_culture.site_id')
                  ->whereColumn('s.block_id', 'inter_culture.block_name')
                  ->whereColumn('s.plot_id', 'inter_culture.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('inter_culture.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'inter_culture.site_id')
                  ->whereColumn('s.block_id', 'inter_culture.block_name')
                  ->whereColumn('s.plot_id', 'inter_culture.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('inter_culture.date BETWEEN s.start_date AND s.end_date');
            });
        }

$query = $query->orderBy('inter_culture.date', 'DESC')->paginate(10)->appends($request->all());

        
     

        return view('inter_culture.index', compact(
            'query',
            'blocks',
            'block',
            'machines',
            'tractors',
            'sites',
            'selectedSiteId',
            'role',
            'financialYears'
        ));
    }

    public function search(Request $request)
    {
        $user = Auth::user();
        $siteId = $user->site_name;
        $role = $user->role;

        $selectedSiteId = $request->get('site_id', $siteId);
        $block = $request->get('block_name');
        $keyword = $request->get('keyword');

        $machines = DB::table('master_machine')->pluck('machine_name', 'id');
        $tractors = DB::table('master_tractors')->pluck('tractor_name', 'id');

        $query = DB::table('inter_culture')
            ->leftJoin('users', 'inter_culture.user_id', '=', 'users.id')
            ->select('inter_culture.*', 'users.name as user_name');

        if ($role == 1 && $request->filled('site_id')) {
            $query->where('inter_culture.site_id', $selectedSiteId);
        } else {
            $query->where('inter_culture.site_id', $siteId);
        }

        if ($block) {
            $query->where('inter_culture.block_name', $block);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('inter_culture.plot_name', 'like', "%$keyword%")
                    ->orWhere('inter_culture.activity_type', 'like', "%$keyword%")
                    ->orWhere('inter_culture.date_activity', 'like', "%$keyword%");
            });
        }

        $query = $query->orderBy('inter_culture.id', 'DESC')->paginate(10)->appends($request->all());
        
        // Session filter
        if ($request->filled('session')) {
            $session = $request->session;
            switch ($session) {
                case 'kharif':
                    $query->whereMonth('pre.date', '>=', 6)
                          ->whereMonth('pre.date', '<=', 10);
                    break;
                case 'rabi':
                    $query->where(function($q) {
                        $q->whereMonth('pre.date', '>=', 11)
                          ->orWhereMonth('pre.date', '<=', 3);
                    });
                    break;
                case 'zaid':
                    $query->whereMonth('pre.date', '>=', 4)
                          ->whereMonth('pre.date', '<=', 6);
                    break;
            }
        }

        $sites = ($role == 1)
            ? DB::table('master_sites')->pluck('site_name', 'id')
            : DB::table('master_sites')->where('id', $siteId)->pluck('site_name', 'id');

       
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
 
        return view('inter_culture.index', compact(
            'query',
            'blocks',
            'block',
            'machines',
            'tractors',
            'sites',
            'selectedSiteId',
            'role'
        ));
    }




    //=================hARVEST DATA SHOW IN TABLES

 
public function harvestList(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $defaultSiteId = $user->site_id;

    // Selected site from request or user's site
    $selectedSiteId = $request->get('site_id', $defaultSiteId);

    // Get sites for dropdown (only admin)
    $sites = ($role == 1)
        ? DB::table('master_sites')->pluck('site_name', 'id')
        : DB::table('master_sites')->where('id', $defaultSiteId)->pluck('site_name', 'id');
        
    $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');
    // Base query with user join
    $query = DB::table('harvesting_update as hu')
        ->leftJoin('master_seed as ms', 'hu.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->leftJoin('blocks', 'hu.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'hu.plot_name', '=', 'master_plots.id')
        ->leftJoin('users', 'hu.user_id', '=', 'users.id')
        ->leftJoin('manpower_type', 'hu.manpower_type', '=', 'manpower_type.id')
        ->select('hu.*',
                DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
                'users.name as user_name', 'manpower_type.type as manpower_type',
                'blocks.block_name as block_name',
                'master_plots.plot_name as plot_name',
                'master_plots.area as area');

    // Apply filters
    if ($role == 1 && $request->filled('site_id')) {
        $query->where('hu.site_id', $selectedSiteId);
    } else {
        $query->where('hu.site_id', $defaultSiteId);
    }

    if ($request->block_name) {
        $query->where('hu.block_name', $request->block_name);
    }

    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('hu.plot_name', 'like', '%' . $request->search . '%')
              ->orWhere('hu.harvest_method', 'like', '%' . $request->search . '%')
              ->orWhere('hu.harvest_purpose', 'like', '%' . $request->search . '%');
        });
    }
     // Seasion filter
      if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'hu.site_id')
                  ->whereColumn('s.block_id', 'hu.block_name')
                  ->whereColumn('s.plot_id', 'hu.plot_name')
                  ->where('s.name', $request->season_name)
                  ->whereRaw('hu.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'hu.site_id')
                  ->whereColumn('s.block_id', 'hu.block_name')
                  ->whereColumn('s.plot_id', 'hu.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('hu.date BETWEEN s.start_date AND s.end_date');
            });
        }

    // Clone query to calculate total yield before pagination
    $totalYieldAll = $query->clone()->sum('yield_mt');

    // Paginate the results
    $harvests = $query->orderBy('hu.id', 'desc')
                      ->paginate(10)
                      ->appends($request->all());

    // Transform results
    $harvests->getCollection()->transform(function ($item) {
        $machineIds = explode(',', $item->machine_id);
        $machines = DB::table('master_machine')
            ->whereIn('id', $machineIds)
            ->pluck('machine_name')
            ->toArray();
        $item->machine_names = implode(', ', $machines);

        $tractorIds = explode(',', $item->tractor_id);
        $tractors = DB::table('master_tractors')
            ->whereIn('id', $tractorIds)
            ->pluck('tractor_name')
            ->toArray();
        $item->tractor_names = implode(', ', $tractors);

        $maintenanceData = json_decode($item->major_maintenance, true);
        $item->major_maintenance_text = is_array($maintenanceData)
            ? implode(', ', array_column($maintenanceData, 'spare_part'))
            : '-';

        return $item;
    });

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
 
    return view('harvest.index', compact(
        'harvests',
        'blocks',
        'sites',
        'selectedSiteId',
        'role',
        'totalYieldAll',
        'financialYears'
    ));
}



}