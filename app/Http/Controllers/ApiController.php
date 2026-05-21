<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class ApiController extends Controller
{
    /**
     * Get weather forecast for given latitude and longitude.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForecast(Request $request)
     {
         $lat = $request->lat ?? '26.8682246';
         $lon = $request->lon ?? '80.9933125';
         $apiKey = 'b41274328f52dcb04a9f6aff4c48c85a';

         $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

         $response = Http::get($url);

         if ($response->successful()) {
             return response()->json([
                 'status' => true,
                 'data' => $response->json(),
             ]);
         } else {
             return response()->json([
                 'status' => false,
                 'message' => 'Failed to fetch weather data',
             ], $response->status());
         }
     }
    //login api

    /**
     * Login user and return token if credentials are valid.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
{
    $validatedData = $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6',
    ]);

    $user = User::where('email', $validatedData['email'])->first();

    if (!$user || !Hash::check($validatedData['password'], $user->password)) {
        return response()->json(["error" => "Invalid email or password"], 401);
    }

    // ✅ Role check (only role = 4 can login)
    if ($user->role != 4) {
        return response()->json(["error" => "You are not authorized to login."], 403);
    }

    // Update last_activity_at on successful login
    $user->last_activity_at = Carbon::now();
    $user->last_login = Carbon::now();
    $user->save();
    // Create authentication token
    $token = $user->createToken('authToken')->plainTextToken;

    return response()->json([
        "message" => "Login successful",
        "user" => $user,
        "token" => $token,
        "token_type" => "Bearer"
    ]);
}

    /**
     * Get authenticated user details.
     * This endpoint should be protected by 'auth:sanctum' middleware.
     */
    public function getUser(Request $request)
    {
        return response()->json(["user" => $request->user()]);
    }

    /**
     * Log out the authenticated user.
     * This endpoint should be protected by 'auth:sanctum' middleware.
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            // Optionally set last_activity_at to null or an old timestamp on logout
            $user = $request->user();
            $user->last_activity_at = null; // Mark as explicitly offline
            $user->gps_status = false; // Mark GPS off on logout
            $user->is_moving = false;  // Mark not moving on logout
            $user->save();

            // Revoke the user's current access token
            $request->user()->tokens()->delete();
            return response()->json(["message" => "User logged out successfully"], 200);
        }

        return response()->json(["error" => "User not authenticated"], 401);
    }

    /**
     * Mobile app sends a heartbeat to update user's general activity time.
     * This endpoint should be protected by 'auth:sanctum' middleware.
     */
    /**
     * Update user's last activity timestamp (heartbeat from mobile app).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActivity(Request $request)
    {
        if (!$request->user()) {
            return response()->json(["error" => "Unauthenticated."], 401);
        }

        $user = $request->user();
        $user->last_activity_at = Carbon::now(); // Update with current timestamp
        $user->save();

        return response()->json(["message" => "General activity timestamp updated successfully."], 200);
    }

    
    public function getSiteBlocksPlots(Request $request)
    {
        $userId = auth()->id();

        // Get the current user's email and role
        $currentUser = DB::table('users')
            ->select('email', 'role')
            ->where('id', $userId)
            ->first();

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get user plots including IDs
        $userPlots = DB::table('user_plots as up')
            ->join('blocks as b', 'up.block_id', '=', 'b.id')
            ->join('master_plots as mp', 'up.plot_id', '=', 'mp.id')
            ->join('users as u', 'up.user_id', '=', 'u.id')
            ->select(
                'b.id as block_id',
                'b.block_name',
                'mp.id as plot_id',
                'mp.plot_name',
                'mp.area'
            )
            ->where('u.id', $userId)
            ->where('u.role', 4)
            ->get();

        if ($userPlots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No plot data found for this user'
            ], 404);
        }

        // Group the data by block_id
        $grouped = [];
        foreach ($userPlots as $plot) {
            $blockId = $plot->block_id;

            if (!isset($grouped[$blockId])) {
                $grouped[$blockId] = [
                'block_id'   => $blockId,
                'block_name' => $plot->block_name,
                'plots'      => []
            ];
        }

        $grouped[$blockId]['plots'][] = [
            'plot_id'   => $plot->plot_id,
            'plot_name' => $plot->plot_name,
            'area'      => $plot->area
        ];
    }

    return response()->json([
        'success' => true,
        'data' => array_values($grouped)
    ]);
}


    //get trackter name
    /**
     * Get tractor names for the current user's site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTractorNames(Request $request)
    {
         $userId = auth()->id();
     $currentUser = DB::table('users')
            ->select('site_id')
            ->where('id', $userId)
            ->first();
        $tractorNames = DB::table('master_tractors')
         ->select('id','tractor_name', 'tractor_type')
        ->where('is_deleted', '0')
        ->where('site_id', $currentUser->site_id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $tractorNames
        ], 200);
    }

    /**
     * Get machine names for the current user's site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMachineNames(Request $request)
    {

     $userId = auth()->id();
     $currentUser = DB::table('users')
            ->select('site_id')
            ->where('id', $userId)
            ->first();
        $machines = DB::table('master_machine')
                    ->select('id','machine_name', 'machine_no')
                    ->where('site_id', $currentUser->site_id)
                    ->get();
                    // ->map(function($machine) {
                    //     return $machine->machine_name . ' (' . $machine->machine_no . ')';
                    // });

        return response()->json([
            'status' => 'success',
            'data' => $machines
        ], 200);
    }



    /**
     * Get manpower categories grouped by type for the current user's site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMasterManpowerCategories(Request $request)
{
    $userId = auth()->id();

    $currentUser = DB::table('users')
        ->select('site_id')
        ->where('id', $userId)
        ->first();

    if (!$currentUser) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    $records = DB::table('manpower_type as t')
        ->join('master_manpower as m', 't.id', '=', 'm.type')
        ->select(
            'm.type as type_id',
            't.type as type_name',
            'm.id as category_id',
            'm.category',
            'm.no_of_person'
        )
        ->where('m.site_id', $currentUser->site_id)
        ->get();

    // Group by type
    $grouped = $records->groupBy('type_id')->map(function ($items) {
        return [
            'type_id'   => $items->first()->type_id,
            'type_name' => $items->first()->type_name,
            'categories' => $items->map(function ($item) {
                return [
                    'category_id'   => $item->category_id,
                    'category_name' => $item->category,
                    'no_of_person'  => $item->no_of_person,
                ];
            })->values()
        ];
    })->values();

    return response()->json([
        'status'  => 'success',
        'site_id' => $currentUser->site_id,
        'data'    => $grouped
    ], 200);
}

// Alternative: If you want to make site_id required
    /**
     * Get manpower categories for a specific site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMasterManpowerCategoriesBySite(Request $request)
{
    $request->validate([
        'site_id' => 'required|integer'
    ]);

    $categories = DB::table('master_manpower')
        ->where('site_id', $request->site_id)
        ->distinct()
        ->pluck('category');

    return response()->json([
        'status' => 'success',
        'data' => $categories,
        'site_id' => $request->site_id
    ], 200);
}

// If you want to get categories with additional info
    /**
     * Get detailed manpower categories for a site, including count.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMasterManpowerCategoriesDetailed(Request $request)
{
    $query = DB::table('master_manpower')
        ->select('category', 'site_id', DB::raw('COUNT(*) as count'));

    if ($request->has('site_id') && $request->site_id) {
        $query->where('site_id', $request->site_id);
    }

    $categories = $query->groupBy('category', 'site_id')->get();

    return response()->json([
        'status' => 'success',
        'data' => $categories,
        'site_id' => $request->site_id ?? 'all'
    ], 200);
}

//Area Leveling


//master_irrigation_types
    /**
     * Get all master irrigation types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function master_irrigation_types()
    {
        $irrigationTypes = DB::table('master_irrigation_types')->get();

        return response()->json([
            'status' => 'success',
            'data' => $irrigationTypes
        ], 200);
    }


    //master_water_source

    /**
     * Get all water sources.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function master_water_source(Request $request)
    {
        
         $userId = auth()->id();
         $currentUser = DB::table('users')
                ->select('site_id')
                ->where('id', $userId)
                ->first();
        // Get only the data columns (excluding action buttons)
        $watersource = DB::table('water_sources')
                    ->select([
                        'id',
                        DB::raw("CONCAT(borewell_no, ' (', name, ')') as name")
                    ])
                    ->where('site_id', $currentUser->site_id)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $watersource
        ], 200);
    }

    //master_capacity
    /**
     * Get all water source capacities (lph).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function master_capacity(Request $request)
    {
          $userId = auth()->id();
         $currentUser = DB::table('users')
                ->select('site_id')
                ->where('id', $userId)
                ->first();
        $master_capacity = DB::table('water_sources')
        ->select(['id','capacity_lph'])
         ->where('site_id', $currentUser->site_id)
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $master_capacity
        ], 200);
    }


    /**
     * Store area leveling activity and update diesel stock FIFO.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAreaLeveling(Request $request)
{
    // 1. Validation rules
    $validator = Validator::make($request->all(), [
        'block_name' => 'required|string|max:255',
        'plot_name' => 'required|string|max:255',
        'machine_ids' => 'nullable|array',
        //'machine_ids.*' => 'string|max:20',
        'tractor_ids' => 'nullable|array',
        'tractor_ids.*' => 'string|max:20',
        'area' => 'required|numeric|min:0',
        'area_leveling' => 'nullable|string|max:255',
        'hsd_consumption' => 'nullable|numeric|min:0',
        'time_hrs' => 'nullable|numeric|min:0',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
        'manpower_categories' => 'nullable|array',
        'date' => 'required|date',
        'manpower_type_id' => 'nullable',

        'spare_parts' => 'nullable|array',
        'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
        'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
    }

    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteName = $user->site_id;
        $userId = $user->id;

        if (!$siteName) {
            return response()->json([
                'status' => 'error',
                'message' => 'Site name not found for this user.'
            ], 404);
        }


        // used hours
         $hours_used = null;
        if ($request->filled('start_time') && $request->filled('end_time') && $request->filled('date')) {
            $dateStr = Carbon::parse($request->date)->format('Y-m-d');
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $request->start_time);
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $request->end_time);

            if ($endDateTime->lessThan($startDateTime)) {
                $endDateTime->addDay();
            }

            $hours_used = round($startDateTime->diffInSeconds($endDateTime) / 3600, 2);
        } elseif ($request->filled('time_hrs')) {
            $hours_used = $request->time_hrs;
        }

     // Fetch manpower categories by IDs
    $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

    $manpowerCategories = DB::table('master_manpower')
        ->whereIn('id', $categoryIds)
        ->get()
        ->keyBy('id'); // index by id

    $manpowerCosts = [];
    $manpowerTotal = 0;
    $manpower_categories = null;
    // Initialize default columns (for DB fields)
    $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
     if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
         $manpower_categories = json_encode($request->manpower_categories);
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];
                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
     }

  
            // Diesel FIFO logic
            $dieselCost = 0;
            $dieselRate = 0;
            $litersTaken = 0;
            $remainingConsumption = $request->input('hsd_consumption', 0);
            $consumptionDetails = [];

          $requestedConsumption = $request->input('hsd_consumption', 0);

            if ($requestedConsumption > 0) {
            // Check total available stock first
            $availableStock = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
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

            // ✅ Now safe to update (wrap in transaction)
            DB::beginTransaction();
            try {
                $dieselCost = 0;
                $dieselRate = 0;
                $remainingConsumption = $requestedConsumption;
                $consumptionDetails = [];

                $stocks = DB::table('diesel_stocks')
                    ->where('site_id', $user->site_id)
                    ->where('diesel_stock', '>', 0)
                    ->orderBy('date_of_purchase', 'asc')
                    ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

                foreach ($stocks as $stock) {
                    if ($remainingConsumption <= 0) break;

                    $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                    $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

                foreach ($consumptionDetails as $detail) {
                    DB::table('diesel_consumption')->insert([
                        'stock_id'   => $detail['stock_id'],
                        'liters_used'=> $detail['liters_used'],
                        'rate'       => $detail['rate'],
                        'cost'       => $detail['cost'],
                        'date'       => $request->date,
                        'activity'   => 'Area Leveling',
                        'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                        'user_id'    => $userId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    DB::table('diesel_stock_history')->insert([
                        'diesel_stock_id'   => $detail['stock_id'],
                        'site_id'   => $detail['site_id'],
                        'type'   => 'Consumption',
                        'note'   => 'Area Leveling',
                        'date_of_entry' => $request->date,
                        'stock_before_addition'=> $detail['previous_stock'],
                        'consumed_quantity'=> $detail['liters_used'],
                        'rate_per_liter'       => $detail['rate'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'error' => 'SERVER_ERROR',
                    'message' => $e->getMessage()
                ], 500);
            }
            }


        // Spare parts logic
        $majorMaintenanceData = null;
        $majorCost = 0;
        $processedSpareParts = [];

        if ($request->has('spare_parts') && is_array($request->spare_parts)) {
            $sparePartsInput = $request->spare_parts;
            $majorMaintenanceData = json_encode($sparePartsInput);

            foreach ($sparePartsInput as $part) {
                if (isset($part['value']) && is_numeric($part['value'])) {
                    $majorCost += (float)$part['value'];
                }
                if (isset($part['spare_part']) && isset($part['value'])) {
                    $processedSpareParts[] = [
                        'item' => $part['spare_part'],
                        'cost' => (float)$part['value']
                    ];
                }
            }
        }

        $totalCost = $manpowerTotal + $dieselCost + $majorCost;
        
        $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
            
        // Insert into area_levelings
        $recordId = DB::table('area_levelings')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'area' => $request->area,
            'area_leveling' => $request->area_leveling,
            'hsd_consumption' => $request->input('hsd_consumption'),
            'hsd_cost' => $dieselCost,
            'time_hrs' => $request->time_hrs,
            'date' => $request->date,
            'start_time' => $request->filled('start_time') ? Carbon::parse($request->date . ' ' . $request->start_time)->format('Y-m-d H:i:s') : null,
            'end_time' => $request->filled('end_time') ? Carbon::parse($request->date . ' ' . $request->end_time)->format('Y-m-d H:i:s') : null,
            'hours_used' => $hours_used,
            'category_id' => $manpower_categories,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceData,
            'major_cost' => $majorCost,
            'user_id' => $userId,
            'site_id' => $siteName,
            'total_cost' => $totalCost,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Area leveling record created successfully',
            'insert_id' => $recordId,
            'total_cost' => number_format($totalCost, 2),
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'area' => $request->area,
            'hours_used' => $hours_used,
            'cost_breakdown' => [
                'manpower' => [
                    // 'unskilled' => [
                    //     'quantity' => $request->input('unskilled', 0),
                    //     'rate' => number_format($rates['unskilled'], 2),
                    //     'cost' => number_format($manpowerCosts['unskilled'], 2)
                    // ],
                    // 'semi_skilled_1' => [
                    //     'quantity' => $request->input('semi_skilled_1', 0),
                    //     'rate' => number_format($rates['semi_skilled_1'], 2),
                    //     'cost' => number_format($manpowerCosts['semi_skilled_1'], 2)
                    // ],
                    // 'semi_skilled_2' => [
                    //     'quantity' => $request->input('semi_skilled_2', 0),
                    //     'rate' => number_format($rates['semi_skilled_2'], 2),
                    //     'cost' => number_format($manpowerCosts['semi_skilled_2'], 2)
                    // ],
                    'total' => number_format($manpowerTotal, 2)
                ],
                'diesel' => [
                    'consumption' => $request->input('hsd_consumption', 0),
                    'rate_per_liter' => number_format($dieselRate, 2),
                    'cost' => number_format($dieselCost, 2),
                    'consumption_details' => $consumptionDetails,
                    'method' => 'FIFO'
                ],
                'major_maintenance' => [
                    'details' => $processedSpareParts,
                    'total_cost' => number_format($majorCost, 2)
                ]
            ],
            'machine_ids' => $request->machine_ids,
            'tractor_ids' => $request->tractor_ids,
            'manpower_rates_used' => $manpowerCategories->map(function ($item) {
                return [
                    'id' => $item->id,
                    'category' => $item->category,
                    'type' => $item->type,
                    'rate' => number_format($item->rate, 2)
                ];
            })->toArray(),
            'timestamp' => now()->toDateTimeString()
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error("Area Leveling Validation Error: " . $e->getMessage() . "\n" . json_encode($e->errors()));
        return response()->json([
            'status' => 'error',
            'error' => 'VALIDATION_ERROR',
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
            'timestamp' => now()->toDateTimeString()
        ], 422);
    } catch (\Exception $e) {
        Log::error("Area Leveling Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'message' => 'Failed to create area leveling record: ' . $e->getMessage(),
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }
}

    //pre land preparation

    /**
     * Store pre-land preparation activity and update diesel stock FIFO.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePreLandPreparation(Request $request)
{
    $request->validate([
        'block_name' => 'required|string',
        'plot_name' => 'required|string',
        'machine_ids' => 'nullable|array',
        'machine_ids.*' => 'string|max:20',
        'tractor_ids' => 'nullable|array',
        'tractor_ids.*' => 'string|max:20',
        'area' => 'required|numeric',
        'area_covered' => 'nullable|string',
        'hsd_consumption' => 'nullable|numeric',
        'time_hrs' => 'nullable|numeric',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
        'manpower_categories' => 'nullable|array',
        //'category.*' => 'string',
        'date' => 'required|date',
        'manpower_type_id' => 'nullable|integer',
        'major_maintenance' => 'nullable|string|max:65535',
        'spare_parts' => 'nullable|array',
        'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
        'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    try {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.'
            ], 401);
        }

        $siteName = $user->site_id;
        $userId = $user->id;

        if (!$siteName) {
            return response()->json([
                'status' => 'error',
                'message' => 'Site name not found for this user.'
            ], 404);
        }

         $hours_used = null;
        if ($request->filled('start_time') && $request->filled('end_time') && $request->filled('date')) {
            $dateStr = Carbon::parse($request->date)->format('Y-m-d');
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $request->start_time);
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $request->end_time);

            if ($endDateTime->lessThan($startDateTime)) {
                $endDateTime->addDay();
            }

            $hours_used = round($startDateTime->diffInSeconds($endDateTime) / 3600, 2);
        } elseif ($request->filled('time_hrs')) {
            $hours_used = $request->time_hrs;
        }

         // Fetch manpower categories by IDs
    $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

    $manpowerCategories = DB::table('master_manpower')
        ->whereIn('id', $categoryIds)
        ->get()
        ->keyBy('id'); // index by id

    $manpowerCosts = [];
    $manpowerTotal = 0;

    // Initialize default columns (for DB fields)
    $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
     if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
    foreach ($request->manpower_categories as $cat) {
        $id = $cat['category_id'];
        $noOfPerson = (int) $cat['no_of_person'];

        if (isset($manpowerCategories[$id])) {
            $row = $manpowerCategories[$id];
            $rate = $row->rate ?? 0;
            $cost = $rate * $noOfPerson;

            $manpowerCosts[] = [
                'category_id'   => $id,
                'category_name' => $row->category,
                'rate'          => $rate,
                'no_of_person'  => $noOfPerson,
                'cost'          => $cost
            ];

            $manpowerTotal += $cost;

            // Map into table columns
            if ($row->category === 'Unskilled') {
                $unskilled = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 1') {
                $semiSkilled1 = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 2') {
                $semiSkilled2 = $noOfPerson;
            }
        }
    }
    }

         // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Pre Land Preparation',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Pre Land Preparation',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }

        // Spare parts & maintenance cost
        $majorMaintenanceContent = null;
        $majorCostValue = null;
        $processedSpareParts = [];

        if ($request->has('spare_parts') && is_array($request->spare_parts) && !empty($request->spare_parts)) {
            $sparePartsInput = $request->spare_parts;
            $majorMaintenanceContent = json_encode($sparePartsInput);
            $currentMajorCost = 0;
            foreach ($sparePartsInput as $part) {
                if (isset($part['value']) && is_numeric($part['value'])) {
                    $currentMajorCost += (float)$part['value'];
                }
                if (isset($part['spare_part']) && isset($part['value'])) {
                    $processedSpareParts[] = [
                        'item' => $part['spare_part'],
                        'cost' => number_format((float)$part['value'], 2)
                    ];
                }
            }

            $majorCostValue = $currentMajorCost;
        } elseif ($request->filled('major_maintenance')) {
            $majorMaintenanceContent = $request->input('major_maintenance');
            if ($majorMaintenanceContent !== null && strtolower($majorMaintenanceContent) !== 'none' && $majorMaintenanceContent !== "") {
                $processedSpareParts[] = ['item' => $majorMaintenanceContent, 'cost' => 'N/A'];
            }
        }

        $totalCost = $manpowerTotal + $dieselCost + ($majorCostValue ?? 0);

         $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
        
        $insertId = DB::table('pre_land_preparation')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'site_id' => $siteName,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'hsd_consumption' => $request->input('hsd_consumption'),
            'time_hrs' => $hours_used,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours_used' => $hours_used,
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'user_id' => $userId,
            'total_cost' => $totalCost,
             'season_id' => $season ? $season->id : null, 
              'manual_season' => $request->manual_season,
              'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pre Land Preparation record created successfully',
            'insert_id' => $insertId,
            'total_cost' => number_format($totalCost, 2),
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'area' => $request->area,
            'hours_used' => $hours_used,
            'cost_breakdown' => [
                'manpower' => [

                    'total' => number_format($manpowerTotal, 2)
                ],
                'diesel' => [
                    'consumption' => $request->input('hsd_consumption', 0),
                    'rate_per_liter' => number_format($dieselRate, 2),
                    'cost' => number_format($dieselCost, 2),
                    'consumption_details' => $consumptionDetails,
                    'method' => 'FIFO'
                ],
                'major_maintenance' => [
                    'details_provided' => $majorMaintenanceContent,
                    'calculated_cost' => $majorCostValue !== null ? number_format($majorCostValue, 2) : 'N/A',
                    'breakdown' => $processedSpareParts
                ]
            ],
            'machine_ids' => $request->machine_ids,
            'tractor_ids' => $request->tractor_ids,
            'manpower_rates_used' => $manpowerCategories->isNotEmpty() ? $manpowerCategories->map(function ($item) {
                return [
                    'id' => $item->id,
                    'category' => $item->category,
                    'type' => $item->type,
                    'rate' => number_format($item->rate, 2)
                ];
            })->toArray() : [],
            'timestamp' => now()->toDateTimeString()
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error("Pre Land Preparation Validation Error: " . $e->getMessage() . "\n" . json_encode($e->errors()));
        return response()->json([
            'status' => 'error',
            'error' => 'VALIDATION_ERROR',
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
            'timestamp' => now()->toDateTimeString()
        ], 422);
    } catch (\Exception $e) {
        Log::error("Pre Land Preparation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        return response()->json([
            'status' => 'error',
            'error' => 'SERVER_ERROR',
            'message' => 'Failed to create pre land preparation record',
            'system_message' => $e->getMessage(),
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }
}

//pre irrigation

    public function storePreIrrigation(Request $request)
{
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteName = $user->site_id;
        $userId = $user->id;

        if (!$siteName) {
            return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);
        }

        // add water_source_id in validation
        $validated = $request->validate([
            'block_name' => 'required|string',
            'plot_name' => 'required|string',
            'area_acre' => 'required|numeric|min:0',
            'area_covered' => 'required|numeric|min:0',
            'irrigation_no' => 'required|string',
            'irrigation_type_id' => 'required|integer|exists:master_irrigation_types,id',
            'irrigation_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'water_source_id' => 'required|integer|exists:water_sources,id', 
            'capacity_id' => 'required|integer|exists:water_sources,id',
            'manpower_categories' => 'nullable|array',
            //'category.*' => 'string',
            'manpower_type_id' => 'nullable|integer',
            'major_maintenance' => 'nullable|string',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.spare_part' => 'nullable|string',
            'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
            'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
            'crop_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();



        // Time calculation
        $startMinutes = (explode(':', $request->start_time)[0] * 60) + explode(':', $request->start_time)[1];
        $endMinutes = (explode(':', $request->end_time)[0] * 60) + explode(':', $request->end_time)[1];
        if ($endMinutes < $startMinutes) $endMinutes += 1440;
        $hoursUsed = round(($endMinutes - $startMinutes) / 60, 2);

        $references = DB::table('master_irrigation_types')->find($request->irrigation_type_id);
        if (!$references) throw new \Exception('Irrigation type data not found');

        // Fetch both water_source and capacity
        $waterSource = DB::table('water_sources')->find($request->water_source_id);
        if (!$waterSource) throw new \Exception('Water source data not found');

        $capacityDetails = DB::table('water_sources')->find($request->capacity_id);
        if (!$capacityDetails) throw new \Exception('Capacity details not found');

        $capacityLph = $capacityDetails->capacity_lph;
        $waterUsed = $capacityLph * $hoursUsed;

        $powerConsumptionKw = $capacityDetails->power_consumption_kw;
        $electricityUnits = $powerConsumptionKw * $hoursUsed;
        $costPerUnit = $request->cost_per_unit ?? $waterSource->cost_per_unit ?? 0;
        $electricityCost = $electricityUnits * $costPerUnit;

    // Fetch manpower categories by IDs
    $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

    $manpowerCategories = DB::table('master_manpower')
        ->whereIn('id', $categoryIds)
        ->get()
        ->keyBy('id'); // index by id

    $manpowerCosts = [];
    $manpowerTotal = 0;

    // Initialize default columns (for DB fields)
    $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
     if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
    foreach ($request->manpower_categories as $cat) {
        $id = $cat['category_id'];
        $noOfPerson = (int) $cat['no_of_person'];

        if (isset($manpowerCategories[$id])) {
            $row = $manpowerCategories[$id];
            $rate = $row->rate ?? 0;
            $cost = $rate * $noOfPerson;

            $manpowerCosts[] = [
                'category_id'   => $id,
                'category_name' => $row->category,
                'rate'          => $rate,
                'no_of_person'  => $noOfPerson,
                'cost'          => $cost
            ];

            $manpowerTotal += $cost;

            // Map into table columns
            if ($row->category === 'Unskilled') {
                $unskilled = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 1') {
                $semiSkilled1 = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 2') {
                $semiSkilled2 = $noOfPerson;
            }
        }
    }
    }
     // Spare parts & maintenance cost
        $majorMaintenanceContent = null;
        $majorCostValue = null;
        $processedSpareParts = [];

        if ($request->has('spare_parts') && is_array($request->spare_parts) && !empty($request->spare_parts)) {
            $sparePartsInput = $request->spare_parts;
            $majorMaintenanceContent = json_encode($sparePartsInput);
            $currentMajorCost = 0;
            foreach ($sparePartsInput as $part) {
                if (isset($part['value']) && is_numeric($part['value'])) {
                    $currentMajorCost += (float)$part['value'];
                }
                if (isset($part['spare_part']) && isset($part['value'])) {
                    $processedSpareParts[] = [
                        'item' => $part['spare_part'],
                        'cost' => number_format((float)$part['value'], 2)
                    ];
                }
            }

            $majorCostValue = $currentMajorCost;
        } elseif ($request->filled('major_maintenance')) {
            $majorMaintenanceContent = $request->input('major_maintenance');
            if ($majorMaintenanceContent !== null && strtolower($majorMaintenanceContent) !== 'none' && $majorMaintenanceContent !== "") {
                $processedSpareParts[] = ['item' => $majorMaintenanceContent, 'cost' => 'N/A'];
            }
        }

        $totalCost = round($manpowerTotal + $electricityCost + ($majorCostValue ?? 0));
     
     $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();

        // Insert with separate water_source_id and capacity_id
        $irrigationId = DB::table('pre_irrigation')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'area' => $request->area_acre,
            'area_covered' => $request->area_covered,
            'irrigation_no' => $request->irrigation_no,
            'irrigation_type_id' => $request->irrigation_type_id,
            'date' => $request->irrigation_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours_used' => $hoursUsed,
            'water_source_id' => $request->water_source_id,
            'capacity_id' => $request->capacity_id,
            'capacity_lph' => $capacityLph,
            'water_used' => $waterUsed,
            'power_consumption_kw' => $powerConsumptionKw,
            'electricity_units' => $electricityUnits,
            'cost_per_unit' => $costPerUnit,
            'electricity_cost' => $electricityCost,
            'manpower_category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'total_cost' => $totalCost,
            'user_id' => $userId,
            'site_id' => $siteName,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Irrigation record created successfully',
            'data' => [
                'id' => $irrigationId,
                'block_name' => $request->block_name,
                'plot_name' => $request->plot_name,
                'area_acre' => $request->area_acre,
                'irrigation_type' => $references->type_name,
                'water_source' => $waterSource->name,
                'capacity' => $capacityLph,
                'water_used' => $waterUsed,
                'cost_breakdown' => [
                    'manpower' => [
                        'total' => $manpowerTotal
                    ],
                    'electricity' => [
                        'units' => $electricityUnits,
                        'rate' => $costPerUnit,
                        'cost' => $electricityCost
                    ],
                    'major_maintenance' => $processedSpareParts,
                    'total_cost' => $manpowerTotal
                ],
                'timestamp' => now()->toDateTimeString()
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error' => 'Failed to create irrigation record',
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null
        ], 500);
    }
}


//soil condition
    /**
     * Get all master soil conditions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSoilCondition(Request $request)
{
    // Get only the data columns (excluding action buttons)
    $watersource = DB::table('master_soil_condition')
                    ->select(['id','soil_condition'])
                    ->get();

    return response()->json([
        'status' => 'success',
        'data' => $watersource
    ], 200);
}
    //land preparation

    /**
     * Store land preparation activity and update diesel stock FIFO.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function LandPreparation(Request $request)
{
    // Validation rules
    $request->validate([
        'block_name' => 'required|string',
        'plot_name' => 'required|string',
        'machine_ids' => 'nullable|array',
        'machine_ids.*' => 'string|max:20',
        'tractor_ids' => 'nullable|array',
        'tractor_ids.*' => 'string|max:20',
        'area' => 'required|numeric',
        'area_covered' => 'nullable|string',
        'hsd_consumption' => 'nullable|numeric|min:0',
        'time_hrs' => 'nullable|numeric',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i',
        'soil_condition_id' => 'required|integer|exists:master_soil_condition,id',
        'manpower_categories' => 'nullable|array', // changed from required
        'date' => 'required|date',
        'manpower_type_id' => 'nullable|integer',
        'unskilled' => 'nullable|integer|min:0',
        'semi_skilled_1' => 'nullable|integer|min:0',
        'semi_skilled_2' => 'nullable|integer|min:0',
        'spare_parts' => 'nullable|array',
        'user_id' => 'sometimes|required|integer',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    try {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteName = $user->site_id;
        $userId = $user->id;

        if (!$siteName) {
            return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);
        }

        DB::beginTransaction();

        // Calculate hours used
        $hours_used = $request->time_hrs;
        if ($request->start_time && $request->end_time) {
            $start = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->start_time);
            $end = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->end_time);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $hours_used = round($start->floatDiffInHours($end), 2);
        }

        // Manpower defaults
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id

        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
        }

        // --- rest of your code remains unchanged (spare parts, diesel, etc.) ---

        // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Land Preparation',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Land Preparation',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }
          // Spare parts & maintenance cost
        $majorMaintenanceContent = null;
        $majorCostValue = null;
        $processedSpareParts = [];

        if ($request->has('spare_parts') && is_array($request->spare_parts) && !empty($request->spare_parts)) {
            $sparePartsInput = $request->spare_parts;
            $majorMaintenanceContent = json_encode($sparePartsInput);
            $currentMajorCost = 0;
            foreach ($sparePartsInput as $part) {
                if (isset($part['value']) && is_numeric($part['value'])) {
                    $currentMajorCost += (float)$part['value'];
                }
                if (isset($part['spare_part']) && isset($part['value'])) {
                    $processedSpareParts[] = [
                        'item' => $part['spare_part'],
                        'cost' => number_format((float)$part['value'], 2)
                    ];
                }
            }

            $majorCostValue = $currentMajorCost;
        } elseif ($request->filled('major_maintenance')) {
            $majorMaintenanceContent = $request->input('major_maintenance');
            if ($majorMaintenanceContent !== null && strtolower($majorMaintenanceContent) !== 'none' && $majorMaintenanceContent !== "") {
                $processedSpareParts[] = ['item' => $majorMaintenanceContent, 'cost' => 'N/A'];
            }
        }

        $totalCost = $manpowerTotal + $dieselCost + ($majorCostValue ?? 0);

         
        $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
        // Insert into land preparation
        $insertId = DB::table('land_prepration')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'site_id' => $siteName,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'soil_condition_id' => $request->soil_condition_id,
            'hsd_consumption' => $request->hsd_consumption,
            'time_hrs' => $hours_used,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours_used' => $hours_used,
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent ?? null,
            'user_id' => $userId,
            'total_cost' => $totalCost,
            'season_id' => $season ? $season->id : null,
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $soilCondition = DB::table('master_soil_condition')
            ->where('id', $request->soil_condition_id)
            ->value('soil_condition');

        DB::commit();

        return response()->json([
            'message' => 'Land Preparation record inserted successfully',
            'insert_id' => $insertId,
            'soil_condition' => $soilCondition,
            'total_cost' => number_format($totalCost, 2),
            'time_data' => [
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_used' => $hours_used
            ],
            'cost_breakdown' => [
                'manpower' => array_merge($manpowerCosts, ['total' => number_format($manpowerTotal, 2)]),
                'diesel' => $request->hsd_consumption ? [
                    'consumption' => $request->hsd_consumption,
                    'rate_per_liter' => number_format($dieselRate, 2),
                    'cost' => number_format($dieselCost, 2),
                    'consumption_details' => $consumptionDetails,
                    'method' => 'FIFO'
                ] : null
            ],
            'manpower_rates_used' => $manpowerCategories->map(function ($item) {
                return [
                    'id' => $item->id,
                    'category' => $item->category,
                    'type' => $item->type,
                    'rate' => number_format($item->rate, 2)
                ];
            })
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to create land preparation record',
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null
        ], 500);
    }
}

 
    public function getSeedVarieties(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated.'
        ], 401);
    }

    // Get seeds for user's site and only non-deleted ones
    $seeds = DB::table('master_seed')
            ->leftJoin('seed as s', 'master_seed.seed_id', '=', 's.id')
            ->leftJoin('master_veriety as mv', 'master_seed.seed_variety_id', '=', 'mv.id')
            ->where('master_seed.site_id', $user->site_id)       // Only seeds from user's site
            ->where('master_seed.is_deleted', 0)   
            ->where('s.site_id',$user->site_id)// Exclude deleted seeds
            ->select('master_seed.id as seed_id','s.name as seed_name','mv.id as seed_variety', 'mv.variety_name as variety_of_seed')
            ->orderByRaw("FIELD(seed_name, 'Wheat') DESC")
            ->orderBy('seed_name')
            ->get();

    return response()->json([
        'status' => 'success',
        'data' => $seeds
    ]);
}


    //Sowing Method
    /**
     * Get all sowing methods for the user's site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSowingMethods(Request $request)
{
    $user = Auth::user();
    $sowingMethods = DB::table('sowing_method')
        ->select('id', 'name as sowing_method')
       // ->where('site_id', $user->site_id)
       // ->whereNotNull('sowing_method')  // Exclude null values
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $sowingMethods
    ]);
}

    /**
     * Get all purposes from master seed table.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPurpose(Request $request)
{
    $purposes = DB::table('master_seed')
        ->distinct()
        ->whereNotNull('purpose')
        ->pluck('purpose');

    return response()->json([
        'status' => 'success',
        'data' => $purposes
    ]);
}
//sowing source
    /**
     * Get all sowing sources from master seed table.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getswoing_source(Request $request)
{
    $purposes = DB::table('master_seed')
        ->distinct()
        ->whereNotNull('sowing_source')  // Exclude null values
        ->pluck('sowing_source');

    return response()->json([
        'status' => 'success',
        'data' => $purposes
    ]);
}
    //sowing opration

    public function storeSowingOperation(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
    }

    $siteName = $user->site_id;
    $userId = $user->id;
    if (!$siteName) {
        return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);
    }

    $request->validate([
        'block_name' => 'required|string',
        'plot_name' => 'required|string',
        'seed_name' => 'required|string',
        'variety_of_seed' => 'nullable|string',
        'sowing_method' => 'nullable',
        'sowing_date' => 'required|date',
        'seed_consumption' => 'nullable|numeric',
        'purpose_name' => 'nullable|string',
        'sowing_source_id' => 'nullable|string',
        'row_distance' => 'nullable|numeric',
        'sowing_depth' => 'nullable|numeric',
        'machine_ids' => 'nullable|array',
        'machine_ids.*' => 'string|max:20',
        'tractor_ids' => 'nullable|array',
        'tractor_ids.*' => 'string|max:20',
        'area' => 'nullable|numeric',
        'seed_id' => 'required|integer',
        'area_covered' => 'nullable|numeric',
        'hsd_consumption' => 'nullable|numeric',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i',
        'manpower_categories' => 'nullable|array',
        'manpower_type_id' => 'nullable|integer',
        'major_maintenance' => 'nullable|string',
        'spare_parts' => 'nullable|array',
        'user_id' => 'sometimes|integer',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    try {
        DB::beginTransaction();

     // 👉 Validation: Agar crop cycle close hai to nayi sowing entry reject karo
    $existingCycle = DB::table('crop_cycles')
        ->where('block', $request->block_name)
        ->where('plot', $request->plot_name)
        ->where('site_id', $siteName)
        ->orderBy('id', 'desc')
        ->first();

//   if ($existingCycle && $existingCycle->status === 'Closed') {
//     return response()->json([
//         'status' => 'error',
//         'message' => 'This plot/block crop cycle is already closed on ' .
//                      (!empty($existingCycle->closed_date) ? date('d-m-Y', strtotime($existingCycle->closed_date)) : 'Unknown date') .
//                      '. Please create new activity after this date.'
//     ], 400);
// }


       // ===== Seed Consumption (FIFO, immutable Add Stock rows) =====
$seedId = $request->seed_id;
$requestedSeedConsumption = (float) ($request->seed_consumption ?? 0);
$seedCost = 0.0;
$seedConsumptionDetails = [];

if ($requestedSeedConsumption <= 0) {
    return response()->json([
        'status' => 'error',
        'error' => 'INVALID_QTY',
        'message' => 'Consumption quantity must be greater than 0.'
    ], 400);
}

// Master stock check
$AvailableSeed = DB::table('master_seed')->where('id', $seedId)->first();
$totalAvailableSeed = (float) ($AvailableSeed->seed_stock_kg ?? 0);

if ($totalAvailableSeed < $requestedSeedConsumption) {
    return response()->json([
        'status' => 'error',
        'error' => 'INSUFFICIENT_SEED',
        'message' => 'Not enough seed stock available.',
        'requested_qty' => $requestedSeedConsumption,
        'available_qty' => $totalAvailableSeed,
        'shortage' => $requestedSeedConsumption - $totalAvailableSeed
    ], 400);
}

$remainingSeedConsumption = $requestedSeedConsumption;

// ✅ Get Add Stock batches with remaining stock calculation (FIFO order)
$seedStocks = DB::table('seed_stock_history as ssh')
    ->select(
        'ssh.*',
        DB::raw('COALESCE((
            SELECT SUM(consumed.seed_stock_kg)
            FROM seed_stock_history as consumed
            WHERE consumed.consumed_from_stock_id = ssh.id
            AND consumed.type = "Consumption"
        ), 0) as total_consumed'),
        DB::raw('(ssh.seed_stock_kg - COALESCE((
            SELECT SUM(consumed.seed_stock_kg)
            FROM seed_stock_history as consumed
            WHERE consumed.consumed_from_stock_id = ssh.id
            AND consumed.type = "Consumption"
        ), 0)) as remaining_stock')
    )
    ->where('ssh.seed_id', $seedId)
    ->where('ssh.type', 'Add Stock')
    ->havingRaw('remaining_stock > 0')  // ✅ Only batches with remaining stock
    ->orderBy('ssh.date_of_packing', 'asc')
    ->orderBy('ssh.id', 'asc')
    ->get();

// ✅ Check if we have enough stock in batches
$totalRemainingInBatches = $seedStocks->sum('remaining_stock');
if ($totalRemainingInBatches < $requestedSeedConsumption) {
    return response()->json([
        'status' => 'error',
        'error' => 'STOCK_MISMATCH',
        'message' => 'Master stock and batch stock mismatch. Please verify stock records.',
        'master_stock' => $totalAvailableSeed,
        'batch_stock' => $totalRemainingInBatches
    ], 400);
}

// ✅ FIFO Consumption Logic
foreach ($seedStocks as $stock) {
    if ($remainingSeedConsumption <= 0) break;

    $availableInThisBatch = (float) $stock->remaining_stock;
    if ($availableInThisBatch <= 0) continue;

    // ✅ Take minimum of (available in batch, remaining to consume)
    $qtyTaken = min($availableInThisBatch, $remainingSeedConsumption);
    $cost = $qtyTaken * (float) $stock->rate_of_seed;

    // ✅ Insert Consumption row (linking to Add Stock batch)
    DB::table('seed_stock_history')->insert([
        'seed_id'               => $stock->seed_id,
        'variety_id'            => $stock->variety_id,
        'type_of_packing'       => $stock->type_of_packing,
        'packing_size'          => $stock->packing_size,
        'date_of_packing'       => $stock->date_of_packing,
        'seed_stock_kg'         => $qtyTaken,   // consumed qty from this batch
        'rate_of_seed'          => $stock->rate_of_seed,  // price from this batch
        'type'                  => "Consumption",
        'uom'                   => $stock->uom,
        'site_id'               => $stock->site_id,
        'consumed_from_stock_id'=> $stock->id,   //link to Add Stock batch
        'related_add_stock_id'  => $stock->id,   // Optional: another reference field
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);

    $seedConsumptionDetails[] = [
        'stock_id'        => $stock->id,
        'batch_date'      => $stock->date_of_packing,
        'qty_used'        => $qtyTaken,
        'rate'            => (float) $stock->rate_of_seed,
        'cost'            => $cost,
        'remaining_before'=> $availableInThisBatch,
        'remaining_after' => $availableInThisBatch - $qtyTaken,
    ];

    $seedCost += $cost;
    $remainingSeedConsumption -= $qtyTaken;
    }

    // ✅ Update master stock
    DB::table('master_seed')
        ->where('id', $seedId)
        ->update([
            'seed_stock_kg' => $totalAvailableSeed - $requestedSeedConsumption,
    
        ]);

        $purposeMap = ['Fodder Crop' => 1, 'Cash Crop' => 2, 'Seed Crop' => 3];
        $purposeName = $request->purpose_name ?? $seedInfo->purpose ?? 'unknown';
        $purposeId = $purposeMap[$purposeName] ?? null;

        $categoryIds = [];

        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];
                $manpowerTotal += $cost;
            }
             if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }

        $majorMaintenanceContent = null;
        $majorCostValue = null;
        $processedSpareParts = [];

        if (!empty($request->spare_parts) && is_array($request->spare_parts)) {
            $majorMaintenanceContent = json_encode($request->spare_parts);
            $majorCostValue = 0;

            foreach ($request->spare_parts as $part) {
                if (isset($part['value']) && is_numeric($part['value'])) {
                    $majorCostValue += (float)$part['value'];
                }
                if (isset($part['spare_part'], $part['value'])) {
                    $processedSpareParts[] = [
                        'item' => $part['spare_part'],
                        'cost' => number_format((float)$part['value'], 2)
                    ];
                }
            }
        } elseif (!empty($request->major_maintenance)) {
            $majorMaintenanceContent = $request->major_maintenance;
            if (strtolower($majorMaintenanceContent) !== 'none') {
                $processedSpareParts[] = ['item' => $majorMaintenanceContent, 'cost' => 'N/A'];
            }
        }

        // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->sowing_date,
                    'activity'   => 'Sowing',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Sowing',
                    'date_of_entry' => $request->sowing_date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }

        $totalCost = $manpowerTotal + $dieselCost + $seedCost;

        $hours_used = null;
        if ($request->start_time && $request->end_time) {
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->sowing_date . ' ' . $request->start_time);
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->sowing_date . ' ' . $request->end_time);

            if ($endDateTime->lessThan($startDateTime)) {
                $endDateTime->addDay();
            }

            $hours_used = round($startDateTime->floatDiffInHours($endDateTime), 2);
        } else {
            $hours_used = $request->time_hrs;
        }
         
        $entryDate = Carbon::parse($request->sowing_date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
        $sowingMethod = $request->sowing_method;
        $insertId = DB::table('showing_oprations')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'seed_id' => $seedId,
            'variety' => $request->variety_of_seed,
            'sowing_method_id' => $request->sowing_method,
            'date' => $request->sowing_date,
            'seed_consumption' => $request->seed_consumption,
            'purpose_name' => $request->purpose_name,
            'swowing_source_id' => $request->sowing_source_id,
            'row_distance' => $request->row_distance,
            'sowing_depth' => $request->sowing_depth,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'hsd_consumption' => $request->hsd_consumption,
            'time_hrs' => $hours_used,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours_used' => $hours_used,
            'site_id' => $siteName,
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'user_id' => $userId,
            'total_cost' => $totalCost,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
       
        DB::table('crop_cycles')->insert([
            'block' => $request->block_name,
            'plot' => $request->plot_name,
            'seed_id' => $seedId,
            'sowing_id' => $insertId,
            'start_date' => $request->sowing_date,
            'user_id' => $userId,
            'site_id' => $siteName,
            'status' => 'Active'
        ]);

        DB::commit();
        $response = [
            'message' => 'Sowing operation record created successfully',
            'insert_id' => $insertId,
            'total_cost' => number_format($totalCost, 2),
            //'updated_seed_stock' => $seedInfo->seed_stock_kg - ($request->seed_consumption ?? 0),
            'cost_breakdown' => [
                'manpower' => [

                    'total' => number_format($manpowerTotal, 2)
                ],
                    'seed' => [
                'consumption' => $requestedSeedConsumption,
               // 'unit' => $seedUnitInfo,
                'total_cost' => number_format($seedCost, 2),
                'method' => 'FIFO',
                'consumption_details' => $seedConsumptionDetails
            ],
            ],

            'time_data' => [
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_used' => $hours_used
            ]
        ];

        if ($request->hsd_consumption) {
            $response['cost_breakdown']['diesel'] = [
                'consumption' => $request->hsd_consumption,
                'rate_per_liter' => number_format($dieselRate, 2),
                'cost' => number_format($dieselCost, 2),
                'consumption_details' => $consumptionDetails,
                'method' => 'FIFO'
            ];
        }

        return response()->json($response, 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  $e->getMessage(),
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null
        ], 500);
    }
}

   //post irrigation
    public function postIrrigation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteId = $user->site_id;
        $userId = $user->id;

        if (!$siteId) {
            return response()->json(['status' => 'error', 'message' => 'Site ID not found for this user.'], 404);
        }

        $validated = $request->validate([
            'block_name' => 'required|string',
            'plot_name' => 'required|string',
            'area_acre' => 'required|numeric|min:0',
            'area_covered' => 'required|numeric|min:0',
            'irrigation_no' => 'required|numeric|string',
            'irrigation_type_id' => 'required|integer|exists:master_irrigation_types,id',
            'irrigation_date' => 'required|date',
            'day_ofter_swowing' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'water_source_id' => 'required|integer|exists:water_sources,id',
            'capacity_id' => 'required|integer|exists:water_sources,id',
            'manpower_categories' => 'nullable|array',
            //'category.*' => 'string',
            'manpower_type_id' => 'nullable|integer',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
            'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'user_id' => 'required|integer|exists:users,id',
            'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
            'crop_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

        // Time calculation
        $startMinutes = (explode(':', $request->start_time)[0] * 60) + explode(':', $request->start_time)[1];
        $endMinutes = (explode(':', $request->end_time)[0] * 60) + explode(':', $request->end_time)[1];
        if ($endMinutes < $startMinutes) $endMinutes += 1440;
        $hoursUsed = round(($endMinutes - $startMinutes) / 60, 2);

             // ✅ Fetch both water_source and capacity
        $waterSource = DB::table('water_sources')->find($request->water_source_id);
        if (!$waterSource) throw new \Exception('Water source data not found');

        $capacityDetails = DB::table('water_sources')->find($request->capacity_id);
        if (!$capacityDetails) throw new \Exception('Capacity details not found');

        $capacityLph = $capacityDetails->capacity_lph;
        $waterUsed = $capacityLph * $hoursUsed;

        $powerConsumptionKw = $capacityDetails->power_consumption_kw;
        $electricityUnits = $powerConsumptionKw * $hoursUsed;
        $costPerUnit = $request->cost_per_unit ?? $waterSource->cost_per_unit ?? 0;
        $electricityCost = $electricityUnits * $costPerUnit;

        $manpowerCategoryIds = [];
          if (!empty($request->category) && is_array($request->category)) {
                    foreach ($request->category as $categoryName) {
                        $category = DB::table('master_manpower')
                            ->where('category', $categoryName)
                            ->first();

                        if (!$category) {
                            throw new \Exception("Manpower category '$categoryName' not found");
                        }

                        $manpowerCategoryIds[] = $category->id;
                    }
                }

            $startTimeParts = explode(':', $request->start_time);
            $endTimeParts = explode(':', $request->end_time);
            $startMinutes = ($startTimeParts[0] * 60) + $startTimeParts[1];
            $endMinutes = ($endTimeParts[0] * 60) + $endTimeParts[1];

            if ($endMinutes < $startMinutes) {
                $endMinutes += 24 * 60;
            }

            $minutesUsed = $endMinutes - $startMinutes;
            $hoursUsed = round($minutesUsed / 60, 2);

            $references = DB::table('master_irrigation_types')
                ->where('id', $request->irrigation_type_id)
                ->first();
            if (!$references) {
                throw new \Exception('Irrigation type data not found');
            }

            $waterSource = DB::table('water_sources')
                ->where('id', $request->water_source_id)
                ->first();
            if (!$waterSource) {
                throw new \Exception('Water source data not found');
            }

            $capacityDetails = DB::table('water_sources')
                ->where('id', $request->capacity_id)
                ->first();
            if (!$capacityDetails) {
                throw new \Exception('Capacity details not found');
            }
        //Handle manpower
         $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'skilled') {
                    $skilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
         }


            $majorMaintenanceData = null;
            $majorCost = 0;
            $processedSpareParts = [];

            if ($request->has('spare_parts') && is_array($request->spare_parts)) {
                $sparePartsInput = $request->spare_parts;
                $majorMaintenanceData = json_encode($sparePartsInput);

                foreach ($sparePartsInput as $part) {
                    if (isset($part['value']) && is_numeric($part['value'])) {
                        $majorCost += (float)$part['value'];
                    }
                    if (isset($part['spare_part']) && isset($part['value'])) {
                        $processedSpareParts[] = [
                            'item' => $part['spare_part'],
                            'cost' => (float)$part['value']
                        ];
                    }
                }
            }

           // $totalManpowerCost = array_sum($manpowerCosts);
            $totalCost = round($manpowerTotal + $electricityCost + $majorCost);
           $manpowerCategoryIdsString = implode(',', $categoryIds);
           
            $entryDate = Carbon::parse($request->irrigation_date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteId)
            ->first();

            $irrigationId = DB::table('post_irrigation')->insertGetId([
                'block_name' => $request->block_name,
                'plot_name' => $request->plot_name,
                'area' => $request->area_acre,
                'area_covered' => $request->area_covered,
                'irrigation_no' => $request->irrigation_no,
                'irrigation_type_id' => $request->irrigation_type_id,
                'date' => $request->irrigation_date,
                'day_ofter_swowing' => $request->day_ofter_swowing,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_used' => $hoursUsed,
                'water_source_id' => $request->water_source_id,
                'capacity_id' => $request->capacity_id,
                'capacity_lph' => $capacityLph,
                'water_used' => $waterUsed,
                'power_consumption_kw' => $powerConsumptionKw,
                'electricity_units' => $electricityUnits,
                'cost_per_unit' => $costPerUnit,
                'electricity_cost' => $electricityCost,
                'manpower_category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
                'manpower_type' => $request->manpower_type_id,
                'unskilled' => $unskilled,
                'semi_skilled_1' => $semiSkilled1,
                'semi_skilled_2' => $semiSkilled2,
                'major_maintenance' => $majorMaintenanceData,
                'major_cost' => $majorCost,
                'user_id' => $userId,
                'site_id' => $siteId,
                'total_cost' => $totalCost,
                'season_id' => $season ? $season->id : null, 
                'manual_season' => $request->manual_season,
                'crop_id' => $request->crop_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post Irrigation record created successfully',
                'data' => [
                    'id' => $irrigationId,
                    'block_name' => $request->block_name,
                    'plot_name' => $request->plot_name,
                    'area_acre' => $request->area_acre,
                    'irrigation_type' => $references->type_name,
                    'water_source' => $waterSource->name,
                    'capacity' => $capacityLph,
                    'water_used' => $waterUsed,
                    'cost_breakdown' => [
                        'manpower' => [

                            'total' => $manpowerTotal
                        ],
                        'electricity' => [
                            'units' => $electricityUnits,
                            'rate' => $costPerUnit,
                            'cost' => $electricityCost
                        ],
                        'major_maintenance_cost' => $majorCost,
                        'major_maintenance_items' => $processedSpareParts,
                        'total_cost' => $totalCost
                    ],
                    'timestamp' => now()->toDateTimeString()
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

//master fotilizer name
   
    public function masetr_fatilizer(Request $request)
{
    $userId = auth()->id();

    // Get current user site_id
    $currentUser = DB::table('users')
        ->select('site_id')
        ->where('id', $userId)
        ->first();

    if (!$currentUser) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }
 
    // Fetch fertilizers for that site
    $fertilizers = DB::table('master_fertilizer as ms')
        ->leftJoin('fertilizers as frt', 'ms.fertilizer_id', '=', 'frt.id')
        ->where('ms.site_id', $currentUser->site_id)
        ->select('ms.id', 'frt.fertilizer_name', 'ms.fertilizer_type', 'ms.uom')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $fertilizers
    ], 200);
}

//fatilizer soil

   
    public function postFertilizer(Request $request)
{
    DB::beginTransaction();
    try {
        $user = auth()->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);

        $siteName = $user->site_id;
        $userId = $user->id;
        if (!$siteName) return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);

        $validated = $request->validate([
            'block_name' => 'nullable|string',
            'plot_name' => 'nullable|string',
            'area' => 'required|numeric|min:0',
            'area_covered' => 'required|string',
            'fertilizer_ids' => 'required|array|min:1',
            'fertilizer_ids.*.id' => 'required|integer|exists:master_fertilizer,id',
            'fertilizer_ids.*.quantity' => 'required|numeric|min:0',
            'activity_type' => 'required|string',
            // 'uom' => 'required|string',
            'machine_ids' => 'nullable|array',
            'tractor_ids' => 'nullable|array',
            'hsd_consumption' => 'nullable|numeric|min:0',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'category' => 'nullable|array',
            'manpower_type_id' => 'nullable|integer',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
            'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
            'date' => 'required|date',
            'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
            'crop_id' => 'nullable|integer',
        ]);

        $fertilizers = $request->input('fertilizer_ids');
        $totalQuantity = 0;

        $userData = DB::table('users')->where('id', $userId)->select('block_name', 'plot_name')->first();
        $blockName = $request->block_name ?? $userData->block_name;
        $plotName = $request->plot_name ?? $userData->plot_name;
        if (!$blockName || !$plotName) return response()->json(['error' => 'Block name and plot name are required.'], 422);

        [$sh, $sm] = explode(':', $request->start_time);
        [$eh, $em] = explode(':', $request->end_time);
        $startMinutes = ($sh * 60) + $sm;
        $endMinutes = ($eh * 60) + $em;
        if ($endMinutes < $startMinutes) $endMinutes += 1440;
        $hoursUsed = round(($endMinutes - $startMinutes) / 60, 2);

        //Handle manpower
        $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();
         $manpowerCategoryIdsString = implode(',', $categoryIds);
        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
         }

        // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Fertilizers',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Fertilizers',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }

        // Spare parts
        $majorCostValue = 0;
        $majorMaintenanceContent = null;
        if ($request->has('spare_parts') && is_array($request->spare_parts)) {
            foreach ($request->spare_parts as $part) {
                $majorCostValue += (float) ($part['value'] ?? 0);
            }
            $majorMaintenanceContent = json_encode($request->spare_parts);
        } elseif ($request->filled('major_maintenance')) {
            $majorMaintenanceContent = $request->major_maintenance;
        }


       // Fertilizer cost calculation and stock management
            $fertilizerCost = 0;
            $processeDetails = [];
            $individualFertilizerCost = 0;
            $chemicalIdsForDb = [];
            $dosesForDb = [];

            $uomsForDb = [];
            $companiesForDb = [];

            foreach ($request->fertilizer_ids as $ferti) {
                $fertilizer = DB::table('master_fertilizer')
                ->leftJoin('fertilizers as cm', 'master_fertilizer.fertilizer_id', '=', 'cm.id')
                ->where('master_fertilizer.id', $ferti['id'])->first();

                if (!$fertilizer) {
                    DB::rollBack();
                    return response()->json(['error' => "Selected Fertilizer with ID {$ferti['id']} not found"], 404);
                }

                if ($fertilizer->stock_kg < $ferti['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Insufficient stock for fertilizer: {$fertilizer->fertilizer_name} (ID: {$ferti['id']})",
                        'fertilizer_id' => $ferti['id'],
                        'fertilizer_name' => $fertilizer->fertilizer_name,
                      //  'available_stock' => $ferti->stock_qty,
                        'requested_dose' => $ferti['quantity']
                    ], 422);
                }

                // FIFO stock deduction from chemical_stock_history
                $requiredQty = $ferti['quantity'];
                $remainingQty = $requiredQty;

                $batches = DB::table('fertilizer_stock_history')
                    ->where('fertilizer_id', $ferti['id'])
                    ->where('site_id', $user->site_id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $individualFertilizerCost = 0;

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                   $takeQty = min($batch->remaining_quantity, $remainingQty);
                    Log::info("Deducting", [
                        'takeQty' => $takeQty,
                        'batch_id' => $batch->id
                    ]);
                    if ($takeQty <= 0) continue; // extra safety
                    $batchCost = $takeQty * $batch->rate;

                    // Deduct from stock batch
                    DB::table('fertilizer_stock_history')
                        ->where('id', $batch->id)
                        ->update([
                            'remaining_quantity' => $batch->remaining_quantity - $takeQty,
                            'updated_at' => now(),
                        ]);

                    // Insert into consumption table
                    DB::table('fertilizer_consumption')->insert([
                        'fertilizer_id' => $ferti['id'],
                        'stock_id' => $batch->id,
                        'consumed_quantity' => $takeQty,
                        'rate' => $batch->rate,
                        'cost' => $batchCost,
                        'date' => $request->date,
                        'site_id' => $user->site_id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $individualFertilizerCost += $batchCost;
                    $remainingQty -= $takeQty;
                }

                $newTotal = DB::table('fertilizer_stock_history')
                    ->where('fertilizer_id', $ferti['id'])
                    ->where('site_id', $user->site_id)
                    ->sum('remaining_quantity');

                $newTotal = max(0, $newTotal); // negative ko zero kar do

                DB::table('master_fertilizer')
                    ->where('id', $ferti['id'])
                    ->update([
                        'stock_kg' => $newTotal,
                    ]);

                $fertilizerCost += $individualFertilizerCost;

                $fertilizerDetails[] = [
                    'fertilizer_id' => $ferti['id'],
                    'fertilzer_name' => $fertilizer->fertilizer_name ?? 'N/A',
                    'active_ingredients' =>  $fertilizer->active_ingredients ?? null,
                    'dose' => $ferti['quantity'],
                    'rate' => number_format($individualFertilizerCost / $ferti['quantity'], 2),
                    'cost' => number_format($individualFertilizerCost, 2),
                    'initial_stock' => number_format($fertilizer->stock_kg, 2)
                ];

                $chemicalIdsForDb[] = $ferti['id'];
                $dosesForDb[] = $ferti['quantity'];
                $uomsForDb[] = $ferti['uom'];
               // $companiesForDb[] = $ferti['company'];
            }
            
        $totalCost = round($fertilizerCost + $manpowerTotal + $dieselCost + $majorCostValue, 2);
        
        $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();

        $fertilizerRecordId = DB::table('fertilizer_soil_record')->insertGetId([
            'block_name' => $blockName,
            'plot_name' => $plotName,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'fertilizer_id' => json_encode($request->fertilizer_ids),
            'activity_type' => $request->activity_type,
            'fertilizer_quantity' => $totalQuantity,
            'uom' => $request->uom,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'hsd_consumption' => $request->hsd_consumption,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours_used' => $hoursUsed,
            // 'category_id' => implode(',', $request->category),
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'total_cost' => $totalCost,
            'user_id' => $userId,
            'site_id' => $siteName,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Fertilizer record created successfully',
            'data' => [
                'id' => $fertilizerRecordId,
                'fertilizer_details' => $fertilizerDetails,
                'cost_breakdown' => [
                    'fertilizer' => $fertilizerCost,
                    'manpower' => array_merge($manpowerCosts, ['total' => $manpowerTotal]),
                    'diesel' => $request->hsd_consumption ? [
                        'consumption' => $request->hsd_consumption,
                        'rate_per_liter' => number_format($dieselRate, 2),
                        'cost' => number_format($dieselCost, 2),
                        'consumption_details' => $consumptionDetails,
                        'method' => 'FIFO'
                    ] : null,
                    'major_cost' => $majorCostValue,
                    'total_cost' => $totalCost
                ],
                'time_data' => [
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'hours_used' => $hoursUsed
                ]
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            //'error' => 'Failed to create fertilizer record',
            'error' => $e->getMessage(),
            'message' => $e->getMessage()
        ], 500);
    }
}


//inter culture

    /**
     * Store inter-culture activity and update diesel stock FIFO.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeInterCulture(Request $request)
{
    DB::beginTransaction();
    try {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteName = $user->site_id;
        $userId = $user->id;
        if (!$siteName) {
            return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);
        }

        $request->validate([
            'block_name' => 'required|string',
            'plot_name' => 'required|string',
            'machine_ids' => 'nullable|array',
            'tractor_ids' => 'nullable|array',
            'area' => 'required|numeric',
            'area_covered' => 'nullable|string',
            'hsd_consumption' => 'nullable|numeric',
            'time_hrs' => 'nullable|numeric',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'manpower_categories' => 'nullable|array', // ✅ Changed from 'required|array' to 'nullable|array'
            'date' => 'required|date',
            'manpower_type_id' => 'nullable|integer',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
            'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
            'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
            'crop_id' => 'nullable|integer',
        ]);

        //Handle manpower
        $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();
         $manpowerCategoryIdsString = implode(',', $categoryIds);
        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
         }

         // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Inter Culture',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Inter Culture',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }
        // Spare parts handling
        $majorCostValue = 0;
        $majorMaintenanceContent = null;

        if ($request->has('spare_parts') && is_array($request->spare_parts)) {
            foreach ($request->spare_parts as $part) {
                $majorCostValue += (float) ($part['value'] ?? 0);
            }
            $majorMaintenanceContent = json_encode($request->spare_parts);
        } elseif ($request->filled('major_maintenance')) {
            $majorMaintenanceContent = $request->major_maintenance;
        }

        $totalCost = $manpowerTotal + $dieselCost + $majorCostValue;
        $categoryIds = $manpowerCategories->isNotEmpty() ? $manpowerCategories->pluck('id')->implode(',') : null;
        
        $entryDate = Carbon::parse($request->date)->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
                
        $insertId = DB::table('inter_culture')->insertGetId([
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'machine_id' => $request->machine_ids ? implode(',', $request->machine_ids) : null,
            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : null,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'hsd_consumption' => $request->hsd_consumption,
            'time_hrs' => $request->time_hrs,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $request->manpower_type_id,
            'unskilled' => $unskilled,
            'semi_skilled_1' => $semiSkilled1,
            'semi_skilled_2' => $semiSkilled2,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'user_id' => $userId,
            'site_id' => $siteName,
            'total_cost' => $totalCost,
            'activity_type' => $request->activity_type,
            'date' => $request->date,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Inter Culture record inserted successfully',
            'insert_id' => $insertId,
            'total_cost' => number_format($totalCost, 2),
            'cost_breakdown' => [
                'manpower' => [

                    'total' => number_format($manpowerTotal, 2)
                ],
                'diesel' => [
                    'consumption' => $request->hsd_consumption ?? 0,
                    'rate_per_liter' => number_format($dieselRate, 2),
                    'cost' => number_format($dieselCost, 2)
                ]
            ],
            'manpower_rates_used' => $manpowerCategories->map(function ($item) {
                return [
                    'id' => $item->id,
                    'category' => $item->category,
                    'type' => $item->type,
                    'rate' => number_format($item->rate, 2)
                ];
            })
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to create Inter Culture record',
            'message' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Store crop protection activity and update chemical/diesel stock FIFO.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCropProtection(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteIdToStore = $user->site_id;

        // 1. Validation rules
        $validator = Validator::make($request->all(), [
            'block_name' => 'required|string',
            'plot_name' => 'required|string',
            'area' => 'required|numeric',
            'area_covered' => 'nullable|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'manpower_categories' => 'nullable|array',
            'date' => 'required|date',
            'manpower_type_id' => 'nullable|integer',
            'major_maintenance' => 'nullable|string', // Kept for existing structure, though spare_parts is preferred
            'application_method' => 'required|string',
            'application_stage_source' => 'required|string',
            'chemical_ids' => 'required|array|min:1',
            'chemical_ids.*.id' => 'required|integer|exists:master_chemical,id',
            'chemical_ids.*.dose' => 'required|numeric|min:0',
            'chemical_ids.*.uom' => 'required|string',
            'chemical_ids.*.company' => 'required|string',
            'user_id' => 'nullable|integer|exists:users,id',
            // Validation rules for machine, tractor, and HSD consumption (HSD related removed as requested)
            'machine_ids' => 'nullable|array',
            'machine_ids.*' => 'string|max:20',
            'tractor_ids' => 'nullable|array',
            'tractor_ids.*' => 'string|max:20',
            'hsd_consumption' => 'nullable|numeric|min:0', // Keeping hsd_consumption validation if it's passed but not saved/calculated
            'spare_parts' => 'nullable|array', // Validation for spare_parts (major maintenance)
            'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
            'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
            'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
            'crop_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $userIdToStore = $user->id;
            // Logic for admin/manager role to specify user_id
            if (in_array($user->role, ['admin', 'manager'])) {
                if ($request->has('user_id') && $request->user_id !== null) {
                    $userIdToStore = $request->user_id;
                } else {
                    DB::rollBack();
                    return response()->json(['error' => 'For admin/manager roles, user_id is required in the request body.'], 422);
                }
            }


        //Handle manpower
          // Fetch manpower categories by IDs
    $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();

    $manpowerCategories = DB::table('master_manpower')
        ->whereIn('id', $categoryIds)
        ->get()
        ->keyBy('id'); // index by id

    $manpowerCosts = [];
    $manpowerTotal = 0;

    // Initialize default columns (for DB fields)
    $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
     if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
    foreach ($request->manpower_categories as $cat) {
        $id = $cat['category_id'];
        $noOfPerson = (int) $cat['no_of_person'];

        if (isset($manpowerCategories[$id])) {
            $row = $manpowerCategories[$id];
            $rate = $row->rate ?? 0;
            $cost = $rate * $noOfPerson;

            $manpowerCosts[] = [
                'category_id'   => $id,
                'category_name' => $row->category,
                'rate'          => $rate,
                'no_of_person'  => $noOfPerson,
                'cost'          => $cost
            ];

            $manpowerTotal += $cost;

            // Map into table columns
            if ($row->category === 'Unskilled') {

                $unskilled = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 1') {

                $semiSkilled1 = $noOfPerson;
            } elseif ($row->category === 'Semi Skilled 2') {

                $semiSkilled2 = $noOfPerson;
            }
        }
    }
     }
  
       // Chemical cost calculation and stock management
            $totalChemicalCostOverall = 0;
            $processedChemicalsDetails = [];

            $chemicalIdsForDb = [];
            $dosesForDb = [];
            $uomsForDb = [];
            $companiesForDb = [];

            foreach ($request->chemical_ids as $chemicalData) {
                $chemical = DB::table('master_chemical')
                ->leftJoin('chemical_master as cm', 'master_chemical.chemical_id', '=', 'cm.id')
                ->where('master_chemical.id', $chemicalData['id'])->first();

                if (!$chemical) {
                    DB::rollBack();
                    return response()->json(['error' => "Selected chemical with ID {$chemicalData['id']} not found"], 404);
                }

                if ($chemical->stock_qty < $chemicalData['dose']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Insufficient stock for chemical: {$chemical->chemical_name} (ID: {$chemicalData['id']})",
                        'chemical_id' => $chemicalData['id'],
                        'chemical_name' => $chemical->chemical_name,
                        'available_stock' => $chemical->stock_qty,
                        'requested_dose' => $chemicalData['dose']
                    ], 422);
                }

                // FIFO stock deduction from chemical_stock_history
                $requiredQty = $chemicalData['dose'];
                $remainingQty = $requiredQty;

                $batches = DB::table('chemical_stock_history')
                    ->where('chemical_id', $chemicalData['id'])
                    ->where('site_id', $user->site_id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $individualChemicalCost = 0;

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                   $takeQty = min($batch->remaining_quantity, $remainingQty);
                    Log::info("Deducting", [
                        'takeQty' => $takeQty,
                        'batch_id' => $batch->id
                    ]);
                    if ($takeQty <= 0) continue; // extra safety
                    $batchCost = $takeQty * $batch->rate;

                    // Deduct from stock batch
                    DB::table('chemical_stock_history')
                        ->where('id', $batch->id)
                        ->update([
                            'remaining_quantity' => $batch->remaining_quantity - $takeQty,
                            'updated_at' => now(),
                        ]);

                    // Insert into consumption table
                    DB::table('chemical_consumption')->insert([
                        'chemical_id' => $chemicalData['id'],
                        'stock_id' => $batch->id,
                        'consumed_quantity' => $takeQty,
                        'rate' => $batch->rate,
                        'cost' => $batchCost,
                        'date' => $request->date,
                        'site_id' => $user->site_id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $individualChemicalCost += $batchCost;
                    $remainingQty -= $takeQty;
                }

                $newTotal = DB::table('chemical_stock_history')
                    ->where('chemical_id', $chemicalData['id'])
                    ->where('site_id', $user->site_id)
                    ->sum('remaining_quantity');
                Log::info("After batches", [
                    'newTotal' => $newTotal,
                    'chemical_id' => $chemicalData['id']
                ]);
                $newTotal = max(0, $newTotal); // negative ko zero kar do

                DB::table('master_chemical')
                    ->where('id', $chemicalData['id'])
                    ->update([
                        'stock_qty' => $newTotal,
                    ]);

                $totalChemicalCostOverall += $individualChemicalCost;

                $processedChemicalsDetails[] = [
                    'chemical_id' => $chemicalData['id'],
                    'chemical_name' => $chemical->chemical_name ?? 'N/A',
                    'company' => $chemicalData['company'],
                    'active_ingredients' => $chemical->active_ingredients ?? null,
                    'dose' => $chemicalData['dose'],
                    'uom' => $chemicalData['uom'],
                    'rate' => number_format($individualChemicalCost / $chemicalData['dose'], 2),
                    'cost' => number_format($individualChemicalCost, 2),
                    'initial_stock' => number_format($chemical->stock_qty, 2)
                ];

                $chemicalIdsForDb[] = $chemicalData['id'];
                $dosesForDb[] = $chemicalData['dose'];
                $uomsForDb[] = $chemicalData['uom'];
                $companiesForDb[] = $chemicalData['company'];
            }


            $chemicalIdsString = implode(',', $chemicalIdsForDb);
            $dosesString = implode(',', $dosesForDb);
            $uomsString = implode(',', $uomsForDb);
            $companiesString = implode(',', $companiesForDb);

            // Calculate hours used
            $hours_used = null;
            if ($request->filled('start_time') && $request->filled('end_time') && $request->filled('date')) {
                try {
                    $date = Carbon::parse($request->date);
                    $startTimeString = $date->format('Y-m-d') . ' ' . $request->start_time;
                    $endTimeString = $date->format('Y-m-d') . ' ' . $request->end_time;

                    $startTimestamp = Carbon::createFromFormat('Y-m-d H:i', $startTimeString);
                    $endTimestamp = Carbon::createFromFormat('Y-m-d H:i', $endTimeString);

                    if ($endTimestamp->lt($startTimestamp)) {
                        $endTimestamp->addDay();
                    }

                    $hours_used = round($startTimestamp->diffInSeconds($endTimestamp) / 3600, 2);
                } catch (\Exception $e) {
                    Log::error("Error calculating hours_used in CropProtection: " . $e->getMessage());
                }
            }

            $hsdConsumptionValue = $request->input('hsd_consumption'); // Just get the value if present, not used for cost calculation

            // Spare parts logic (Major Maintenance)
            $majorMaintenanceData = null;
            $majorCost = 0;
            $processedSpareParts = [];

            if ($request->has('spare_parts') && is_array($request->spare_parts)) {
                $sparePartsInput = $request->spare_parts;
                $majorMaintenanceData = json_encode($sparePartsInput);

                foreach ($sparePartsInput as $part) {
                    if (isset($part['value']) && is_numeric($part['value'])) {
                        $majorCost += (float)$part['value'];
                    }
                    if (isset($part['spare_part']) && isset($part['value'])) {
                        $processedSpareParts[] = [
                            'item' => $part['spare_part'],
                            'cost' => (float)$part['value']
                        ];
                    }
                }
            } else if ($request->filled('major_maintenance')) {
                // If major_maintenance is a string, assume it's a direct cost
                $majorCost = (float)$request->major_maintenance;
                $majorMaintenanceData = json_encode(['description' => $request->major_maintenance, 'value' => $majorCost]);
            }
            // End of Spare parts logic

            // Implode machine and tractor IDs if they exist
            $machineIdsString = $request->has('machine_ids') && is_array($request->machine_ids) ? implode(',', $request->machine_ids) : null;
            $tractorIdsString = $request->has('tractor_ids') && is_array($request->tractor_ids) ? implode(',', $request->tractor_ids) : null;
             // Diesel FIFO logic
                $dieselCost = 0;
                $dieselRate = 0;
                $litersTaken = 0;
                $remainingConsumption = $request->input('hsd_consumption', 0);
                $consumptionDetails = [];

              $requestedConsumption = $request->input('hsd_consumption', 0);

                if ($requestedConsumption > 0) {
                // Check total available stock first
                $availableStock = DB::table('diesel_stocks')
                    ->where('site_id', $user->site_id)
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

                // ✅ Now safe to update (wrap in transaction)
                DB::beginTransaction();
                try {
                    $dieselCost = 0;
                    $dieselRate = 0;
                    $remainingConsumption = $requestedConsumption;
                    $consumptionDetails = [];

                    $stocks = DB::table('diesel_stocks')
                        ->where('site_id', $user->site_id)
                        ->where('diesel_stock', '>', 0)
                        ->orderBy('date_of_purchase', 'asc')
                        ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

                    foreach ($stocks as $stock) {
                        if ($remainingConsumption <= 0) break;

                        $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                        $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

                    foreach ($consumptionDetails as $detail) {
                        DB::table('diesel_consumption')->insert([
                            'stock_id'   => $detail['stock_id'],
                            'liters_used'=> $detail['liters_used'],
                            'rate'       => $detail['rate'],
                            'cost'       => $detail['cost'],
                            'date'       => $request->date,
                            'activity'   => 'Crop Protection',
                            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                            'user_id'    => $userId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        DB::table('diesel_stock_history')->insert([
                            'diesel_stock_id'   => $detail['stock_id'],
                            'site_id'   => $detail['site_id'],
                            'type'   => 'Consumption',
                            'note'   => 'Crop Protection',
                            'date_of_entry' => $request->date,
                            'stock_before_addition'=> $detail['previous_stock'],
                            'consumed_quantity'=> $detail['liters_used'],
                            'rate_per_liter'       => $detail['rate'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'error' => 'SERVER_ERROR',
                        'message' => $e->getMessage()
                    ], 500);
                }
                }


            // Total cost calculation including manpower, chemicals, and major maintenance (diesel cost removed)
            $totalCostForSingleRow = $manpowerTotal + $totalChemicalCostOverall + $majorCost + $dieselCost;
            
            $entryDate = Carbon::parse($request->date)->format('Y-m-d');
            // Find season by date range
            $season = DB::table('seasons')
                ->where('block_id', $request->block_name)
                ->where('plot_id', $request->plot_name)
                ->whereDate('start_date', '<=', $entryDate)
                ->whereDate('end_date', '>=', $entryDate)
                ->where('site_id', $siteIdToStore)
                ->first();

            // Insert into crop_protection table
            $insertId = DB::table('crop_protection')->insertGetId([
                'block_name' => $request->block_name,
                'plot_name' => $request->plot_name,
                'area' => $request->area,
                'area_covered' => $request->area_covered,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_used' => $hours_used,
                'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
                'manpower_type' => $request->manpower_type_id,
                'unskilled' => $unskilled,
                'semi_skilled_1' => $semiSkilled1,
                'semi_skilled_2' => $semiSkilled2,
                'major_maintenance' => $majorMaintenanceData,
                'major_cost' => $majorCost,
                'application_method' => $request->application_method,
                'application_stage_source' => $request->application_stage_source,
                'chemical_id' => $chemicalIdsString,
                'dose' => $dosesString,
                'uom' => $uomsString,
                'company' => $companiesString,
                'site_id' => $siteIdToStore,
                'user_id' => $userIdToStore,
                'machine_id' => $machineIdsString,
                'tractor_id' => $tractorIdsString,
                'hsd_consumption' => $hsdConsumptionValue, 
                'total_cost' => $totalCostForSingleRow,
                'season_id' => $season ? $season->id : null, 
                'manual_season' => $request->manual_season,
                'crop_id' => $request->crop_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // // Decrement chemical stock after successful insertion
            // foreach ($request->chemical_ids as $index => $chemicalData) {
            //     $chemical = DB::table('master_chemical')->find($chemicalData['id']);
            //     if ($chemical) {
            //         DB::table('master_chemical')
            //             ->where('id', $chemicalData['id'])
            //             ->decrement('stock_qty', $chemicalData['dose']);

            //         // Update remaining stock in the response details
            //         $updatedChemical = DB::table('master_chemical')->find($chemicalData['id']);
            //         $processedChemicalsDetails[$index]['remaining_stock'] = number_format($updatedChemical->stock_qty, 2);
            //     }
            // }

            DB::commit();

            // Return success response with detailed breakdown
            return response()->json([
                'message' => 'Crop Protection record inserted successfully.',
                'insert_id' => $insertId,
                'grand_total_operation_cost' => number_format($totalCostForSingleRow, 2),
                'cost_breakdown' => [
                    'manpower' => [

                        'total' => number_format($manpowerTotal, 2)
                    ],
                    'chemicals' => $processedChemicalsDetails,
                    'total_chemical_cost' => number_format($totalChemicalCostOverall, 2),
                    'application_stage_source' => $request->application_stage_source,
                    // Removed 'diesel' breakdown from here
                    'major_maintenance' => [
                        'details' => $processedSpareParts,
                        'total_cost' => number_format($majorCost, 2)
                    ],
                ],
                'machine_ids' => $request->machine_ids,
                'tractor_ids' => $request->tractor_ids,
                'hours_used' => $hours_used,
                'hsd_consumption' => $hsdConsumptionValue, // Include hsd_consumption in response if needed
                'manpower_rates_used' => $manpowerCategories->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'category' => $item->category,
                        'type' => $item->type,
                        'rate' => number_format($item->rate, 2)
                    ];
                })
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error("Crop Protection Validation Error: " . $e->getMessage() . "\n" . json_encode($e->errors()));
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Crop Protection Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ], 500);
        }
    }






    //storeActivityMonitoring

    /**
     * Store activity monitoring data, including image upload.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeActivityMonitoring(Request $request)
{
    $authUser = auth()->user();
    $siteIdToStore = $authUser->site_id;
    switch ($authUser->role) {
        case 'admin':
        case 'manager':
            if (!$request->has('user_id')) {
                return response()->json([
                    'error' => 'user_id is required for role: ' . $authUser->role
                ], 422);
            }
            $userIdToStore = $request->user_id;
            break;

        case 'field_user':
        default:
            $userIdToStore = $authUser->id;
            break;
    }

    $request->validate([
        'block_name' => 'nullable|string',
        'plot_name' => 'nullable|string',
        'area' => 'nullable|numeric',
        'area_covered' => 'nullable|string',
        'activity_stage' => 'nullable|string',
        'leaf_condition' => 'nullable|string',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'image_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'crop_id' => 'nullable|integer'
    ]);

    try {
        $imagePath = null;

        if ($request->hasFile('image_path')) {
            $image = $request->file('image_path');
            $filename = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('activity_monitoring'), $filename);
            $imagePath = 'activity_monitoring/' . $filename;
        }

        $data = [
            'block_name' => $request->block_name,
            'plot_name' => $request->plot_name,
            'area' => $request->area,
            'area_covered' => $request->area_covered,
            'activity_stage' => $request->activity_stage,
            'leaf_condition' => $request->leaf_condition,
            'manual_season' => $request->manual_season,
            'image_path' => $imagePath,
            'user_id' => $userIdToStore,
            'site_id' => $siteIdToStore,
            'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $id = DB::table('activity_monitoring')->insertGetId($data);

        $insertedData = DB::table('activity_monitoring')->where('id', $id)->first();

        return response()->json([
            'message' => 'Activity monitoring data stored successfully',
            'data' => $insertedData
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to store activity monitoring data',
            'message' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Check if sowing data exists for fodder crop for the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFodderCrop(Request $request)
{
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        // ✅ Get plot data ONLY for logged-in user
       $plots = DB::table('showing_oprations as so')
    ->leftJoin('master_seed as ms', 'so.seed_id', '=', 'ms.id')
    ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
    ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
    ->leftJoin('blocks as b', 'so.block_name', '=', 'b.id') // use block_id
    ->leftJoin('master_plots as mp', 'so.plot_name', '=', 'mp.id') // use plot_id
    ->select(
        'ms.id as seed_id',
        'b.id as block_id','b.block_name' ,'mp.id as plot_id','mp.plot_name',
        DB::raw('so.area as area'),
        DB::raw('CASE WHEN so.purpose_name = "Fodder Crop" THEN true ELSE false END as is_fodder_crop'),
        DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name")
    )
    ->where('so.user_id', $user->id)
    ->get();


        // If no data found, show message: sowing not done yet
        if ($plots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No sowing data found. Please complete sowing before performing harvest.'
            ], 404);
        }

        // Group plot data by block
        $grouped = [];
        foreach ($plots as $plot) {
            $blockName = $plot->block_name;
        if($blockName){
            if (!isset($grouped[$blockName])) {
                $grouped[$blockName] = [
                    'block_name' => $blockName,
                    'block_id' => $plot->block_id,
                    'plots' => []
                ];
            }

            $grouped[$blockName]['plots'][] = [
                'seed_id' => $plot->seed_id,
                'plot_id' => $plot->plot_id,
                'plot_name' => $plot->plot_name,
                'area' => $plot->area,
                'is_fodder_crop' => $plot->is_fodder_crop,
                'seed_name' => $plot->seed_name
            ];
        }
}
        // 1. Get all distinct seed names for A + A1 (example logic)
        $seedData = DB::table('showing_oprations as so')
            ->leftJoin('master_seed as ms', 'so.seed_id', '=', 'ms.id')
             ->leftJoin('blocks as b', 'so.block_name', '=', 'b.id')
             ->leftJoin('master_plots as mp', 'so.block_name', '=', 'mp.id')
            ->select('ms.seed_name')
            ->groupBy('ms.seed_name')
            ->get();

        $seedNames = [];
        foreach ($seedData as $seed) {
            $seedNames[] = $seed->seed_name;
        }

        // ✅ 2. Get latest inserted seed_name (highest id) for A + A1
        $latestSeedRow = DB::table('showing_oprations as so')
            ->leftJoin('master_seed as ms', 'so.seed_id', '=', 'ms.id')
            ->select('ms.seed_name')
            ->orderByDesc('so.id')
            ->first();

        $latestSeed = $latestSeedRow ? $latestSeedRow->seed_name : null;

        // ✅ Final response
        return response()->json([
            'success' => true,
            'data' => array_values($grouped),
            'seeds' => $seedNames,
            'selected_seed_name' => $latestSeed
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching plot information: ' . $e->getMessage()
        ], 500);
    }
}


 //harvest update

    
    public function storeHarvestUpdate(Request $request)
{

    $validatedData = $request->validate([
        'block_name' => 'required|string|max:255',
        'plot_name' => 'required|string|max:255',
        'harvest_method' => 'required|string|max:255',
        'harvest_purpose' => 'nullable|string|max:255',
        'machine_ids' => 'nullable|array',
        'tractor_ids' => 'nullable|array',
        'area' => 'nullable|numeric|min:0',
        'area_covered' => 'nullable|numeric|min:0',
        'seed_name' => 'required|string|max:255', // Made required to get selected seed from frontend
        'yield_mt' => 'required|numeric|min:0',
        'seed_id' => 'required|numeric|min:0',
        'hsd_consumption' => 'nullable|numeric|min:0',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after_or_equal:start_time',
        'category' => 'nullable|array',
        'category.*' => 'string|max:255|exists:master_manpower,category',
        'date' => 'required|date',
        'manpower_type_id' => 'nullable|max:255',
        'unskilled' => 'nullable|integer|min:0',
        'semi_skilled_1' => 'nullable|integer|min:0',
        'semi_skilled_2' => 'nullable|integer|min:0',
        'spare_parts' => 'nullable|array',
        'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
        'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
        'major_maintenance' => 'nullable|string',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    DB::beginTransaction();
    try {
        $user = auth()->user();
        if (!$user) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'User not authenticated.'], 401);
        }

        $siteId = $user->site_id;
        $siteName = $user->site_id;
        $userId = $user->id;

        if (!$siteName) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Site name not found for this user.'], 404);
        }

        // Updated logic: Get seed_id based on selected seed_name, block_name, and plot_name
        $showing = DB::table('showing_oprations as s')
            ->leftJoin('master_seed as sd', 's.seed_id', '=', 'sd.id')
            ->where('s.block_name', $validatedData['block_name'])
            ->where('s.plot_name', $validatedData['plot_name'])
            ->where('s.seed_id', $validatedData['seed_id']) // Match with selected seed_name
            ->orderByDesc('s.id')
            ->first(['s.id', 's.seed_id', 'sd.seed_name as seed_name']);

        // Validate if the selected seed exists for the given block and plot
        if (!$showing) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Selected seed not found for the specified block and plot combination.'
            ], 404);
        }



        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['start_time']);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['end_time']);
        if ($endDateTime->lessThan($startDateTime)) {
            $endDateTime->addDay();
        }
        $hours_used = round($startDateTime->floatDiffInHours($endDateTime), 2);

        //Handle manpower
        $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();
         $manpowerCategoryIdsString = implode(',', $categoryIds);
        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;

        if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
        }

         $dieselCost = 0;
                $dieselRate = 0;
                $litersTaken = 0;
                $remainingConsumption = $request->input('hsd_consumption', 0);
                $consumptionDetails = [];

              $requestedConsumption = $request->input('hsd_consumption', 0);

                if ($requestedConsumption > 0) {
                // Check total available stock first
                $availableStock = DB::table('diesel_stocks')
                    ->where('site_id', $user->site_id)
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

                // Now safe to update (wrap in transaction)
                DB::beginTransaction();
                try {
                    $dieselCost = 0;
                    $dieselRate = 0;
                    $remainingConsumption = $requestedConsumption;
                    $consumptionDetails = [];

                    $stocks = DB::table('diesel_stocks')
                        ->where('site_id', $user->site_id)
                        ->where('diesel_stock', '>', 0)
                        ->orderBy('date_of_purchase', 'asc')
                        ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

                    foreach ($stocks as $stock) {
                        if ($remainingConsumption <= 0) break;

                        $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                        $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

                    foreach ($consumptionDetails as $detail) {
                        DB::table('diesel_consumption')->insert([
                            'stock_id'   => $detail['stock_id'],
                            'liters_used'=> $detail['liters_used'],
                            'rate'       => $detail['rate'],
                            'cost'       => $detail['cost'],
                            'date'       => $request->date,
                            'activity'   => 'Harvesting',
                            'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                            'user_id'    => $userId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        DB::table('diesel_stock_history')->insert([
                            'diesel_stock_id'   => $detail['stock_id'],
                            'site_id'   => $detail['site_id'],
                            'type'   => 'Consumption',
                            'note'   => 'Harvesting',
                            'date_of_entry' => $request->date,
                            'stock_before_addition'=> $detail['previous_stock'],
                            'consumed_quantity'=> $detail['liters_used'],
                            'rate_per_liter'       => $detail['rate'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'error' => 'SERVER_ERROR',
                        'message' => $e->getMessage()
                    ], 500);
                }
                }


        $majorCostValue = 0;
        $majorMaintenanceContent = null;
        if (!empty($validatedData['spare_parts'])) {
            foreach ($validatedData['spare_parts'] as $part) {
                $majorCostValue += (float) ($part['value'] ?? 0);
            }
            $majorMaintenanceContent = json_encode($validatedData['spare_parts']);
        } elseif (!empty($validatedData['major_maintenance'])) {
            $majorMaintenanceContent = $validatedData['major_maintenance'];
        }

        $totalCost = $manpowerTotal + $dieselCost + $majorCostValue;
       
        $entryDate = Carbon::parse($validatedData['date'])->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
            
        $otherProductionContent = null;

        if ($request->filled('other_production') && is_array($request->other_production)) {
            $otherProductionContent = json_encode(
                collect($request->other_production)->map(function ($item) {
                    return [
                        'product_name' => $item['product_name'],
                        'quantity' => (float) $item['quantity']
                    ];
                })->values()
            );
        }

        // Insert into harvesting_update
        $recordId = DB::table('harvesting_update')->insertGetId([
            'block_name' => $validatedData['block_name'],
            'plot_name' => $validatedData['plot_name'],
            'area' => $validatedData['area'],
            'area_covered' => $validatedData['area_covered'],
            'seed_name' => $showing->seed_name, // Use seed_name from the matched record
            'seed_id' => $showing->seed_id,     // Use seed_id from the matched record
            'harvest_method' => $validatedData['harvest_method'],
            'harvest_purpose' => $validatedData['harvest_purpose'],
            'machine_id' => !empty($validatedData['machine_ids']) ? implode(',', $validatedData['machine_ids']) : null,
            'tractor_id' => !empty($validatedData['tractor_ids']) ? implode(',', $validatedData['tractor_ids']) : null,
            'yield_mt' => $validatedData['yield_mt'],
            'hsd_consumption' => $validatedData['hsd_consumption'],
            'start_time' => $validatedData['start_time'],
            'end_time' => $validatedData['end_time'],
            'hours_used' => $hours_used,
            'date' => $validatedData['date'],
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $validatedData['manpower_type_id'] ?? null,
            'unskilled' => $unskilled ?? null,
            'semi_skilled_1' => $semiSkilled1 ?? null,
            'semi_skilled_2' => $semiSkilled2 ?? null,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'user_id' => $userId,
            'site_id' => $siteName,
            'total_cost' => $totalCost,
            'season_id' => $season ? $season->id : null, 
            'manual_season' => $request->manual_season,
            'crop_id' => $request->crop_id,
            'other_production' => $otherProductionContent,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update or insert into harvest_store_manage
        // Modified logic: Only match by seed_name and site_id (not date, block/plot specific)
        $existingHarvest = DB::table('harvest_store_manage')
            ->where('seed_name', $showing->seed_name)
            ->where('site_id', $siteId)
            ->first();
        if($validatedData['harvest_purpose'] == 'Cash Crop'){
            $product_id = 4;
        }else{
            $product_id = 1;
        }
         
        DB::table('harvest_store_manage')->insert([
            'product_id' => $product_id,
            'site_id' => $siteId,
            'harvest_id' => $recordId,
            'silage_id' => null,
            'hey_id' => null,
            'block_name' => $validatedData['block_name'],
            'plot_name' => $validatedData['plot_name'],
            'seed_name' => $showing->seed_name, // Use seed_name from the matched record
            'seed_id' => $showing->seed_id,     // Use seed_id from the matched record
            'date' => $validatedData['date'],
            'yield_mt' => $validatedData['yield_mt'],
            'total_mt' => $validatedData['yield_mt'],
            'mt_price' => null,
            'sale_price' => null,
            'quantity' => null,
             'season_id' => $season ? $season->id : null, 
            'created_at' => now(),
            'updated_at' => now()
        ]);
        if ($request->filled('other_production') && is_array($request->other_production)) {
            foreach ($request->other_production as $op) {
                if (!empty($op['product_name']) && isset($op['quantity'])) {
                    DB::table('harvest_store_manage')->insert([
                        'product_id' => 5, // ✅ Other Production
                        'site_id' => $siteId,
                        'harvest_id' => $recordId,
                        'silage_id' => null,
                        'hey_id' => null,
                        'block_name' => $validatedData['block_name'],
                        'plot_name' => $validatedData['plot_name'],
        
                        // by-product name
                        'seed_name' => $op['product_name'],
                        'seed_id' => $showing->seed_id,
        
                        'date' => $validatedData['date'],
                        'yield_mt' => $op['quantity'],
                        'total_mt' => $op['quantity'],
                        'quantity' => $op['quantity'],
        
                        'mt_price' => null,
                        'sale_price' => null,
                        'season_id' => $season ? $season->id : null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Harvest data stored and store updated successfully.',
            'harvest_id' => $recordId
        ], 201);
    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => 'Server error', 'error' => $e->getMessage()], 500);
    }
}


//check hey making

 
    public function checkHayMaking(Request $request)
{
    try {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Get all plots with relevant purposes (same as silage making logic)
        $plots = DB::table('harvesting_update as hu')
            ->leftJoin('master_seed as ms', 'hu.seed_id', '=', 'ms.id')
            ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
            ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
             ->leftJoin('blocks as b', 'hu.block_name', '=', 'b.id')
             ->leftJoin('master_plots as mp', 'hu.plot_name', '=', 'mp.id')
            ->select('hu.id','b.id as block_id','b.block_name' ,'mp.id as plot_id', 'mp.plot_name', 'hu.area',
            'ms.id as seed_id',
            DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            'harvest_purpose','hu.site_id')
            ->where('user_id', $userId)
            ->whereIn('harvest_purpose', ['Silage Making', 'Hay Making']) // Only get relevant records
            ->get();

        if ($plots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No plots found for Silage or Hay Making'
            ], 404);
        }

        $grouped = [];
        $seenSeeds = [];

        // Group all harvest_purpose by seed_name (same logic as silage making)
        $seedPurposeMap = [];
        foreach ($plots as $plot) {
            if (!isset($seedPurposeMap[$plot->seed_id])) {
            }
            $seedPurposeMap[$plot->seed_name][] = $plot->harvest_purpose;
        }

        foreach ($plots as $plot) {
            // Avoid duplicate seed_name processing
            if (in_array($plot->seed_id, $seenSeeds)) {
                continue;
            }
            $seenSeeds[] = $plot->seed_id;

            $blockName = $plot->block_name;
            if($blockName){
            if (!isset($grouped[$blockName])) {
                $grouped[$blockName] = [
                    'block_name' => $blockName,
                    'block_id' => $plot->block_id,
                    'plots' => []
                ];
            }

            // Updated logic: Check if ANY harvest_purpose is "Hay Making"
            $purposes = $seedPurposeMap[$plot->seed_name] ?? [];
            $isFodderCrop = 0;

            // Only if purpose is "Hay Making", set to 1
            if (in_array('Hay Making', $purposes)) {
                $isFodderCrop = 1;
            }

            // Get yield_mt from harvest_store_manage
            $yield_mt = DB::table('harvest_store_manage')
                ->where('seed_id', $plot->seed_id)
                ->where('site_id', $plot->site_id)
                ->where('product_id', 1)
                ->sum('yield_mt');

            $grouped[$blockName]['plots'][] = [
                'harvest_id' => $plot->id,
                'plot_id' => $plot->plot_id,
                'plot_name' => $plot->plot_name,
                'area' => $plot->area,
                'seed_id' => $plot->seed_id,
                'seed_name' => $plot->seed_name,
                'is_fodder_crop' => $isFodderCrop,
                'yield_mt' => $yield_mt ?? '0.00'
            ];
        }
        }
        return response()->json([
            'success' => true,
            'data' => array_values($grouped)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

//check silage making

    public function checkSilageMaking(Request $request)
{
    try {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Get all plots with relevant purposes
        $plots = DB::table('harvesting_update as hu')
            ->leftJoin('master_seed as ms', 'hu.seed_id', '=', 'ms.id')
            ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
            ->leftJoin('master_veriety as mv', 'ms.seed_variety_id', '=', 'mv.id')
            ->leftJoin('blocks as b', 'hu.block_name', '=', 'b.id') // use block_id
            ->leftJoin('master_plots as mp', 'hu.plot_name', '=', 'mp.id') // use plot_id
            ->select('hu.id','b.id as block_id','b.block_name' ,'mp.id as plot_id', 'mp.plot_name', 'hu.area',
            'ms.id as seed_id',
             DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') as seed_name"),
            'harvest_purpose','hu.site_id')
            ->where('user_id', $userId)
            ->whereIn('harvest_purpose', ['Silage Making', 'Hay Making'])
            ->get();

        if ($plots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No plots found for Silage or Hay Making'
            ], 404);
        }

        $grouped = [];
        $seenSeeds = [];

        // 🔁 Group all harvest_purpose by seed_name
        $seedPurposeMap = [];
        foreach ($plots as $plot) {
            if (!isset($seedPurposeMap[$plot->seed_name])) {
                $seedPurposeMap[$plot->seed_name] = [];
            }
            $seedPurposeMap[$plot->seed_name][] = $plot->harvest_purpose;
        }

        foreach ($plots as $plot) {
            // ✅ Avoid duplicate seed_name processing
            if (in_array($plot->seed_name, $seenSeeds)) {
                continue;
            }
            $seenSeeds[] = $plot->seed_name;

            $blockName = $plot->block_name;
            if($blockName){
            if (!isset($grouped[$blockName])) {
                $grouped[$blockName] = [
                    'block_name' => $blockName,
                     'block_id' => $plot->block_id,
                    'plots' => []
                ];
            }

            // ✅ Updated logic: Only check for "Silage Making"
            $purposes = $seedPurposeMap[$plot->seed_name] ?? [];
            $isSilageMaking = 0;

            // Only if purpose is "Silage Making", set to 1
            if (in_array('Silage Making', $purposes)) {
                $isSilageMaking = 1;
            }

            // ✅ Get yield_mt
            $yield_mt = DB::table('harvest_store_manage')
                ->where('seed_id', $plot->seed_id)
                ->where('site_id', $plot->site_id)
                ->where('product_id', 1)
                ->sum('yield_mt');

            $grouped[$blockName]['plots'][] = [
                'plot_id' => $plot->plot_id,
                'plot_name' => $plot->plot_name,
                'area' => $plot->area,
                'seed_id' => $plot->seed_id,
                'seed_name' => $plot->seed_name,
                'is_silage_making' => $isSilageMaking,
                'yield_mt' => $yield_mt ?? '0.00'
            ];
        }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($grouped)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

//check hay making


    /**
     * Store hay making activity and update harvest store manage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeHayMaking(Request $request)
{
    $validatedData = $request->validate([
        'block_name' => 'required|string|max:255',
        'plot_name' => 'required|string|max:255',
        'seed_name' => 'required|string|max:255',
        'hay_making_method' => 'nullable|string|max:255',
        'machine_ids' => 'nullable|array',
        'tractor_ids' => 'nullable|array',
        'area' => 'nullable|numeric|min:0',
        'area_covered' => 'nullable|string|max:255',
        'yield_mt' => 'required|numeric|min:0',
        'seed_id' => 'required|numeric|min:0',
        'hsd_consumption' => 'nullable|numeric|min:0',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'manpower_categories' => 'nullable|array',
        'date' => 'required|date',
        'manpower_type_id' => 'nullable|integer',
        'spare_parts' => 'nullable|array',
        'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
        'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
        'major_maintenance' => 'nullable|string',
        'user_id' => 'required|integer|exists:users,id',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    DB::beginTransaction();

    try {
        $user = auth()->user();
        if (!$user || !$user->site_name) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'User not authenticated or missing site name.'], 401);
        }

        $siteName = $user->site_name;
        $siteId = $user->site_id;
        $userId = $user->id;

        // Get harvest data based on block_name, plot_name, and selected seed_name
        $harvest = DB::table('harvesting_update')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $validatedData['seed_id'])
            ->latest('date')
            ->first(['id', 'seed_id', 'seed_name']);

        if (!$harvest) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'No matching harvest data found for the selected block, plot, and seed combination.',
                'data' => [
                    'block_name' => $validatedData['block_name'],
                    'plot_name' => $validatedData['plot_name'],
                    'seed_name' => $validatedData['seed_name']
                ]
            ], 404);
        }

        // ✅ Check current available yield_mt in harvest_store_manage before proceeding
        $currentAvailableYield = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('site_id', $siteId)
            ->where('product_id', 1) // Harvested products only
            ->sum('yield_mt');

        if ($currentAvailableYield <= 0) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                 'message' => 'You cannot make hay because you do not have yield available.',
                //'message' => 'आप hay नहीं बना सकते हैं क्योंकि आपके पास yield नहीं है।',
                'english_message' => 'You cannot make hay because you do not have yield available.',
                'available_yield' => $currentAvailableYield,
                'requested_yield' => $validatedData['yield_mt']
            ], 400);
        }

        if ($validatedData['yield_mt'] > $currentAvailableYield) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Requested yield amount exceeds available yield.',
                'available_yield' => $currentAvailableYield,
                'requested_yield' => $validatedData['yield_mt']
            ], 400);
        }

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['start_time']);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['end_time']);

        if ($endDateTime->lessThan($startDateTime)) {
            $endDateTime->addDay();
        }

        $hours_used = round($startDateTime->floatDiffInHours($endDateTime), 2);

        //Handle manpower
        $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();
         $manpowerCategoryIdsString = implode(',', $categoryIds);
        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;

        if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
            foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
        }
        // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Hay Making',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Hay Making',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }
        $majorCostValue = 0;
        $majorMaintenanceContent = null;

        if (!empty($validatedData['spare_parts']) && is_array($validatedData['spare_parts'])) {
            foreach ($validatedData['spare_parts'] as $part) {
                $majorCostValue += (float) ($part['value'] ?? 0);
            }
            $majorMaintenanceContent = json_encode($validatedData['spare_parts']);
        } elseif (!empty($validatedData['major_maintenance'])) {
            $majorMaintenanceContent = $validatedData['major_maintenance'];
        }

        $totalCost = $manpowerTotal + $dieselCost + $majorCostValue;

        // CORRECTED LOGIC: total_yield_mt should be current available yield (before this activity)
        $totalYieldMt = $currentAvailableYield;

        $entryDate = Carbon::parse($validatedData['date'])->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
       
        $recordId = DB::table('hay_making')->insertGetId([
            'user_id' => $validatedData['user_id'],
            'block_name' => $validatedData['block_name'],
            'plot_name' => $validatedData['plot_name'],
            'area' => $validatedData['area'],
            'area_covered' => $validatedData['area_covered'],
            'seed_name' => $harvest->seed_name,
            'seed_id' => $harvest->seed_id,
            'hay_making_method' => $request->hay_making_method,
            'machine_id' => !empty($validatedData['machine_ids']) ? implode(',', $validatedData['machine_ids']) : null,
            'tractor_id' => !empty($validatedData['tractor_ids']) ? implode(',', $validatedData['tractor_ids']) : null,
            'yield_mt' => $validatedData['yield_mt'],
            'total_yield_mt' => $totalYieldMt, // Current available yield
            'hsd_consumption' => $validatedData['hsd_consumption'],
            'start_time' => $validatedData['start_time'],
            'end_time' => $validatedData['end_time'],
            'hours_used' => $hours_used,
            'harvest_date' => $validatedData['date'],
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $validatedData['manpower_type_id'] ?? null,
            'unskilled' => $unskilled ?? null,
            'semi_skilled_1' => $semiSkilled1 ?? null,
            'semi_skilled_2' => $semiSkilled2 ?? null,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'site_id' => $siteName,
            'user_id' => $userId,
            'total_cost' => $totalCost,
             'season_id' => $season ? $season->id : null, 
             'manual_season' => $request->manual_season,
             'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ✅ Deduct yield_mt from harvest_store_manage (product_id = 1 for harvested products)
        $harvestRecords = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('seed_name', $harvest->seed_name)
            ->where('site_id', $siteId)
            ->where('product_id', 1) // Harvested products
            ->where('yield_mt', '>', 0)
            ->orderBy('date', 'asc')
            ->get();

        $remainingToDeduct = $validatedData['yield_mt'];

        foreach ($harvestRecords as $record) {
            if ($remainingToDeduct <= 0) break;

            $deductAmount = min($record->yield_mt, $remainingToDeduct);

            DB::table('harvest_store_manage')
                ->where('id', $record->id)
                ->update([
                    'yield_mt' => $record->yield_mt - $deductAmount,

                    'updated_at' => now()
                ]);

            $remainingToDeduct -= $deductAmount;
        }

        // ✅ Calculate hay yield with 80% reduction
        $originalYield = $validatedData['yield_mt'];
        $hayYield = $originalYield * 0.2; // 80% reduction, so 20% remains

        // ✅ Create/Update hay entry in harvest_store_manage (product_id = 2 for hay) with reduced yield
        $matchedSeed = DB::table('master_seed')->where('id', $harvest->seed_id)->first();
        $mtPrice = $matchedSeed->rate_of_seed ?? null;

        // Check for existing HAY record
        $existingHayRecord = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('seed_name', $harvest->seed_name)
            ->where('site_id', $siteId)
            ->where('product_id', 2) // Only hay records
            ->whereDate('date', $validatedData['date'])
            ->first();

        if ($existingHayRecord) {
            // Update existing HAY record
            DB::table('harvest_store_manage')
                ->where('id', $existingHayRecord->id)
                ->update([
                    'yield_mt' => DB::raw("yield_mt + {$hayYield}"),
                    'total_mt' => DB::raw("total_mt + {$hayYield}"),
                    'hey_id' => $recordId,
                    'quantity' => DB::raw("quantity + {$validatedData['yield_mt']}"),
                    'updated_at' => now()
                ]);
        } else {
            // Insert NEW record for hay making
            DB::table('harvest_store_manage')->insert([
                'hey_id'       => $recordId,
                'harvest_id'   => null,
                'silage_id'    => null,
                'product_id'   => 2, // product_id = 2 for hay
                'site_id'      => $siteId,
                'block_name'   => $validatedData['block_name'],
                'plot_name'    => $validatedData['plot_name'],
                'seed_name'    => $harvest->seed_name,
                'seed_id'      => $harvest->seed_id,
                'date'         => $validatedData['date'],
                'yield_mt'     => $hayYield, // 80% reduced yield
                'total_mt'     => $hayYield, // 80% reduced yield
                'mt_price'     => $mtPrice,
                'quantity'      => $validatedData['yield_mt'],
                'season_id' => $season ? $season->id : null, 
                'created_at'   => now(),
                'updated_at'   => now()
            ]);
        }

        // ✅ Calculate remaining yield after this activity
        $remainingYieldAfter = $currentAvailableYield - $validatedData['yield_mt'];

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Hay making data stored successfully',
            'insert_id' => $recordId,
            'seed_name' => $harvest->seed_name,
            'seed_id' => $harvest->seed_id,
            'total_cost' => number_format($totalCost, 2),
            'cost_breakdown' => [
                'manpower' => ['total' => number_format($manpowerTotal, 2)],
                'diesel' => ['cost' => number_format($dieselCost, 2)],
            ],
            'time_used' => $hours_used,
            'original_yield_used' => $originalYield,
            'hay_yield_produced' => $hayYield,
            'yield_reduction_percentage' => '80%',
            'total_yield_mt' => $totalYieldMt, // Shows available yield at time of activity
            'available_yield_before' => $currentAvailableYield,
            'available_yield_after' => $remainingYieldAfter
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'error' => 'SERVER_ERROR',
            'message' => 'Failed to store hay making data',
            'system_message' => $e->getMessage()
        ], 500);
    }
}

//silage making store data

    /**
     * Store silage making activity and update harvest store manage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storesilageMaking(Request $request)
{
    $validatedData = $request->validate([
        'block_name' => 'required|string|max:255',
        'plot_name' => 'required|string|max:255',
        'seed_name' => 'required|string|max:255',
        'silage_making_method' => 'nullable|string|max:255',
        'machine_ids' => 'nullable|array',
        'tractor_ids' => 'nullable|array',
        'area' => 'nullable|numeric|min:0',
        'area_covered' => 'nullable|string|max:255',
        'yield_mt' => 'required|numeric|min:0',
        'seed_id' => 'required|numeric|min:0',
        'hsd_consumption' => 'nullable|numeric|min:0',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'category' => 'nullable|array',
        //'category.*' => 'string|max:255',
        'date' => 'required|date',
        'manpower_type_id' => 'nullable|integer',
        'spare_parts' => 'nullable|array',
        'manpower_categories' => 'nullable|array',
        'spare_parts.*.spare_part' => 'required_with:spare_parts|string|max:255',
        'spare_parts.*.value' => 'required_with:spare_parts|numeric|min:0',
        'major_maintenance' => 'nullable|string',
        'user_id' => 'required|integer|exists:users,id',
        'manual_season' => 'nullable|string|in:Kharif,Rabi,Zaid',
        'crop_id' => 'nullable|integer',
    ]);

    DB::beginTransaction();

    try {
        $user = auth()->user();
        if (!$user || !$user->site_name) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'User not authenticated or missing site name.'], 401);
        }

        $siteName = $user->site_id;
        $siteId = $user->site_id;
        $userId = $user->id;

        // Get harvest data based on block_name, plot_name, and selected seed_name
        $harvest = DB::table('harvesting_update')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $validatedData['seed_id'])
            ->latest('date')
            ->first(['id', 'seed_id', 'seed_name']);

        if (!$harvest) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'No matching harvest data found for the selected block, plot, and seed combination.',
                'data' => [
                    'block_name' => $validatedData['block_name'],
                    'plot_name' => $validatedData['plot_name'],
                    'seed_name' => $validatedData['seed_name']
                ]
            ], 404);
        }

        // ✅ Check current available yield_mt in harvest_store_manage before proceeding
        $currentAvailableYield = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('site_id', $siteId)
            ->where('product_id', 1) // Harvested products only
            ->sum('yield_mt');

        if ($currentAvailableYield <= 0) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot make silage because you do not have yield available.',
                'english_message' => 'You cannot make silage because you do not have yield available.',
                'available_yield' => $currentAvailableYield,
                'requested_yield' => $validatedData['yield_mt']
            ], 400);
        }

        if ($validatedData['yield_mt'] > $currentAvailableYield) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Requested yield amount exceeds available yield.',
                'available_yield' => $currentAvailableYield,
                'requested_yield' => $validatedData['yield_mt']
            ], 400);
        }
        //Handle manpower
        $categoryIds = [];
        $categoryIds = collect($request->manpower_categories)->pluck('category_id')->toArray();
         $manpowerCategoryIdsString = implode(',', $categoryIds);
        $manpowerCategories = DB::table('master_manpower')
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id'); // index by id
        $manpowerCosts = [];
        $manpowerTotal = 0;

        // Initialize default columns (for DB fields)
        $unskilled = $semiSkilled1 = $semiSkilled2 = 0;
         if ($request->filled('manpower_categories') && is_array($request->manpower_categories)) {
        foreach ($request->manpower_categories as $cat) {
            $id = $cat['category_id'];
            $noOfPerson = (int) $cat['no_of_person'];

            if (isset($manpowerCategories[$id])) {
                $row = $manpowerCategories[$id];
                $rate = $row->rate ?? 0;
                $cost = $rate * $noOfPerson;

                $manpowerCosts[] = [
                    'category_id'   => $id,
                    'category_name' => $row->category,
                    'rate'          => $rate,
                    'no_of_person'  => $noOfPerson,
                    'cost'          => $cost
                ];

                $manpowerTotal += $cost;

                // Map into table columns
                if ($row->category === 'Unskilled') {
                    $unskilled = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 1') {
                    $semiSkilled1 = $noOfPerson;
                } elseif ($row->category === 'Semi Skilled 2') {
                    $semiSkilled2 = $noOfPerson;
                }
            }
        }
         }

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['start_time']);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['end_time']);
        if ($endDateTime->lessThan($startDateTime)) {
            $endDateTime->addDay();
        }

        $hours_used = round($startDateTime->floatDiffInHours($endDateTime), 2);

        // Diesel FIFO logic
        $dieselCost = 0;
        $dieselRate = 0;
        $litersTaken = 0;
        $remainingConsumption = $request->input('hsd_consumption', 0);
        $consumptionDetails = [];

      $requestedConsumption = $request->input('hsd_consumption', 0);

        if ($requestedConsumption > 0) {
        // Check total available stock first
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $user->site_id)
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

        // ✅ Now safe to update (wrap in transaction)
        DB::beginTransaction();
        try {
            $dieselCost = 0;
            $dieselRate = 0;
            $remainingConsumption = $requestedConsumption;
            $consumptionDetails = [];

            $stocks = DB::table('diesel_stocks')
                ->where('site_id', $user->site_id)
                ->where('diesel_stock', '>', 0)
                ->orderBy('date_of_purchase', 'asc')
                ->get(['id', 'diesel_stock', 'rate_per_liter', 'site_id', 'diesel_consumption']);

            foreach ($stocks as $stock) {
                if ($remainingConsumption <= 0) break;

                $litersTaken = min($stock->diesel_stock, $remainingConsumption);
                $diesel_consumption = ($stock->diesel_consumption ?? 0) + $litersTaken;

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

            foreach ($consumptionDetails as $detail) {
                DB::table('diesel_consumption')->insert([
                    'stock_id'   => $detail['stock_id'],
                    'liters_used'=> $detail['liters_used'],
                    'rate'       => $detail['rate'],
                    'cost'       => $detail['cost'],
                    'date'       => $request->date,
                    'activity'   => 'Silage Making',
                    'tractor_id' => $request->tractor_ids ? implode(',', $request->tractor_ids) : 0,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('diesel_stock_history')->insert([
                    'diesel_stock_id'   => $detail['stock_id'],
                    'site_id'   => $detail['site_id'],
                    'type'   => 'Consumption',
                    'note'   => 'Silage Making',
                    'date_of_entry' => $request->date,
                    'stock_before_addition'=> $detail['previous_stock'],
                    'consumed_quantity'=> $detail['liters_used'],
                    'rate_per_liter'       => $detail['rate'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        }

        $majorCostValue = 0;
        $majorMaintenanceContent = null;

        if (!empty($validatedData['spare_parts']) && is_array($validatedData['spare_parts'])) {
            foreach ($validatedData['spare_parts'] as $part) {
                $majorCostValue += (float) ($part['value'] ?? 0);
            }
            $majorMaintenanceContent = json_encode($validatedData['spare_parts']);
        } elseif (!empty($validatedData['major_maintenance'])) {
            $majorMaintenanceContent = $validatedData['major_maintenance'];
        }

        $totalCost = $manpowerTotal + $dieselCost + $majorCostValue;
        $totalYieldMt = $currentAvailableYield;
        
         
        $entryDate = Carbon::parse($validatedData['date'])->format('Y-m-d');
        // Find season by date range
        $season = DB::table('seasons')
            ->where('block_id', $request->block_name)
            ->where('plot_id', $request->plot_name)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->where('site_id',$siteName)
            ->first();
        
        $recordId = DB::table('silage_making')->insertGetId([
            'user_id' => $userId,
            'block_name' => $validatedData['block_name'],
            'plot_name' => $validatedData['plot_name'],
            'area' => $validatedData['area'],
            'area_covered' => $validatedData['area_covered'],
            'silage_making_method' => $validatedData['silage_making_method'],
            'machine_id' => !empty($validatedData['machine_ids']) ? implode(',', $validatedData['machine_ids']) : null,
            'tractor_id' => !empty($validatedData['tractor_ids']) ? implode(',', $validatedData['tractor_ids']) : null,
            'yield_mt' => $validatedData['yield_mt'],
            'total_yield_mt' => $totalYieldMt, // Current available yield
            'hsd_consumption' => $validatedData['hsd_consumption'],
            'start_time' => $validatedData['start_time'],
            'end_time' => $validatedData['end_time'],
            'hours_used' => $hours_used,
            'harvest_date' => $validatedData['date'],
            'date' => $validatedData['date'],
            'category_id' => $request->manpower_categories ? json_encode($request->manpower_categories) : null,
            'manpower_type' => $validatedData['manpower_type_id'] ?? null,
            'unskilled' => $unskilled ?? null,
            'semi_skilled_1' => $semiSkilled1 ?? null,
            'semi_skilled_2' => $semiSkilled2 ?? null,
            'major_maintenance' => $majorMaintenanceContent,
            'major_cost' => $majorCostValue,
            'site_id' => $siteName,
            'seed_name' => $harvest->seed_name,
            'seed_id' => $harvest->seed_id,
            'total_cost' => $totalCost,
             'season_id' => $season ? $season->id : null,
             'manual_season' => $request->manual_season,
             'crop_id' => $request->crop_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ✅ Deduct yield_mt from harvest_store_manage (product_id = 1 for harvested products)
        $harvestRecords = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('seed_name', $harvest->seed_name)
            ->where('site_id', $siteId)
            ->where('product_id', 1) // Harvested products
            ->where('yield_mt', '>', 0)
            ->orderBy('date', 'asc')
            ->get();

        $remainingToDeduct = $validatedData['yield_mt'];

        foreach ($harvestRecords as $record) {
            if ($remainingToDeduct <= 0) break;

            $deductAmount = min($record->yield_mt, $remainingToDeduct);

            DB::table('harvest_store_manage')
                ->where('id', $record->id)
                ->update([
                    'yield_mt' => $record->yield_mt - $deductAmount,

                    'updated_at' => now()
                ]);

            $remainingToDeduct -= $deductAmount;
        }

        // ✅ Calculate silage yield with 20% reduction
        $originalYield = $validatedData['yield_mt'];
        $silageYield = $originalYield * 0.8; // 20% reduction, so 80% remains

        // ✅ Create/Update silage entry in harvest_store_manage (product_id = 3 for silage) with reduced yield
        $matchedSeed = DB::table('master_seed')->where('id', $harvest->seed_id)->first();
        $mtPrice = $matchedSeed->rate_of_seed ?? null;

        $existingSilage = DB::table('harvest_store_manage')
            ->where('block_name', $validatedData['block_name'])
            ->where('plot_name', $validatedData['plot_name'])
            ->where('seed_id', $harvest->seed_id)
            ->where('seed_name', $harvest->seed_name)
            ->where('site_id', $siteId)
            ->where('product_id', 3) // Silage products
            ->whereDate('date', $validatedData['date'])
            ->first();

        if ($existingSilage) {
            DB::table('harvest_store_manage')
                ->where('id', $existingSilage->id)
                ->update([
                    'yield_mt' => DB::raw("yield_mt + {$silageYield}"),
                    'total_mt' => DB::raw("total_mt + {$silageYield}"),
                    'silage_id' => $recordId,
                    'quantity' => DB::raw("quantity + {$validatedData['yield_mt']}"),
                    'updated_at' => now()
                ]);
        } else {
            DB::table('harvest_store_manage')->insert([
                'silage_id'    => $recordId,
                'harvest_id'   => null,
                'hey_id'       => null,
                'product_id'   => 3, // Silage
                'site_id'      => $siteId,
                'block_name'   => $validatedData['block_name'],
                'plot_name'    => $validatedData['plot_name'],
                'seed_name'    => $harvest->seed_name,
                'seed_id'      => $harvest->seed_id,
                'date'         => $validatedData['date'],
                'yield_mt'     => $silageYield, // 20% reduced yield
                'total_mt'     => $silageYield, // 20% reduced yield
                'mt_price'     => $mtPrice,
                'quantity'     =>$validatedData['yield_mt'],
                 'season_id' => $season ? $season->id : null, 
                'created_at'   => now(),
                'updated_at'   => now()
            ]);
        }

        // ✅ Calculate remaining yield after this activity
        $remainingYieldAfter = $currentAvailableYield - $validatedData['yield_mt'];

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Silage making data stored successfully',
            'insert_id' => $recordId,
            'seed_name' => $harvest->seed_name,
            'seed_id' => $harvest->seed_id,
            'total_cost' => number_format($totalCost, 2),
            'cost_breakdown' => [
                'manpower' => ['total' => number_format($manpowerTotal, 2)],
                'diesel' => ['cost' => number_format($dieselCost, 2)],
            ],
            'time_used' => $hours_used,
            'original_yield_used' => $originalYield,
            'silage_yield_produced' => $silageYield,
            'yield_reduction_percentage' => '20%',
            'total_yield_mt' => $totalYieldMt, // Shows available yield at time of activity
            'available_yield_before' => $currentAvailableYield,
            'available_yield_after' => $remainingYieldAfter
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'message' => 'Failed to store silage making data',
            'system_message' => $e->getMessage()
        ], 500);
    }
}
//chemical data get api
    /**
     * Get all chemicals for the user's site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chemicals(Request $request)
        {
           $user = Auth::user();

            $chemicals = DB::table('master_chemical as mc')
                ->leftJoin('chemical_master as cm', 'mc.chemical_id', '=', 'cm.id')
                ->select(
                    'mc.*',
                    DB::raw("CONCAT(cm.chemical_name, ' (', mc.chemical_type, ')') as chemical_name")
                )
                ->where('mc.site_id', $user->site_id)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $chemicals
            ], 200);
        }

         //notification user logn base show to message
    /**
     * Get notifications for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
   public function notification(Request $request)
{
    // Get the authenticated user.
    $user = Auth::user();
    
    // Fetch notifications for the user with block and plot names
    $notifications = DB::table('notifications')
        ->where('notifications.user_id', $user->id)
        ->whereNull('notifications.deleted_at')
        ->leftJoin('blocks as block_user', 'block_user.id', '=', 'notifications.block_name')
        ->leftJoin('master_plots as plot_user', 'plot_user.id', '=', 'notifications.plot_name')
        ->orderBy('notifications.created_at', 'desc')
        ->select(
            'notifications.*',
            'block_user.block_name',
            'plot_user.plot_name'
        )
        ->get();
    
    return response()->json([
        'message' => 'Notifications retrieved successfully',
        'notifications' => $notifications,
    ]);
}

        //updated the notification
    /**
     * Update notification status and remarks for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotification(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
            'status' => 'required|string|in:Open,Pending,Resolved', // Validate allowed status values
            'remark' => 'nullable|string',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Find the notification using DB::table()
        // Ensure the notification belongs to the authenticated user
        $notification = DB::table('notifications')
                            ->where('id', $request->notification_id)
                            ->where('user_id', $user->id)
                            ->first();

        // If notification not found or doesn't belong to the user
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found or unauthorized access.',
            ], 404);
        }

        // Prepare data for update
        $updateData = [
            'status' => $request->status,
            'remark' => $request->remark,
            'updated_at' => now(), // Manually set updated_at
        ];

        $message = 'Notification updated successfully.';

        // Check if the notification should be "removed" (soft-deleted)
        // This condition is met when the status is 'Resolved' AND a remark is provided
        if ($request->status === 'Resolved' && !empty($request->remark)) {
            // Manually set the 'deleted_at' timestamp to "soft delete" the record
            $updateData['deleted_at'] = now();
            $message = 'Notification marked as resolved and removed from view.';
        } else {
            // Ensure 'deleted_at' is NULL if it's not being resolved/removed,
            // in case it was previously soft-deleted and is being re-opened.
            // This line is crucial for re-activating a previously "removed" notification.
            $updateData['deleted_at'] = null;
        }

        // Perform the update using DB::table()
        DB::table('notifications')
            ->where('id', $request->notification_id)
            ->update($updateData);

        // Return a success response
        return response()->json([
            'message' => $message,
        ]);
    }



       ///App all activity filter
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
        ['display' => 'Harvest', 'table' => 'harvesting_update'],
        ['display' => 'Hay Making', 'table' => 'hay_making'],
        ['display' => 'Silage Making', 'table' => 'silage_making'],
    ];

    protected $expectedColumns = [
        'block_name',
        'plot_name',
        'date',
        'area',
        'irrigation_date',
        'area_acre',
        'machine_id',
        'tractor_id',
        'hsd_consumption',
        'manpower_type',
        'electricity_units',
        'cost_per_unit',
        'fertilizer_id',
        'fertilizer_quantity',
        'major_maintenance',
        'area_covered',
        'seed_name',
        'total_cost',
        'created_at',
        'unskilled',
        'sowing_date',
        'semi_skilled_1',
        'semi_skilled_2',
        'yield_mt',
        'manual_session',
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

     
    /**
     * Get filter options for locations, blocks, plots, and activities for the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
  public function getFilters(Request $request)
{
    try {
        $user = Auth::user();
        $userId = $user->id;
        $role = $user->role;
        
        // ---- Locations ----
        $locations = [];
        if ($role == 1) {
            $locations = DB::table('master_sites')
                ->select('id', 'site_name')
                ->get();
        } else {
            $locations = DB::table('master_sites as s')
                ->join('blocks as b', 's.id', '=', 'b.site_id')
                ->join('user_plots as up', 'b.id', '=', 'up.block_id')
                ->where('up.user_id', $userId)
                ->select('s.id', 's.site_name')
                ->distinct()
                ->get();
        }
        
        // ---- Blocks with assigned plots ----
        $blocksWithPlots = DB::table('blocks as b')
            ->join('user_plots as up', 'b.id', '=', 'up.block_id')
            ->join('master_plots as p', 'p.id', '=', 'up.plot_id')
            ->where('up.user_id', $userId)
            ->select(
                'b.id as block_id', 
                'b.block_name',
                'p.id as plot_id',
                'p.plot_name'
            )
            ->orderBy('b.block_name')
            ->orderBy('p.plot_name')
            ->get();
        
        // Group plots under blocks
        $blocks = [];
        foreach ($blocksWithPlots as $item) {
            $blockId = $item->block_id;
            
            // Initialize block if not exists
            if (!isset($blocks[$blockId])) {
                $blocks[$blockId] = [
                    'block_id' => $item->block_id,
                    'block_name' => $item->block_name,
                    'plots' => []
                ];
            }
            
            // Add plot to the block
            $blocks[$blockId]['plots'][] = [
                'plot_id' => $item->plot_id,
                'plot_name' => $item->plot_name
            ];
        }
        
        // Convert to indexed array
        $blocks = array_values($blocks);
        
        // ---- Activities ----
        $activities = [];
        foreach ($this->orderedActivities as $activity) {
            $activities[] = $activity['display'];
        }
        
        return response()->json([
            'locations' => $locations,
            'blocks' => $blocks,
            'activities' => $activities
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching filter data',
            'error' => $e->getMessage()
        ], 500);
    }
}



   
    /**
     * Get history of activities for the user, with cost and yield summaries.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
       public function getHistory(Request $request)
{
    try {
        $user = Auth::user();
        $loggedInSiteId = $user->site_id;
        $role = $user->role;

        $selectedLocation = $request->input('location');
        $blockFilter = $request->input('block');  // This is block_id now
        $plotFilter = $request->input('plot');    // This is plot_id now
        $activity = $request->input('activity');

        $dateRange = $request->input('dateRange');
        $from = $dateRange['start'] ?? null;
        $to = $dateRange['end'] ?? null;

        // Determine selected site
        $selectedSiteId = $loggedInSiteId;
        if ($role == 1 && $selectedLocation) {
            $site = DB::table('master_sites')->where('id', $selectedLocation)->first();
            if ($site) $selectedSiteId = $site->id;
        }

        // Master data
        $masterManpowerRates = DB::table('master_manpower')->pluck('rate', 'category')->toArray();
        $masterFertilizers = DB::table('master_fertilizer')->get(['id', 'fertilizer_name', 'rate'])->keyBy('id')->toArray();
        $machines = DB::table('master_machine')->pluck('machine_name', 'id')->toArray();
        $tractors = DB::table('master_tractors')->pluck('tractor_name', 'id')->toArray();
        $fertilizers = DB::table('master_fertilizer')->pluck('fertilizer_name', 'id')->toArray();
        $seeds = DB::table('master_seed')
        ->leftJoin('seed as s', 'master_seed.seed_id', '=', 's.id')
        ->leftJoin('master_veriety as mv', 's.id', '=', 'mv.seed_id')
        ->select(
        'master_seed.id',
        DB::raw("CONCAT(s.name, ' (', mv.variety_name, ')') AS seed_name"), 
        'master_seed.seed_stock_kg')
        ->get()->keyBy('id')->toArray();

        // Block & Plot mappings
        $blockNames = DB::table('blocks')->pluck('block_name', 'id')->toArray();
        $plotNames = DB::table('master_plots')->pluck('plot_name', 'id')->toArray();

        $summary = [];
        $totalStats = [
            'electricityCost' => 0,
            'hsdCost' => 0,
            'fertilizerCost' => 0,
            'maintenanceCost' => 0,
            'manpowerCost' => 0,
            'totalCost' => 0,
        ];

        foreach ($this->activityTables as $table) {
            // Filter by activity
            if ($activity) {
                $activityTable = null;
                foreach ($this->orderedActivities as $act) {
                    if ($act['display'] === $activity) {
                        $activityTable = $act['table'];
                        break;
                    }
                }
                if ($activityTable && $activityTable !== $table) continue;
            }

            if ($table === 'harvest_store_manage') continue;
            if (!Schema::hasTable($table)) continue;

            $colsInTable = Schema::getColumnListing($table);
            $selectCols = ['id'];

            foreach ($this->expectedColumns as $col) {
                if ($col === 'area_acre' && in_array('area_covered', $colsInTable)) {
                    $selectCols[] = 'area_covered AS area_acre';
                } elseif (in_array($col, $colsInTable)) {
                    $selectCols[] = $col;
                }
            }

            if ($table === 'crop_protection' && in_array('chemical_id', $colsInTable)) $selectCols[] = 'chemical_id';
            if ($table === 'showing_oprations') {
                if (in_array('seed_id', $colsInTable)) $selectCols[] = 'seed_id';
                if (in_array('seed_consumption', $colsInTable)) $selectCols[] = 'seed_consumption';
                if (in_array('variety', $colsInTable)) $selectCols[] = 'variety';
            }

            if (in_array('site_id', $colsInTable)) $selectCols[] = 'site_id';
            if (in_array('block_name', $colsInTable)) $selectCols[] = 'block_name';
            if (in_array('plot_name', $colsInTable)) $selectCols[] = 'plot_name';

            $q = DB::table($table)->select($selectCols);

            // Apply filters
            if (in_array('site_id', $colsInTable)) {
                $q->where('site_id', $selectedSiteId);
            }
            
            // Block filter - blockFilter is now the block_id
            if ($blockFilter && in_array('block_name', $colsInTable)) {
                $q->where('block_name', $blockFilter);
            }
            
            // Plot filter - plotFilter is now the plot_id
            if ($plotFilter && in_array('plot_name', $colsInTable)) {
                $q->where('plot_name', $plotFilter);
            }
            
            // Date filter
            if ($from && $to && in_array('date', $colsInTable)) {
                $q->whereBetween('date', [$from, $to]);
            }

            $records = $q->get();

            foreach ($records as $rec) {
                $row = $this->processRecord($rec, $table, $machines, $tractors, $fertilizers, $seeds, $masterFertilizers, $masterManpowerRates, $selectedSiteId);

                // Convert block_name & plot_name IDs to actual names
                if (isset($rec->block_name) && array_key_exists($rec->block_name, $blockNames)) {
                    $row['block_name'] = $blockNames[$rec->block_name];
                } else if (isset($rec->block_name)) {
                    $row['block_name'] = 'Unknown Block (ID: ' . $rec->block_name . ')';
                } else {
                    $row['block_name'] = '-';
                }

                if (isset($rec->plot_name) && array_key_exists($rec->plot_name, $plotNames)) {
                    $row['plot_name'] = $plotNames[$rec->plot_name];
                } else if (isset($rec->plot_name)) {
                    $row['plot_name'] = 'Unknown Plot (ID: ' . $rec->plot_name . ')';
                } else {
                    $row['plot_name'] = '-';
                }

                $row['date'] = Carbon::parse($row['date'])->format('d-m-Y');
                
                // Aggregate costs
                $totalStats['electricityCost'] += $row['electricity_cost_numeric'] ?? 0;
                $totalStats['hsdCost'] += $row['hsd_cost_numeric'] ?? 0;
                $totalStats['fertilizerCost'] += $row['fertilizer_cost_numeric'] ?? 0;
                $totalStats['maintenanceCost'] += $row['maintenance_cost_numeric'] ?? 0;
                $totalStats['manpowerCost'] += $row['manpower_cost_numeric'] ?? 0;
                $totalStats['totalCost'] += $row['total_cost_numeric'] ?? 0;

                unset(
                    $row['electricity_cost_numeric'],
                    $row['hsd_cost_numeric'],
                    $row['fertilizer_cost_numeric'],
                    $row['maintenance_cost_numeric'],
                    $row['manpower_cost_numeric'],
                    $row['total_cost_numeric'],
                    $row['seed_id'],
                    $row['area_covered']
                );

                $summary[] = $row;
            }
        }

        $yieldTotals = $this->getYieldTotals($blockFilter, $plotFilter, null, $selectedSiteId);

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $summary,
                'summary' => [
                    'totalCost' => number_format($totalStats['totalCost'], 2),
                    'electricityCost' => number_format($totalStats['electricityCost'], 2),
                    'hsdCost' => number_format($totalStats['hsdCost'], 2),
                    'fertilizerCost' => number_format($totalStats['fertilizerCost'], 2),
                    'maintenanceCost' => number_format($totalStats['maintenanceCost'], 2),
                    'manpowerCost' => number_format($totalStats['manpowerCost'], 2)
                ],
                'yieldTotals' => $yieldTotals,
                'recordCount' => count($summary)
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching history data',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function getSeasonInfo($season)
    {
        $seasons = [
            'kharif' => ['start_month' => 6, 'end_month' => 10],
            'rabi' => ['start_month' => 11, 'end_month' => 4],
            'zaid' => ['start_month' => 4, 'end_month' => 6]
        ];

        return $seasons[$season] ?? null;
    }

   private function processRecord($rec, $table, $machines, $tractors, $fertilizers, $seeds, $masterFertilizers, $masterManpowerRates, $selectedSiteId)
{
    $row = [
        'table' => $table,
        'activity_name' => $this->getActivityDisplayName($table)
    ];

    $electricityCost = 0;
    $hsdCost = 0;
    $fertilizerCost = 0;
    $maintenanceCost = 0;
    $manpowerCost = 0;
    $recordTotalCost = 0;

    foreach ($this->expectedColumns as $col) {
        $val = property_exists($rec, $col) ? $rec->$col : null;

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

        if ($col === 'area_acre') {
            $val = property_exists($rec, 'area_covered') ? $rec->area_covered : null;
        }

        // Handle seed_name for showing_oprations table
        if ($col === 'seed_name') {
            if ($table === 'showing_oprations' && property_exists($rec, 'seed_id') && !empty($rec->seed_id)) {
                $seedId = $rec->seed_id;
                if (isset($seeds[$seedId])) {
                    $val = $seeds[$seedId]->seed_name ?? 'Unknown';
                } else {
                    $val = 'Unknown';
                }
            } else {
                $val = $val ?? '-';
            }
        }

        $row[$col] = $val ?? '-';
    }

    // Store seed_id for later removal (if needed in response)
    if (property_exists($rec, 'seed_id')) {
        $row['seed_id'] = $rec->seed_id;
    }

    if (property_exists($rec, 'electricity_units') && property_exists($rec, 'cost_per_unit')) {
        $u = (float)($rec->electricity_units ?? 0);
        $r = (float)($rec->cost_per_unit ?? 0);
        $electricityCost = $u * $r;
    }

    if (property_exists($rec, 'hsd_consumption')) {
        $hsd = (float)($rec->hsd_consumption ?? 0);
        $latestRate = DB::table('diesel_stocks')
            ->where('site_id', $selectedSiteId)
            ->orderByDesc('date_of_purchase')
            ->value('rate_per_liter');
        $rate = is_numeric($latestRate) ? (float)$latestRate : 0;
        $hsdCost = $hsd * $rate;
    }

    if (property_exists($rec, 'major_maintenance')) {
        $maintenanceCost = (float)($rec->major_maintenance ?? 0);
    }

    $currentRecordManpowerCost = 0;
    foreach (['unskilled', 'semi_skilled_1', 'semi_skilled_2'] as $skillColumn) {
        if (property_exists($rec, $skillColumn) && is_numeric($rec->$skillColumn) && $rec->$skillColumn > 0) {
            $numberOfPersons = (int) $rec->$skillColumn;
            $categoryName = '';
            if ($skillColumn === 'unskilled') {
                $categoryName = 'Unskilled';
            } elseif ($skillColumn === 'semi_skilled_1') {
                $categoryName = 'Semi Skilled 1';
            } elseif ($skillColumn === 'semi_skilled_2') {
                $categoryName = 'Semi Skilled 2';
            }

            if (isset($masterManpowerRates[$categoryName])) {
                $ratePerPerson = (float)$masterManpowerRates[$categoryName];
                $currentRecordManpowerCost += ($numberOfPersons * $ratePerPerson);
            }
        }
    }
    $manpowerCost = $currentRecordManpowerCost;

    if ($table === 'fertilizer_soil_record' && property_exists($rec, 'fertilizer_id') && !empty($rec->fertilizer_id)) {
        $fertilizerDetails = json_decode($rec->fertilizer_id, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($fertilizerDetails)) {
            $currentRecordFertilizerCost = 0;

            foreach ($fertilizerDetails as $item) {
                $fertId = $item['id'] ?? null;
                $qty = $item['quantity'] ?? 0;

                if ($fertId && isset($masterFertilizers[$fertId])) {
                    $masterFert = (object)$masterFertilizers[$fertId];
                    $fertRate = $masterFert->rate ?? $this->costConfig['fertilizer_rate_per_kg'];
                    $currentRecordFertilizerCost += ((float)$qty * (float)$fertRate);
                }
            }
            $fertilizerCost = $currentRecordFertilizerCost;
        }
    }

    $recordTotalCost = $electricityCost + $hsdCost + $fertilizerCost + $maintenanceCost + $manpowerCost;

    $row['electricity_cost'] = $electricityCost > 0 ? number_format($electricityCost, 2) : '-';
    $row['hsd_cost'] = $hsdCost > 0 ? number_format($hsdCost, 2) : '-';
    $row['fertilizer_cost'] = $fertilizerCost > 0 ? number_format($fertilizerCost, 2) : '-';
    $row['maintenance_cost'] = $maintenanceCost > 0 ? number_format($maintenanceCost, 2) : '-';
    $row['manpower_cost'] = $manpowerCost > 0 ? number_format($manpowerCost, 2) : '-';
    $row['total_cost'] = number_format($recordTotalCost, 2);

    $row['yield_mt'] = property_exists($rec, 'yield_mt') && is_numeric($rec->yield_mt) && $rec->yield_mt > 0 ? number_format($rec->yield_mt, 2) : '-';

    $row['electricity_cost_numeric'] = $electricityCost;
    $row['hsd_cost_numeric'] = $hsdCost;
    $row['fertilizer_cost_numeric'] = $fertilizerCost;
    $row['maintenance_cost_numeric'] = $maintenanceCost;
    $row['manpower_cost_numeric'] = $manpowerCost;
    $row['total_cost_numeric'] = $recordTotalCost;

    return $row;
}

    // You need to define these helper methods or adjust them based on your actual class structure
     

    private function getActivityDisplayName($table)
    {
        foreach ($this->orderedActivities as $activity) {
            if ($activity['table'] === $table) {
                return $activity['display'];
            }
        }
        return ucfirst(str_replace('_', ' ', $table));
    }

    private function getYieldTotals($blockFilter, $plotFilter, $seedNameFilter, $siteId)
    {
        $totals = [
            'harvest' => 0,
            'silage' => 0,
            'hay' => 0
        ];

        if (Schema::hasTable('harvest_store_manage')) {
            $harvestQuery = DB::table('harvest_store_manage')
                ->where('product_id', 1)
                ->where('site_id', $siteId);

            if ($blockFilter) $harvestQuery->where('block_name', $blockFilter);
            if ($plotFilter) $harvestQuery->where('plot_name', $plotFilter);
            if ($seedNameFilter) $harvestQuery->where('seed_name', $seedNameFilter);

            $totals['harvest'] = $harvestQuery->sum('yield_mt') ?? 0;

            $silageQuery = DB::table('harvest_store_manage')
                ->where('product_id', 3)
                ->where('site_id', $siteId);

            if ($blockFilter) $silageQuery->where('block_name', $blockFilter);
            if ($plotFilter) $silageQuery->where('plot_name', $plotFilter);
            if ($seedNameFilter) $silageQuery->where('seed_name', $seedNameFilter);

            $totals['silage'] = $silageQuery->sum('yield_mt') ?? 0;

            $hayQuery = DB::table('harvest_store_manage')
                ->where('product_id', 2)
                ->where('site_id', $siteId);

            if ($blockFilter) $hayQuery->where('block_name', $blockFilter);
            if ($plotFilter) $hayQuery->where('plot_name', $plotFilter);
            if ($seedNameFilter) $hayQuery->where('seed_name', $seedNameFilter);

            $totals['hay'] = $hayQuery->sum('yield_mt') ?? 0;
        }

        return [
            'harvest_total' => number_format($totals['harvest'], 2),
            'silage_total' => number_format($totals['silage'], 2),
            'hay_total' => number_format($totals['hay'], 2),
            'grand_total' => number_format($totals['harvest'] + $totals['silage'] + $totals['hay'], 2)
        ];
    }

    /**
     * Get all plots for a given block and site.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlotsByBlock(Request $request)
    {
        try {
            $blockName = $request->input('block');
            $siteId = $request->input('site_id');
            $plots = [];

            if (!empty($blockName)) {
                foreach ($this->activityTables as $table) {
                    if (!Schema::hasTable($table)) continue;

                    if (Schema::hasColumn($table, 'block_name') && Schema::hasColumn($table, 'plot_name')) {
                        $query = DB::table($table)
                            ->where('block_name', $blockName)
                            ->distinct()
                            ->pluck('plot_name');

                        if ($siteId && Schema::hasColumn($table, 'site_id')) {
                            $query = DB::table($table)
                                ->where('block_name', $blockName)
                                ->where('site_id', $siteId)
                                ->distinct()
                                ->pluck('plot_name');
                        }

                        $blockPlots = $query->toArray();
                        $plots = array_merge($plots, $blockPlots);
                    }
                }

                $plots = array_unique($plots);
                sort($plots);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'plots' => array_values($plots)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching plots',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function storeConsumption(Request $request)
{
    try {
        $user = Auth::user();
        $site_id = $user->site_id;

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ Request Validation
        $validator = Validator::make($request->all(), [
             
            'diesel_consumption' => 'required|numeric|min:0.01',
            'date_of_entry' => 'required|date',
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $requestedConsumption = $request->diesel_consumption;
       

        // 🔎 Check available stock
        $availableStock = DB::table('diesel_stocks')
            ->where('site_id', $site_id)
            ->sum('diesel_stock');
 
        if ($availableStock < $requestedConsumption) {
            return response()->json([
                'status' => false,
                'error' => 'INSUFFICIENT_DIESEL',
                'message' => 'Not enough diesel stock available.',
                'requested_liters' => $requestedConsumption,
                'available_liters' => $availableStock,
                'shortage' => $requestedConsumption - $availableStock
            ], 400);
        }

        // 🔄 FIFO Logic
        DB::beginTransaction();

        $remainingConsumption = $requestedConsumption;
        $consumptionDetails = [];
        $totalCost = 0;

        $stocks = DB::table('diesel_stocks')
            ->where('site_id', $site_id)
            ->where('diesel_stock', '>', 0)
            ->orderBy('date_of_purchase', 'asc')
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingConsumption <= 0) break;

            $litersTaken = min($stock->diesel_stock, $remainingConsumption);

            DB::table('diesel_stocks')->where('id', $stock->id)->update([
                'diesel_stock' => $stock->diesel_stock - $litersTaken,
                'diesel_consumption' => ($stock->diesel_consumption ?? 0) + $litersTaken,
                'updated_at' => now()
            ]);

            $cost = $litersTaken * $stock->rate_per_liter;
            $totalCost += $cost;
            $previousStock = $stock->diesel_stock;
            $remainingConsumption -= $litersTaken;

            $consumptionDetails[] = [
                'stock_id'     => $stock->id,
                'liters_used'  => $litersTaken,
                'rate'         => $stock->rate_per_liter,
                'cost'         => $cost,
                'previous_stock' => $previousStock,
                'site_id' => $site_id,
                 
            ];
        }

        // 📝 Insert Records
        foreach ($consumptionDetails as $detail) {
            DB::table('diesel_consumption')->insert([
                'stock_id'   => $detail['stock_id'],
                'liters_used'=> $detail['liters_used'],
                'rate'       => $detail['rate'],
                'cost'       => $detail['cost'],
                'date'       => $request->date_of_entry,
                'activity'   => $request->remark,
                'user_id'    => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('diesel_stock_history')->insert([
                'diesel_stock_id' => $detail['stock_id'],
                'site_id'        => $detail['site_id'],
                'type'           => 'Consumption',
                'note'           => $request->remark ?? 'Manual Consumption Entry',
                'date_of_entry'  => $request->date_of_entry,
                'stock_before_addition' => $detail['previous_stock'],
                'consumed_quantity'     => $detail['liters_used'],
                'rate_per_liter'        => $detail['rate'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Diesel consumption recorded successfully.',
            'total_cost' => $totalCost,
            'details' => $consumptionDetails
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
