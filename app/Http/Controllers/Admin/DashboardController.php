<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
USE Illuminate\Support\Facades\Log;


class DashboardController extends Controller
{
    

public function ccbf_dashboard(Request $request)
{
    $loggedInUser = Auth::user();
    $userRole = $loggedInUser->role;
    $userPrimarySiteName = $loggedInUser->site_name; // Use site_name from users table

    $targetSiteName = null; // This will be the site_name used for filtering queries
    $displayMessage = null;

    $defaultMonthlyData = array_replace(array_fill_keys(range(1, 12), 0), []);
    $defaultLandStageData = ['Standing Crop' => 0, 'Crop Sowing within month' => 0, 'Empty Plot' => 0];

    if ($userRole == 1) { // Super Admin
        // Super admin can view a specific site if 'site_name' is in the request query
        if ($request->has('site_name') && !empty($request->input('site_name'))) {
            $targetSiteName = $request->input('site_name');
        }
    } elseif ($userRole == 3) { // Role 3
        $targetSiteName = '2'; // Set targetSiteName to site 2 for role 3
    } else { // Non-Super Admin Users
        $targetSiteName = $userPrimarySiteName;
        if (!$targetSiteName) {
            $displayMessage = 'You are not currently assigned to a specific site. Please contact an administrator. Displaying global data or zeros where site-specific data is required.';
            Log::warning("User {$loggedInUser->id} (Role: {$userRole}) has no site_name assigned.");
        }
    }

    $currentMonth = now()->month;

    // --- Updated logic for total land graph ---
    $monthlyQuery = DB::table('master_land');
    if ($targetSiteName) {
        $monthlyQuery->where('site_id', $loggedInUser->site_id);
    }
    $monthlyDataRaw = $monthlyQuery
        ->selectRaw('MONTH(created_at) as month, SUM(area_ha) as total_area')
        ->where('is_deleted', 0) // Exclude deleted records
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->pluck('total_area', 'month')
        ->toArray();
    $monthlyData = array_replace(array_fill_keys(range(1, 12), 0), $monthlyDataRaw);
    $totalStockQuery = DB::table('master_fertilizer');
    if ($targetSiteName) {
        $totalStockQuery->where('site_id', $loggedInUser->site_id);
    }
    $totalStock = $totalStockQuery->sum('stock_kg');
    
    // Total Used from fertilizer_consumption table
    $totalUsedQuery = DB::table('fertilizer_consumption');
    if ($targetSiteName) {
        $totalUsedQuery->where('site_id', $loggedInUser->site_id);
    }
    $totalUsed = $totalUsedQuery
    ->whereNotNull('consumed_quantity')
    ->sum('consumed_quantity');
    
   $seedStockQuery = DB::table('master_seed');
    if ($targetSiteName) {
        $seedStockQuery->where('site_id', $loggedInUser->site_id);
    }
    $seedStock = $seedStockQuery->sum('seed_stock_kg');
     
    $seedUsedQuery = DB::table('seed_stock_history')
        ->where('type', 'Consumption');   // only consumed rows
    if ($targetSiteName) {
        $seedUsedQuery->where('site_id', $loggedInUser->site_id);
    }
    $seedUsed = $seedUsedQuery->sum('seed_stock_kg');

    $totalHayQuery = DB::table('hay_making');
    if ($targetSiteName) {
        $totalHayQuery->where('site_id', $loggedInUser->site_id);
    }
    $totalHay = $totalHayQuery->sum('yield_mt');
    $totalHay = $totalHay * 0.2; // Apply 20% conversion factor
    
     
    $usedHayQuery = DB::table('harvest_sale_records')
        ->where('product_id', 2); // 2 = Hay
    
    if ($targetSiteName) {
        $usedHayQuery->where('site_id', $loggedInUser->site_id);
    }
    
    $usedHay = $usedHayQuery->sum('sold_mt');
    
    
    $remainingHay = $totalHay - $usedHay;

    $baseShowingQuery = DB::table('showing_oprations');
    if ($targetSiteName) {
        $baseShowingQuery->where('site_id', $loggedInUser->site_id);
    }

    $totalPlots = (clone $baseShowingQuery)->count();
    $totalWithCrop = (clone $baseShowingQuery)->whereNotNull('date')->count();
    $cropSowingThisMonth = (clone $baseShowingQuery)
        ->whereMonth('date', $currentMonth)
        ->whereNotNull('date')
        ->count();

    $standingCrop = $totalWithCrop - $cropSowingThisMonth;
    $emptyPlots = $totalPlots - $totalWithCrop;

    $landStageData = [
        'Standing Crop' => $standingCrop,
        'Crop Sowing within month' => $cropSowingThisMonth,
        'Empty Plot' => $emptyPlots,
    ];
 
    // Base user query for total user count stat card
    $baseUserCountQuery = User::query();
    if ($targetSiteName) {
        $baseUserCountQuery->where('site_id', $loggedInUser->site_id);
    }
    $userCount = $baseUserCountQuery->count(); // Total users for the 'Users' stat card

    $fertilizerMasterQuery = DB::table('master_fertilizer');
    if ($targetSiteName) {
        $fertilizerMasterQuery->where('site_id', $loggedInUser->site_id);
    }
    $fertilizerCount = $fertilizerMasterQuery->count();

    $machineMasterQuery = DB::table('master_machine');
    if ($targetSiteName) {
        $machineMasterQuery->where('site_id', $loggedInUser->site_id);
    }
    $machineCount = $machineMasterQuery->count();
    
    // Dynamic crop count from master_seed table
        $cropCountQuery = DB::table('master_seed');
        if ($targetSiteName) {
            $cropCountQuery->where('site_id', $loggedInUser->site_id);
        }
$cropCount = $cropCountQuery->count(); // No distinct, all rows count

    $sitesForSelector = [];
    if ($userRole == 1) {
        // Fetch distinct site_names from the users table for the selector
        $sitesForSelector = User::select('site_name')
            ->whereNotNull('site_name')
            ->distinct()
            ->orderBy('site_name')
            ->get();
    }
    
    $purchaseData = $this->getTotalDieselPurchased();
    $totalDiesel = $purchaseData['total_purchased_liters'];
     

    
    if ($userRole != 1 && $userPrimarySiteName == null && !$loggedInUser->site_id) {
        $monthlyData = $defaultMonthlyData;
        $landStageData = $defaultLandStageData;
        $totalStock = 0; $totalUsed = 0;
        $seedStock = 0; $seedUsed = 0;
        $totalHay = 0; $usedHay = 0;
        // For users with no site, show global counts for these summary stats
        $userCount = DB::table('users')->count(); // Revert to global count if no site assigned
        $fertilizerCount = DB::table('master_fertilizer')->count();
        $machineCount = DB::table('master_machine')->count();
        $cropCount = DB::table('master_seed')->distinct()->count('seed_name'); // Global crop count
    }

    
    $onlineThresholdMinutes = 5;
    $offlineTime = Carbon::now()->subMinutes($onlineThresholdMinutes);

    // Build a query specifically for online/offline counts, applying site filtering
    $onlineOfflineUserQuery = User::query();
    if ($targetSiteName) {
        $onlineOfflineUserQuery->where('site_name', $targetSiteName);
    }

    // Count online users
    $onlineUsersCount = (clone $onlineOfflineUserQuery)
                            ->where('last_activity_at', '>=', $offlineTime)
                            ->count();

    // Count offline users (activity before threshold or never recorded)
    $offlineUsersCount = (clone $onlineOfflineUserQuery)
                            ->where(function($query) use ($offlineTime) {
                                $query->where('last_activity_at', '<', $offlineTime)
                                      ->orWhereNull('last_activity_at');
                            })
                            ->count();
    // --- END NEW LOGIC ---

    // --- START NEW LOGIC FOR MOVING COUNTING AND MOBILE USER GPS ---
    $recentGpsThresholdMinutes = 10; // GPS data considered recent if within this many minutes
    $recentGpsTime = Carbon::now()->subMinutes($recentGpsThresholdMinutes);

    $mobileUserQuery = User::query();
    if ($targetSiteName) {
        $mobileUserQuery->where('site_id', $loggedInUser->site_id);
    }

    // Count users currently detected as moving (and have sent recent GPS data)
    $movingUsersCount = (clone $mobileUserQuery)
                            ->where('is_moving', true)
                            ->where('last_gps_update_at', '>=', $recentGpsTime)
                            ->count();

    // Count users not moving (and have sent recent GPS data)
    $notMovingUsersCount = (clone $mobileUserQuery)
                            ->where('is_moving', false)
                            ->where('last_gps_update_at', '>=', $recentGpsTime)
                            ->count();

    // Count users with GPS ON (and have sent recent GPS data, and gps_status is true)
    $gpsOnUsersCount = (clone $mobileUserQuery)
                            ->where('gps_status', true)
                            ->where('last_gps_update_at', '>=', $recentGpsTime)
                            ->count();

    // Count users with GPS OFF (or no recent GPS data, or gps_status is explicitly false)
    $gpsOffUsersCount = (clone $mobileUserQuery)
                            ->where(function($query) use ($recentGpsTime) {
                                $query->where('gps_status', false) // GPS explicitly reported as off
                                      ->orWhereNull('last_gps_update_at') // Never sent GPS data
                                      ->orWhere('last_gps_update_at', '<', $recentGpsTime); // GPS data is too old
                            })
                            ->count();
                            
                            
  $site = DB::table('master_sites')->where('id', $loggedInUser->site_id)->first();
  
  
$notificationsQuery = DB::table('notifications')
    ->leftJoin('users', 'notifications.user_id', '=', 'users.id')
    ->select(
        'notifications.*',
        'users.name as user_name'
    )
    ->orderBy('notifications.created_at', 'desc');

// Apply site filter if available
if ($targetSiteName) {
    $notificationsQuery->where('notifications.site_id', $loggedInUser->site_id);
}

// Handle day filter (from ?day_filter=Monday etc.)
if ($request->has('day_filter')) {
    $selectedDay = $request->input('day_filter'); // e.g. "Monday"
    
    // Correct mapping for DAYNAME() in MySQL
    $notificationsQuery->whereRaw('DAYNAME(notifications.created_at) = ?', [$selectedDay]);
}

// Execute query
$notifications = $notificationsQuery->get();

// Get week statuses with task counts for all days using WEEKDAY instead of DAYNAME
$weekStart = now()->startOfWeek(Carbon::MONDAY); // Explicitly start from Monday
$weekEnd = now()->endOfWeek(Carbon::SUNDAY);     // Explicitly end on Sunday

$weekStatusesQuery = DB::table('notifications')
    ->selectRaw('
        WEEKDAY(created_at) as day_index,
        COUNT(*) as task_count,
        SUBSTRING_INDEX(GROUP_CONCAT(status ORDER BY created_at DESC), ",", 1) as latest_status
    ')
    ->where('created_at', '>=', $weekStart)
    ->where('created_at', '<=', $weekEnd);
 
// Apply site filter to week statuses
if ($targetSiteName) {
    $weekStatusesQuery->where('site_id', $loggedInUser->site_id);
}

$weekStatusesRaw = $weekStatusesQuery
    ->groupBy('day_index')
    ->get()
    ->keyBy('day_index');

// Convert day_index (0-6) to day names and rekey
$dayMapping = [
    0 => 'Monday',
    1 => 'Tuesday',
    2 => 'Wednesday',
    3 => 'Thursday',
    4 => 'Friday',
    5 => 'Saturday',
    6 => 'Sunday'
];

$weekStatuses = collect();
foreach ($weekStatusesRaw as $dayIndex => $status) {
    if (isset($dayMapping[$dayIndex])) {
        $weekStatuses->put($dayMapping[$dayIndex], $status);
    }
}

   

    $rainfall = DB::table('weather_logs')
        ->select(
            DB::raw('DATE(COALESCE(date, recorded_at)) as day'), // fallback to recorded_at
            DB::raw('SUM(COALESCE(precipitation, 0)) as total_rainfall')
        )
        ->whereRaw('COALESCE(date, recorded_at) >= ?', [now()->subDays(7)])
        ->groupBy('day')
        ->orderBy('day')
        ->get();
    
// STEP 1: Get all blocks from master_land for selected site
$landBlocks = DB::table('master_land')
    ->where('site_id', $loggedInUser->site_id)
    ->select('block_name')
    ->distinct()
    ->get();

// STEP 2: Get sowing per plot (area_covered sum) - grouped by block_id and plot_id
$sownAreaPerPlot = DB::table('showing_oprations')
    ->where('site_id', $loggedInUser->site_id)
    ->select(
        'block_name as block_id',  // ✅ Actually ye block ID hai
        'plot_name as plot_id',     // ✅ Actually ye plot ID hai
        DB::raw('SUM(area_covered) as sown_area')
    )
    ->groupBy('block_name', 'plot_name')
    ->get()
    ->groupBy('block_id'); // Group by block_id for easy access

$sowingProgressData = [];
 
// STEP 3: Build final block → plot structure
foreach ($landBlocks as $land) {
    $blockId = $land->block_name;  // Actually ye block ID hai
    
    // Get block details to get actual name
    $block = DB::table('blocks')->where('id', $blockId)->first();
    if (!$block) continue;
    
    $blockName = $block->block_name; // Actual block name
    
    // Get all plots from master_plots
    $plots = DB::table('master_plots')
        ->where('block_id', $blockId)
        ->get();
    
    if (!isset($sowingProgressData[$blockName])) {
        $sowingProgressData[$blockName] = ['plots' => []];
    }
    
    // Loop all plots of this block
    foreach ($plots as $plot) {
        $plotId = $plot->id;           // Plot ID
        $plotName = $plot->plot_name;   // Actual plot name
        $totalArea = (float)$plot->area;
        
        // ✅ Find sown area using plot ID (not name)
        $sownArea = 0;
        if (isset($sownAreaPerPlot[$blockId])) {
            $sownPlot = $sownAreaPerPlot[$blockId]->firstWhere('plot_id', $plotId);
            if ($sownPlot) {
                $sownArea = (float)$sownPlot->sown_area;
            }
        }
        
        // Add final structure
        $sowingProgressData[$blockName]['plots'][$plotName] = [
            'total' => $totalArea,
            'sown'  => $sownArea,
        ];
    }
}

 
$sowingProgressJson = json_encode($sowingProgressData);



    // Total rainfall
    $totalRainfall = $rainfall->sum('total_rainfall');

    // Average rainfall
    $avgRainfall = $rainfall->avg('total_rainfall');

    // Labels = dates, Data = rainfall values
    $labels = $rainfall->pluck('day')->map(function ($date) {
        return \Carbon\Carbon::parse($date)->format('M d');
    });

    $data = $rainfall->pluck('total_rainfall');
        
    // dd($notifications);

    return view('admin.dashboard', compact(
        'monthlyData',
        'totalStock', 'totalUsed',
        'seedStock', 'seedUsed',
        'totalDiesel',
        'landStageData',
        'totalHay', 'usedHay',
        'userCount', 'fertilizerCount', 'machineCount','cropCount',
        'sitesForSelector',
        'targetSiteName',
        'displayMessage',
        'onlineUsersCount',
        'offlineUsersCount',
        'movingUsersCount',
        'notMovingUsersCount',
        'gpsOnUsersCount',
        'gpsOffUsersCount',
        'notifications', // Pass the notifications to the view
        'site',
        'labels',
        'data',
        'totalRainfall',
        'avgRainfall',
        'weekStatuses',
        'sowingProgressJson' // <--- NEW DATA VARIABLE
    ));
}
public function getTotalDieselPurchased($siteId = null)
{
    $siteId = $siteId ?? Auth::user()->site_id;

    // Fetch all diesel stock history for the site
    $dieselHistory = DB::table('diesel_stock_history')
        ->where('site_id', $siteId)
        ->get();

    // Sum total purchased (added_quantity)
    $totalPurchased = $dieselHistory->sum('added_quantity');

    // Calculate total cost if needed
    $totalPurchasedCost = $dieselHistory->sum(function ($entry) {
        return $entry->added_quantity * $entry->rate_per_liter;
    });

    return [
        'total_purchased_liters' => $totalPurchased,
        'total_purchased_cost'   => $totalPurchasedCost,
    ];
}
    
    public function storeRainfall(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'rainfallDate' => 'required|date',
            'rainfallAmount' => 'required|numeric|min:0'
        ]);
    
        DB::table('weather_logs')->insert([
            'date'       => $request->rainfallDate,
            'precipitation'   => $request->rainfallAmount,
            'site_id'   =>  $user->site_id,
            'source'     => 'manual',   // ✅ mark manual entries
            'created_at' => now(),
            'recorded_at' =>  now(),
            'updated_at' => now(),
        ]);
    
        return redirect()->back()->with('success', 'Rainfall data added successfully!');
    }
    
    
    
   


    
}

    