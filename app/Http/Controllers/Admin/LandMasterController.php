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
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Validator;
class LandMasterController extends Controller
{
    public function indexblock(Request $request)
{
    $user = Auth::user();
    $userSiteId = $user->site_id;

    // Fetch all sites (for dropdown if admin)
    $sites = DB::table('master_sites')
        ->select('id', 'site_name')
        ->get();

    // Search filter
    $search = $request->input('search');

    // Fetch blocks related to user's site
    $masterBlocks = DB::table('blocks')
        ->where('site_id', $userSiteId)
        ->when($search, fn($q) => $q->where('block_name', 'like', "%{$search}%"))
        ->orderByDesc('id')
        ->get();

    return view('admin.master-block', compact('masterBlocks', 'search', 'sites'));
}
    
public function store(Request $request)
{
    
  
    $validator = Validator::make($request->all(), [
        'block_name' => [
            'required', 'string', 'max:255',
            Rule::unique('blocks')->where(fn($q) => $q->where('site_id', $request->site_id))
        ],
        'site_id'    => 'required|integer',
        'plots'      => 'required|array|min:1',
        'plots.*'    => 'required|string|max:255',
        'areas'      => 'required|array|min:1',
        'areas.*'    => 'required|numeric|min:0',
        'latitudes'  => 'required|array|min:1',
        'latitudes.*'=> 'nullable|string|max:255',
        'longitudes' => 'required|array|min:1',
        'longitudes.*'=> 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    try {
        DB::beginTransaction();

        //  Create block
        $blockId = DB::table('blocks')->insertGetId([
            'block_name' => $request->block_name,
            'site_id'    => $request->site_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seasonsInfo = [
            'Rabi'   => ['start_month' => 10, 'end_month' => 3],
            'Kharif' => ['start_month' => 6,  'end_month' => 10],
            'Zaid'   => ['start_month' => 3,  'end_month' => 6],
        ];

        foreach ($request->plots as $i => $plotName) {
            $plotArea = $request->areas[$i] ?? null;
            $latitude = $request->latitudes[$i] ?? null;
            $longitude = $request->longitudes[$i] ?? null;

            //  Insert plot with lat-long
            $plotId = DB::table('master_plots')->insertGetId([
                'block_id'   => $blockId,
                'plot_name'  => $plotName,
                'area'       => $plotArea,
                'lattitude'   => $latitude,
                'longitude'  => $longitude,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createCurrentSeason($blockId, $plotId, $seasonsInfo);
        }

        DB::commit();
        return redirect()->route('master.blocks')->with('success', 'Block and plots are created successfully');
    } catch (\Exception $e) {
        DB::rollBack();
        dd($e->getMessage());
        Log::error('❌ Block Store Error: ' . $e->getMessage());
        return back()->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}
        public function edit($id)
        {
            // Fetch block
            $block = DB::table('blocks')->where('id', $id)->first();
         
            if (!$block) {
                return response()->json(['error' => 'Block not found'], 404);
            }
        
            // Fetch all plots of this block
            $plots = DB::table('master_plots')
                ->where('block_id', $id)
                ->select('id', 'plot_name', 'area', 'lattitude', 'longitude')
                ->get();
           // print_r($plots); 
            return response()->json([
                'block' => $block,
                'plots' => $plots
            ]);
        }

    
 public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'block_name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('blocks')->where(function ($query) use ($request) {
                return $query->where('site_id', $request->site_id);
            })->ignore($id),
        ],
        'site_id' => 'required|integer',
        'plots'   => 'required|array|min:1',
        'plots.*' => 'required|string|max:255',
        'areas'   => 'required|array|min:1',
        'areas.*' => 'required|string|max:255',
        'latitudes'  => 'nullable|array',
        'latitudes.*'=> 'nullable|string|max:255',
        'longitudes' => 'nullable|array',
        'longitudes.*'=> 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        dd($validator->errors());
        return redirect()->back()->with('error', $validator->errors());
    }

    try {
        DB::beginTransaction();

        // 🧩 Update block
        DB::table('blocks')->where('id', $id)->update([
            'block_name' => $request->block_name,
            'site_id'    => $request->site_id,
            'updated_at' => now(),
        ]);

        // 🌾 Season info
        $seasonsInfo = [
            'Rabi'   => ['start_month' => 10, 'end_month' => 3],
            'Kharif' => ['start_month' => 6,  'end_month' => 10],
            'Zaid'   => ['start_month' => 3,  'end_month' => 6],
        ];

        $currentMonth = now()->month;
        $currentYear  = now()->year;
        $plotIds = $request->plot_ids ?? [];

        // 🧱 Update or create plots
        foreach ($request->plots as $index => $plotName) {
            $plotArea   = $request->areas[$index] ?? null;
            $latitude   = $request->latitudes[$index] ?? null;
            $longitude  = $request->longitudes[$index] ?? null;
            $plotId     = $plotIds[$index] ?? null;

            if ($plotId) {
                // 🔄 Update existing plot (include lat-long)
                DB::table('master_plots')->where('id', $plotId)->update([
                    'plot_name'  => $plotName,
                    'area'       => $plotArea,
                    'lattitude'   => $latitude,
                    'longitude'  => $longitude,
                    'updated_at' => now(),
                ]);
            } else {
                // ➕ Insert new plot (with lat-long)
                $plotId = DB::table('master_plots')->insertGetId([
                    'block_id'   => $id,
                    'plot_name'  => $plotName,
                    'area'       => $plotArea,
                    'lattitude'   => $latitude,
                    'longitude'  => $longitude,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 🌱 Determine live season dynamically
                $seasonInserted = false;

                foreach ($seasonsInfo as $seasonName => $months) {
                    if ($months['start_month'] <= $months['end_month']) {
                        // Same year (e.g., Kharif)
                        if ($currentMonth >= $months['start_month'] && $currentMonth <= $months['end_month']) {
                            $startMonth = $months['start_month'];
                            $endMonth   = $months['end_month'];
                            $startDate  = $currentYear . '-' . sprintf('%02d', $startMonth) . '-01';
                            $endDate    = $currentYear . '-' . sprintf('%02d', $endMonth) . '-01';
                            $seasonInserted = true;
                        }
                    } else {
                        // Across year-end (e.g., Rabi)
                        if ($currentMonth >= $months['start_month'] || $currentMonth <= $months['end_month']) {
                            $startMonth = $months['start_month'];
                            $endMonth   = $months['end_month'];
                            $startYear  = ($currentMonth >= $months['start_month']) ? $currentYear : $currentYear - 1;
                            $endYear    = ($currentMonth >= $months['start_month']) ? $currentYear + 1 : $currentYear;
                            $startDate  = $startYear . '-' . sprintf('%02d', $startMonth) . '-01';
                            $endDate    = $endYear . '-' . sprintf('%02d', $endMonth) . '-01';
                            $seasonInserted = true;
                        }
                    }

                    if ($seasonInserted) {
                        try {
                            DB::table('seasons')->insert([
                                'block_id'      => $id,
                                'plot_id'       => $plotId,
                                'name'          => $seasonName,
                                'start_date'    => $startDate,
                                'end_date'      => $endDate,
                                'start_month'   => $startMonth,
                                'end_month'     => $endMonth,
                                'status'        => 'active',
                                'year'          => ($startMonth >= 4 ? $currentYear : $currentYear - 1) . '-' .
                                                   ($startMonth >= 4 ? $currentYear + 1 : $currentYear),
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ]);
                            Log::info("✅ Season '$seasonName' created for new plot ID $plotId (block $id)");
                        } catch (\Exception $ex) {
                            Log::error("❌ Season insert failed for plot $plotId: " . $ex->getMessage());
                        }
                        break; // only one season per plot
                    }
                }

                if (!$seasonInserted) {
                    Log::warning("⚠️ No season determined for new plot ID: $plotId (month: $currentMonth)");
                }
            }
        }

        DB::commit();

        return redirect()->route('master.blocks')
            ->with('success', 'Block and plots updated successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Block Update Error: ' . $e->getMessage());
       // dd($e->getMessage());
        return redirect()->back()->with('error', 'Block record could not be updated');
    }
}


private function createCurrentSeason($blockId, $plotId, $seasonsInfo)
{
    $currentMonth = now()->month;
    $currentYear  = now()->year;

    foreach ($seasonsInfo as $seasonName => $months) {
        if ($months['start_month'] <= $months['end_month']) {
            if ($currentMonth >= $months['start_month'] && $currentMonth <= $months['end_month']) {
                $startDate = Carbon::create($currentYear, $months['start_month'], 1);
                $endDate   = Carbon::create($currentYear, $months['end_month'], 1);
            } else {
                continue;
            }
        } else {
            if ($currentMonth >= $months['start_month'] || $currentMonth <= $months['end_month']) {
                $startYear = $currentMonth >= $months['start_month'] ? $currentYear : $currentYear - 1;
                $endYear   = $currentMonth >= $months['start_month'] ? $currentYear + 1 : $currentYear;
                $startDate = Carbon::create($startYear, $months['start_month'], 1);
                $endDate   = Carbon::create($endYear, $months['end_month'], 1);
            } else {
                continue;
            }
        }

        DB::table('seasons')->insert([
            'block_id'    => $blockId,
            'plot_id'     => $plotId,
            'name'        => $seasonName,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'start_month' => $months['start_month'],
            'end_month'   => $months['end_month'],
            'status'      => 'active',
            'year'        => ($months['start_month'] >= 4 ? $currentYear : $currentYear - 1) . '-' .
                             ($months['start_month'] >= 4 ? $currentYear + 1 : $currentYear),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return; // stop after inserting one current season
    }

    Log::warning("⚠️ No season found for plot $plotId (month: $currentMonth)");
}


}