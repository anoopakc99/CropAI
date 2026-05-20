<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ActivityMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $userSiteId = $user->site_id;

        // Get distinct blocks for dropdown


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
            ->where('ml.site_id', $userSiteId)
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    }

        // Get site list from master_sites
        $sites = DB::table('master_sites')->pluck('site_name', 'id');
        $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');

        // Start main query
        $query = DB::table('activity_monitoring')
        ->leftJoin('blocks', 'activity_monitoring.block_name', '=', 'blocks.id')
        ->leftJoin('master_plots', 'activity_monitoring.plot_name', '=', 'master_plots.id')
        ->select('activity_monitoring.*', 'blocks.block_name as block_name', 'master_plots.plot_name as plot_name', 'master_plots.area as area');

        // Site filtering
        $selectedSiteId = null;
        if ($role != 1) {
            $selectedSiteId = $userSiteId;
            $query->where('activity_monitoring.site_id', $userSiteId);
        } elseif ($request->filled('site_id')) {
            $selectedSiteId = $request->site_id;
            $query->where('activity_monitoring.site_id', $selectedSiteId);
        }

        // Block filtering
        if ($request->filled('block_name')) {
            $query->where('activity_monitoring.block_name', $request->block_name);
        }

        // Search filtering
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plot_name', 'like', "%$search%")
                  ->orWhere('activity_stage', 'like', "%$search%")
                  ->orWhere('leaf_condition', 'like', "%$search%");
            });
        }
         if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'activity_monitoring.site_id')
                  ->whereColumn('s.block_id', 'activity_monitoring.block_name')
                  ->whereColumn('s.plot_id', 'activity_monitoring.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('activity_monitoring.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'activity_monitoring.site_id')
                  ->whereColumn('s.block_id', 'activity_monitoring.block_name')
                  ->whereColumn('s.plot_id', 'activity_monitoring.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('activity_monitoring.date BETWEEN s.start_date AND s.end_date');
            });
        }
        // Get paginated result
        $activities = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
       


        // Send to view
        return view('activity_monitoring.index', compact(
            'blocks',
            'sites',
            'activities',
            'selectedSiteId',
            'role',
            'userSiteId',
            'financialYears'
        ));
    }

    public function indexhh(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $userSiteId = $user->site_id;
        if ($role == 1) {
            // Admins get all sites
            $sites = DB::table('master_sites')->pluck('site_name', 'id');
        } else {
            // Non-admins get only their own site name
            $sites = DB::table('master_sites')
                        ->where('id', $userSiteId)
                        ->pluck('site_name', 'id');
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
            ->where('ml.site_id', $userSiteId)
            ->select('b.id as block_id', 'b.block_name')
            ->distinct()
            ->get();
    }
  //  dd($blocks);
    // Get site list from master_sites
   // $sites = DB::table('master_sites')->pluck('site_name', 'id');
    $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');
 
     // Main Query for harvest data with sales integration
   $query = DB::table('harvest_store_manage as hsm')
     
    ->leftJoin('harvest_sale_records as hsr', 'hsm.id', '=', 'hsr.harvest_store_id')
    ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
    ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
    ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
    ->select(
        
        'hsm.seed_id as seed_id',
        'hsm.product_id as product_id',
        'hsm.seed_name as product_name',
        DB::raw('MAX(hsm.id) as id'),
        DB::raw('MAX(hsm.date) as date'),
        DB::raw('MAX(hsm.yield_mt) as yield_mt'),
        DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
        
        
        DB::raw('SUM(hsm.yield_mt) as total_yield_mt'),
        DB::raw('SUM(hsm.total_mt) as total_mt'),
        DB::raw('SUM(hsm.mt_price) as total_mt_price'),
        DB::raw('SUM(hsm.sale_price) as total_sale_price'),
        DB::raw('SUM(hsm.quantity) as total_quantity'),

        DB::raw('COALESCE(SUM(hsr.sold_mt), 0) as sold_mt'),
        DB::raw('COALESCE(SUM(hsr.total_price), 0) as total_sale'),

        // Remaining MT = Yield - Sold (agar sale na ho to total dikhe)
      DB::raw('COALESCE(MAX(hsm.yield_mt), 0) as remaining_mt')
    )
    ->groupBy(
        'hsm.seed_id', 
        'product_id',
        'product_name',
        's.name',
        'mv.variety_name'
    );

// --- Site filtering
$selectedSiteId = null;
if ($role != 1) {
    $selectedSiteId = $userSiteId;
    $query->where('hsm.site_id', $userSiteId);
} elseif ($request->filled('site_id')) {
    $selectedSiteId = $request->site_id;
    $query->where('hsm.site_id', $selectedSiteId);
}
 
// Session filter
      if ($request->filled('season_name')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'hsm.site_id')
                  ->whereColumn('s.block_id', 'hsm.block_name')
                  ->whereColumn('s.plot_id', 'hsm.plot_name')
                  ->where('s.name', $request->season_name)
                  
                  ->whereRaw('hsm.date BETWEEN s.start_date AND s.end_date');
            });
        }
         if ($request->filled('year')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('seasons as s')
                  ->whereColumn('s.site_id', 'hsm.site_id')
                  ->whereColumn('s.block_id', 'hsm.block_name')
                  ->whereColumn('s.plot_id', 'hsm.plot_name')
                  ->where('s.year', $request->year)
                  ->whereRaw('hsm.date BETWEEN s.start_date AND s.end_date');
            });
        }

