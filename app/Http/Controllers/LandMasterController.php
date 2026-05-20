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
        $role = $user->role ?? null;
        // some apps store site id in site_name field; keep existing behaviour
        $userSiteId = $user->site_name ?? null;


        $search = $request->input('site_id');

        if ($role == 1) {
            // Admin: show all sites and all blocks (optionally filtered by search)
            $sites = DB::table('master_sites')->select('id', 'site_name')->get();

            $masterBlocks = DB::table('blocks')
                ->when($search, function ($q) use ($search) {
                    $q->where('site_id', 'like', "%{$search}%");
                })
                ->orderBy('id', 'desc')
                ->get();
        } else {
            // Non-admin: only show blocks for the user's site
            $sites = DB::table('master_sites')->select('id', 'site_name')->where('id', $userSiteId)->get();

            $masterBlocks = DB::table('blocks')
                ->where('site_id', $userSiteId)
                ->when($search, function ($q) use ($search) {
                    $q->where('site_id', 'like', "%{$search}%");
                })
                ->orderBy('id', 'desc')
                ->get();
        }

        return view('admin.master-block', compact('masterBlocks', 'search', 'sites'));
    }
//   public function store(Request $request)
//         {
//             // Validate the incoming request data.
//           $validator = Validator::make($request->all(), [
//                 'block_name'    => [
//                     'required',
//                     'string',
//                     'max:255',
//                     Rule::unique('blocks')->where(function ($query) use ($request) {
//                         return $query->where('site_id', $request->site_id);
//                     })
//                 ],
//                 'site_id' => 'required|integer',
//                 'plots'   => 'required|array|min:1',
//                 'plots.*' => 'required|string|max:255',
//                 'areas'   => 'required|array|min:1',
//                 'areas.*' => 'required|string|max:255',
//             ]);

//             if ($validator->fails()) {
//               return redirect()
//                 ->back()
//                 ->with('errors', $validator->errors());
//             }

//             try {
//                 DB::beginTransaction();

//                 // Insert block
//                 $blockId = DB::table('blocks')->insertGetId([
//                     'block_name' => $request->block_name,
//                     'site_id'    => $request->site_id,
//                     'created_at' => now(),
//                     'updated_at' => now(),
//                 ]);

//                 // Insert plots with areas
//                 foreach ($request->plots as $index => $plotName) {
//                     $plotArea = $request->areas[$index] ?? null; // match area by index

//                     DB::table('master_plots')->insert([
//                         'block_id'   => $blockId,
//                         'plot_name'  => $plotName,
//                         'area'  => $plotArea, // new column in DB (make sure exists)
//                       // 'site_id'    => $request->site_id,
//                         'created_at' => now(),
//                         'updated_at' => now(),
//                     ]);
//                 }

//                 DB::commit();

//                 return redirect()
//                     ->route('master.blocks') // ✅ fixed wrong route
//                     ->with('success', 'Block and plots created successfully!');
//             } catch (\Exception $e) {
//                 DB::rollBack();
//                 dd($e->getMessage());
//                 Log::error('Block Store Error: ' . $e->getMessage());

