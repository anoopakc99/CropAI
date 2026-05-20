<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Validator;
class MasterController extends Controller
{

public function consolidated(Request $request)
{
    $currentUser = Auth::user();

    // Role-based site access logic
    if ($currentUser->role == 1) {
        // Role 1: Can see all sites data
        $siteCondition = function($query) {
        };
        $siteId = null; // Will be used for queries without join
    } else {
        // Roles 2, 3, 4: Can only see their own site_id data
        $siteCondition = function($query) use ($currentUser) {
            $query->where('site_id', $currentUser->site_id);
        };
        $siteId = $currentUser->site_id;
    }

    // Get all blocks and plots based on role
    $plotsQuery = DB::table('master_plots as mp')
        ->join('blocks as mb', 'mp.block_id', '=', 'mb.id')
        ->select('mp.id', 'mb.block_name', 'mp.plot_name', 'mp.area', 'mb.site_id');

    if ($currentUser->role != 1) {
        $plotsQuery->where('mb.site_id', $siteId);
    }

    $plots = $plotsQuery->orderBy('mb.block_name')
        ->orderBy('mp.plot_name')
        ->get();

       $grandTotal = $plots->sum('area');

    // Seed usage with role-based filtering
    $seedUsageQuery = DB::table('showing_oprations')
        ->leftJoin('blocks', 'showing_oprations.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'showing_oprations.plot_name', '=', 'master_plots.id')
        ->select('blocks.block_name', 'master_plots.plot_name', DB::raw('SUM(seed_consumption) as total_seed'));

    if ($currentUser->role != 1) {
        $seedUsageQuery->where('showing_oprations.site_id', $siteId);
    }

    $seedUsage = $seedUsageQuery->groupBy('block_name', 'plot_name')
        ->get()
        ->keyBy(fn($s) => $s->block_name . '_' . $s->plot_name);

    // Fertilizer usage with role-based filtering
    $fertilizerUsageQuery = DB::table('fertilizer_soil_record')
        ->leftJoin('blocks', 'fertilizer_soil_record.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'fertilizer_soil_record.plot_name', '=', 'master_plots.id')
        ->select('blocks.block_name', 'master_plots.plot_name', DB::raw('SUM(fertilizer_quantity) as total_fertilizer'));

    if ($currentUser->role != 1) {
        $fertilizerUsageQuery->where('fertilizer_soil_record.site_id', $siteId);
    }

    $fertilizerUsage = $fertilizerUsageQuery->groupBy('block_name', 'plot_name')
        ->get()
        ->keyBy(fn($f) => $f->block_name . '_' . $f->plot_name);

    // Final formatted data
    $processedPlots = $plots->map(function ($plot, $index) use ($seedUsage, $fertilizerUsage) {
        $key = $plot->block_name . '_' . $plot->plot_name;
        $plot->sr_no = $index + 1;
        $plot->seed_used = isset($seedUsage[$key]) ? $seedUsage[$key]->total_seed : 0;
        $plot->fertilizer_used = isset($fertilizerUsage[$key]) ? $fertilizerUsage[$key]->total_fertilizer : 0;

        if ($plot->seed_used > 0 && $plot->fertilizer_used > 0) {
            $plot->stage = 'harvesting';
        } elseif ($plot->seed_used > 0) {
            $plot->stage = 'growing';
        } else {
            $plot->stage = 'planted';
        }

        $plot->supervisor = 'Not Assigned';
        return $plot;
    });

    // Group by block name
    $groupedPlots = $processedPlots->groupBy('block_name');

    // Info cards count with role-based filtering
    $landsCountQuery = DB::table('master_land');
    $seedsCountQuery = DB::table('master_seed');
    $machinesCountQuery = DB::table('master_machine');
    $fertilizersCountQuery = DB::table('master_fertilizer');

    //FIXED: Tractor count query using correct table name
    $tractorsCountQuery = DB::table('master_tractors'); // Using correct table name

    if ($currentUser->role != 1) {
        $landsCountQuery->where('site_id', $siteId);
        $seedsCountQuery->where('site_id', $siteId);
        $machinesCountQuery->where('site_id', $siteId);
        $fertilizersCountQuery->where('site_id', $siteId);
        $tractorsCountQuery->where('site_id', $siteId); // Apply role-based filtering to tractors
    }

    $landsCount = $landsCountQuery->count();
    $seedsCount = $seedsCountQuery->count();
    $machinesCount = $machinesCountQuery->count();
    $fertilizersCount = $fertilizersCountQuery->count();
    $tractorsCount = $tractorsCountQuery->count(); // Get tractor count from master_tractors table

   // Get distinct blocks
     if ($currentUser->role == 1) {
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
            ->where('ml.site_id', $siteId)
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    }

    // Pass everything to view (including tractorsCount)
    return view('admin.consolidated', compact(
        'groupedPlots',
        'currentUser',
        'landsCount',
        'seedsCount',
        'machinesCount',
        'fertilizersCount',
        'tractorsCount', // Add tractorsCount to compact
        'blocks',
        'grandTotal'
    ));
}

//==============================================================================================================================================

protected $soilTypes = [
    'Loamy', 'Sandy', 'Clay',
    'Silty', 'Peaty', 'Chalky'
];

// ==============================================================================
// VIEW LAND RECORDS




public function indexland(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $userSiteId = $user->site_id; // Adjust if actual site_id column is different

    // Prepare sites dropdown
    $sitesQuery = DB::table('master_sites')->orderBy('site_name');
    if ($role == 2) {
        $sitesQuery->where('id', $userSiteId);
    } elseif ($role == 3) {
        $sitesQuery->where('id', $userSiteId);
    }
    $sites = $sitesQuery->get();

    // Determine applicable site_id for filter
    $siteId = $userSiteId;
    // if ($role == 2) {
    //     $siteId = 1;
    // } elseif ($role == 3) {
    //     $siteId = 2;
    // } elseif ($role == 4) {
    //     $siteId = 3;
    // } elseif ($role == 5) {
    //     $siteId = 4;
    // } elseif ($role == 1 && $request->filled('site_id')) {
    //     $siteId = $request->site_id;
    // }

    // Filter blocks site-wise
    $blocksQuery = DB::table('blocks')->orderBy('block_name');
    if ($siteId) {
        $blocksQuery->where('site_id', $siteId);
    }
    $blocks = $blocksQuery->get();
 
    // Years range
    $years = range(date('Y') - 5, date('Y') + 5);

    // Initialize land query with site restriction
    $query = DB::table('master_land as ml')
     ->leftJoin('blocks as b', 'ml.block_name', '=', 'b.id')
     ->leftJoin('master_plots as mp', 'ml.plot_no', '=', 'mp.id')
     ->leftJoin('users as u', 'ml.super_wiser_name', '=', 'u.id')
     ->where('ml.is_deleted', 0)
     ->select('ml.*', 'b.block_name as block', 'mp.plot_name as plot', 'u.name as superviser');
    if ($siteId) {
        $query->where('ml.site_id', $siteId);
    }

    // Block filter
    if ($request->filled('block')) {
    $query->where('ml.block_name', $request->block);
}

    // Year filter
    if ($request->filled('year')) {
        $query->whereYear('ml.created_at', $request->year);
    }

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('plot_no', 'like', "%{$search}%")
              ->orWhere('block_name', 'like', "%{$search}%")
              ->orWhere('super_wiser_name', 'like', "%{$search}%")
              ->orWhere('soil_type', 'like', "%{$search}%")
              ->orWhere('previous_crop', 'like', "%{$search}%")
              ->orWhere('current_crop', 'like', "%{$search}%");
        });
    }
    $supervisor = DB::table('users')->where('site_id', $siteId)
                    ->where('role', 4)->get();


    $lands = $query->orderBy('id', 'desc')->get();
//dd($lands);
    return view('admin.land', compact('lands', 'blocks', 'years', 'sites', 'role', 'supervisor'));
}




