<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactFarming;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactFarmingController extends Controller
{
    public function create()
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to access this page.');
            }

            $userSiteId = Auth::user()->site_id;
            
            if (!$userSiteId) {
                return redirect()->back()->with('error', 'No site assigned to your account. Please contact administrator.');
            }

            $records = DB::table('contact_farming as cf')
                ->leftJoin('blocks as mb', 'cf.block_id', '=', 'mb.id')
                ->leftJoin('master_plots as mp', 'cf.plot_id', '=', 'mp.id')
                ->where('mb.site_id', $userSiteId)
                ->select(
                    'cf.*',
                    'mb.block_name',
                    'mp.plot_name',
                    'mp.area as plot_area'
                )
                ->orderBy('cf.created_at', 'desc')
                ->paginate(10);

             $blocks = DB::table('master_land as ml')
            ->join('blocks as b', 'ml.block_name', '=', 'b.id')
            ->where('ml.site_id', $userSiteId)
            ->select('b.id', 'b.block_name')
            ->distinct()
            ->get();

            $allPlots = DB::table('master_plots as mp')
                ->join('blocks as mb', 'mp.block_id', '=', 'mb.id')
                ->where('mb.site_id', $userSiteId)
                ->select('mp.id', 'mp.plot_name', 'mp.area', 'mp.block_id')
                ->orderBy('mp.plot_name')
                ->get()
                ->groupBy('block_id');

            $seeds = DB::table('master_seed')
                ->where('site_id', $userSiteId)
                ->select('seed_name')
                ->distinct()
                ->orderBy('seed_name')
                ->get();

            $allSeedVarieties = DB::table('master_seed')
                ->where('site_id', $userSiteId)
                ->select('seed_name', 'variety_of_seed')
                ->orderBy('variety_of_seed')
                ->get()
                ->groupBy('seed_name');

            return view('pages.contact-farming-form', compact('records', 'blocks', 'allPlots', 'seeds', 'allSeedVarieties'));

        } catch (\Exception $e) {
            Log::error('Error in ContactFarmingController@create: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the page. Please try again.');
        }
    }

    public function store(Request $request)
    {
        //dd($request->all());
        try {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            $userSiteId = Auth::user()->site_id;
            
            if (!$userSiteId) {
                return redirect()->back()->with('error', 'No site assigned to your account.');
            }

            $validated = $request->validate([
                'block' => 'required|integer|exists:master_block,id',
                'plot' => 'required|integer|exists:master_plots,id',
                'seed_name' => 'required|string|max:255',
                'area' => 'required|integer',
                'seed_variety' => 'nullable|string|max:255',
                'contractor_name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'production' => 'required|numeric|min:0',
                'production_price' => 'required|numeric',
                'product_amount' => 'required|numeric',
                'amount_recovery' => 'nullable|numeric',
                'sale_price' => 'required|numeric|min:0',
                'quantity_sold' => 'required|numeric|min:0',
                'sale_part_to' => 'required|string|max:255',
                'handling_loss' => 'nullable|numeric|min:0',
                'remark' => 'nullable|string',
                'production_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
                'sale_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'
            ]);

            if ($validated['quantity_sold'] > $validated['production']) {
                return redirect()->back()->withErrors(['quantity_sold' => 'Quantity sold cannot exceed production amount.'])->withInput();
            }

            DB::beginTransaction();
            try {
                $productionAttachment = null;
                $saleAttachment = null;

                if ($request->hasFile('production_attachment')) {
                    $file = $request->file('production_attachment');
                    $productionAttachment = $file->store('contract_farming/production', 'public');
                }

                if ($request->hasFile('sale_attachment')) {
                    $file = $request->file('sale_attachment');
                    $saleAttachment = $file->store('contract_farming/sales', 'public');
                }

                $saleAmount = $validated['quantity_sold'] * $validated['sale_price'];

                $insertData = [
                    'block_id' => $validated['block'],
                    'plot_id' => $validated['plot'],
                    'area_hectares' => $validated['area'],
                    'seed_name' => $validated['seed_name'],
                    'seed_variety' => $validated['seed_variety'],
                    'contractor_name' => $validated['contractor_name'],
                    'contract_start_date' => $validated['start_date'],
                    'contract_end_date' => $validated['end_date'],
                    'production_mt' => $validated['production'],
                    'production_price_per_mt' => $validated['production_price'],
                    'product_amount' => $validated['product_amount'],
                    'amount_recovery' => $validated['amount_recovery'] ?? 0,
                    'sale_price_per_mt' => $validated['sale_price'],
                    'quantity_sold_mt' => $validated['quantity_sold'],
                    'sale_part_to' => $validated['sale_part_to'],
                    'sale_amount' => $saleAmount,
                    'handling_loss_mt' => $validated['handling_loss'] ?? 0,
                    'remark' => $validated['remark'],
                    'production_attachment' => $productionAttachment,
                    'sale_attachment' => $saleAttachment,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('contact_farming')->insert($insertData);
                DB::commit();

                return redirect()->route('contact-farming.create')->with('success', 'Contract farming record created successfully!');

            } catch (\Exception $e) {
                DB::rollback();
                
                if (isset($productionAttachment) && Storage::disk('public')->exists($productionAttachment)) {
                    Storage::disk('public')->delete($productionAttachment);
                }
                if (isset($saleAttachment) && Storage::disk('public')->exists($saleAttachment)) {
                    Storage::disk('public')->delete($saleAttachment);
                }
                
                return redirect()->back()->with('error', 'Failed to create record: ' . $e->getMessage())->withInput();
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->back()->with('error', 'An error occurred. Please try again.')->withInput();
        }
    }

  

    public function destroy($id)
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $userSiteId = Auth::user()->site_id;
            $record = DB::table('contact_farming as cf')
                ->join('master_block as mb', 'cf.block_id', '=', 'mb.id')
                ->where('cf.id', $id)
                ->where('mb.site_id', $userSiteId)
                ->select('cf.production_attachment', 'cf.sale_attachment')
                ->first();

            if (!$record) {
                return redirect()->route('contact-farming.create')->with('error', 'Record not found or access denied.');
            }

            DB::beginTransaction();
            try {
                if ($record->production_attachment && Storage::disk('public')->exists($record->production_attachment)) {
                    Storage::disk('public')->delete($record->production_attachment);
                }
                if ($record->sale_attachment && Storage::disk('public')->exists($record->sale_attachment)) {
                    Storage::disk('public')->delete($record->sale_attachment);
                }
                
                DB::table('contact_farming')->where('id', $id)->delete();
                DB::commit();

                return redirect()->route('contact-farming.create')->with('success', 'Contract farming record deleted successfully!');
            } catch (\Exception $e) {
                DB::rollback();
                return redirect()->back()->with('error', 'Failed to delete record. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting contract farming record: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete record. Please try again.');
        }
    }

    public function getPlots(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $userSiteId = Auth::user()->site_id;
            $blockId = $request->input('block_id');

            if (!$blockId) {
                return response()->json(['error' => 'Block ID is required'], 400);
            }

            $blockExists = DB::table('master_block')->where('id', $blockId)->where('site_id', $userSiteId)->exists();
            if (!$blockExists) {
                return response()->json(['error' => 'Block not found or access denied'], 404);
            }

            $plots = DB::table('master_plots')
                ->where('block_id', $blockId)
                ->select('id', 'plot_name', 'area')
                ->orderBy('plot_name')
                ->get();

            return response()->json($plots);
        } catch (\Exception $e) {
            Log::error('Error fetching plots: ' . $e->getMessage());
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

    public function getSeedVarieties(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $userSiteId = Auth::user()->site_id;
            $seedName = $request->input('seed_name');

            if (!$seedName) {
                return response()->json(['error' => 'Seed name is required'], 400);
            }

            $seedExists = DB::table('master_seed')->where('seed_name', $seedName)->where('site_id', $userSiteId)->exists();
            if (!$seedExists) {
                return response()->json(['error' => 'Seed not found or access denied'], 404);
            }

            $varieties = DB::table('master_seed')
                ->where('seed_name', $seedName)
                ->where('site_id', $userSiteId)
                ->select('variety_of_seed')
                ->distinct()
                ->orderBy('variety_of_seed')
                ->get();

            return response()->json($varieties);
        } catch (\Exception $e) {
            Log::error('Error fetching seed varieties: ' . $e->getMessage());
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

public function show($id)
{
    try {
        if (!Auth::check()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            return redirect()->route('login');
        }
        
        $userSiteId = Auth::user()->site_id;
        
        $record = DB::table('contact_farming as cf')
            ->leftJoin('blocks as mb', 'cf.block_id', '=', 'mb.id')
            ->leftJoin('master_plots as mp', 'cf.plot_id', '=', 'mp.id')
            ->where('cf.id', $id)
            ->where('mb.site_id', $userSiteId)
            ->select(
                'cf.*',
                'mb.block_name',
                'mp.plot_name',
                'mp.area as plot_area'
            )
            ->first();
            
        if (!$record) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Record not found or access denied'], 404);
            }
            return redirect()->route('contact-farming.create')->with('error', 'Record not found or access denied.');
        }
        
        // Convert stdClass to array for easier handling
        $recordArray = json_decode(json_encode($record), true);
        
        // If it's an AJAX request, return JSON
        if (request()->ajax()) {
            return response()->json($recordArray);
        }
        
        // Otherwise return view (for direct access)
        return view('pages.contact-farming-view', compact('record'));
        
    } catch (\Exception $e) {
        Log::error('Error showing contract farming record: ' . $e->getMessage());
        
        if (request()->ajax()) {
            return response()->json(['error' => 'An error occurred while loading the record: ' . $e->getMessage()], 500);
        }
        
        return redirect()->back()->with('error', 'An error occurred while loading the record: ' . $e->getMessage());
    }
}
        public function crop_cycle()
            {
                 
                try {
                  
                    $records = DB::table('crop_cycle as cc')
                        ->leftJoin('master_seed as ms', 'cc.seed_id', '=', 'ms.id')
                       // ->where('mb.site_id', $userSiteId)
                        ->select(
                            'cc.*',
                            'ms.seed_name' 
                        )
                        ->orderBy('cc.created_at', 'desc')
                        ->paginate(10);
        
                     
                    return view('pages.crop-cycle', compact('records'));
        
                } catch (\Exception $e) {
                    Log::error('Error in CropCycleController@create: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'An error occurred while loading the page. Please try again.');
                }
            }
            
    public function exportContactFarming(Request $request)
{
    try {
        $userSiteId = Auth::user()->site_id;
        if (!$userSiteId) {
            return redirect()->back()->with('error', 'No site assigned to your account. Please contact administrator.');
        }

        $records = DB::table('contact_farming as cf')
            ->leftJoin('blocks as mb', 'cf.block_id', '=', 'mb.id')
            ->leftJoin('master_plots as mp', 'cf.plot_id', '=', 'mp.id')
            ->where('mb.site_id', $userSiteId)
            ->select(
                'mb.block_name as block_name',   // ✅ use name, not id
                'mp.plot_name as plot_name',     // ✅ use name, not id
                'mp.area as plot_area',
                'cf.area_hectares as covered_area',
                'cf.seed_name',
                'cf.seed_variety',
                'cf.contractor_name',
                'cf.contract_start_date as start_date',
                'cf.contract_end_date as end_date',
                'cf.production_mt',
                'cf.production_price_per_mt',
                'cf.product_amount',
                'cf.amount_recovery',
                'cf.sale_price_per_mt',
                'cf.quantity_sold_mt',
                'cf.sale_part_to',
                'cf.sale_amount',
                'cf.handling_loss_mt',
                'cf.remark',
                'cf.created_at'
            )
            ->orderBy('cf.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();

        if (empty($records)) {
            return redirect()->back()->with('error', 'No records found to export.');
        }

        // Prepare columns
        $columns = array_keys($records[0] ?? []);

        return response()->streamDownload(function () use ($records, $columns) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, $columns);

            // Data rows
            foreach ($records as $row) {
                $csvRow = [];
                foreach ($columns as $col) {
                    $csvRow[] = $row[$col] ?? '';
                }
                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        }, 'contact_farming_' . date('Y_m_d_H_i_s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=contact_farming_' . date('Y_m_d_H_i_s') . '.csv',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in ContactFarmingController@exportContactFarming: ' . $e->getMessage());
        return redirect()->back()->with('error', $e->getMessage());
    }
}

}
