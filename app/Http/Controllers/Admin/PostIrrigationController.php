<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PostIrrigationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $defaultSiteId = $user->site_id;

        // Handle selected site
        $selectedSiteId = $request->get('site_id', $defaultSiteId);

        // Site list for dropdown (admin only)
        $sites = ($role == 1)
            ? DB::table('master_sites')->pluck('site_name', 'id')
            : DB::table('master_sites')->where('id', $defaultSiteId)->pluck('site_name', 'id');

        // Blocks dropdown filter
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
            ->where('site_id', $selectedSiteId)
            ->orderBy('year', 'desc')
            
            ->pluck('year');
    
    

        $selectedBlock = $request->input('block_name', null);  
        $search = $request->input('search', '');

        // Main data query
        $query = DB::table('post_irrigation')
            ->leftJoin('master_irrigation_types', 'post_irrigation.irrigation_type_id', '=', 'master_irrigation_types.id')
            ->leftJoin('water_sources', 'post_irrigation.water_source_id', '=', 'water_sources.id')
            ->leftJoin('blocks', 'post_irrigation.block_name', '=', 'blocks.id')
            ->leftJoin('master_plots', 'post_irrigation.plot_name', '=', 'master_plots.id')
            ->leftJoin('users', 'post_irrigation.user_id', '=', 'users.id')
            ->leftJoin('manpower_type', 'post_irrigation.manpower_type', '=', 'manpower_type.id')
            ->select(
                'post_irrigation.id',
                 'post_irrigation.crop_id',
                'post_irrigation.block_name',
                'post_irrigation.total_cost',
                'post_irrigation.plot_name',
                'post_irrigation.area',
                'post_irrigation.area_covered',
                'post_irrigation.irrigation_no',
                'master_irrigation_types.type_name as irrigation_type',
                'post_irrigation.date',
                'water_sources.name as water_source',
                'post_irrigation.capacity_lph as capacity',
                'post_irrigation.day_ofter_swowing',
                'post_irrigation.electricity_units',
                'post_irrigation.electricity_cost',
                'post_irrigation.hours_used as consumption_time',
                'post_irrigation.manpower_category_id',
                'post_irrigation.semi_skilled_1',
                'post_irrigation.semi_skilled_2',
                DB::raw('(post_irrigation.unskilled + post_irrigation.semi_skilled_1 + post_irrigation.semi_skilled_2) as man_power'),
                'post_irrigation.major_maintenance', // Select as is for now
                'users.name as user_name',
                'manpower_type.type as manpower_type',
                'blocks.block_name as block_name',
                'master_plots.plot_name as plot_name',
                'master_plots.area as area'
                
            );

        // Apply site filter
        if ($role != 1) {
            $query->where('post_irrigation.site_id', $defaultSiteId);
        } elseif ($request->filled('site_id')) {
            $query->where('post_irrigation.site_id', $selectedSiteId);
        }

        // Block filter
        if ($selectedBlock) {
            $query->where('post_irrigation.block_name', $selectedBlock);
        }
        if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'post_irrigation.site_id')
                  ->whereColumn('s.block_id', 'post_irrigation.block_name')
                  ->whereColumn('s.plot_id', 'post_irrigation.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('post_irrigation.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'post_irrigation.site_id')
                  ->whereColumn('s.block_id', 'post_irrigation.block_name')
                  ->whereColumn('s.plot_id', 'post_irrigation.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('post_irrigation.date BETWEEN s.start_date AND s.end_date');
            });
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('post_irrigation.plot_name', 'like', "%$search%")
                    ->orWhere('post_irrigation.irrigation_no', 'like', "%$search%")
                    ->orWhere('post_irrigation.major_maintenance', 'like', "%$search%")
                    ->orWhere('users.name', 'like', "%$search%");
            });
        }

        $irrigationData = $query->orderBy('post_irrigation.date', 'desc')
            ->paginate(10)
            ->appends($request->all());
            // Session filter
           

        // Process major_maintenance after pagination
        $irrigationData->getCollection()->transform(function ($item) {
            if (isset($item->major_maintenance) && is_string($item->major_maintenance)) {
                $item->major_maintenance = json_decode($item->major_maintenance, true);
            } elseif (!is_array($item->major_maintenance)) {
                $item->major_maintenance = []; // Ensure it's an array if not already or null
            }
            return $item;
        });

        return view('post-irrigation.index', compact(
            'irrigationData',
            'blocks',
            'selectedBlock',
            'search',
            'sites',
            'selectedSiteId',
            'role',
            'financialYears',
             
        ));
    }
}