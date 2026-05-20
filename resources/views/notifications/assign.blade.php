@extends('layouts.app')

@section('content')
<div class="contvvainer">
    <h2>Assign Notification</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            Notification Details
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>ST No:</strong> {{ $notification->st_no }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Block:</strong> {{ $notification->block_name }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Plot:</strong> {{ $notification->plot_name }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Priority:</strong> 
                        <span class="badge 
                            @if($notification->priority == 'Low') badge-info 
                            @elseif($notification->priority == 'Medium') badge-warning 
                            @elseif($notification->priority == 'High') badge-danger 
                            @else badge-dark @endif">
                            {{ $notification->priority }}
                        </span>
                    </p>
                </div>
                <div class="col-md-4">
                    <p><strong>Type:</strong> {{ $notification->notification_type }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Status:</strong> {{ $notification->status }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p><strong>Message:</strong> {{ $notification->message }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Assign to User
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('notifications.assign', $notification->id) }}">
                @csrf
                
                <div class="form-group">
                    <label for="assignedTo">Select User</label>
                    <select name="assigned_to" id="assignedTo" class="form-control" required>
                        <option value="">-- Select User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Schedule Days</label>
                    <div class="row">
                        @foreach(['Mon', 'Tue', 'Wed', 'Thurs', 'Fri', 'Sat', 'Sun'] as $day)
                            <div class="col-md-3 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="day-{{ $day }}" name="days[]" value="{{ $day }}">
                                    <label class="custom-control-label" for="day-{{ $day }}">{{ $day }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Assign Notification</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection