<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContractFarming;
use App\Models\ContractFarmingVariety;
use App\Models\ContractFarmingSale;
use App\Http\Requests\StoreContractFarmingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactFarmingController extends Controller
{
    public function index()
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $userSiteId = Auth::user()->site_id;

        if (!$userSiteId) {
            return redirect()->back()->with('error', 'No site assigned to your account. Please contact administrator.');
        }

        // Fetch main records
        $records = DB::table('contact_farming as cf')
            ->where('cf.site_id', $userSiteId)
            ->select('cf.*')
            ->orderBy('cf.created_at', 'desc')
            ->paginate(10);

        // Enhance records with block, plot, and variety names
      foreach ($records as $record) {
            //  Convert JSON block IDs
            $blockIds = json_decode($record->block_id, true);
            $plotIds = json_decode($record->plot_id, true);

            if (is_array($blockIds) && count($blockIds) > 0) {
                $record->block_names = DB::table('blocks')
                    ->whereIn('id', $blockIds)
                    ->pluck('block_name')
                    ->implode(', ');
            } else {
                $record->block_names = 'N/A';
            }

            if (is_array($plotIds) && count($plotIds) > 0) {
                $record->plot_names = DB::table('master_plots')
                    ->whereIn('id', $plotIds)
                    ->pluck('plot_name')
                    ->implode(', ');
            } else {
                $record->plot_names = 'N/A';
            }

            // Fetch related varieties
            $record->varieties = DB::table('contact_farming_varieties')
                ->where('contact_farming_id', $record->id)
                ->pluck('seed_variety')
                ->implode(', ');
        }

    return view('pages.contact-farming-index', compact('records'));

    } catch (\Exception $e) {
       // dd($e->getMessage());
        Log::error('Error in ContactFarmingController@index: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while loading the page. Please try again.');
    }
}


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

            return view('pages.contact-farming-add', compact('blocks', 'allPlots', 'seeds', 'allSeedVarieties'));

        } catch (\Exception $e) {
            Log::error('Error in ContactFarmingController@create: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the page. Please try again.');
        }
    }


