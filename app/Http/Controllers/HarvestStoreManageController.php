<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HarvestStoreManageController extends Controller
{
   public function indexhh(Request $request)
    {
        // Get authenticated user details
        $user = Auth::user();
        $role = $user->role; // Assuming 'role' 1 is admin
        $userSiteId = $user->site_name; // Assuming site_name stores the site_id from master_sites

        // Fetch distinct blocks for the filter dropdown based on user's site or all for admin
        $blocksQuery = DB::table('harvest_store_manage');
        if ($role != 1) { // If not admin (role 1), filter by user's site
            $blocksQuery->where('site_id', $userSiteId);
        }
        $blocks = $blocksQuery->select('block_name')->distinct()->get();

        // Fetch all sites for the filter dropdown (only relevant for admin)
        $sites = DB::table('master_sites')->pluck('site_name', 'id');

        // Determine the selected site for filtering the main table and summaries
        $selectedSiteId = null;
        if ($role != 1) { // Non-admin users see only their site's data
            $selectedSiteId = $userSiteId;
        } elseif ($request->filled('site_id')) { // Admin can filter by any site
            $selectedSiteId = $request->site_id;
        }

        // Main Query for detailed harvest data (harvest_store_manage) with sales
        $query = DB::table('harvest_store_manage as hsm')
                    ->select(
                        'hsm.id',
                        'hsm.site_id',
                        'hsm.block_name',
                        'hsm.plot_name',
                        'hsm.seed_name',
                        'hsm.product_id',
                        'hsm.date',
                        'hsm.yield_mt',
                        'hsm.sale_price',
                        // Subquery to calculate total sold quantity for each harvest item
                        DB::raw('(SELECT COALESCE(SUM(hsr.sold_mt), 0) FROM harvest_sale_records as hsr WHERE hsr.harvest_store_id = hsm.id) as total_sold_quantity')
                    )
                    ->when($selectedSiteId, function ($q) use ($selectedSiteId) {
                        $q->where('hsm.site_id', $selectedSiteId); // Apply site filter
                    })
                    ->when($request->filled('block_name'), function ($q) use ($request) {
                        $q->where('hsm.block_name', $request->block_name); // Apply block filter
                    })
                    ->when($request->filled('record_type'), function ($q) use ($request) {
                        // Map record_type string to product_id integer
                        $productIdMap = [
                            'Harvest' => 1,
                            'Hay' => 2,
                            'Silage' => 3,
                        ];
                        if (isset($productIdMap[$request->record_type])) {
                            $q->where('hsm.product_id', $productIdMap[$request->record_type]);
                        }
                    })
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $search = $request->search;
                        $q->where(function ($sq) use ($search) {
                            $sq->where('hsm.plot_name', 'like', "%$search%")
                               ->orWhere('hsm.seed_name', 'like', "%$search%");
                        });
                    });

        // Execute the query, paginate results, and transform each item for display
        $harvests = $query->orderBy('hsm.id', 'desc') // Order by latest records first
                          ->paginate(10)
                          ->through(function ($item) {
                              // Calculate remaining_mt (available stock)
                              $item->total_mt = (float) $item->yield_mt - (float) $item->total_sold_quantity;
                              return $item;
                          })
                          ->withQueryString(); // Maintain filter parameters in pagination links

        // Fetch summary data for each production type (Harvest, Silage, Hay)
        $silageSummary = $this->getSummaryData(3, $selectedSiteId); // product_id 3 for Silage
        $haySummary = $this->getSummaryData(2, $selectedSiteId);    // product_id 2 for Hay
        $harvestSummary = $this->getSummaryData(1, $selectedSiteId); // product_id 1 for Harvest

        // Return the view with all necessary data
        return view('harvest_store_manage.index', compact(
            'blocks',
            'sites',
            'harvests',
            'selectedSiteId',
            'role',
            'userSiteId',
            'silageSummary',
            'haySummary',
            'harvestSummary'
        ));
    }

    /**
     * Records a new sale for a specific harvest item.
     * Inserts a record into harvest_sale_records and optionally updates last sale price in harvest_store_manage.
     *
     * @param Request $request
     * @param int $harvest_store_id The ID of the harvest record being sold.
     * @return \Illuminate\Http\RedirectResponse
     */
