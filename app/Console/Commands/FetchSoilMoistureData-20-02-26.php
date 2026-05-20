<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\EosService;
use App\Models\SoilMoisture;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FetchSoilMoistureData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soilmoisture:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch soil moisture data from EOS API and store in database';

    /**
     * The EOS service instance.
     *
     * @var \App\Services\EosService
     */
    protected $eosService;

    /**
     * Create a new command instance.
     */
    public function __construct(EosService $eosService)
    {
        parent::__construct();
        $this->eosService = $eosService;
    }

    /**
     * Execute the console command.
     */
public function handle()
{
    try {
        $this->info('Starting soil moisture data fetch...');
        Log::info('Starting soil moisture data fetch via command');

        // --- Throttling / retry config (adjust as needed) ---
        $REQUEST_DELAY = 2;    // seconds between each createTask call (reduce to 1 if EOS allows)
        $BASE_RETRY_DELAY = 5; // base seconds for exponential backoff
        $MAX_ATTEMPTS = 5;     // max attempts per plot (includes first attempt)

        // --- Step 1: Fetch plots (plot-wise moisture) ---
        $plots = DB::table('master_plots as mp')
            ->leftJoin('blocks', 'mp.block_id', '=', 'blocks.id')
            ->leftJoin('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
            ->select(
                'ms.id as site_id',
                'ms.site_name',
                'mp.id as plot_id',
                'mp.lattitude',
                'mp.longitude'
            )
            ->where('blocks.site_id', 4)
            ->whereNotNull('mp.lattitude')
            ->whereNotNull('mp.longitude')
            ->get();
  
        if ($plots->isEmpty()) {
            $this->warn("⚠ No plots found. Moisture not fetched.");
            return 1;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($plots as $plot) {
            $this->info("Processing Plot ID: {$plot->plot_id} ({$plot->site_name})");

            $attempt = 0;
            $shouldContinuePlot = false;

            while ($attempt < $MAX_ATTEMPTS && !$shouldContinuePlot) {
                $attempt++;

                // Prevent spamming EOS API → delay between plot requests (skip delay on first attempt if you prefer)
                if ($attempt === 1) {
                    sleep($REQUEST_DELAY);
                } else {
                    // exponential backoff delay on retries
                    $delay = $BASE_RETRY_DELAY * pow(2, $attempt - 2);
                    $this->warn("Attempt {$attempt} for Plot {$plot->plot_id} — sleeping {$delay}s before retry");
                    sleep($delay);
                }

                try {
                    // --- Create EOS Task ---
                    $taskResult = $this->eosService->createSoilMoistureTask(
                        $plot->lattitude,
                        $plot->longitude
                    );

                    // If service returns an array with status key (429 handling)
                    $status = $taskResult['status'] ?? null;

                    if ($status == 429) {
                        $this->warn("⚠ 429 Rate-Limit Hit for Plot {$plot->plot_id} (attempt {$attempt})");
                        // continue loop to retry until attempts exhausted
                        continue;
                    }

                    if (!isset($taskResult['success']) || !$taskResult['success']) {
                        $err = $taskResult['error'] ?? 'Unknown error creating task';
                        $this->error("❌ Failed to create task for Plot {$plot->plot_id}: {$err}");
                        $failureCount++;
                        $shouldContinuePlot = true; // break out of retry loop for this plot
                        break;
                    }

                    // --- Get Task Result ---
                    $taskResponse = $this->eosService->getTaskResult($taskResult['task_id']);

                    $respStatus = $taskResponse['status'] ?? null;
                    if ($respStatus == 429) {
                        $this->warn("⚠ 429 while fetching result for Plot {$plot->plot_id} (attempt {$attempt})");
                        // continue to retry
                        continue;
                    }

                    $responseBody = $taskResponse['body'] ?? null;

                    if (empty($responseBody)) {
                        $this->error("❌ Empty response for Plot {$plot->plot_id}");
                        $failureCount++;
                        $shouldContinuePlot = true;
                        break;
                    }

                    $decodedBody = json_decode($responseBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->error("❌ Invalid JSON for Plot {$plot->plot_id}: " . json_last_error_msg());
                        Log::error('Invalid JSON body', [
                            'plot_id' => $plot->plot_id,
                            'raw_start' => substr($responseBody, 0, 200)
                        ]);
                        $failureCount++;
                        $shouldContinuePlot = true;
                        break;
                    }

                    $records = $decodedBody['result'] ?? [];

                    if (empty($records)) {
                        $this->warn("⚠ No moisture data for Plot {$plot->plot_id}");
                        $failureCount++;
                        $shouldContinuePlot = true;
                        break;
                    }

                    // Latest moisture entry
                    $latest = end($records);

                    $sceneId = $latest['scene_id'];

                    
                    
                    // 2️⃣ Insert only NEW records
                    DB::table('soil_moistures')->insert([
                        'type' => "soil-moiture",
                        'scene_id' => $sceneId,
                        'view_id' => $latest['view_id'] ?? "view_" . uniqid(),
                        'date' => $latest['date'] ?? now()->format("Y-m-d"),
                        'site_id' => $plot->site_id,
                        'plot_id' => $plot->plot_id,
                        'q1' => (float)($latest['q1'] ?? null),
                        'q3' => (float)($latest['q3'] ?? null),
                        'max' => (float)($latest['max'] ?? null),
                        'min' => (float)($latest['min'] ?? null),
                        'p10' => (float)($latest['p10'] ?? null),
                        'p90' => (float)($latest['p90'] ?? null),
                        'std' => (float)($latest['std'] ?? null),
                        'median' => (float)($latest['median'] ?? null),
                        'average' => (float)($latest['average'] ?? null),
                        'variance' => (float)($latest['variance'] ?? null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("✅ Saved moisture for Plot {$plot->plot_id} ({$latest['date']})");
                    $successCount++;
                    $shouldContinuePlot = true; // success — exit retry loop

                } catch (\Exception $e) {
                    $this->error("❌ Exception for Plot {$plot->plot_id} (attempt {$attempt}): " . $e->getMessage());
                    Log::error("Plot processing exception", [
                        "plot_id" => $plot->plot_id,
                        "attempt" => $attempt,
                        "error" => $e->getMessage(),
                        "trace" => $e->getTraceAsString()
                    ]);
                    // will retry until attempts exhausted
                }
            } // end while attempts

            if (!$shouldContinuePlot) {
                // exhausted attempts without success
                $this->error("❌ Plot {$plot->plot_id} failed after {$MAX_ATTEMPTS} attempts.");
                $failureCount++;
            }
        } // end foreach plots

        // Summary
        $this->newLine();
        $this->info("=== Soil Moisture Fetch Summary ===");
        $this->info("Success: {$successCount}");
        $this->info("Failed: {$failureCount}");

        return 0;

    } catch (\Exception $e) {

        $this->error("❌ Fatal Error: " . $e->getMessage());

        Log::error("Fatal soil moisture fetch error", [
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ]);

        return 1;
    }
}



 

}
