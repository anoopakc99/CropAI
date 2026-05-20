@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.apexcharts-legend-group {
    flex-direction: row !important;
    display: flex !important;
    flex-wrap: nowrap !important;
}
.day-item {
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.day-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.day-item.current-day {
    border: 2px solid #4CAF50;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

.day-item.current-day::before {
    content: 'TODAY';
    position: absolute;
    top: -8px;
    right: -8px;
    background: #4CAF50;
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: bold;
}

.task-count {
    margin-top: 8px;
    font-size: 11px;
    color: #666;
    font-weight: 600;
}

.day-item.current-day .task-count {
    color: #4CAF50;
}
.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 24px;
    margin-bottom: 20px;
}

.chart-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.chart-card h3 .chart-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.chart-card h3 .chart-header-right {
    display: flex;
    gap: 5px;
}

.chart-card h3 i {
    color: #667E06;
    font-size: 16px;
}

/* Add Rainfall Button */
.add-rainfall-btn {
    background: #667E06;
    color: white;
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(102, 126, 6, 0.2);
}

.add-rainfall-btn:hover {
    background: #506205;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(102, 126, 6, 0.3);
}

/* Modal Styles */
.rainfall-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.rainfall-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.rainfall-modal-header {
    background: linear-gradient(135deg, #667E06, #506205);
    color: white;
    padding: 20px 24px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rainfall-modal-header h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rainfall-close {
    color: white;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.rainfall-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.rainfall-modal-body {
    padding: 24px;
}

.rainfall-input-section {
    margin-bottom: 24px;
}

.rainfall-input-section h5 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rainfall-input-group {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    align-items: end;
    margin-bottom: 16px;
}

.rainfall-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.rainfall-form-group label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.rainfall-form-group input {
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.rainfall-form-group input:focus {
    outline: none;
    border-color: #667E06;
    box-shadow: 0 0 0 3px rgba(102, 126, 6, 0.1);
}

.rainfall-add-btn {
    background: #667E06;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    height: fit-content;
}

.rainfall-add-btn:hover {
    background: #506205;
}

.rainfall-comparison {
    background: #f8fafc;
    border-radius: 8px;
    padding: 16px;
    border-left: 4px solid #667E06;
}

.rainfall-comparison h5 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 12px;
}

.comparison-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.comparison-item:last-child {
    border-bottom: none;
}

.comparison-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.comparison-value {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.comparison-difference {
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.difference-positive {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.difference-negative {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.difference-neutral {
    background: rgba(107, 114, 128, 0.1);
    color: #6b7280;
}

.rainfall-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.rainfall-cancel-btn, .rainfall-save-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.rainfall-cancel-btn {
    background: #f3f4f6;
    color: #374151;
}

.rainfall-cancel-btn:hover {
    background: #e5e7eb;
}

.rainfall-save-btn {
    background: #667E06;
    color: white;
}

.rainfall-save-btn:hover {
    background: #506205;
}

/* Tasks Container */
.tasks-list-container {
    max-height: 150px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
}

.tasks-list-container::-webkit-scrollbar {
    width: 6px;
}

.tasks-list-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.tasks-list-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.tasks-list-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Task Item */
.task-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 12px;
    padding: 8px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.task-item:hover {
    border-color: #667E06;
    box-shadow: 0 2px 8px rgba(102, 126, 6, 0.1);
    transform: translateY(-1px);
}

/* Priority Border */
.task-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #667E06;
}

.notification-priority-high::before {
    background: #ef4444;
}

.notification-priority-medium::before {
    background: #f59e0b;
}

.notification-priority-low::before {
    background: #10b981;
}

/* Task Header */
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    /*margin-bottom: 12px;*/
    flex-wrap: wrap;
    gap: 8px;
    width: 140px;
}

.task-assignee {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.assignee-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.assignee-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 4px;
}

.task-type {
    display: flex;
    align-items: center;
    gap: 6px;
}

.type-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
}

.type-name {
    font-size: 12px;
    font-weight: 600;
    color: #667E06;
    background: rgba(102, 126, 6, 0.1);
    padding: 4px 8px;
    border-radius: 12px;
    text-transform: capitalize;
}

/* Task Content */
/*.task-content {*/
/*    margin-bottom: 12px;*/
/*}*/

.task-message {
    font-size: 14px;
    color: #334155;
    line-height: 1.5;
    margin-bottom: 10px;
    font-weight: 400;
}

/* Task Details */
.task-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 8px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #64748b;
    background: #f8fafc;
    padding: 4px 8px;
    border-radius: 4px;
}

.detail-item i {
    font-size: 10px;
    opacity: 0.7;
}

/* Status Specific Colors */
.status-pending {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
}

.status-completed {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.status-resolved {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.status-in-progress {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

/* Task Footer */
.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
    padding-top: 8px;
    margin-top: 8px;
}

.timestamp {
    font-size: 11px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 4px;
}

.timestamp i {
    font-size: 10px;
}

.created-at {
    color: #64748b;
}

.updated-at {
    color: #667E06;
}

/* No Tasks State */
.no-tasks {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.no-tasks p:first-child {
    font-size: 16px;
    font-weight: 500;
    color: #334155;
    margin-bottom: 8px;
}

.no-tasks p:first-child i {
    color: #10b981;
    margin-right: 8px;
}

.no-tasks .text-muted {
    font-size: 14px;
    color: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .task-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .task-details {
        flex-direction: column;
        gap: 6px;
    }

    .task-footer {
        flex-direction: column;
        align-items: flex-start;
    }

    .rainfall-modal-content {
        margin: 10% auto;
        width: 95%;
    }

    .rainfall-input-group {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

/* Animation for new tasks */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.task-item {
    animation: slideIn 0.3s ease;
}

/* Rainfall Chart Styles */
.rainfall-chart {
    margin-top: 20px;
}

.rainfall-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 15px;
    background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
    border-radius: 8px;
    border-left: 4px solid #2196f3;
}

.rainfall-summary {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.rainfall-total {
    font-size: 14px;
    font-weight: 600;
    color: #1976d2;
}

.rainfall-avg {
    font-size: 12px;
    color: #666;
}

.rainfall-icon {
    font-size: 24px;
    color: #2196f3;
}
.stats-cards-row {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    gap: 20px;
    flex-wrap: nowrap; 
    height: 120px;
}

.stat-card-summary {
    flex: 1; 
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-width: 180px;
    margin-bottom: 15px;
    
}

.stat-card-summary:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
}

.icon-area {
    font-size: 32px;
    margin-right: 15px;
    color: #FE4D43;
}

.info-area .title {
    font-size: 14px;
    font-weight: 600;
    color: #555;
}

.info-area .value {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}
.stat-card-summary .icon-area {
    font-size: 32px;
    margin-right: 15px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background-color: #5E8C6A;
}

/* Icon colors for each card */
.stat-card-summary.sites .icon-area {
    color: #fff;
}

.stat-card-summary.area .icon-area {
    color: #fff;
}

.stat-card-summary.production .icon-area {
    color: #fff;
}

.stat-card-summary.machines .icon-area {
    color: #fff !important;
}


.stat-card-summary.diesel .icon-area {
    color: #fff;
}

/* Colored borders */
.sites { border-left: 5px solid #7fa831; }
.area { border-left: 5px solid #7fa831; }
.production { border-left: 5px solid #7fa831; }
.machines { border-left: 5px solid #7fa831; }
.diesel { border-left: 5px solid #7fa831; }

.container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #1e40af; }
        .chart-container { width: 100%; height: 450px; margin-bottom: 20px; }
        .back-button { 
            display: none; 
            margin-bottom: 15px; 
            padding: 8px 15px;
            background-color: #059669; /* Green button */
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        .instructions { text-align: center; color: #4b5563; margin-bottom: 15px; }
.apexcharts-legend {
    display: flex !important;
    flex-wrap: nowrap !important;
    justify-content: center !important;
    gap: 20px !important;
}
</style>

<link href="{{ asset('css/dashboard.css') }}" rel="stylesheet" />
<div class="dashboard-container" style="margin: 0px auto;">

    @if(isset($displayMessage) && $displayMessage)
        <div class="alert alert-warning chart-card" style="margin-bottom: 20px; padding: 15px; background-color: #fff3cd; border-color: #ffeeba; color: #856404;">
            {{ $displayMessage }}
        </div>
    @endif
<div class="banner">
        <div class="banner-content">
            <h5>Crop AI: Where Every Insight Grows Trust, From Production to Harvesting!</h4>
            <p>Monitor every stage — from sowing to silage — with real-time insights, performance tracking, and transparent reporting.</p>
        </div>
        <div class="banner-image">
            @if(Auth::user()->site_id == 3)
            <img src="{{ asset('dashboard/dhamroad.jpeg') }}" alt="Dhamroad" onerror="this.style.display='none'" style="border-radius: 10px;"> 
            @else
            <img src="{{ asset('dashboard/andeshnagar.jpeg') }}" alt="Andeshnagar" onerror="this.style.display='none'" style="border-radius: 10px;">
            @endif
        </div>
    </div>

<div class="summary-container-wrapper">
    <div class="stats-cards-row">
    <a href="{{ url('user') }}" class="stat-card-summary sites">
        <div class="icon-area">
            <img src="{{ asset('assets/img/gif/users.gif') }}" alt="Users Icon" class="summary-icon-gif" style="width: 60px;">
        </div>
        <div class="info-area">
            <div class="title">Users</div>
            <div class="value">{{ $userCount ?? 0 }}</div>
        </div>
    </a>
     <a href="{{ url('seeds') }}" class="stat-card-summary production">
        <div class="icon-area">
            <img src="{{ asset('assets/img/gif/crop.gif') }}" alt="Crops Icon" class="summary-icon-gif" style="width: 60px;">
        </div>
        <div class="info-area">
            <div class="title">Crops</div>
            <div class="value">{{ $cropCount ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ url('fertilizers') }}" class="stat-card-summary area">
        <div class="icon-area">
            <img src="{{ asset('assets/img/gif/fertilizers.gif') }}" alt="Fertilizer Types Icon" class="summary-icon-gif" style="width: 60px;">
        </div>
        <div class="info-area">
            <div class="title">Fertilizers</div>
            <div class="value">{{ $fertilizerCount ?? 0 }}</div>
        </div>
    </a>

   

    <a href="{{ url('machines') }}" class="stat-card-summary machines">
        <div class="icon-area">
            <img src="{{ asset('assets/img/gif/tractor.gif') }}" alt="Total Machines Icon" class="summary-icon-gif" style="width: 100px;">
        </div>
        <div class="info-area">
            <div class="title"> Machines</div>
            <div class="value">{{ $machineCount ?? 0 }}</div>
        </div>
    </a>

    <a href="{{ url('diesels') }}" class="stat-card-summary diesel">
    <div class="icon-area">
        <img src="{{ asset('assets/img/gif/fuel.gif') }}" alt="Diesel Stock Icon" class="summary-icon-gif" style="width: 60px;">
    </div>
    <div class="info-area">
        <div class="title">Diesel</div>
        <div class="value">{{ $totalDiesel ?? 0 }} L</div>
    </div>
</a>
    </div>
</div>

    <div class="main-content-grid">
        <div class="left-column">
            <div class="weather-widget" style="height: 445px;">
                <div class="weather-header">
                    <div class="location-info">
                        <div class="location-name">Lucknow</div>
                        <div class="location-day">Friday</div> </div>
                    <div class="temp-display">
                        <div class="current-temp">38</div> </div>
                </div>

                <div class="weather-metrics">
                    <div class="metric-item">
                        <div class="metric-icon"><i class="fas fa-temperature-high"></i></div>
                        <div class="metric-value">40&deg;C</div> <div class="metric-label">Feels Like</div>
                    </div>

                    <div class="metric-item">
                        <div class="metric-icon"><i class="fas fa-tint"></i></div>
                        <div class="metric-value humidity-value">35%</div> <div class="metric-label">Humidity</div>
                    </div>
                </div>
                 <div class="weather-metrics">
                    <div class="metric-item">
                        <div class="metric-icon"><i class="fas fa-wind"></i></div>
                        <div class="metric-value wind-speed">1.4 m/s</div> <div class="metric-label">Wind</div>
                    </div>

                    <div class="metric-item">
                        <div class="metric-icon"><i class="fas fa-cloud-rain"></i></div>
                        <div class="metric-value precipitation-value">0.0 mm</div> <div class="metric-label">Precipitation</div>
                    </div>
                </div>

                <div class="sun-timeline">
                    <div class="sun-time">
                        <div class="sunrise">
                            <div class="time">7:00 am</div> <div class="label">Sunrise</div>
                        </div>
                        <div class="sun-progress">
                            <div class="progress-bar">
                                <div class="sun-position"></div>
                            </div>
                        </div>
                        <div class="sunset">
                            <div class="time">8:00 pm</div> <div class="label">Sunset</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chart-card rainfall-chart" style="height: 415px;">
                <h3>
                    <div class="chart-header-left">
                        <i class="fas fa-cloud-rain" style="color: #667E06;"></i> Rainfall Chart
                    </div>
                    <div class="chart-header-right">
                    <button id="exportRainfallExcel" class="add-rainfall-btn" title="Download Excel">
                       <i class="fas fa-file-excel"></i>
                    </button>

                    <button class="add-rainfall-btn" id="addRainfallBtn" title="Add Manual Rainfall Data">
                        <i class="fas fa-plus"></i>
                    </button>
                    </div>
                </h3>
                <div class="rainfall-info">
                    <div class="rainfall-summary">
                        <div class="rainfall-total">Total Rainfall: <span id="totalRainfall">{{ $totalRainfall }} mm</span></div>
                        <div class="rainfall-avg">Average: <span id="avgRainfall"></span>{{$avgRainfall}} mm/day</span></div>
                    </div>
                    <div class="rainfall-icon">
                        <i class="fas fa-cloud-rain-heavy"></i>
                    </div>
                </div>
                <div class="chart-container" style="height:200px;">
                    <canvas id="rainfallChart"></canvas>
                </div>
            </div>

            <div id="rainfallModal" class="rainfall-modal">
                <form action="{{ url('rainfall/store') }}" method="POST">
                   @csrf
                <div class="rainfall-modal-content">
                    <div class="rainfall-modal-header">
                        <h4><i class="fas fa-cloud-rain"></i> Manual Rainfall Entry</h4>
                        <button class="rainfall-close">&times;</button>
                    </div>
                    <div class="rainfall-modal-body">
                        <div class="rainfall-input-section">
                            <h5><i class="fas fa-keyboard"></i> Add Rainfall Data</h5>
                            <div class="rainfall-input-group">
                                <div class="rainfall-form-group">
                                    <label for="rainfallDate">Date</label>
                                    <input type="date" id="rainfallDate" name="rainfallDate" required>
                                </div>
                                <div class="rainfall-form-group">
                                    <label for="rainfallAmount">Rainfall (mm)</label>
                                    <input type="number" id="rainfallAmount" name="rainfallAmount" step="0.1" min="0" placeholder="0.0" required>
                                </div>
                            </div>
                        </div>


                    </div>
                    <div class="rainfall-modal-footer">
                        <button class="rainfall-cancel-btn" id="cancelRainfallModal">Cancel</button>
                        <button type="submit" class="rainfall-save-btn" id="saveRainfallData">Save Changes</button>
                    </div>
                </div>
                </form>
            </div>
            <div class="chart-card seed-stock">
                <h3>
                    <div class="chart-header-left">
                     <i class="fas fa-seedling" style="color: #667E06;"></i> Seed Stock
                    </div>
                    <div class="chart-header-right">
                     
                    </div>
                </h3>
                <div class="chart-container">
                    <canvas id="seedChart"></canvas>
                </div>
                
            </div>
          

            <div class="chart-card land-stage-report">
                 <h3>
                    <div class="chart-header-left">
                     <i class="fas fa-chart-pie" style="color: #667E06;"></i> Land Stage Report
                    </div>
                    <div class="chart-header-right">
                     
                    </div>
                </h3>
                
                <div class="report-content">
                    <div class="chart-container">
                        <canvas id="landStageChart"></canvas>
                    </div>
                     
                </div>
            </div>
        </div>

<div class="right-column">
    <div class="chart-card upcoming-week">
        <h3>
            <div class="chart-header-left">
             <i class="fas fa-calendar-week" style="color: #667E06;"></i>Week Notification
            </div>
            <div class="chart-header-right">
             <a href="{{ url('notification/create') }}">
            <span><i class="fas fa-arrow-right" style="color: #667E06;"></i></span>
        </a>
            </div>
        </h3>
     
        <div class="week-grid">
            @php
                $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $currentDay = now()->format('l');
            @endphp
            
            @foreach($daysOfWeek as $day)
                @php
                    $dayShort = substr($day, 0, 3);
                    $dayStatus = $weekStatuses->get($day);
                    $taskCount = $dayStatus->task_count ?? 0;
                    $status = $dayStatus->latest_status ?? 'assigned';
                    $statusClass = strtolower($status);
                    
                    if (in_array($statusClass, ['completed', 'resolved'])) {
                        $statusClass = 'completed';
                    } elseif (in_array($statusClass, ['pending', 'in progress'])) {
                        $statusClass = 'pending';
                    } elseif ($statusClass == 'on it') {
                        $statusClass = 'on-it';
                    } else {
                        $statusClass = 'assigned';
                    }
                    
                    $isCurrentDay = ($day === $currentDay);
                @endphp
                
                <div class="day-item {{ $isCurrentDay ? 'current-day' : '' }}" 
                    onclick="filterTasksByDay('{{ $day }}')">
                    <div class="day-name">{{ $dayShort }}</div>
                    <div class="day-status {{ $statusClass }}"></div>
                    <div class="task-count">{{ $taskCount }} task{{ $taskCount != 1 ? 's' : '' }}</div>
                </div>
            @endforeach
        </div>
    </div>

            
    <div class="chart-card current-tasks" style="height: 235px;">
        <h3>
            <i class="fas fa-tasks" style="color: #667E06;"></i> 
            <span id="tasks-title"> Current Tasks</span>
        </h3>
        <div class="tasks-list-container custom-scrollbar">
            @forelse($notifications as $notification)
                <div class="task-item notification-priority-{{ strtolower($notification->priority ?? 'low') }}">
                    <div class="task-header">
                        <div class="task-assignee">
                            <span class="assignee-label">Assigned:</span>
                            <span class="assignee-name">{{ $notification->user_name ?? $notification->user_no ?? 'N/A' }}</span>
                        </div>
                        <div class="task-type">
                            <span class="type-label">Type:</span>
                            <span class="type-name">{{ $notification->notification_type ?? 'Task' }}</span>
                        </div>
                    </div>
                    <div class="task-content">
                        <p class="task-message">{{ $notification->message ?? 'No message' }}</p>
                        <div class="task-details">
                            @if($notification->block_name)
                                <span class="detail-item"><i class="fas fa-cube"></i> Block: {{ $notification->block_name }}</span>
                            @endif
                            @if($notification->plot_name)
                                <span class="detail-item"><i class="fas fa-map-marker-alt"></i> Plot: {{ $notification->plot_name }}</span>
                            @endif
                            @if($notification->status)
                                <span class="detail-item status-{{ strtolower($notification->status) }}"><i class="fas fa-info-circle"></i> Status: {{ $notification->status }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="task-footer">
                        <span class="timestamp created-at"><i class="far fa-clock"></i>  {{ Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                        
                    </div>
                </div>
            @empty
                <div class="no-tasks">
                    <p><i class="fas fa-check-circle"></i> No current tasks or notifications found.</p>
                    <p class="text-muted">Good job, everything is up-to-date!</p>
                </div>
            @endforelse
        </div>
    
</div>
    

            <div class="report-grid" style="height: 415px;">
                <div class="chart-card report-card">
                    <h3>
                        <div class="chart-header-left">
                         <i class="fas fa-flask" style="color: #667E06;"></i> Fertilizers Usage
                        </div>
                        <div class="chart-header-right">
                         
                        </div>
                    </h3>
                    
                    <div class="chart-container">
                        <canvas id="fertilizerChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="color-indicator total"></span>
                            <span class="legend-label">Remaining Stock</span>
                        </div>
                        <div class="legend-item">
                            <span class="color-indicator consumed"></span>
                            <span class="legend-label">Consumed</span>
                        </div>
                    </div>
                </div>

                <div class="chart-card report-card" style="height: 415px;">
                    <h3>
                        <div class="chart-header-left">
                         <i  class="fas fa-leaf" style="color: #667E06;"></i> Hay Usage
                        </div>
                        <div class="chart-header-right">
                         
                        </div>
                    </h3>
                     
                    <div class="chart-container">
                        <canvas id="hayChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="color-indicator total"></span>
                            <span class="legend-label">Remaining Stock</span>
                        </div>
                        <div class="legend-item">
                            <span class="color-indicator consumed"></span>
                            <span class="legend-label">Consumed</span>
                        </div>
                    </div>
                </div>
            </div>

<div class="chart-card land-stage-report" style="height: 822px; padding:20px;">
    
    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div class="chart-header-left" style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-chart-pie" style="color: #667E06; font-size:20px;"></i>
            <h3 style="margin:0;">Cultivation Area Overview</h3>
        </div>

        <div class="chart-header-right">
            <!-- future filters/buttons -->
        </div>
    </div>

    <!-- THREE CARDS -->
    <div style="display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap;">
        
        <!-- Total Area -->
        <div style="
            padding:18px; 
            background:#eaf3ff; 
            border-radius:12px; 
            width:220px; 
            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <h4 style="margin:0; font-size:18px; color:#0d47a1;">Total Area</h4>
            <h3 id="totalArea" style="margin:0; font-weight:bold; color:#002171;">0 Acre</h3>
        </div>

        <!-- Cultivated Area -->
        <div style="
            padding:18px; 
            background:#fff3cd; 
            border-radius:12px; 
            width:220px; 
            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <h4 style="margin:0; font-size:18px; color:#996300;">Cultivated Area</h4>
            <h3 id="totalSown" style="margin:0; font-weight:bold; color:#7a4f00;">0 Acre</h3>
        </div>

        <!-- Coverage % -->
        <div style="
            padding:18px; 
            background:#e8ffe8; 
            border-radius:12px; 
            width:220px; 
            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <h4 style="margin:0; font-size:18px; color:#0a8a0a;">Coverage %</h4>
            <h3 id="coveragePercent" style="margin:0; font-weight:bold; color:#066606;">0%</h3>
        </div>
    </div>

    <!-- Chart -->
    <div id="modernBarLineChart"></div>

</div>
     
</div>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const rainfallData = {
        labels: @json($labels),
        data: @json($data)
    };
 
document.addEventListener('DOMContentLoaded', function() {
    const apiKey = 'b41274328f52dcb04a9f6aff4c48c85a';
    const usersite = {{ Auth::user()->site_id }};
    let lat, lon, locationName;

    if (usersite === 2) {
        lat = '13.3378381';
        lon = '80.1928933';
        locationName = 'Alamadhi';
    } else if (usersite === 1) {
        lat = '28.028404622457526';
        lon = '80.74739795870505';
        locationName = 'Andeshnagar';
    }else if (usersite === 3) {
        lat = '21.511871';
        lon = '73.003653';
        locationName = 'Dhamroad';
    }else {
        lat = '28.028404622457526';
        lon = '80.74739795870505';
        locationName = 'Lucknow';
    }

    // Rainfall Modal Logic
    const modal = document.getElementById('rainfallModal');
    const addBtn = document.getElementById('addRainfallBtn');
    const closeBtn = document.querySelector('.rainfall-close');
    const cancelBtn = document.getElementById('cancelRainfallModal');
    const saveBtn = document.getElementById('saveRainfallData');
    const addDataBtn = document.getElementById('saveRainfallData');

    // Store manual rainfall data and API comparison
    let manualRainfallData = {};
    let apiRainfallData = {};
    let rainfallChart;

    // Modal Event Listeners
    addBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        // Set today's date as default
        document.getElementById('rainfallDate').value = new Date().toISOString().split('T')[0];
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Add rainfall data
    addDataBtn.addEventListener('click', () => {
        const date = document.getElementById('rainfallDate').value;
        const amount = parseFloat(document.getElementById('rainfallAmount').value);

        if (!date || isNaN(amount) || amount < 0) {
            alert('Please enter a valid date and rainfall amount.');
            return;
        }

        // Store manual data
        manualRainfallData[date] = amount;

        // Show comparison if we have API data for the same date
        updateComparison();

        // Clear inputs
        document.getElementById('rainfallDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('rainfallAmount').value = '';

        // Update chart with manual data
        updateRainfallChart();
    });

    // Save changes
    saveBtn.addEventListener('click', () => {
        // Here you would typically send data to server
        console.log('Saving rainfall data:', manualRainfallData);
        modal.style.display = 'none';

    });

    function updateComparison() {
        const comparisonDiv = document.getElementById('rainfallComparison');
        const comparisonData = document.getElementById('comparisonData');

        if (Object.keys(manualRainfallData).length === 0) {
            comparisonDiv.style.display = 'none';
            return;
        }

        comparisonDiv.style.display = 'block';
        let comparisonHTML = '';

        Object.keys(manualRainfallData).forEach(date => {
            const manualAmount = manualRainfallData[date];
            const apiAmount = apiRainfallData[date] || 0;
            const difference = manualAmount - apiAmount;

            let diffClass = 'difference-neutral';
            let diffText = 'Same';

            if (difference > 0) {
                diffClass = 'difference-positive';
                diffText = `+${difference.toFixed(1)} mm more`;
            } else if (difference < 0) {
                diffClass = 'difference-negative';
                diffText = `${Math.abs(difference).toFixed(1)} mm less`;
            }

            comparisonHTML += `
                <div class="comparison-item">
                    <span class="comparison-label">${date}</span>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                        <div style="font-size: 12px; color: #64748b;">
                            Manual: ${manualAmount}mm | API: ${apiAmount}mm
                        </div>
                        <span class="comparison-difference ${diffClass}">${diffText}</span>
                    </div>
                </div>
            `;
        });

        comparisonData.innerHTML = comparisonHTML;
    }

    function updateRainfallChart() {
        if (!rainfallChart) return;

        // Combine API and manual data
        const combinedData = [...rainfallData.data];
        const today = new Date();

        // Update chart data with manual entries
        Object.keys(manualRainfallData).forEach(date => {
            const dateObj = new Date(date);
            const daysDiff = Math.floor((today - dateObj) / (1000 * 60 * 60 * 24));

            if (daysDiff >= 0 && daysDiff < 7) {
                combinedData[6 - daysDiff] = manualRainfallData[date];
            }
        });

        rainfallChart.data.datasets[0].data = combinedData;
        rainfallChart.update();

        // Update totals
        const totalRainfall = combinedData.reduce((sum, val) => sum + val, 0);
        const avgRainfall = totalRainfall / combinedData.length;
        document.getElementById('totalRainfall').textContent = `${totalRainfall.toFixed(1)} mm`;
        document.getElementById('avgRainfall').textContent = `${avgRainfall.toFixed(1)} mm/day`;
    }

    function updateWeather() {
        const apiUrl = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric`;

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    console.error('Weather API response error:', response.status, response.statusText);
                    return response.json().then(errData => {
                        throw new Error(`Network response was not ok: ${response.status} ${response.statusText}. API Message: ${errData.message || 'No specific message'}`);
                    }).catch(() => {
                           throw new Error(`Network response was not ok: ${response.status} ${response.statusText}. Could not parse error response.`);
                    });
                }
                return response.json();
            })
            .then(data => {
                updateCurrentWeather(data);
                positionSun(data);

                // Store API rainfall data for comparison
                const today = new Date().toISOString().split('T')[0];
                if (data.rain && data.rain['1h']) {
                    apiRainfallData[today] = data.rain['1h'];
                } else if (data.rain && data.rain['3h']) {
                    apiRainfallData[today] = data.rain['3h'];
                }
            })
            .catch(error => {
                console.error('Error fetching or processing weather data:', error);
            });
    }

    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const today = new Date();
    const locationDayElement = document.querySelector('.location-day');
    if (locationDayElement) {
        locationDayElement.textContent = days[today.getDay()];
    }

    function updateCurrentWeather(data) {
        if (!data || !data.weather || data.weather.length === 0 || !data.main || !data.wind) {
            console.error('Invalid or incomplete weather data received for current weather:', data);
            document.querySelector('.current-temp').textContent = 'N/A';
            document.querySelector('.weather-metrics .metric-item:nth-child(1) .metric-value').textContent = 'N/A';
            document.querySelector('.humidity-value').textContent = 'N/A';
            document.querySelector('.wind-speed').textContent = 'N/A';
            document.querySelector('.precipitation-value').textContent = 'N/A';
            document.querySelector('.location-name').textContent = 'Weather Unavailable';
            return;
        }

        const weather = data.weather[0];
        const main = data.main;
        const wind = data.wind;

    const currentTempElement = document.querySelector('.current-temp');
    if (currentTempElement) currentTempElement.innerHTML = `${Math.round(main.temp)}&deg;C`;

    // Fix: Set 'Feels Like' value using main.feels_like
    const feelsLikeElement = document.querySelector('.weather-metrics .metric-item:nth-child(1) .metric-value');
    if (feelsLikeElement) feelsLikeElement.innerHTML = `${Math.round(main.feels_like)}&deg;C`;

        const humidityElement = document.querySelector('.humidity-value');
        if (humidityElement) humidityElement.textContent = `${main.humidity}%`;

        const windElement = document.querySelector('.wind-speed');
        if (windElement) windElement.textContent = `${wind.speed.toFixed(1)} m/s`;

        let precipitation = 0;
        if (data.rain && data.rain['1h']) {
            precipitation = data.rain['1h'];
        } else if (data.rain && data.rain['3h']) {
             precipitation = data.rain['3h'];
        } else if (data.snow && data.snow['1h']) {
            precipitation = data.snow['1h'];
        } else if (data.snow && data.snow['3h']) {
            precipitation = data.snow['3h'];
        }
        const precipitationElement = document.querySelector('.precipitation-value');
        if (precipitationElement) precipitationElement.textContent = `${precipitation.toFixed(1)} mm`;

        const locationNameElement = document.querySelector('.location-name');
        if (locationNameElement && data.name) {
            locationNameElement.textContent = data.name;
        } else if (locationNameElement) {
            locationNameElement.textContent = "Current Location";
        }
    }

    function positionSun(data) {
        if (!data.sys || !data.sys.sunrise || !data.sys.sunset) {
            console.error('Sunrise/sunset data not available for sun positioning:', data);
            return;
        }

        const now = new Date().getTime();
        const sunriseTime = data.sys.sunrise * 1000;
        const sunsetTime = data.sys.sunset * 1000;

        const sunriseDate = new Date(sunriseTime);
        const sunsetDate = new Date(sunsetTime);

        const sunriseElement = document.querySelector('.sunrise .time');
        if (sunriseElement) sunriseElement.textContent = sunriseDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true});

        const sunsetElement = document.querySelector('.sunset .time');
        if (sunsetElement) sunsetElement.textContent = sunsetDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true});

        const sunIcon = document.querySelector('.sun-position');
        if (sunIcon) {
            if (now < sunriseTime || now > sunsetTime) {
                sunIcon.style.left = (now < sunriseTime && now < sunsetTime) ? '0%' : '100%';
                if (sunIcon.classList.contains('animated')) {
                    sunIcon.classList.remove('animated');
                }
            } else {
                const dayLength = sunsetTime - sunriseTime;
                if (dayLength <= 0) {
                    sunIcon.style.left = '50%';
                    return;
                }
                const timeSinceSunrise = now - sunriseTime;
                let percentage = (timeSinceSunrise / dayLength) * 100;
                percentage = Math.min(Math.max(percentage, 0), 100);
                sunIcon.style.left = `${percentage}%`;
                if (!sunIcon.classList.contains('animated')) {
                    sunIcon.classList.add('animated');
                }
            }
        }
    }

    if (typeof updateWeather === "function") {
        updateWeather();
        setInterval(updateWeather, 30 * 60 * 1000);
    }

    // Rainfall Chart Data - Last 7 days with daily data
const rainfallChartCtx = document.getElementById('rainfallChart');
if (rainfallChartCtx) {
    rainfallChart = new Chart(rainfallChartCtx, {
        type: 'bar',
        data: {
            labels: rainfallData.labels,
            datasets: [{
                label: 'Daily Rainfall (mm)',
                data: rainfallData.data,
                backgroundColor: rainfallData.data.map(value => {
                    if (value === 0) return '#e5e7eb';
                    if (value < 10) return '#93c5fd';
                    if (value < 20) return '#60a5fa';
                    if (value < 30) return '#3b82f6';
                    return '#1d4ed8';
                }),
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: { }
    });

    // Update rainfall summary
    const totalRainfall = rainfallData.data.reduce((sum, val) => sum + val, 0);
    const avgRainfall = totalRainfall / rainfallData.data.length;
    document.getElementById('totalRainfall').textContent = `${totalRainfall.toFixed(1)} mm`;
    document.getElementById('avgRainfall').textContent = `${avgRainfall.toFixed(1)} mm/day`;
}
// export rainfall chart data
document.getElementById("exportRainfallExcel").addEventListener("click", function () {
    // Chart data
    const labels = rainfallData.labels;
    const values = rainfallData.data;

    // Prepare rows
    const rows = [["S.No", "Date", "Rainfall (mm)"]];
    labels.forEach((date, index) => {
        rows.push([index + 1, date, values[index]]);
    });

    // Create worksheet
    const worksheet = XLSX.utils.aoa_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Rainfall Data");

    // Export file
    XLSX.writeFile(workbook, "rainfall_data.xlsx");
});

// Total area and cultivate area
let sowingData = {!! $sowingProgressJson !!};

let totalAreaAll = 0;
let totalSownAll = 0;

// Prepare block-level aggregated data
let blockData = {};
Object.keys(sowingData).forEach(blockName => {
    let blockTotal = 0;
    let blockSown = 0;
    
    Object.keys(sowingData[blockName].plots).forEach(plotName => {
        let p = sowingData[blockName].plots[plotName];
        blockTotal += parseFloat(p.total);
        blockSown += parseFloat(p.sown);
        totalAreaAll += parseFloat(p.total);
        totalSownAll += parseFloat(p.sown);
    });
    
    blockData[blockName] = {
        total: blockTotal,
        sown: blockSown,
        coverage: blockTotal > 0 ? ((blockSown / blockTotal) * 100).toFixed(1) : 0
    };
});

// Update summary boxes
document.getElementById("totalArea").innerHTML = totalAreaAll.toFixed(2) + " Acre";
document.getElementById("totalSown").innerHTML = totalSownAll.toFixed(2) + " Acre";

// Coverage percent
let totalCoverage = totalAreaAll > 0 
    ? ((totalSownAll / totalAreaAll) * 100).toFixed(1)
    : 0;
document.getElementById("coveragePercent").innerHTML = totalCoverage + "%";

// Initial state: Show blocks
let currentView = 'blocks';
let selectedBlock = null;
let chart = null;

function renderBlockView() {
    currentView = 'blocks';
    selectedBlock = null;
    
    let blockNames = [];
    let blockTotalArea = [];
    let blockSownArea = [];
    let blockCoverage = [];
    
    Object.keys(blockData).forEach(blockName => {
        blockNames.push(blockName);
        blockTotalArea.push(blockData[blockName].total);
        blockSownArea.push(blockData[blockName].sown);
        blockCoverage.push(parseFloat(blockData[blockName].coverage));
    });
    
    let options = {
        chart: {
            height: 450,
            type: 'line',
            stacked: false,
            toolbar: { show: true },
            dropShadow: {
                enabled: true,
                top: 5,
                left: 3,
                blur: 8,
                opacity: 0.2
            },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    let blockIndex = config.dataPointIndex;
                    let clickedBlock = blockNames[blockIndex];
                    renderPlotView(clickedBlock);
                }
            }
        },
        series: [
            {
                name: "Total Area",
                type: "bar",
                data: blockTotalArea
            },
            {
                name: "Sown Area",
                type: "bar",
                data: blockSownArea
            },
            {
                name: "Coverage %",
                type: "line",
                data: blockCoverage
            }
        ],
        colors: ["#7c9414", "#F5D020", "#FF8F00"],
        plotOptions: {
            bar: {
                columnWidth: '50%',
                borderRadius: 6,
                distributed: false
            }
        },
        stroke: {
            width: [0, 0, 3],
            curve: "smooth"
        },
        xaxis: {
            categories: blockNames,
            labels: { 
                rotate: -45, 
                style: { fontSize: "14px", fontWeight: 600 }
            },
            title: {
                text: "Blocks (Click to view plots)",
                style: { fontSize: "13px", color: "#666" }
            }
        },
        yaxis: [
            {
                title: { text: "Area (Acre)" }
            },
            {
                opposite: true,
                title: { text: "Coverage %" },
                max: 100
            }
        ],
        dataLabels: {
            enabled: false
        },
        tooltip: {
            shared: true,
            intersect: false,
            custom: function({series, seriesIndex, dataPointIndex, w}) {
                let blockName = blockNames[dataPointIndex];
                return `<div style="padding: 10px; background: white; border: 1px solid #ddd; border-radius: 6px;">
                    <strong>${blockName}</strong><br/>
                    <span style="color: #7c9414;">●</span> Total: ${series[0][dataPointIndex].toFixed(2)} Acre<br/>
                    <span style="color: #F5D020;">●</span> Sown: ${series[1][dataPointIndex].toFixed(2)} Acre<br/>
                    <span style="color: #FF8F00;">●</span> Coverage: ${series[2][dataPointIndex]}%<br/>
                    <em style="color: #999; font-size: 11px;">Click to view plots</em>
                </div>`;
            }
        },
        legend: {
            position: "bottom",
            horizontalAlign: "center",
            fontSize: "14px",
            markers: {
                width: 14,
                height: 14,
                strokeWidth: 0,
                radius: 6,
            },
            itemMargin: {
                horizontal: 40,
                vertical: 30
            }
        },
        fill: {
            type: ["gradient", "gradient", "solid"],
            gradient: {
                shade: "light",
                type: "vertical",
                shadeIntensity: 0.5,
                gradientToColors: ["#6AA0FF", "#4DE08F"],
                inverseColors: false,
                opacityFrom: 0.9,
                opacityTo: 0.7,
            }
        }
    };
    
    if (chart) {
        chart.destroy();
    }
    chart = new ApexCharts(document.querySelector("#modernBarLineChart"), options);
    chart.render();
}

function renderPlotView(blockName) {
    currentView = 'plots';
    selectedBlock = blockName;
    
    let plotNames = [];
    let plotTotalArea = [];
    let plotSownArea = [];
    let plotCoverage = [];
    
    Object.keys(sowingData[blockName].plots).forEach(plotName => {
        let p = sowingData[blockName].plots[plotName];
        plotNames.push(plotName);
        plotTotalArea.push(p.total);
        plotSownArea.push(p.sown);
        let percent = p.total > 0 ? ((p.sown / p.total) * 100).toFixed(1) : 0;
        plotCoverage.push(parseFloat(percent));
    });
    
    let options = {
        chart: {
            height: 450,
            type: 'line',
            stacked: false,
            toolbar: { 
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true,
                    customIcons: [{
                        icon: '<span style="font-size:16px;">← Back</span>',
                        index: -1,
                        title: 'Back to Blocks',
                        class: 'custom-icon',
                        click: function(chart, options, e) {
                            renderBlockView();
                        }
                    }]
                }
            },
            dropShadow: {
                enabled: true,
                top: 5,
                left: 3,
                blur: 8,
                opacity: 0.2
            }
        },
        series: [
            {
                name: "Total Area",
                type: "bar",
                data: plotTotalArea
            },
            {
                name: "Sown Area",
                type: "bar",
                data: plotSownArea
            },
            {
                name: "Coverage %",
                type: "line",
                data: plotCoverage
            }
        ],
        colors: ["#7c9414", "#F5D020", "#FF8F00"],
        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 6,
                distributed: false
            }
        },
        stroke: {
            width: [0, 0, 3],
            curve: "smooth"
        },
        xaxis: {
            categories: plotNames,
            labels: { 
                rotate: -45, 
                style: { fontSize: "13px" }
            },
            title: {
                text: `Plots in ${blockName} (Click "← Back" to return)`,
                style: { fontSize: "13px", color: "#666", fontWeight: 600 }
            }
        },
        yaxis: [
            {
                title: { text: "Area (Acre)" }
            },
            {
                opposite: true,
                title: { text: "Coverage %" },
                max: 100
            }
        ],
        dataLabels: {
            enabled: false
        },
        tooltip: {
            shared: true,
            intersect: false
        },
        legend: {
            position: "bottom",
            horizontalAlign: "center",
            fontSize: "14px",
            markers: {
                width: 14,
                height: 14,
                strokeWidth: 0,
                radius: 6,
            },
            itemMargin: {
                horizontal: 40,
                vertical: 30
            }
        },
        fill: {
            type: ["gradient", "gradient", "solid"],
            gradient: {
                shade: "light",
                type: "vertical",
                shadeIntensity: 0.5,
                gradientToColors: ["#6AA0FF", "#4DE08F"],
                inverseColors: false,
                opacityFrom: 0.9,
                opacityTo: 0.7,
            }
        }
    };
    
    if (chart) {
        chart.destroy();
    }
    chart = new ApexCharts(document.querySelector("#modernBarLineChart"), options);
    chart.render();
}

// Initial render: Show blocks
renderBlockView();




    const landStageDataValues = [
        {{ isset($landStageData['Standing Crop']) ? (float)$landStageData['Standing Crop'] : 0 }},
        {{ isset($landStageData['Crop Sowing within month']) ? (float)$landStageData['Crop Sowing within month'] : 0 }},
        {{ isset($landStageData['Empty Plot']) ? (float)$landStageData['Empty Plot'] : 0 }}
    ];
    const landStageChartCtx = document.getElementById('landStageChart');
    if (landStageChartCtx && landStageDataValues.some(v => v > 0)) {
        new Chart(landStageChartCtx, {
            type: 'pie',
            data: {
                labels: ['Standing Crop', 'Crop Sowing', 'Empty Plot'],
                datasets: [{
                    data: landStageDataValues,
                    // UPDATED: Set Standing Crop (index 0) to #7c9414
                    backgroundColor: ['#7c9414', '#F5D020', '#909B37'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed.toFixed(2) + '%';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else if (landStageChartCtx) {
        landStageChartCtx.getContext('2d').fillText("No data available for Land Stage Report.", 10, 50);
        console.warn('Land Stage Chart: No data to display or sum of data is zero.');
    } else {
        console.warn('Land Stage Chart: Canvas element not found.');
    }

    const fertilizerRemaining = {{ (isset($totalStock) ? (float)$totalStock : 0) }};
    const fertilizerConsumed = {{ isset($totalUsed) ? (float)$totalUsed : 0 }};
    const fertilizerChartCtx = document.getElementById('fertilizerChart');
    if (fertilizerChartCtx && (fertilizerRemaining >= 0 || fertilizerConsumed > 0)) {
        new Chart(fertilizerChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Remaining Stock', 'Consumed'],
                datasets: [{
                    data: [Math.max(0, fertilizerRemaining), fertilizerConsumed],
                    // UPDATED: Set Remaining Stock (index 0) to #7c9414
                    backgroundColor: ['#7c9414', '#F2C401'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.formattedValue} units`;
                            }
                        }
                    }
                }
            }
        });
    } else if(fertilizerChartCtx) {
        fertilizerChartCtx.getContext('2d').fillText("No fertilizer data.", 10, 50);
        console.warn('Fertilizer Chart: No data to display.');
    } else {
        console.warn('Fertilizer Chart: Canvas element not found.');
    }

    const hayRemaining = {{ (isset($totalHay) ? (float)$totalHay : 0) - (isset($usedHay) ? (float)$usedHay : 0) }};
    const hayConsumed = {{ isset($usedHay) ? (float)$usedHay : 0 }};
    const hayChartCtx = document.getElementById('hayChart');
    if (hayChartCtx && (hayRemaining >= 0 || hayConsumed > 0)) {
        new Chart(hayChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Remaining Hay', 'Used Hay'],
                datasets: [{
                    data: [Math.max(0, hayRemaining), hayConsumed],
                    // UPDATED: Set Remaining Hay (index 0) to #7c9414
                    backgroundColor: ['#7c9414', '#F2C401'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.formattedValue} units`;
                            }
                        }
                    }
                }
            }
        });
    } else if(hayChartCtx) {
        hayChartCtx.getContext('2d').fillText("No hay data.", 10, 50);
        console.warn('Hay Chart: No data to display.');
    } else {
        console.warn('Hay Chart: Canvas element not found.');
    }

    const seedRemaining = {{ (isset($seedStock) ? (float)$seedStock : 0) }};
    const seedConsumed = {{ isset($seedUsed) ? (float)$seedUsed : 0 }};
    const seedChartCtx = document.getElementById('seedChart');
    if (seedChartCtx && (seedRemaining >= 0 || seedConsumed > 0)) {
        new Chart(seedChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Remaining Seed', 'Used Seed'],
                datasets: [{
                    data: [Math.max(0, seedRemaining), seedConsumed],
                    // UPDATED: Set Remaining Seed (index 0) to #7c9414
                    backgroundColor: ['#7c9414', '#F2C401'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.formattedValue} units`;
                            }
                        }
                    }
                }
            }
        });
    } else if(seedChartCtx) {
        seedChartCtx.getContext('2d').fillText("No seed data.", 10, 50);
        console.warn('Seed Chart: No data to display.');
    } else {
        console.warn('Seed Chart: Canvas element not found.');
    }
});
function filterTasksByDay(day) {
    // Get current URL and add/update day_filter parameter
    const url = new URL(window.location.href);
    url.searchParams.set('day_filter', day);
    
    // Update the tasks title
    document.getElementById('tasks-title').textContent = day + ' Tasks';
    
    // Reload page with filter
    window.location.href = url.toString();
}

// If there's a day_filter in URL, update the title
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const dayFilter = urlParams.get('day_filter');
    
    if (dayFilter) {
        document.getElementById('tasks-title').textContent = dayFilter + ' Tasks';
        
        // Add a "Show All" button
        const titleElement = document.querySelector('.current-tasks h3');
        if (!document.getElementById('show-all-btn')) {
            const showAllBtn = document.createElement('button');
            showAllBtn.id = 'show-all-btn';
            showAllBtn.className = 'btn btn-sm btn-secondary ml-2';
            showAllBtn.innerHTML = '<i class="fas fa-list"></i> Show All';
            showAllBtn.style.cssText = 'margin-left: 10px; padding: 4px 12px; font-size: 12px;';
            showAllBtn.onclick = function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('day_filter');
                window.location.href = url.toString();
            };
            titleElement.appendChild(showAllBtn);
        }
    }
});
</script>
@endsection

@section('scripts')
@endsection