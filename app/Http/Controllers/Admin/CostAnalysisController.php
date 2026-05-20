<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class CostAnalysisController extends Controller
{
    protected $activityTables = [
        'activity_monitoring',
        'area_levelings',
        'crop_protection',
        'fertilizer_soil_record',
        'harvestings',
        'harvesting_update',
        'inter_culture',
        'irrigation_records',
        'land_prepration',
        'post_irrigation',
        'pre_irrigation',
        'pre_land_preparation',
        'showing_oprations',
        'silage_making',
        'hay_making',
        'harvest_store_manage',
    ];

    // DISPLAY name and table names with new ordered activity mapping
    protected $orderedActivities = [
        ['display' => 'Area Leveling', 'table' => 'area_levelings'],
        ['display' => 'Pre Land Preparation', 'table' => 'pre_land_preparation'],
        ['display' => 'Pre Irrigation', 'table' => 'pre_irrigation'],
        ['display' => 'Land Preparation', 'table' => 'land_prepration'],
        ['display' => 'Sowing', 'table' => 'showing_oprations'],
        ['display' => 'Post Irrigation', 'table' => 'post_irrigation'],
        ['display' => 'Fertilizer', 'table' => 'fertilizer_soil_record'],
        ['display' => 'Inter Culture', 'table' => 'inter_culture'],
        ['display' => 'Crop Protection', 'table' => 'crop_protection'],
        ['display' => 'Harvest', 'table' => 'harvesting_update'], // Renamed for clarity, assumed to be the main 'Harvest'
        ['display' => 'Hay Making', 'table' => 'hay_making'],
        ['display' => 'Silage Making', 'table' => 'silage_making'],
        // ['display' => 'Harvest Store Manage', 'table' => 'harvest_store_manage'], // REMOVED from dropdown as requested
    ];

    protected $fullStart;
    protected $fullEnd;

    protected $seasons = [
        'kharif' => ['display' => 'Kharif', 'start_month' => 6, 'end_month' => 10],
        'rabi' => ['display' => 'Rabi', 'start_month' => 11, 'end_month' => 3],
        'zaid' => ['display' => 'Zaid', 'start_month' => 4, 'end_month' => 5]
    ];

    protected $expectedColumns = [
        'block_name',
        'plot_name',
        'date',
        'area',
        'irrigation_date',
        'area_acre',
        'machine_id',
        'tractor_id', // Added tractor_id column
        'hsd_consumption',
        'manpower_type',
        'electricity_units',
        'cost_per_unit',
        'fertilizer_id',
        'fertilizer_quantity',
        'major_maintenance',
        // 'production_cost', // REMOVED - Process Cost column removed
        'profit',
        'loss',
        'area_covered',

        'seed_name',
        'total_cost',
        'created_at',
        'unskilled', 'semi_skilled_1', 'semi_skilled_2',
        'yield_mt',
        'total_mt',
        'price_per_mt',
        'sale_price',
        'product_id',
    ];

    protected $costConfig = [
        'hsd_rate_per_liter' => 88.21,
        'fertilizer_rate_per_kg' => 5.36,
        'manpower_rates' => [
            'skilled' => 500,
            'unskilled' => 300,
            'default' => 400
        ]
    ];

    protected function determineSeason($date)
    {
        if (!$date) return null;

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $month = (int)$date->format('n');

        if ($month >= 6 && $month <= 10) {
            return 'kharif';
        }

        if ($month >= 11 || $month <= 3) {
            return 'rabi';
        }

        if ($month >= 4 && $month <= 5) {
            return 'zaid';
        }

        return null;
    }

    protected function normalizeFinancialYear($financialYear)
    {
        if (!$financialYear) {
            return null;
        }

        $financialYear = trim($financialYear);

        if (preg_match('/^20\d{2}-\d{2}$/', $financialYear)) {
            return $financialYear;
        }

        if (preg_match('/^\d{2}-\d{2}$/', $financialYear)) {
            return '20' . $financialYear;
        }

        return null;
    }

    protected function getFinancialYearDateRange($financialYear, $seasonFilter = null)
    {
        $financialYear = $this->normalizeFinancialYear($financialYear);

        if (!$financialYear) {
            return [null, null];
        }

        [$startYear, $endYearSuffix] = explode('-', $financialYear);
        $endYear = '20' . $endYearSuffix;

        if ($seasonFilter === 'Kharif') {
            return [$startYear . '-06-01', $startYear . '-10-31'];
        }

        if ($seasonFilter === 'Rabi') {
            return [$startYear . '-11-01', $endYear . '-03-31'];
        }

        if ($seasonFilter === 'Zaid') {
            return [$endYear . '-04-01', $endYear . '-05-31'];
        }

        return [$startYear . '-04-01', $endYear . '-03-31'];
    }

    protected function getSeasonMonthCondition($column, $seasonFilter)
    {
        if ($seasonFilter === 'Kharif') {
            return "MONTH($column) >= 6 AND MONTH($column) <= 10";
        }

        if ($seasonFilter === 'Rabi') {
            return "(MONTH($column) >= 11 OR MONTH($column) <= 3)";
        }

        if ($seasonFilter === 'Zaid') {
            return "MONTH($column) >= 4 AND MONTH($column) <= 5";
        }

        return null;
    }

    protected function applySeasonMonthFilter($query, $column, $seasonFilter)
    {
        $monthCondition = $this->getSeasonMonthCondition($column, $seasonFilter);

        if ($monthCondition) {
            return $query->whereRaw($monthCondition);
        }

        return $query;
    }

    protected function applySeasonFilter($query, $column, $seasonFilter, $manualSeasonColumn = null)
    {
        $monthCondition = $this->getSeasonMonthCondition($column, $seasonFilter);

        if (!$monthCondition) {
            return $query;
        }

        if (!$manualSeasonColumn) {
            return $query->whereRaw($monthCondition);
        }

        return $query->where(function ($seasonQuery) use ($manualSeasonColumn, $seasonFilter, $monthCondition) {
            $seasonQuery->where($manualSeasonColumn, $seasonFilter)
                ->orWhereRaw("(({$manualSeasonColumn} IS NULL OR TRIM({$manualSeasonColumn}) = '') AND {$monthCondition})");
        });
    }

    protected function buildSeasonSqlCondition($dateColumn, $seasonFilter, $manualSeasonColumn = null)
    {
        $monthCondition = $this->getSeasonMonthCondition($dateColumn, $seasonFilter);

        if (!$monthCondition) {
            return [null, []];
        }

        if (!$manualSeasonColumn) {
            return [$monthCondition, []];
        }

        return [
            "({$manualSeasonColumn} = ? OR (({$manualSeasonColumn} IS NULL OR TRIM({$manualSeasonColumn}) = '') AND {$monthCondition}))",
            [$seasonFilter]
        ];
    }

    protected function appendSeasonSqlFilter(&$sql, &$params, $dateColumn, $seasonFilter, $manualSeasonColumn = null)
    {
        [$condition, $conditionParams] = $this->buildSeasonSqlCondition($dateColumn, $seasonFilter, $manualSeasonColumn);

        if ($condition) {
            $sql .= " AND {$condition}";
            $params = array_merge($params, $conditionParams);
        }
    }

 protected function getSeedBasedYieldTotals($blockFilter = null, $plotFilter = null, $seedNameFilter = null, $siteId = null, $from = null, $to = null, $seasonFilter = null, $selectedCropId = null, $masterSeedIds = [], $applySeasonWithinDateRange = false)
{
    if (
        !Schema::hasTable('harvesting_update') ||
        !Schema::hasColumn('harvesting_update', 'yield_mt') ||
        !Schema::hasTable('master_seed') ||
        !Schema::hasColumn('master_seed', 'sale_price')
    ) {
        return (object)[
            'seedTotals' => [],
            'blockwiseTotals' => [],
            'grandTotal' => 0,
            'grandTotalPrice' => 0,
            'grandTotalSold' => 0
        ];
    }

    $baseQuery = "FROM harvesting_update hu
                  LEFT JOIN master_seed ms ON hu.seed_name = ms.seed_name
                  LEFT JOIN blocks b ON hu.block_name = b.id
                  LEFT JOIN master_plots mp ON hu.plot_name = mp.id
                  WHERE hu.seed_name IS NOT NULL AND hu.seed_name <> ''";
    $params = [];

    if ($siteId && Schema::hasColumn('harvesting_update', 'site_id')) {
        $baseQuery .= " AND hu.site_id = ?";
        $params[] = $siteId;
    }

    if ($blockFilter) {
        $baseQuery .= " AND hu.block_name = ?";
        $params[] = $blockFilter;
    }

    if ($plotFilter) {
        $baseQuery .= " AND hu.plot_name = ?";
        $params[] = $plotFilter;
    }
    if ($seedNameFilter) {
        $baseQuery .= " AND hu.seed_name = ?";
        $params[] = $seedNameFilter;
    }

    if ($from && $to) {
        $manualCol = Schema::hasColumn('harvesting_update', 'manual_season') ? 'hu.manual_season' : null;
        if ($applySeasonWithinDateRange && $seasonFilter && $manualCol && isset($this->fullStart) && isset($this->fullEnd)) {
            $baseQuery .= " AND (
                (hu.date BETWEEN ? AND ? AND ({$manualCol} IS NULL OR TRIM({$manualCol}) = '' OR {$manualCol} = ?))
                OR ({$manualCol} = ? AND hu.date BETWEEN ? AND ?)
            )";
            $params[] = $from;
            $params[] = $to;
            $params[] = $seasonFilter;
            $params[] = $seasonFilter;
            $params[] = $this->fullStart;
            $params[] = $this->fullEnd;
        } else {
            $baseQuery .= " AND hu.date BETWEEN ? AND ?";
            $params[] = $from;
            $params[] = $to;
            if ($applySeasonWithinDateRange && $seasonFilter) {
                $this->appendSeasonSqlFilter($baseQuery, $params, 'hu.date', $seasonFilter, $manualCol);
            }
        }
    } elseif ($seasonFilter) {
        $manualCol = Schema::hasColumn('harvesting_update', 'manual_season') ? 'hu.manual_season' : null;
        $this->appendSeasonSqlFilter($baseQuery, $params, 'hu.date', $seasonFilter, $manualCol);
    }

    if ($selectedCropId) {
        $selectedCropIds = is_array($selectedCropId) ? array_filter($selectedCropId) : [$selectedCropId];
        $placeholders = implode(',', array_fill(0, count($selectedCropIds), '?'));
        $baseQuery .= " AND ms.seed_id IN ($placeholders)";
        $params = array_merge($params, $selectedCropIds);
    }

    $seedTotalsQuery = "
        SELECT
            hu.seed_name,
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2))) AS total_production_mt,
            COALESCE(CAST(ms.sale_price AS DECIMAL(10,2)), 0) AS price_per_mt,
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2)) * COALESCE(CAST(ms.sale_price AS DECIMAL(10,2)), 0)) AS total_price
        " . $baseQuery . "
        GROUP BY hu.seed_name, ms.sale_price
    ";

    $seedTotals = DB::select($seedTotalsQuery, $params);

    // Get sold MT data from harvest_store_manage and harvest_sale_records
    foreach ($seedTotals as &$seedTotal) {
        $soldMtQuery = "
            SELECT SUM(COALESCE(hsr.sold_mt, 0)) as total_sold_mt
            FROM harvest_store_manage hsm
            LEFT JOIN harvest_sale_records hsr ON hsm.id = hsr.harvest_store_id
            WHERE hsm.seed_name = ?
            AND hsm.product_id = 1
        ";
        $soldParams = [$seedTotal->seed_name];

        if ($siteId) {
            $soldMtQuery .= " AND hsm.site_id = ?";
            $soldParams[] = $siteId;
        }
        if ($blockFilter) {
            $soldMtQuery .= " AND hsm.block_name = ?";
            $soldParams[] = $blockFilter;
        }
        if ($plotFilter) {
            $soldMtQuery .= " AND hsm.plot_name = ?";
            $soldParams[] = $plotFilter;
        }
        if ($from && $to) {
            $manualCol = Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null;
            if ($applySeasonWithinDateRange && $seasonFilter && $manualCol && isset($this->fullStart) && isset($this->fullEnd)) {
                $soldMtQuery .= " AND (
                    (hsm.date BETWEEN ? AND ? AND ({$manualCol} IS NULL OR TRIM({$manualCol}) = '' OR {$manualCol} = ?))
                    OR ({$manualCol} = ? AND hsm.date BETWEEN ? AND ?)
                )";
                $soldParams[] = $from;
                $soldParams[] = $to;
                $soldParams[] = $seasonFilter;
                $soldParams[] = $seasonFilter;
                $soldParams[] = $this->fullStart;
                $soldParams[] = $this->fullEnd;
            } else {
                $soldMtQuery .= " AND hsm.date BETWEEN ? AND ?";
                $soldParams[] = $from;
                $soldParams[] = $to;
                if ($applySeasonWithinDateRange && $seasonFilter) {
                    $this->appendSeasonSqlFilter($soldMtQuery, $soldParams, 'hsm.date', $seasonFilter, $manualCol);
                }
            }
        } elseif ($seasonFilter) {
            $manualCol = Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null;
            $this->appendSeasonSqlFilter($soldMtQuery, $soldParams, 'hsm.date', $seasonFilter, $manualCol);
        }

        $soldResult = DB::select($soldMtQuery, $soldParams);
        $seedTotal->total_sold_mt = $soldResult[0]->total_sold_mt ?? 0;
    }

    $blockwiseTotalsQuery = "
        SELECT
            b.block_name,
            hu.seed_name,
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2))) AS total_production_mt,
            COALESCE(CAST(ms.sale_price AS DECIMAL(10,2)), 0) AS price_per_mt,
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2)) * COALESCE(CAST(ms.sale_price AS DECIMAL(10,2)), 0)) AS total_price
        " . $baseQuery . "
        GROUP BY b.block_name, hu.block_name, hu.seed_name, ms.sale_price
        ORDER BY hu.block_name, hu.seed_name
    ";

    $blockwiseTotals = DB::select($blockwiseTotalsQuery, $params);

    $grandTotalQuery = "
        SELECT
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2))) AS total_production_mt,
            SUM(CAST(hu.yield_mt AS DECIMAL(10,2)) * COALESCE(CAST(ms.sale_price AS DECIMAL(10,2)), 0)) AS total_price
        " . $baseQuery . "
    ";

    $grandTotalResult = DB::select($grandTotalQuery, $params);
    $grandTotal = $grandTotalResult[0]->total_production_mt ?? 0;
    $grandTotalPrice = $grandTotalResult[0]->total_price ?? 0;

    // Get grand total sold MT
    $grandSoldMtQuery = "
        SELECT SUM(COALESCE(hsr.sold_mt, 0)) as grand_total_sold_mt
        FROM harvest_store_manage hsm
        LEFT JOIN harvest_sale_records hsr ON hsm.id = hsr.harvest_store_id
        WHERE hsm.product_id = 1
        AND hsm.seed_id IS NOT NULL AND hsm.seed_id <> ''
    ";
    $grandSoldParams = [];

    if ($siteId) {
        $grandSoldMtQuery .= " AND hsm.site_id = ?";
        $grandSoldParams[] = $siteId;
    }
    if ($blockFilter) {
        $grandSoldMtQuery .= " AND hsm.block_name = ?";
        $grandSoldParams[] = $blockFilter;
    }
    if ($plotFilter) {
        $grandSoldMtQuery .= " AND hsm.plot_name = ?";
        $grandSoldParams[] = $plotFilter;
    }
    if ($seedNameFilter) {
        $grandSoldMtQuery .= " AND hsm.seed_id = ?";
        $grandSoldParams[] = $seedNameFilter;
    }
    if ($from && $to) {
        $manualCol = Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null;
        if ($applySeasonWithinDateRange && $seasonFilter && $manualCol && isset($this->fullStart) && isset($this->fullEnd)) {
            $grandSoldMtQuery .= " AND (
                (hsm.date BETWEEN ? AND ? AND ({$manualCol} IS NULL OR TRIM({$manualCol}) = '' OR {$manualCol} = ?))
                OR ({$manualCol} = ? AND hsm.date BETWEEN ? AND ?)
            )";
            $grandSoldParams[] = $from;
            $grandSoldParams[] = $to;
            $grandSoldParams[] = $seasonFilter;
            $grandSoldParams[] = $seasonFilter;
            $grandSoldParams[] = $this->fullStart;
            $grandSoldParams[] = $this->fullEnd;
        } else {
            $grandSoldMtQuery .= " AND hsm.date BETWEEN ? AND ?";
            $grandSoldParams[] = $from;
            $grandSoldParams[] = $to;
            if ($applySeasonWithinDateRange && $seasonFilter) {
                $this->appendSeasonSqlFilter($grandSoldMtQuery, $grandSoldParams, 'hsm.date', $seasonFilter, $manualCol);
            }
        }
    } elseif ($seasonFilter) {
        $manualCol = Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null;
        $this->appendSeasonSqlFilter($grandSoldMtQuery, $grandSoldParams, 'hsm.date', $seasonFilter, $manualCol);
    }

    if ($selectedCropId) {
        if (!empty($masterSeedIds)) {
            $inClause = implode(',', array_map('intval', $masterSeedIds));
            $grandSoldMtQuery .= " AND hsm.seed_id IN ($inClause)";
        } else {
            $grandSoldMtQuery .= " AND 1=0";
        }
    }

    $grandSoldResult = DB::select($grandSoldMtQuery, $grandSoldParams);
    $grandTotalSold = $grandSoldResult[0]->grand_total_sold_mt ?? 0;

    $organizedBlockTotals = [];
    foreach ($blockwiseTotals as $item) {
        $blockName = $item->block_name;

        if (!isset($organizedBlockTotals[$blockName])) {
            $organizedBlockTotals[$blockName] = [
                'seeds' => [],
                'blockTotal' => 0,
                'blockTotalPrice' => 0
            ];
        }

        $organizedBlockTotals[$blockName]['seeds'][] = (object)[
            'seed_name' => $item->seed_name,
            'total_production_mt' => (float)$item->total_production_mt,
            'price_per_mt' => (float)$item->price_per_mt,
            'total_price' => (float)$item->total_price
        ];

        $organizedBlockTotals[$blockName]['blockTotal'] += (float)$item->total_production_mt;
        $organizedBlockTotals[$blockName]['blockTotalPrice'] += (float)$item->total_price;
    }

    return (object)[
        'seedTotals' => $seedTotals,
        'blockwiseTotals' => $organizedBlockTotals,
        'grandTotal' => (float)$grandTotal,
        'grandTotalPrice' => (float)$grandTotalPrice,
        'grandTotalSold' => (float)$grandTotalSold
    ];
}