public function recordSale(Request $request, $seed_id)
{
   // dd($request->all());
    try {
        $user = Auth::user();

        // Validation
        $request->validate([
            'product_id'          => 'required|integer',
            'sold_quantity'       => 'required|numeric|min:0.01',
            'sale_price_per_mt'   => 'required|numeric|min:0',
            'sale_date'           => 'required|date',
            'buyer_name'          => 'nullable|string|max:255',
            'remarks'             => 'nullable|string|max:1000',
        ]);

        $product_id = $request->product_id;

        // Get total harvested quantity
        // $harvestRecord = DB::table('harvest_store_manage')
        //     ->selectRaw('SUM(yield_mt) as yield_mt')
        //     ->where('product_id', $product_id)
        //     ->where('seed_id', $seed_id)
        //     ->first();
        $harvestRecord = DB::table('harvest_store_manage as hsm')
        ->leftJoin('harvest_sale_records as hsr', 'hsm.seed_id', '=', 'hsr.id')
        ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->where('hsm.seed_id', $seed_id)
        ->where('hsm.product_id', $product_id)
        ->select(
            DB::raw('SUM(hsm.total_mt) as yield_mt')
        )
        ->groupBy('hsm.seed_id', 'hsm.product_id', 's.name', 'mv.variety_name')
        ->first();

        if (!$harvestRecord || $harvestRecord->yield_mt <= 0) {
            return back()->with('error', 'No harvest stock available.');
        }

        // Get total sold quantity
        $totalSold = DB::table('harvest_sale_records')
            ->where('seed_id', $seed_id)
            ->where('product_id', $product_id)
            ->sum('sold_mt');
 
        $remainingStock = (float) $harvestRecord->yield_mt - (float) $totalSold;
        if ($request->sold_quantity > $remainingStock) {
            echo $request->sold_quantity;
            echo $remainingStock;
            return back()->with(
                'error',
                'Sale quantity exceeds available stock. Remaining: ' .
                number_format($remainingStock, 2) . ' MT'
            );
        }

        // Transaction starts
        DB::transaction(function () use ($request, $seed_id, $product_id, $user) {

            $soldQuantity = (float) $request->sold_quantity;
            $salePrice    = (float) $request->sale_price_per_mt;

            // Insert sale record
            DB::table('harvest_sale_records')->insert([
                //'harvest_store_id'  => $request->hsm_id,
                'product_id'        => $product_id,
                'seed_id'           => $seed_id,
                'sold_mt'           => $soldQuantity,
                'sale_price_per_mt' => $salePrice,
                'sale_date'         => $request->sale_date,
                'buyer_name'        => $request->buyer_name,
                'remarks'           => $request->remarks,
                'site_id'           => $user->site_id,
                
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            //  FIFO stock reduction
            $stocks = DB::table('harvest_store_manage')
                ->where('seed_id', $seed_id)
                ->where('product_id', $product_id)
                ->where('yield_mt', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $remaining = $soldQuantity;

            foreach ($stocks as $stock) {
                if ($remaining <= 0) break;

                if ($stock->yield_mt <= $remaining) {
                    $remaining -= $stock->yield_mt;

                    DB::table('harvest_store_manage')
                        ->where('id', $stock->id)
                        ->update([
                            'yield_mt'    => 0,
                            'sale_yield'  => 0,
                            'sale_price'  => $salePrice,
                            'updated_at'  => now()
                        ]);
                } else {
                    DB::table('harvest_store_manage')
                        ->where('id', $stock->id)
                        ->update([
                            'yield_mt'    => $stock->yield_mt - $remaining,
                            'sale_yield'  => $stock->yield_mt - $remaining,
                            'sale_price'  => $salePrice,
                            'updated_at'  => now()
                        ]);

                    $remaining = 0;
                }
            }
        });

        return back()->with('success', 'Sale recorded successfully!');

    } catch (\Throwable $e) {
 
        //Log exact error (storage/logs/laravel.log)
        Log::error('Harvest Sale Error', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        // Show error on UI
        return back()->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}

    /**
     * Fetches the sales history for a specific harvest record via AJAX.
     *
     * @param int $harvest_store_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSalesHistory($harvest_store_id)
    {
        // Get the initial harvest record details
        $harvestRecord = DB::table('harvest_store_manage')
                            ->where('id', $harvest_store_id)
                            ->select('yield_mt', 'sale_price', 'seed_name', 'plot_name', 'product_id')
                            ->first();

        if (!$harvestRecord) {
            return response()->json(['error' => 'Harvest record not found'], 404);
        }

        // Fetch all sales records associated with this harvest item
        $sales = DB::table('harvest_sale_records')
                    ->where('harvest_store_id', $harvest_store_id)
                    ->orderBy('sale_date', 'asc')
                    ->get();

        // Calculate total sold quantity and remaining stock
        $totalSoldFromRecords = $sales->sum('sold_mt');
        $remainingStock = (float) $harvestRecord->yield_mt - (float) $totalSoldFromRecords;

        // Determine product type for display in modal title
        $productType = 'Unknown Type';
        if ($harvestRecord->product_id == 1) {
            $productType = 'Harvest';
        } elseif ($harvestRecord->product_id == 2) {
            $productType = 'Hay';
        } elseif ($harvestRecord->product_id == 3) {
            $productType = 'Silage';
        }

        // Return sales data and summary information as JSON
        return response()->json([
            'sales' => $sales,
            'initialYield' => $harvestRecord->yield_mt,
            'totalSold' => $totalSoldFromRecords,
            'remainingStock' => $remainingStock,
            'harvest_details' => $harvestRecord,
            'product_type' => $productType
        ]);
    }

    /**
     * Helper function to get summary data for a specific product_id (type).
     * This method correctly aggregates initial yield and sold quantities for summary cards.
     *
     * @param int $productId The product_id (e.g., 1 for Harvest, 2 for Hay, 3 for Silage).
     * @param int|null $siteId Optional site ID for filtering.
     * @return array Contains summary data and grand totals.
     */
    private function getSummaryData($productId, $siteId = null)
    {
        // Calculate the grand total initial yield for the entire product type, filtered by site.
        $grandInitialYieldQuery = DB::table('harvest_store_manage')
            ->where('product_id', $productId)
            ->when($siteId, function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        $grandTotalYieldMT = $grandInitialYieldQuery->sum('yield_mt');

        // Get detailed summary per seed name by combining initial yields and sales data
        $summary = DB::table('harvest_store_manage as hsm')
            ->select(
                'hsm.seed_name',
                DB::raw('SUM(hsm.yield_mt) as total_yield_mt'),
                DB::raw('COALESCE(SUM(hsr.sold_mt * hsr.sale_price_per_mt), 0) as total_sale_for_seed'),
                DB::raw('SUM(hsr.sold_mt * hsr.sale_price_per_mt) / NULLIF(SUM(hsr.sold_mt), 0) as avg_sale_price')
            )
            ->leftJoin('harvest_sale_records as hsr', 'hsm.id', '=', 'hsr.harvest_store_id')
            ->where('hsm.product_id', $productId)
            ->when($siteId, function ($q) use ($siteId) {
                $q->where('hsm.site_id', $siteId);
            })
            ->groupBy('hsm.seed_name')
            ->orderBy('hsm.seed_name')
            ->get();

        // Calculate the grand total sale across all seeds for this product type
        $grandTotalSale = $summary->sum('total_sale_for_seed');

        return [
            'summary' => $summary,
            'grand_total_sale' => $grandTotalSale,
            'grand_total_yield_mt' => $grandTotalYieldMT
        ];
    }

    public function salesHistory()
    {
        $harvests = DB::table('harvest_store_manage as hsm')
            ->leftJoin('harvest_sale_records as hsr', 'hsm.id', '=', 'hsr.harvest_store_id')
            ->select(
                'hsm.id',
                'hsm.seed_name',
                'hsm.yield_mt',
                'hsm.total_mt',
                DB::raw('COUNT(hsr.id) as sale_count')
            )
            ->groupBy('hsm.id', 'hsm.seed_name', 'hsm.yield_mt', 'hsm.total_mt')
            ->get();
        
        return view('salereport', compact('harvests'));
    }
    
    // Sales Report Page (Detail Report)
   public function salesReport($seed_id, $product_id)
{
    // Get aggregated harvest data using DB facade
    $harvest = DB::table('harvest_store_manage as hsm')
        ->leftJoin('harvest_sale_records as hsr', 'hsm.seed_id', '=', 'hsr.id')
        ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->where('hsm.seed_id', $seed_id)
        ->where('hsm.product_id', $product_id)
        ->select(
            'hsm.seed_id',
            'hsm.product_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(hsm.total_mt) as total_mt'),
            DB::raw('SUM(hsm.yield_mt) as yield_mt'),
            DB::raw('SUM(hsm.mt_price) as avg_price')
        )
        ->groupBy('hsm.seed_id', 'hsm.product_id', 's.name', 'mv.variety_name')
        ->first();
    
    if (!$harvest) {
        abort(404, 'Harvest record not found');
    }
    
    // Get all sales for this harvest using DB facade
    $sales = DB::table('harvest_sale_records')
        ->where('seed_id', $seed_id)
        ->where('product_id', $product_id)
        ->orderBy('sale_date', 'desc')
        ->get();
    
    // Calculate statistics
    $totalSales = $sales->count();
    $totalRevenue = $sales->sum('total_price');
    $totalQuantity = $sales->sum('sold_mt');
    
    $avgPrice = $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0;
    
    return view('salereport', compact(
        'harvest',
        'sales', 
        'totalSales', 
        'totalRevenue', 
        'totalQuantity', 
        'avgPrice'
    ));
}

}