@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
          
        </div>
    </div>
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

    <!-- Production Summaries Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-dark mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    Production Summary
                </h4>
                <!--<button class="btn btn-outline-primary btn-sm" onclick="toggleSummaryView()">-->
                <!--    <i class="fas fa-expand-arrows-alt me-1"></i>-->
                <!--    <span id="toggleText">Expand All</span>-->
                <!--</button>-->
            </div>
        </div>
        
        <!-- Harvest Production Summary -->
      <!-- Harvest Production Summary -->
<!-- Harvest Production Summary -->
<div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-lg h-100 summary-card">
        <div class="card-header bg-primary text-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-tractor me-2"></i>
                   GREEN FODDER PRODUCTION
                </h6>
                <!--<button class="btn btn-sm btn-outline-light" onclick="toggleTable('harvest-table')">-->
                <!--    <i class="fas fa-chevron-down" id="harvest-icon"></i>-->
                <!--</button>-->
            </div>
        </div>
        <div class="card-body p-0">
            <div class="summary-stats p-3 bg-light">
                <div class="row text-center">
                    <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-primary mb-0">{{ number_format($harvestSummary['grand_total_yield_mt'], 2) }}</h5>
                            <small class="text-muted">Total (MT)</small>
                        </div>
                    </div>
                     <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">{{ $harvestSummary['heytotal'] }}</h5>
                            <small class="text-muted">Hay (MT)</small>
                        </div>
                    </div>
                     <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">{{ $harvestSummary['silagetotal'] }}</h5>
                            <small class="text-muted">Silage (MT)</small>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-warning mb-0">{{ number_format($harvestSummary['grand_total_sold_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Sold (MT)</small>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-info mb-0">{{ number_format($harvestSummary['grand_total_remaining_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Remaining (MT)</small>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">₹{{ number_format($harvestSummary['grand_total_sale'], 2) }}</h5>
                            <small class="text-muted">Total Sale Price (Rs)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" id="harvest-table">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="bg-primary text-white sticky-top">
                            <tr>
                                <th class="border-0 text-white">Seed Name</th>
                                <th class="text-end border-0 text-white">Yield (MT)</th>
                                <th class="text-end border-0 text-white">Sold (MT)</th>
                                <th class="text-end border-0 text-white">Remaining (MT)</th>
                                <th class="text-end border-0 text-white">Total Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($harvestSummary['summary'] as $item)
                            <tr class="table-row-hover">
                                <td class="fw-medium text-dark">{{ $item->seed_name }}</td>
                                <td class="text-end text-primary">{{ number_format($item->total_yield_mt, 2) }} MT</td>
                                <td class="text-end text-warning">{{ number_format($item->total_sold_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-info">{{ number_format($item->remaining_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($item->total_sale_for_seed, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No harvest production records.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-lg h-100 summary-card">
        <div class="card-header bg-warning text-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-tractor me-2"></i>
                   GRAIN PRODUCTION
                </h6>
                <!--<button class="btn btn-sm btn-outline-light" onclick="toggleTable('harvest-table')">-->
                <!--    <i class="fas fa-chevron-down" id="harvest-icon"></i>-->
                <!--</button>-->
            </div>
        </div>
               <div class="card-body p-0">
            <div class="summary-stats p-3 bg-light">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-primary mb-0">{{ number_format($grainSummary['grand_total_yield_mt'], 2) }}</h5>
                            <small class="text-muted">Total (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-warning mb-0">{{ number_format($grainSummary['grand_total_sold_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Sold (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-info mb-0">{{ number_format($grainSummary['grand_total_remaining_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Remaining (MT)</small>
                        </div>
                    </div>
 
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">₹{{ number_format($grainSummary['grand_total_sale'], 2) }}</h5>
                            <small class="text-muted">Total Sale Price (Rs)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" id="harvest-table">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="bg-primary text-white sticky-top">
                            <tr>
                                <th class="border-0 text-white">Seed Name</th>
                                <th class="text-end border-0 text-white">Yield (MT)</th>
                                <th class="text-end border-0 text-white">Sold (MT)</th>
                                <th class="text-end border-0 text-white">Remaining (MT)</th>
                                <th class="text-end border-0 text-white">Total Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($grainSummary['summary'] as $item)
                            <tr class="table-row-hover">
                                <td class="fw-medium text-dark">{{ $item->seed_name }}</td>
                                <td class="text-end text-primary">{{ number_format($item->total_yield_mt, 2) }} MT</td>
                                <td class="text-end text-warning">{{ number_format($item->total_sold_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-info">{{ number_format($item->remaining_mt ?? 0, 2) }} MT</td>
                                <?php $totalRevenue = DB::table('harvest_sale_records')
                                ->where('seed_id', $item->seed_id)
                                ->where('product_id', 4)
                                ->sum(DB::raw('sold_mt * sale_price_per_mt')); ?>
                                <td class="text-end text-success fw-bold">₹{{ number_format($totalRevenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No harvest production records.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
        <!-- Silage Production Summary -->
     <!-- Silage Production Summary -->
<div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-lg h-100 summary-card">
        <div class="card-header bg-success text-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-leaf me-2"></i>
                    SILAGE PRODUCTION
                </h6>
                <!--<button class="btn btn-sm btn-outline-light" onclick="toggleTable('silage-table')">-->
                <!--    <i class="fas fa-chevron-down" id="silage-icon"></i>-->
                <!--</button>-->
            </div>
        </div>
        <div class="card-body p-0">
            <div class="summary-stats p-3 bg-light">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">{{ number_format($silageSummary['grand_total_yield_mt'], 2) }}</h5>
                            <small class="text-muted">Total  (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-warning mb-0">{{ number_format($silageSummary['grand_total_sold_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Sold (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-info mb-0">{{ number_format($silageSummary['grand_total_remaining_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Remaining (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">₹{{ number_format($silageSummary['grand_total_sale'], 2) }}</h5>
                            <small class="text-muted">Total Price (Rs)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" id="silage-table">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="bg-success text-white sticky-top">
                            <tr>
                                <th class="border-0 text-white">Seed Name</th>
                                <th class="text-end border-0 text-white">Yield (MT)</th>
                                <th class="text-end border-0 text-white">Sold (MT)</th>
                                <th class="text-end border-0 text-white">Remaining (MT)</th>
                                <th class="text-end border-0 text-white">Total Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($silageSummary['summary'] as $item)
                            <tr class="table-row-hover">
                                <td class="fw-medium text-dark">{{ $item->seed_name }}</td>
                                <td class="text-end text-success">{{ number_format($item->total_yield_mt, 2) }} MT</td>
                                <td class="text-end text-warning">{{ number_format($item->total_sold_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-info">{{ number_format($item->remaining_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($item->total_sale_for_seed, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No silage production records.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Hay Production Summary -->
<div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-lg h-100 summary-card">
        <div class="card-header bg-info text-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-haystack me-2"></i>
                    HAY PRODUCTION
                </h6>
                <!--<button class="btn btn-sm btn-outline-light" onclick="toggleTable('hay-table')">-->
                <!--    <i class="fas fa-chevron-down" id="hay-icon"></i>-->
                <!--</button>-->
            </div>
        </div>
        <div class="card-body p-0">
            <div class="summary-stats p-3 bg-light">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-primary mb-0">{{ number_format($haySummary['grand_total_yield_mt'], 2) }}</h5>
                            <small class="text-muted">Total (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-warning mb-0">{{ number_format($haySummary['grand_total_sold_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Sold (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-info mb-0">{{ number_format($haySummary['grand_total_remaining_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Remaining (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">₹{{ number_format($haySummary['grand_total_sale'], 2) }}</h5>
                            <small class="text-muted">Total Sale Price (Rs)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" id="hay-table">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="bg-info text-white sticky-top">
                            <tr>
                                <th class="border-0 text-white">Seed Name</th>
                                <th class="text-end border-0 text-white">Yield (MT)</th>
                                <th class="text-end border-0 text-white">Sold (MT)</th>
                                <th class="text-end border-0 text-white">Remaining (MT)</th>
                                <th class="text-end border-0 text-white">Total Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($haySummary['summary'] as $item)
                            <tr class="table-row-hover">
                                <td class="fw-medium text-dark">{{ $item->seed_name }}</td>
                                <td class="text-end text-primary">{{ number_format($item->total_yield_mt, 2) }} MT</td>
                                <td class="text-end text-warning">{{ number_format($item->total_sold_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-info">{{ number_format($item->remaining_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($item->total_sale_for_seed, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay production records.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
 @if($strawSummary['grand_total_yield_mt'] > 0)
<!-- Other Production Summary -->
<div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-lg h-100 summary-card">
        <div class="card-header bg-danger text-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-haystack me-2"></i>
                   CROP RESIDUE PRODUCTION
                </h6>
                <!--<button class="btn btn-sm btn-outline-light" onclick="toggleTable('hay-table')">-->
                <!--    <i class="fas fa-chevron-down" id="hay-icon"></i>-->
                <!--</button>-->
            </div>
        </div>
       
        <div class="card-body p-0">
            <div class="summary-stats p-3 bg-light">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-primary mb-0">{{ number_format($strawSummary['grand_total_yield_mt'], 2) }}</h5>
                            <small class="text-muted">Total (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-warning mb-0">{{ number_format($strawSummary['grand_total_sold_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Sold (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-info mb-0">{{ number_format($strawSummary['grand_total_remaining_mt'] ?? 0, 2) }}</h5>
                            <small class="text-muted">Remaining (MT)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <h5 class="text-success mb-0">₹{{ number_format($strawSummary['grand_total_sale'], 2) }}</h5>
                            <small class="text-muted">Total Sale Price (Rs)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" id="hay-table">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="bg-info text-white sticky-top">
                            <tr>
                                <th class="border-0 text-white">Product Name</th>
                                <th class="text-end border-0 text-white">Yield (MT)</th>
                                <th class="text-end border-0 text-white">Sold (MT)</th>
                                <th class="text-end border-0 text-white">Remaining (MT)</th>
                                <th class="text-end border-0 text-white">Total Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($strawSummary['summary'] as $item)
                            <tr class="table-row-hover">
                                <td class="fw-medium text-dark">{{ $item->product_name }}</td>
                                <td class="text-end text-primary">{{ number_format($item->total_yield_mt, 2) }} MT</td>
                                <td class="text-end text-warning">{{ number_format($item->total_sold_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-info">{{ number_format($item->remaining_mt ?? 0, 2) }} MT</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($item->total_sale_for_seed, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay production records.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-filter me-2 text-primary"></i>
                        Filter Harvest Records
                    </h6>
                </div>
                <div class="card-body bg-light">
                    <form method="GET" action="{{ route('harvest.store.manage') }}" class="row g-3">
                        
                        <div class="col-md-2">
                            <label for="site_id" class="form-label fw-bold text-dark">Site</label>
                            <select name="site_id" id="site_id" class="form-select shadow-sm" onchange="this.form.submit()">
                                <option value="">All Sites</option>
                                @foreach($sites as $id => $name)
                                    <option value="{{ $id }}" {{ request('site_id', $selectedSiteId) == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                         
                        <!--<div class="col-md-3">-->
                        <!--    <label for="block_name" class="form-label fw-bold text-dark">Block</label>-->
                        <!--    <select name="block_name" id="block_name" class="form-select shadow-sm">-->
                        <!--        <option value="">All Blocks</option>-->
                        <!--        @foreach($blocks as $block)-->
                        <!--            <option value="{{ $block->block_id }}" {{ request('block_name') == $block->block_id ? 'selected' : '' }}>-->
                        <!--                {{ $block->block_name }}-->
                        <!--            </option>-->
                        <!--        @endforeach-->
                        <!--    </select>-->
                        <!--</div>-->
                        <div class="col-md-2">
                            <label for="record_type" class="form-label fw-bold text-dark">Record Type</label>
                            <select name="record_type" id="record_type" class="form-select shadow-sm" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="Green Fodder" {{ request('record_type') == 'Green Fodder' ? 'selected' : '' }}>Green Fodder</option>
                                <option value="Hay" {{ request('record_type') == 'Hay' ? 'selected' : '' }}>Hay</option>
                                <option value="Silage" {{ request('record_type') == 'Silage' ? 'selected' : '' }}>Silage</option>
                                <option value="Grain" {{ request('record_type') == 'Grain' ? 'selected' : '' }}>Grain</option>
                                <option value="Crop Residue" {{ request('record_type') == 'Crop Residue' ? 'selected' : '' }}>Crop Residue</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Financial Year
                    </label>
                    <select name="year" class="form-select custom-select-enhanced" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        @foreach($financialYears as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-seedling"></i> Season
                    </label>
                    <select name="season_name" class="form-select custom-select-enhanced" onchange="this.form.submit()">
                        <option value="">All Seasons</option>
                        <option value="Rabi" {{ request('season_name') == "Rabi" ? 'selected' : '' }}>Rabi</option>
                        <option value="Zaid" {{ request('season_name') == "Zaid" ? 'selected' : '' }}>Zaid</option>
                        <option value="Kharif" {{ request('season_name') == "Kharif" ? 'selected' : '' }}>Kharif</option>
                         
                    </select>
                </div>
                        <div class="col-md-2">
                            <label for="search" class="form-label fw-bold text-dark" onchange="this.form.submit()">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control shadow-sm" placeholder="Search by plot or seed name">
                        </div>
                        <!--<div class="col-md-2">-->
                        <!--    <button type="submit" class="btn btn-success px-4 py-2 shadow-sm btn-modern" style="margin-top: 30px;">-->
                        <!--        <i class="fas fa-search me-2"></i>-->
                        <!--        Apply Filters-->
                        <!--    </button>-->
                        <!--</div>-->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Harvest Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
            
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle modern-table" id="harvestTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center text-white">ID</th>
                                   
                                    <th class="text-white">Seed Name</th>
                                    <th class="text-center text-white">Activity Type</th>
                                    <th class="text-center text-white">Date</th>
                                    <th class="text-end text-white">Total Yield (MT)</th>
                                    {{-- <th class="text-end text-white">Total (MT)</th> --}}
                                    <!--<th class="text-center text-white">Total Sale Price (Rs)</th>-->
                                    <th class="text-center text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($harvests as $item)
                                    <tr class="table-row-modern">
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $item->id }}</span>
                                        </td>
                                        @if($item->product_id == 5)
                                        <td class="fw-medium text-primary">{{ $item->product_name }}</td>
                                        @else
                                         <td class="fw-medium text-primary">{{ $item->seed_name }}</td>
                                        @endif
                                        <td class="text-center">
                                            @if($item->product_id == 1)
                                                <span class="badge bg-primary px-3 py-2">Green Fodder Production</span>
                                            @elseif($item->product_id == 2)
                                                <span class="badge bg-secondary px-3 py-2">Hay Production</span>
                                            @elseif($item->product_id == 3)
                                                <span class="badge bg-success px-3 py-2">Silage Production</span>
                                            @elseif($item->product_id == 4)
                                                <span class="badge bg-warning px-3 py-2">Grain Production</span>
                                            @else
                                                <span class="badge bg-danger px-3 py-2">Crop Residue Production</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-dark">{{ \Carbon\Carbon::parse($item->date)->format('d-m-Y') }}</td>
                         @if($item->sold_mt == 0.00)
                        <td class="text-end fw-bold text-dark">
                             {{ number_format(max(0, $item->total_yield_mt), 2) }}
                        </td>
                        @else
                        <td class="text-end fw-bold text-dark">
                          {{ number_format(max(0, $item->remaining_mt), 2) }}
                        </td>
                        @endif
                                        
                                       
                                       <td class="text-center">
                                           <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateModal{{ $item->id }}" title="Update Sale Price">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                        <a href="{{ route('harvest.sales.report', ['seed_id' => $item->seed_id, 'product_id' => $item->product_id]) }}"
                           class="btn btn-sm btn-info" title="View Sales Report">
                           <i class="fas fa-eye"></i>
                        </a>
                                         <!-- Update Modal -->
                                            <div class="modal fade" id="updateModal{{ $item->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <form method="POST" action="{{ route('harvest.store.record_sale', $item->seed_id) }}">
                                                        @csrf
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title text-white" id="modalLabel{{ $item->id }}">
                                                                    <i class="fas fa-edit me-2"></i>
                                                                    Update Sale Price
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold text-dark">Product Details</label>
                                                                    <p class="text-muted">
                                                                        @if($item->product_id == 5)
                                                                        {{ $item->product_name }}
                                                                        @else
                                                                        {{ $item->seed_name }}
                                                                        @endif
                                                                        @if($item->product_id == 1) (Green Fodder)
                                                                        @elseif($item->product_id == 2) (Hay)
                                                                        @elseif($item->product_id == 3) (Silage)
                                                                        @elseif($item->product_id == 4) (Grain)
                                                                        @else
                                                                        (Crop Residue)
                                                                        @endif
                                                                    </p>
                                                                <input type="hidden" name="product_id" value="{{ $item->product_id}}">
                                                                <input type="hidden" name="hsm_id" value="{{ $item->id}}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="sale_yield_mt_{{ $item->id }}" class="form-label fw-bold text-dark">Sale Yield (MT)</label>
                                                                    <input type="number" step="0.01" name="sold_quantity" id="sale_yield_mt_{{ $item->id }}" class="form-control" required min="0" max="{{ $item->total_mt }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="sale_price_per_mt_{{ $item->id }}" class="form-label fw-bold text-dark">
                                                                        Sale Price/(MT) ({{ config('app.currency_symbol', 'Rs') }})
                                                                    </label>
                                                                    <input type="number" step="0.01" name="sale_price_per_mt" id="sale_price_per_mt_{{ $item->id }}" class="form-control" value="" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="sale_date_{{ $item->id }}" class="form-label fw-bold text-dark">Sale Date</label>
                                                                    <input type="date" name="sale_date" id="sale_date_{{ $item->id }}" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="buyer_name_{{ $item->id }}" class="form-label fw-bold text-dark">Buyer Name</label>
                                                                    <input type="text" name="buyer_name" id="buyer_name_{{ $item->id }}" class="form-control" maxlength="255">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="remarks_{{ $item->id }}" class="form-label fw-bold text-dark">Remark</label>
                                                                    <textarea name="remarks" id="remarks_{{ $item->id }}" class="form-control" maxlength="1000"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="fas fa-save me-2"></i>
                                                                    Record Sale
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                    </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-search fa-3x mb-3"></i>
                                                <h5 class="text-muted">No harvest records found</h5>
                                                <p>Try adjusting your filters or search terms.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $harvests->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

  
</div>

@push('styles')
<style>
    /* Main Gradients */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .bg-gradient-success {
        background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
    }

    /* Text Color Fixes */
    .text-white-75 {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    .text-dark {
        color: #333 !important;
    }

    /* Card Improvements */
    .card {
        border-radius: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }

    /* Table Improvements */
    .table-container {
        transition: all 0.3s ease;
    }

    .table-container.collapsed {
        display: none;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table-row-hover:hover {
        background-color: rgba(102, 126, 234, 0.05) !important;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table-row-modern:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* Summary Stats */
    .summary-stats {
        border-bottom: 1px solid #eee;
    }

    .stat-item {
        padding: 10px;
        border-radius: 2px;
        background: white;
        margin: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Modern Table */
    .modern-table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .modern-table thead th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 15px 10px;
    }

    .modern-table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        padding: 12px 10px;
        border-top: 1px solid #f0f0f0;
    }

    /* Button Improvements */
    .btn-modern {
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* Form Controls */
    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        padding: 10px 15px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }

    /* Badge Improvements */
    .badge {
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 8px 12px;
        font-weight: 500;
    }

    /* Shadow Improvements */
    .shadow-lg {
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }

    .shadow-sm {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
    }

    /* Responsive Improvements */
    @media (max-width: 768px) {
        .summary-card {
            margin-bottom: 20px;
        }
        
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .card-body {
            padding: 15px;
        }
    }

    /* Animation for collapsible tables */
    .table-container {
        max-height: 400px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .table-container.collapsed {
        max-height: 0;
    }

    /* Loading Animation */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Fullscreen Mode */
    .fullscreen-mode {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 9999;
        background: white;
        overflow: auto;
    }
</style>
@endpush

@push('scripts')
<script>
// Toggle table visibility in summary cards
function toggleTable(tableId) {
    const table = document.getElementById(tableId);
    const icon = document.getElementById(tableId.replace('-table', '-icon'));
    
    if (table.classList.contains('collapsed')) {
        table.classList.remove('collapsed');
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        table.classList.add('collapsed');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

// Toggle all summary tables
function toggleSummaryView() {
    const tables = ['harvest-table', 'silage-table', 'hay-table'];
    const toggleText = document.getElementById('toggleText');
    const isExpanded = !document.getElementById('harvest-table').classList.contains('collapsed');
    
    tables.forEach(tableId => {
        const table = document.getElementById(tableId);
        const icon = document.getElementById(tableId.replace('-table', '-icon'));
        
        if (isExpanded) {
            table.classList.add('collapsed');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        } else {
            table.classList.remove('collapsed');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        }
    });
    
    toggleText.textContent = isExpanded ? 'Expand All' : 'Collapse All';
}

// Export table functionality
function exportTable() {
    alert('Export functionality can be implemented based on your requirements');
}

// Fullscreen toggle
function toggleFullscreen() {
    const table = document.getElementById('harvestTable').closest('.card');
    table.classList.toggle('fullscreen-mode');
}

// Auto-refresh functionality (optional)
function autoRefresh() {
    console.log('Auto-refresh triggered');
}

// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            this.classList.add('loading');
        });
    });
});
</script>



@endpush
@endsection