// --- Search filtering (on plot_name and seed_name)
if ($request->filled('search')) {
    $search = $request->search;
    $query->where(function ($q) use ($search) {
        $q->where('hsm.plot_name', 'like', "%$search%")
          ->orWhere(DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')')"), 'like', "%$search%");
    });
}

// --- Filter by record_type
if ($request->filled('record_type')) {
    $recordTypeFilter = $request->record_type;
    $query->where(function ($q) use ($recordTypeFilter) {
        if ($recordTypeFilter == 'Green Fodder') {
            $q->where('hsm.product_id', 1);
        } elseif ($recordTypeFilter == 'Hay') {
            $q->where('hsm.product_id', 2);
        } elseif ($recordTypeFilter == 'Silage') {
            $q->where('hsm.product_id', 3);
        } elseif ($recordTypeFilter == 'Grain') {
            $q->where('hsm.product_id', 4);
        } elseif ($recordTypeFilter == 'Crop Residue') {
            $q->where('hsm.product_id', 5);
        }
    });
}

// --- Final result with record type
$harvests = $query->orderBy('id', 'asc')
    ->paginate(10)
    ->through(function ($item) {
        if ($item->product_id == 1) {
            $item->record_type = 'Green Fodder';
        } elseif ($item->product_id == 2) {
            $item->record_type = 'Hay';
        } elseif ($item->product_id == 3) {
            $item->record_type = 'Silage';
        } elseif ($item->product_id == 4) {
            $item->record_type = 'Grain';
        }else{
            $item->record_type = 'Crop Residue';
        }
        return $item;
    })
    ->withQueryString();

    // Fetch all summaries for the main page display using product_id
    $silageSummary = $this->calculateProductionSummary(3, $selectedSiteId);
    $haySummary = $this->calculateProductionSummary(2, $selectedSiteId);
    $harvestSummary = $this->calculateProductionSummary(1, $selectedSiteId);
    $grainSummary = $this->calculateProductionSummary(4, $selectedSiteId);
    $strawSummary = $this->calculateProductionSummary(5, $selectedSiteId);

    
  //dd($harvests);
    return view('harvest_store_manage.index', compact(
        'blocks',
        'sites',
        'harvests',
        'selectedSiteId',
        'role',
        'userSiteId',
        'silageSummary',
        'haySummary',
        'harvestSummary',
        'grainSummary',
        'strawSummary',
        'financialYears'
    ));
}

