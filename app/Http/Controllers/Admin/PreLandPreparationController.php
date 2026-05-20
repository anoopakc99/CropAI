<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PreLandPreparationController extends Controller
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
    
   // Block dropdown
    if ($role == 1) {
         
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
        DB::raw('MIN(id) as id'), // pick one season id
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
    
    // ðŸ”¹ 4. Build main query
    $query = DB::table('pre_land_preparation as pre')
         ->leftJoin('blocks', 'pre.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'pre.plot_name', '=', 'master_plots.id')
        ->leftJoin('users as u', 'pre.user_id', '=', 'u.id')
        ->leftJoin('manpower_type', 'pre.manpower_type', '=', 'manpower_type.id')
        ->select('pre.*', 'u.name as user_name', 'manpower_type.type as manpower_type',
        'blocks.block_name as block_name', 'master_plots.plot_name as plot_name', 'master_plots.area as area')
        ->leftJoin('seasons', 'pre.season_id', '=', 'seasons.id')
        ->addSelect('seasons.name as season_name', 'seasons.year as season_year');
    if (!empty($selectedSiteId)) {
        $query->where('pre.site_id', $selectedSiteId);
    }
    if ($request->filled('year')) {
    $query->whereIn('pre.season_id', function ($q) use ($request) {
        $q->select('id')
          ->from('seasons')
          ->where('year', $request->year);
    });
}
    // // ðŸ”¹ NEW SESSION FILTER - ADD THIS
    // if ($request->filled('session')) {
    //     $session = $request->session;
    //     switch ($session) {
    //         case 'kharif':
    //             $query->whereMonth('pre.date', '>=', 6)
    //                   ->whereMonth('pre.date', '<=', 10);
    //             break;
    //         case 'rabi':
    //             $query->where(function($q) {
    //                 $q->whereMonth('pre.date', '>=', 11)
    //                   ->orWhereMonth('pre.date', '<=', 3);
    //             });
    //             break;
    //         case 'zaid':
    //             $query->whereMonth('pre.date', '>=', 4)
    //                   ->whereMonth('pre.date', '<=', 6);
    //             break;
    //     }
    // }
    
    if ($request->filled('season_id')) {
        $selectedSeasonName = DB::table('seasons')
            ->where('id', $request->season_id)
            ->value('name');

        if ($selectedSeasonName) {
            $hasManualSeason = Schema::hasColumn('pre_land_preparation', 'manual_season');

            $query->where(function ($seasonQuery) use ($request, $selectedSeasonName, $hasManualSeason) {
                if ($hasManualSeason) {
                    $seasonQuery->where('pre.manual_season', $selectedSeasonName)
                        ->orWhere(function ($fallbackQuery) use ($request, $selectedSeasonName) {
                            $fallbackQuery->where(function ($emptyManualQuery) {
                                $emptyManualQuery->whereNull('pre.manual_season')
                                    ->orWhereRaw("TRIM(pre.manual_season) = ''");
                            })
                            ->whereIn('pre.season_id', function ($q) use ($request, $selectedSeasonName) {
                                $q->select('id')
                                    ->from('seasons')
                                    ->where('name', $selectedSeasonName)
                                    ->when($request->filled('year'), function ($yearQuery) use ($request) {
                                        $yearQuery->where('year', $request->year);
                                    })
                                    ->when($request->filled('block_name'), function ($blockQuery) use ($request) {
                                        $blockQuery->where('block_id', $request->block_name);
                                    });
                            });
                        });
                } else {
                    $seasonQuery->whereIn('pre.season_id', function ($q) use ($request, $selectedSeasonName) {
                        $q->select('id')
                            ->from('seasons')
                            ->where('name', $selectedSeasonName)
                            ->when($request->filled('year'), function ($yearQuery) use ($request) {
                                $yearQuery->where('year', $request->year);
                            })
                            ->when($request->filled('block_name'), function ($blockQuery) use ($request) {
                                $blockQuery->where('block_id', $request->block_name);
                            });
                    });
                }
            });
        }
    }
    
    // ðŸ”¹ 5. Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('pre.area', 'LIKE', "%$search%")
              ->orWhere('u.name', 'LIKE', "%$search%");
        });
    }
    
    // 6. Block filter
    if ($request->filled('block_name')) {
        $query->where('pre.block_name', $request->block_name);
    }
    
    // 7. Pagination
    $data = $query->orderBy('pre.date', 'desc')->paginate(10)->appends($request->all());
    
    // 8. Load machine and tractor names
    $allMachines = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
    $allTractors = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();
    
   return view('pre_land.index', compact(
    'data',
    'blocks',
    'allMachines',
    'allTractors',
    'sites',
    'role',
    'selectedSiteId',
    'financialYears',
    'seasonList'
));
}

}
