<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EosService
{
    protected $apiKey;
    protected $baseUrl = 'https://api-connect.eos.com/api/gdw/api';

    public function __construct()
    {
        $this->apiKey = config('services.eos.api_key');
    }

    /**
     * Step 1: Create a GDW soil moisture task
     */
public function createSoilMoistureTask($lat, $lng)
{
    try {
        $dateEnd = Carbon::now()->format('Y-m-d');
        $dateStart = Carbon::now()->subDays(5)->format('Y-m-d');
        
        // $dateEnd = '2025-11-30';
        // $dateStart = '2025-11-25';
        

        // Create a small bounding box polygon (approx ~100m around point)
        $buffer = 0.01; // ~100m
        $lat = (float) $lat;
        $lng = (float) $lng;
        
        $polygon = [
            [
                [$lng - $buffer, $lat - $buffer],
                [$lng + $buffer, $lat - $buffer],
                [$lng + $buffer, $lat + $buffer],
                [$lng - $buffer, $lat + $buffer],
                [$lng - $buffer, $lat - $buffer] // close polygon
            ]
        ];

        // Generate a unique reference using timestamp
        $reference = 'ref_' . date('Ymd_His') . '_' . uniqid();

        $payload = [
            "type" => "mt_stats",
            "params" => [
                "bm_type" => "soilmoisture",
                "date_start" => $dateStart,
                "date_end" => $dateEnd,
                "geometry" => [
                    "type" => "Polygon",
                    "coordinates" => $polygon
                ],
                "reference" => $reference,
                "sensors" => ["soilmoisture"],
                "limit" => 10
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        Log::debug('EOS GDW Create Task Payload', [
            'payload' => $jsonPayload,
            'reference' => $reference,
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng
            ]
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'text/plain',
            'Accept' => 'application/json',
        ])->send('POST', "{$this->baseUrl}?api_key={$this->apiKey}", [
            'body' => $jsonPayload,
        ]);

        Log::debug('EOS GDW Create Task Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->status() === 429) {
            Log::warning('Rate limit hit during Soil Moisture task creation');
            return [
                'success' => false,
                'status' => 429,
                'error' => 'Rate limit exceeded'
            ];
        }

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['task_id'])) {
                return [
                    'success' => true,
                    'task_id' => $json['task_id']
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Failed to create task: ' . $response->body()
        ];

    } catch (\Exception $e) {
        Log::error('EOS GDW Create Task Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => 'Error creating task: ' . $e->getMessage()
        ];
    }
}


    /**
     * Step 2: Get the GDW task result
     */
  public function getTaskResult($taskId)
{
    try {
        if (!$taskId) {
            return [
                'success' => false,
                'error' => 'No task ID provided'
            ];
        }

        // NOTE: Ensure $this->baseUrl is correct and points to the task endpoint base
        $url = "{$this->baseUrl}/{$taskId}?api_key={$this->apiKey}";
        $attempts = 0;
        $maxAttempts = 3;
        $finalBody = null; // Variable to store the successful body

        do {
            $response = Http::get($url);
            $attempts++; // Increment attempt here

            Log::debug('EOS GDW Task Raw Response', [
                'attempt' => $attempts,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->status() === 429) {
                return [
                    'success' => false,
                    'status' => 429,
                    'error' => 'Rate limit exceeded while fetching result'
                ];
            }

            if (!$response->successful()) {
                // Return a failure response with the unsuccessful status body
                return [
                    'success' => false,
                    'error' => 'Failed to get task result: ' . $response->body(),
                    'body' => $response->body(), // Include body for better error logging in the command
                ];
            }

            try {
                $data = $response->json();
            } catch (\Exception $e) {
                // Handle non-JSON responses gracefully
                Log::error('Failed to parse JSON response', [
                    'response' => $response->body(),
                    'error' => $e->getMessage()
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid response format',
                    'body' => $response->body() // Return the bad body
                ];
            }

            // Log the parsed data structure
            Log::debug('EOS GDW Task Data Structure', [
                'attempt' => $attempts,
                'data_keys' => array_keys($data),
                'has_result' => isset($data['result']),
                'response_type' => gettype($data)
            ]);

            // Check if we have actual soil moisture data
            if (isset($data['result']) && is_array($data['result']) && count($data['result']) > 0) {
                // SUCCESS PATH: Store the raw body and break the loop
                $finalBody = $response->body();
                break; // Task result found, stop polling

            }

            // Check for processing status
            $status = $data['status'] ?? 'unknown';
            if (in_array($status, ['created', 'processing', 'queued'])) {
                Log::info('Task still processing', [
                    'status' => $status,
                    'attempt' => $attempts
                ]);
                sleep(5);
                // Do NOT increment attempts again here, it was done at the start of the loop
                continue;
            }

            // If we get here, the task status is unexpected (e.g., 'failed' or 'cancelled')
            Log::warning('Unexpected task status or task failure', [
                'status' => $status,
                'data' => $data
            ]);
            
            // Task status is not pending and not successful, so it failed/finished without result.
            return [
                'success' => false,
                'error' => "Task completed with status: {$status}",
                'body' => $response->body(),
            ];


        } while ($attempts < $maxAttempts);

        // FINAL RETURN BLOCK AFTER LOOP:
        if ($finalBody) {
            // Success: Return the raw JSON body so the command can decode it
            return [
                'success' => true,
                'status' => $response->status(), // Use the last response status
                'body' => $finalBody // <-- This is the crucial line for the command
            ];
        }

        // Failure: If we reached max attempts without a result
        return [
            'success' => false,
            'error' => 'Task still processing after multiple attempts, or final result was not available.',
            'status' => $data['status'] ?? 'unknown',
            'body' => $response->body() ?? null // Return the last body for debug
        ];

    } catch (\Exception $e) {
        // ... (Existing Exception handling)
        return [
            'success' => false,
            'error' => 'Error getting task result: ' . $e->getMessage()
        ];
    }
}

/**
 * Create NDVI task with rate limiting
 */
  public function createNdviTask($polygon)
{
try {
$apiKey = config('services.eos.api_key');


    // Generate unique reference for tracking
    $reference = 'ref_' . date('Ymd_His') . '_' . uniqid();

    $dateEnd = Carbon::now()->format('Y-m-d');
    $dateStart = Carbon::now()->subDays(30)->format('Y-m-d'); // 30 days to catch Sentinel-2 flyovers

    // build payload (correct geometry + lowercase bm_type + valid sensor)
    $payload = [
        "type" => "mt_stats",
        "params" => [
            "bm_type" => "NDVI",
            "date_start" => $dateStart,
            "date_end" => $dateEnd,
            "geometry" => [
                "type" => "Polygon",
                "coordinates" => [$polygon]   // GeoJSON Polygon: coordinates is array of rings
            ],
            "reference" => $reference,
            "sensors" => ["sentinel2"],        // Sentinel-2 Level-2A surface reflectance
            "limit" => 10
        ]
    ];

    Log::debug('EOS NDVI Create Task Payload', [
        'payload' => json_encode($payload),
        'reference' => $reference
    ]);

    // Correct endpoint and headers
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'x-api-key' => $apiKey,
    ])->post('https://api-connect.eos.com/api/gdw/api', $payload);

    Log::debug('EOS NDVI Create Task Response', [
        'status' => $response->status(),
        'body' => $response->body()
    ]);

    // Handle rate limiting specifically
    if ($response->status() === 429) {
        Log::warning('Rate limit hit during NDVI task creation');
        return [
            'success' => false,
            'error' => 'Rate limit exceeded',
            'retry_after' => 60
        ];
    }

    // Handle failed requests
    if ($response->failed()) {
        Log::error('NDVI API Error', ['response' => $response->body()]);
        return [
            'success' => false,
            'error' => 'Failed to create NDVI task',
            'body' => $response->body()
        ];
    }

    $result = $response->json();

    // Task created successfully
    if (isset($result['task_id'])) {
        return [
            'success' => true,
            'task_id' => $result['task_id'],
            'status' => $result['status'] ?? 'created',
            'reference' => $reference
        ];
    }

    // Fallback if task_id missing
    return [
        'success' => false,
        'error' => 'No task_id returned',
        'body' => $result
    ];

} catch (\Exception $e) {
    Log::error('NDVI Task Creation Exception', ['error' => $e->getMessage()]);
    return [
        'success' => false,
        'error' => $e->getMessage()
    ];
}


}


/**
 * Get NDVI task results with proper rate limiting and retry logic
 */
public function getNDVITaskResult($taskId, $maxAttempts = 20, $waitSeconds = 15)
{
    try {
        if (!$taskId) {
            return [
                'success' => false,
                'error' => 'No NDVI task ID provided'
            ];
        }

        $apiKey = config('services.eos.api_key');
        $url = "https://api-connect.eos.com/api/gdw/api/{$taskId}?api_key={$apiKey}";

        $attempts = 0;

        do {
            $attempts++;

            // Add delay between requests to respect rate limits
            if ($attempts > 1) {
                sleep($waitSeconds);
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            Log::debug('EOS NDVI Task Status Check', [
                'attempt' => $attempts,
                'task_id' => $taskId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Handle rate limiting
            if ($response->status() === 429) {
                Log::warning('Rate limit hit checking NDVI task', [
                    'task_id' => $taskId,
                    'attempt' => $attempts
                ]);
                
                // Wait longer on rate limit
                if ($attempts < $maxAttempts) {
                    sleep(60); // Wait full minute
                    continue;
                } else {
                    return [
                        'success' => false,
                        'error' => 'Rate limit exceeded after multiple attempts'
                    ];
                }
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch NDVI task result: ' . $response->body()
                ];
            }

            $data = $response->json();
            $status = $data['status'] ?? 'unknown';
            $taskType = $data['task_type'] ?? null;

            // Fail fast: EOS task errored internally (e.g. malformed geometry)
            if ($taskType === 'error') {
                $errMsg = $data['error_message']['error'] ?? 'EOS task error';
                Log::error('NDVI task returned error type', ['task_id' => $taskId, 'error' => $errMsg]);
                return [
                    'success' => false,
                    'error' => $errMsg,
                ];
            }

            // Success: result array present and non-empty (EOS may not always return status='completed')
            if (isset($data['result']) && is_array($data['result']) && count($data['result']) > 0) {
                Log::info('NDVI task completed successfully', [
                    'task_id' => $taskId,
                    'records' => count($data['result'])
                ]);
                return [
                    'success' => true,
                    'data' => $data['result'],
                    'errors' => $data['errors'] ?? []
                ];
            }

            // Check if task is complete with results (fallback status check)
            if ($status === 'completed' && isset($data['result'])) {
                return [
                    'success' => false,
                    'message' => 'No NDVI data available (cloudy or incomplete coverage)',
                    'errors' => $data['errors'] ?? []
                ];
            }

            // Task still processing
            if (in_array($status, ['created', 'processing', 'queued'])) {
                Log::info('NDVI task still processing', [
                    'task_id' => $taskId,
                    'status' => $status,
                    'attempt' => $attempts
                ]);
                continue;
            }

            // Task failed
            if ($status === 'failed') {
                return [
                    'success' => false,
                    'error' => 'NDVI task failed',
                    'body' => $data
                ];
            }

            // EOS returns task_type='error' with error_message — detect and fail fast
            // Unknown status — log and continue polling
            Log::warning('Unknown NDVI task status', [
                'task_id' => $taskId,
                'status' => $status
            ]);

        } while ($attempts < $maxAttempts);

        return [
            'success' => false,
            'error' => 'NDVI result not ready after ' . $maxAttempts . ' attempts',
            'last_status' => $status ?? 'unknown'
        ];

    } catch (\Exception $e) {
        Log::error('NDVI Result Fetch Error', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => 'Error fetching NDVI result: ' . $e->getMessage()
        ];
    }
}

/**
 * Process multiple NDVI tasks with rate limiting
 */
public function processMultipleNdviTasks($polygons)
{
    $results = [
        'success' => 0,
        'failed' => 0,
        'tasks' => []
    ];

    $taskIds = [];
    $requestCount = 0;
    $startTime = time();

    // Step 1: Create all tasks with rate limiting
    foreach ($polygons as $index => $polygon) {
        // Rate limit check: max 9 requests per minute
        if ($requestCount >= 9) {
            $elapsed = time() - $startTime;
            if ($elapsed < 60) {
                $waitTime = 60 - $elapsed;
                Log::info("Rate limit protection: waiting {$waitTime}s");
                sleep($waitTime);
            }
            $requestCount = 0;
            $startTime = time();
        }

        $result = $this->createNdviTask($polygon);
        $requestCount++;

        if ($result['success'] && isset($result['task_id'])) {
            $taskIds[] = [
                'task_id' => $result['task_id'],
                'polygon_index' => $index,
                'reference' => $result['reference']
            ];
        } else {
            $results['failed']++;
            Log::warning('Failed to create NDVI task', ['index' => $index]);
        }

        // Small delay between creations
        usleep(500000); // 0.5 seconds
    }

    // Step 2: Wait for processing
    Log::info('Waiting for NDVI tasks to process', ['count' => count($taskIds)]);
    sleep(30); // Initial wait

    // Step 3: Retrieve results with rate limiting
    $requestCount = 0;
    $startTime = time();

    foreach ($taskIds as $task) {
        // Rate limit check
        if ($requestCount >= 9) {
            $elapsed = time() - $startTime;
            if ($elapsed < 60) {
                $waitTime = 60 - $elapsed;
                Log::info("Rate limit protection: waiting {$waitTime}s");
                sleep($waitTime);
            }
            $requestCount = 0;
            $startTime = time();
        }

        $result = $this->getNDVITaskResult($task['task_id']);
        $requestCount++;

        if ($result['success']) {
            $results['success']++;
            $results['tasks'][] = [
                'task_id' => $task['task_id'],
                'reference' => $task['reference'],
                'data' => $result['data']
            ];
        } else {
            $results['failed']++;
        }

        // Delay between retrievals
        sleep(1);
    }

    Log::info('NDVI fetch completed', [
        'success' => $results['success'],
        'failed' => $results['failed']
    ]);

    return $results;
}

}