private function calculateProductionSummary($productId, $selectedSiteId = null)
{
    // First, get the basic harvest data without JOIN to avoid duplication
    $baseQuery = DB::table('harvest_store_manage')
        ->leftJoin('master_seed as ms', 'harvest_store_manage.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->where('harvest_store_manage.product_id', $productId);

    if ($selectedSiteId) {
        $baseQuery->where('harvest_store_manage.site_id', $selectedSiteId);
    }

 $yieldSummary = $baseQuery->select(
    'harvest_store_manage.seed_id',
    DB::raw("MAX(CONCAT(harvest_store_manage.seed_name)) as product_name"), // safe single name
    DB::raw("MAX(CONCAT(s.name, ' (', mv.variety_name, ')')) as seed_name"), // safe single name
    DB::raw('SUM(harvest_store_manage.yield_mt) as yield_mt'),
    DB::raw('SUM(harvest_store_manage.total_mt) as total_yield_mt'),
    DB::raw('AVG(harvest_store_manage.mt_price) as avg_price')
)
->groupBy('harvest_store_manage.seed_id')
->get();
  // Step 1: Summarize sales first (avoid duplication)
$salesSummary = DB::table('harvest_sale_records')
    ->select(
        'seed_id',
        'product_id',
        DB::raw('SUM(COALESCE(sold_mt, 0)) as total_sold_mt'),
        DB::raw('SUM(COALESCE(total_price, 0)) as total_sale_for_seed'),
        DB::raw('AVG(COALESCE(sale_price_per_mt, 0)) as avg_sale_price')
    )
    ->groupBy('seed_id', 'product_id');

// Step 2: Join this summarized result to other tables
$salesSummary = DB::table(DB::raw("({$salesSummary->toSql()}) as hsr"))
    ->mergeBindings($salesSummary)
    ->leftJoin('master_seed as ms', 'hsr.seed_id', '=', 'ms.id')
    ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
    ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
    ->leftJoin('harvest_store_manage as hsm', 'hsr.seed_id', '=', 'hsm.seed_id')
    ->when($selectedSiteId, function ($q) use ($selectedSiteId) {
        return $q->where('hsm.site_id', $selectedSiteId);
    })
    ->where('hsr.product_id', $productId)
    ->select(
        'hsr.seed_id',
        'hsr.product_id',
        DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
        'hsr.total_sold_mt',
        'hsr.total_sale_for_seed',
        'hsr.avg_sale_price'
    )
    ->groupBy('hsr.seed_id', 'hsr.product_id', 's.name', 'mv.variety_name', 'hsr.total_sold_mt', 'hsr.total_sale_for_seed', 'hsr.avg_sale_price')
    ->get()
    ->keyBy('seed_id');
 

    // Combine yield and sales data
  $summary = $yieldSummary->map(function ($item) use ($salesSummary) {
    // Match by seed_id (since keyBy('seed_id'))
    $salesData = $salesSummary->get($item->seed_id);
    // Safely assign values with fallback
    $item->total_sold_mt = $salesData->total_sold_mt ?? 0;
    $item->total_sale_for_seed = $salesData->total_sale_for_seed ?? 0;
    $item->avg_sale_price = $salesData->avg_sale_price ?? $item->avg_price ?? 0;

    // Calculate remaining_mt correctly
    // $item->remaining_mt = max(($item->total_yield_mt ?? 0) - ($item->total_sold_mt ?? 0), 0);
    $item->remaining_mt = $item->yield_mt;

    return $item;
});
//dd($summary);
    $heyQuantity = DB::table('harvest_store_manage')
    ->where('harvest_store_manage.site_id', $selectedSiteId)
    ->whereNotNull('hey_id')
    ->sum('quantity');
    $silageQuantity = DB::table('harvest_store_manage')
    ->where('harvest_store_manage.site_id', $selectedSiteId)
    ->whereNotNull('silage_id')
    ->sum('quantity');
    //   $grainQuantity = DB::table('harvest_store_manage')
    // ->where('harvest_store_manage.site_id', $selectedSiteId)
    // ->whereNotNull('hey_id')



    // Calculate grand totals from the summary data to ensure consistency
    $grandTotalYield = $summary->sum('total_yield_mt');
    $grandTotalSold = $summary->sum('total_sold_mt');
    $grandTotalRemaining = $summary->sum('remaining_mt');
    $grandTotalSale = $summary->sum('total_sale_for_seed');
    $grandTotalSold = $summary->sum('total_sold_mt');
    $heytotal = $heyQuantity;
    $silagetotal = $silageQuantity;
//dd($summary);
    return [
        'summary' => $summary,
        'grand_total_yield_mt' => $grandTotalYield,
        'grand_total_sold_mt' => $grandTotalSold,
        'grand_total_remaining_mt' => $grandTotalRemaining,
        'grand_total_sale' => $grandTotalSale,
        'heytotal' => $heytotal,
        'silagetotal' => $silagetotal
    ];
}
///Production Summaries End
    public function updateSalePrice(Request $request, $id)
    {
        $request->validate([
            'sale_price' => 'required|numeric|min:0',
        ]);

        DB::table('harvest_store_manage')->where('id', $id)->update([
            'sale_price' => $request->sale_price,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Sale Price updated successfully! ðŸŽ‰');
    }
}
