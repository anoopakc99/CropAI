<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class MasterStockController extends Controller
{
 public function masterseedindex(Request $request)
    {
        $user = Auth::user();
        $sites = [];

        if ($user && is_null($user->site_id)) {
            $sites = DB::table('master_sites')->select('id', 'site_name')->get();
        }
        elseif ($user && $user->site_id) {
            $sites = DB::table('master_sites')->where('id', $user->site_id)->select('id', 'site_name')->get();
        }

        // Build query for seeds
        $query = DB::table('seed')->orderBy('id', 'desc');

        // Apply search filter if provided
        if ($request->has('query') && !empty($request->query)) {
            $searchTerm = $request->query;
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Apply site filter if provided (for admin users)
        if ($request->has('site_id') && !empty($request->site_id) && $user && is_null($user->site_id)) {
            $query->where('site_id', $request->site_id);
        }
        // For non-admin users, filter by their site
        elseif ($user && $user->site_id) {
            $query->where('site_id', $user->site_id);
        }

        $mseeds = $query->paginate(10);
 
        // Preserve query parameters in pagination links
        $mseeds->appends($request->query());

        return view('admin.masterseedindex', compact('mseeds', 'sites'));
    }

  public function store(Request $request)
    {

        //  Validate the incoming request data with unique-per-site rule for name.
        $validator = Validator::make($request->all(), [
            'seed_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('seed', 'name')->where(function ($query) use ($request) {
                    return $query->where('site_id', $request->site_id);
                }),
            ],
            'site_id' => 'required|integer',
            'varieties' => 'required|array|min:1', // at least 1 variety
            'varieties.*' => 'required|string|max:255', // each variety required
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Extra server-side safety: ensure no existing seed with same name in same site
        $exists = DB::table('seed')
            ->where('name', $request->seed_name)
            ->where('site_id', $request->site_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['seed_name' => 'Seed name already exists for this site.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Insert the new record into the 'seed' table and get its ID.
            $seedId = DB::table('seed')->insertGetId([
                'name' => $request->seed_name,
                'status' => 'active',
                'site_id' => $request->site_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert each variety into 'seed_variety' table.
            foreach ($request->varieties as $variety) {
                DB::table('master_veriety')->insert([
                    'seed_id' => $seedId,
                    'variety_name' => $variety,
                     'site_id' => $request->site_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

           return redirect()->route('master.seed.index')->with('success', 'Seed record added successfully!');
        } catch (\Exception $e) {

            DB::rollBack();

             Log::error('Seed Delete Error: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Seed Record is not created');
        }
    }


 public function update(Request $request, $id)
{
    $request->validate([
        'seed_name' => 'required|string',
        'site_id' => 'required|integer',
        'varieties' => 'array',
        'varieties.*' => 'string'
    ]);
    $user = Auth::user();
    $site_id = $user->site_id;

    DB::table('seed')->where('id', $id)->update([
        'name' => $request->seed_name,
        'site_id' => $site_id,
        'updated_at' => now(),
    ]);

    // purane varieties delete + naye insert
    DB::table('master_veriety')->where('seed_id', $id)->delete();

    foreach ($request->varieties as $variety) {
        DB::table('master_veriety')->insert([
            'seed_id' => $id,
            'variety_name' => $variety,
            'site_id'  => $site_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

 return redirect()->route('master.seed.index')->with('success', 'Seed record updated successfully!');
}

    public function edit($id)
    {
        $record = DB::table('seed')->where('id', $id)->first();
        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }
        return response()->json(['record' => $record]);
    }

  public function destroy($id)
    {
        try {
            $deleted = DB::table('seed')->where('id', $id)->delete();

            if ($deleted) {
                return redirect()->route('master.seed.index')->with('success', 'Seed record deleted successfully!');
            } else {
                return redirect()->back()->with('error', 'Record not found');
            }

        } catch (\Exception $e) {
            Log::error('Seed Delete Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting seed record: ' . $e->getMessage());
        }
    }

   public function show($id)
{
    $user = Auth::user();
    $site_id = $user->site_id;
    // Seed record
    $record = DB::table('seed')
        ->where('id', $id)
        ->where('site_id', $site_id)
        ->first();

    if (!$record) {
        return response()->json([
            'message' => 'Seed record not found'
        ], 404);
    }

    // Varieties related to seed
    $varieties = DB::table('master_veriety')
        ->where('site_id', $site_id)
        ->where('seed_id', $id)
        ->get();

    return response()->json([
        'record'    => $record,
        'varieties' => $varieties
    ]);
}


public function indexChemicals(Request $request)
    {
        try {
            $user = Auth::user();
            $userRole = $user->role;
            $userSiteId = $user->site_id;

            // Fetch sites for Admin dropdown
            $sites = DB::table('master_sites')
                ->select('id', 'site_name')
                ->orderBy('site_name')
                ->get();

            // Build chemical query
            $query = DB::table('chemical_master as c')
                ->leftJoin('master_sites as s', 'c.site_id', '=', 's.id')
                ->select('c.*', 's.site_name');

            // Admin can filter by selected site
            if ($userRole == 1) {
                if ($request->has('site_id') && $request->site_id != '') {
                    $query->where('c.site_id', $request->site_id);
                }
            } else {
                // Other roles can only see their own site
                $query->where('c.site_id', $userSiteId);
            }

            // Search filter
            if ($request->has('search') && $request->search != '') {
                $query->where(function ($q) use ($request) {
                    $q->where('c.chemical_name', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('c.brand_name', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('c.supplier_name', 'LIKE', '%' . $request->search . '%');
                });
            }

            // Get chemical records
            $chemicals = $query->orderBy('c.id', 'desc')->get();


            // Convert to paginated collection
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $chemicals->slice(($currentPage - 1) * $perPage, $perPage)->all();

            $paginatedChemicals = new LengthAwarePaginator(
                $currentItems,
                $chemicals->count(),
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query()
                ]
            );

            // For edit mode (existing logic)
            $chemical = null;


            return view('admin.chemical-master', compact('paginatedChemicals', 'chemicals', 'sites', 'userRole'));

        } catch (\Exception $e) {
            Log::error('Error loading chemical data: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading data.');
        }
    }
    public function chemicalStore(Request $request)
    {
        // Validate the incoming request data.
        $validator = Validator::make($request->all(), [
            'chemical_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chemical_master', 'chemical_name')->where(function ($query) use ($request) {
                    return $query->where('site_id', $request->site_id);
                }),
            ],
            'site_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            // Attach validation errors and also set a single-string flash message
            $first = $validator->errors()->first('fertilizer_name') ?? $validator->errors()->first();
            return redirect()->back()->withErrors($validator)->withInput()->with('error', $first);
        }
        try {
            DB::beginTransaction();

            // Insert the new record into the 'seed' table and get its ID.
            $chemical = DB::table('chemical_master')->insertGetId([
                'chemical_name' => $request->chemical_name,

                'site_id' => $request->site_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();

           return redirect()->route('master-chemical.index')->with('success', 'Chemical record added successfully!');
        } catch (\Exception $e) {

            DB::rollBack();

             Log::error('Chemical Add Error: ' . $e->getMessage());
                return redirect()->back()->with('error', $e->getMessage());
        }
    }
     public function updatechemicals(Request $request, $id)
{
    // Validate incoming request data
    $validatedNewData = $request->validate([
        'chemical_name' => 'required|string|max:255',
        'site_id' => 'required|integer|exists:master_sites,id',

    ]);

    // Check if seed record exists
    $originalSeed = DB::table('chemical_master')->where('id', $id)->first();

    if (!$originalSeed) {
        return redirect()->back()
            ->with('error', 'Chemical record not found for update.');
    }

    // Always update the existing record – no duplicate insert
    DB::table('chemical_master')
        ->where('id', $id)
        ->update($validatedNewData);

    return redirect()->back()
        ->with('success', 'Chemical data updated successfully for ID: ' . $id);
}

public function indexFertilizers(Request $request)
    {
        try {
            $user = Auth::user();
            $userRole = $user->role;
            $userSiteId = $user->site_id;

            // Fetch sites for Admin dropdown
            $sites = DB::table('master_sites')
                ->select('id', 'site_name')
                ->orderBy('site_name')
                ->get();

            // Build chemical query
            $query = DB::table('fertilizers as f')
                ->leftJoin('master_sites as s', 'f.site_id', '=', 's.id')
                ->select('f.*', 's.site_name');

            // Admin can filter by selected site
            if ($userRole == 1) {
                if ($request->has('site_id') && $request->site_id != '') {
                    $query->where('f.site_id', $request->site_id);
                }
            } else {
                // Other roles can only see their own site
                $query->where('f.site_id', $userSiteId);
            }

            // Get chemical records
            $fertilizers = $query->orderBy('f.id', 'desc')->get();

            // Convert to paginated collection
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $fertilizers->slice(($currentPage - 1) * $perPage, $perPage)->all();

            $paginatedfertilizers = new LengthAwarePaginator(
                $currentItems,
                $fertilizers->count(),
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query()
                ]
            );

            // For edit mode (existing logic)
            $chemical = null;

            return view('admin.fertilizer-master', compact('paginatedfertilizers', 'fertilizers', 'sites', 'userRole'));

        } catch (\Exception $e) {
           //dd($e->getMessage());
            Log::error('Error loading Fertilizer data: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading data.');
        }
    }
    public function fertilizerStore(Request $request)
    {
        // Validate the incoming request data.
        $validator = Validator::make($request->all(), [
            'fertilizer_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fertilizers', 'fertilizer_name')->where(function ($query) use ($request) {
                    return $query->where('site_id', $request->site_id);
                }),
            ],
            'site_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            // Return validation errors as a JSON response.
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            DB::beginTransaction();

            // Insert the new record into the 'seed' table and get its ID.
            $chemical = DB::table('fertilizers')->insertGetId([
                'fertilizer_name' => $request->fertilizer_name,
                'site_id' => $request->site_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();

           return redirect()->route('master-fertilizer.index')->with('success', 'fertilizers record added successfully!');
        } catch (\Exception $e) {

            DB::rollBack();

             Log::error('fertilizer Add Error: ' . $e->getMessage());
                return redirect()->back()->with('error', $e->getMessage());
        }
    }
     public function updateFertilizers(Request $request, $id)
{
    // Validate incoming request data
    $validatedNewData = $request->validate([
        'fertilizer_name' => 'required|string|max:255',
        'site_id' => 'required|integer|exists:master_sites,id',
    ]);

    // Check if fertilizer record exists
    $originalFertilizer = DB::table('fertilizers')->where('id', $id)->first();

    if (!$originalFertilizer) {
        return redirect()->back()
            ->with('error', 'Fertilizer record not found for update.');
    }

    // Always update the existing record – no duplicate insert
    DB::table('fertilizers')
        ->where('id', $id)
        ->update($validatedNewData);

    return redirect()->back()
        ->with('success', 'Fertilizer data updated successfully for ID: ' . $id);
}




}