public function store(Request $request)
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }
 
        $userSiteId = Auth::user()->site_id;
        $validated = $request->validate([
            'blocks' => 'required|array|min:1',
            'plots' => 'required|array|min:1',
            'area' => 'required|numeric|min:0',
            'seed_name' => 'required|string|max:255',
            'contractor_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remark' => 'nullable|string',
            'variety' => 'required|array|min:1',
            'variety.*' => 'required|string|max:255',
            'production' => 'required|array|min:1',
            'production.*' => 'required|numeric|min:0',
            'production_price' => 'required|array|min:1',
            'production_price.*' => 'required|numeric|min:0',
            'product_amount' => 'required|array|min:1',
            'product_amount.*' => 'required|numeric|min:0',
            'handling_loss' => 'nullable|array',
            'handling_loss.*' => 'nullable|numeric|min:0',
            'amount_recovery' => 'nullable|array',
            'amount_recovery.*' => 'nullable|numeric|min:0',
            'quantity_for_sale' => 'required|array',
            'quantity_for_sale.*' => 'required|numeric|min:0',
            'remaining_sale_quantity' => 'required|array',
            'remaining_sale_quantity.*' => 'required|numeric|min:0',
            'sales' => 'nullable|array',
            'sales.*' => 'nullable|array',
            'sales.*.quantity_sold_actual.*' => 'required|numeric|min:0',
            'sales.*.sale_price.*' => 'required|numeric|min:0',
            'sales.*.total_sale_amount.*' => 'nullable|numeric|min:0',
            'sales.*.sale_part_to.*' => 'nullable|string|max:255',
            'sales.*.sale_date.*' => 'nullable|date',
            'sales.*.is_reverse.*' => 'nullable|in:0,1',
            'sales.*.parent_sale_id.*' => 'nullable|integer|exists:contact_farming_sales,id',
            'production_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'sale_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'
        ]);

        DB::beginTransaction();

        $productionAttachment = null;
        if ($request->hasFile('production_attachment')) {
            $path = $request->file('production_attachment')->store('contract_farming/production', 'public');
            $productionAttachment = 'storage/' . $path;
        }

        $saleAttachment = null;
        if ($request->hasFile('sale_attachment')) {
            $path = $request->file('sale_attachment')->store('contract_farming/sales', 'public');
            $saleAttachment = 'storage/' . $path;
        }

        $cfId = DB::table('contact_farming')->insertGetId([
            'block_id'            => json_encode($validated['blocks']),
            'plot_id'             => json_encode($validated['plots']),
            'area_hectares'       => $validated['area'],
            'seed_name'           => $validated['seed_name'],
            'contractor_name'     => $validated['contractor_name'],
            'contract_start_date' => $validated['start_date'],
            'contract_end_date'   => $validated['end_date'],
            'production_mt'       => array_sum($validated['production']),
            'product_amount'      => array_sum($validated['product_amount']),
            'amount_recovery'     => array_sum($validated['amount_recovery'] ?? []),
            'sale_amount'         => 0,
            'sale_quantity'       => 0,
            'handling_loss_mt'    => array_sum($validated['handling_loss'] ?? []),
            'remark'              => $validated['remark'] ?? null,
            'production_attachment' => $productionAttachment,
            'sale_attachment'       => $saleAttachment,
            'site_id'             => $userSiteId,
            'created_by'          => Auth::id(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);


        $totalSaleAmount = 0;
        $totalSaleQuantity = 0;

        foreach ($validated['variety'] as $index => $seedVarietyName) {
            $varietyId = DB::table('contact_farming_varieties')->insertGetId([
                'contact_farming_id' => $cfId,
                'seed_variety' => $seedVarietyName,
                'production_quantity_mt' => $validated['production'][$index] ?? 0,
                'production_price_mt' => $validated['production_price'][$index] ?? 0,
                'production_amount' => $validated['product_amount'][$index] ?? 0,
                'handling_loss' => $validated['handling_loss'][$index] ?? 0,
                'amount_recovery' => $validated['amount_recovery'][$index] ?? 0,
                'remaining_quantity' => $validated['remaining_sale_quantity'][$index] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $varTotalQty = 0;
            $varTotalAmount = 0;

            if (isset($validated['sales'][$index])) {
                $salesData = $validated['sales'][$index];
                $num = count($salesData['quantity_sold_actual'] ?? []);
                for ($i = 0; $i < $num; $i++) {
                    $qty = $salesData['quantity_sold_actual'][$i] ?? 0;
                    $price = $salesData['sale_price'][$i] ?? 0;
                    $amount = $salesData['total_sale_amount'][$i] ?? ($qty * $price);
                    $buyer = $salesData['sale_part_to'][$i] ?? null;
                    $date = $salesData['sale_date'][$i] ?? null;

                    $parentId = $salesData['parent_sale_id'][$i] ?? 0;
                    $isRev = isset($salesData['is_reverse'][$i]) ? (int)$salesData['is_reverse'][$i] : 0;
                    if ($isRev && empty($parentId)) {
                        continue;
                    }

                    // If this entry is a reversal, deactivate the parent sale record
                    if ($isRev && !empty($parentId)) {
                        DB::table('contact_farming_sales')->where('id', $parentId)->update(['status' => 'Deactive']);
                    }

                    DB::table('contact_farming_sales')->insert([
                        'variety_id' => $varietyId,
                        'quantity_sold' => $qty,
                        'sale_price' => $price,
                        'buyer_name' => $buyer,
                        'sale_date' => $date,
                        'parent_sale_id' => $parentId,
                        'is_reverse' => $isRev,
                        'status' => 'Active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $varTotalQty += $qty;
                    $varTotalAmount += $amount;
                    $totalSaleQuantity += $qty;
                    $totalSaleAmount += $amount;
                }
            }

            DB::table('contact_farming_varieties')->where('id', $varietyId)->update([
                'sale_quantity_production' => $varTotalQty,
                'total_sale_amount' => $varTotalAmount,
                'remaining_quantity' => $validated['remaining_sale_quantity'][$index] ?? 0,
                'updated_at' => now(),
            ]);
        }

        DB::table('contact_farming')->where('id', $cfId)->update([
            'sale_amount' => $totalSaleAmount,
            'sale_quantity' => $totalSaleQuantity,
        ]);

        DB::commit();

        return redirect()->route('contact-farming.index')->with('success', 'Contract farming record saved successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error saving contract farming record: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed: ' . $e->getMessage())->withInput();
    }
}


   public function destroy($id)
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userSiteId = Auth::user()->site_id;

        // Get main record
        $record = DB::table('contact_farming')
            ->where('id', $id)
            ->where('site_id', $userSiteId)
            ->select('production_attachment', 'sale_attachment')
            ->first();

        if (!$record) {
            return redirect()->route('contact-farming.index')->with('error', 'Record not found or access denied.');
        }

        DB::beginTransaction();

        try {
            // Delete main attachments
            if ($record->production_attachment && Storage::disk('public')->exists($record->production_attachment)) {
                Storage::disk('public')->delete($record->production_attachment);
            }

            if ($record->sale_attachment && Storage::disk('public')->exists($record->sale_attachment)) {
                Storage::disk('public')->delete($record->sale_attachment);
            }

            // Get all varieties for this contact_farming record
            $varieties = DB::table('contact_farming_varieties')
                ->where('contact_farming_id', $id)
                ->get();

            foreach ($varieties as $variety) {
                // Delete related sales (and their attachments)
                $sales = DB::table('contact_farming_sales')
                    ->where('variety_id', $variety->id)
                    ->get();

                foreach ($sales as $sale) {
                    if ($sale->sale_attachment && Storage::disk('public')->exists($sale->sale_attachment)) {
                        Storage::disk('public')->delete($sale->sale_attachment);
                    }
                }

                DB::table('contact_farming_sales')->where('variety_id', $variety->id)->delete();
            }

            // Delete varieties
            DB::table('contact_farming_varieties')->where('contact_farming_id', $id)->delete();

            // Delete main record
            DB::table('contact_farming')->where('id', $id)->delete();

            DB::commit();

            return redirect()->route('contact-farming.index')->with('success', 'Contract farming record deleted successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting contact farming record: ' . $e->getMessage());
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
    $record = DB::table('contact_farming')->where('id', $id)->first();

    if (!$record) {
        return response()->json(['error' => 'Record not found'], 404);
    }

    // Decode block/plot IDs
    $blockIds = json_decode($record->block_id, true) ?? [];
    $plotIds = json_decode($record->plot_id, true) ?? [];

    // Fetch readable names
    $record->block_names = DB::table('blocks')->whereIn('id', $blockIds)->pluck('block_name')->implode(', ');
    $record->plot_names = DB::table('master_plots')->whereIn('id', $plotIds)->pluck('plot_name')->implode(', ');

    // Fetch all varieties with sales
    $record->varieties = DB::table('contact_farming_varieties as v')
    ->where('v.contact_farming_id', $id)
    ->select(
        'v.id',
        'v.seed_variety',
        'v.production_quantity_mt',
        'v.production_price_mt',
        'v.production_amount',
        'v.handling_loss',
        'v.amount_recovery'
    )
    ->get();

// Add sales per variety but compute effective/current sales by applying reversals
foreach ($record->varieties as $variety) {
    $allSales = DB::table('contact_farming_sales')
        ->where('variety_id', $variety->id)
        ->select('id','quantity_sold','sale_price','total_amount','buyer_name','created_at','parent_sale_id','is_reverse')
        ->orderBy('id')
        ->get();

    // Historical list for optional display
    $variety->history_sales = $allSales;

    // Originals are rows without a parent (parent_sale_id = 0 or null)
    $originals = $allSales->filter(fn($s) => (empty($s->parent_sale_id) || $s->parent_sale_id == 0) && (($s->is_active ?? 1) == 1))->values();

    $currentSales = collect();

    foreach ($originals as $orig) {
        $reversals = $allSales->filter(fn($r) => (int)($r->parent_sale_id ?? 0) === (int)$orig->id);
        $reversedQty = $reversals->sum(fn($r) => (float)($r->quantity_sold ?? 0));

        $netQty = (float)$orig->quantity_sold - $reversedQty;

        if ($netQty > 0) {
            $currentSales->push((object)[
                'quantity_sold' => $netQty,
                'sale_price' => $orig->sale_price,
                'total_amount' => $netQty * ($orig->sale_price ?? 0),
                'buyer_name' => $orig->buyer_name,
                'created_at' => $orig->created_at,
                'original_sale_id' => $orig->id,
            ]);
        }
    }

    // Also include standalone sales that are themselves reversals of prior records (if any were entered as separate without original) - unlikely but safe
    $standaloneReversals = $allSales->filter(fn($s) => !empty($s->parent_sale_id) && $s->is_reverse && $s->quantity_sold > 0 && !$allSales->contains(fn($o) => (int)($o->id) === (int)$s->parent_sale_id));
    foreach ($standaloneReversals as $sr) {
        // treat as negative adjustment; skip adding to currentSales
    }

    $variety->current_sales = $currentSales;

    // Also compute effective sold total for quick access
    $variety->effective_sold_total = $currentSales->sum(fn($s) => (float)($s->quantity_sold ?? 0));
}

     return view('pages.contact-farming-show', compact('record'));
}


    public function edit($id)
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            $userSiteId = Auth::user()->site_id;

            // fetch main record
            $record = DB::table('contact_farming')->where('id', $id)->where('site_id', $userSiteId)->first();
            if (!$record) {
                return redirect()->route('contact-farming.index')->with('error', 'Record not found or access denied.');
            }

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

            // prepare form data
            $selectedBlocks = json_decode($record->block_id, true) ?? [];
            $selectedPlots = json_decode($record->plot_id, true) ?? [];

            $formData = [
                'contractor_name' => $record->contractor_name,
                'start_date' => $record->contract_start_date,
                'end_date' => $record->contract_end_date,
                'area' => $record->area_hectares,
                'seed_name' => $record->seed_name,
                'remark' => $record->remark,
            ];

            // get varieties and sales in structured arrays for form prefill
            $varieties = DB::table('contact_farming_varieties')
                ->where('contact_farming_id', $id)
                ->orderBy('id')
                ->get();

            $formVarieties = [];
            foreach ($varieties as $v) {
                $sales = DB::table('contact_farming_sales')->where('variety_id', $v->id)->where('status', 'Active')->orderBy('id')->get();

                $salesStruct = [
                    'quantity_sold_actual' => [],
                    'sale_price' => [],
                    'total_sale_amount' => [],
                    'sale_part_to' => [],
                    'sale_date' => []
                ];

                foreach ($sales as $s) {
                    $salesStruct['sale_id'][] = $s->id;
                    $salesStruct['quantity_sold_actual'][] = $s->quantity_sold;
                    $salesStruct['sale_price'][] = $s->sale_price;
                    $salesStruct['total_sale_amount'][] = $s->total_amount ?? ($s->quantity_sold * $s->sale_price);
                    $salesStruct['sale_part_to'][] = $s->buyer_name;
                    $salesStruct['sale_date'][] = isset($s->sale_date) ? date('Y-m-d', strtotime($s->sale_date)) : null;
                    // indicate if this sale already has a reversal linked to it
                    $hasReverse = DB::table('contact_farming_sales')->where('parent_sale_id', $s->id)->exists();
                    $salesStruct['is_already_reversed'][] = $hasReverse ? 1 : 0;
                }

                $formVarieties[] = [
                    'seed_variety' => $v->seed_variety,
                    'production' => $v->production_quantity_mt,
                    'production_price' => $v->production_price_mt,
                    'product_amount' => $v->production_amount,
                    'handling_loss' => $v->handling_loss,
                    'amount_recovery' => $v->amount_recovery,
                    'quantity_for_sale' => $v->production_quantity_mt - ($v->handling_loss ?? 0),
                    'remaining_sale_quantity' => $v->remaining_quantity,
                    'sales' => $salesStruct,
                ];
            }
//dd($formVarieties);
            return view('pages.contact-farming-edit', compact('blocks', 'allPlots', 'seeds', 'allSeedVarieties', 'formData', 'formVarieties', 'selectedBlocks', 'selectedPlots', 'record'));

        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error in ContactFarmingController@edit: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the edit page.');
        }
    }


    public function update(Request $request, $id)
    {

       // dd(($request->all()));
        try {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            $userSiteId = Auth::user()->site_id;

            $validated = $request->validate([
                'blocks' => 'required|array|min:1',
                'plots' => 'required|array|min:1',
                'area' => 'required|numeric|min:0',
                'seed_name' => 'required|string|max:255',
                'contractor_name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'remark' => 'nullable|string',
                'variety' => 'required|array|min:1',
                'variety.*' => 'required|string|max:255',
                'production' => 'required|array|min:1',
                'production.*' => 'required|numeric|min:0',
                'production_price' => 'required|array|min:1',
                'production_price.*' => 'required|numeric|min:0',
                'product_amount' => 'required|array|min:1',
                'product_amount.*' => 'required|numeric|min:0',
                'handling_loss' => 'nullable|array',
                'handling_loss.*' => 'nullable|numeric|min:0',
                'amount_recovery' => 'nullable|array',
                'amount_recovery.*' => 'nullable|numeric|min:0',
                'quantity_for_sale' => 'required|array',
                'quantity_for_sale.*' => 'required|numeric|min:0',
                'remaining_sale_quantity' => 'required|array',
                'remaining_sale_quantity.*' => 'required|numeric|min:0',
                'sales' => 'nullable|array',
                'sales.*' => 'nullable|array',
                'sales.*.quantity_sold_actual.*' => 'required|numeric|min:0',
                'sales.*.sale_price.*' => 'required|numeric|min:0',
                'sales.*.total_sale_amount.*' => 'nullable|numeric|min:0',
                'sales.*.sale_part_to.*' => 'nullable|string|max:255',
                'sales.*.sale_date.*' => 'nullable|date',
                'sales.*.is_reverse.*' => 'nullable|in:0,1',
                'sales.*.parent_sale_id.*' => 'nullable|integer|exists:contact_farming_sales,id',
                'production_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
                'sale_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'
            ]);

            DB::beginTransaction();

            $existing = DB::table('contact_farming')->where('id', $id)->where('site_id', $userSiteId)->first();
            if (!$existing) {
                return redirect()->route('contact-farming.index')->with('error', 'Record not found or access denied.');
            }

            // handle attachments replacement
            if ($request->hasFile('production_attachment')) {
                // delete old
                if ($existing->production_attachment && Storage::disk('public')->exists($existing->production_attachment)) {
                    Storage::disk('public')->delete($existing->production_attachment);
                }
                $file = $request->file('production_attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('storage/contract_farming/production');
                $file->move($destinationPath, $filename);
                $productionAttachment = 'storage/contract_farming/production/' . $filename;
            } else {
                $productionAttachment = $existing->production_attachment;
            }

            if ($request->hasFile('sale_attachment')) {
                if ($existing->sale_attachment && Storage::disk('public')->exists($existing->sale_attachment)) {
                    Storage::disk('public')->delete($existing->sale_attachment);
                }
                $file = $request->file('sale_attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('storage/contract_farming/sales');
                $file->move($destinationPath, $filename);
                $saleAttachment = 'storage/contract_farming/sales/' . $filename;
            } else {
                $saleAttachment = $existing->sale_attachment;
            }

            // update main record
            DB::table('contact_farming')->where('id', $id)->update([
                'block_id' => json_encode($validated['blocks']),
                'plot_id' => json_encode($validated['plots']),
                'area_hectares' => $validated['area'],
                'seed_name' => $validated['seed_name'],
                'contractor_name' => $validated['contractor_name'],
                'contract_start_date' => $validated['start_date'],
                'contract_end_date' => $validated['end_date'],
                'production_mt' => array_sum($validated['production']),
                'product_amount' => array_sum($validated['product_amount']),
                'amount_recovery' => array_sum($validated['amount_recovery'] ?? []),
                'sale_amount' => collect($validated['sales'] ?? [])->flatMap(fn($sale) => $sale['total_sale_amount'] ?? [])->sum(),
                'sale_quantity' => collect($validated['sales'] ?? [])->flatMap(fn($sale) => $sale['quantity_sold_actual'] ?? [])->sum(),
                'handling_loss_mt' => array_sum($validated['handling_loss'] ?? []),
                'remark' => $validated['remark'] ?? null,
                'production_attachment' => $productionAttachment,
                'sale_attachment' => $saleAttachment,
                'updated_at' => now(),
            ]);

            // Preserve historical sales and update/insert varieties.
            // Build lookup of existing varieties by lowercased variety name
            $existingVars = DB::table('contact_farming_varieties')->where('contact_farming_id', $id)->get()
                ->keyBy(function($v){ return strtolower(trim($v->seed_variety)); });

            foreach ($validated['variety'] as $index => $seedVarietyName) {
                $varKey = strtolower(trim($seedVarietyName));

                // If a matching variety exists, update it; otherwise insert a new one
                if (isset($existingVars[$varKey])) {
                    $varietyId = $existingVars[$varKey]->id;
                    DB::table('contact_farming_varieties')->where('id', $varietyId)->update([
                        'production_quantity_mt' => $validated['production'][$index],
                        'production_price_mt' => $validated['production_price'][$index],
                        'production_amount' => $validated['product_amount'][$index],
                        'handling_loss' => $validated['handling_loss'][$index] ?? 0,
                        'amount_recovery' => $validated['amount_recovery'][$index] ?? 0,
                        'remaining_quantity' => $validated['remaining_sale_quantity'][$index] ?? 0,
                        'updated_at' => now(),
                    ]);
                } else {
                    $varietyId = DB::table('contact_farming_varieties')->insertGetId([
                        'contact_farming_id' => $id,
                        'seed_variety' => $seedVarietyName,
                        'production_quantity_mt' => $validated['production'][$index],
                        'production_price_mt' => $validated['production_price'][$index],
                        'production_amount' => $validated['product_amount'][$index],
                        'handling_loss' => $validated['handling_loss'][$index] ?? 0,
                        'amount_recovery' => $validated['amount_recovery'][$index] ?? 0,
                        'remaining_quantity' => $validated['remaining_sale_quantity'][$index] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Insert sales from the form as NEW records (do not delete historical sales).
                // For calculations/aggregates we will use only the submitted sales (so history remains preserved but not used in calculation).
                $varTotalQty = 0;
                $varTotalAmount = 0;

                if (isset($validated['sales'][$index])) {
                    $salesData = $validated['sales'][$index];
                    $numSalesEntries = count($salesData['quantity_sold_actual'] ?? []);
                    for ($i = 0; $i < $numSalesEntries; $i++) {
                            $saleQty = $salesData['quantity_sold_actual'][$i] ?? 0;
                            $salePrice = $salesData['sale_price'][$i] ?? 0;
                            $buyerName = $salesData['sale_part_to'][$i] ?? null;
                            $saleDate = $salesData['sale_date'][$i] ?? null;
                                $isReverse = isset($salesData['is_reverse'][$i]) ? (int)$salesData['is_reverse'][$i] : 0;
                                $parentSaleId = $salesData['parent_sale_id'][$i] ?? null;
                                // If form submitted a sale_id for an existing sale and this row represents a reverse,
                                // use that sale_id as the parent for the newly inserted reverse record.
                                if ($isReverse && empty($parentSaleId) && isset($salesData['sale_id'][$i]) && !empty($salesData['sale_id'][$i])) {
                                    $parentSaleId = (int)$salesData['sale_id'][$i];
                                }

                        // Quantities are stored as absolute values; reversal semantics are indicated by parent_sale_id/is_reverse.
                        $signedQty = abs($saleQty);

                        // For reverse entries, require a parentSaleId and prevent multiple reversals for same original sale
                        if ($isReverse) {
                            if (empty($parentSaleId)) {
                                // skip inserting a reverse without a valid parent
                                continue;
                            }
                            $alreadyReversed = DB::table('contact_farming_sales')->where('parent_sale_id', $parentSaleId)->exists();
                            if ($alreadyReversed) {
                                continue;
                            }

                            // deactivate the original parent sale
                            DB::table('contact_farming_sales')->where('id', $parentSaleId)->update(['status' => 'Deactive']);
                        }

                        DB::table('contact_farming_sales')->insert([
                            'variety_id' => $varietyId,
                            'quantity_sold' => $signedQty,
                            'sale_price' => $salePrice,
                            'buyer_name' => $buyerName,
                            'sale_date' => $saleDate,
                            'parent_sale_id' => $parentSaleId ?? 0,
                            'is_reverse' => $isReverse,
                            'status' => 'Active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Aggregate from submitted values (signed)
                        $varTotalQty += $signedQty;
                        $varTotalAmount += ($signedQty * $salePrice);
                    }
                }

                // Update variety aggregates using submitted sales only (history preserved but excluded from these aggregates)
                DB::table('contact_farming_varieties')->where('id', $varietyId)->update([
                    'sale_quantity_production' => $varTotalQty,
                    'total_sale_amount' => $varTotalAmount,
                    'remaining_quantity' => $validated['remaining_sale_quantity'][$index] ?? 0,
                    'updated_at' => now(),
                ]);
            }

            // After processing all varieties/sales, update top-level contact_farming aggregates from DB sums
            $totalSaleQty = DB::table('contact_farming_sales')
                ->join('contact_farming_varieties as v', 'contact_farming_sales.variety_id', '=', 'v.id')
                ->where('v.contact_farming_id', $id)
                ->sum('contact_farming_sales.quantity_sold');

            $totalSaleAmount = DB::table('contact_farming_sales')
                ->join('contact_farming_varieties as v', 'contact_farming_sales.variety_id', '=', 'v.id')
                ->where('v.contact_farming_id', $id)
                ->sum('contact_farming_sales.total_amount');

            DB::table('contact_farming')->where('id', $id)->update([
                'sale_quantity' => $totalSaleQty,
                'sale_amount' => $totalSaleAmount,
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('contact-farming.index')->with('success', 'Contract farming record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating contact farming record: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update: ' . $e->getMessage())->withInput();
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

public function sellRemaining(Request $request)
{
    $request->validate([
        'variety_id' => 'required|integer',
        'quantity_sold' => 'required|numeric|min:0.01',
        'sale_price' => 'required|numeric|min:0',
        'buyer_name' => 'required|string|max:255',
    ]);

    DB::beginTransaction();
    try {
        // Fetch the variety (use first() and check manually)
        $variety = DB::table('contact_farming_varieties')->where('id', $request->variety_id)->first();
        if (!$variety) {
            return response()->json(['error' => 'Variety not found!'], 404);
        }

        // Calculate total sold so far
        $totalSold = DB::table('contact_farming_sales')
            ->where('variety_id', $request->variety_id)
            ->sum('quantity_sold');

        // Calculate remaining quantity
        $remainingQty = $variety->production_quantity_mt - $totalSold;

        if ($request->quantity_sold > $remainingQty) {
            return response()->json([
                'error' => 'Sale quantity exceeds remaining stock!'
            ], 400);
        }

        // Insert new sale record
        $totalAmount = $request->quantity_sold * $request->sale_price;
                DB::table('contact_farming_sales')->insert([
                        'variety_id' => $request->variety_id,
                        'quantity_sold' => $request->quantity_sold,
                        'sale_price' => $request->sale_price,
                        'buyer_name' => $request->buyer_name,
                        'parent_sale_id' => null,
                        'is_reverse' => 0,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                ]);

        // Update variety totals
        $newTotalSold = $totalSold + $request->quantity_sold;
        $newTotalSaleAmount = ($variety->total_sale_amount ?? 0) + $totalAmount;
        $newRemainingQty = $variety->production_quantity_mt - $newTotalSold;

        DB::table('contact_farming_varieties')
            ->where('id', $request->variety_id)
            ->update([
                'sale_quantity_production' => $newTotalSold,
                'total_sale_amount' => $newTotalSaleAmount,
                'remaining_quantity' => $newRemainingQty,
                'updated_at' => now(),
            ]);

        // 🔹 Update top-level contact_farming totals
        $contactFarmingId = $variety->contact_farming_id;
        $totalSaleQty = DB::table('contact_farming_sales')
            ->join('contact_farming_varieties as v', 'contact_farming_sales.variety_id', '=', 'v.id')
            ->where('v.contact_farming_id', $contactFarmingId)
            ->sum('contact_farming_sales.quantity_sold');

        $totalSaleAmount = DB::table('contact_farming_sales')
            ->join('contact_farming_varieties as v', 'contact_farming_sales.variety_id', '=', 'v.id')
            ->where('v.contact_farming_id', $contactFarmingId)
            ->sum('contact_farming_sales.total_amount');

        DB::table('contact_farming')
            ->where('id', $contactFarmingId)
            ->update([
                'sale_quantity' => $totalSaleQty,
                'sale_amount' => $totalSaleAmount,
                'updated_at' => now(),
            ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Sale added successfully!',
            'remaining_quantity' => $newRemainingQty,
            'total_sale_quantity' => $totalSaleQty,
            'total_sale_amount' => $totalSaleAmount,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error selling remaining quantity: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to sell remaining quantity. ' . $e->getMessage()
        ], 500);
    }
}




}