// Fixed getSilageYieldTotals method
protected function getSilageYieldTotals($blockFilter = null, $plotFilter = null, $seedNameFilter = null, $siteId = null, $from = null, $to = null, $seasonFilter = null, $selectedCropId = null, $applySeasonWithinDateRange = false)
{
    if (!Schema::hasTable('harvest_store_manage') || !Schema::hasTable('harvest_sale_records')) {
        return (object)[
            'seedTotals' => [],
            'blockwiseTotals' => [],
            'grandTotal' => 0,
            'grandTotalPrice' => 0,
            'grandTotalSold' => 0
        ];
    }

    
    $yieldQuery = DB::table('harvest_store_manage as hsm')
        ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsm.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(hsm.yield_mt) as total_yield_mt')
        )
        ->where('hsm.product_id', 3) // Silage product_id = 3
        ->whereNotNull('hsm.seed_id');
    
    $saleQuery = DB::table('harvest_sale_records as hsr')
        ->leftJoin('master_seed as ms', 'hsr.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsr.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(COALESCE(hsr.total_price, 0)) as total_price'),
            DB::raw('SUM(COALESCE(hsr.sold_mt, 0)) as total_sold_mt')
        )
        ->where('hsr.product_id', 3) // Silage product_id = 3
        ->whereNotNull('hsr.seed_id');
    
    if ($siteId) {
        $yieldQuery->where('hsm.site_id', $siteId);
        $saleQuery->where('hsr.site_id', $siteId);
    }
    if ($blockFilter) {
        $yieldQuery->where('hsm.block_name', $blockFilter);
    }
    if ($plotFilter) {
        $yieldQuery->where('hsm.plot_name', $plotFilter);
    }
    if ($seedNameFilter) {
        $yieldQuery->where('hsm.seed_id', $seedNameFilter);
        $saleQuery->where('hsr.seed_id', $seedNameFilter);
    }
    
    if ($from && $to) {
        $yieldQuery->whereBetween('hsm.date', [$from, $to]);
        $saleQuery->whereBetween('hsr.sale_date', [$from, $to]);
        if ($applySeasonWithinDateRange && $seasonFilter) {
            $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
            $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
        }
    } elseif ($seasonFilter) {
        $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
        $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
    }

    if ($selectedCropId) {
        $selectedCropIds = is_array($selectedCropId) ? array_filter($selectedCropId) : [$selectedCropId];
        $yieldQuery->whereIn('s.id', $selectedCropIds);
        $saleQuery->whereIn('s.id', $selectedCropIds);
    }
    
    
    $yieldQuery->groupBy('hsm.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    $saleQuery->groupBy('hsr.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    
    $yieldResults = $yieldQuery->get();
    $saleResults = $saleQuery->get();
    
    // Convert sale results to associative array keyed by seed_id
    $salesBySeedId = [];
    foreach ($saleResults as $saleData) {
        $salesBySeedId[$saleData->seed_id] = $saleData;
    }
    
    $seedTotals = [];
    $grandYield = 0;
    $grandPrice = 0;
    $grandSold = 0;
    
    foreach ($yieldResults as $yieldData) {
        $yield_mt = (float)$yieldData->total_yield_mt;
        $seed_id = $yieldData->seed_id;
        
        // Check if we have sales data for this seed_id
        $total_price = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_price : 0;
        $total_sold_mt = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_sold_mt : 0;
        
        $price_per_mt = $yield_mt > 0 ? round($total_price / $yield_mt, 2) : 0;
        
        $seedTotals[] = (object)[
            'seed_name' => $yieldData->seed_name,
            'total_yield_mt' => $yield_mt,
            'price_per_mt' => $price_per_mt,
            'total_price' => $total_price,
            'total_sold_mt' => $total_sold_mt
        ];
        
        $grandYield += $yield_mt;
        $grandPrice += $total_price;
        $grandSold += $total_sold_mt;
    }
    
    return (object)[
        'seedTotals' => $seedTotals,
        'blockwiseTotals' => [],
        'grandTotal' => $grandYield,
        'grandTotalPrice' => $grandPrice,
        'grandTotalSold' => $grandSold
    ];
}