public function storeland(Request $request)
{
    $validator = Validator::make($request->all(), [
        'block_name' => 'required',
        'plot_no' => 'required',
        'area_ha' => 'required|numeric',
        //'super_wiser_name' => 'required',
        'soil_type' => 'required',
        'previous_crop' => 'required',
        'current_crop' => 'required',
        'site_id' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Check if the same block_name and plot_no already exist in the database
        $exists = DB::table('master_land')
            ->where('block_name', $request->block_name)
            ->where('plot_no', $request->plot_no)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'This block and plot already exist.'], 409);
        }

        DB::beginTransaction();

       $insert = DB::table('master_land')->insert([
            'plot_no' => $request->plot_no,
            'block_name' => $request->block_name,
            'site_id' => $request->site_id,
            'super_wiser_name' => $request->super_wiser_name,
            'area_ha' => $request->area_ha,
            'soil_type' => $request->soil_type,
            'previous_crop' => $request->previous_crop,
            'current_crop' => $request->current_crop,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if($insert && $request->super_wiser_name){
            DB::table('user_plots')->insert([
                'user_id'   => $request->super_wiser_name,
                'block_id'  => $request->block_name,
                'plot_id'   =>  $request->plot_no,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }

        DB::commit();

        return response()->json(['success' => 'Land record added successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Failed to add land record. ' . $e->getMessage()], 500);
    }
}


// ==============================================================================
// UPDATE LAND RECORD
public function updateland(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'block_name' => 'required',
        'plot_no' => 'required',
        'area_ha' => 'required|numeric',
        'super_wiser_name' => 'required',
        'soil_type' => 'required',
        'previous_crop' => 'required',
        'current_crop' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();

        DB::table('master_land')
            ->where('id', $id)
            ->update([
                'plot_no' => $request->plot_no,
                'block_name' => $request->block_name,
                    'site_id' => $request->site_id, // <-- add this line

                'super_wiser_name' => $request->super_wiser_name,
                'area_ha' => $request->area_ha,
                'soil_type' => $request->soil_type,
                'previous_crop' => $request->previous_crop,
                'current_crop' => $request->current_crop,
                'updated_at' => now(),
            ]);

        DB::commit();

        return response()->json(['success' => 'Land record updated successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Failed to update land record. ' . $e->getMessage()], 500);
    }
}
public function editland($id)
{
    // Get land record
    $land = DB::table('master_land')->where('id', $id)->first();

    if (!$land) {
        return response()->json(['error' => 'Land not found'], 404);
    }
    $user = Auth::user();
    // All blocks for dropdown
    $blocks = DB::table('blocks')
        ->where('site_id', $user->site_id)
        ->orderBy('block_name')
        ->get();

    // Plots of selected block
    $plots = DB::table('master_plots')
        ->where('block_id', $land->block_name) 
        ->select('id', 'plot_name', 'area as plot_area')
        ->get();

    return response()->json([
        'land'   => $land,
        'blocks' => $blocks,
        'plots'  => $plots
    ]);
}


public function getPlotArealand(Request $request)
{
     $user = Auth::user();
    $plot = DB::table('master_plots')
                ->where('plot_name', $request->plot_name)
                
                ->first();

if ($plot) {
    return response()->json(['area' => $plot->area]);
}

return response()->json(['area' => 0]);
}

public function getPlotsByBlockland(Request $request)
{
    $query = DB::table('master_plots')->select('id', 'plot_name', 'area');

    if ($request->has('block_ids') && is_array($request->block_ids) && count($request->block_ids) > 0) {
        $query->whereIn('block_id', $request->block_ids);
    } elseif ($request->filled('block_id')) {
        $query->where('block_id', $request->block_id);
    }

    $plots = $query->get();

    return response()->json($plots);
}

// ==============================================================================
// DELETE LAND RECORD (Soft Delete)
public function destroyland($id)
{
    try {
        DB::beginTransaction();

        DB::table('master_land')
            ->where('id', $id)
            ->update(['is_deleted' => 1]);

        DB::commit();

        return response()->json(['success' => 'Land record deleted successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Failed to delete land record. ' . $e->getMessage()], 500);
    }
}

//==============================================================================================================================================

public function seedIndex(Request $request)
{
    $user = Auth::user();
    $role = $user->role;
    $userSiteId = $user->site_id;
    $selectedSiteId = $request->input('site_id');

    // Get filter parameters (default to 'all' for both)
    $filterYear = $request->input('filter_year', 'all');
    $filterMonth = $request->input('filter_month', 'all'); // 'all' or specific month (1-12)

    // --- 1. Base Query for the Main Seed List (Latest Stock) ---
    $baseQuery = DB::table('master_seed as ms1')
        ->leftJoin('seed as s', 'ms1.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms1.seed_variety_id', '=', 'mv.id')
        ->select(
            'ms1.*',
            's.name as seed_name',
            'mv.variety_name as variety_of_seed',
            DB::raw('(ms1.seed_stock_kg * ms1.rate_of_seed) as total_amount_sale')
        )
        ->where('ms1.is_deleted', 0)
        ->whereRaw('ms1.id = (
            SELECT id FROM master_seed as ms_inner
            WHERE ms_inner.seed_id = ms1.seed_id
              AND ms_inner.seed_variety_id = ms1.seed_variety_id
              AND ms_inner.is_deleted = 0
            ORDER BY ms_inner.date_of_packing DESC, ms_inner.id DESC
            LIMIT 1
        )');

    // Apply Site and Search filters
    if ($role == 1 && $selectedSiteId) {
        $baseQuery->where('ms1.site_id', $selectedSiteId);
    } elseif ($role != 1) {
        $baseQuery->where('ms1.site_id', $userSiteId);
    }

    if ($request->filled('search')) {
        $search = $request->input('search');
        $baseQuery->where(function ($q) use ($search) {
            $q->where('s.name', 'like', '%' . $search . '%')
              ->orWhere('mv.variety_name', 'like', '%' . $search . '%')
              ->orWhere('ms1.location', 'like', '%' . $search . '%');
        });
    }

    $seeds = $baseQuery->orderBy('s.id', 'asc')->get();

    // --- 2. Weighted Average & Stock History Calculations ---
    foreach ($seeds as $seed) {
        $addAgg = DB::table('seed_stock_history')
            ->where('seed_id', $seed->id)
            ->where('type', 'Add Stock')
            ->select(
                DB::raw('COALESCE(SUM(seed_stock_kg),0) as add_qty'),
                DB::raw('COALESCE(SUM(seed_stock_kg * rate_of_seed),0) as add_value')
            )
            ->first();

        $consAgg = DB::table('seed_stock_history')
            ->where('seed_id', $seed->id)
            ->where('type', 'Consumption')
            ->select(
                DB::raw('COALESCE(SUM(seed_stock_kg),0) as cons_qty'),
                DB::raw('COALESCE(SUM(seed_stock_kg * rate_of_seed),0) as cons_value')
            )
            ->first();

        $total_qty = (float) $addAgg->add_qty;
        $total_value = (float) $addAgg->add_value;
        $cons_qty = (float) $consAgg->cons_qty;
        $cons_value = (float) $consAgg->cons_value;

        $current_qty = $total_qty - $cons_qty;
        $current_value = $total_value - $cons_value;

        $seed->total_stock = $total_qty > 0 ? $total_qty : 0;
        $seed->calculated_balance = max(0, $current_qty);
        $seed->totalStockPrice = $total_value;
        $seed->calculated_amount = $current_value;
        $seed->is_balance_correct = ((float)$seed->seed_stock_kg === (float)$current_qty);

        $seed->latest_rate = DB::table('seed_stock_history')
            ->where('seed_id', $seed->id)
            ->where('type', 'Add Stock')
            ->orderBy('date_of_packing', 'desc')
            ->orderBy('id', 'desc')
            ->value('rate_of_seed') ?? $seed->rate_of_seed;
    }

    // --- 3. Chart Data Preparation with Filters ---
    $seedIds = $seeds->pluck('id')->toArray();

    // Base query for chart data
    $chartQuery = DB::table('seed_stock_history as ssh')
        ->join('master_seed as ms', 'ssh.seed_id', '=', 'ms.id')
        ->join('seed as s', 'ms.seed_id', '=', 's.id')
        ->join('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->whereIn('ssh.seed_id', $seedIds);

    // Apply year filter
    if ($filterYear && $filterYear !== 'all') {
        $chartQuery->whereYear('ssh.date_of_packing', $filterYear);
    }

    // Apply month filter
    if ($filterMonth && $filterMonth !== 'all') {
        $chartQuery->whereMonth('ssh.date_of_packing', $filterMonth);
    }

    // Pie Chart Data (Total by seed type)
    $pieChartData = (clone $chartQuery)
        ->select(
            DB::raw('CONCAT(s.name, " (", mv.variety_name, ")") as seed_name'),
            DB::raw('SUM(CASE WHEN ssh.type = "Consumption" THEN ssh.seed_stock_kg ELSE 0 END) as consumed')
        )
        ->groupBy('s.name', 'mv.variety_name')
        ->having('consumed', '>', 0)
        ->get();

    // Bar Chart Data (Month-wise breakdown)
    $barChartQuery = (clone $chartQuery)
        ->select(
            DB::raw('CONCAT(s.name, " (", mv.variety_name, ")") as seed_name'),
            DB::raw('DATE_FORMAT(ssh.date_of_packing, "%Y-%m") as month'),
            DB::raw('SUM(CASE WHEN ssh.type = "Add Stock" THEN ssh.seed_stock_kg ELSE 0 END) as added'),
            DB::raw('SUM(CASE WHEN ssh.type = "Consumption" THEN ssh.seed_stock_kg ELSE 0 END) as consumed')
        )
        ->groupBy('s.name', 'mv.variety_name', 'month')
        ->orderBy('month', 'asc')
        ->get();

    // Format data for charts
    $chartDataForPie = $pieChartData->map(function($item) {
        return [
            'seed_name' => $item->seed_name,
            'consumed' => (float)$item->consumed,
        ];
    })->values()->toArray();

    $chartDataForBar = $barChartQuery->groupBy('seed_name')->map(function($items, $seedName) {
        return [
            'seed_name' => $seedName,
            'months' => $items->pluck('month')->toArray(),
            'added' => $items->pluck('added')->map(fn($v) => (float)$v)->toArray(),
            'consumed' => $items->pluck('consumed')->map(fn($v) => (float)$v)->toArray(),
        ];
    })->values()->toArray();

    // Get available years for filter dropdown
    $availableYears = DB::table('seed_stock_history')
        ->selectRaw('DISTINCT YEAR(date_of_packing) as year')
        ->whereNotNull('date_of_packing')
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->toArray();

    // --- 4. Sites & Master Seeds ---
    $sitesQuery = DB::table('master_sites')->orderBy('site_name');
    if ($role != 1) $sitesQuery->where('id', $userSiteId);
    $sites = $sitesQuery->get();

    $masterSeeds = DB::table('seed')->where('site_id', $userSiteId)->get();

    return view('admin.seed', compact(
        'seeds',
        'sites',
        'masterSeeds',
        'chartDataForPie',
        'chartDataForBar',
        'availableYears',
        'filterYear',
        'filterMonth'
    ));
}





public function getSeedStockHistory($id)
{
try {
        Log::info('Fetching history for seed_id: ' . $id);

        $masterSeed = DB::table('master_seed')
            ->where('id', $id)
            ->first();

        if (!$masterSeed) {
            return response()->json(['message' => 'Seed not found.'], 404);
        }

        $history = DB::table('seed_stock_history as ssh')
            ->select([
                'ssh.id',
                'ssh.seed_id',
                'ssh.date_of_packing',
                'ssh.type',
                'ssh.seed_stock_kg',
                'ssh.add_new_seed_stock',
                'ssh.rate_of_seed',
                'ssh.add_new_seed_rate',
                'ssh.uom',
                'ssh.site_id',
                'ssh.created_at',
                'ssh.updated_at',
                'ms.seed_name',
                'ms.variety_of_seed'
            ])
            ->leftJoin('master_seed as ms', 'ssh.seed_id', '=', 'ms.id')
            ->where('ssh.seed_id', $id)
            ->orderBy('ssh.created_at', 'desc')
            ->get();

        // ✅ Format existing history dates
        $history = $history->map(function ($item) {
            $item->date_of_packing = \Carbon\Carbon::parse($item->date_of_packing)->format('j /n/y');
            return $item;
        });

        // ✅ Create virtual entry from master_seed (ALWAYS insert at top)
        $initialRecord = (object)[
            'id' => null,
            'seed_id' => $masterSeed->id,
            'date_of_packing' => \Carbon\Carbon::parse($masterSeed->date_of_packing)->format('j /n/y'),
            'stock_type' => $masterSeed->type,
            'packing_size' => $masterSeed->packing_size,
            'seed_stock_kg' => $masterSeed->seed_stock_kg,
            'rate_of_seed' => $masterSeed->rate_of_seed,
            'uom' => $masterSeed->uom,
            'site_id' => $masterSeed->site_id,
            'created_at' => $masterSeed->created_at,
            'updated_at' => $masterSeed->updated_at,
            'seed_name' => $masterSeed->seed_name,
            'variety_of_seed' => $masterSeed->variety_of_seed,
        ];

        // ✅ Add at beginning (prepend)
        $history->prepend($initialRecord);

        // ✅ Debug output (optional)
        // dd($history);

        return response()->json([
            'success' => true,
            'master_seed' => $masterSeed,
            'history' => $history,
            'total_records' => $history->count()
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching seed history: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching seed history.', 'error' => $e->getMessage()], 500);
    }
}

// Alternative method with more detailed information
public function getSeedHistory($id)
{
    try {
        Log::info('Fetching history for seed_id: ' . $id);

        $masterSeed = DB::table('master_seed')
            ->where('id', $id)
            ->first();

        if (!$masterSeed) {
            return response()->json(['message' => 'Seed not found.'], 404);
        }

        $history = DB::table('seed_stock_history as ssh')
            ->leftJoin('master_seed as ms', 'ssh.seed_id', '=', 'ms.id')
            ->select([
                'ssh.id',
                'ssh.seed_id',
                'ssh.type',
                'ssh.packing_size',
                'ssh.seed_stock_kg',
                'ssh.rate_of_seed',
                'ssh.date_of_packing',
                'ssh.uom',
                'ssh.site_id',
                'ssh.created_at',
                'ssh.updated_at',
                'ms.seed_name',
                'ms.variety_of_seed'
            ])

            ->where('ssh.seed_id', $id)
            ->orderBy('ssh.created_at', 'desc')
            ->get();

        // ✅ Format date_of_packing
        $history = $history->map(function ($item) {
            $item->date_of_packing = \Carbon\Carbon::parse($item->date_of_packing)->format('j-n-y');
            return $item;
        });

        Log::info('Found ' . $history->count() . ' history records for seed_id: ' . $id);
        Log::info('History data: ' . json_encode($history));

        return response()->json([
            'success' => true,
            'master_seed' => $masterSeed,
            'history' => $history,
            'total_records' => $history->count()
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching seed history: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching seed history.', 'error' => $e->getMessage()], 500);
    }
}

    public function getSeedStockHistoryDetailed($id)
    {
        try {
            $masterSeed = DB::table('master_seed')
                ->where('id', $id)
                ->first();

            if (!$masterSeed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seed not found',
                    'history' => []
                ], 404);
            }

            $history = DB::table('seed_stock_history as ssh')
                ->select([
                    'ssh.id',
                    'ssh.seed_id',
                    'ssh.date_of_packing',
                    'ssh.type_of_packing',
                    'ssh.packing_size',
                    'ssh.seed_stock_kg',
                    'ssh.add_new_seed_stock',
                    'ssh.rate_of_seed',
                    'ssh.add_new_seed_rate',
                    'ssh.uom',
                    'ssh.site_id',
                    'ssh.created_at',
                    'ssh.updated_at',
                    'ms.seed_name',
                    'ms.variety_of_seed',
                    'sites.site_name as location'
                ])
                ->leftJoin('master_seed as ms', 'ssh.seed_id', '=', 'ms.id')
                ->leftJoin('sites', 'ssh.site_id', '=', 'sites.id')
                ->where('ssh.seed_id', $id)
                ->orderBy('ssh.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'master_seed' => $masterSeed,
                'history' => $history,
                'total_records' => $history->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stock history: ' . $e->getMessage(),
                'history' => []
            ], 500);
        }
    }
public function storeseed(Request $request)
    {
       $validated =  $request->validate([
        'site_id' => 'required|integer',
        'seed' => 'required|integer',
        'seed_variety' => 'required|integer',
        'production_type' => 'required|string',
        'type_of_packing' => 'required|string',
        'packing_size' => 'required|string',
        'date_of_packing' => 'required|date',
        'seed_stock_kg' => 'required|numeric|min:0',
        'rate_of_seed' => 'required|numeric|min:0',
        'location' => 'required|string',
        'uom' => 'required|string',
    ]);
 // Check duplicate: same fertilizer_id + fertilizer_type in same site
        $duplicate = DB::table('master_seed')
            ->where('seed_id', $request->seed)
            ->where('seed_variety_id', $request->seed_variety)
            ->where('site_id', $validated['site_id'])
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors(['seed' => 'This Seed with this variety already exists.']);
        }
   $seedId = DB::table('master_seed')->insertGetId([
        'site_id' => $request->site_id,
        'seed_id' => $request->seed,
        'seed_variety_id' => $request->seed_variety,
        'production_type' => $request->production_type,
        'purpose' => $request->purpose,
        'type_of_packing' => $request->type_of_packing,
        'packing_size' => $request->packing_size,
        'date_of_packing' => $request->date_of_packing,
        'seed_stock_kg' => $request->seed_stock_kg,
        'rate_of_seed' => $request->rate_of_seed,
        'location' => $request->location,
        'uom' => $request->uom,
        'sowing_method' => $request->sowing_method,
        'remark' => $request->remark,
        'sowing_source' => $request->sowing_source,

    ]);



        // ✅ Insert into seed_stock_history
        DB::table('seed_stock_history')->insert([
            'seed_id'             => $seedId,
            'variety_id'     => $validated['seed_variety'],
            //'seed_name'           => $validated['seed_name'],
            //'variety_of_seed'     => $validated['variety_of_seed'],
            'type_of_packing'     => $validated['type_of_packing'],
            'packing_size'        => $validated['packing_size'],
            'date_of_packing'     => $validated['date_of_packing'] ?? now()->toDateString(),
            'seed_stock_kg'       => $validated['seed_stock_kg'],   // Total available stock after add

            'rate_of_seed'        => $validated['rate_of_seed'],

            'type'                => "Add Stock",
            'uom'                 => $validated['uom'],
            'site_id'             => $validated['site_id'],
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('seeds.index')
            ->with('success', 'Seed stock added successfully');
    }
    public function getVarieties($seedId)
    {
        $varieties = DB::table('master_veriety')
            ->where('seed_id', $seedId)
            ->select('id', 'variety_name')
            ->get();

        return response()->json($varieties);
    }


    public function addStock(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'quantity_to_add' => 'required|numeric|min:0.01',
            'new_rate_of_seed' => 'nullable|numeric|min:0',
            'new_sale_price' => 'nullable|numeric|min:0',
            'location' => 'required|string|max:255',
            'date' => 'required',
        ]);

        $seed = DB::table('master_seed')
            ->where('id', $id)
            ->where('is_deleted', 0)
            ->first();

        if (!$seed) {
            return redirect()->route('seeds.index')->with('error', 'Seed not found.');
        }

        DB::beginTransaction();

        // ✅ Agar rate diya gaya hai to use karo, warna master ka current rate
        $rate = $validated['new_rate_of_seed'] ?? $seed->rate_of_seed;

        // ✅ Insert a new independent Add Stock batch
        DB::table('seed_stock_history')->insert([
            'seed_id'           => $id,
            'variety_id'        => $seed->seed_variety_id,
            'type_of_packing'   => $seed->type_of_packing,
            'packing_size'      => $seed->packing_size,
            'date_of_packing'   => $validated['date'],
            'seed_stock_kg'     => $validated['quantity_to_add'],
            'rate_of_seed'      => $rate,
            'type'              => "Add Stock",
            'uom'               => $seed->uom,
            'location'           => $validated['location'],
            'site_id'           => $seed->site_id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Master table me sirf total stock update hoga
        $newStock = $seed->seed_stock_kg + $validated['quantity_to_add'];
        DB::table('master_seed')
            ->where('id', $id)
            ->update([
                'seed_stock_kg' => $newStock,
                'rate_of_seed'  => $rate, // latest purchase rate update

            ]);

        DB::commit();

        return redirect()->route('seeds.index')->with('success', 'Stock added successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('seeds.index')->with('error', 'Failed to add stock: ' . $e->getMessage());
    }
}



    public function updateseed(Request $request, $id)
{
    // Validate incoming request data
    $validatedNewData = $request->validate([
        'seed_name' => 'required|string|max:255',
        'variety_of_seed' => 'required|string|max:255',
        'site_id' => 'required|integer|exists:master_sites,id',

        'purpose' => 'nullable|string|max:255',
        'sowing_method' => 'nullable|string|max:255',
        'type_of_packing' => 'required|string|max:255',
        'packing_size' => 'required|string|max:255',
        'date_of_packing' => 'required|date',
        'seed_stock_kg' => 'required|numeric|min:0',
        'rate_of_seed' => 'required|numeric|min:0', // Base rate/cost
        'sale_price' => 'required|numeric|min:0',   // Selling price
        'location' => 'required|string|max:255',
        'remark' => 'nullable|string',
        'uom' => 'required|string|max:50',
        'production_type' => 'required|string|max:50',
        'sowing_source' => 'nullable|string|max:255',
    ]);

    // Check if seed record exists
    $originalSeed = DB::table('master_seed')->where('id', $id)->first();

    if (!$originalSeed) {
        return redirect()->route('seeds.index')
            ->with('error', 'Seed record not found for update.');
    }

    // Always update the existing record – no duplicate insert
    DB::table('master_seed')
        ->where('id', $id)
        ->update($validatedNewData);

    return redirect()->route('seeds.index')
        ->with('success', 'Seed data updated successfully for ID: ' . $id);
}


public function destroyseed($id)
{
    DB::table('master_seed')
      ->where('id', $id)
      ->update(['is_deleted' => 1]);

    return redirect()->route('seeds.index')
        ->with('success', 'Seed deleted successfully');
}


public function searchseed(Request $request)
{
$search = $request->input('search');

$seeds = DB::table('master_seed')
    ->where('seed_name', 'LIKE', "%{$search}%")
    ->orWhere('variety_of_seed', 'LIKE', "%{$search}%")
    ->orWhere('purpose', 'LIKE', "%{$search}%")
    ->orWhere('location', 'LIKE', "%{$search}%")
    ->paginate(10);
$sites = DB::table('master_sites')->orderBy('site_name')->get();

return view('admin.seed', compact('seeds','sites'));
}

//==============================================================================================================================================

    // machine add, update, delete


public function machine(Request $request)
{
    try {
        $user = Auth::user();
        $role = $user->role;
        $userSiteId = $user->site_id; // corrected from site_name to site_id
        $selectedSiteId = $request->input('site_id');
        $search = $request->get('search');
        $perPage = 15;

        // --- Machine Query ---
        $query = DB::table('master_machine as m')
            ->leftJoin('master_sites as s', 'm.site_id', '=', 's.id')
            ->select('m.*', 's.site_name');

        // Role-based site filtering
        if ($role == 1 && $selectedSiteId) {
            $query->where('m.site_id', $selectedSiteId);
        } else {
            $query->where('m.site_id', $userSiteId);
        }

        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('m.machine_name', 'LIKE', "%{$search}%")
                  ->orWhere('m.machine_no', 'LIKE', "%{$search}%")
                  ->orWhere('m.machine_type', 'LIKE', "%{$search}%")
                  ->orWhere('m.brand_model_no', 'LIKE', "%{$search}%")
                  ->orWhere('m.status', 'LIKE', "%{$search}%")
                  ->orWhere('s.site_name', 'LIKE', "%{$search}%");
            });
        }

        $machines = $query->orderBy('m.created_at', 'desc')->paginate($perPage);

        // --- User List ---
        $usersQuery = DB::table('users as u')
            ->leftJoin('master_sites as s', 'u.site_id', '=', 's.id')
            ->select('u.*', 's.site_name');

        if ($role == 1 && $selectedSiteId) {
            $usersQuery->where('u.site_id', $selectedSiteId);
        } elseif ($role != 1) {
            $usersQuery->where('u.site_id', $userSiteId);
        }

        $users = $usersQuery->orderBy('u.created_at', 'desc')->get();

        // --- Site Dropdown ---
        $sitesQuery = DB::table('master_sites')->select('id', 'site_name')->orderBy('site_name');

        if ($role != 1) {
            $sitesQuery->where('id', $userSiteId);
        }

        $sites = $sitesQuery->get();

        // Add machine usage summary for donut chart
        $usageSummary = $this->getMachineUsageSummary($selectedSiteId ?? $userSiteId);

        return view('admin.machine', compact('machines', 'users', 'sites', 'usageSummary'));

    } catch (\Exception $e) {
        return back()->with('error', 'Error loading data: ' . $e->getMessage());
    }
}


public function getMachineUsageSummary($siteId = null)
{
    $siteId = $siteId ?? Auth::user()->site_id;

    //  Base machine list - NOW INCLUDING machine_no
    $machines = DB::table('master_machine')
        ->where('site_id', $siteId)
        ->select('id', 'machine_name', 'machine_no')  // Added machine_no here
        ->get();

    // Aggregate usage from different activity tables
    $usageData = collect();

    foreach ($machines as $machine) {
        $totalUsage = 0;

        // Example activity tables (customize names & columns below)
        $ploughing = DB::table('area_levelings')
            ->where('machine_id', $machine->id)
            ->sum('hours_used');

        $harvesting = DB::table('crop_protection')
            ->where('machine_id', $machine->id)
            ->sum('hours_used');

        $sowing = DB::table('fertilizer_soil_record')
            ->where('machine_id', $machine->id)
            ->sum('hours_used');

        $transport = DB::table('harvesting_update')
            ->where('machine_id', $machine->id)
            ->sum('hours_used');

        // Sum all usage
        $totalUsage = $ploughing + $harvesting + $sowing + $transport;

        $usageData->push([
            'machine_name' => $machine->machine_name,
            'machine_no'   => $machine->machine_no,  // ✅ Added machine_no here
            'total_usage'  => $totalUsage,
        ]);
    }

    return $usageData;
}

    /**
     * Store a newly created machine
     */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'machine_name' => 'required|string|max:255',
        'machine_no' => 'required|string|max:100|unique:master_machine,machine_no',
        'site_id' => 'required|exists:master_sites,id',
        'machine_type' => 'required|string|max:255',
        'brand_model_no' => 'required|string|max:255',
        'purchase_date' => 'required|date',
        'status' => 'required|in:active,inactive,maintenance',
        'last_service_date' => 'nullable|date',
        'next_service_date' => 'nullable|date|after_or_equal:last_service_date',
        'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    try {
        DB::beginTransaction();

        $documentPath = null;
        $imagePath = null;

        if ($request->hasFile('document')) {
            $documentName = time() . '_' . $request->file('document')->getClientOriginalName();
            $request->file('document')->move(public_path('machine/documents'), $documentName);
            $documentPath = 'machine/documents/' . $documentName;
        }

        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('machine/images'), $imageName);
            $imagePath = 'machine/images/' . $imageName;
        }

        DB::table('master_machine')->insert([
            'machine_name' => $request->machine_name,
            'machine_no' => $request->machine_no,
            'site_id' => $request->site_id,
            'machine_type' => $request->machine_type,
            'brand_model_no' => $request->brand_model_no,
            'purchase_date' => $request->purchase_date,
            'status' => $request->status,
            'last_service_date' => $request->last_service_date,
            'next_service_date' => $request->next_service_date,
            'document_path' => $documentPath,
            'image_path' => $imagePath,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::commit();
        return redirect()->route('machines.index')->with('success', 'Machine added successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error adding machine: ' . $e->getMessage())->withInput();
    }
}


    /**
     * Show the form for editing the specified machine (AJAX)
     */
    public function editMachine($id)
    {
        try {
            $machine = DB::table('master_machine')
                        ->where('id', $id)
                        ->first();

            if (!$machine) {
                return response()->json(['error' => 'Machine not found'], 404);
            }

            return response()->json($machine);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching machine details'], 500);
        }
    }

    /**
     * Update the specified machine
     */
