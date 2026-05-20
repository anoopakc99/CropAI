@extends('layouts.app')

@section('content')
<style>
    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .upcoming-week {
        margin-bottom: 30px;
    }
    
    .week-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 15px;
        margin-top: 15px;
    }
    
    .day-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }
    
    .day-name {
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
    }
    
    .day-task {
        font-size: 14px;
        color: #555;
        margin-top: 5px;
    }
    
    .current-tasks {
        margin-top: 30px;
    }
    
    .tasks-list {
        margin-top: 15px;
    }
    
    .task-item {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .task-header {
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }
    
    .task-content {
        font-size: 14px;
        color: #666;
        padding-left: 15px;
    }
</style>

<div class="container">
    <!-- Upcoming Week -->
    <div class="upcoming-week">
        <h2>Upcoming Week Notification</h2>
        <div class="week-grid">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                <div class="day-item">
                    <div class="day-name">{{ $day }}</div>
                    @if(isset($weeklyNotifications[$day]) && count($weeklyNotifications[$day]) > 0)
                        @foreach($weeklyNotifications[$day] as $notification)
                            <div class="day-task">{{ $notification->name }}</div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Current Tasks -->
    <div class="current-tasks">
        <h2>Current Tasks</h2>
        <div class="tasks-list">
            @foreach($currentTasks as $task)
                <div class="task-item">
                    <div class="task-header">{{ $task->name }}</div>
                    <div class="task-content">
                        {{ $task->message }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsections