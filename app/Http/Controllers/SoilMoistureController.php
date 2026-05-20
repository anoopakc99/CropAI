<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SoilMoisture;
use App\Models\NDVIData;
use App\Services\EosService;

class SoilMoistureController extends Controller
{
     protected $eosService;

    public function __construct(EosService $eosService)
    {
        $this->eosService = $eosService;
    }
   public function testndvi(Request $request)
{
    try {
        $plot = DB::table('master_plots as mp')
            ->leftJoin('blocks', 'mp.block_id', '=', 'blocks.id')
            ->leftJoin('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
            ->select('ms.id as site_id', 'ms.site_name', 'mp.lattitude', 'mp.longitude', 'mp.id as plot_id')
            ->where('blocks.site_id', 1)
            ->where('mp.id', 187)
            ->first();

        if (!$plot) {
            return response()->json(['error' => 'No plot found'], 404);
        }

        if (empty($plot->lattitude) || empty($plot->longitude)) {
            return response()->json(['error' => 'Invalid coordinates'], 400);
        }

        $lat = (float) $plot->lattitude;
        $lng = (float) $plot->longitude;

        $polygon = [
            [$lng, $lat],
            [$lng + 0.02, $lat],
            [$lng + 0.02, $lat + 0.02],
            [$lng, $lat + 0.02],
            [$lng, $lat],
        ];

        // Step 1: Create NDVI task
        $taskResult = $this->eosService->createNdviTask($polygon);
        //dd($taskResult);
        if ($taskResult instanceof \Illuminate\Http\JsonResponse) {
            $taskResult = $taskResult->getData(true);
        }

        if (empty($taskResult['success']) || !$taskResult['success']) {
            return response()->json(['error' => 'Task creation failed', 'response' => $taskResult]);
        }

        $taskId = $taskResult['task_id'] ?? null;
        echo $taskId;
        if (!$taskId) {
            return response()->json(['error' => 'Task ID not found', 'response' => $taskResult]);
        }
         
            $ndviResult = $this->eosService->getNDVITaskResult($taskId);

        //   dd($ndviResult);

        // Final result check
        if (empty($ndviResult['data'])) {
            return response()->json([
                'status' => 'pending',
                'message' => 'NDVI result not ready yet, please retry after a few minutes',
                'task_id' => $taskId,
                'last_response' => $ndviResult,
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'task_result' => $taskResult,
            'ndvi_result' => $ndviResult,
        ]);

    } catch (\Exception $e) {
        Log::error('NDVI Debug Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    
    public function index(Request $request)
{
    try {
        $user = auth()->user();
        $selectedDate = $request->input('date') ?? Carbon::now()->format('Y-m-d');
        $period = $request->input('period', '7'); // Default to 7 days

        // Get site details
        $site = DB::table('master_sites')->where('id', $user->site_id)->first();

        // Get all plots with basic info
        $plots = DB::table('master_plots as mp')
            ->leftJoin('blocks', 'mp.block_id', '=', 'blocks.id')
            ->where('blocks.site_id', $user->site_id)
            ->select(
                'mp.id as plot_id',
                'mp.plot_name',
                'mp.area',
                'blocks.block_name'
            )
            ->get();

        $soilMoistureData = [];
        $allAverages = [];
        $plotStatuses = [];

        foreach ($plots as $plot) {
            // Get soil moisture data for the selected period
            $startDate = Carbon::parse($selectedDate)->subDays($period)->format('Y-m-d');
            $endDate = $selectedDate;

            $records = SoilMoisture::where('site_id', $user->site_id)
                ->where('plot_id', $plot->plot_id)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->orderBy('date', 'asc')
                ->get();

            if ($records->isNotEmpty()) {
                // Calculate soil moisture statistics
                $latest = $records->last();
                $currentMoisture = (float)$latest->average;
                $allAverages[] = $currentMoisture;

                $minMoisture = (float)$records->min('min');
                $maxMoisture = (float)$records->max('max');
                $avgMoisture = (float)$records->avg('average');
                $trend = $this->calculateTrend($records->pluck('average')->toArray());
                $status = $this->determinePlotStatus($currentMoisture);
                $plotStatuses[] = $status;

                // Fetch NDVI data for the same plot and date range
                $ndviRecords = NDVIData::where('plot_id', $plot->plot_id)
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->orderBy('date', 'asc')
                    ->get();

                if ($ndviRecords->isNotEmpty()) {
                    $latestNDVI = $ndviRecords->last();
                    $avgNDVI = round($ndviRecords->avg('ndvi_value'), 3);
                    $minNDVI = round($ndviRecords->min('ndvi_value'), 3);
                    $maxNDVI = round($ndviRecords->max('ndvi_value'), 3);
                    $trendNDVI = $this->calculateTrend($ndviRecords->pluck('ndvi_value')->toArray());
                    $ndviStatus = $this->determineNDVIStatus($latestNDVI->ndvi_value);
                } else {
                    $avgNDVI = $minNDVI = $maxNDVI = null;
                    $trendNDVI = 'no_data';
                    $ndviStatus = 'unknown';
                }

                // Merge soil moisture and NDVI into one dataset
                $soilMoistureData[] = [
                    'plot_id' => $plot->plot_id,
                    'plot_name' => $plot->plot_name ?? "Plot {$plot->plot_id}",
                    'block_name' => $plot->block_name,
                    'area' => $plot->area,
                    'success' => true,
                    'current_moisture' => $currentMoisture,
                    'min_moisture' => $minMoisture,
                    'max_moisture' => $maxMoisture,
                    'avg_moisture' => $avgMoisture,
                    'trend' => $trend,
                    'status' => $status,
                    'last_update' => $latest->updated_at,
                    'alerts' => $this->generateAlerts($currentMoisture, $status),
                    'recommendations' => $this->generateRecommendations($status, $currentMoisture),

                    // NDVI data
                    'ndvi_avg' => $avgNDVI,
                    'ndvi_min' => $minNDVI,
                    'ndvi_max' => $maxNDVI,
                    'ndvi_trend' => $trendNDVI,
                    'ndvi_status' => $ndviStatus,
                ];
            } else {
                $soilMoistureData[] = [
                    'plot_id' => $plot->plot_id,
                    'plot_name' => $plot->plot_name ?? "Plot {$plot->plot_id}",
                    'block_name' => $plot->block_name,
                    'area' => $plot->area,
                    'success' => false,
                    'status' => 'unknown',
                    'error' => 'No data available for the selected period',
                    'alerts' => [['type' => 'warning', 'message' => 'No sensor data available for selected period']],
                ];
            }
        }

        // Calculate overall stats
        $overallStats = $this->calculateOverallStats($allAverages, $plotStatuses);

        return view('soil-moisture', [
            'soilMoistureData' => $soilMoistureData,
            'totalPlots' => count($plots),
            'activePlots' => count($allAverages),
            'overallStats' => $overallStats,
            'selectedDate' => $selectedDate,
            'selectedPeriod' => $period,
            'siteName' => $site->site_name ?? "Site {$user->site_id}",
        ]);

    } catch (\Exception $e) {
        Log::error('Soil moisture controller error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return view('soil-moisture', [
            'soilMoistureData' => [],
            'totalPlots' => 0,
            'activePlots' => 0,
            'overallStats' => [],
            'selectedDate' => $request->input('date') ?? now()->format('Y-m-d'),
            'selectedPeriod' => $request->input('period', '7'),
            'siteName' => "Unknown Site",
            'error' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
}

    private function calculateTrend($values)
    {
        if (count($values) < 2) return 'stable';
        
        // Use last 3 values for trend calculation
        $recentValues = array_slice($values, -3);
        $olderValues = array_slice($values, 0, min(3, count($values)));
        
        if (count($recentValues) < 2 || count($olderValues) < 2) return 'stable';
        
        $recentAvg = array_sum($recentValues) / count($recentValues);
        $olderAvg = array_sum($olderValues) / count($olderValues);
        
        $change = $recentAvg - $olderAvg;
        
        if (abs($change) < 1.0) return 'stable';
        return $change > 0 ? 'rising' : 'falling';
    }

    private function determinePlotStatus($moisture)
    {
        $optimalRange = $this->getOptimalRange();
        
        if ($moisture < $optimalRange['min'] * 0.7 || $moisture > $optimalRange['max'] * 1.3) {
            return 'critical';
        } elseif ($moisture < $optimalRange['min'] * 0.8 || $moisture > $optimalRange['max'] * 1.2) {
            return 'warning';
        }
        
        return 'healthy';
    }

    private function getOptimalRange()
    {
        // Default optimal moisture ranges (25-35%)
        // You can adjust these values based on your requirements
        return ['min' => 25, 'max' => 35];
    }

    private function generateAlerts($moisture, $status)
{
    $alerts = [];

    switch ($status) {
        case 'critical':
            $alerts[] = [
                'type' => 'danger',
                'message' => "Low moisture level ({$moisture}%) - Immediate irrigation required",
            ];
            break;

        case 'warning':
            $alerts[] = [
                'type' => 'warning',
                'message' => "High moisture level ({$moisture}%) - Monitor for waterlogging",
            ];
            break;

        case 'healthy':
            $alerts[] = [
                'type' => 'success',
                'message' => "Moisture level ({$moisture}%) is within optimal range - Healthy condition",
            ];
            break;
    }

    return $alerts;
}
    private function generateRecommendations($status, $moisture)
    {
        $recommendations = [];
        
        if ($status === 'critical') {
            if ($moisture < 25) {
                $recommendations[] = 'Immediate irrigation required';
                $recommendations[] = 'Monitor soil moisture closely';
            } else {
                $recommendations[] = 'Stop irrigation immediately';
                $recommendations[] = 'Improve drainage system';
            }
        } elseif ($status === 'warning') {
            if ($moisture < 25) {
                $recommendations[] = 'Schedule irrigation soon';
            } else {
                $recommendations[] = 'Reduce irrigation frequency';
            }
        } else {
            $recommendations[] = 'Maintain current irrigation schedule';
        }

        return $recommendations;
    }

    private function calculateOverallStats($averages, $statuses)
    {
        if (empty($averages)) {
            return [
                'avg_moisture' => 0,
                'min_moisture' => 0,
                'max_moisture' => 0,
                'healthy_plots' => 0,
                'warning_plots' => 0,
                'critical_plots' => 0,
            ];
        }

        return [
            'avg_moisture' => round(array_sum($averages) / count($averages), 2),
            'min_moisture' => round(min($averages), 2),
            'max_moisture' => round(max($averages), 2),
            'healthy_plots' => count(array_filter($statuses, fn($s) => $s === 'healthy')),
            'warning_plots' => count(array_filter($statuses, fn($s) => $s === 'warning')),
            'critical_plots' => count(array_filter($statuses, fn($s) => $s === 'critical')),
        ];
    }

    // Debug method to check data
    public function debugData(Request $request)
    {
        $user = auth()->user();
        $selectedDate = $request->input('date') ?? Carbon::now()->format('Y-m-d');
        $period = $request->input('period', '7');

        $startDate = Carbon::parse($selectedDate)->subDays($period)->format('Y-m-d');
        
        $data = DB::table('soil_moistures')
            ->where('site_id', $user->site_id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $selectedDate)
            ->select('plot_id', 'date', 'average', 'min', 'max')
            ->orderBy('date', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'site_id' => $user->site_id,
            'start_date' => $startDate,
            'end_date' => $selectedDate,
            'period' => $period,
            'total_records' => $data->count(),
            'data' => $data
        ]);
    }
    
    private function determineNDVIStatus($ndvi)
{
    if (is_null($ndvi)) return 'unknown';

    if ($ndvi < 0.2) return 'poor';       // low vegetation
    if ($ndvi < 0.4) return 'moderate';   // medium vegetation
    if ($ndvi < 0.6) return 'good';       // healthy vegetation
    return 'excellent';                   // very healthy
}

}