public function update(Request $request, $id)
{
    $machine = DB::table('master_machine')->where('id', $id)->first();
    if (!$machine) {
        return back()->with('error', 'Machine not found.');
    }

    $validator = Validator::make($request->all(), [
        'machine_name' => 'required|string|max:255',
        'machine_no' => 'required|string|max:100|unique:master_machine,machine_no,' . $id,
        'site_id' => 'required|exists:master_sites,id',
        'machine_type' => 'required|string|max:255',
        'brand_model_no' => 'required|string|max:255',
        'purchase_date' => 'required|date',
        'status' => 'required|in:active,inactive,maintenance',
        'last_service_date' => 'nullable|date',
        'next_service_date' => 'nullable|date|after_or_equal:last_service_date',
        'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    try {
        DB::beginTransaction();

        $documentPath = $machine->document_path;
        $imagePath = $machine->image_path;

        if ($request->hasFile('document')) {
            if ($documentPath && file_exists(public_path($documentPath))) {
                unlink(public_path($documentPath));
            }

            $docName = time() . '_' . $request->file('document')->getClientOriginalName();
            $request->file('document')->move(public_path('machine/documents'), $docName);
            $documentPath = 'machine/documents/' . $docName;
        }

        if ($request->hasFile('image')) {
            if ($imagePath && file_exists(public_path($imagePath))) {
                unlink(public_path($imagePath));
            }

            $imgName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('machine/images'), $imgName);
            $imagePath = 'machine/images/' . $imgName;
        }

        DB::table('master_machine')->where('id', $id)->update([
            'machine_name' => $request->machine_name,
            'machine_no' => $request->machine_no,
            'site_id' => $request->site_id,
            'machine_type' => $request->machine_type,
            'brand_model_no' => $request->brand_model_no,
            'purchase_date' => $request->purchase_date,
            'status' => $request->status,
            'last_service_date' => $request->last_service_date,
            'next_service_date' => $request->next_service_date,
            'document_path' => $documentPath,
            'image_path' => $imagePath,
            'updated_at' => now()
        ]);

        DB::commit();
        return redirect()->route('machines.index')->with('success', 'Machine updated successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error updating machine: ' . $e->getMessage())->withInput();
    }
}

public function destroy_machine($id)
{
    $machine = DB::table('master_machine')->where('id', $id)->first();
    if (!$machine) {
        return redirect()->route('machines.index')->with('error', 'Machine not found.');
    }

    // Delete associated files if they exist
    if ($machine->document_path && file_exists(public_path($machine->document_path))) {
        unlink(public_path($machine->document_path));
    }

    if ($machine->image_path && file_exists(public_path($machine->image_path))) {
        unlink(public_path($machine->image_path));
    }

    // Soft delete - update status instead of deleting
    DB::table('master_machine')
        ->where('id', $id)
        ->update(['is_deleted' => 1]);

    return redirect()->route('machines.index')->with('success', 'Machine removed successfull .');
}


  public function getDetails(Machine $machine)
{
    return response()->json([
        'machine_name' => $machine->machine_name,
        'machine_type' => $machine->machine_type,
        'brand_model_no' => $machine->brand_model_no,
        'purchase_date' => $machine->purchase_date,
        'status' => $machine->status,
        'last_service_date' => $machine->last_service_date,
        'next_service_date' => $machine->next_service_date,
        'site_id' => $machine->site_id
    ]);
}
//==============================================================================================================================================

  // Controller methods for fertilizer management

