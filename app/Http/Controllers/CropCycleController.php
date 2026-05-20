<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ContactFarming;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class CropCycleController extends Controller
{
    // public function index()
    // {
       
    //   $userSiteId = Auth::user()->site_id;    
    // $records = DB::table('crop_cycles as cc')
    //     ->leftJoin('master_seed as ms', 'cc.seed_id', '=', 'ms.id')
    //     ->leftJoin('seed as s', 'ms.seed_id', '=', 's.id')
    //     ->leftJoin('users as u', 'cc.user_id', '=', 'u.id')
    //     ->leftJoin('blocks', 'cc.block', '=', 'blocks.id')
    //     ->leftJoin('master_plots', 'cc.plot', '=', 'master_plots.id')
    //     // Join Harvesting
    //     ->leftJoin('harvesting_update as hu', function($join) {
    //         $join->on('cc.block', '=', 'hu.block_name')
    //              ->on('cc.plot', '=', 'hu.plot_name');
    //     })
    //     // Join Sowing
    //     ->leftJoin('showing_oprations as so', function($join) {
    //         $join->on('cc.block', '=', 'so.block_name')
    //              ->on('cc.plot', '=', 'so.plot_name');
    //     })
    //     ->select(
    //         'cc.*',
    //         'u.name as user_name',
    //         'blocks.block_name as block_name',
    //         'master_plots.plot_name as plot_name',
    //         's.name as seed_name',
    //         // First & Last Sowing
    //         DB::raw('MIN(so.date) as first_sowing_date'),
    //         DB::raw('MAX(so.date) as last_sowing_date'),
    //         // First & Last Harvest
    //         DB::raw('MIN(hu.date) as first_harvest_date'),
    //         DB::raw('MAX(hu.date) as last_harvest_date'),
    //         DB::raw('SUM(hu.yield_mt) as total_yield')
    //     )
    //     ->where('cc.site_id',$userSiteId)
    //     ->groupBy('cc.id', 'u.name')
    //     ->orderBy('cc.created_at', 'desc')
    //     ->get();

    //  return view('pages.crop-cycle', compact('records'));
    // }
    
    public function index()
    {
        $userSiteId = Auth::user()->site_id;
    
$records = DB::table('seasons')
    ->leftJoin('blocks', 'seasons.block_id', '=', 'blocks.id')
    ->leftJoin('master_plots', 'seasons.plot_id', '=', 'master_plots.id')
    ->leftJoin(DB::raw('(
        SELECT 
            plot_name,
            season_id,
            MIN(date) AS first_sowing_date,
            MAX(date) AS last_sowing_date
        FROM showing_oprations
        GROUP BY plot_name, season_id
    ) as sowing_summary'),
    function ($join) {
        $join->on('seasons.id', '=', 'sowing_summary.season_id')
             ->on('seasons.plot_id', '=', 'sowing_summary.plot_name');   // 🔥 IMPORTANT
    })
    ->leftJoin(DB::raw('(
        SELECT 
            plot_name,
            season_id,
            MIN(date) AS first_harvest_date,
            MAX(date) AS last_harvest_date,
            SUM(yield_mt) AS total_yield
        FROM harvesting_update
        GROUP BY plot_name, season_id
    ) as harvest_summary'),
    function ($join) {
        $join->on('seasons.id', '=', 'harvest_summary.season_id')
             ->on('seasons.plot_id', '=', 'harvest_summary.plot_name');  // 🔥 IMPORTANT
    })
    ->select(
        'seasons.id',
        'seasons.name as season_name',
        'seasons.start_date',
        'seasons.status',
        'seasons.end_date as closed_date',
        'blocks.block_name',
        'master_plots.plot_name',
        'sowing_summary.first_sowing_date',
        'sowing_summary.last_sowing_date',
        'harvest_summary.first_harvest_date',
        'harvest_summary.last_harvest_date',
        'harvest_summary.total_yield'
    )
    ->where('seasons.site_id', $userSiteId)
    ->orderBy('seasons.created_at', 'desc')
    ->get();

    
        return view('pages.crop-cycle', compact('records'));
    }


    
    
    public function season()
    {
      $seasons = DB::table('seasons')
            ->join('blocks', 'seasons.block_id', '=', 'blocks.id')
            ->join('master_plots', 'seasons.plot_id', '=', 'master_plots.id')
            ->select(
                'seasons.*',
                'blocks.block_name',
                'master_plots.plot_name'
            )
            ->orderBy('seasons.created_at', 'desc')
            ->get();

        return view('pages.season', compact('seasons'));
    }
   public function close($id)
    {
        $cycle = DB::table('seasons')->where('id', $id)->first();
    
        if (!$cycle) {
            return response()->json(['status' => 'error', 'message' => 'Cycle not found'], 404);
        }
    
        if ($cycle->status === 'Closed') {
            return response()->json(['status' => 'error', 'message' => 'Already closed'], 400);
        }
    
         
        DB::table('seasons')
            ->where('id', $id)
            ->update([
                'status' => 'Closed',
                'end_date' => now()
            ]);
    
       
        $seasonOrder = [
            'Kharif' => 'Rabi',
            'Rabi'   => 'Summer',
            'Summer' => 'Kharif'
        ];
    
         
        $nextSeasonName = $seasonOrder[$cycle->name] ?? null;
    
        if ($nextSeasonName) {
            $nextYear = ($cycle->name === 'Summer') ? $cycle->year + 1 : $cycle->year;
    
            // Define months for next season
            $seasonMonths = [
                'Kharif' => ['start_month' => 6, 'end_month' => 10],
                'Rabi'   => ['start_month' => 11, 'end_month' => 3],
                'Summer' => ['start_month' => 4, 'end_month' => 5],
            ];
    
            $months = $seasonMonths[$nextSeasonName];
    
            DB::table('seasons')->insert([
                'name'         => $nextSeasonName,
                'year'         => $nextYear,
                'start_month'  => $months['start_month'],
                'end_month'    => $months['end_month'],
                'status'       => 'Active',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    
        return response()->json(['status' => 'success', 'message' => 'Cycle closed and next season created automatically']);
    }


public function closeSeason($id)
{
    try {
        $season = DB::table('seasons')->where('id', $id)->first();

        if (!$season) {
            if (request()->ajax()) {
                return response()->json(['message' => 'Season not found.'], 404);
            }
            return redirect()->back()->with('error', 'Season not found.');
        }

        DB::beginTransaction();

        // 🛑 Close current season
        DB::table('seasons')->where('id', $id)->update([
            'status'     => 'closed',
            'updated_at' => now(),
        ]);

        // 🌾 Next-season mapping
        $nextSeasonMap = [
            'Rabi'   => 'Zaid',
            'Zaid'   => 'Kharif',
            'Kharif' => 'Rabi',
        ];

        $nextSeasonName = $nextSeasonMap[$season->name] ?? null;

        if (!$nextSeasonName) {
            DB::commit();

            if (request()->ajax()) {
                return response()->json(['message' => 'Season closed. No next season mapping found.']);
            }
            return redirect()->back()->with('success', 'Season closed. No next season mapping found.');
        }

        // 🌱 Season months info
        $seasonsInfo = [
            'Rabi'   => ['start_month' => 10, 'end_month' => 3],
            'Kharif' => ['start_month' => 6,  'end_month' => 10],
            'Zaid'   => ['start_month' => 3,  'end_month' => 6],
        ];

        $info = $seasonsInfo[$nextSeasonName];
        $currentYear = now()->year;

        // ➕ Compute start and end dates using Carbon
        if ($info['start_month'] <= $info['end_month']) {
            $startDate = \Carbon\Carbon::create($currentYear, $info['start_month'], 1)->format('Y-m-d');
            $endDate   = \Carbon\Carbon::create($currentYear, $info['end_month'], 1)->format('Y-m-d');
        } else {
            $startDate = \Carbon\Carbon::create($currentYear, $info['start_month'], 1)->format('Y-m-d');
            $endDate   = \Carbon\Carbon::create($currentYear + 1, $info['end_month'], 1)->format('Y-m-d');
        }

        $userSiteId = \Auth::user()->site_id;

        // ➕ Create next season
        DB::table('seasons')->insert([
            'block_id'    => $season->block_id,
            'plot_id'     => $season->plot_id,
            'name'        => $nextSeasonName,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'start_month' => $info['start_month'],
            'end_month'   => $info['end_month'],
            'status'      => 'active',
            'year'        => $currentYear . '-' . ($currentYear + 1),
            'site_id'     => $userSiteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::commit();

        if (request()->ajax()) {
            return response()->json([
                'message' => "Season '{$season->name}' closed and new season '{$nextSeasonName}' created.",
            ]);
        }

        return redirect()->back()->with('success', "Season '{$season->name}' closed and new season '{$nextSeasonName}' created.");
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('❌ Season close failed: ' . $e->getMessage());

        if (request()->ajax()) {
            return response()->json(['message' => 'Something went wrong while closing the season.'], 500);
        }

        return redirect()->back()->with('error', 'Something went wrong while closing the season.');
    }
}


   public function details($id)
{
    // 🔹 Fetch the selected season
    $season = DB::table('seasons as s')
        ->leftJoin('blocks', 's.block_id', '=', 'blocks.id')
        ->leftJoin('master_plots', 's.plot_id', '=', 'master_plots.id')
        ->select(
            's.*',
            'blocks.block_name',
            'master_plots.plot_name'
        )
        ->where('s.id', $id)
        ->first();

    if (!$season) {
        return response()->json(['error' => 'Season not found'], 404);
    }

    // 🌱 Fetch sowing details (with seed info)
    $sowingDetails = DB::table('showing_oprations as so')
        ->leftJoin('master_seed as ms', 'so.seed_id', '=', 'ms.id')
        ->leftJoin('seed as sd', 'ms.seed_id', '=', 'sd.id')
        ->select(
            'so.date',
            'so.area_covered as area_used',
            'so.seed_consumption as seed_quantity',
            'sd.name as seed_name',
            'ms.variety_of_seed'
        )
        ->where('so.season_id', $season->id)
        ->orderBy('so.date', 'asc')
        ->get();

    // 🌾 Fetch harvesting details
    $harvestDetails = DB::table('harvesting_update')
        ->where('season_id', $season->id)
        ->select('date', 'area_covered as area_used', 'yield_mt')
        ->orderBy('date', 'asc')
        ->get();

    return response()->json([
        'season' => $season,
        'sowing' => $sowingDetails,
        'harvesting' => $harvestDetails,
    ]);
}





    
}
