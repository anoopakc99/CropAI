<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Don't forget to import Auth
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    /**
     * Display a listing of the users based on user role and site access.
     */
public function index(Request $request)
{
    $authenticatedUser = Auth::user();
    $query = DB::table('users');

    // Apply role-based and site-based filtering

        // Admin (all users) → no filter
    if ($authenticatedUser->role == 2 || $authenticatedUser->role == 3) {
        $query->where('site_id', $authenticatedUser->site_id);

    }
    // Search filter
    if ($request->has('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $users = $query->orderBy('id', 'desc')->paginate(10);
//dd($users);
    // Blocks
    if ($authenticatedUser->role == 1) {
        $blocks = DB::table('blocks')->select('id', 'block_name')->distinct()->get();
    } else {
        $userSiteIds = explode(',', $authenticatedUser->site_id);
        $blocks = DB::table('master_land')
            ->join('master_sites', 'master_land.site_id', '=', 'master_sites.id')
            ->join('blocks', 'master_land.block_name', '=', 'blocks.id')
            ->whereIn('master_land.site_id', $userSiteIds)
            ->select('blocks.id', 'blocks.block_name')
            ->distinct()
            ->get();
    }

    // Sites (for Add User modal)
    $sites = collect();
    $siteIds = explode(',', $authenticatedUser->site_id);

    if ($authenticatedUser->role == 1) {
        $sites = DB::table('master_sites')->select('id', 'site_name')->get();
    } elseif (in_array($authenticatedUser->role, [2, 3, 4])) {
        $sites = DB::table('master_sites')->select('id', 'site_name')
            ->whereIn('id', $siteIds)
            ->get();
    }

    return view('admin.user', compact('users', 'blocks', 'sites'));
}



    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:6',
        'user_type' => 'required|string',
        'site_name' => 'required', // Add site_id validation
        // 'block_ids' => 'required|array|min:1',
        // 'plots' => 'required|array|min:1',
    ]);

    DB::beginTransaction();
    try {

        if($request->user_type == 2){
            $usertype = "Admin";
        }elseif($request->user_type == 3){
            $usertype = "Fodder Crop Officer";
        }elseif($request->user_type == 4){
            $usertype = "Supervisor";
        }else{
            $usertype = null;
        }

        // Create User
        $userId = DB::table('users')->insertGetId([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'user_type' => $usertype,
            'role' => $request->user_type,
            'site_id' => $request->site_name, // ✅ Add site_id

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Arrays to collect assigned blocks and plots for notification
        $assignedBlocks = [];
        $assignedPlots = [];

        if ($request->plots && is_array($request->plots)) {
            foreach ($request->plots as $blockId => $plotIds) {
                // Get block name
                $block = DB::table('blocks')->where('id', $blockId)->first();
                $blockName = $block ? $block->block_name : "Block #$blockId";

                if (!in_array($blockName, $assignedBlocks)) {
                    $assignedBlocks[] = $blockName;
                }

                foreach ($plotIds as $plotId) {
                    $plot = DB::table('master_plots')->where('id', $plotId)->first();

                    if ($plot) {
                        DB::table('user_plots')->insert([
                            'user_id'   => $userId,
                            'block_id'  => $blockId,
                            'plot_id'   => $plotId,
                            'created_at'=> now(),
                            'updated_at'=> now(),
                        ]);

                        // Collect plot name for notification
                        $plotName = $plot->plot_name ?? "Plot #$plotId";
                        $assignedPlots[] = "$plotName ($blockName)";
                    }
                }
            }
        }

        // Send notification if blocks/plots were assigned
        if (!empty($assignedBlocks) || !empty($assignedPlots)) {
            // Generate ST Number
            $stNo = 'ST-' . time() . '-' . $userId;

            // Create notification message
            $message = "Welcome! You have been assigned to the following blocks and plots:\n\n";

            if (!empty($assignedBlocks)) {
                $message .= "Blocks: " . implode(', ', $assignedBlocks) . "\n";
            }

            if (!empty($assignedPlots)) {
                $message .= "Plots: " . implode(', ', $assignedPlots);
            }

            // Insert notification
            DB::table('notifications')->insert([
                'st_no' => $stNo,
                'user_id' => $userId,
                'priority' => 'High',
                'notification_type' => 'New Assignment',
                'message' => $message,
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Optional: Send welcome notification even without plots
        if (empty($assignedBlocks) && empty($assignedPlots)) {
            $stNo = 'ST-' . time() . '-' . $userId;

            DB::table('notifications')->insert([
                'st_no' => $stNo,
                'user_id' => $userId,
                'priority' => 'Medium',
                'notification_type' => 'Welcome',
                'message' => "Welcome to the system! Your account has been created successfully as $usertype.",
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();
        return redirect()->route('users.index')->with('success', 'User created successfully with multiple blocks & plots.');
    } catch (\Exception $e) {
        //dd($e->getMessage());
        DB::rollBack();
        \Log::error('User creation failed: ' . $e->getMessage()); // Add logging
        return back()->withInput()->with('error', 'Error: '.$e->getMessage());
    }
}


public function edit($id)
{
    // Get user
    $user = DB::table('users')->where('id', $id)->first();

    // Get all blocks
    $blocks = DB::table('blocks as b')
    ->join('master_land as ml', 'b.id', '=', 'ml.block_name') // only blocks present in master_land
    ->select('b.id', 'b.block_name')
    ->where('ml.site_id', $user->site_id)
    ->distinct() // to avoid duplicates if multiple entries in master_land
    ->get();

    // Get plots grouped by block from master_plots
    $plots = [];
    foreach ($blocks as $block) {
        $plots[$block->id] = DB::table('master_plots')
            ->where('block_id', $block->id) // master_plots has block_id
            ->select('id', 'plot_name', 'area')
            ->get();
    }

    // Get user assigned blocks & plots
    $user_blocks = DB::table('user_plots')
        ->where('user_id', $id)
        ->pluck('block_id')
        ->toArray();

    $user_plot_ids = DB::table('user_plots')
        ->where('user_id', $id)
        ->pluck('plot_id')
        ->toArray();

    return response()->json([
        'user'        => $user,
        'blocks'      => $blocks,
        'plots'       => $plots,
        'user_blocks' => $user_blocks,
        'user_plots'  => $user_plot_ids, // just IDs for JS
    ]);
}

public function update(Request $request, $id)
{
    // Validation
    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $id,
        // 'user_type' => 'required|in:3,4', // optional
    ]);

    // Update user basic info
    DB::table('users')->where('id', $id)->update([
        'name'  => $request->name,
        'email' => $request->email,
        'updated_at' => now(),
        // 'user_type' => $request->user_type,
    ]);

    // Only handle blocks & plots if provided
    if ($request->filled('block_ids') && is_array($request->block_ids)) {
        // Clear old user plots
        DB::table('user_plots')->where('user_id', $id)->delete();

        // Arrays to collect assigned blocks and plots for notification
        $assignedBlocks = [];
        $assignedPlots = [];

        // Insert new plots
        foreach ($request->block_ids as $blockId) {
            if (!empty($request->plots[$blockId]) && is_array($request->plots[$blockId])) {
                // Get block name
                $block = DB::table('blocks')->where('id', $blockId)->first();
                $blockName = $block ? $block->block_name : "Block #$blockId";

                if (!in_array($blockName, $assignedBlocks)) {
                    $assignedBlocks[] = $blockName;
                }

                foreach ($request->plots[$blockId] as $plotId) {
                    $plot = DB::table('master_plots')->where('id', $plotId)->first();
                    if ($plot) {
                        DB::table('user_plots')->insert([
                            'user_id'    => $id,
                            'block_id'   => $blockId,
                            'plot_id'    => $plotId,
                            'created_at' => now(),
                            'updated_at' => now(),
                            // 'area'     => $plot->area, // optional
                        ]);

                        // Collect plot name for notification
                        $plotName = $plot->plot_name ?? "Plot #$plotId";
                        $assignedPlots[] = "$plotName ($blockName)";
                    }
                }
            }
        }

        // Send notification if blocks/plots were assigned
        if (!empty($assignedBlocks) || !empty($assignedPlots)) {
            // Generate ST Number (you can customize this logic)
            $stNo = 'ST-' . time() . '-' . $id;

            // Create notification message
            $message = "New blocks and plots have been assigned to you:\n\n";

            if (!empty($assignedBlocks)) {
                $message .= "Blocks: " . implode(', ', $assignedBlocks) . "\n";
            }

            if (!empty($assignedPlots)) {
                $message .= "Plots: " . implode(', ', $assignedPlots);
            }

            // Insert notification
            DB::table('notifications')->insert([
                'st_no' => $stNo,
                'user_id' => $id,
                'priority' => 'Medium', // You can make this configurable
                'notification_type' => 'Assignment', // or 'Block Assignment'
                'message' => $message,
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    return redirect()->route('users.index')->with('success', 'User updated successfully!');
}


public function destroy($id)
{
    $authenticatedUser = Auth::user();
    $userToDelete = DB::table('users')->find($id);

    if (!$userToDelete) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Authorization checks
    if ($authenticatedUser->role == 2 && str_contains($authenticatedUser->site_id, '1') && $userToDelete->site_name != 1) {
        return response()->json(['error' => 'Unauthorized to delete this user.'], 403);
    }
    if ($authenticatedUser->role == 3 && str_contains($authenticatedUser->site_id, '2') && $userToDelete->site_name != 2) {
        return response()->json(['error' => 'Unauthorized to delete this user.'], 403);
    }
    if ($authenticatedUser->role == 5 && str_contains($authenticatedUser->site_id, '4') && $userToDelete->site_name != 4) {
        return response()->json(['error' => 'Unauthorized to delete this user.'], 403);
    }

    DB::table('users')->where('id', $id)->delete();
    return redirect()->back()->with('success', 'User deleted successfully.');
}

// Updated helper method to get plots based on user's site access
public function getPlots($blockId)
{
    $user = Auth::user();

    // Base query from master_plots joined with blocks
    $query = DB::table('master_plots as mp')
        ->join('blocks as b', 'mp.block_id', '=', 'b.id')
        ->where('b.id', $blockId)
        ->select('mp.id', 'mp.plot_name', 'mp.area');

    // Restrict plots by user's site if not superadmin
    if ($user->role != 1) {
        $userSiteIds = explode(',', $user->site_id);
        $query->whereIn('b.site_id', $userSiteIds);
    }

    $plots = $query->get();

    // If no plots found, return a default option
    if ($plots->isEmpty()) {
        return response()->json([
            ['id' => null, 'plot_name' => 'N/A', 'area' => 0]
        ]);
    }

    return response()->json($plots);
}


public function getArea(Request $request)
{
    $authenticatedUser = Auth::user();
    $blockName = $request->block_name;
    $plotName = $request->plot_name;

    $query = DB::table('master_land')->where('block_name', $blockName);

    // Add site restriction for non-superadmin users
    if ($authenticatedUser->role != 1) {
        $userSiteIds = explode(',', $authenticatedUser->site_id);
        $query->whereIn('site_id', $userSiteIds);
    }

    if ($plotName === 'N/A') {
        $area = $query->whereNull('plot_no')->whereNull('st_no')->value('area_ha');
    } else {
        $area = $query->where(function($subQuery) use ($plotName) {
            $subQuery->where('plot_no', $plotName)->orWhere('st_no', $plotName);
        })->value('area_ha');
    }

    return response()->json($area ?: 0);
}
public function show($id)
{
    // First fetch user basic info
    $user = DB::table('users')->where('id', $id)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    }

    // Fetch only assigned blocks & plots for this user
    $blocks = DB::table('user_plots as up')
        ->join('blocks as b', 'up.block_id', '=', 'b.id')
        ->join('master_plots as p', 'up.plot_id', '=', 'p.id') // join directly on assigned plots
        ->where('up.user_id', $id)
        ->select('b.id as block_id', 'b.block_name', 'p.id as plot_id', 'p.plot_name')
        ->get();

    // Group plots under their block
    $groupedBlocks = [];
    foreach ($blocks as $row) {
        if (!isset($groupedBlocks[$row->block_id])) {
            $groupedBlocks[$row->block_id] = [
                'block_id'   => $row->block_id,
                'block_name' => $row->block_name,
                'plots'      => []
            ];
        }

        $groupedBlocks[$row->block_id]['plots'][] = [
            'plot_id'   => $row->plot_id,
            'plot_name' => $row->plot_name,
        ];
    }
      if($user->role == 2){
          $usertype = "Admin";
        }elseif($user->role == 3){
          $usertype = "Fodder Crop Officer";
        }elseif($user->role == 4){
          $usertype = "Supervisor";
        }else{
            $usertype = "Unknown";
        }
    return response()->json([
        'success' => true,
        'data' => [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'user_type' => $usertype,
            'role'      => $user->role,
            'blocks'    => array_values($groupedBlocks)
        ]
    ]);
}



public function changePassword(Request $request)
{
    // Validation
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'password' => 'required|min:6|confirmed'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // Fetch user
    $user = DB::table('users')->where('id', $request->user_id)->first();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    }

    // Update password
    DB::table('users')->where('id', $request->user_id)->update([
        'password' => Hash::make($request->password)
    ]);

    // Send email with new password
//     try {
//         Mail::send('mail.change-password', [
//             'name'     => $user->name,
//             'password' => $request->password
//         ], function($message) use ($user) {
//             $message->to($user->email)
//                     ->subject('Your password has been changed');
// });
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => true,
//             'message' => 'Password changed successfully, but email could not be sent: ' . $e->getMessage()
//         ]);
//     }

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully and email sent to user.'
    ]);
}

}
