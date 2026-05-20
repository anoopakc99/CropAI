<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;


class AreaLevelingController extends Controller
{
 

public function index(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $userSiteId = $user->site_id;

    //  1. Get site dropdown for admin
    $sites = DB::table('master_sites')->pluck('site_name', 'id')->toArray();

    //  2. Get selected site ID
    $selectedSiteId = $request->input('site_id');

    // If non-admin, always force to user site
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
    //  4. Base query for data
    $query = DB::table('area_levelings')
        ->leftJoin('blocks', 'area_levelings.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'area_levelings.plot_name', '=', 'master_plots.id')
        ->leftJoin('users', 'area_levelings.user_id', '=', 'users.id')
        ->leftJoin('manpower_type', 'area_levelings.manpower_type', '=', 'manpower_type.id')
        ->select('area_levelings.*', 'users.name as user_name', 'manpower_type.type as manpower_type', 'blocks.block_name as block_name', 'master_plots.plot_name as plot_name', 'master_plots.area as area');

    if (!empty($selectedSiteId)) {
        $query->where('area_levelings.site_id', $selectedSiteId);
    }
     if ($request->filled('season_id')) {
                $query->where('area_levelings.season_id', $request->season_id);
        }
     if ($request->filled('year')) {
            $query->whereIn('area_levelings.season_id', function ($q) use ($request) {
                $q->select('id')
                  ->from('seasons')
                  ->where('year', $request->year);
            });
    }
     

    // 6. Block filter
    if ($request->filled('block_name')) {
        $query->where('area_levelings.block_name', $request->block_name);
    }

    // 🔹 7. Search filter
    if ($request->filled('search')) {
        $search = '%' . $request->search . '%';
        $query->where(function ($q) use ($search) {
            $q->where('area_levelings.plot_name', 'like', $search)
                ->orWhere('area_levelings.area', 'like', $search)
                ->orWhere('area_levelings.manpower_type', 'like', $search);
        });
    }

    // 🔹 8. Pagination
    $data = $query->orderBy('area_levelings.block_name')
        ->paginate(10)
        ->appends($request->all());

    // 🔹 9. Load machines and tractors
    $allMachines = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
    $allTractors = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();

    // 🔹 10. Diesel FIFO stock
    $stockFIFO = DB::table('diesel_stocks')
        ->where('diesel_stock', '>', 0)
        ->where('site_id', $user->site_id)
        ->orderBy('date_of_entry', 'asc')
        ->get(['id', 'diesel_stock', 'rate_per_liter']);

    // 🔹 11. Map records with machine/tractor names and diesel cost
    foreach ($data as $item) {
        // Machine & Tractor Mapping
        $machineIds = !empty($item->machine_id) ? array_filter(explode(',', $item->machine_id)) : [];
        $tractorIds = !empty($item->tractor_id) ? array_filter(explode(',', $item->tractor_id)) : [];

        $item->machine_names = array_map(function ($id) use ($allMachines) {
            return $allMachines[trim($id)] ?? "Unknown Machine (ID: $id)";
        }, $machineIds);

        $item->tractor_names = array_map(function ($id) use ($allTractors) {
            return $allTractors[trim($id)] ?? "Unknown Tractor (ID: $id)";
        }, $tractorIds);

        // Diesel cost calculation
        $dieselCost = 0;
        $remainingLiters = $item->hsd_consumption ?? 0;

        $stockClone = $stockFIFO->map(function ($s) {
            return (object)[
                'diesel_stock' => $s->diesel_stock,
                'rate_per_liter' => $s->rate_per_liter
            ];
        });

        foreach ($stockClone as $stockEntry) {
            if ($remainingLiters <= 0) break;

            $litersUsed = min($stockEntry->diesel_stock, $remainingLiters);
            $dieselCost += $litersUsed * $stockEntry->rate_per_liter;
            $remainingLiters -= $litersUsed;
            $stockEntry->diesel_stock -= $litersUsed;
        }

        $item->diesel_cost = round($dieselCost, 2);

        // if (($item->hsd_consumption ?? 0) == 0) {
        //     $item->diesel_cost = 0.00;
        // }
    }

    return view('area_leveling.index', compact('data', 'blocks', 'role', 'sites', 'selectedSiteId', 'financialYears',
            'seasonList'));
}

public function exportCsv(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $userSiteId = $user->site_name;
    $selectedSiteId = $request->input('site_id');

    if ($role != 1) {
        $selectedSiteId = $userSiteId;
    }

    // Build query
    $query = DB::table('area_levelings')
        ->leftJoin('users', 'area_levelings.user_id', '=', 'users.id')
        ->select('area_levelings.*', 'users.name as user_name');

    if (!empty($selectedSiteId)) {
        $query->where('area_levelings.site_id', $selectedSiteId);
    }

    if ($request->filled('block_name')) {
        $query->where('area_levelings.block_name', $request->block_name);
    }

    if ($request->filled('search')) {
        $search = '%' . $request->search . '%';
        $query->where(function ($q) use ($search) {
            $q->where('area_levelings.plot_name', 'like', $search)
                ->orWhere('area_levelings.area', 'like', $search)
                ->orWhere('area_levelings.manpower_type', 'like', $search);
        });
    }

    $data = $query->orderBy('area_levelings.block_name')->get();

    // ✅ Updated: Fetch machine and tractor names from correct tables
    $machineMap = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
    $tractorMap = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();

    $columns = [
        'Sr No', 'Date', 'Season', 'Block Name', 'Plot No', 'Area (Acre)', 'Area Covered (Acre)',
        'Machine Used', 'Tractor Used', 'HSD Consumption (Ltr)', 'Time (Hrs)',
        'Manpower Type', 'Unskilled', 'Semi Skilled 1', 'Semi Skilled 2',
        'Major Maintenance', 'Total Cost (Rs)', 'Supervisor Name'
    ];

    return response()->streamDownload(function () use ($data, $columns, $machineMap, $tractorMap) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, $columns);

        $index = 1;
        foreach ($data as $item) {
            // Season Label
            $manualSession = trim((string)($item->season_name ?? '')) ?: (trim((string)($item->manual_season ?? '')) ?: trim((string)($item->manual_session ?? '')));
            if ($manualSession) {
                $seasonLabel = $manualSession;
            } else {
                $date = \Carbon\Carbon::parse($item->date);
                $month = $date->month;
                if ($month >= 6 && $month <= 10) {
                    $seasonLabel = 'Kharif';
                } elseif ($month >= 11 || $month <= 3) {
                    $seasonLabel = 'Rabi';
                } else {
                    $seasonLabel = 'Zaid';
                }
            }

            // Machine name conversion
            $machineNames = 'N/A';
            if (!empty($item->machine_id)) {
                $ids = array_map('trim', explode(',', $item->machine_id));
                $machineNames = implode(', ', array_map(fn($id) => $machineMap[$id] ?? $id, $ids));
            }

            // Tractor name conversion
            $tractorNames = 'N/A';
            if (!empty($item->tractor_id)) {
                $ids = array_map('trim', explode(',', $item->tractor_id));
                $tractorNames = implode(', ', array_map(fn($id) => $tractorMap[$id] ?? $id, $ids));
            }

            // Maintenance parsing
            $maintenanceInfo = '';
            if (!empty($item->major_maintenance)) {
                $decoded = json_decode($item->major_maintenance, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $maint) {
                        $maintenanceInfo .= ($maint['spare_part'] ?? '-') . ' - ₹' . ($maint['value'] ?? 0) . "; ";
                    }
                }
            }

            // Write row
            fputcsv($handle, [
                $index++,
                $item->date,
                $seasonLabel,
                $item->block_name,
                $item->plot_name,
                $item->area,
                $item->area_leveling,
                $machineNames,
                $tractorNames,
                $item->hsd_consumption,
                $item->hours_used,
                $item->manpower_type,
                $item->unskilled,
                $item->semi_skilled_1,
                $item->semi_skilled_2,
                $maintenanceInfo,
                $item->total_cost,
                $item->user_name
            ]);
        }

        fclose($handle);
    }, 'area_leveling.csv', [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename=area_leveling.csv',
        'Cache-Control' => 'no-cache, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
}


}
