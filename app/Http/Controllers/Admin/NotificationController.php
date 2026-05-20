<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
USE CArbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{


public function create(Request $request)
{
    $user = Auth::user();
    
    // Get distinct blocks
    $blocks = DB::table('users')
        ->whereNotNull('block_name')
        ->distinct()
        ->pluck('block_name');
    
    // Get plots with their blocks
    $plots = DB::table('users')
        ->whereNotNull('plot_name')
        ->select('plot_name', 'block_name')
        ->get();
    
    // Get users for the current site
    $users = DB::table('users')
        ->where('site_id', $user->site_id)
        ->where('role', 4)
        ->select('id', 'name', 'block_name', 'plot_name', 'role')
        ->get();
    
    // Weekly Notifications with block and plot names
    $startOfWeek = Carbon::now()->startOfWeek();
    $weeklyNotifications = [];
    
    for ($i = 0; $i < 7; $i++) {
        $day = $startOfWeek->copy()->addDays($i);
        $dayName = $day->format('D');
        
        $notifications = DB::table('notifications')
            ->join('users', 'users.id', '=', 'notifications.user_id')
            ->leftJoin('blocks as block_user', 'block_user.id', '=', 'notifications.block_name')
            ->leftJoin('master_plots as plot_user', 'plot_user.id', '=', 'notifications.plot_name')
            ->whereDate('notifications.created_at', $day->format('Y-m-d'))
            ->select(
                'users.name',
                'notifications.message',
                'notifications.status',
                'notifications.priority',
                'notifications.notification_type',
                'block_user.block_name',
                'plot_user.plot_name'
            )
            ->get();
        
        $weeklyNotifications[$dayName] = $notifications;
    }
    
    // Date filter
    $startDate = $request->get('start_date');
    $endDate = $request->get('end_date');
    
    // Current tasks query with block and plot joins
    $currentTasksQuery = DB::table('notifications')
        ->join('users', 'users.id', '=', 'notifications.user_id')
        ->leftJoin('blocks as block_user', 'block_user.id', '=', 'notifications.block_name')
        ->leftJoin('master_plots as plot_user', 'plot_user.id', '=', 'notifications.plot_name')
        ->where('users.site_id', $user->site_id)
        ->orderByDesc('notifications.created_at')
        ->select(
            'users.name',
            'block_user.block_name',
            'plot_user.plot_name',
            'notifications.message',
            'notifications.priority',
            'notifications.notification_type',
            'notifications.created_at'
        );
    
    if ($startDate && $endDate) {
        $currentTasksQuery->whereBetween(DB::raw('DATE(notifications.created_at)'), [$startDate, $endDate]);
    } else {
        $currentTasksQuery->whereDate('notifications.created_at', '>=', Carbon::now()->subDays(7));
    }
    
    // Paginate current tasks
    $currentTasks = $currentTasksQuery->paginate(10)->withQueryString();
    
    return view('notifications.create', compact(
        'blocks',
        'plots',
        'users',
        'weeklyNotifications',
        'currentTasks'
    ));
}

    // Get user details via AJAX
    public function getUserDetails($userId)
    {
        $user = DB::table('users')
            ->where('id', $userId)
            ->select('block_name', 'plot_name', 'role')
            ->first();
            
        return response()->json($user);
    }

    // Get plots for selected block via AJAX
    public function getPlots($block)
    {
        $plots = DB::table('users')
            ->where('block_name', $block)
            ->distinct()
            ->pluck('plot_name');
            
        return response()->json($plots);
    }

    // Store new notification
  public function store(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'priority' => 'required|in:Low,Medium,High,Critical',
        'notification_type' => 'required|string',
        'notification' => 'required|string',
        'block_id' => 'required',
        'plot_id' => 'required',
        'roll' => 'nullable|string', // Make roll optional
    ]);
    
    // Generate ST number
    $stNo = 'ST-' . strtoupper(Str::random(6));
    $loggedInUser = Auth::user();
    $site_id = $loggedInUser->site_id; 
    // Insert notification with null roll if not provided
    DB::table('notifications')->insert([
        'st_no' => $stNo,
        'user_id' => $validated['user_id'],
        'priority' => $validated['priority'],
        'block_name' => $validated['block_id'],
        'plot_name' => $validated['plot_id'],
        'notification_type' => $validated['notification_type'],
        'message' => $validated['notification'],
        'status' => 'Pending',
        'site_id'=> $site_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    return redirect()->route('notifications.create')->with('success', 'Notification created successfully!');
}
    // Show weekly notifications
public function weekly()
{
    $startOfWeek = Carbon::now()->startOfWeek(); // Monday
    $weeklyNotifications = [];

    for ($i = 0; $i < 7; $i++) {
        $day = $startOfWeek->copy()->addDays($i);
        $dayName = $day->format('D'); // Mon, Tue, etc.

        $notifications = DB::select("
            SELECT users.name, notifications.message, notifications.status
            FROM notifications
            JOIN users ON users.id = notifications.user_id
            WHERE DATE(notifications.created_at) = ?
        ", [$day->format('Y-m-d')]);

        $weeklyNotifications[$dayName] = $notifications;
    }

    // Get current tasks
    $currentTasks = DB::select("
        SELECT users.name, notifications.message
        FROM notifications
        JOIN users ON users.id = notifications.user_id
        WHERE DATE(notifications.created_at) >= ?
        ORDER BY notifications.created_at DESC
        LIMIT 3
    ", [Carbon::now()->subDays(7)->format('Y-m-d')]);

    return view('notifications.weekly', compact('weeklyNotifications', 'currentTasks'));
}
    
    // Assign notification to user
    public function assign(Request $request, $notificationId)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'days' => 'required|array',
            'days.*' => 'in:Mon,Tue,Wed,Thurs,Fri,Sat,Sun',
        ]);
        
        DB::transaction(function () use ($notificationId, $validated) {
            // Create assignment
            DB::table('notification_assignments')->insert([
                'notification_id' => $notificationId,
                'assigned_to' => $validated['assigned_to'],
                'assigned_by' => auth()->id(),
                'status' => 'Assigned',
                'assigned_at' => now(),
            ]);
            
            // Update notification status
            DB::table('notifications')
                ->where('id', $notificationId)
                ->update(['status' => 'Assigned']);
                
            // Create schedule entries
            $scheduleData = array_map(function ($day) use ($notificationId) {
                return [
                    'notification_id' => $notificationId,
                    'day_of_week' => $day,
                    'status' => 'Assigned',
                ];
            }, $validated['days']);
            
            DB::table('notification_schedule')->insert($scheduleData);
        });
        
        return redirect()->back()->with('success', 'Notification assigned successfully!');
    }
    
  public function getUserBlocks($userId)
{
    try {
        \Log::info('getUserBlocks called with userId: ' . $userId);

        // Get all unique blocks assigned to the selected user
        $userBlocks = DB::table('user_plots')
            ->join('blocks', 'user_plots.block_id', '=', 'blocks.id')
            ->where('user_plots.user_id', $userId)
            ->select('blocks.id as block_id', 'blocks.block_name')
            ->distinct()
            ->get();

        \Log::info('User blocks data:', ['data' => $userBlocks]);

        if ($userBlocks->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No blocks assigned to this user',
                'blocks' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'blocks' => $userBlocks,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getUserBlocks: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'An error occurred',
            'message' => $e->getMessage(),
            'blocks' => [],
        ], 500);
    }
}

public function getBlockPlots($blockId)
{
    try {
        $userId = request()->input('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => 'User ID required',
                'plots' => []
            ], 400);
        }

        // Get only the plots assigned to this user for this specific block
        $plots = DB::table('user_plots')
            ->join('master_plots', 'user_plots.plot_id', '=', 'master_plots.id')
            ->where('user_plots.user_id', $userId)
            ->where('user_plots.block_id', $blockId)
            ->select('master_plots.id', 'master_plots.plot_name')
            ->get();

        \Log::info('Plots for user ' . $userId . ' and block ' . $blockId, ['data' => $plots]);

        if ($plots->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No plots assigned for this block and user',
                'plots' => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'plots' => $plots
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getBlockPlots: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'An error occurred',
            'message' => $e->getMessage(),
            'plots' => []
        ], 500);
    }
}
}