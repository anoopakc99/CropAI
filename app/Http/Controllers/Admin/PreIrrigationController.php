<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class PreIrrigationController extends Controller
{
 public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $userSiteId = $user->site_id;
        $sites = DB::table('master_sites')->pluck('site_name', 'id')->toArray();

       $selectedSiteId = $request->input('site_id');
        if ($role != 1) {
            $selectedSiteId = $userSiteId;
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

        //  4. Main query with joins
        $query = DB::table('pre_irrigation as pre')
            ->leftJoin('blocks', 'pre.block_name', '=', 'blocks.id')
            ->leftJoin('master_plots', 'pre.plot_name', '=', 'master_plots.id')
            ->leftJoin('master_irrigation_types as it', 'pre.irrigation_type_id', '=', 'it.id')
            ->leftJoin('water_sources as ws', 'pre.water_source_id', '=', 'ws.id')
            ->leftJoin('water_sources as cap', 'pre.capacity_id', '=', 'cap.id')
            ->leftJoin('users', 'pre.user_id', '=', 'users.id')
            ->leftJoin('manpower_type', 'pre.manpower_type', '=', 'manpower_type.id')
            ->select(
                'pre.*',
                'it.type_name as irrigation_type',
                'ws.name as source_of_water',
                'cap.capacity_lph as capacity',
                'users.name as user_name',
                'manpower_type.type as manpower_type',
                'blocks.block_name as block_name',
                'master_plots.plot_name as plot_name',
                'master_plots.area as area'
            );

        // Site filter
        if (!empty($selectedSiteId)) {
            $query->where('pre.site_id', $selectedSiteId);
        }

        // 🔹 5. SESSION FILTER - FIXED AND WORKING
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

        // 🔹 6. Block filter
        if ($request->filled('block_name')) {
            $query->where('pre.block_name', $request->block_name);
        }

        // 🔹 7. Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pre.plot_name', 'like', "%$search%")
                  ->orWhere('it.type_name', 'like', "%$search%")
                  ->orWhere('ws.name', 'like', "%$search%")
                  ->orWhere('cap.capacity_lph', 'like', "%$search%")
                  ->orWhere('pre.irrigation_no', 'like', "%$search%");
            });
        }
        if ($request->filled('season_id')) {
                $query->where('pre.season_id', $request->season_id);
            }
         if ($request->filled('year')) {
                $query->whereIn('pre.season_id', function ($q) use ($request) {
                    $q->select('id')
                      ->from('seasons')
                      ->where('year', $request->year);
                });
            }

        // 🔹 8. Get paginated data
        $data = $query->orderBy('pre.id', 'desc')
                      ->paginate(10)
                      ->appends($request->all());

        return view('pre_irrigation.index', compact('data', 'blocks', 'role', 'sites', 'selectedSiteId', 'financialYears',
            'seasonList'));
    }
}




    


