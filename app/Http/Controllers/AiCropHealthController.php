<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AiCropHealthController extends Controller
{
   
    public function index(Request $request)
    {
        $user     = auth()->user();
        $siteId   = $user?->site_id;
        $siteName = 'Site';
        if ($siteId) {
            $siteName = DB::table('master_sites')->where('id', $siteId)->value('site_name') ?? 'Site';
        }
        return view('ai-crop-health', compact('siteName', 'siteId'));
    }

    
    public function allPlotsSummary(Request $request)
    {
        try {
            $user   = auth()->user();
            $siteId = $user?->site_id;
            $date   = $request->get('date'); // optional date filter

            // Always fetch ALL plots first (so no-data cards can be shown)
            $allPlotsQuery = DB::table('master_plots as mp')
                ->join('blocks','mp.block_id','=','blocks.id')
                ->join('master_sites as ms','blocks.site_id','=','ms.id')
                ->select('mp.id as plot_id','mp.plot_name','mp.area','blocks.block_name','ms.site_name')
                ->when($siteId, fn($q) => $q->where('blocks.site_id', $siteId))
                ->orderBy('mp.plot_name')
                ->get()->keyBy('plot_id');

            if ($allPlotsQuery->isEmpty()) {
                return response()->json(['success'=>false,'message'=>'No plots configured.'], 404);
            }

            // Sub-query: latest date per plot (on or before selected date)
            $sub = DB::table('ndvi_data')
                ->selectRaw('plot_id, MAX(date) as max_date')
                ->whereNotNull('average')
                ->when($date, fn($q) => $q->where('date', '<=', $date));

            if ($siteId) {
                $sub->whereIn('plot_id', $allPlotsQuery->keys()->toArray());
            }
            $sub->groupBy('plot_id');

            // Fetch matching NDVI records
            $records = DB::table('ndvi_data as nd')
                ->joinSub($sub, 'lr', fn($j) =>
                    $j->on('nd.plot_id','=','lr.plot_id')->on('nd.date','=','lr.max_date'))
                ->select('nd.plot_id','nd.average','nd.min','nd.max','nd.median',
                         'nd.status','nd.date','nd.cloud_coverage')
                ->get();

            // Sparklines (last 5 per plot)
            $plotIds = $records->pluck('plot_id');
            $sparklines = DB::table('ndvi_data')
                ->whereIn('plot_id', $plotIds)->whereNotNull('average')
                ->when($date, fn($q) => $q->where('date','<=',$date))
                ->orderByDesc('date')->get()
                ->groupBy('plot_id')
                ->map(fn($r) => $r->take(5)->reverse()->values()
                    ->map(fn($x) => [
                        'v' => round((float)$x->average, 4),
                        'd' => Carbon::parse($x->date)->format('d M'),
                    ]));

            // Build per-plot output
            $hasNdvi = $records->keyBy('plot_id');
            $plots = $allPlotsQuery->map(function ($plt) use ($hasNdvi, $sparklines) {
                $r = $hasNdvi[$plt->plot_id] ?? null;
                if (!$r) {
                    return [
                        'plot_id'    => $plt->plot_id,
                        'plot_name'  => $plt->plot_name,
                        'block_name' => $plt->block_name,
                        'area'       => $plt->area,
                        'has_data'   => false,
                    ];
                }
                $ndvi   = (float)$r->average;
                $status = $r->status ?? $this->getNdviStatus($ndvi);
                $spark  = $sparklines[$plt->plot_id] ?? collect();
                // Trend from sparkline
                $trend = 'stable'; $impPct = 0;
                if ($spark->count() >= 2) {
                    $first = (float)$spark->first()['v'];
                    $last  = (float)$spark->last()['v'];
                    $impPct = $first > 0 ? round((($last-$first)/$first)*100,1) : 0;
                    if ($impPct > 2)      $trend = 'improving';
                    elseif ($impPct < -2) $trend = 'declining';
                }
                return [
                    'plot_id'    => $plt->plot_id,
                    'plot_name'  => $plt->plot_name,
                    'block_name' => $plt->block_name,
                    'area'       => $plt->area,
                    'has_data'   => true,
                    'ndvi'       => round($ndvi, 4),
                    'min'        => round((float)$r->min, 4),
                    'max'        => round((float)$r->max, 4),
                    'median'     => round((float)$r->median, 4),
                    'status'     => $status,
                    'trend'      => $trend,
                    'improvement'=> $impPct,
                    'last_date'  => Carbon::parse($r->date)->format('d M Y'),
                    'cloud'      => $r->cloud_coverage ?? 0,
                    'sparkline'  => $spark->values(),
                ];
            })->values();

            // Summary
            $withData  = $plots->where('has_data', true);
            $summary   = [
                'total'    => $plots->count(),
                'healthy'  => $withData->whereIn('status',['good','excellent'])->count(),
                'weak'     => $withData->whereIn('status',['poor','moderate'])->count(),
                'no_data'  => $plots->where('has_data',false)->count(),
                'avg_ndvi' => round($withData->avg('ndvi'), 4),
            ];

            return response()->json(['success'=>true,'plots'=>$plots,'summary'=>$summary]);

        } catch (\Exception $e) {
            Log::error('AI All Plots API', ['error'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * Show the All-Plots Overview dashboard page.
     */
    public function overview(Request $request)
    {
        $user     = auth()->user();
        $siteId   = $user?->site_id;
        $siteName = 'Site';
        if ($siteId) {
            $siteName = DB::table('master_sites')->where('id', $siteId)->value('site_name') ?? 'Site';
        }
        return view('ai-crop-health-overview', compact('siteName', 'siteId'));
    }

    /**
     * API: All plots latest NDVI summary for the overview grid.
     * GET /api/ai-crop-health-overview
     */
    public function allPlotsData(Request $request)
    {
        try {
            $user   = auth()->user();
            $siteId = $user?->site_id;

            // Sub-query: latest date per plot
            $latestSub = DB::table('ndvi_data')
                ->selectRaw('plot_id, MAX(date) as max_date')
                ->whereNotNull('average')
                ->groupBy('plot_id');

            // Join to get latest record per plot
            $latestRecords = DB::table('ndvi_data as nd')
                ->joinSub($latestSub, 'lr', function ($j) {
                    $j->on('nd.plot_id', '=', 'lr.plot_id')->on('nd.date', '=', 'lr.max_date');
                })
                ->join('master_plots as mp', 'nd.plot_id', '=', 'mp.id')
                ->join('blocks', 'mp.block_id', '=', 'blocks.id')
                ->join('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
                ->select(
                    'nd.plot_id', 'nd.average', 'nd.min', 'nd.max', 'nd.status', 'nd.date', 'nd.cloud_coverage',
                    'mp.plot_name', 'mp.area', 'blocks.block_name', 'ms.site_name'
                )
                ->when($siteId, fn($q) => $q->where('blocks.site_id', $siteId))
                ->orderByRaw('nd.average DESC')
                ->get();

            if ($latestRecords->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No NDVI data found.'], 404);
            }

            $plotIds = $latestRecords->pluck('plot_id');

            // Fetch last 5 records per plot for sparkline
            $sparklineData = DB::table('ndvi_data')
                ->whereIn('plot_id', $plotIds)
                ->whereNotNull('average')
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('plot_id')
                ->map(fn($rows) => $rows->take(5)->reverse()->values()->pluck('average')->map(fn($v) => round((float)$v, 4)));

            // Fetch previous record per plot for improvement calculation
            $previousSub = DB::table('ndvi_data')
                ->selectRaw('plot_id, MAX(date) as prev_date')
                ->whereNotNull('average')
                ->whereIn('plot_id', $plotIds)
                ->groupBy('plot_id');

            $prevRecords = DB::table('ndvi_data as nd')
                ->joinSub($previousSub, 'pr', function ($j) {
                    $j->on('nd.plot_id', '=', 'pr.plot_id')->on('nd.date', '=', 'pr.prev_date');
                })
                ->select('nd.plot_id', 'nd.average', 'nd.date')
                ->get()
                ->keyBy('plot_id');

            // Build result
            $plots = $latestRecords->map(function ($r) use ($sparklineData, $prevRecords) {
                $latestNdvi = (float)$r->average;
                $status     = $r->status ?? $this->getNdviStatus($latestNdvi);

                // Improvement vs previous record
                $prevRec  = $prevRecords[$r->plot_id] ?? null;
                $prevNdvi = $prevRec ? (float)$prevRec->average : $latestNdvi;
                $improvPct = $prevNdvi > 0 ? round((($latestNdvi - $prevNdvi) / $prevNdvi) * 100, 1) : 0;

                $trend = 'stable';
                if ($improvPct > 2)      $trend = 'improving';
                elseif ($improvPct < -2) $trend = 'declining';

                return [
                    'plot_id'       => $r->plot_id,
                    'plot_name'     => $r->plot_name ?? 'Plot '.$r->plot_id,
                    'block_name'    => $r->block_name,
                    'area'          => $r->area,
                    'ndvi'          => round($latestNdvi, 4),
                    'status'        => $status,
                    'trend'         => $trend,
                    'improvement'   => $improvPct,
                    'last_date'     => \Carbon\Carbon::parse($r->date)->format('d M Y'),
                    'cloud'         => $r->cloud_coverage ?? 0,
                    'sparkline'     => $sparklineData[$r->plot_id] ?? [],
                ];
            });

            // Summary
            $total     = $plots->count();
            $healthy   = $plots->whereIn('status', ['good','excellent'])->count();
            $weak      = $plots->whereIn('status', ['poor','moderate'])->count();
            $avgNdvi   = round($plots->avg('ndvi'), 4);

            return response()->json([
                'success' => true,
                'summary' => compact('total','healthy','weak','avgNdvi'),
                'plots'   => $plots->values(),
            ]);

        } catch (\Exception $e) {
            \Log::error('AI Overview API Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Get NDVI data for a specific plot.
     * GET /api/ai-crop-health/{plot_id}
     */
    public function plotData(Request $request, $plotId)
    {
        try {
            // Fetch last 10 records ordered by date descending
            $records = DB::table('ndvi_data')
                ->where('plot_id', $plotId)
                ->whereNotNull('average')
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No NDVI data found for this plot.'
                ], 404);
            }

            // Latest record
            $latest = $records->first();
            $latestNdvi = (float) $latest->average;

            // Reverse for chart (chronological order)
            $last10 = $records->reverse()->values();

            // Calculate trend (compare latest vs oldest in last 10)
            $oldest = $last10->first();
            $oldestNdvi = (float) $oldest->average;
            $improvement = $latestNdvi - $oldestNdvi;
            $improvementPct = $oldestNdvi > 0 ? round(($improvement / $oldestNdvi) * 100, 1) : 0;

            $trend = 'stable';
            if ($improvementPct > 5)       $trend = 'improving';
            elseif ($improvementPct < -5)  $trend = 'declining';

            // Health status
            $healthStatus = $this->getNdviStatus($latestNdvi);

            // AI message based on data
            $aiMessage = $this->generateAiMessage($latestNdvi, $healthStatus, $trend, $improvementPct, $latest);

            // Format last 10 records for chart & table
            $formattedRecords = $last10->map(function ($r) {
                return [
                    'date'           => Carbon::parse($r->date)->format('d M Y'),
                    'raw_date'       => $r->date,
                    'average'        => round((float)$r->average, 4),
                    'min'            => round((float)$r->min, 4),
                    'max'            => round((float)$r->max, 4),
                    'median'         => round((float)$r->median, 4),
                    'std'            => round((float)$r->std, 4),
                    'cloud_coverage' => $r->cloud_coverage ?? 0,
                    'status'         => $r->status ?? $this->getNdviStatus((float)$r->average),
                    'scene_id'       => $r->scene_id,
                ];
            });

            return response()->json([
                'success'                => true,
                'latest_data'            => [
                    'ndvi'           => round($latestNdvi, 4),
                    'date'           => Carbon::parse($latest->date)->format('d M Y'),
                    'scene_id'       => $latest->scene_id,
                    'cloud_coverage' => $latest->cloud_coverage ?? 0,
                    'min'            => round((float)$latest->min, 4),
                    'max'            => round((float)$latest->max, 4),
                    'median'         => round((float)$latest->median, 4),
                ],
                'health_status'          => $healthStatus,
                'trend'                  => $trend,
                'improvement_percentage' => $improvementPct,
                'last_10_records'        => $formattedRecords,
                'ai_message'             => $aiMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Crop Health API Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Single source of truth for NDVI classification (4-tier).
     * Rounded to 3dp before comparison to avoid floating-point edge cases.
     *
     * Thresholds (matching original agronomic convention):
     *   excellent : NDVI > 0.60   (0.61 → excellent)
     *   good      : NDVI > 0.40   (0.49 → good ✓)
     *   moderate  : NDVI > 0.20   (0.30 → moderate)
     *   critical  : NDVI ≤ 0.20
     */
    private function getNdviStatus(?float $ndvi): string
    {
        if ($ndvi === null) return 'no_data';
        $v = round($ndvi, 3);
        if ($v > 0.60) return 'excellent';
        if ($v > 0.40) return 'good';
        if ($v > 0.20) return 'moderate';
        return 'critical';
    }

    /**
     * Generate AI-style crop health insight message (legacy – used by plotData).
     */
    private function generateAiMessage(float $ndvi, string $status, string $trend, float $improvPct, $latest): string
    {
        $dateStr  = Carbon::parse($latest->date)->format('d M Y');
        $cloud    = $latest->cloud_coverage ?? 0;

        $statusMessages = [
            'critical'  => "🔴 Vegetation is critically low (NDVI: {$ndvi}). Immediate action required — check for crop failure, water shortage, or pest damage.",
            'warning'   => "🟠 Vegetation density is dangerously below optimal (NDVI: {$ndvi}). Urgent irrigation and soil check recommended.",
            'moderate'  => "🟡 Vegetation density is below optimal (NDVI: {$ndvi}). Crop health needs attention. Review irrigation schedule and fertilizer application.",
            'good'      => "🟢 Crop vegetation looks healthy (NDVI: {$ndvi}). Continue current agricultural practices. Monitor upcoming satellite passes.",
            'excellent' => "🌿 Exceptional crop health detected (NDVI: {$ndvi})! Vegetation is thriving. Maintain current regime for consistent yield.",
        ];

        $trendNote = match($trend) {
            'improving' => " Positive trend observed — NDVI improved by {$improvPct}% over the past 10 readings.",
            'declining' => " ⚠️ Declining trend detected — NDVI dropped by " . abs($improvPct) . "% over recent readings. Investigate possible causes.",
            default     => " NDVI remains relatively stable across recent observations.",
        };

        $cloudNote = $cloud > 20
            ? " Note: {$cloud}% cloud coverage on last capture ({$dateStr}) may affect accuracy."
            : " Last satellite capture on {$dateStr} had clear sky conditions.";

        return ($statusMessages[$status] ?? '') . $trendNote . $cloudNote;
    }

    /* ═══════════════════════════════════════════════════════════════
     *  NEW API: GeoJSON FeatureCollection of all plot polygons
     *  GET /ai-crop-health-geojson?date=YYYY-MM-DD
     * ═══════════════════════════════════════════════════════════════ */
    public function plotsGeoJSON(Request $request)
    {
        try {
            $user   = auth()->user();
            $siteId = $user?->site_id;
            $date   = $request->get('date');

            // All plots with coordinates
            $allPlots = DB::table('master_plots as mp')
                ->join('blocks', 'mp.block_id', '=', 'blocks.id')
                ->join('master_sites as ms', 'blocks.site_id', '=', 'ms.id')
                ->select('mp.id as plot_id','mp.plot_name','mp.area','mp.lattitude','mp.longitude','blocks.block_name')
                ->when($siteId, fn($q) => $q->where('blocks.site_id', $siteId))
                ->whereNotNull('mp.lattitude')
                ->whereNotNull('mp.longitude')
                ->orderBy('mp.plot_name')
                ->get()->keyBy('plot_id');

            if ($allPlots->isEmpty()) {
                return response()->json(['type'=>'FeatureCollection','features'=>[]]);
            }

            // Latest NDVI records (on or before selected date)
            $sub = DB::table('ndvi_data')
                ->selectRaw('plot_id, MAX(date) as max_date')
                ->whereNotNull('average')
                ->when($date, fn($q) => $q->where('date', '<=', $date))
                ->whereIn('plot_id', $allPlots->keys()->toArray())
                ->groupBy('plot_id');

            $records = DB::table('ndvi_data as nd')
                ->joinSub($sub, 'lr', fn($j) =>
                    $j->on('nd.plot_id','=','lr.plot_id')->on('nd.date','=','lr.max_date'))
                ->select('nd.plot_id','nd.average','nd.min','nd.max','nd.status','nd.date','nd.cloud_coverage')
                ->get()->keyBy('plot_id');

            // Sparklines (last 10 per plot)
            $sparklines = DB::table('ndvi_data')
                ->whereIn('plot_id', $allPlots->keys()->toArray())
                ->whereNotNull('average')
                ->when($date, fn($q) => $q->where('date','<=',$date))
                ->orderByDesc('date')
                ->get()
                ->groupBy('plot_id')
                ->map(fn($rows) => $rows->take(10)->reverse()->values()
                    ->map(fn($x) => ['v' => round((float)$x->average, 4), 'd' => Carbon::parse($x->date)->format('d M')]));

            // Build GeoJSON features
            $features = [];
            foreach ($allPlots as $plt) {
                $lat = (float) $plt->lattitude;   // note: double-t column name
                $lng = (float) $plt->longitude;

                /**
                 * Compute a centroid-centered polygon sized by the plot's actual area.
                 *
                 * area column is in acres (1 acre = 4047 m²).
                 * We convert the square root of the area (side of an equivalent square) to degrees.
                 *   1° latitude  ≈ 111,000 m
                 *   1° longitude ≈ 111,000 × cos(lat) m
                 *
                 * Minimum display size capped at 100 m per side so tiny/null areas still show.
                 */
                $areaSqM   = max((float)($plt->area ?? 1) * 4047, 10000); // min 1 ha
                $sideMetre = sqrt($areaSqM);

                $halfLat  = ($sideMetre / 111000) / 2;
                $halfLng  = ($sideMetre / (111000 * cos(deg2rad($lat)))) / 2;

                // Box centered on centroid
                $coords = [[
                    [$lng - $halfLng, $lat - $halfLat],
                    [$lng + $halfLng, $lat - $halfLat],
                    [$lng + $halfLng, $lat + $halfLat],
                    [$lng - $halfLng, $lat + $halfLat],
                    [$lng - $halfLng, $lat - $halfLat],   // close ring
                ]];

                $r      = $records[$plt->plot_id] ?? null;
                $spark  = $sparklines[$plt->plot_id] ?? collect();

                // Trend
                $trend = 'stable'; $impPct = 0;
                if ($spark->count() >= 2) {
                    $first = (float)$spark->first()['v'];
                    $last  = (float)$spark->last()['v'];
                    $impPct = $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0;
                    if ($impPct > 2)      $trend = 'improving';
                    elseif ($impPct < -2) $trend = 'declining';
                }

                // Always derive status from getNdviStatus() — NEVER use stored $r->status
                // because the DB column was written by the old 4-tier fetch command
                // (poor/moderate/good/excellent) and is now inconsistent with our 5-tier system.
                $ndviVal = $r ? round((float)$r->average, 3) : null;
                $props = [
                    'plot_id'    => $plt->plot_id,
                    'plot_name'  => $plt->plot_name,
                    'block_name' => $plt->block_name,
                    'area'       => $plt->area,
                    'has_data'   => $r !== null,
                    'ndvi'       => $ndviVal,
                    'min'        => $r ? round((float)$r->min, 3) : null,
                    'max'        => $r ? round((float)$r->max, 3) : null,
                    'status'     => $this->getNdviStatus($ndviVal), // single source of truth
                    'trend'      => $trend,
                    'improvement'=> $impPct,
                    'last_date'  => $r ? Carbon::parse($r->date)->format('d M Y') : null,
                    'cloud'      => $r ? ($r->cloud_coverage ?? 0) : 0,
                    'sparkline'  => $spark->values(),
                    'lat'        => $lat,      // actual centroid (heat layer point)
                    'lng'        => $lng,
                ];

                $features[] = [
                    'type'       => 'Feature',
                    'geometry'   => ['type' => 'Polygon', 'coordinates' => $coords],
                    'properties' => $props,
                ];
            }

            return response()->json(['type' => 'FeatureCollection', 'features' => $features]);

        } catch (\Exception $e) {
            Log::error('plotsGeoJSON error', ['error' => $e->getMessage()]);
            return response()->json(['type'=>'FeatureCollection','features'=>[]], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  NEW API: Rule-based AI insight for a specific plot
     *  GET /ai-crop-health-insight/{plot_id}
     * ═══════════════════════════════════════════════════════════════ */
    public function aiInsight(Request $request, $plotId)
    {
        try {
            $date = $request->get('date');

            // Last 10 records for trend analysis
            $records = DB::table('ndvi_data')
                ->where('plot_id', $plotId)
                ->whereNotNull('average')
                ->when($date, fn($q) => $q->where('date', '<=', $date))
                ->orderByDesc('date')
                ->limit(10)
                ->get();

            $plot = DB::table('master_plots as mp')
                ->join('blocks','mp.block_id','=','blocks.id')
                ->select('mp.plot_name','mp.area','blocks.block_name')
                ->where('mp.id', $plotId)
                ->first();

            if ($records->isEmpty() || !$plot) {
                return response()->json([
                    'plot_name'    => $plot->plot_name ?? 'Unknown Plot',
                    'risk_level'   => 'unknown',
                    'trend'        => 'stable',
                    'ndvi'         => null,
                    'stress_reason'=> 'No satellite data available',
                    'recommendation' => 'Run ndvi:fetch to capture latest data',
                    'fertilizer_note' => '—',
                    'irrigation_status' => 'Unknown',
                    'narrative'    => 'No NDVI data available for this plot.',
                    'sparkline'    => [],
                ]);
            }

            $latest     = $records->first();
            // Round to 3dp — same as plotsGeoJSON — ensuring identical classification
            $ndvi       = round((float)$latest->average, 3);
            $status     = $this->getNdviStatus($ndvi); // single source of truth
            $cloud      = $latest->cloud_coverage ?? 0;
            $lastDate   = Carbon::parse($latest->date)->format('d M Y');

            // Trend (latest vs oldest in window)
            $oldest     = $records->last();
            $oldNdvi    = round((float)$oldest->average, 3);
            $impPct     = $oldNdvi > 0 ? round((($ndvi - $oldNdvi) / $oldNdvi) * 100, 1) : 0;
            $daysDiff   = Carbon::parse($oldest->date)->diffInDays(Carbon::parse($latest->date));
            $trend      = 'stable';
            if ($impPct > 2)      $trend = 'improving';
            elseif ($impPct < -2) $trend = 'declining';

            // Sparkline for mini chart
            $sparkline = $records->reverse()->values()->map(fn($r) => [
                'v' => round((float)$r->average, 4),
                'd' => Carbon::parse($r->date)->format('d M'),
            ]);

            // ── Rule-based logic ──────────────────────────────────────
            $riskLevel        = 'low';
            $stressReason     = 'No significant stress detected';
            $recommendation   = 'Maintain current agricultural practices';
            $fertNote         = 'Continue scheduled fertilizer program';
            $irrigationStatus = 'Not required';

            // Risk level determination
            if ($ndvi < 0.25) {
                $riskLevel = 'critical';
            } elseif ($ndvi < 0.40 || $impPct < -8) {
                $riskLevel = 'high';
            } elseif ($ndvi < 0.55 || $impPct < -3) {
                $riskLevel = 'medium';
            }

            // Stress reason detection
            if ($ndvi < 0.25) {
                $stressReason = 'Severe vegetation loss — possible crop failure or severe drought';
                $recommendation = 'Immediate field inspection and emergency irrigation required';
                $fertNote = 'Soil test required before any fertilizer application';
                $irrigationStatus = 'Emergency — within 12 hours';
            } elseif ($ndvi < 0.35 && ($trend === 'declining' || $impPct < -5)) {
                $stressReason = 'Acute water stress — rapid NDVI decline detected';
                $recommendation = 'Irrigate within 24 hours. Check soil moisture at root zone';
                $fertNote = 'Avoid fertilizer application during stress; apply post-recovery';
                $irrigationStatus = 'Required — within 24 hours';
            } elseif ($ndvi < 0.40) {
                $stressReason = 'Water stress or nutrient deficiency suspected';
                $recommendation = 'Light irrigation recommended. Check N-P-K levels';
                $fertNote = 'Consider foliar spray of micronutrients (Zn, Fe)';
                $irrigationStatus = 'Required — within 48 hours';
            } elseif ($ndvi >= 0.40 && $ndvi < 0.55 && $trend === 'declining') {
                $stressReason = 'Early water stress or possible disease onset';
                $recommendation = 'Monitor closely. Schedule irrigation within 3 days';
                $fertNote = 'Verify nitrogen level — yellowing may indicate N deficiency';
                $irrigationStatus = 'Recommended within 3 days';
            } elseif ($ndvi >= 0.40 && $ndvi < 0.55) {
                $stressReason = 'Moderate vegetation density — below optimal';
                $recommendation = 'Review irrigation schedule and apply balanced fertilizer';
                $fertNote = 'Apply NPK 20-20-0 or as per soil test recommendation';
                $irrigationStatus = 'Scheduled';
            } elseif ($ndvi >= 0.55 && $ndvi < 0.70 && $trend === 'declining') {
                $stressReason = 'Slight NDVI decline — early watch required';
                $recommendation = 'Continue monitoring. Pre-schedule next irrigation';
                $fertNote = 'Maintain current fertility program';
                $irrigationStatus = 'On schedule';
            } elseif ($ndvi >= 0.70) {
                $stressReason = 'No stress detected — excellent vegetation health';
                $recommendation = 'Maintain current practices for consistent yield';
                $fertNote = 'Continue balanced fertilizer schedule';
                $irrigationStatus = 'As scheduled';
            }

            // Narrative generation
            $trendTxt = $trend === 'declining'
                ? "a {$impPct}% NDVI decline"
                : ($trend === 'improving' ? "a +{$impPct}% NDVI improvement" : "stable NDVI");

            $periodTxt = $daysDiff > 0 ? "over the last {$daysDiff} days" : "in the latest scan";
            $cloudNote = $cloud > 20 ? " Note: {$cloud}% cloud cover may affect accuracy." : '';

            $narrative = "Plot {$plot->plot_name} recorded NDVI {$ndvi} on {$lastDate}, showing {$trendTxt} {$periodTxt}. {$stressReason}. {$recommendation}.{$cloudNote}";

            return response()->json([
                'plot_name'         => $plot->plot_name,
                'block_name'        => $plot->block_name,
                'area'              => $plot->area,
                'risk_level'        => $riskLevel,
                'trend'             => $trend,
                'improvement'       => $impPct,
                'ndvi'              => $ndvi,
                'status'            => $status,
                'last_date'         => $lastDate,
                'stress_reason'     => $stressReason,
                'recommendation'    => $recommendation,
                'fertilizer_note'   => $fertNote,
                'irrigation_status' => $irrigationStatus,
                'narrative'         => $narrative,
                'sparkline'         => $sparkline,
                'cloud'             => $cloud,
                'scans_analyzed'    => $records->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('aiInsight error', ['plot_id' => $plotId, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  NEW API: Last 30 distinct scan dates for timeline slider
     *  GET /ai-crop-health-dates
     * ═══════════════════════════════════════════════════════════════ */
    public function scanDates(Request $request)
    {
        try {
            $user   = auth()->user();
            $siteId = $user?->site_id;

            $plotIds = DB::table('master_plots as mp')
                ->join('blocks','mp.block_id','=','blocks.id')
                ->when($siteId, fn($q) => $q->where('blocks.site_id', $siteId))
                ->pluck('mp.id')->toArray();

            $dates = DB::table('ndvi_data')
                ->selectRaw('DATE(date) as scan_date')
                ->when(!empty($plotIds), fn($q) => $q->whereIn('plot_id', $plotIds))
                ->whereNotNull('average')
                ->groupBy('scan_date')
                ->orderByDesc('scan_date')
                ->limit(30)
                ->pluck('scan_date')
                ->values();

            return response()->json(['dates' => $dates]);

        } catch (\Exception $e) {
            Log::error('scanDates error', ['error' => $e->getMessage()]);
            return response()->json(['dates' => []], 500);
        }
    }
}
