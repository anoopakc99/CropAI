<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;

class FertilizerRecordController extends Controller
{
   
     public function index(Request $request)
    {
        $user = Auth::user();
        $siteId = $user->site_id;
        $role = $user->role;

        $selectedSiteId = ($role == 1) ? $request->get('site_id', '') : $siteId;

             // Block dropdown
        if ($role == 1) {
            // Admin: get all blocks from master_land
            $blocks = DB::table('master_land')
                ->join('blocks as b', 'master_land.block_name', '=', 'b.id')
                ->select('b.id as block_id', 'b.block_name')
                ->distinct()
                ->get();
        } else {
            // Other roles: get blocks only for user's site from harvest_store_manage
            $blocks = DB::table('master_land as ml')
                ->join('blocks as b', 'ml.block_name', '=', 'b.id')
                ->where('ml.site_id', $selectedSiteId)
                ->select('b.id as block_id', 'b.block_name')
                ->distinct()
                ->get();
        }
        $selectedBlock = $request->input('block_name', null);
        
          $financialYears = DB::table('seasons')
        ->select('year')
        ->when($request->filled('block_name'), function ($q) use ($request) {
            $q->where('block_id', $request->block_name);
        })
        ->distinct()
        ->where('site_id', $selectedSiteId)
        ->orderBy('year', 'desc')
        
        ->pluck('year');

        $search = $request->input('search', '');

        $query = DB::table('fertilizer_soil_record as f')
            ->leftJoin('blocks', 'f.block_name', '=', 'blocks.id')
            ->leftJoin('master_plots', 'f.plot_name', '=', 'master_plots.id')
            ->leftJoin('users as u', 'f.user_id', '=', 'u.id')
            ->leftJoin('manpower_type', 'f.manpower_type', '=', 'manpower_type.id')
            // Change 'mp.category as manpower_type' to 'f.manpower_type'
            ->select('f.*', 'manpower_type.type as manpower_type', 'u.name as user_name',
             'blocks.block_name as block_name',
                'master_plots.plot_name as plot_name',
                'master_plots.area as area');

        if ($role == 1 && $request->filled('site_id')) {
            $query->where('f.site_id', $selectedSiteId);
        } else {
            $query->where('f.site_id', $siteId);
        }

        if ($selectedBlock) {
            $query->where('f.block_name', $selectedBlock);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('f.plot_name', 'like', "%$search%")
                    ->orWhere('u.name', 'like', "%$search%");
            });
        }
        
        if ($request->filled('year')) {
                $query->whereExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                      ->from('seasons as s')
                      ->whereColumn('s.site_id', 'f.site_id')
                      ->whereColumn('s.block_id', 'f.block_name')
                      ->whereColumn('s.plot_id', 'f.plot_name')
                      ->where('s.year', $request->year)
                      ->whereRaw('f.date BETWEEN s.start_date AND s.end_date');
                });
        }
            // Session filter
        if ($request->filled('season_name')) {
                $query->whereExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                      ->from('seasons as s')
                      ->whereColumn('s.site_id', 'f.site_id')
                      ->whereColumn('s.block_id', 'f.block_name')
                      ->whereColumn('s.plot_id', 'f.plot_name')
                      ->where('s.name', $request->season_name)
                      
                      ->whereRaw('f.date BETWEEN s.start_date AND s.end_date');
                });
            }
        $records = $query->orderBy('f.date', 'DESC')
            ->paginate(10)
            ->appends($request->all());
            
          
       

        $allMachineIds = collect();
        $allTractorIds = collect();
        $allFertilizerIds = collect();

        foreach ($records as $record) {
            if ($record->machine_id) {
                $allMachineIds = $allMachineIds->merge(explode(',', $record->machine_id));
            }
            
            if ($record->tractor_id) {
                $allTractorIds = $allTractorIds->merge(explode(',', $record->tractor_id));
            }

            $fertilizerArray = json_decode($record->fertilizer_id, true);
            if (is_array($fertilizerArray)) {
                foreach ($fertilizerArray as $item) {
                    if (isset($item['id'])) {
                        $allFertilizerIds->push($item['id']);
                    }
                }
            } elseif ($record->fertilizer_id) {
                $allFertilizerIds->push($record->fertilizer_id);
            }
        }

        $machines = DB::table('master_machine')
            ->whereIn('id', $allMachineIds->unique()->filter())
            ->pluck('machine_name', 'id')
            ->toArray();

        $tractors = DB::table('master_tractors')
            ->whereIn('id', $allTractorIds->unique()->filter())
            ->pluck('tractor_name', 'id')
            ->toArray();
