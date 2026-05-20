<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\EosService;
use App\Models\NDVIData;
use Carbon\Carbon;

class FetchNDVIData extends Command
{
    protected $signature = 'ndvi:fetch';
    protected $description = 'Fetch NDVI (Crop Health) data from EOS API and store in database';

    protected $eosService;

    public function __construct(EosService $eosService)
    {
        parent::__construct();
        $this->eosService = $eosService;
    }

    public function handle()
{
    try {
        $this->info('Starting NDVI data fetch...');

        // Step 1: Fetch all plots with coordinates
        $plots = DB::table('master_plots as mp')
            ->leftJoin('blocks', 'mp.block_id', '=', 'blocks.id')
            ->leftJoin('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
            ->select('ms.id as site_id', 'ms.site_name', 'mp.lattitude', 'mp.longitude', 'mp.id as plot_id')
            ->where('blocks.site_id', 1)
            ->get();

        if ($plots->isEmpty()) {
            $this->warn('No plots found for NDVI fetch');
            return 1;
        }

        $success = 0;
        $failed = 0;
        $requestCount = 0;
        $startTime = time();

        foreach ($plots as $plot) {
            $this->info("Processing Plot ID: {$plot->plot_id} ({$plot->site_name})");

            if (empty($plot->lattitude) || empty($plot->longitude)) {
                $this->warn("Skipping Plot {$plot->plot_id} - Missing coordinates");
                $failed++;
                continue;
            }

            try {
                // Rate limiting: max 9 requests per minute
                if ($requestCount >= 9) {
                    $elapsed = time() - $startTime;
                    if ($elapsed < 60) {
                        $waitTime = 60 - $elapsed;
                        $this->warn(" Rate limit protection: waiting {$waitTime}s...");
                        sleep($waitTime);
                    }
                    $requestCount = 0;
                    $startTime = time();
                }

                // Create polygon around the plot — ensure floats (not strings from DB)
                $lat = (float) $plot->lattitude;
                $lng = (float) $plot->longitude;

                $polygon = [
                    [$lng, $lat],
                    [$lng + 0.02, $lat],
                    [$lng + 0.02, $lat + 0.02],
                    [$lng, $lat + 0.02],
                    [$lng, $lat]
                ];

                // Step 2: Create NDVI Task
                $taskResult = $this->eosService->createNdviTask($polygon);
                $requestCount++;

                // Handle JsonResponse
                if ($taskResult instanceof \Illuminate\Http\JsonResponse) {
                    $taskResult = $taskResult->getData(true);
                }

                // Check if task creation succeeded
                if (!isset($taskResult['success']) || !$taskResult['success']) {
                    $errorMessage = $taskResult['error'] ?? $taskResult['message'] ?? 'Unknown error';
                    
                    if (isset($taskResult['body']) && is_string($taskResult['body'])) {
                        $errorMsgLow = strtolower($taskResult['body']);
                        if (str_contains($errorMsgLow, 'requests limit exceeded') && str_contains($errorMsgLow, '1000')) {
                            $this->error(" CRITICAL: Daily limit of 1000 requests exceeded. Aborting all remaining NDVI plots.");
                            return 1;
                        }
                    }

                    // Handle rate limit with longer wait
                    if (isset($taskResult['retry_after']) || str_contains(strtolower($errorMessage), '429') || str_contains(strtolower($errorMessage), 'limit exceeded')) {
                        $retryTime = $taskResult['retry_after'] ?? 60;
                        $this->warn("Rate limit hit, waiting {$retryTime}s...");
                        sleep($retryTime);
                        $requestCount = 0;
                        $startTime = time();
                        continue; // Wait and try the next plot
                    }
                    
                    $this->error(" Task creation failed for plot {$plot->plot_id}: {$errorMessage}");
                    $failed++;
                    continue;
                }

                // Get task_id and wait for processing
                $taskId = $taskResult['task_id'] ?? null;
                
                if (!$taskId) {
                    $this->error(" No task_id returned for plot {$plot->plot_id}");
                    $failed++;
                    continue;
                }

                $this->info("Task created: {$taskId}. Waiting for processing...");
                sleep(20); // Wait for task to process

                // Step 3: Get task results with rate limiting
                if ($requestCount >= 9) {
                    $elapsed = time() - $startTime;
                    if ($elapsed < 60) {
                        $waitTime = 60 - $elapsed;
                        $this->warn(" Rate limit protection: waiting {$waitTime}s...");
                        sleep($waitTime);
                    }
                    $requestCount = 0;
                    $startTime = time();
                }

                $ndviResult = $this->eosService->getNDVITaskResult($taskId);
                $requestCount++;

                if (!isset($ndviResult['success']) || !$ndviResult['success']) {
                    $errorMessage = $ndviResult['error'] ?? 'Failed to fetch results';
                    
                    if (isset($ndviResult['body']) && is_string($ndviResult['body'])) {
                        $errorMsgLow = strtolower($ndviResult['body']);
                        if (str_contains($errorMsgLow, 'requests limit exceeded') && str_contains($errorMsgLow, '1000')) {
                            $this->error("❌ CRITICAL: Daily limit of 1000 requests exceeded. Aborting all remaining NDVI plots.");
                            return 1;
                        }
                    }
                    
                    if (str_contains(strtolower($errorMessage), '429') || str_contains(strtolower($errorMessage), 'limit exceeded')) {
                        $this->warn("Rate limit hit while fetching result, waiting 60s...");
                        sleep(60);
                        $requestCount = 0;
                        $startTime = time();
                        continue;
                    }

                    $this->error("❌ Result fetch failed for plot {$plot->plot_id}: {$errorMessage}");
                    $failed++;
                    continue;
                }

                $ndviRecords = $ndviResult['data'] ?? [];

                if (empty($ndviRecords)) {
                    $this->warn("⚠️ No NDVI data available for plot {$plot->plot_id}");
                    $failed++;
                    continue;
                }

                // Process each record (usually multiple dates)
                $savedCount = 0;
                foreach ($ndviRecords as $record) {
                    // EOS GDW mt_stats returns stats at root level of each record
                    // Fallback: check nested indexes.NDVI structure (older API format)
                    if (isset($record['min']) || isset($record['average'])) {
                        $ndviData = $record; // Flat structure (current EOS GDW format)
                    } else {
                        $ndviData = $record['indexes']['NDVI'] ?? null;
                    }

                    if (!$ndviData || (!isset($ndviData['min']) && !isset($ndviData['average']))) {
                        $this->warn("⚠️ No NDVI indexes found in record");
                        continue;
                    }

                    $sceneId = $record['scene_id'] ?? "ndvi_site_{$plot->site_id}_" . date('Ymd_His') . '_' . uniqid();
                    $date = $record['date'] ?? Carbon::now()->format('Y-m-d');

                    // Check if record already exists in ndvi_data table
                    $exists = DB::table('ndvi_data')
                        ->where('scene_id', $sceneId)
                        ->where('plot_id', $plot->plot_id)
                        ->exists();

                    if ($exists) {
                        $this->info("⏭️ Skipping duplicate: {$sceneId}");
                        continue;
                    }

                    // Determine crop health status from average NDVI
                    $avgNdvi = $ndviData['average'] ?? null;
                    $ndviStatus = null;
                    if ($avgNdvi !== null) {
                        if ($avgNdvi < 0.2)      $ndviStatus = 'poor';
                        elseif ($avgNdvi < 0.4)  $ndviStatus = 'moderate';
                        elseif ($avgNdvi < 0.6)  $ndviStatus = 'good';
                        else                      $ndviStatus = 'excellent';
                    }

                    // Save to ndvi_data table with all EOS API fields
                    DB::table('ndvi_data')->insert([
                        'scene_id'       => $sceneId,
                        'view_id'        => $record['view_id'] ?? null,
                        'date'           => $date,
                        'site_id'        => $plot->site_id,
                        'plot_id'        => $plot->plot_id,

                        // Main NDVI value (average) kept for backward compat
                        'ndvi_value'     => $avgNdvi,
                        'status'         => $ndviStatus,

                        // Full statistical data from EOS API
                        'average'        => $ndviData['average'] ?? null,
                        'min'            => $ndviData['min'] ?? null,
                        'max'            => $ndviData['max'] ?? null,
                        'std'            => $ndviData['std'] ?? null,
                        'variance'       => $ndviData['variance'] ?? null,
                        'median'         => $ndviData['median'] ?? null,
                        'q1'             => $ndviData['q1'] ?? null,
                        'q3'             => $ndviData['q3'] ?? null,
                        'p10'            => $ndviData['p10'] ?? null,
                        'p90'            => $ndviData['p90'] ?? null,
                        'cloud_coverage' => $record['cloud'] ?? null,

                        'created_at'     => Carbon::now(),
                        'updated_at'     => Carbon::now(),
                    ]);

                    $savedCount++;

                }

                if ($savedCount > 0) {
                    $this->info("✅ Saved {$savedCount} NDVI record(s) for Plot: {$plot->plot_id} | Site: {$plot->site_name}");
                    $success++;
                } else {
                    $this->warn("⚠️ No new NDVI records saved for plot {$plot->plot_id}");
                    $failed++;
                }

                // Small delay between plots
                sleep(2);

            } catch (\Exception $e) {
                $this->error("❌ Error processing plot {$plot->plot_id}: {$e->getMessage()}");
                Log::error("NDVI Fetch Error", [
                    'plot_id' => $plot->plot_id,
                    'site_id' => $plot->site_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("=== NDVI Fetch Summary ===");
        $this->info("✅ Success: {$success}");
        $this->error("❌ Failed: {$failed}");

        Log::info('NDVI fetch completed', [
            'success' => $success,
            'failed' => $failed,
        ]);

        return 0;

    } catch (\Exception $e) {
        $this->error("❌ Fatal NDVI Fetch Error: {$e->getMessage()}");
        Log::error("Fatal NDVI Fetch Error", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return 1;
    }
}

/**
 * Alternative: Batch processing for better efficiency
 */
public function handleBatch()
{
    try {
        $this->info('Starting NDVI batch fetch...');

        $plots = DB::table('master_plots as mp')
            ->leftJoin('blocks', 'mp.block_id', '=', 'blocks.id')
            ->leftJoin('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
            ->select('ms.id as site_id', 'ms.site_name', 'mp.lattitude', 'mp.longitude', 'mp.id as plot_id')
            ->where('blocks.site_id', 4)
            ->whereNotNull('mp.lattitude')
            ->whereNotNull('mp.longitude')
            ->get();

        if ($plots->isEmpty()) {
            $this->warn('No plots found');
            return 1;
        }

        $success = 0;
        $failed = 0;
        $taskIds = [];
        $requestCount = 0;
        $startTime = time();

        // Phase 1: Create all tasks
        $this->info("Phase 1: Creating tasks for {$plots->count()} plots...");
        
        foreach ($plots as $index => $plot) {
            // Rate limiting
            if ($requestCount >= 9) {
                $elapsed = time() - $startTime;
                if ($elapsed < 60) {
                    $waitTime = 60 - $elapsed;
                    $this->warn("⏳ Waiting {$waitTime}s for rate limit...");
                    sleep($waitTime);
                }
                $requestCount = 0;
                $startTime = time();
            }

            $polygon = [
                [$plot->longitude, $plot->lattitude],
                [$plot->longitude + 0.02, $plot->lattitude],
                [$plot->longitude + 0.02, $plot->lattitude + 0.02],
                [$plot->longitude, $plot->lattitude + 0.02],
                [$plot->longitude, $plot->lattitude]
            ];

            $taskResult = $this->eosService->createNdviTask($polygon);
            $requestCount++;

            if ($taskResult instanceof \Illuminate\Http\JsonResponse) {
                $taskResult = $taskResult->getData(true);
            }

            if (isset($taskResult['success']) && $taskResult['success'] && isset($taskResult['task_id'])) {
                $taskIds[] = [
                    'task_id' => $taskResult['task_id'],
                    'plot' => $plot,
                    'reference' => $taskResult['reference']
                ];
                $this->info("Task created for plot {$plot->plot_id}: {$taskResult['task_id']}");
            } else {
                $this->error("Failed to create task for plot {$plot->plot_id}");
                $failed++;
            }

            usleep(500000); // 0.5s delay
        }

        // Phase 2: Wait for processing
        $this->info("Phase 2: Waiting 30s for tasks to process...");
        sleep(30);

        // Phase 3: Fetch results
        $this->info("Phase 3: Fetching results for " . count($taskIds) . " tasks...");
        $requestCount = 0;
        $startTime = time();

        foreach ($taskIds as $task) {
            // Rate limiting
            if ($requestCount >= 9) {
                $elapsed = time() - $startTime;
                if ($elapsed < 60) {
                    $waitTime = 60 - $elapsed;
                    $this->warn("⏳ Waiting {$waitTime}s for rate limit...");
                    sleep($waitTime);
                }
                $requestCount = 0;
                $startTime = time();
            }

            $result = $this->eosService->getNDVITaskResult($task['task_id']);
            $requestCount++;

            if (isset($result['success']) && $result['success']) {
                $records = $result['data'] ?? [];
                
                foreach ($records as $record) {
                    $ndviData = $record['indexes']['NDVI'] ?? null;
                    if (!$ndviData) continue;

                    DB::table('soil_moisture')->insert([
                        'scene_id' => $record['scene_id'],
                        'view_id' => $record['view_id'],
                        'date' => $record['date'],
                        'site_id' => $task['plot']->site_id,
                        'plot_id' => $task['plot']->plot_id,
                        'type' => 'ndvi-data',
                        'min' => $ndviData['min'],
                        'max' => $ndviData['max'],
                        'average' => $ndviData['average'],
                        'std' => $ndviData['std'],
                        'variance' => $ndviData['variance'],
                        'q1' => $ndviData['q1'],
                        'q3' => $ndviData['q3'],
                        'median' => $ndviData['median'],
                        'p10' => $ndviData['p10'],
                        'p90' => $ndviData['p90'],
                        'cloud_coverage' => $record['cloud'],
                        'lattitude' => (float)$task['plot']->lattitude,
                        'longitude' => (float)$task['plot']->longitude,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
                
                $success++;
                $this->info("✅ Saved NDVI data for plot {$task['plot']->plot_id}");
            } else {
                $failed++;
                $this->error("❌ Failed to get results for plot {$task['plot']->plot_id}");
            }

            sleep(1);
        }

        $this->info("✅ Success: {$success} | ❌ Failed: {$failed}");
        return 0;

    } catch (\Exception $e) {
        $this->error("Fatal error: {$e->getMessage()}");
        return 1;
    }
}

}