//                 return redirect()
//                     ->back()
//                     ->with('error', 'Block record is not created');
//             }
//         }

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'block_name'    => [
            'required',
            'string',
            'max:255',
            Rule::unique('blocks')->where(function ($query) use ($request) {
                return $query->where('site_id', $request->site_id);
            })
        ],
        'site_id' => 'required|integer',
        'plots'   => 'required|array|min:1',
        'plots.*' => 'required|string|max:255',
        'areas'   => 'required|array|min:1',
        'areas.*' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->with('errors', $validator->errors());
    }

    try {
        DB::beginTransaction();

        // 1️⃣ Insert block
        $blockId = DB::table('blocks')->insertGetId([
            'block_name' => $request->block_name,
            'site_id'    => $request->site_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2️⃣ Define seasons
        $seasonsInfo = [
            'Rabi'   => ['start_month' => 10, 'end_month' => 3], // Oct-Mar
            'Kharif' => ['start_month' => 6,  'end_month' => 10], // Jun-Oct
            'Zaid'   => ['start_month' => 3,  'end_month' => 6], // Mar-Jun
        ];

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // 3️⃣ Insert plots + create season
        foreach ($request->plots as $index => $plotName) {
            $plotArea = $request->areas[$index] ?? null;

            // Insert plot
            $plotId = DB::table('master_plots')->insertGetId([
                'block_id'   => $blockId,
                'plot_name'  => $plotName,
                'area'       => $plotArea,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Determine current season dynamically
            $seasonInserted = false;
            foreach ($seasonsInfo as $seasonName => $months) {
                if ($months['start_month'] <= $months['end_month']) {
                    // Same year
                    if ($currentMonth >= $months['start_month'] && $currentMonth <= $months['end_month']) {
                        $startMonth = $months['start_month'];
                        $endMonth   = $months['end_month'];
                        $startDate  = $currentYear . '-' . sprintf('%02d', $startMonth) . '-01';
                        $endDate    = $currentYear . '-' . sprintf('%02d', $endMonth) . '-01';
                        $seasonInserted = true;
                    }
                } else {
                    // Across year-end (e.g., Oct-Mar)
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
                    DB::table('seasons')->insert([
                        'block_id'      => $blockId,
                        'plot_id'       => $plotId,
                        'name'          => $seasonName,
                        'start_date'    => $startDate,
                        'end_date'      => $endDate,
                        'start_month'   => $startMonth,
                        'end_month'     => $endMonth,
                        'status'        => 'active',
                      //  'financial_year'=> ($startMonth >= 4 ? $currentYear : $currentYear - 1) . '-' . ($startMonth >= 4 ? $currentYear + 1 : $currentYear),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    break; // insert only one season per plot
                }
            }

            if (!$seasonInserted) {
                 
                Log::error("No season determined for plot ID: $plotId, current month: $currentMonth");
            }
        }

        DB::commit();

        return redirect()->route('master.blocks')->with('success', 'Block, plots, and live seasons created successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Block Store Error: ' . $e->getMessage());
        dd($e->getMessage());
        return redirect()->back()->with('error', 'Block record is not created');
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
                ->select('id', 'plot_name', 'area')
                ->get();
           // print_r($plots);
            return response()->json([
                'block' => $block,
                'plots' => $plots
            ]);
        }


//   public function update(Request $request, $id)
//     {
//         $validator = Validator::make($request->all(), [
//             'block_name'    => [
//                 'required',
//                 'string',
//                 'max:255',
//                 Rule::unique('blocks')->where(function ($query) use ($request) {
//                     return $query->where('site_id', $request->site_id);
//                 })->ignore($id) // yaha current block ignore hoga
//             ],
//             'site_id' => 'required|integer',
//             'plots'   => 'required|array|min:1',
//             'plots.*' => 'required|string|max:255',
//             'areas'   => 'required|array|min:1',
//             'areas.*' => 'required|string|max:255',
//         ]);

//         if ($validator->fails()) {
//             // return response()->json([
//             //     'success' => false,
//             //     'errors' => $validator->errors()
//             // ], 422);
//             return redirect()
//                 ->back()
//                 ->with('error', $validator->errors());
//         }

//         try {
//             DB::beginTransaction();

//             // Update block info
//             DB::table('blocks')->where('id', $id)->update([
//                 'block_name' => $request->block_name,
//                 'site_id'    => $request->site_id,
//                 'updated_at' => now(),
//             ]);

//             $plotIds = $request->plot_ids ?? [];

//             foreach ($request->plots as $index => $plotName) {
//                 $plotArea = $request->areas[$index] ?? null;
//                 $plotId   = $plotIds[$index] ?? null;

//                 if ($plotId) {
//                     // Update existing plot
//                     DB::table('master_plots')->where('id', $plotId)->update([
//                         'plot_name'  => $plotName,
//                         'area'       => $plotArea,
//                         'updated_at' => now(),
//                     ]);
//                 } else {
//                     // Insert new plot
//                     DB::table('master_plots')->insert([
//                         'block_id'   => $id,
//                         'plot_name'  => $plotName,
//                         'area'       => $plotArea,
//                         'created_at' => now(),
//                         'updated_at' => now(),
//                     ]);
//                 }
//             }

//             DB::commit();

//             return redirect()
//                 ->route('master.blocks')
//                 ->with('success', 'Block and plots updated successfully!');
//         } catch (\Exception $e) {
//             DB::rollBack();
//             Log::error('Block Update Error: ' . $e->getMessage());

//             return redirect()
//                 ->back()
//                 ->with('error', 'Block record could not be updated');
//         }
//     }

public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'block_name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('blocks')->where(function ($query) use ($request) {
                return $query->where('site_id', $request->site_id);
            })->ignore($id) // ignore current block
        ],
        'site_id' => 'required|integer',
        'plots'   => 'required|array|min:1',
        'plots.*' => 'required|string|max:255',
        'areas'   => 'required|array|min:1',
        'areas.*' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return redirect()
            ->back()
            ->with('error', $validator->errors());
    }

    try {
        DB::beginTransaction();

        // 1️⃣ Update block
        DB::table('blocks')->where('id', $id)->update([
            'block_name' => $request->block_name,
            'site_id'    => $request->site_id,
            'updated_at' => now(),
        ]);

        // 2️⃣ Seasons calendar
        $seasonsCalendar = [
            'Rabi'   => ['start' => '2025-10-15', 'end' => '2026-03-15'],
            'Kharif' => ['start' => '2025-06-01', 'end' => '2025-10-15'],
            'Zaid'   => ['start' => '2025-03-16', 'end' => '2025-05-31'],
        ];
        $today = now()->toDateString();

        $plotIds = $request->plot_ids ?? [];

        foreach ($request->plots as $index => $plotName) {
            $plotArea = $request->areas[$index] ?? null;
            $plotId   = $plotIds[$index] ?? null;

            if ($plotId) {
                // Update existing plot
                DB::table('master_plots')->where('id', $plotId)->update([
                    'plot_name'  => $plotName,
                    'area'       => $plotArea,
                    'updated_at' => now(),
                ]);
            } else {
                // Insert new plot
                $newPlotId = DB::table('master_plots')->insertGetId([
                    'block_id'   => $id,
                    'plot_name'  => $plotName,
                    'area'       => $plotArea,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // ✅ Create first live season for new plot
                foreach ($seasonsCalendar as $seasonName => $dates) {
                    if ($today >= $dates['start'] && $today <= $dates['end']) {
                        DB::table('seasons')->insert([
                            'block_id'    => $id,
                            'plot_id'     => $newPlotId,
                            'name' => $seasonName,
                            'start_date'  => $dates['start'],
                            'end_date'    => $dates['end'],
                            'status'      => 'active',
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        break; // only one live season per plot
                    }
                }
            }
        }

        DB::commit();

        return redirect()
            ->route('master.blocks')
            ->with('success', 'Block, plots, and live seasons updated successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Block Update Error: ' . $e->getMessage());

        return redirect()
            ->back()
            ->with('error', 'Block record could not be updated');
    }
}



}
