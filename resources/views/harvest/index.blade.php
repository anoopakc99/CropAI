@extends('layouts.app')

@section('content')

<style>
    /* [All your previous styles remain unchanged] */
    .form-select, .form-control { border-radius: 8px; }
    .btn-outline-secondary { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: center; vertical-align: middle; white-space: nowrap; }
    th { background-color: #f9f9f9; font-weight: bold; }
    .manpower-label { display: block; font-size: 12px; font-weight: normal; color: #555; }
    .bg-kharif { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
    .bg-rabi { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
    .bg-zaid { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; }
    .total-row { background-color: #e8f4f8 !important; font-weight: bold; border-top: 2px solid #007bff !important; }
    .total-row td { background-color: #e8f4f8 !important; font-weight: bold; color: #0056b3; }

    /* Modern filter styles remain unchanged */
    .filter-container { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .filter-section { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
    .filter-label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 0; }
    .filter-label i { font-size: 16px; color: #6c757d; }
    .modern-select, .modern-input { border: 2px solid #e9ecef; border-radius: 8px; padding: 8px 12px; font-size: 14px; transition: all 0.3s ease; background: white; }
    .modern-select:focus, .modern-input:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); outline: none; }
    .search-container { display: flex; align-items: flex-end; gap: 10px; margin-left: auto; }
    .search-group { display: flex; flex-direction: column; gap: 5px; }
    .search-input-group { display: flex; border: 2px solid #e9ecef; border-radius: 8px; overflow: hidden; background: white; transition: all 0.3s ease; }
    .search-input-group:focus-within { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
    .search-input { border: none; padding: 8px 12px; font-size: 14px; outline: none; flex: 1; min-width: 200px; }
    .search-btn { background: #007bff; color: white; border: none; padding: 8px 15px; cursor: pointer; transition: background 0.3s ease; }
    .search-btn:hover { background: #0056b3; }
    .bg-success-subtle {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid #b8e6c1;
    }
    .bg-warning-subtle {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
        border: 1px solid #f5d982;
    }

    @media (max-width: 768px) {
        .filter-section { flex-direction: column; align-items: stretch; }
        .search-container { margin-left: 0; margin-top: 10px; }
        .filter-group { min-width: auto; }
    }
</style>

<div class="card shadow-sm">
    <div class="card-body">

        <!-- Filter Form -->
        <form method="GET" action="{{ route('harvest.list') }}">
            <div class="filter-container">
                <div class="filter-section">

                    <!-- Site -->
                    
                         @if ($role == 1)
                    <div class="form-group-compact">
                        <label for="site_id" class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Location
                        </label>
                        <select name="site_id" id="site_id" class="form-select custom-select-enhanced" onchange="this.form.submit()">
                            <option value="">All Sites</option>
                            @foreach ($sites as $id => $name)
                                <option value="{{ $id }}" {{ ($selectedSiteId == $id) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="form-group-compact">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Location
                        </label>
                        <input type="text" class="form-control custom-select-enhanced" value="{{ $sites[$selectedSiteId] ?? 'N/A' }}" readonly>
                    </div>
                @endif
                   

                    <!-- Block -->
                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-th-large"></i> BLOCK</label>
                        <select name="block_name" onchange="this.form.submit()" class="modern-select">
                            <option value="">All Blocks</option>
                            @foreach($blocks as $block)
                                <option value="{{ $block->block_id }}" {{ request('block_name') == $block->block_id ? 'selected' : '' }}>
                                    {{ $block->block_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact">
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
                <div class="form-group-compact">
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

                    <!-- Search -->
                    <div class="search-container">
                        <div class="search-group">
                            <div class="search-input-group">
                                <input type="text" name="search" class="search-input" placeholder="Search records..." value="{{ request('search') }}">
                                <button class="search-btn" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Block Name</th>
                        <th>Plot No.</th>
                        <th>Area (Acre)</th>
                        <th>Area Covered (Acre)</th>
                        <th>Manual Season</th>
                        <th>Seed Name</th>
                        <th>Yield (MT)</th>
                        <th>Harvest Date</th>
                        <th>Method of Harvest</th>
                        <th>Purpose</th>
                        <th>Machine Used</th>
                        <th>Tractor Used</th>
                        <th>HSD Consumption (Ltr)</th>
                        <th>Time (Hrs)</th>
                        <th>Manpower Type</th>
                        <th>Manpower Categories</th>
                        <th>Major Maintenance (Rs)</th>
                        <th>Crop Residue</th>
                        <th>Total Cost (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($harvests as $key => $row)
                    <tr>
                        <td>{{ $harvests->firstItem() + $key }}</td>
                        <td>{{ $row->block_name ?? 'N/A' }}</td>
                        <td>{{ $row->plot_name }}</td>
                        <td>{{ $row->area }}</td>
                         <td>{{ $row->area_covered }}</td>
                         <td>{{ trim((string)($row->season_name ?? '')) ?: (trim((string)($row->manual_season ?? '')) ?: (trim((string)($row->manual_session ?? '')) ?: '--')) }}</td>
                        <td>{{ $row->seed_name }}</td>
                        <td>{{ $row->yield_mt }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-y') }}</td>
                        <td>{{ $row->harvest_method }}</td>
                        <td>{{ $row->harvest_purpose }}</td>
                        <td>
                            @if ($row->machine_names && $row->machine_names != '-')
                                @foreach(explode(',', $row->machine_names) as $machine)
                                 <span class="badge bg-success-subtle">
                                        <i class="fas fa-cogs"></i> {{ trim($machine) }}
                                </span><br>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($row->tractor_names)
                            
                        <span class="badge bg-warning-subtle">
                                        <i class="fas fa-cogs"></i> {{ $row->tractor_names }}
                                </span>
                                @else
                                -
                                @endif</td>
                        <td>{{ $row->hsd_consumption }}</td>
                        <td>{{ $row->hours_used }}</td>
                        <td>{{ $row->manpower_type ?? '-' }}</td>
                       <td>
                    @php
                        $manpowerRows = [];
                
                        // Case 1: category_id is JSON string
                        if (is_string($row->category_id)) {
                            $decoded = json_decode($row->category_id, true);
                            if (is_array($decoded)) {
                                $manpowerRows = $decoded;
                            }
                        }
                        // Case 2: category_id is array
                        elseif (is_array($row->category_id)) {
                            $manpowerRows = $row->category_id;
                        }
                        // Case 3: category_id is single INT
                        elseif (is_numeric($row->category_id)) {
                            $manpowerRows[] = [
                                'category_id'   => $row->category_id,
                                'no_of_person'  => $row->no_of_person ?? 0
                            ];
                        }
                    @endphp
                    @forelse($manpowerRows as $row1)
                        @php
                            $categoryName = DB::table('master_manpower')
                                            ->where('id', $row1['category_id'])
                                            ->value('category');
                        @endphp
                            <span>
                                {{ $categoryName ?? 'N/A' }} - {{ $row1['no_of_person'] }}
                            </span><br>
                    @empty
                        <span class="text-muted">N/A</span>
                    @endforelse
                    </td>
                     
                        <td>
                            @php $maintenance = json_decode($row->major_maintenance, true); @endphp
                            @if(is_array($maintenance))
                                @foreach($maintenance as $item)
                                    {{ $item['spare_part'] ?? '-' }} - ₹{{ $item['value'] ?? '0' }}<br>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php $production = json_decode($row->other_production, true); @endphp
                            @if(is_array($production))
                                @foreach($production as $item)
                                    {{ $item['product_name'] ?? '-' }} - {{ $item['quantity'] ?? '0' }} MT<br>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $row->total_cost ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="19" class="text-center">No Records Found</td>
                    </tr>
                    @endforelse

                    <!-- Total Yield Row -->
                    @if($harvests->total() > 0)
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right;">TOTAL:</td>
                        <td>{{ number_format($totalYieldAll, 2) }} MT</td>
                        <td colspan="13"></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {!! $harvests->links('pagination::bootstrap-5') !!}
        </div>

    </div>
</div>

@endsection