public function indexFertilizerS(Request $request)
{
    try {
        $user = Auth::user();
        $userRole = $user->role;
        $userSiteId = $user->site_id;

        //️ Fetch sites for Admin dropdown
        $sites = DB::table('master_sites')
            ->select('id', 'site_name')
            ->orderBy('site_name')
            ->get();

        // Build fertilizer query with proper joins
        $query = DB::table('master_fertilizer as f')
            ->leftJoin('fertilizers as frt', 'f.fertilizer_id', '=', 'frt.id')
            ->leftJoin('master_sites as s', 'f.site_id', '=', 's.id')
            ->select(
                'f.*',
                's.site_name',
                'frt.fertilizer_name',
                'frt.id as fertilizer_master_id'
            );

        // Admin vs. Site filter
        if ($userRole == 1) {
            if ($request->has('site_id') && $request->site_id != '') {
                $query->where('f.site_id', $request->site_id);
            }
        } else {
            $query->where('f.site_id', $userSiteId);
        }

        // Search filter
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('frt.fertilizer_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('f.brand_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('f.supplier_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        // 🌾 Get fertilizers
        $fertilizers = $query->orderBy('f.id', 'desc')->get();

        // ➕ Add computed fields
        foreach ($fertilizers as $fertilizer) {
            // Get the correct fertilizer_id (from fertilizers master table)
            $fertId = $fertilizer->fertilizer_master_id ?? $fertilizer->fertilizer_id;

            // 🧾 Stock History with proper date field
            $history = DB::table('fertilizer_stock_history')
                ->where('fertilizer_id', $fertId)
                ->select(
                    'id',
                    'fertilizer_id',
                    'added_quantity',
                    'remaining_quantity',
                    'rate',
                    'created_at',
                    DB::raw('DATE(created_at) as purchase_date')
                )
                ->orderBy('created_at', 'asc')
                ->get();

            $fertilizer->stockHistoryRecords = $history;

            // 🧮 Weighted average calculation
            if ($history->isEmpty()) {
                $fertilizer->calculated_amount = $fertilizer->stock_kg * $fertilizer->rate;
            } else {
                $totalQty = 0;
                $totalCost = 0;

                foreach ($history as $h) {
                    $qty = $h->added_quantity;
                    $rate = $h->rate;
                    $totalQty += $qty;
                    $totalCost += ($qty * $rate);
                }

                // Include base stock if not already counted
                $originalQty = $fertilizer->stock_kg - $totalQty;
                if ($originalQty > 0) {
                    $totalQty += $originalQty;
                    $totalCost += ($originalQty * $fertilizer->rate);
                }

                $averageRate = $totalQty > 0 ? ($totalCost / $totalQty) : $fertilizer->rate;
                $fertilizer->calculated_amount = $averageRate * $fertilizer->stock_kg;
            }

            // 🆕 Consumption History with proper date
            $consumptionHistory = DB::table('fertilizer_consumption')
                ->where('fertilizer_id', $fertId)
                ->select(
                    'id',
                    'fertilizer_id',
                    'consumed_quantity',
                    'date',
                    'created_at',
                    DB::raw('DATE(date) as consumption_date')
                )
                ->orderBy('date', 'desc')
                ->get();

            $fertilizer->consumptionHistory = $consumptionHistory;

            // 📊 Chart-related aggregate totals
            // 1️⃣ Total Purchased (all time)
            $totalPurchased = $history->sum('added_quantity');

            // 2️⃣ Total Consumed (all time)
            $totalConsumed = $consumptionHistory->sum('consumed_quantity');

            // 3️⃣ Expiring stock (next 3 months from master_fertilizer)
            $expiringStock = 0;
            if (!empty($fertilizer->expiry_date)) {
                $expiryDate = \Carbon\Carbon::parse($fertilizer->expiry_date);
                $threeMonthsLater = now()->addMonths(3);

                if ($expiryDate->between(now(), $threeMonthsLater)) {
                    $expiringStock = $fertilizer->stock_kg;
                }
            }

            // Attach to fertilizer object for chart
            $fertilizer->total_purchased = $totalPurchased ?? 0;
            $fertilizer->total_consumed = $totalConsumed ?? 0;
            $fertilizer->total_expiring = $expiringStock ?? 0;

            // Add fertilizer name from joined table
            if (empty($fertilizer->fertilizer_name) && !empty($fertilizer->fertilizer_type)) {
                $fertilizer->fertilizer_name = $fertilizer->fertilizer_type;
            }
        }

        // 📊 NEW: Generate Quarterly Chart Data
        $quarterlyData = $this->getQuarterlyChartData($userRole, $userSiteId, $request->site_id ?? null);

        // 🌱 Dropdown data
        $fertilizer_masters = DB::table('fertilizers')
            ->select('id', 'fertilizer_name')
            ->where('site_id', $userSiteId)
            ->orderBy('id', 'desc')
            ->get();

        // 🧩 Edit Mode (when editing)
        $fertilizer = null;
        if ($request->has('edit_id')) {
            $fertilizerQuery = DB::table('master_fertilizer as f')
                ->leftJoin('master_sites as s', 'f.site_id', '=', 's.id')
                ->leftJoin('fertilizers as frt', 'f.fertilizer_id', '=', 'frt.id')
                ->select('f.*', 's.site_name', 'frt.fertilizer_name')
                ->where('f.id', $request->edit_id);

            if ($userRole !== 1) {
                $fertilizerQuery->where('f.site_id', $userSiteId);
            }

            $fertilizer = $fertilizerQuery->first();

            if ($fertilizer) {
                $fertId = $fertilizer->fertilizer_id;

                $fertilizer->stockHistoryRecords = DB::table('fertilizer_stock_history')
                    ->where('fertilizer_id', $fertId)
                    ->select('*', DB::raw('DATE(created_at) as purchase_date'))
                    ->orderBy('created_at', 'desc')
                    ->get();

                $fertilizer->consumptionHistory = DB::table('fertilizer_consumption')
                    ->where('fertilizer_id', $fertId)
                    ->select('*', DB::raw('DATE(date) as consumption_date'))
                    ->orderBy('date', 'desc')
                    ->get();
            }
        }

        return view('admin.fertilizer', compact(
            'fertilizers',
            'fertilizer',
            'sites',
            'userRole',
            'fertilizer_masters',
            'quarterlyData'
        ));

    } catch (\Exception $e) {
        Log::error('Error loading fertilizer data: ' . $e->getMessage());
        return back()->with('error', 'An error occurred while loading data: ' . $e->getMessage());
    }
}

/**
 * Generate quarterly chart data for fertilizers
 */
private function getQuarterlyChartData($userRole, $userSiteId, $adminSelectedSiteId = null)
{
    // Determine which site to use
    $siteId = ($userRole == 1 && $adminSelectedSiteId) ? $adminSelectedSiteId : $userSiteId;

    // Define quarters (April-based fiscal year)
    $quarters = [
        'Q1' => ['start' => '-04-01', 'end' => '-06-30', 'label' => 'Q1 (Apr–Jun)'],
        'Q2' => ['start' => '-07-01', 'end' => '-09-30', 'label' => 'Q2 (Jul–Sep)'],
        'Q3' => ['start' => '-10-01', 'end' => '-12-31', 'label' => 'Q3 (Oct–Dec)'],
        'Q4' => ['start' => '-01-01', 'end' => '-03-31', 'label' => 'Q4 (Jan–Mar)'],
    ];

    $currentYear = now()->year;
    $years = [$currentYear - 1, $currentYear];
    $data = [];

    // Get all unique fertilizers from fertilizers table for this site
    $fertilizers = DB::table('fertilizers')
        ->select('id', 'fertilizer_name')
        ->where('site_id', $siteId)
        ->get();

    \Log::info('Fertilizers found: ', ['count' => $fertilizers->count(), 'site_id' => $siteId]);

    foreach ($years as $year) {
        foreach ($quarters as $qKey => $quarter) {
            // Adjust year for Q4 (Jan-Mar is in the next calendar year)
            $startYear = ($qKey === 'Q4') ? $year + 1 : $year;
            $endYear = ($qKey === 'Q4') ? $year + 1 : $year;

            $startDate = $startYear . $quarter['start'];
            $endDate = $endYear . $quarter['end'];

            foreach ($fertilizers as $fert) {
                // Get purchased quantity for this quarter from stock history
                $purchased = DB::table('fertilizer_stock_history')
                    ->where('fertilizer_id', $fert->id)
                    ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                    ->sum('added_quantity');

                // Get consumed quantity for this quarter from consumption table
                $consumed = DB::table('fertilizer_consumption')
                    ->where('fertilizer_id', $fert->id)
                    ->whereBetween(DB::raw('DATE(date)'), [$startDate, $endDate])
                    ->sum('consumed_quantity');

                // Add to data array (even if zero, to show complete picture)
                $data[] = [
                    'fertilizer' => $fert->fertilizer_name,
                    'quarter' => $quarter['label'],
                    'year' => (string)$year,
                    'purchased' => (float)($purchased ?? 0),
                    'consumed' => (float)($consumed ?? 0),
                ];
            }
        }
    }

    // Debug: Log the data
    \Log::info('Quarterly Chart Data: ', ['count' => count($data), 'sample' => array_slice($data, 0, 5)]);

    return $data;
}


//fetilizer history
public function showFertilizerHistory($id)
{
    $fertilizer = DB::table('master_fertilizer')->where('id', $id)->first();
    $history = DB::table('fertilizer_stock_history')
                ->where('fertilizer_id', $id)
                ->orderByDesc('created_at')
                ->get();

    return view('admin.fertilizer', compact('fertilizer', 'history'));
}


public function addFertilizerStock(Request $request, $id)
{
    try {
        // Step 1: Validate input
        $validated = $request->validate([
            'quantity_to_add' => 'required|numeric|min:0.01',
            'new_rate' => 'required|numeric|min:0',
            'supplier' => 'required|string',
            'date' => 'required',
        ]);

        // Step 2: Fetch fertilizer record
        $fertilizer = DB::table('master_fertilizer')->where('id', $id)->first();

        if (!$fertilizer) {
            return redirect()->route('fertilizer.index')->with('error', 'Fertilizer not found.');
        }

        // Step 3: Start DB Transaction
        DB::beginTransaction();

        // Step 4: Insert previous master data as baseline entry in history if not already added
        $exists = DB::table('fertilizer_stock_history')
            ->where('fertilizer_id', $fertilizer->id)
            ->where('stock_before_addition', $fertilizer->stock_kg)
            ->where('added_quantity', 0)
            ->exists();

        // Step 5: Calculate new stock
        $newStock = $fertilizer->stock_kg + $validated['quantity_to_add'];
        $rateToUse = $validated['new_rate'] ?? $fertilizer->rate;
        $locationToUse = $validated['storage_location'] ?? $fertilizer->storage_location;

        // Step 6: Insert new entry in stock history
        DB::table('fertilizer_stock_history')->insert([
            'fertilizer_id'         => $fertilizer->id,
            'fertilizer_name'       => $fertilizer->fertilizer_name,
            'fertilizer_type'       => $fertilizer->fertilizer_type,
            'brand_name'            => $fertilizer->brand_name,
            'uom'                   => $fertilizer->uom,
            'stock_before_addition' => $fertilizer->stock_kg,
            'added_quantity'        => $validated['quantity_to_add'],
            'remaining_quantity'    => $validated['quantity_to_add'], // ✅ FIFO ke liye zaroori
            'rate'                  => $rateToUse,
            'storage_location'      => $validated['supplier'],
            'date'                  => $validated['date'],
            'site_id'               => $fertilizer->site_id,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Step 7: Update stock in master
        DB::table('master_fertilizer')->where('id', $id)->update([
            'stock_kg' => $newStock,
            'rate' => $rateToUse,
            //'storage_location' => $locationToUse,
            'updated_at' => now(),
        ]);

        DB::commit();

        return redirect()->route('fertilizer.index')->with('success', 'Fertilizer stock updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('fertilizer.index')->with('error', 'Error updating stock: ' . $e->getMessage());
    }
}



public function getFertilizerDetails($id)
{
    $fertilizer = DB::table('master_fertilizer as mf')
        ->leftJoin('fertilizers as f', 'mf.fertilizer_id', '=', 'f.id')
        ->select('mf.*', 'f.fertilizer_name')
        ->where('mf.id', $id)
        ->first();

    if (!$fertilizer) {
        return response()->json(['error' => 'Fertilizer not found.'], 404);
    }

    // Total added quantity (all time)
    $totalAddedStock = (float) DB::table('fertilizer_stock_history')
        ->where('fertilizer_id', $id)
        ->sum('added_quantity');

    // Total stock amount (all time added * their rate)
    $totalStockAmount = (float) DB::table('fertilizer_stock_history')
        ->where('fertilizer_id', $id)
        ->select(DB::raw('COALESCE(SUM(added_quantity * rate), 0) as total_amount'))
        ->value('total_amount');

    // If remaining_quantity column exists, use it (preferred)
    if (Schema::hasColumn('fertilizer_stock_history', 'remaining_quantity')) {
        $currentStock = (float) DB::table('fertilizer_stock_history')
            ->where('fertilizer_id', $id)
            ->sum('remaining_quantity');

        $currentStockAmount = (float) DB::table('fertilizer_stock_history')
            ->where('fertilizer_id', $id)
            ->select(DB::raw('COALESCE(SUM(remaining_quantity * rate), 0) as current_amount'))
            ->value('current_amount');
    } else {
        // Fallback: compute current stock from additions minus consumptions
        $totalConsumed = (float) DB::table('fertilizer_consumption')
            ->where('fertilizer_id', $id)
            ->sum('consumed_quantity');

        $currentStock = max(0, $totalAddedStock - $totalConsumed);

        // Use weighted average rate as fallback for amount (not exact but best effort)
        $avgRate = $totalAddedStock > 0 ? ($totalStockAmount / $totalAddedStock) : (float)($fertilizer->rate ?? 0);
        $currentStockAmount = $currentStock * $avgRate;
    }

    // Format values to 2 decimal places for frontend
    $formatted = [
        'id' => $fertilizer->id,
        'fertilizer_name' => $fertilizer->fertilizer_name,
        'fertilizer_type' => $fertilizer->fertilizer_type ?? 'N/A',
        'brand_name' => $fertilizer->brand_name ?? 'N/A',

        'stock_kg' => number_format((float)$fertilizer->stock_kg, 2, '.', ''),

        // summary values (formatted)
        'total_stock' => number_format($totalAddedStock, 2, '.', ''),
        'total_stock_amount' => number_format($totalStockAmount, 2, '.', ''),
        'current_stock' => number_format($currentStock, 2, '.', ''),
        'current_stock_amount' => number_format($currentStockAmount, 2, '.', ''),

        // raw values for debug if you want to inspect
        'raw' => [
            'master_stock_kg' => (float)$fertilizer->stock_kg,
            'total_added_raw' => $totalAddedStock,
            'total_amount_raw' => $totalStockAmount,
            'current_stock_raw' => $currentStock,
            'current_amount_raw' => $currentStockAmount,
        ]
    ];

    // Warning if master and calculated remaining mismatch (helps debug)
    $calcMasterCheck = abs((float)$fertilizer->stock_kg - $currentStock);
    if ($calcMasterCheck > 0.01) {
        $formatted['warning'] = "Master stock ({$fertilizer->stock_kg}) and history-sum ({$currentStock}) mismatch.";
    }

    return response()->json($formatted);
}





public function getFertilizerHistory($id)
{
    // Stock history (additions)
    $stockHistory = DB::table('fertilizer_stock_history')
        ->select(
            'id',
            'fertilizer_id',
            'added_quantity as added_qty',
            DB::raw('0 as consumed_qty'),
            'rate',
            DB::raw('(added_quantity * rate) as total_price'),
            'storage_location as location',
            'created_at'
        )
        ->where('fertilizer_id', $id);

    // Consumption history
    $consumptionHistory = DB::table('fertilizer_consumption')
        ->select(
            'id',
            'fertilizer_id',
            DB::raw('0 as added_qty'),
            'consumed_quantity as consumed_qty',
            'rate',
            DB::raw('(consumed_quantity * rate) as total_price'),
            DB::raw('NULL as location'),
            'created_at'
        )
        ->where('fertilizer_id', $id);

    // 🚨 Important: don't call ->get() before unionAll
    $history = $stockHistory
        ->unionAll($consumptionHistory)
        ->orderBy('created_at', 'desc'); // this still won’t work directly with union

    // Workaround for ordering union query
    $history = DB::query()
        ->fromSub($history, 'combined')
        ->orderBy('created_at', 'desc')
        ->get();

    // Format date
    $history->map(function ($record) {
        $record->date_formatted = $record->created_at
            ? \Carbon\Carbon::parse($record->created_at)->format('d-m-y')
            : '—';
        return $record;
    });

    return response()->json($history);
}




public function storefertilizers(Request $request)
{
    try {
        $user = Auth::user();
        $userRole = $user->role;
        $userSiteId = $user->site_id;

        // Determine which site_id to use
        $siteId = ($userRole == 1) ? $request->site_id : $userSiteId;

        // Validate that site_id is provided
        if (!$siteId) {
            return redirect()->back()->with('error', 'Site selection is required!');
        }

        // Validate request inputs
        $validator = Validator::make($request->all(), [
            'fertilizer_id' => 'required|integer',
            'fertilizer_type' => 'required|string|max:255',
            'stock_kg' => 'required|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'brand_name' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'supplier_name' => 'nullable|string|max:255',
            'uom' => 'required|string|max:20',
            'rate' => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check duplicate: same fertilizer_id + fertilizer_type in same site
        $duplicate = DB::table('master_fertilizer')
            ->where('fertilizer_id', $request->fertilizer_id)
            ->where('fertilizer_type', $request->fertilizer_type)
            ->where('site_id', $siteId)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors(['fertilizer_id' => 'This fertilizer with this type already exists.'])->withInput()->with('error', 'This fertilizer with the same type already exists for this site.');
        }

        $insertId = DB::table('master_fertilizer')->insertGetId([
            'fertilizer_id' => $request->fertilizer_id,
            'purchase_date' => $request->purchase_date,
            'site_id' => $siteId,
            'fertilizer_type' => $request->fertilizer_type,
            'stock_kg' => $request->stock_kg,
            'brand_name' => $request->brand_name,
            'expiry_date' => $request->expiry_date,
            'supplier_name' => $request->supplier_name,
            'uom' => $request->uom,
            'rate' => $request->rate,
            'storage_location' => $request->storage_location,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($insertId) {
            DB::table('fertilizer_stock_history')->insert([
                'fertilizer_id' => $insertId,
                'fertilizer_type' => $request->fertilizer_type,
                'brand_name' => $request->brand_name,
                'uom' => $request->uom,
                'stock_before_addition' => 0,
                'added_quantity' => $request->stock_kg,
                'remaining_quantity' => $request->stock_kg,
                'rate' => $request->rate,
                'site_id' => $siteId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('fertilizer.index')->with('success', 'Fertilizer added successfully!');

    } catch (\Exception $e) {
        \Log::error('Error adding fertilizer: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while adding fertilizer.');
    }
}

  public function updatefertilizers(Request $request, $id)
  {
      DB::table('master_fertilizer')->where('id', $id)->update([
          'fertilizer_name' => $request->fertilizer_name,
          'purchase_date' => $request->purchase_date,
                  'site_id' => $request->site_id, // <-- add this line

          'fertilizer_type' => $request->fertilizer_type,
          'stock_kg'   => $request->stock_kg,
          // We don't update stock_kg here as it might be managed separately
          'brand_name' => $request->brand_name,
          'expiry_date' => $request->expiry_date,
          'supplier_name' => $request->supplier_name,
          'uom' => $request->uom,
            'rate' => $request->rate,
          'storage_location' => $request->storage_location,
          'updated_at' => now()
      ]);

      return redirect()->route('fertilizer.index')->with('success', 'Fertilizer updated!');
  }

  public function editfertilizer($id)
  {
      $fertilizer = DB::table('master_fertilizer')->where('id', $id)->first();
      $fertilizers = DB::table('master_fertilizer')->get();

      return view('admin.fertilizer', compact('fertilizer', 'fertilizers'));
  }

  public function deletefertilizers($id)
  {
      DB::table('master_fertilizer')->where('id', $id)->delete();
      return redirect()->route('fertilizer.index')->with('success', 'Fertilizer deleted!');
  }


//==============================================================================================================================================
public function indexTractor(Request $request)
{
    $user = Auth::user(); // Logged-in user
    $userRole = $user->role;
    $userSiteId = $user->site_id; // Assuming `site_name` holds site ID

    $tractorsQuery = DB::table('master_tractors as m')
        ->leftJoin('master_sites as s', 'm.site_id', '=', 's.id')
        ->select('m.*', 's.site_name')
        ->where('m.is_deleted', 0);

    // Role-based site filtering
    if ($userRole == 2) {
        $tractorsQuery->where('m.site_id', $userSiteId);
    } elseif ($userRole == 3) {
        $tractorsQuery->where('m.site_id', $userSiteId);

    } elseif ($userRole == 1) {
        // Admin (role 1) can filter by site if provided in request
        if ($request->has('site_id') && !empty($request->input('site_id'))) {
            $tractorsQuery->where('m.site_id', $request->input('site_id'));
        }
        // No site_id provided: admin sees all
    }

    $tractors = $tractorsQuery->orderBy('m.id', 'DESC')->get();

    $sites = DB::table('master_sites')->orderBy('site_name')->get();

    return view('admin.tractor', compact('tractors', 'sites'));
}


  public function storeTractor(Request $request)
{
    // Create directories if they don't exist
    if (!file_exists(public_path('tractor/documents'))) {
        mkdir(public_path('tractor/documents'), 0777, true);
    }

    if (!file_exists(public_path('tractor/images'))) {
        mkdir(public_path('tractor/images'), 0777, true);
    }

    // Handle document upload
    $documentName = null;
    if ($request->hasFile('upload_document')) {
        $documentName = time() . '_doc.' . $request->upload_document->extension();
        $request->upload_document->move(public_path('tractor/documents'), $documentName);
    }

    // Handle image upload
    $imageName = null;
    if ($request->hasFile('tractor_image')) {
        $imageName = time() . '_img.' . $request->tractor_image->extension();
        $request->tractor_image->move(public_path('tractor/images'), $imageName);
    }

    // Insert record
    DB::table('master_tractors')->insert([
        'tractor_name'    => $request->tractor_name,
        'tractor_type'    => $request->tractor_type,
        'tractor_no'    => $request->asset_no,
        'brand_model'     => $request->brand_model,
        'site_id'         => $request->site_id,
        'purchase_date'   => $request->purchase_date,
        'upload_document' => $documentName,
        'machine_status'  => $request->machine_status,
        'tractor_image'   => $imageName,
        'service_prev'    => $request->service_prev,
        'service_upco'    => $request->service_upco,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return redirect()->route('tractors.index')->with('success', 'Tractor added successfully!');
}


public function updateTractor(Request $request, $id)
{
    $user = Auth::user(); // Get logged-in user

    // Create directories if they don't exist
    if (!file_exists(public_path('tractor/documents'))) {
        mkdir(public_path('tractor/documents'), 0777, true);
    }

    if (!file_exists(public_path('tractor/images'))) {
        mkdir(public_path('tractor/images'), 0777, true);
    }

    // Handle document upload
    $documentName = null;
    if ($request->hasFile('upload_document')) {
        $documentName = time() . '_doc.' . $request->upload_document->extension();
        $request->upload_document->move(public_path('tractor/documents'), $documentName);
    }

    // Handle image upload
    $imageName = null;
    if ($request->hasFile('tractor_image')) {
        $imageName = time() . '_img.' . $request->tractor_image->extension();
        $request->tractor_image->move(public_path('tractor/images'), $imageName);
    }

    // Use user's site_id, ignore whatever comes from form
    $updateData = [
        'tractor_name'    => $request->tractor_name,
        'tractor_type'    => $request->tractor_type,
        'tractor_no'    => $request->asset_no,
        'brand_model'     => $request->brand_model,
        'site_id'         => $user->site_id,
        'purchase_date'   => $request->purchase_date,
        'machine_status'  => $request->machine_status,
        'service_prev'    => $request->service_prev,
        'service_upco'    => $request->service_upco,
        'updated_at'      => now(),
    ];

    if ($documentName) {
        $updateData['upload_document'] = $documentName;
    }

    if ($imageName) {
        $updateData['tractor_image'] = $imageName;
    }

    DB::table('master_tractors')->where('id', $id)->update($updateData);

    return redirect()->route('tractors.index')->with('success', 'Tractor updated successfully!');
}


public function destroyTractor($id)
{
    // Get tractor to delete associated files
    $tractor = DB::table('master_tractors')->where('id', $id)->first();

    // Delete image if exists
    if ($tractor->tractor_image && file_exists(public_path('tractor/images/' . $tractor->tractor_image))) {
        unlink(public_path('tractor/images/' . $tractor->tractor_image));
    }

    // Delete document if exists
    if ($tractor->upload_document && file_exists(public_path('tractor/documents/' . $tractor->upload_document))) {
        unlink(public_path('tractor/documents/' . $tractor->upload_document));
    }

    // Soft delete: update is_deleted to 1
    DB::table('master_tractors')->where('id', $id)->update(['is_deleted' => 1]);

    return redirect()->route('tractors.index')->with('success', 'Tractor deleted successfully (soft delete)!');
}

    public function searchTractor(Request $request)
    {
        $query = $request->input('query');

        $tractors = DB::table('master_tractors')
            ->where('tractor_name', 'LIKE', "%{$query}%")
            ->orWhere('brand_model', 'LIKE', "%{$query}%")
            ->orWhere('tractor_type', 'LIKE', "%{$query}%")
            ->orderBy('id', 'DESC')
            ->get();

        return view('admin.tractor', compact('tractors'));
    }
   public function diesel()
{
    $user = Auth::user();
    $siteId = $user->site_id;

    // Get diesel data for the logged-in user's site
    $dieselData = DB::table('diesel_stocks')
        ->where('site_id', $siteId)
        ->orderByDesc('id')
        ->get();

    return view('admin.diesel', [
        'dieselData' => $dieselData
    ]);
}
    //==============================================================================================================================================

        // block  add, update, delete
    public function indexblock(Request $request)
    {
        $search = $request->input('search');
        $masterBlocs = DB::table('master_block')
            ->where('name', 'like', "%{$search}%")
            ->orderBy('id', 'desc')
            ->paginate(5);

        return view('admin.block', compact('masterBlocs', 'search'));
    }

    public function storeblock(Request $request)
    {
        $request->validate(['name' => 'required']);
        DB::table('master_block')->insert(['name' => $request->name, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Master Bloc added successfully');
    }

    public function edit($id)
    {
        return response()->json(DB::table('master_block')->where('id', $id)->first());
    }

    public function updateblock(Request $request, $id)
    {
        $request->validate(['name' => 'required']);
        DB::table('master_block')->where('id', $id)->update(['name' => $request->name, 'updated_at' => now()]);
        return back()->with('success', 'Master Bloc updated successfully');
    }

    public function destroyblock($id)
    {
        DB::table('master_block')->where('id', $id)->delete();
        return back()->with('success', 'Master Bloc deleted successfully');
    }


    //==============================================================================================================================================



public function indexdiesel(Request $request)
{
    $currentUser = Auth::user();

    // 1. Fetch all 'in' entries (in order)
    $inEntries = DB::table('diesel_stocks')
        ->where('site_id', $currentUser->site_id)
       // ->where('type', 'in')
        ->orderBy('date_of_entry')
        ->orderBy('id')
        ->get();

    // 2. Calculate total stock IN
    $totalIn = $inEntries->sum('diesel_stock');

    // 3. Calculate current diesel balance (remaining)
    $latestBalance = DB::table('diesel_stocks')
        ->where('site_id', $currentUser->site_id)
       // ->where('type', 'in')
        ->sum('diesel_stock'); // because stock gets auto-decreased

    // 4. Usage = totalIn - currentRemaining
    $usedTotal = $totalIn - $latestBalance;

    // 5. Apply FIFO distribution to calculate how much used from each IN entry
    $remainingToSubtract = $usedTotal;
    $fifoStack = [];

    foreach ($inEntries as $entry) {
        $entryStock = $entry->diesel_stock;
        $usedFromThis = 0;
        $dateZero = null;

        if ($remainingToSubtract > 0) {
            $usedFromThis = min($entryStock, $remainingToSubtract);
            $remainingToSubtract -= $usedFromThis;

            if ($entryStock == $usedFromThis) {
                $dateZero = now()->toDateString(); // approximate - real last usage date not tracked
            }
        }

        $fifoStack[] = [
            'date_in' => $entry->date_of_entry,
            'stock_in' => $entryStock,
            'used' => $usedFromThis,
            'date_zero' => $dateZero,
            'rate' => $entry->rate_per_liter,
            'site' => $entry->site_id,
        ];
    }

    $consumptionReport = collect($fifoStack);

    // Load diesel entries for table display
    $query = DB::table('diesel_stocks')
        ->where('site_id', $currentUser->site_id)
        ->orderByDesc('id');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('diesel_stock', 'like', "%{$search}%")
              ->orWhere('rate_per_liter', 'like', "%{$search}%");
        });
    }

    $diesels = $query->get();

    $sites = DB::table('master_sites')->orderBy('site_name')->get();

    // Monthly summary logic
    $monthlySummary = DB::table('diesel_stocks')
        ->select(
            DB::raw("DATE_FORMAT(date_of_entry, '%Y-%m') AS month"),
            DB::raw("SUM(CASE WHEN type = 'in' THEN diesel_stock ELSE 0 END) AS total_in"),
            DB::raw("0 AS total_out") // Out is now calculated from logic, not table
        )
        ->where('site_id', $currentUser->site_id)
        ->groupBy(DB::raw("DATE_FORMAT(date_of_entry, '%Y-%m')"))
        ->orderBy('month', 'desc')
        ->get();

    // Add balance to summary
    $runningMonthly = 0;
    foreach ($monthlySummary as $summary) {
        $runningMonthly += $summary->total_in - $summary->total_out;
        $summary->balance = $runningMonthly;
    }

            //Nigar
        $dieselHistory = DB::table('diesel_stock_history')
            ->where('site_id', $currentUser->site_id)
            ->orderBy('date_of_entry')
            ->orderBy('id')
            ->get()
            ->groupBy('date_of_entry');

        $stockReport = [];
         $data = [];
        $sr = 1;
        $runningStock = 0;
        $total_hsd_consumed = 0;
        $totalPurchased = 0;
        $totalConsumed  = 0;
        $totalPurchasedCost = 0;
        $totalConsumedCost  = 0;
        foreach ($dieselHistory as $date => $entries) {
    if ($sr == 1) {
        $openingStock   = $entries->first()->added_quantity;
        $purchasedLiters = $entries->skip(1)->sum('added_quantity');

    } else {
        $openingStock   = $runningStock;
        $purchasedLiters = $entries->sum('added_quantity');
    }
    $totalPurchasedLiters = $entries->sum('added_quantity');
    $consumedLiters = $entries->sum('consumed_quantity');

    // Closing stock
    $closingStock = $openingStock + $purchasedLiters - $consumedLiters;
    $runningStock = $closingStock;

    // Totals
    $totalPurchased += $totalPurchasedLiters;
    $totalConsumed  += $consumedLiters;

    // Cost calculation (loop each entry for rate × qty)
    foreach ($entries as $entry) {
        if ($entry->added_quantity > 0) {
            $totalPurchasedCost += $entry->added_quantity * $entry->rate_per_liter;
        }
        if ($entry->consumed_quantity > 0) {
            $totalConsumedCost += $entry->consumed_quantity * $entry->rate_per_liter;
        }
    }

    $stockReport[] = [
        'sr_no'          => $sr++,
        'date'           => $date,
        'opening_stock'  => $openingStock,
        'purchased_stock'=> $purchasedLiters,
        'consumed_stock' => $consumedLiters,
        'closing_stock'  => $closingStock,
    ];
}


        return view('admin.diesel', compact('diesels', 'sites', 'consumptionReport', 'monthlySummary', 'stockReport', 'totalPurchased', 'totalPurchasedCost', 'totalConsumed', 'totalConsumedCost'));
    }

    public function storeConsumption(Request $request)
{
    $user = Auth::user();
    $userId = $user->id;

    $request->validate([
        'site_id' => 'required|integer',
        'diesel_consumption' => 'required|numeric|min:0.01',
        'date_of_entry' => 'required|date',
        'remark' => 'nullable|string|max:255'
    ]);

    $requestedConsumption = $request->input('diesel_consumption', 0);
    $siteId = $request->input('site_id');

    if ($requestedConsumption <= 0) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid consumption amount.'
        ], 400);
    }

    // ✅ Check total available stock
    $availableStock = DB::table('diesel_stocks')
        ->where('site_id', $siteId)
        ->sum('diesel_stock');

    if ($availableStock < $requestedConsumption) {
        return response()->json([
            'status' => 'error',
            'error' => 'INSUFFICIENT_DIESEL',
            'message' => 'Not enough diesel stock available.',
            'requested_liters' => $requestedConsumption,
            'available_liters' => $availableStock,
            'shortage' => $requestedConsumption - $availableStock
        ], 400);
    }

    // ✅ FIFO Logic for Consumption
    DB::beginTransaction();
    try {
        $dieselCost = 0;
        $dieselRate = 0;
        $remainingConsumption = $requestedConsumption;
        $consumptionDetails = [];

        $stocks = DB::table('diesel_stocks')
            ->where('site_id', $siteId)
            ->where('diesel_stock', '>', 0)
            ->orderBy('date_of_purchase', 'asc')
            ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

        foreach ($stocks as $stock) {
            if ($remainingConsumption <= 0) break;

            $litersTaken = min($stock->diesel_stock, $remainingConsumption);
            $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

            // Update diesel stock
            DB::table('diesel_stocks')->where('id', $stock->id)->update([
                'diesel_stock' => $stock->diesel_stock - $litersTaken,
                'diesel_consumption' => $diesel_consumption,
                'updated_at' => now()
            ]);

            $dieselCost += $litersTaken * $stock->rate_per_liter;
            $remainingConsumption -= $litersTaken;

            $consumptionDetails[] = [
                'stock_id' => $stock->id,
                'liters_used' => $litersTaken,
                'site_id' => $stock->site_id,
                'rate' => $stock->rate_per_liter,
                'cost' => $litersTaken * $stock->rate_per_liter,
                'previous_stock' => $stock->diesel_stock
            ];

            if ($dieselRate == 0) {
                $dieselRate = $stock->rate_per_liter;
            }
        }

        // ✅ Insert Diesel Consumption Entry
        foreach ($consumptionDetails as $detail) {
            DB::table('diesel_consumption')->insert([
                'stock_id'   => $detail['stock_id'],
                'liters_used'=> $detail['liters_used'],
                'rate'       => $detail['rate'],
                'cost'       => $detail['cost'],
                'date'       => $request->date_of_entry,
                'activity'     => $request->remark,
                'user_id'    => $userId,

                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ✅ Add to Stock History
            DB::table('diesel_stock_history')->insert([
                'diesel_stock_id'   => $detail['stock_id'],
                'site_id'   => $detail['site_id'],
                'type'   => 'Consumption',
                'note'   => $request->remark ?? 'Manual Consumption Entry',
                'date_of_entry' => $request->date_of_entry,
                'stock_before_addition'=> $detail['previous_stock'],
                'consumed_quantity'=> $detail['liters_used'],
                'rate_per_liter'       => $detail['rate'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::commit();
         return back()->with('error', 'Diesel consumption recorded successfully.');


    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());

    }
}

   public function getDieselConsumptionByDate(Request $request)
{
    $date = $request->input('date');
    $currentUser = Auth::user();

    $consumptions = DB::table('diesel_stock_history as dsh')
        ->join('diesel_consumption as dc', function($join) {
            $join->on('dc.date', '=', 'dsh.date_of_entry')
                 ->on('dc.activity', '=', 'dsh.note')
                 ->on('dc.liters_used', '=', 'dsh.consumed_quantity');
                 //->on('dc.rate', '=', 'dsh.rate_per_liter');
        })
        ->join('users as u', 'dc.user_id', '=', 'u.id')
        ->leftJoin('master_tractors as t', function ($join) {
            $join->whereRaw("FIND_IN_SET(t.id, dc.tractor_id)");
        })
        ->where('dsh.site_id', $currentUser->site_id)
        ->where('dsh.type', 'Consumption')
        ->whereDate('dsh.date_of_entry', $date)
        ->groupBy(
            'dc.id', 'dc.liters_used', 'dc.rate',
            'dc.cost', 'dc.activity', 'u.name', 'dsh.date_of_entry'
        )
        ->select(
            'dc.id',
            'dc.liters_used',
            'dc.rate',
            'dc.cost',
            'dc.activity',
            DB::raw('NULLIF(GROUP_CONCAT(t.tractor_name SEPARATOR ", "), "") as tractor_name'),
            'u.name as supervisor_name',
            'dsh.date_of_entry as activity_date'
        )
        ->get();

    return response()->json($consumptions);
}


    public function getDieselpurchasedByDate(Request $request)
{
    $date = $request->input('date');
    $currentUser = Auth::user();
    // Get first (opening stock) id for that date
    $openingStockId = DB::table('diesel_stock_history')
         ->where('site_id', $currentUser->site_id)
        ->where('type', 'add diesel')
        ->min('id');

    // Get purchased entries (excluding opening stock)
    $consumptions = DB::table('diesel_stock_history as dsh')
        ->where('dsh.site_id', $currentUser->site_id)
        ->where('dsh.type', 'add diesel')
        ->whereDate('dsh.date_of_entry', $date)
        ->when($openingStockId, function ($q) use ($openingStockId) {
            $q->where('dsh.id', '!=', $openingStockId);
        })
        ->select(
            'dsh.id',
            'dsh.added_quantity',
            'dsh.rate_per_liter',
            'dsh.date_of_entry as activity_date'
        )
        ->get();


    return response()->json($consumptions);
}



 public function storediesel(Request $request)
{
    $user = Auth::user();

    $request->validate([

        'diesel_stock' => 'required|numeric',
        'date_of_entry' => 'required|date',
        'rate_per_liter' => 'nullable|numeric',
    ]);

    $insertedId = DB::table('diesel_stocks')->insertGetId([

        'diesel_stock' => $request->diesel_stock,
        'site_id' => $user->site_id,
        'date_of_entry' => $request->date_of_entry,
        'rate_per_liter' => $request->rate_per_liter,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $previous = DB::table('diesel_stocks')
    ->where('date_of_entry', $request->date_of_entry)
    ->latest('id')
    ->first();
    if(!empty($previous)){

    }else{
    }
     DB::table('diesel_stock_history')->insert([
                        'diesel_stock_id'   => $insertedId,
                        'site_id'   => $user->site_id,
                        'type'   => 'add diesel',
                        'note'   => 'first entry',
                        'date_of_entry' => $request->date_of_entry,
                        'stock_before_addition'=> 0,
                        'added_quantity'=> $request->diesel_stock,
                        'rate_per_liter'       => $request->rate_per_liter,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

    return redirect()->route('diesels.index')->with('success', 'Diesel entry added successfully!');
}


    public function updatediesel(Request $request, $id)
    {
        $request->validate([
            'diesel_stock' => 'required|numeric',

            'date_of_purchase' => 'required|date',
            'rate_per_liter' => 'required|numeric',
        ]);

        DB::table('diesel_stocks')->where('id', $id)->update([
            'diesel_stock' => $request->diesel_stock,
              'site_id' => $request->site_id,

            'date_of_purchase' => $request->date_of_purchase,
            'rate_per_liter' => $request->rate_per_liter,
            'updated_at' => now(),
        ]);

        return redirect('/diesels')->with('success', 'Diesel record updated!');
    }

    public function destroydiesel($id)
    {
        DB::table('diesel_stocks')->where('id', $id)->delete();
        return redirect('/diesels')->with('success', 'Diesel record deleted!');
    }
//==============================================================================================================================================

    //MANPOWER ADD EDIT DELETE
 public function indexManpower(Request $request)
{
    try {
        // Get authenticated user
        $user = Auth::user();
        $userRole = $user->role; // Assuming '2' is Supervisor, '1' is Admin
        $userSiteId = $user->site_id;

        // Build manpower query with join to sites
        $query = DB::table('master_manpower as m')
            ->leftJoin('master_sites as s', 'm.site_id', '=', 's.id')
            ->Join('manpower_type as t', 'm.type', '=', 't.id')
            ->select(
                'm.*',
                's.site_name',
                't.type as type_name'
            );

        // Role-based filtering
        if ($userRole !== '1') {
            // Non-Admins (e.g., Supervisors) see only their site's data
            $query->where('m.site_id', $userSiteId);
        } else {
            // Role 1 (Admin) - Apply site filter if provided
            $siteFilter = $request->input('site_filter', '');
            if (!empty($siteFilter)) {
                $query->where('m.site_id', $siteFilter);
            }
        }

        // Search filter
        $search = $request->input('search', '');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('m.category', 'LIKE', "%$search%")
                  ->orWhere('m.type', 'LIKE', "%$search%")
                  ->orWhere('m.no_of_person', 'LIKE', "%$search%")
                  ->orWhere('m.rate', 'LIKE', "%$search%")
                  ->orWhere('s.site_name', 'LIKE', "%$search%");
            });
        }

        // Get paginated manpower records
        $manPowers = $query->orderByDesc('m.id')->paginate(15);

        // Fetch categories and types (assuming they are distinct values from the table)
        $categories = DB::table('master_manpower')
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        $types = DB::table('manpower_type')
            ->select('id', 'type')->get();

        // Fetch sites for dropdown
        $sites = DB::table('master_sites')
            ->select('id', 'site_name')
            ->orderBy('site_name')
            ->get();

        return view('admin.manpower', compact('manPowers', 'categories', 'types', 'sites', 'search'));

    } catch (\Exception $e) {
        \Log::error('Error loading manpower data: ' . $e->getMessage());
        return back()->with('error', 'An error occurred while loading manpower data.');
    }
}

public function storemanpower(Request $request)
{
    // Get the logged-in user's site_id
    $userSiteId = Auth::user()->site_id;
//dd($request->all());
    // Check if a record with the same category and type already exists for this site
    $existingManpower = DB::table('master_manpower')
                        ->where('category', $request->category)
                        ->where('type', $request->type)
                        ->where('site_id', $userSiteId)
                        ->first();
 
    if (!empty($existingManpower)) {
        // If it exists, return with an error message
        return back()->with('error', 'Manpower with this category and type already exists for your site.');
    }

    // If it doesn't exist, proceed with the insertion using user's site_id
    DB::table('master_manpower')->insert([
        'category' => $request->category,
        'site_id' => $userSiteId, // Use logged-in user's site_id
        'type' => $request->type,
        'no_of_person' => $request->no_of_person,
        'rate' => $request->rate,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('success', 'Man Power added successfully');
}

public function editmanpower($id)
{
    $data = DB::table('master_manpower')->where('id', $id)->first();
    return response()->json($data);
}

public function updatemanpower(Request $request, $id)
{
    // Get the logged-in user's site_id
    $userSiteId = Auth::user()->site_id;

    DB::table('master_manpower')->where('id', $id)->update([
        'category' => $request->category,
        'site_id' => $userSiteId, // Use logged-in user's site_id
        'type' => $request->type,
        'no_of_person' => $request->no_of_person,
        'rate' => $request->rate,
        'updated_at' => now(),
    ]);

    return back()->with('success', 'Updated successfully');
}

    public function destroymanpower($id)
    {
        DB::table('master_manpower')->where('id', $id)->delete();
        return back()->with('success', 'Deleted successfully');
    }

    // master_irrigation_types add, update, delete
    public function indexmaster_irrigation_types(Request $request)
    {
        $query = DB::table('master_irrigation_types');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type_name', 'like', "%$search%");

            });
        }

        $master_irrigation_types = $query->orderByDesc('id')->get();

        return view('admin.master_irrigation_types', compact('master_irrigation_types'));
    }

    public function storedmaster_irrigation_types(Request $request)
    {


        DB::table('master_irrigation_types')->insert([
            'type_name' => $request->type_name,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/master_irrigation_types')->with('success', 'master_irrigation_types added successfully!');
    }

    public function updatedmaster_irrigation_types(Request $request, $id)
    {
        $request->validate([
            'type_name' => 'required|string|max:255',
        ]);

        DB::table('master_irrigation_types')->where('id', $id)->update([
            'type_name' => $request->type_name,

            'updated_at' => now(),
        ]);

        return redirect('/master_irrigation_types')->with('success', 'master_irrigation_types record updated!');
    }

    public function destroymaster_irrigation_types($id)
    {
        DB::table('master_irrigation_types')->where('id', $id)->delete();
        return redirect('/master_irrigation_types')->with('success', 'master_irrigation_types record deleted!');
    }
//==============================================================================================================================================
    // master_capacities add, update, delete
    public function indexMasterCapacities(Request $request)
    {
        $query = DB::table('master_capacities');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('capacity_value', 'like', "%$search%");
        }

        $capacities = $query->orderBy('capacity_value', 'asc')->get();

        return view('admin.master_capacities', compact('capacities'));
    }

    public function storeMasterCapacities(Request $request)
    {
        $request->validate([
            'capacity_value' => 'required|string|max:255'
        ]);

        DB::table('master_capacities')->insert([
            'capacity_value' => $request->capacity_value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/master_capacities')->with('success', 'Capacity added successfully!');
    }

    public function updateMasterCapacities(Request $request, $id)
    {
        $request->validate([
            'capacity_value' => 'required|string|max:255'
        ]);

        DB::table('master_capacities')->where('id', $id)->update([
            'capacity_value' => $request->capacity_value,
            'updated_at' => now(),
        ]);

        return redirect('/master_capacities')->with('success', 'Capacity updated successfully!');
    }

    public function destroyMasterCapacities($id)
    {
        DB::table('master_capacities')->where('id', $id)->delete();
        return redirect('/master_capacities')->with('success', 'Capacity deleted successfully!');
    }
    //==============================================================================================================================================
// public function indexWaterSource(Request $request)
//     {
//         try {
//             // Get authenticated user
//             $user = Auth::user();
//             $userRole = $user->role; // Assuming '2' is Supervisor, '1' is Admin
//             $userSiteId = $user->site_id;

//             // Build water source query with join to sites
//             $query = DB::table('water_sources as w')
//                 ->leftJoin('master_sites as s', 'w.site_id', '=', 's.id')
//                 ->select('w.id', 'w.name', 'w.capacity_lph', 's.site_name', 's.id as site_id');

//             // Role-based filtering
//             if ($userRole !== '1') { // Non-Admins (e.g., Supervisors) see only their site's data
//                 $query->where('w.site_id', $userSiteId);
//             }

//             // Site filter
//             if ($request->filled('site_id')) {
//                 $siteId = $request->site_id;
//                 $query->where('w.site_id', $siteId);
//                 // For Supervisors, ensure siteId matches their site if set
//                 if ($userRole !== '1' && $siteId != $userSiteId) {
//                     $query->where('w.site_id', $userSiteId); // Override to user's site
//                 }
//             }

//             // Get paginated water source records with unique constraint
//             $water_sources = $query->orderBy('w.id', 'asc')
//                 ->get()
//                 ->unique(fn($item) => $item->name . '_' . $item->capacity_lph)
//                 ->values()
//                 ->forPage($request->page ?: 1, 10);

//             // Fetch sites for dropdown
//             $sites = DB::table('master_sites')
//                 ->orderBy('site_name')
//                 ->get();

//             return view('admin.water_sources', compact('water_sources', 'sites'));

//         } catch (\Exception $e) {
//             \Log::error('Error loading water source data: ' . $e->getMessage());
//             return back()->with('error', 'An error occurred while loading water source data.');
//         }
//     }
public function indexWaterSource(Request $request)
{
    try {
        // Get authenticated user
        $user = Auth::user();
        $userRole = $user->role; // Assuming '2' is Supervisor, '1' is Admin
        $userSiteId = $user->site_id;

        // Build water source query with join to sites
        $query = DB::table('water_sources as w')
            ->leftJoin('master_sites as s', 'w.site_id', '=', 's.id')
            ->select('w.id', 'w.name', 'w.capacity_lph', 'w.borewell_no', 'w.plot_name', 's.id as site_id', 'w.power_consumption_kw','w.cost_per_unit', 'w.is_active', 'w.notes');

        // Role-based filtering
        if ($userRole !== '1') { // Non-Admins (e.g., Supervisors) see only their site's data
            $query->where('w.site_id', $userSiteId);
        }

        // Site filter
        if ($request->filled('site_id')) {
            $siteId = $request->site_id;
            $query->where('w.site_id', $siteId);

            // For Supervisors, ensure siteId matches their site if set
            if ($userRole !== '1' && $siteId != $userSiteId) {
                $query->where('w.site_id', $userSiteId); // Override to user's site
            }
        }

        // Get paginated water source records with unique constraint
        $water_sources = $query->orderBy('w.id', 'asc')
            ->get();
            // ->unique(fn($item) => $item->name . '_' . $item->capacity_lph)
            // ->values()
            //- ->forPage($request->page ?: 1, 10);

        // Fetch sites for dropdown
        $sites = DB::table('master_sites')
            ->orderBy('site_name')
            ->get();

        return view('admin.water_sources', compact('water_sources', 'sites'));

    } catch (\Exception $e) {
        \Log::error('Error loading water source data: ' . $e->getMessage());
        return back()->with('error', 'An error occurred while loading water source data.');
    }
}

    public function storeWaterSource(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'capacity_lph' => 'required|numeric',
        'power_consumption_kw' => 'required|numeric',
        'cost_per_unit' => 'required|numeric',
        'notes' => 'nullable|string',
        'is_active' => 'required|boolean',
    ]);

    $user = Auth::user();
    $siteId = $user->role == 1 ? $request->site_id : $user->site_id;
    try {
    $insert = DB::table('water_sources')->insert([
        'name' => $request->name,
        'site_id' => $siteId,
        'capacity_lph' => $request->capacity_lph,
        'borewell_no' => $request->borewell_no,
        'power_consumption_kw' => $request->power_consumption_kw,
        'cost_per_unit' => $request->cost_per_unit,
        'notes' => $request->notes,
        'plot_name' => $request->plot_name,
        'is_active' => $request->is_active,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    if($insert){

    return redirect()->back()->with('success', 'Water source added successfully!');
    }else{
        return redirect()->back()->with('error', 'Water source not added!');
    }

    } catch (\Exception $e) {
        dd($e->getMessage());
        \Log::error('Error loading water source data: ' . $e->getMessage());
        return back()->with('error', 'An error occurred while loading water source data.');
    }
}



   public function updateWaterSource(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'capacity_lph' => 'required|numeric',
        'power_consumption_kw' => 'required|numeric',
        'cost_per_unit' => 'required|numeric',
        'notes' => 'nullable|string',
        'is_active' => 'required|boolean',
    ]);

    $user = Auth::user();
    $siteId = $user->role == 1 ? $request->site_id : $user->site_id;

    DB::table('water_sources')
        ->where('id', $id)
        ->update([
            'name' => $request->name,
            'site_id' => $siteId,
            'capacity_lph' => $request->capacity_lph,
            'power_consumption_kw' => $request->power_consumption_kw,
            'cost_per_unit' => $request->cost_per_unit,
            'notes' => $request->notes,
            'plot_name' => $request->plot_name,
            'is_active' => $request->is_active,
            'updated_at' => now(),
        ]);

    return redirect()->back()->with('success', 'Water source updated successfully!');

}


    public function deleteWaterSource($id)
    {
        DB::table('water_sources')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Water source deleted successfully!');
    }
    //chemicals =======================================================

public function indexChemicals(Request $request)
{
    try {
        $user = Auth::user();
        $userRole = $user->role;
        $userSiteId = $user->site_id;

        // Fetch sites for Admin dropdown
        $sites = DB::table('master_sites')
            ->select('id', 'site_name')
            ->orderBy('site_name')
            ->get();

        // Build chemical query
        $query = DB::table('master_chemical as c')
            ->leftJoin('master_sites as s', 'c.site_id', '=', 's.id')
            ->leftJoin('chemical_master as cm', 'c.chemical_id', '=', 'cm.id')
            ->select('c.*', 's.site_name', 'cm.chemical_name as chemicalNname');

        // Admin can filter by selected site
        if ($userRole == 1) {
            if ($request->has('site_id') && $request->site_id != '') {
                $query->where('c.site_id', $request->site_id);
            }
        } else {
            // Other roles can only see their own site
            $query->where('c.site_id', $userSiteId);
        }

        // Search filter
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('c.chemical_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('c.brand_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('c.supplier_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Get chemical records
        $chemicals = $query->orderBy('c.id', 'desc')->get();

       // Add stock history and calculated amount using FIFO remaining_quantity
        foreach ($chemicals as $chemical) {
            $history = DB::table('chemical_stock_history')
                ->where('chemical_id', $chemical->id)
                ->orderBy('created_at', 'asc') // FIFO order
                ->get();

            $chemical->stockHistoryRecords = $history;

            if ($history->isEmpty()) {
                // No stock history, fallback to master_chemical
                $chemical->calculated_amount = $chemical->stock_qty * $chemical->rate;
            } else {
                $totalQty = 0;
                $totalCost = 0;

                foreach ($history as $h) {
                    $qty = $h->remaining_quantity ?? $h->added_quantity; // ✅ use remaining quantity if available
                    $rate = $h->rate;

                    $totalQty += $qty;
                    $totalCost += ($qty * $rate);
                }

                // master_chemical.stock_qty is already updated on add/consume
                // so directly use remaining quantities for value
                $averageRate = $totalQty > 0 ? ($totalCost / $totalQty) : $chemical->rate;

                $chemical->calculated_amount = $averageRate * $chemical->stock_qty;
            }
        }

        // Convert to paginated collection
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $chemicals->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $paginatedChemicals = new LengthAwarePaginator(
            $currentItems,
            $chemicals->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );

        // For edit mode (existing logic)
        $chemical = null;
        if ($request->has('edit_id')) {
            $chemicalQuery = DB::table('master_chemical as c')
                ->leftJoin('master_sites as s', 'c.site_id', '=', 's.id')
                ->select('c.*', 's.site_name')
                ->where('c.id', $request->edit_id);

            if ($userRole !== 1) {
                $chemicalQuery->where('c.site_id', $userSiteId);
            }

            $chemical = $chemicalQuery->first();

            if ($chemical) {
                $chemical->stockHistoryRecords = DB::table('chemical_stock_history')
                    ->where('chemical_id', $chemical->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        $masterChemical = DB::table('chemical_master')->where('site_id',$userSiteId)->get();
        //dd($masterChemical);
        return view('admin.chemicals', compact('paginatedChemicals', 'chemical', 'sites', 'masterChemical', 'userRole'));

    } catch (\Exception $e) {
        Log::error('Error loading chemical data: ' . $e->getMessage());
        return back()->with('error', 'An error occurred while loading data.');
    }
}

public function storechemicals(Request $request)
{
    // Check duplicate: same fertilizer_id + fertilizer_type in same site
        $duplicate = DB::table('master_chemical')
            ->where('chemical_id', $request->chemical_id)
            ->where('chemical_type', $request->chemical_type)
            ->where('site_id', $request->site_id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors(['chemical_id' => 'This Chemical with this chemical type already exists.'])->withInput()->with('error', 'This fertilizer with the same type already exists for this site.');
        }
  $insertId = DB::table('master_chemical')->insertGetId([

        'chemical_id' => $request->chemical_id,
        'site_id' => $request->site_id,
        'purchase_date' => $request->purchase_date,
        'chemical_type' => $request->chemical_type,
        'stock_qty' => $request->stock_qty,
        'brand_name' => $request->brand_name,
        'expiry_date' => $request->expiry_date,
        'supplier_name' => $request->supplier_name,
        'uom' => $request->UOM,
        'rate' => $request->rate,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
        // Check for duplicate seed and variety for the same site
        $duplicate = DB::table('master_seed')
            ->where('seed_id', $request->seed)
            ->where('seed_variety_id', $request->seed_variety)
            ->where('site_id', $validated['site_id'])
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors(['seed' => 'This Seed with this variety already exists.']);
        }  // Insert the history record

    if($insertId){
     $historyData = [
                'chemical_id' => $insertId,
                //'chemical_name' => $chemical->chemical_name,
                'chemical_type' => $request->chemical_type,
                'brand_name' => $request->brand_name,
                'uom' => $request->UOM,
                'stock_before_addition' => 0,
                'added_quantity' => $request->stock_qty,
                'remaining_quantity' => $request->stock_qty,
                'rate' =>$request->rate,
               //'storage_location' => $validated['storage_location'] ?? $chemical->storage_location,
                'site_id' => $request->site_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $insert = DB::table('chemical_stock_history')->insert($historyData);
}

    return redirect()->back()->with('success', 'Chemical added successfully!');
}
public function updatechemicals(Request $request, $id)
{
    DB::table('master_chemical')
        ->where('id', $id)
        ->update([
            'chemical_name' => $request->chemical_name,
            'site_id' => $request->site_id,
            //'chemical_id' => $request->chemical_id,
            'purchase_date' => $request->purchase_date,
            'chemical_type' => $request->chemical_type,
            //'stock_qty' => $request->stock_qty,
            'brand_name' => $request->brand_name,
            'expiry_date' => $request->expiry_date,
            'supplier_name' => $request->supplier_name,
            'uom' => $request->uom,
            //'rate' => $request->rate,
            'updated_at' => now(),
        ]);

    return redirect()->back()->with('success', 'Chemical updated successfully!');
}
public function destroychemicals($id)
{
    DB::table('master_chemical')->where('id', $id)->delete();

  return redirect()->back()->with('success', 'Chemical delete successfully!');
}
//========================================================================
 public function showChemicalHistory($id)
    {
        $chemical = DB::table('master_chemical')->where('id', $id)->first();
        $history = DB::table('chemical_stock_history')
                    ->where('chemical_id', $id)
                    ->orderByDesc('created_at')
                    ->get();


        return view('admin.chemicals', compact('chemical', 'history'));
    }

    /**
     * Add stock to an existing chemical.
     * This function handles the form submission for adding stock.
     */
    public function addChemicalStock(Request $request, $id)
{
    try {
        // Validate incoming request data
        $validated = $request->validate([
            'quantity_to_add' => 'required|numeric|min:0.01',
            'new_rate' => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
            'date' => 'required'
        ]);

        // Find the chemical by ID
        $chemical = DB::table('master_chemical')
            ->where('id', $id)
            ->first();

        if (!$chemical) {
            return redirect()->route('chemicals.index')->with('error', 'Chemical not found.');
        }

        DB::beginTransaction();

        // Calculate new stock quantity
        $newStock = $chemical->stock_qty + $validated['quantity_to_add'];

        // Prepare data for stock history with remaining_quantity
        $historyData = [
            'chemical_id'         => $id,
            'chemical_name'       => $chemical->chemical_name,
            'chemical_type'       => $chemical->chemical_type,
            'brand_name'          => $chemical->brand_name,
            'uom'                 => $chemical->uom,
            'stock_before_addition' => $chemical->stock_qty,
            'added_quantity'      => $validated['quantity_to_add'],
            'remaining_quantity'  => $validated['quantity_to_add'], // ✅ important for FIFO
            'rate'                => $validated['new_rate'] ?? $chemical->rate,
            'storage_location'    => $validated['storage_location'] ?? $chemical->storage_location,
            'site_id'             => $chemical->site_id,
            'date'                => $validated['date'], // for FIFO ordering
            'created_at'          => now(),
            'updated_at'          => now(),
        ];

        // Insert stock history
        DB::table('chemical_stock_history')->insert($historyData);

        // Update master chemical stock
        DB::table('master_chemical')
            ->where('id', $id)
            ->update([
                'stock_qty'  => $newStock,
                'rate'       => $validated['new_rate'] ?? $chemical->rate, // optional update
                'updated_at' => now(),
            ]);

        DB::commit();

        return redirect()->route('chemicals.index')
            ->with('success', 'Chemical stock updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        dd($e->getMessage());
        return redirect()->route('chemicals.index')
            ->with('error', 'Failed to add stock: ' . $e->getMessage());
    }
}


    /**
     * Get details of a single chemical (API endpoint).
//      * Used by AJAX to populate the history modal header.
//      */

public function getChemicalDetails($id)
{
    $chemical = DB::table('master_chemical as mc')
        ->leftJoin('chemical_master as cm', 'mc.chemical_id', '=', 'cm.id')
        ->select('mc.*','cm.chemical_name')
        ->where('mc.id', $id)
        ->first();

    if (!$chemical) {
        return response()->json(['error' => 'Chemical not found.'], 404);
    }

    // Total added quantity (all time)
    $totalAddedStock = (float) DB::table('chemical_stock_history')
        ->where('chemical_id', $id)
        ->sum('added_quantity');

    // Total stock amount (all time added * their rate)
    $totalStockAmount = (float) DB::table('chemical_stock_history')
        ->where('chemical_id', $id)
        ->select(DB::raw('COALESCE(SUM(added_quantity * rate), 0) as total_amount'))
        ->value('total_amount');

    // If remaining_quantity column exists, use it (preferred)
    if (Schema::hasColumn('chemical_stock_history', 'remaining_quantity')) {
        $currentStock = (float) DB::table('chemical_stock_history')
            ->where('chemical_id', $id)
            ->sum('remaining_quantity');

        $currentStockAmount = (float) DB::table('chemical_stock_history')
            ->where('chemical_id', $id)
            ->select(DB::raw('COALESCE(SUM(remaining_quantity * rate), 0) as current_amount'))
            ->value('current_amount');
    } else {
        // Fallback: compute current stock from additions minus consumptions
        $totalConsumed = (float) DB::table('chemical_consumption')
            ->where('chemical_id', $id)
            ->sum('consumed_quantity');

        $currentStock = max(0, $totalAddedStock - $totalConsumed);

        // Use weighted average rate as fallback for amount (not exact but best effort)
        $avgRate = $totalAddedStock > 0 ? ($totalStockAmount / $totalAddedStock) : (float)($chemical->rate ?? 0);
        $currentStockAmount = $currentStock * $avgRate;
    }

    // Format values to 2 decimal places for frontend
    $formatted = [
        'id' => $chemical->id,
        'chemical_name' => $chemical->chemical_name,
        'chemical_type' => $chemical->chemical_type ?? 'N/A',
        'brand_name' => $chemical->brand_name ?? 'N/A',

        'stock_qty' => number_format((float)$chemical->stock_qty, 2, '.', ''),

        // summary values (formatted)
        'total_stock' => number_format($totalAddedStock, 2, '.', ''),
        'total_stock_amount' => number_format($totalStockAmount, 2, '.', ''),
        'current_stock' => number_format($currentStock, 2, '.', ''),
        'current_stock_amount' => number_format($currentStockAmount, 2, '.', ''),

        // raw values for debug if you want to inspect
        'raw' => [
            'master_stock_qty' => (float)$chemical->stock_qty,
            'total_added_raw' => $totalAddedStock,
            'total_amount_raw' => $totalStockAmount,
            'current_stock_raw' => $currentStock,
            'current_amount_raw' => $currentStockAmount,
        ]
    ];

    // Warning if master and calculated remaining mismatch (helps debug)
    $calcMasterCheck = abs((float)$chemical->stock_qty - $currentStock);
    if ($calcMasterCheck > 0.01) {
        $formatted['warning'] = "Master stock ({$chemical->stock_qty}) and history-sum ({$currentStock}) mismatch.";
    }

    return response()->json($formatted);
}

    /**
     * Get stock history for a single chemical (API endpoint).
     * Used by AJAX to populate the history modal table body.
     */
  public function getChemicalHistory($id)
{
    // Stock history
    $stockHistory = DB::table('chemical_stock_history')
        ->select(
            'id',
            'chemical_id',
            'added_quantity as added_qty',
            DB::raw('0 as consumed_qty'),
            'rate',
            DB::raw('(added_quantity * rate) as total_price'),
            'storage_location as location',
            'created_at'
        )
        ->where('chemical_id', $id);

    // Consumption history
    $consumptionHistory = DB::table('chemical_consumption')
        ->select(
            'id',
            'chemical_id',
            DB::raw('0 as added_qty'),
            'consumed_quantity as consumed_qty',
            'rate',
            DB::raw('(consumed_quantity * rate) as total_price'),
            DB::raw('NULL as location'),
            'created_at'
        )
        ->where('chemical_id', $id);

    // Merge dono ko UNION karke latest date ke hisaab se order
    $history = $stockHistory
        ->unionAll($consumptionHistory)
        ->orderBy('created_at', 'desc')
        ->get();

    // Format date
    $history->map(function ($record) {
        $record->date_formatted = \Carbon\Carbon::parse($record->created_at)->format('d-m-y');
        return $record;
    });

    return response()->json($history);
}


//===========================desiel add history
    public function addStockd(Request $request, $id)
{  try {
        $validated = $request->validate([
            'added_quantity'     => 'required|numeric|min:0.01',
            'new_rate'           => 'nullable|numeric|min:0',
            'storage_location'   => 'nullable|string|max:255',
            'note'               => 'nullable|string',
        ]);

        $diesel = DB::table('diesel_stocks')->where('id', $id)->first();

        if (!$diesel) {
            return redirect()->route('diesels.index')->with('error', 'Diesel stock not found.');
        }

        DB::beginTransaction();

        $historyData = [
            'diesel_stock_id'       => $id,
            'type'                  => $diesel->type,
            'date_of_entry'         => now(),
            'stock_before_addition' => $diesel->diesel_stock,
            'added_quantity'        => $validated['added_quantity'],
            'consumed_quantity'     => 0,
            'rate_per_liter'        => $validated['new_rate'] ?? $diesel->rate_per_liter,
            'site_id'               => $diesel->site_id,
            'storage_location'      => $validated['storage_location'] ?? null,
            'note'                  => $validated['note'] ?? null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ];

        DB::table('diesel_stock_history')->insert($historyData);

        DB::table('diesel_stocks')
            ->where('id', $id)
            ->update([
                'diesel_stock'    => $diesel->diesel_stock + $validated['added_quantity'],
                'rate_per_liter'  => $validated['new_rate'] ?? $diesel->rate_per_liter,
                'updated_at'      => now(),
            ]);

        DB::commit();
        return redirect()->route('diesels.index')->with('success', 'Diesel stock updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('diesels.index')->with('error', 'Failed to add stock: ' . $e->getMessage());
    }
}
public function getDieselDetails($id)
{
    $diesel = DB::table('diesel_stocks')->where('id', $id)->first();

    if (!$diesel) {
        return response()->json(['message' => 'Diesel record not found.'], 404);
    }

    return response()->json($diesel);
}

public function getDieselHistory($id)
{
    $history = DB::table('diesel_stock_history')
        ->where('diesel_stock_id', $id)
        ->orderByDesc('created_at')
        ->get()
        ->map(function ($record) {
            $record->date_of_entry = Carbon::parse($record->date_of_entry)->toIso8601String();
            return $record;
        });

    return response()->json($history);
}


}
