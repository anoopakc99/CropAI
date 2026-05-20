<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VoiceSowingController extends Controller
{
    /**
     * Parse recognized voice text to extract block and plot and convert to IDs.
     * Returns array: ['block_id'=>int|null, 'plot_id'=>int|null, 'query_type'=>'sowing', 'error'=>null]
     */
    public function parseVoice($text)
    {
        $result = [
            'block_id' => null,
            'plot_id' => null,
            'query_type' => 'sowing',
            'error' => null,
        ];

        if (!$text) {
            $result['error'] = 'Empty text';
            return $result;
        }

        $upper = mb_strtoupper($text);

        // Try to extract plot like A1, B12 etc.
        $plotName = null;
        if (preg_match('/\b([A-Z]\d{1,2})\b/', $upper, $m)) {
            $plotName = $m[1];
        }

        // Try to extract explicit 'Block X'
        $blockLetter = null;
        if (preg_match('/BLOCK\s*([A-Z])\b/', $upper, $m2)) {
            $blockLetter = $m2[1];
        }

        // If plotName found, fetch master_plots by plot_name.
        if ($plotName) {
            $plot = DB::table('master_plots')->where('plot_name', $plotName)->first();
            if ($plot) {
                $result['plot_id'] = $plot->id;
                // determine block id from plot record (preferred)
                if (isset($plot->block_id) && $plot->block_id) {
                    $result['block_id'] = $plot->block_id;
                }
            } else {
                // If no plot matched exact, attempt case-insensitive like
                $plot = DB::table('master_plots')->whereRaw('UPPER(plot_name) = ?', [strtoupper($plotName)])->first();
                if ($plot) {
                    $result['plot_id'] = $plot->id;
                    $result['block_id'] = $plot->block_id ?? null;
                }
            }
        }

        // If block letter explicitly found, resolve it to block id.
        if ($blockLetter) {
            $block = DB::table('blocks')->where('block_name', $blockLetter)->first();
            if (!$block) {
                $block = DB::table('blocks')->whereRaw('UPPER(block_name) = ?', [strtoupper($blockLetter)])->first();
            }
            if ($block) {
                $result['block_id'] = $block->id;
            }
        }

        // If plot wasn't found but block present and plotName not present, we leave plot_id null.
        if (!$result['block_id'] && $result['plot_id']) {
            // try getting block id from master_plots as fallback
            $plot = DB::table('master_plots')->where('id', $result['plot_id'])->first();
            if ($plot && isset($plot->block_id)) {
                $result['block_id'] = $plot->block_id;
            }
        }

        if (!$result['plot_id'] && !$result['block_id']) {
            $result['error'] = 'Could not detect block or plot from voice text.';
        }

        return $result;
    }

    /**
     * Handle POST /api/voice-sowing
     * Accepts JSON { text: 'recognized text' }
     */
    public function voiceFetch(Request $request)
    {
        $text = $request->input('text', '');

        $parsed = $this->parseVoice($text);

        if (isset($parsed['error'])) {
            return response()->json(['success' => false, 'message' => $parsed['error']], 422);
        }

        $block_id = $parsed['block_id'];
        $plot_id = $parsed['plot_id'];

        // Build query using the misnamed fields: sowing_operations.block_name = block_id, sowing_operations.plot_name = plot_id
        $query = DB::table('showing_oprations');
        if ($plot_id) {
            $query->where('plot_name', $plot_id);
        }
        if ($block_id) {
            $query->where('block_name', $block_id);
        }

        $sow = $query->orderBy('date', 'desc')->first();

        if (!$sow) {
            return response()->json(['success' => false, 'message' => 'No sowing record found for provided block/plot.']);
        }

        // Resolve related names for better human-readable output
        $methodName = null;
        if (!empty($sow->sowing_method_id)) {
            $methodName = DB::table('master_seed')->where('id', $sow->sowing_method_id)->value('sowing_method');
            if (!$methodName) {
                // fallback: maybe sowing_method_id already contains a label
                $methodName = $sow->sowing_method_id;
            }
        }

        $machineName = null;
        if (!empty($sow->machine_id)) {
            $m = DB::table('master_machine')->where('id', $sow->machine_id)->first();
            if ($m) {
                $machineName = trim(($m->machine_name ?? '') . (isset($m->machine_no) && $m->machine_no ? ' ' . $m->machine_no : ''));
            } else {
                $machineName = $sow->machine_id;
            }
        }

        $tractorName = null;
        if (!empty($sow->tractor_id)) {
            $t = DB::table('master_tractors')->where('id', $sow->tractor_id)->first();
            if ($t) {
                $tractorName = $t->tractor_name ?? $sow->tractor_id;
            } else {
                $tractorName = $sow->tractor_id;
            }
        }

        // Format human-readable output. Use Carbon for date formatting.
        $dt = null;
        if (!empty($sow->date)) {
            try {
                $dt = Carbon::parse($sow->date)->format('d M Y');
            } catch (\Exception $e) {
                $dt = $sow->date;
            }
        }

        $lines = [];
        $plotNameReadable = null;
        // try to fetch plot_name text from master_plots
        if ($plot_id) {
            $mp = DB::table('master_plots')->where('id', $plot_id)->first();
            if ($mp && isset($mp->plot_name)) $plotNameReadable = $mp->plot_name;
        }

        if (!$plotNameReadable && isset($sow->plot_name)) {
            // if sowing_operations.plot_name stored id, try to fetch
            $mp = DB::table('master_plots')->where('id', $sow->plot_name)->first();
            if ($mp) $plotNameReadable = $mp->plot_name;
        }

        $title = 'Sowing';
        if ($plotNameReadable) {
            $title .= " for Plot {$plotNameReadable}";
        }
        if ($dt) {
            $title .= " was performed on {$dt}.";
        }

        $lines[] = $title;

        $seedUsed = $sow->seed_consumption ?? null;
        if (is_numeric($seedUsed)) $seedUsed = $seedUsed . ' KG';

        $rowDist = $sow->row_distance ?? null;
        if (is_numeric($rowDist)) $rowDist = $rowDist . ' cm';

        $depth = $sow->sowing_depth ?? null;
        if (is_numeric($depth)) $depth = $depth . ' cm';

        $map = [
            'Variety' => $sow->variety ?? null,
            'Seed Used' => $seedUsed,
            'Method' => $methodName ?? null,
            'Row Distance' => $rowDist,
            'Depth' => $depth,
            'Area' => $sow->area ?? null,
            'Area Covered' => $sow->area_covered ?? null,
            'Machine' => $machineName ?? null,
            'Tractor' => $tractorName ?? null,
            'Diesel Used' => $sow->hsd_consumption ?? null,
            'Start Time' => $sow->start_time ?? null,
            'End Time' => $sow->end_time ?? null,
            'Hours Used' => $sow->hours_used ?? null,
        ];

        foreach ($map as $k => $v) {
            if ($v !== null && $v !== '') {
                $lines[] = "{$k}: {$v}";
            }
        }

        $humanText = implode("\n", $lines);

        return response()->json([
            'success' => true,
            'query_text' => $text,
            'parsed' => $parsed,
            'data' => $sow,
            'text' => $humanText,
        ]);
    }
}
