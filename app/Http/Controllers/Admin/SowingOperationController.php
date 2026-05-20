<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SowingOperationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $defaultSiteId = $user->site_id;

        // Get sites
        if ($role == 1) {
            // Admins get all sites
            $sites = DB::table('master_sites')->pluck('site_name', 'id');
        } else {
            // Non-admins get only their own site name
            $sites = DB::table('master_sites')
                        ->where('id', $defaultSiteId)
                        ->pluck('site_name', 'id');
        }

        // Site selection
        $selectedSiteId = $request->get('site_id', $defaultSiteId);

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
        
        $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');
    
        // Main query
        $query = DB::table('showing_oprations as so')
            ->select(
                'so.*',
                's.name as seed_name',
                'ms.variety_of_seed',
                'sm.name as sowing_method',
                'ms.purpose',
                'u.name as user_name',
                'manpower_type.type as manpower_type',
                'blocks.block_name as block_name',
                'master_plots.plot_name as plot_name',
                'master_plots.area as area'
            )
            ->leftJoin('blocks', 'so.block_name', '=', 'blocks.id')
            ->leftJoin('master_plots', 'so.plot_name', '=', 'master_plots.id')
            ->leftJoin('master_seed as ms', 'so.seed_id', '=', 'ms.id')
            ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
            ->leftJoin('sowing_method as sm', 'so.sowing_method_id', '=', 'sm.id')
            ->leftJoin('users as u', 'so.user_id', '=', 'u.id')
            ->leftJoin('manpower_type', 'so.manpower_type', '=', 'manpower_type.id');

        if ($role != 1) {
            $query->where('so.site_id', $defaultSiteId);
        } elseif ($request->filled('site_id')) {
            $query->where('so.site_id', $selectedSiteId);
        }

        if ($request->filled('block_name')) {
            $query->where('so.block_name', $request->block_name);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('so.plot_name', 'like', $search)
                  ->orWhere('so.variety', 'like', $search)
                  ->orWhere('so.purpose_name', 'like', $search)
                  ->orWhere('s.name', 'like', $search)
                  ->orWhere('u.name', 'like', $search);
            });
        }
        if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'so.site_id')
                  ->whereColumn('s.block_id', 'so.block_name')
                  ->whereColumn('s.plot_id', 'so.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('so.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'so.site_id')
                  ->whereColumn('s.block_id', 'so.block_name')
                  ->whereColumn('s.plot_id', 'so.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('so.date BETWEEN s.start_date AND s.end_date');
            });
        }
        $operations = $query->orderBy('so.id', 'desc')
                            ->paginate(10)
                            ->appends($request->all());
                            // Session filter
        
        $machineIds = [];
        $tractorIds = [];
        foreach ($operations as $op) {
            if (!empty($op->machine_id)) {
                $machineIds = array_merge($machineIds, array_map('trim', explode(',', $op->machine_id)));
            }
            if (!empty($op->tractor_id)) {
                $tractorIds = array_merge($tractorIds, array_map('trim', explode(',', $op->tractor_id)));
            }
        }

        $machines = DB::table('master_machine')->whereIn('id', array_unique($machineIds))->pluck('machine_name', 'id');
        $tractors = DB::table('master_tractors')->whereIn('id', array_unique($tractorIds))->pluck('tractor_name', 'id');
        $manpower = DB::table('master_manpower')->pluck('category', 'id');
 
        return view('sowing.sowing_operations', compact(
            'blocks',
            'operations',
            'manpower',
            'machines',
            'tractors',
            'sites',
            'selectedSiteId',
            'role',
            'financialYears'
        ));
    }
}
