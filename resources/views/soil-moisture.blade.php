@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="h3 mb-1">
                <i class="fas fa-seedling text-success me-2"></i>{{ $siteName }} - Soil Moisture Monitoring
            </h2>
            <p class="text-muted mb-0">
                <i class="fas fa-calendar-alt me-1"></i>
                Showing last {{ $selectedPeriod }} days data up to {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}
            </p>
            @if($error ?? false)
                <div class="alert alert-danger mt-2 py-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}
                </div>
            @endif
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('soil-moisture.index') }}" class="d-flex align-items-center">
                <div class="input-group me-2">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-calendar text-primary"></i>
                    </span>
                    <input type="date" name="date" class="form-control" 
                           value="{{ $selectedDate }}" max="{{ now()->format('Y-m-d') }}">
                </div>
                
                <select name="period" class="form-select me-2">
                    <option value="7" {{ $selectedPeriod == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $selectedPeriod == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $selectedPeriod == '90' ? 'selected' : '' }}>Last 90 Days</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Apply
                </button>
            </form>
            
            <!--<a href="{{ route('soil-moisture.debug') }}?date={{ $selectedDate }}&period={{ $selectedPeriod }}" -->
            <!--   class="btn btn-outline-info" target="_blank">-->
            <!--    <i class="fas fa-bug me-1"></i>Debug Data-->
            <!--</a>-->
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Healthy Plots
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $overallStats['healthy_plots'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Warning Plots
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $overallStats['warning_plots'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Critical Plots
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $overallStats['critical_plots'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Avg Moisture
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $overallStats['avg_moisture'] ?? 0 }}%
                            </div>
                            <div class="text-xs text-muted">
                                Range: {{ $overallStats['min_moisture'] ?? 0 }}% - {{ $overallStats['max_moisture'] ?? 0 }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tint fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Data Coverage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $activePlots }}/{{ $totalPlots }} Plots
                            </div>
                            <div class="text-xs text-muted">
                                {{ $totalPlots > 0 ? round(($activePlots/$totalPlots)*100, 1) : 0 }}% Active
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Summary Alert -->
    @if($activePlots == 0)
    <div class="alert alert-warning">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">No Data Found</h5>
                <p class="mb-0">No soil moisture data found for the selected period ({{ $selectedPeriod }} days). Please check:</p>
                <ul class="mb-0 mt-1">
                    <li>Data exists in the soil_moistures table for your site</li>
                    <li>Date range is correct</li>
                    <li>Sensor data has been imported</li>
                </ul>
                <a href="{{ route('soil-moisture.debug') }}?date={{ $selectedDate }}&period={{ $selectedPeriod }}" 
                   class="btn btn-sm btn-outline-info mt-2" target="_blank">
                    Check Raw Data
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Plots Grid -->
    <div class="row">
        @foreach($soilMoistureData as $plotData)
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <!-- Plot Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-0">Plot {{ $plotData['plot_name'] }}</h5>
                            <p class="text-muted small mb-0">{{ $plotData['block_name'] }} • {{ $plotData['area'] }} Acre</p>
                        </div>
                        <span class="badge bg-{{ $plotData['status'] == 'healthy' ? 'success' : ($plotData['status'] == 'warning' ? 'warning' : ($plotData['status'] == 'critical' ? 'danger' : 'secondary')) }}">
                            {{ ucfirst($plotData['status']) }}
                        </span>
                    </div>

                    @if($plotData['success'])
                        <!-- Current Moisture -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Current Moisture:</span>
                                <div class="text-end">
                                    <strong class="text-{{ $plotData['current_moisture'] < 25 ? 'danger' : ($plotData['current_moisture'] > 40 ? 'warning' : 'success') }} h5 mb-0">
                                        {{ number_format($plotData['current_moisture'], 1) }}%
                                    </strong>
                                    <i class="fas fa-arrow-{{ $plotData['trend'] == 'rising' ? 'up text-success' : ($plotData['trend'] == 'falling' ? 'down text-danger' : 'right text-muted') }} ms-1"></i>
                                </div>
                            </div>
                            
                            <!-- Period Statistics -->
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Period Average:</span>
                                    <span>{{ number_format($plotData['avg_moisture'], 1) }}%</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Range:</span>
                                    <span>{{ number_format($plotData['min_moisture'], 1) }}% - {{ number_format($plotData['max_moisture'], 1) }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Alerts -->
                        @if(!empty($plotData['alerts']))
                        <div class="mb-3">
                            @foreach($plotData['alerts'] as $alert)
                            <div class="alert alert-{{ $alert['type'] }} alert-sm py-2 mb-2">
                                <i class="fas fa-{{ $alert['type'] == 'danger' ? 'exclamation-triangle' : 'info-circle' }} me-1"></i>
                                {{ $alert['message'] }}
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Recommendations -->
                        @if(!empty($plotData['recommendations']))
                        <div class="mb-3">
                            <h6 class="small text-muted mb-2">Recommended Actions:</h6>
                            <ul class="small mb-0 ps-3">
                                @foreach($plotData['recommendations'] as $recommendation)
                                <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Last Update -->
                        <div class="small text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Updated {{ $plotData['last_update']->diffForHumans() }}
                        </div>

                    @else
                        <!-- No Data -->
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $plotData['error'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    transition: transform 0.2s;
    border-left: 4px solid;
}
.card:hover {
    transform: translateY(-2px);
}
.border-left-success { border-left-color: #1cc88a !important; }
.border-left-warning { border-left-color: #f6c23e !important; }
.border-left-danger { border-left-color: #e74a3b !important; }
.border-left-primary { border-left-color: #4e73df !important; }
.border-left-info { border-left-color: #36b9cc !important; }

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}
</style>
@endpush