// Fixed getHayMakingYieldTotals method
protected function getHayMakingYieldTotals($blockFilter = null, $plotFilter = null, $seedNameFilter = null, $siteId = null, $from = null, $to = null, $seasonFilter = null, $selectedCropId = null, $applySeasonWithinDateRange = false)
{
    if (!Schema::hasTable('harvest_store_manage') || !Schema::hasTable('harvest_sale_records')) {
        return (object)[
            'seedTotals' => [],
            'blockwiseTotals' => [],
            'grandTotal' => 0,
            'grandTotalPrice' => 0,
            'grandTotalSold' => 0
        ];
    }
    
    $yieldQuery = DB::table('harvest_store_manage as hsm')
        ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsm.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(hsm.total_mt) as total_yield_mt')
        )
        ->where('hsm.product_id', 2) // Hay product_id = 2
        ->whereNotNull('hsm.seed_id');
    
    $saleQuery = DB::table('harvest_sale_records as hsr')
        ->leftJoin('master_seed as ms', 'hsr.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsr.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(COALESCE(hsr.total_price, 0)) as total_price'),
            DB::raw('SUM(COALESCE(hsr.sold_mt, 0)) as total_sold_mt')
        )
        ->where('hsr.product_id', 2) // Hay product_id = 2
        ->whereNotNull('hsr.seed_id');
    
    if ($siteId) {
        $yieldQuery->where('hsm.site_id', $siteId);
        $saleQuery->where('hsr.site_id', $siteId);
    }
    if ($blockFilter) {
        $yieldQuery->where('hsm.block_name', $blockFilter);
    }
    if ($plotFilter) {
        $yieldQuery->where('hsm.plot_name', $plotFilter);
    }
    if ($seedNameFilter) {
        $yieldQuery->where('hsm.seed_id', $seedNameFilter);
        $saleQuery->where('hsr.seed_id', $seedNameFilter);
    }

    if ($from && $to) {
        $yieldQuery->whereBetween('hsm.date', [$from, $to]);
        $saleQuery->whereBetween('hsr.sale_date', [$from, $to]);
        if ($applySeasonWithinDateRange && $seasonFilter) {
            $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
            $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
        }
    } elseif ($seasonFilter) {
        $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
        $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
    }

    if ($selectedCropId) {
        $selectedCropIds = is_array($selectedCropId) ? array_filter($selectedCropId) : [$selectedCropId];
        $yieldQuery->whereIn('s.id', $selectedCropIds);
        $saleQuery->whereIn('s.id', $selectedCropIds);
    }

    $yieldQuery->groupBy('hsm.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    $saleQuery->groupBy('hsr.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    
    $yieldResults = $yieldQuery->get();
    $saleResults = $saleQuery->get();
    
    // Convert sale results to associative array keyed by seed_id
    $salesBySeedId = [];
    foreach ($saleResults as $saleData) {
        $salesBySeedId[$saleData->seed_id] = $saleData;
    }
    
    $seedTotals = [];
    $grandYield = 0;
    $grandPrice = 0;
    $grandSold = 0;
    
    foreach ($yieldResults as $yieldData) {
        $yield_mt = (float)$yieldData->total_yield_mt;
        $seed_id = $yieldData->seed_id;
        
        // Check if we have sales data for this seed_id
        $total_price = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_price : 0;
        $total_sold_mt = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_sold_mt : 0;
        
        $price_per_mt = $yield_mt > 0 ? round($total_price / $yield_mt, 2) : 0;
        
        $seedTotals[] = (object)[
            'seed_name' => $yieldData->seed_name,
            'total_yield_mt' => $yield_mt,
            'price_per_mt' => $price_per_mt,
            'total_price' => $total_price,
            'total_sold_mt' => $total_sold_mt
        ];
        
        $grandYield += $yield_mt;
        $grandPrice += $total_price;
        $grandSold += $total_sold_mt;
    }
    
    return (object)[
        'seedTotals' => $seedTotals,
        'blockwiseTotals' => [],
        'grandTotal' => $grandYield,
        'grandTotalPrice' => $grandPrice,
        'grandTotalSold' => $grandSold
    ];
}