// First build fertilizer master mapping (master_fertilizer.id => fertilizers.fertilizer_name)
$fertilizersMaster = DB::table('master_fertilizer as mf')
    ->join('fertilizers as f', 'mf.fertilizer_id', '=', 'f.id')
    ->whereIn('mf.id', $allFertilizerIds->unique()->filter())
    ->pluck('f.fertilizer_name', 'mf.id')
    ->toArray();

foreach ($records as $record) {
    $record->formatted_fertilizers = [];

    // fertilizer_id column stores JSON like: [{"id":10,"quantity":1,"uom":"Kg"}]
    $fertilizerArray = json_decode($record->fertilizer_id, true);

    if (is_array($fertilizerArray)) {
        foreach ($fertilizerArray as $item) {
            if (!empty($item['id']) && isset($fertilizersMaster[$item['id']])) {
                $record->formatted_fertilizers[] = [
                    'name'     => $fertilizersMaster[$item['id']],   // fertilizer name from joined master
                    'quantity' => $item['quantity'] ?? '--',
                    'uom'      => $item['uom'] ?? 'kg',
                ];
            }
        }
    } elseif ($record->fertilizer_id && isset($fertilizersMaster[$record->fertilizer_id])) {
        // In case fertilizer_id is not JSON but a single id
        $record->formatted_fertilizers[] = [
            'name'     => $fertilizersMaster[$record->fertilizer_id],
            'quantity' => $record->fertilizer_quantity ?? 'N/A',
            'uom'      => $record->uom ?? 'kg',
        ];
    }

            $record->formatted_machines = [];
            if ($record->machine_id) {
                $machineIds = explode(',', $record->machine_id);
                foreach ($machineIds as $machId) {
                    $machId = trim($machId);
                    if (isset($machines[$machId])) {
                        $record->formatted_machines[] = $machines[$machId];
                    }
                }
            }

            $record->formatted_tractors = [];
            if ($record->tractor_id) {
                $tractorIds = explode(',', $record->tractor_id);
                foreach ($tractorIds as $trId) {
                    $trId = trim($trId);
                    if (isset($tractors[$trId])) {
                        $record->formatted_tractors[] = $tractors[$trId];
                    }
                }
            }
            
            if ($record->major_maintenance) {
                try {
                    $maintenanceData = json_decode($record->major_maintenance, true);
                    if (is_array($maintenanceData)) {
                        $record->major_maintenance_parsed = $maintenanceData;
                    } else {
                        $record->major_maintenance_parsed = [
                            ['spare_part' => 'Maintenance', 'value' => $maintenanceData]
                        ];
                    }
                } catch (\Exception $e) {
                    $record->major_maintenance_parsed = [];
                }
            } else {
                $record->major_maintenance_parsed = [];
            }

            if (!isset($record->activity_type)) {
                $record->activity_type = 'N/A';
            }
        }
  
        $sites = ($role == 1)
            ? DB::table('master_sites')->pluck('site_name', 'id')->toArray()
            : DB::table('master_sites')->where('id', $siteId)->pluck('site_name', 'id')->toArray();

        return view('fertilizer_soil_record.index', compact(
            'blocks',
            'selectedBlock',
            'search',
            'records',
            'sites',
            'selectedSiteId',
            'role',
            'financialYears'
        ));
    }
}