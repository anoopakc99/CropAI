@extends('layouts.app')

@section('content')
<div class="D py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <button class="btn shadow-sm" style="background-color: #667E06; color: white;" data-bs-toggle="modal" data-bs-target="#notificationModal">
            <i class="fas fa-plus-circle me-2"></i> Create Notification
        </button>
    </div>

   <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="min-height: 90vh;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #667E06, #4d5e05);">
                <h5 class="modal-title fs-4" id="notificationModalLabel">
                    <span class="me-2"><i class="fas fa-bell"></i></span>
                    <span class="blink-text">Create Notification</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('notifications.store') }}" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="min-height: 60vh; max-height: 70vh; overflow-y: auto;">
                    <!-- User Selection Row -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-user me-2"></i>User
                            </label>
                            <select name="user_id" id="userSelect" class="form-select shadow-sm" required>
                                <option value="">Select User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a user</div>
                        </div>
                    </div>

                    <!-- Block and Plot Selection Row -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-building me-2"></i>Block
                            </label>
                            <select name="block_id" id="blockSelect" class="form-select shadow-sm" required disabled>
                                <option value="">Select Block</option>
                            </select>
                            <div class="invalid-feedback">Please select a block</div>
                        </div>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-map-marker-alt me-2"></i>Plot
                            </label>
                            <select name="plot_id" id="plotSelect" class="form-select shadow-sm" required disabled>
                                <option value="">Select Plot</option>
                            </select>
                            <div class="invalid-feedback">Please select a plot</div>
                        </div>
                    </div>

                    <!-- Priority, Type Row -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-flag me-2"></i>Priority
                            </label>
                            <select name="priority" class="form-select shadow-sm" required>
                                <option value="">Select Priority</option>
                                <option value="Low" class="text-success">Low</option>
                                <option value="Medium" style="color: #667E06;">Medium</option>
                                <option value="High" class="text-warning">High</option>
                                <option value="Critical" class="text-danger">Critical</option>
                            </select>
                            <div class="invalid-feedback">Please select a priority level</div>
                        </div>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-tag me-2"></i>Notification Type
                            </label>
                            <select name="notification_type" class="form-select shadow-sm" required>
                                <option value="">Select Type</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Inspection">Inspection</option>
                                <option value="Alert">Alert</option>
                            </select>
                            <div class="invalid-feedback">Please select a notification type</div>
                        </div>
                    </div>

                    <!-- Message Row -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">
                            <i class="fas fa-comment-alt me-2"></i>Notification Message
                        </label>
                        <textarea name="notification" class="form-control shadow-sm" rows="6"
                                  placeholder="Enter notification details..." required></textarea>
                        <div class="invalid-feedback">Please enter a notification message</div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3 position-sticky bottom-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn text-white shadow-sm" style="background-color: #667E06;">
                        <i class="fas fa-check me-2"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!--<div class="upcoming-week mb-5">-->
    <!--    <div class="d-flex justify-content-between align-items-center mb-4">-->
    <!--        <h3 class="fw-bold" style="color: #667E06;">-->
    <!--            <i class="fas fa-calendar-week me-2"></i>Upcoming Week-->
    <!--        </h3>-->
    <!--    </div>-->

    <!--    <div class="week-grid d-flex flex-wrap justify-content-between gap-2">-->
    <!--        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $index => $day)-->
    <!--        <div class="day-card position-relative" style="flex: 1; min-width: 140px; max-width: 180px;">-->
    <!--            <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden {{ \Carbon\Carbon::now()->format('D') === $day ? 'current-day-card' : '' }}">-->
    <!--                <div class="card-header text-center py-3 {{ $index === 0 ? 'text-white' : 'bg-light' }}"-->
    <!--                     style="{{ $index === 0 ? 'background-color: #667E06;' : '' }}">-->
    <!--                    <h5 class="card-title mb-0 fw-bold">{{ $day }}</h5>-->
    <!--                </div>-->
    <!--                <div class="card-body p-0">-->
    <!--                    @if(isset($weeklyNotifications[$day]) && count($weeklyNotifications[$day]) > 0)-->
    <!--                        <ul class="list-group list-group-flush">-->
    <!--                            @foreach($weeklyNotifications[$day] as $notification)-->
    <!--                                <li class="list-group-item border-bottom px-3 py-2 notification-item">-->
    <!--                                    <div class="d-flex align-items-center">-->
    <!--                                        <div class="notification-dot me-2"-->
    <!--                                             style="width: 8px; height: 8px; border-radius: 50%; background-color: #667E06;"></div>-->
    <!--                                        <span class="small {{ \Carbon\Carbon::now()->format('D') === $day ? 'current-day-user' : '' }}">{{ $notification->name }}</span>-->
    <!--                                    </div>-->
    <!--                                </li>-->
    <!--                            @endforeach-->
    <!--                        </ul>-->
    <!--                    @else-->
    <!--                        <div class="d-flex align-items-center justify-content-center h-100 p-4">-->
    <!--                            <span class="text-muted small">-->
    <!--                                <i class="fas fa-coffee me-2"></i>No tasks-->
    <!--                            </span>-->
    <!--                        </div>-->
    <!--                    @endif-->
    <!--                </div>-->
    <!--            </div>-->
    <!--        </div>-->
    <!--        @endforeach-->
    <!--    </div>-->
    <!--</div>-->

    <!--<div class="current-tasks">-->
    <!--    <div class="d-flex justify-content-between align-items-center mb-4">-->
    <!--        <h3 class="fw-bold" style="color: #667E06;">-->
    <!--            <i class="fas fa-tasks me-2"></i>Current Tasks-->
    <!--        </h3>-->
    <!--    </div>-->
        <form method="GET" action="{{ route('notifications.create') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control shadow-sm" value="{{ request('start_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control shadow-sm" value="{{ request('end_date') }}">
            </div>

            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn text-white shadow-sm" style="background-color: #667E06;">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>

        <div class="table-responsive shadow-sm">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Sr No.</th>
                        <th>User Name</th>
                         <th>Block</th>
                         <th>Plot</th>
                        <th>Notification</th>
                        <th>Notification Type</th>
                        <th>Priortiy</th>
                       
                        <th>Date/Time</th>
                
                    </tr>
                </thead>
                <tbody>
                @forelse($currentTasks as $index => $task)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $task->name }}</td>
                    <td>{{ $task->block_name }}</td>
                    <td>{{ $task->plot_name }}</td>
                    <td class="text-left">{{ $task->message }}</td>
                    <td>{{ $task->notification_type }}</td>
                    
                    <td>{{ $task->priority }}</td>
                    
                    <td>{{ \Carbon\Carbon::parse($task->created_at)->format('d-m-Y h:i A') }}</td> {{-- Adjust field if different --}}
                   
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No current tasks available</td>
                </tr>
                @endforelse
                </tbody>

            </table>
            <div class="mt-4 d-flex justify-content-center">
                {{ $currentTasks->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<style>
    .blink-text {
        animation: blinker 1.5s ease-in-out infinite;
    }

    @keyframes blinker {
        50% { opacity: 0.5; }
    }

    .task-card, .day-card .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .task-card:hover, .day-card .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .notification-item {
        transition: background-color 0.2s ease;
    }

    .notification-item:hover {
        background-color: rgba(102, 126, 6, 0.05);
    }

    .form-select, .form-control {
        border-radius: 0.5rem;
        border: 1px solid #e0e0e0;
        padding: 0.6rem 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-select:focus, .form-control:focus {
        border-color: #667E06;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 6, 0.15);
    }

    @media (max-width: 768px) {
        .week-grid {
            flex-direction: row;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .day-card {
            min-width: 120px;
        }
    }

    .current-day-card {
        animation: blinker 1s linear infinite alternate;
    }

    .current-day-user {
        animation: blinker 1.2s ease-in-out infinite alternate;
        font-weight: bold;
        color: #007bff; /* Example blinking color */
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('userSelect');
    const blockSelect = document.getElementById('blockSelect');
    const plotSelect = document.getElementById('plotSelect');

    // Function to load plots via AJAX
    function loadPlots(blockId, preSelectPlotName = null) {
        const userId = userSelect?.value;

        console.log('loadPlots called with:', { blockId, preSelectPlotName, userId });

        // Reset plot dropdown
        plotSelect.innerHTML = '<option value="">Select Plot</option>';
        plotSelect.disabled = true;

        if (!blockId || !userId) {
            console.warn('Missing blockId or userId, skipping loadPlots.', { blockId, userId });
            return;
        }

        plotSelect.innerHTML = '<option value="">Loading plots...</option>';

        fetch(`/notifications/get-block-plots/${blockId}?user_id=${userId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            plotSelect.innerHTML = '<option value="">Select Plot</option>';

            if (data.plots && data.plots.length > 0) {
                data.plots.forEach(plot => {
                    const option = document.createElement('option');
                    option.value = plot.id;
                    option.textContent = plot.plot_name;

                    // Pre-select if specified
                    if (preSelectPlotName && plot.plot_name === preSelectPlotName) {
                        option.selected = true;
                    }

                    plotSelect.appendChild(option);
                });

                plotSelect.disabled = false;
            } else {
                plotSelect.innerHTML = '<option value="">No plots available</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching plots:', error);
            plotSelect.innerHTML = '<option value="">Error loading plots</option>';
        });
    }

    // User change handler - Fetch blocks via AJAX
    userSelect.addEventListener('change', function() {
        const userId = this.value;

        // Reset dropdowns
        blockSelect.innerHTML = '<option value="">Select Block</option>';
        plotSelect.innerHTML = '<option value="">Select Plot</option>';
        blockSelect.disabled = true;
        plotSelect.disabled = true;

        if (!userId) return;

        blockSelect.innerHTML = '<option value="">Loading blocks...</option>';

        fetch(`/notifications/get-user-blocks/${userId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            blockSelect.innerHTML = '<option value="">Select Block</option>';

            if (data.blocks && data.blocks.length > 0) {
               data.blocks.forEach(block => {
            // Use the correct key returned from backend
            const blockValue = block.id ?? block.blockID ?? block.block_id; // try all possibilities
            const blockName = block.block_name ?? block.name ?? "Unnamed Block";
        
            const option = document.createElement('option');
            option.value = blockValue;
            option.textContent = blockName;
        
            // Pre-select user's current block if exists
            if (data.block_id && blockValue == data.block_id) {
                option.selected = true;
            }
        
            blockSelect.appendChild(option);
        });

                blockSelect.disabled = false;

                // Auto-load plots if a block is pre-selected
                if (data.block_id) {
                    loadPlots(data.block_id, data.plot_name ?? null);
                }
            } else {
                blockSelect.innerHTML = '<option value="">No blocks available</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching blocks:', error);
            blockSelect.innerHTML = '<option value="">Error loading blocks</option>';
        });
    });

    // Block change handler - Fetch plots via AJAX
    blockSelect.addEventListener('change', function() {
         
        const blockId = this.value;
        loadPlots(blockId);
    });


    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>
@endsection