// Fixed getHarvestStoreManageYieldTotals method
protected function getHarvestStoreManageYieldTotals($blockFilter = null, $plotFilter = null, $seedNameFilter = null, $siteId = null, $from = null, $to = null, $seasonFilter = null, $selectedCropId = null, $applySeasonWithinDateRange = false)
{
    if (
        !Schema::hasTable('harvest_store_manage') ||
        !Schema::hasTable('harvest_sale_records')
    ) {
        return (object)[
            'seedTotals' => [],
            'blockwiseTotals' => [],
            'grandTotal' => 0,
            'grandTotalPrice' => 0,
            'grandTotalSold' => 0
        ];
    }
    
    $yieldQuery = DB::table('harvest_store_manage as hsm')
        ->leftJoin('master_seed as ms', 'hsm.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsm.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(hsm.total_mt) as total_yield_mt')
        )
        ->whereNotNull('hsm.seed_id')
        ->whereIn('hsm.product_id', [1, 4]);

    $saleQuery = DB::table('harvest_sale_records as hsr')
        ->leftJoin('master_seed as ms', 'hsr.seed_id', '=', 'ms.id')
        ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
        ->select(
            'hsr.seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            DB::raw('SUM(COALESCE(hsr.total_price, 0)) as total_price'),
            DB::raw('SUM(COALESCE(hsr.sold_mt, 0)) as total_sold_mt')
        )
        ->whereNotNull('hsr.seed_id')
        ->whereIn('hsr.product_id', [1, 4]);
    
    if ($siteId) {
        $yieldQuery->where('hsm.site_id', $siteId);
        $saleQuery->where('hsr.site_id', $siteId);
    }
    if ($blockFilter) {
        $yieldQuery->where('hsm.block_name', $blockFilter);
    }
    if ($plotFilter) {
        $yieldQuery->where('hsm.plot_name', $plotFilter);
    }
    if ($seedNameFilter) {
        $yieldQuery->where('hsm.seed_id', $seedNameFilter);
        $saleQuery->where('hsr.seed_id', $seedNameFilter);
    }

    if ($from && $to) {
        $yieldQuery->whereBetween('hsm.date', [$from, $to]);
        $saleQuery->whereBetween('hsr.sale_date', [$from, $to]);
        if ($applySeasonWithinDateRange && $seasonFilter) {
            $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
            $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
        }
    } elseif ($seasonFilter) {
        $this->applySeasonFilter($yieldQuery, 'hsm.date', $seasonFilter, Schema::hasColumn('harvest_store_manage', 'manual_season') ? 'hsm.manual_season' : null);
        $this->applySeasonFilter($saleQuery, 'hsr.sale_date', $seasonFilter, Schema::hasColumn('harvest_sale_records', 'manual_season') ? 'hsr.manual_season' : null);
    }

    if ($selectedCropId) {
        $selectedCropIds = is_array($selectedCropId) ? array_filter($selectedCropId) : [$selectedCropId];
        $yieldQuery->whereIn('s.id', $selectedCropIds);
        $saleQuery->whereIn('s.id', $selectedCropIds);
    }
    
    $yieldQuery->groupBy('hsm.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    $saleQuery->groupBy('hsr.seed_id', 's.id', 's.name', 'mv.id', 'mv.variety_name');
    
    $yieldResults = $yieldQuery->get();
    $saleResults = $saleQuery->get();
    
    // Convert sale results to associative array keyed by seed_id
    $salesBySeedId = [];
    foreach ($saleResults as $saleData) {
        $salesBySeedId[$saleData->seed_id] = $saleData;
    }
    
    $seedTotals = [];
    $grandYield = 0;
    $grandPrice = 0;
    $grandSold = 0;
   
    foreach ($yieldResults as $yieldData) {
        $yield_mt = (float)$yieldData->total_yield_mt;
        $seed_id = $yieldData->seed_id;
        
        // Check if we have sales data for this seed_id
        $total_price = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_price : 0;
        $total_sold_mt = isset($salesBySeedId[$seed_id]) ? (float)$salesBySeedId[$seed_id]->total_sold_mt : 0;
        
        $price_per_mt = $yield_mt > 0 ? round($total_price / $yield_mt, 2) : 0;
        
        $seedTotals[] = (object)[
            'seed_name' => $yieldData->seed_name,
            'total_yield_mt' => $yield_mt,
            'price_per_mt' => $price_per_mt,
            'total_price' => $total_price,
            'total_sold_mt' => $total_sold_mt
        ];
        
        $grandYield += $yield_mt;
        $grandPrice += $total_price;
        $grandSold += $total_sold_mt;
    }
    
    return (object)[
        'seedTotals' => $seedTotals,
        'blockwiseTotals' => [],
        'grandTotal' => $grandYield,
        'grandTotalPrice' => $grandPrice,
        'grandTotalSold' => $grandSold
    ];
}


private function getFertilizerRateByDate($fertilizerId, $useDate = null)
{
    $query = DB::table('fertilizer_stock_history')
        ->where('fertilizer_id', $fertilizerId);

    if (!empty($useDate)) {
        $query->whereDate('created_at', '<=', $useDate);
    }

    return (float) $query
        ->orderBy('created_at', 'asc')   // FIFO → oldest first
        ->value('rate');
}

    public function index(Request $request)
    {
         
        $user = Auth::user();
        $loggedInSiteId = $user->site_id;
        $role = $user->role;

        $selectedSiteId = $request->input('site_id', $loggedInSiteId);

        if ($role != 1) {
            $selectedSiteId = $loggedInSiteId;
        }

        $block = $request->input('block');
        $plot = $request->input('plot');
        $manualFrom = $request->input('from');
        $manualTo = $request->input('to');
        $activity = $request->input('activity');
        $season = $request->input('season');
        $financialYear = $this->normalizeFinancialYear($request->input('financial_year'));
        $seasonFilter = $request->input('season_filter');
        $seedName = $request->input('seed_id');
        $showOnlySeedData = $request->input('show_only_seed_data', false);
        
        $selectedCropId = $request->input('crop_id');
        $selectedCropIds = [];
        $masterSeedIds = [];
        $masterSeedNames = [];
        if ($selectedCropId) {
            $cropName = DB::table('seed')->where('id', $selectedCropId)->value('name');
            if ($cropName) {
                $selectedCropIds = DB::table('seed')
                    ->where('name', $cropName)
                    ->pluck('id')
                    ->toArray();
            }

            $masterSeeds = DB::table('master_seed as ms')
                ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
                ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
                ->whereIn('ms.seed_id', !empty($selectedCropIds) ? $selectedCropIds : [$selectedCropId])
                ->select('ms.id', DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"))
                ->get();
                
            $masterSeedIds = $masterSeeds->pluck('id')->toArray();
            $masterSeedNames = $masterSeeds->pluck('seed_name')->toArray();
            
            if ($cropName && !in_array($cropName, $masterSeedNames)) {
                $masterSeedNames[] = $cropName;
            }
        }
        
        $crops = DB::table('seed')
            ->select(DB::raw('MIN(id) as id'), 'name')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $from = $manualFrom;
        $to = $manualTo;

        if ($from) {
            try {
                $from = \Carbon\Carbon::parse($from)->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        if ($to) {
            try {
                $to = \Carbon\Carbon::parse($to)->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        if ($financialYear) {
            [$startYear, $endYearSuffix] = explode('-', $financialYear);
            $endYear = '20' . $endYearSuffix;
            $this->fullStart = $startYear . '-04-01';
            $this->fullEnd = $endYear . '-05-31';
        }

        $applySeasonWithinDateRange = false;

        // Date Filter Logic for Financial Year and Season
        if ($financialYear && empty($manualFrom) && empty($manualTo)) {
            [$from, $to] = $this->getFinancialYearDateRange($financialYear, $seasonFilter);
            $applySeasonWithinDateRange = (bool)$seasonFilter;
        } elseif ($seasonFilter) {
            $applySeasonWithinDateRange = true;
        }


        $perPage = 8;

        $currentPage = $request->input('page', 1);

        $blocks = [];
        $plots = [];
        $plotsByBlock = [];
        $seedNames = [];
        $sites = [];

        if ($role == 1) {
            $sites = DB::table('master_sites')->pluck('site_name', 'id')->toArray();
        } else {
            $userSite = DB::table('master_sites')->where('id', $loggedInSiteId)->first();
            if ($userSite) {
                $sites = [$userSite->id => $userSite->site_name];
            }
        }

        $masterManpowerRates = DB::table('master_manpower')
        ->where('site_id', $selectedSiteId)
            ->pluck('rate', 'category')
            ->toArray();

        $masterManpowerNoOfPerson = DB::table('master_manpower')
        ->where('site_id', $selectedSiteId)
            ->pluck('no_of_person', 'category')
            ->toArray();

        $masterFertilizers = DB::table('master_fertilizer as mf')
        ->join('fertilizers as f', 'f.id', '=', 'mf.fertilizer_id')
        ->where('mf.site_id', $selectedSiteId)
        ->select(
            'mf.id',
            'mf.fertilizer_id',
            'f.fertilizer_name',
            'mf.rate'
        )
        ->get()
        ->keyBy('id'); 
  
        // Collect unique blocks, plots, and seeds across all tables regardless of activity filter
        foreach ($this->activityTables as $table) {
            if (!Schema::hasTable($table)) continue;

            $blockQuery = DB::table($table);
            if (Schema::hasColumn($table, 'site_id')) {
                $blockQuery->where('site_id', $selectedSiteId);
            }

            if (Schema::hasColumn($table, 'block_name')) {
                $newBlocks = $blockQuery->distinct()->pluck('block_name')->toArray();
               // $blocks = array_merge($blocks, $newBlocks);

                if (Schema::hasColumn($table, 'plot_name')) {
                    $blockPlotPairsQuery = DB::table($table)
                        ->select('block_name', 'plot_name')
                        ->distinct();

                    if (Schema::hasColumn($table, 'site_id')) {
                        $blockPlotPairsQuery->where('site_id', $selectedSiteId);
                    }
                    $blockPlotPairs = $blockPlotPairsQuery->get();

                    foreach ($blockPlotPairs as $pair) {
                        if (!empty($pair->block_name) && !empty($pair->plot_name)) {
                            if (!isset($plotsByBlock[$pair->block_name])) {
                                $plotsByBlock[$pair->block_name] = [];
                            }
                            if (!in_array($pair->plot_name, $plotsByBlock[$pair->block_name])) {
                                $plotsByBlock[$pair->block_name][] = $pair->plot_name;
                            }
                        }
                    }
                }
            }

            $plotQuery = DB::table($table);
            if (Schema::hasColumn($table, 'site_id')) {
                $plotQuery->where('site_id', $selectedSiteId);
            }
            if (Schema::hasColumn($table, 'plot_name')) {
                $newPlots = $plotQuery->distinct()->pluck('plot_name')->toArray();
                $plots = array_merge($plots, $newPlots);
            }

            $seedNameQuery = DB::table($table);
            if (Schema::hasColumn($table, 'site_id')) {
                $seedNameQuery->where('site_id', $selectedSiteId);
            }
            if (Schema::hasColumn($table, 'seed_id')) {
                $tableSeeds = $seedNameQuery
                    ->distinct()
                    ->whereNotNull('seed_id')
                    ->where('seed_id', '!=', '')
                    ->pluck('seed_id')
                    ->toArray();
                $seedNames = array_merge($seedNames, $tableSeeds);
            }
        }

        if ($role == 1) {
        // Admin: get all blocks from master_land
        $blocks = DB::table('master_land')
            ->join('blocks as b', 'master_land.block_name', '=', 'b.id')
            ->select('b.id as block_id', 'b.block_name')
            ->where('site_id', $selectedSiteId)
            ->distinct()
            ->get();
    } else {
        // Other roles: get blocks only for user's site from harvest_store_manage
        $blocks = DB::table('master_land as ml')
            ->join('blocks as b', 'ml.block_name', '=', 'b.id')
            ->where('ml.site_id', $selectedSiteId)
            ->select('b.id as block_id', 'b.block_name')
            ->where('ml.site_id', $selectedSiteId)
            ->distinct()
            ->get();
    }
        $plots = array_unique($plots);
        $seedNames = array_unique($seedNames);
        //sort($blocks);
        sort($plots);
        sort($seedNames);
 
        foreach ($plotsByBlock as $blockName => $blockPlots) {
            sort($plotsByBlock[$blockName]);
        }

        $machines = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
        $tractors = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();
        $fertilizers = DB::table('master_fertilizer as mf')
        ->join('fertilizers as f', 'f.id', '=', 'mf.fertilizer_id')
        ->where('mf.site_id', $selectedSiteId)
        ->pluck('f.fertilizer_name', 'mf.id')
        ->toArray();

        $chemicals = DB::table('master_chemical')->pluck('chemical_name', 'id')->where('site_id', $selectedSiteId)->toArray();
        $seeds = DB::table('master_seed')->get(['id', 'seed_name', 'seed_stock_kg'])->where('site_id', $selectedSiteId)->keyBy('id')->toArray();

        $summary = [];
        $totalStats = [
            'electricityCost' => 0,
            'hsdCost' => 0,
            'fertilizerCost' => 0,
            'maintenanceCost' => 0,
            'manpowerCost' => 0,
            'productionCost' => 0,
            'profitSum' => 0,
            'lossSum' => 0,
            'totalCost' => 0, // This will accumulate costs for filtered activity records
            'seasons' => [
                'kharif' => ['count' => 0, 'totalCost' => 0, 'productionCost' => 0, 'profit' => 0, 'loss' => 0],
                'rabi' => ['count' => 0, 'totalCost' => 0, 'productionCost' => 0, 'profit' => 0, 'loss' => 0],
                'zaid' => ['count' => 0, 'totalCost' => 0, 'productionCost' => 0, 'profit' => 0, 'loss' => 0]
            ]
        ];

        // This variable will hold the total revenue for the financial overview,
        // calculated based on the selected activity.
        $overallTotalSalePriceForFinancialOverview = 0;


        foreach ($this->activityTables as $table) {
            // New logic: If a specific activity is selected, only process that table for detailed rows
            // Skip 'harvest_store_manage' from the main detailed display as it's a summary table
            if ($activity && $activity !== $table) {
                continue; // Skip tables not matching the selected activity
            }
            if ($table === 'harvest_store_manage') {
                continue; // Always skip harvest_store_manage from detailed rows
            }


            if (!Schema::hasTable($table)) continue;

            $colsInTable = Schema::getColumnListing($table);

            $selectCols = ['id'];
            foreach ($this->expectedColumns as $col) {
                if (in_array($col, $colsInTable) && !in_array($col, $selectCols)) {
                    $selectCols[] = $col;
                }
            }

            if ($table === 'crop_protection' && Schema::hasColumn($table, 'chemical_id') && !in_array('chemical_id', $selectCols)) {
                $selectCols[] = 'chemical_id';
            }
            if ($table === 'showing_oprations') {
                if (Schema::hasColumn($table, 'seed_id') && !in_array('seed_id', $selectCols)) {
                    $selectCols[] = 'seed_id';
                }
                if (Schema::hasColumn($table, 'seed_consumption') && !in_array('seed_consumption', $selectCols)) {
                    $selectCols[] = 'seed_consumption';
                }
                if (Schema::hasColumn($table, 'variety') && !in_array('variety', $selectCols)) {
                    $selectCols[] = 'variety';
                }
            }
            if (Schema::hasColumn($table, 'seed_id') && !in_array('seed_id', $selectCols)) {
                $selectCols[] = 'seed_id';
            }
            if (Schema::hasColumn($table, 'yield') && !in_array('yield', $selectCols)) {
                $selectCols[] = 'yield';
            }
            if (Schema::hasColumn($table, 'yield_mt') && !in_array('yield_mt', $selectCols)) {
                $selectCols[] = 'yield_mt';
            }
            if (Schema::hasColumn($table, 'production_value') && !in_array('production_value', $selectCols)) {
                $selectCols[] = 'production_value';
            }
            if ($table === 'fertilizer_soil_record' && Schema::hasColumn($table, 'fertilizer_id') && !in_array('fertilizer_id', $selectCols)) {
                $selectCols[] = 'fertilizer_id';
            }
            if ($table !== 'fertilizer_soil_record' && Schema::hasColumn($table, 'fertilizer_quantity') && !in_array('fertilizer_quantity', $selectCols)) {
                $selectCols[] = 'fertilizer_quantity';
            }
            $hasCreatedAt = Schema::hasColumn($table, 'created_at');
            if ($hasCreatedAt && !in_array('created_at', $selectCols)) {
                $selectCols[] = 'created_at';
            }
            if (Schema::hasColumn($table, 'site_id') && !in_array('site_id', $selectCols)) {
                $selectCols[] = 'site_id';
            }
            foreach (['manual_season', 'manual_session', 'season_name'] as $seasonDisplayColumn) {
                if (Schema::hasColumn($table, $seasonDisplayColumn) && !in_array($seasonDisplayColumn, $selectCols)) {
                    $selectCols[] = $seasonDisplayColumn;
                }
            }
            // Removed specific harvest_store_manage column selection here as it's skipped from detailed view

            if (empty($selectCols)) continue;

            $q = DB::table($table)->select($selectCols);

            if (Schema::hasColumn($table, 'site_id')) {
                $q->where($table . '.site_id', $selectedSiteId);
            }

            if ($block && in_array('block_name', $colsInTable)) $q->where('block_name', $block);
            if ($plot && in_array('plot_name', $colsInTable)) $q->where('plot_name', $plot);
            
            if ($from && $to && in_array('date', $colsInTable)) {
                $manualSeasonCol = in_array('manual_season', $colsInTable) ? 'manual_season' : (in_array('manual_session', $colsInTable) ? 'manual_session' : null);
                if ($applySeasonWithinDateRange && $seasonFilter && $manualSeasonCol && isset($this->fullStart) && isset($this->fullEnd)) {
                    $monthCondition = $this->getSeasonMonthCondition('date', $seasonFilter);
                    $q->where(function ($sub) use ($from, $to, $seasonFilter, $manualSeasonCol, $monthCondition) {
                        $sub->where(function ($sub1) use ($from, $to, $manualSeasonCol, $seasonFilter, $monthCondition) {
                            $sub1->whereBetween('date', [$from, $to])
                                ->where(function ($sub2) use ($manualSeasonCol, $seasonFilter, $monthCondition) {
                                    $sub2->whereNull($manualSeasonCol)
                                         ->orWhere(DB::raw("TRIM($manualSeasonCol)"), '')
                                         ->orWhere($manualSeasonCol, $seasonFilter);
                                });
                        })->orWhere(function ($sub1) use ($manualSeasonCol, $seasonFilter) {
                            $sub1->whereBetween('date', [$this->fullStart, $this->fullEnd])
                                 ->where($manualSeasonCol, $seasonFilter);
                        });
                    });
                } else {
                    $q->whereBetween('date', [$from, $to]);
                    if ($applySeasonWithinDateRange && $seasonFilter) {
                        $this->applySeasonFilter($q, 'date', $seasonFilter, $manualSeasonCol);
                    }
                }
            } elseif ($seasonFilter && in_array('date', $colsInTable)) {
                // Only Season selected (across all years)
                $manualSeasonCol = in_array('manual_season', $colsInTable) ? 'manual_season' : (in_array('manual_session', $colsInTable) ? 'manual_session' : null);
                $this->applySeasonFilter($q, 'date', $seasonFilter, $manualSeasonCol);
            }

            if ($selectedCropId) {
                if (Schema::hasColumn($table, 'seed_id')) {
                    if (!empty($masterSeedIds)) {
                        $q->whereIn('seed_id', $masterSeedIds);
                    } else {
                        continue; // No matching master_seed records
                    }
                } elseif (Schema::hasColumn($table, 'seed_name')) {
                    if (!empty($masterSeedNames)) {
                        $q->whereIn('seed_name', $masterSeedNames);
                    } else {
                        continue; // No matching master_seed records
                    }
                } elseif (Schema::hasColumn($table, 'crop_id')) {
                    $cropIdsToFilter = !empty($selectedCropIds) ? $selectedCropIds : [$selectedCropId];
                    $q->whereIn($table . '.crop_id', $cropIdsToFilter);
                } else {
                    continue; // Table doesn't support crop filtering
                }
            } else {
                if (Schema::hasColumn($table, 'seed_name')) {
                    if ($seedName) {
                        $q->where('seed_name', $seedName)->whereRaw('seed_name COLLATE utf8mb4_unicode_ci = ?', [$seedName]);
                    } else if ($showOnlySeedData) {
                        $q->whereNotNull('seed_id')->where('seed_id', '!=', '');
                    }
                } else {
                    if ($seedName || $showOnlySeedData) {
                        continue;
                    }
                }
            }
            
            $records = $q->get();

            foreach ($records as $rec) {
                $row = ['table' => $table];

                $electricityCost = 0;
                $hsdCost = 0;
                $fertilizerCost = 0;
                $maintenanceCost = 0;
                $manpowerCost = 0;
                $recordTotalCost = 0;
                $productionValue = 0;
                $pricePerMt = 0;
                $yieldMt = 0;
                $currentSalePrice = 0;
                $recordSeason = null;

                $createdAt = property_exists($rec, 'created_at') ? $rec->created_at : null;
                $manualSeason = property_exists($rec, 'manual_season') ? trim((string)$rec->manual_season) : '';
                $manualSession = property_exists($rec, 'manual_session') ? trim((string)$rec->manual_session) : '';
                $dbSeason = property_exists($rec, 'season_name') ? trim((string)$rec->season_name) : '';
                $displaySeason = $dbSeason ?: ($manualSeason ?: $manualSession);
                $recordSeason = $displaySeason ? strtolower($displaySeason) : $this->determineSeason($createdAt);
                $row['season'] = $displaySeason ?: ($recordSeason && isset($this->seasons[$recordSeason]) ? $this->seasons[$recordSeason]['display'] : '--');

            $manpowerTypeMap = DB::table('master_manpower')
            ->where('site_id', $loggedInSiteId)
                ->pluck('category', 'id')
                ->toArray();
                foreach ($this->expectedColumns as $col) {
                    $val = property_exists($rec, $col) ? $rec->$col : null;
                       $manpowerTypeMap = DB::table('manpower_type')
                    ->pluck('type', 'id')   // id => type
                    ->toArray();             
                               
                    if ($col === 'manpower_type' && !empty($val)) {
                
                        // Multiple IDs (e.g. "1,2")
                        if (strpos($val, ',') !== false) {
                
                            $ids = array_map('trim', explode(',', $val));
                            $names = [];
                
                            foreach ($ids as $id) {
                                $names[] = $manpowerTypeMap[$id] ?? 'Unknown';
                            }
                
                            $val = implode(', ', $names);
                
                        } 
                        // Single ID
                        else {
                            $val = $manpowerTypeMap[$val] ?? 'Unknown';
                        }
                    }
                
                    $row[$col] = $val;

                    if ($col === 'machine_id' && isset($val)) {
                        if (strpos($val, ',') !== false) {
                            $ids = explode(',', $val);
                            $names = [];
                            foreach ($ids as $id) {
                                $id = trim($id);
                                $names[] = $machines[$id] ?? 'Unknown';
                            }
                            $val = implode(', ', $names);
                        } else {
                            $val = $machines[$val] ?? 'Unknown';
                        }
                    }
                    // NEW: Handle tractor_id column
                    if ($col === 'tractor_id' && isset($val)) {
                        if (strpos($val, ',') !== false) {
                            $ids = explode(',', $val);
                            $names = [];
                            foreach ($ids as $id) {
                                $id = trim($id);
                                $names[] = $tractors[$id] ?? 'Unknown';
                            }
                            $val = implode(', ', $names);
                        } else {
                            $val = $tractors[$val] ?? 'Unknown';
                        }
                    }
                   
                    if ($table === 'fertilizer_soil_record' && $col === 'fertilizer_id' && !empty($val)) {
                        $fertilizerDetails = json_decode($val, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($fertilizerDetails)) {
                            $displayNames = [];
                            $totalFertilizerQuantity = 0;
                            $currentRecordFertilizerCost = 0;
                            
                            foreach ($fertilizerDetails as $item) {
                                $fertId = $item['id'] ?? null;
                                $qty = $item['quantity'] ?? 0;
                                $uom = $item['uom'] ?? 'kg';

                                if ($fertId && isset($masterFertilizers[$fertId])) {
                                    $masterFert = (object)$masterFertilizers[$fertId];
                                    $fertName = $masterFert->fertilizer_name ?? 'Unknown';
                                    //$fertRate = $masterFert->rate ?? $this->costConfig['fertilizer_rate_per_kg'];
                                    $useDate = $rec->date_of_entry ?? $rec->created_at ?? null;

                                    $fertRate = $this->getFertilizerRateByDate($fertId, $useDate);
                                    
                                    if ($fertRate <= 0) {
                                        $fertRate = $this->costConfig['fertilizer_rate_per_kg'];
                                    }
 
                                    $displayNames[] = "{$fertName}: {$qty}{$uom}";
                                    $totalFertilizerQuantity += (float)$qty;
                                    $currentRecordFertilizerCost += ((float)$qty * (float)$fertRate);
                                } else {
                                    $displayNames[] = "Unknown: {$qty}{$uom}";
                                }
                            }
                            
                            $row['fertilizer_name'] = implode(', ', $displayNames);
                            $row['fertilizer_used'] = round($totalFertilizerQuantity, 2);
                            $fertilizerCost = round($currentRecordFertilizerCost, 2);
                        } else {
                            $fertName = $fertilizers[$val] ?? $val;
                            $row['fertilizer_name'] = $fertName;
                            $row['fertilizer_used'] = $rec->fertilizer_quantity ?? '-';
                            $fertRate = $masterFertilizers[$val]->rate ?? $this->costConfig['fertilizer_rate_per_kg'];
                            $fertilizerCost = is_numeric($row['fertilizer_used']) ? round((float)$row['fertilizer_used'] * (float)$fertRate, 1) : 0;
                        }
                    } elseif ($col === 'fertilizer_id' && isset($val)) {
                        $fertName = $fertilizers[$val] ?? $val;
                        $row['fertilizer_name'] = $fertName;
                        $row['fertilizer_id_raw'] = $val;
                    } else {
                        $row[$col] = $val ?? '-';
                    }
                }

                if ($table !== 'fertilizer_soil_record' && property_exists($rec, 'fertilizer_quantity') && property_exists($row, 'fertilizer_id_raw')) {
                    $fertQty = (float)($rec->fertilizer_quantity ?? 0);
                    $fertId = $row['fertilizer_id_raw'];
                    $fertRate = (float)($masterFertilizers[$fertId]->rate ?? $this->costConfig['fertilizer_rate_per_kg']);
                    $fertilizerCost = is_numeric($fertQty) && is_numeric($fertRate) ? round($fertQty * $fertRate, 1) : 0;
                    $row['fertilizer_used'] = $fertQty;
                } elseif ($table !== 'fertilizer_soil_record') {
                    $row['fertilizer_used'] = '-';
                    $fertilizerCost = 0;
                }
                $row['fertilizer_cost'] = $fertilizerCost ?: '-';


                $hasSeedName = false;
                if (property_exists($rec, 'seed_name') && !empty($rec->seed_name)) {
                    $row['seed_name'] = $rec->seed_name;
                    $hasSeedName = true;
                } else {
                    if ($table === 'showing_oprations' && property_exists($rec, 'seed_id') && !empty($rec->seed_id)) {
                        $seedId = $rec->seed_id;
                        if (isset($seeds[$seedId])) {
                            $seedInfo = (object)$seeds[$seedId];
                            $seedNameFromMaster = $seedInfo->seed_name ?? '';
                            if (!empty($seedNameFromMaster)) {
                                $row['seed_name'] = $seedNameFromMaster;
                                $hasSeedName = true;
                            }
                        }
                    }
                }
                if (!$hasSeedName) {
                    $row['seed_name'] = '-';
                }

                if (property_exists($rec, 'electricity_units') && property_exists($rec, 'cost_per_unit')) {
                    $u = (float)($rec->electricity_units ?? 0);
                    $r = (float)($rec->cost_per_unit ?? 0);
                    $electricityCost = is_numeric($u) && is_numeric($r) ? round($u * $r, 2) : 0;
                    $row['electricity_cost'] = $electricityCost ?: '-';
                } else {
                    $row['electricity_cost'] = '-';
                }

                if (property_exists($rec, 'hsd_consumption')) {
                    $hsd = (float)($rec->hsd_consumption ?? 0);
                    $latestRate = DB::table('diesel_stocks')
                        ->where('site_id', $rec->site_id ?? 1)
                        ->orderByDesc('date_of_purchase')
                        ->value('rate_per_liter');
                    $rate = is_numeric($latestRate) ? (float)$latestRate : 0;
                    $hsdCost = is_numeric($hsd) ? round($hsd * $rate, 2) : 0;
                    $row['hsd_cost'] = $hsdCost ?: '-';
                } else {
                    $row['hsd_cost'] = '-';
                }

                if (property_exists($rec, 'major_maintenance')) {
                    $maint = (float)($rec->major_maintenance ?? 0);
                    $maintenanceCost = is_numeric($maint) ? round($maint, 2) : 0;
                    $row['maintenance_cost'] = $maintenanceCost ?: '-';
                } else {
                    $row['maintenance_cost'] = '-';
                }

                $currentRecordManpowerCost = 0;
            $hasColumnBasedManpower = false;
            
            $columnMap = [
                'unskilled'       => 'Unskilled',
                'semi_skilled_1'  => 'Semi Skilled 1',
                'semi_skilled_2'  => 'Semi Skilled 2',
            ];
            
            foreach ($columnMap as $column => $categoryName) {
            
                if (!empty($rec->$column) && is_numeric($rec->$column) && $rec->$column > 0) {
            
                    $hasColumnBasedManpower = true;
            
                    $persons = (int) $rec->$column;
                    $rate    = (float) ($masterManpowerRates[$categoryName] ?? 0);
            
                    $currentRecordManpowerCost += ($persons * $rate);
                }
            }
            
            // FALLBACK ONLY IF NO COLUMN DATA
            if (!$hasColumnBasedManpower && !empty($rec->manpower_type)) {
            
                $types = array_map('trim', explode(',', $rec->manpower_type));
            
                foreach ($types as $type) {
            
                    if (!isset($masterManpowerRates[$type])) {
                        continue;
                    }
            
                    // 👇 VERY IMPORTANT: persons should come from RECORD, not master
                    $persons = (int) ($rec->no_of_person ?? 0);
                    $rate    = (float) $masterManpowerRates[$type];
            
                    $currentRecordManpowerCost += ($persons * $rate);
                }
            }
            
            $row['manpower_cost'] = $currentRecordManpowerCost > 0
                ? round($currentRecordManpowerCost, 2)
                : '-';


                if (property_exists($rec, 'total_cost') && is_numeric($rec->total_cost)) {
                    $recordTotalCost = (float)$rec->total_cost;
                } else {
                    $recordTotalCost = $electricityCost + $hsdCost + $fertilizerCost + $maintenanceCost + $manpowerCost;
                }
                $row['total_cost'] = is_numeric($recordTotalCost) ? number_format($recordTotalCost, 2) : '-';

                if (!$hasSeedName && ($seedName || $showOnlySeedData)) {
                    continue;
                }

                $yieldMt = property_exists($rec, 'yield_mt') ? (is_numeric($rec->yield_mt) ? (float)$rec->yield_mt : 0) : 0;

                $currentSalePrice = 0;

                // FIXED: For all harvest-related activities, use yield_mt directly from their respective tables
                // Only lookup sale_price from harvest_store_manage for production value calculation
                if (in_array($table, ['harvesting_update', 'silage_making', 'hay_making'])) {
                    // For ALL these tables, use yield_mt directly from the current record
                    $yieldMt = property_exists($rec, 'yield_mt') ? (is_numeric($rec->yield_mt) ? (float)$rec->yield_mt : 0) : 0;

                    // Determine product_id for sale price lookup
                    $productId = null;
                    if ($table === 'silage_making') {
                        $productId = 3; // Silage
                    } elseif ($table === 'hay_making') {
                        $productId = 2; // Hay
                    } elseif ($table === 'harvesting_update') {
                        $productId = 1; // Harvest
                    }

                    // Look up sale price from harvest_store_manage for production value calculation
                    if ($productId !== null) {
                        $seedNameForLookup = $row['seed_name'] ?? null;
                        $blockNameForLookup = $row['block_name'] ?? null;
                        $plotNameForLookup = $row['plot_name'] ?? null;

                        if ($seedNameForLookup && $blockNameForLookup && $plotNameForLookup) {
                            $hsmRecord = DB::table('harvest_store_manage')
                                ->where('product_id', $productId)
                                ->where('seed_name', $seedNameForLookup)
                                ->where('block_name', $blockNameForLookup)
                                ->where('plot_name', $plotNameForLookup)
                                ->where('site_id', $selectedSiteId)
                                ->orderByDesc('date')
                                ->first();

                            if ($hsmRecord && is_numeric($hsmRecord->sale_price)) {
                                $currentSalePrice = (float)$hsmRecord->sale_price;
                            }
                        }
                    }
                } elseif (property_exists($rec, 'production_value') && is_numeric($rec->production_value)) {
                    $productionValue = (float)$rec->production_value;
                } elseif (property_exists($rec, 'yield') && is_numeric($rec->yield)) {
                    $yieldMt = (float)$rec->yield;
                }

                if ($yieldMt > 0 && $currentSalePrice > 0) {
                    $productionValue = $yieldMt * $currentSalePrice;
                    $pricePerMt = $currentSalePrice;
                }

                $row['yield_mt'] = is_numeric($yieldMt) ? number_format($yieldMt, 2) : '-';
                $row['price_per_mt'] = is_numeric($pricePerMt) ? number_format($pricePerMt, 2) : '-';
                $row['production_value'] = is_numeric($productionValue) ? number_format($productionValue, 2) : '-';


                $profit = 0;
                $loss = 0;
                if (is_numeric($recordTotalCost) && is_numeric($productionValue) && (float)$productionValue > (float)$recordTotalCost) {
                    $profit = round((float)$productionValue - (float)$recordTotalCost, 2);
                    $row['profit'] = is_numeric($profit) ? number_format($profit, 2) : '-';
                    $row['loss'] = '-';
                } else if (is_numeric($recordTotalCost) && is_numeric($productionValue)) {
                    $loss = round((float)$recordTotalCost - (float)$productionValue, 2);
                    $row['profit'] = '-';
                    $row['loss'] = is_numeric($loss) ? number_format($loss, 2) : '-';
                } else {
                    $row['profit'] = '-';
                    $row['loss'] = '-';
                }

                if ($recordSeason && isset($totalStats['seasons'][$recordSeason])) {
                    $totalStats['seasons'][$recordSeason]['count']++;
                    $totalStats['seasons'][$recordSeason]['totalCost'] += is_numeric($recordTotalCost) ? (float)$recordTotalCost : 0;
                    $totalStats['seasons'][$recordSeason]['productionCost'] += is_numeric($productionValue) ? (float)$productionValue : 0;
                    $totalStats['seasons'][$recordSeason]['profit'] += (float)$profit;
                    $totalStats['seasons'][$recordSeason]['loss'] += (float)$loss;
                }

                // Accumulate costs for the financial overview based on the current filtered records
                $totalStats['electricityCost'] += (float)$electricityCost;
                $totalStats['hsdCost'] += (float)$hsdCost;
                $totalStats['maintenanceCost'] += (float)$maintenanceCost;
                $totalStats['manpowerCost'] += (float)$manpowerCost;
                $totalStats['totalCost'] += is_numeric($recordTotalCost) ? (float)$recordTotalCost : 0;
                // Accumulate revenue from detailed rows for the financial overview
                // This is specifically for activities that show up in the main table.
                $overallTotalSalePriceForFinancialOverview += is_numeric($productionValue) ? (float)$productionValue : 0;

                $summary[] = $row;
            }
        }
 
       
        $cropFilterIds = !empty($selectedCropIds) ? $selectedCropIds : $selectedCropId;
        $silageTotals = $this->getSilageYieldTotals($request->block, $request->plot, $seedName, $selectedSiteId, $from, $to, $seasonFilter, $cropFilterIds, $applySeasonWithinDateRange);
        $hayTotals = $this->getHayMakingYieldTotals($request->block, $request->plot, $seedName, $selectedSiteId, $from, $to, $seasonFilter, $cropFilterIds, $applySeasonWithinDateRange);
        $harvestStoreManageTotals = $this->getHarvestStoreManageYieldTotals($block, $plot, $seedName, $selectedSiteId, $from, $to, $seasonFilter, $cropFilterIds, $applySeasonWithinDateRange);
        // $seedYieldTotals is for old harvesting_update logic, kept as is but likely less relevant now for total revenue
        $seedYieldTotals = $this->getSeedBasedYieldTotals($block, $plot, $seedName, $selectedSiteId, $from, $to, $seasonFilter, $cropFilterIds, $masterSeedIds, $applySeasonWithinDateRange);


        if (!empty($activity)) { // If a specific activity is selected (not 'All Activities')
            if ($activity == 'harvesting_update') {
                // When 'Harvest' is selected, the Total Revenue should come from Harvest Store Manage totals
                $overallTotalSalePriceForFinancialOverview = ((float)$harvestStoreManageTotals->grandTotalPrice ?? 0);
            } elseif ($activity == 'hay_making') {
                // When 'Hay Making' is selected, the Total Revenue should come from Hay Making totals
                $overallTotalSalePriceForFinancialOverview = ((float)$hayTotals->grandTotalPrice ?? 0);
            } elseif ($activity == 'silage_making') {
                // When 'Silage Making' is selected, the Total Revenue should come from Silage Making totals
                $overallTotalSalePriceForFinancialOverview = ((float)$silageTotals->grandTotalPrice ?? 0);
            } else {
                // For any other specific activity (e.g., Fertilizer, Sowing),
                // the revenue will already be accumulated in $overallTotalSalePriceForFinancialOverview
                // from the detailed loop above (productionValue of that specific activity).
                // These activities typically have 0 productionValue.
                // $overallTotalSalePriceForFinancialOverview would already be correct from the loop for non-harvest activities
            }
        } else {
            // If 'All Activities' is selected, sum up all grand totals for revenue
            $overallTotalSalePriceForFinancialOverview = ((float)$silageTotals->grandTotalPrice ?? 0) +
                                                         ((float)$hayTotals->grandTotalPrice ?? 0) +
                                                         ((float)$harvestStoreManageTotals->grandTotalPrice ?? 0);
            // $totalStats['totalCost'] already sums up all costs from all activities in the main loop for 'All Activities'
        }

        // --- Paging and View Data ---
        $total = count($summary);
        $currentItems = array_slice($summary, ($currentPage - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $currentItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
       
    // ✅ Prepare mapping arrays for both Excel and UI Preview
    $machineMap = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
    $tractorMap = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();
    $blockMap   = DB::table('blocks')->pluck('block_name', 'id')->toArray();
    $plotMap    = DB::table('master_plots')->pluck('plot_name', 'id')->toArray();
    $manpowerMap = DB::table('master_manpower')->pluck('category', 'id')->toArray();
    
    // ✅ Create activity table to display name mapping
    $activityMap = [];
    foreach ($this->orderedActivities as $activity_item) {
        $activityMap[$activity_item['table']] = $activity_item['display'];
    }

// ✅ Excel Download Logic
if ($request->has('download') && $request->input('download') === 'Excel') {
    // Prepare columns for CSV
    $columns = array_keys($summary[0] ?? []);
    $data = $summary;

    // ✅ Create season mapping (display name without Hindi text)
    $seasonMap = [
        'Kharif' => 'Kharif',
        'Rabi' => 'Rabi',
        'Zaid' => 'Zaid'
    ];

    // ✅ Define heading map
    $headingMap = [
        'table'             => 'Activity',
        'machine_id'        => 'Machine Name',
        'tractor_id'        => 'Tractor Name',
        'block_name'        => 'Block Name',
        'plot_name'         => 'Plot Name',
        'major_maintenance' => 'Maintenance Details',
        'total_cost'        => 'Total Cost (₹)',
        'total_hours'       => 'Total Hours',
        'total_area'        => 'Total Area',
        'date'              => 'Date',
        'seed_name'         => 'Seed Name',
        'fertilizer_name'   => 'Fertilizer Name',
        'fertilizer_used'   => 'Fertilizer Used (kg)',
        'fertilizer_cost'   => 'Fertilizer Cost (₹)',
        'electricity_cost'  => 'Electricity Cost (₹)',
        'hsd_cost'          => 'HSD Cost (₹)',
        'maintenance_cost'  => 'Maintenance Cost (₹)',
        'manpower_cost'     => 'Manpower Cost (₹)',
        'yield_mt'          => 'Yield (MT)',
        'price_per_mt'      => 'Price per MT (₹)',
        'production_value'  => 'Production Value (₹)',
        'profit'            => 'Profit (₹)',
        'loss'              => 'Loss (₹)',
        'season'            => 'Season',
    ];

    // Create final headings array
    $finalHeadings = [];
    foreach ($columns as $col) {
        $finalHeadings[] = $headingMap[$col] ?? ucfirst(str_replace('_', ' ', $col));
    }

    // Stream CSV download
    return response()->streamDownload(function () use ($data, $columns, $finalHeadings, $machineMap, $tractorMap, $blockMap, $plotMap, $manpowerMap, $activityMap, $seasonMap) {
        $handle = fopen('php://output', 'w');
        
        // ✅ Write proper headings first
        fputcsv($handle, $finalHeadings);

        foreach ($data as $item) {
            $row = [];
            
            foreach ($columns as $col) {
                $value = $item[$col] ?? '-';
                
                // Apply transformations based on column type
                switch ($col) {
                    case 'table':
                        // ✅ Convert table name to display name
                        if (!empty($value) && $value !== '-') {
                            $value = $activityMap[$value] ?? ucfirst(str_replace('_', ' ', $value));
                        }
                        break;

                    case 'season':
                        // ✅ Remove Hindi text from season names
                        if (!empty($value) && $value !== '-') {
                            $value = $seasonMap[$value] ?? $value;
                        }
                        break;

                    case 'machine_id':
                        if (!empty($value) && $value !== '-') {
                            $ids = array_map('trim', explode(',', $value));
                            $value = implode(', ', array_map(fn($id) => $machineMap[$id] ?? $id, $ids));
                        }
                        break;
                        
                    case 'tractor_id':
                        if (!empty($value) && $value !== '-') {
                            $ids = array_map('trim', explode(',', $value));
                            $value = implode(', ', array_map(fn($id) => $tractorMap[$id] ?? $id, $ids));
                        }
                        break;
                        
                    case 'block_name':
                        if (!empty($value) && $value !== '-') {
                            $blockId = trim($value);
                            $value = $blockMap[$blockId] ?? $blockId;
                        }
                        break;
                        
                    case 'plot_name':
                        if (!empty($value) && $value !== '-') {
                            $plotId = trim($value);
                            $value = $plotMap[$plotId] ?? $plotId;
                        }
                        break;

                    case 'manpower_type':
                        // ✅ Convert manpower type IDs to category names
                        if (!empty($value) && $value !== '-') {
                            $ids = array_map('trim', explode(',', $value));
                            $value = implode(', ', array_map(fn($id) => $manpowerMap[$id] ?? $id, $ids));
                        }
                        break;
                        
                    case 'major_maintenance':
                        if (!empty($value) && $value !== '-') {
                            $decoded = json_decode($value, true);
                            if (is_array($decoded)) {
                                $maintenanceInfo = '';
                                foreach ($decoded as $maint) {
                                    $maintenanceInfo .= ($maint['spare_part'] ?? '-') . ' - ₹' . ($maint['value'] ?? 0) . '; ';
                                }
                                $value = rtrim($maintenanceInfo, '; ');
                            }
                        }
                        break;
                }
                
                $row[] = $value;
            }
            
            fputcsv($handle, $row);
        }

        fclose($handle);
    }, 'cost_analysis_' . date('Y_m_d_H_i_s') . '.csv', [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename=cost_analysis_' . date('Y_m_d_H_i_s') . '.csv',
        'Cache-Control' => 'no-cache, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
}
        return view('cost_analysis.index', [
            'summary' => $currentItems,
            'full_summary' => $summary, // For the Preview modal
            'paginator' => $paginator,
            'totalStats' => $totalStats, // totalCost will now be filtered correctly
            'blocks' => $blocks,
            'plots' => $plots,
            'plotsByBlock' => $plotsByBlock,
            'seedNames' => $seedNames,
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'loggedInUserRole' => $role,
            'expectedColumns' => $this->expectedColumns,
            'activityTables' => $this->activityTables,
            'orderedActivities' => $this->orderedActivities ?? [],
            'seasons' => $this->seasons,
            'silageTotals' => $silageTotals,
            'hayTotals' => $hayTotals,
            'seedYieldTotals' => $seedYieldTotals,
            'harvestStoreManageTotals' => $harvestStoreManageTotals,
            'overallTotalSalePrice' => $overallTotalSalePriceForFinancialOverview, // Pass the computed revenue
            'machineMap' => $machineMap,
            'tractorMap' => $tractorMap,
            'blockMap' => $blockMap,
            'plotMap' => $plotMap,
            'manpowerMap' => $manpowerMap,
            'activityMap' => $activityMap,
            'crops' => $crops,
            'sel' => [
                'block' => $block,
                'plot' => $plot,
                'from' => $manualFrom, // Pass original manual dates back to UI
                'to' => $manualTo,     // Pass original manual dates back to UI
                'activity' => $activity,
                'season' => $season,
                'seedName' => $seedName,
                'selectedSiteId' => $selectedSiteId,
                'financialYear' => $financialYear,
                'seasonFilter' => $seasonFilter,
                'crop_id' => $selectedCropId
            ]


        ]);
    }

  public function getPlotsByBlock(Request $request)
    {
        $blockName = $request->input('block');
    
        if (empty($blockName)) {
            return response()->json([]);
        }
    
        // Fetch plots directly from master_plots table
        $plots = DB::table('master_plots')
            ->where('block_name', $blockName)
            ->select('id', 'plot_name')
            ->orderBy('plot_name')
            ->get();
    
        return response()->json($plots);
    }
    public function getPlots($blockId)
    {
        $plots = DB::table('master_plots')
            ->where('block_id', $blockId)
            ->pluck('plot_name', 'id'); // id => name

        return response()->json($plots);
    }
}
