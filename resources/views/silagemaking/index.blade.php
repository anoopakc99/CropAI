@extends('layouts.app')

@section('content')
<style>
    .container-block {
        background: white;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: end;
        margin-bottom: 20px;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 14px;
        color: #333;
    }

    .filter-group .form-select,
    .filter-group .form-control {
        height: 36px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 14px;
        min-width: 150px;
    }

    .search-container {
        position: relative;
    }

    .search-container input {
        padding-right: 32px;
    }

    .search-container .fa-search {
        position: absolute;
        right: 10px;
        top: 9px;
        color: #888;
    }

    .table-responsive-wrapper {
        overflow-x: auto;
        margin-top: 20px;
    }

    .table thead {
        background-color: #4c6e16;
        color: white;
        font-weight: bold;
        text-align: center;
        white-space: nowrap;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f0f7e0;
    }

    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        padding: 10px;
        white-space: nowrap;
    }

    .pagination {
        justify-content: center;
        margin-top: 20px;
    }
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
    .bg-kharif { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
.bg-rabi { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
.bg-zaid { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; }
</style>

<div class="container-block my-3">
    <form method="GET" class="filter-group">
        {{-- Site Dropdown --}}
        <div>
            <label>Location:</label>
            @if ($role == 1)
                <select name="site_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Sites</option>
                    @foreach ($sites as $id => $name)
                        <option value="{{ $id }}" {{ $selectedSiteId == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            @else
                <input type="text" class="form-control" value="{{ $sites[$selectedSiteId] ?? 'N/A' }}" readonly>
            @endif
        </div>

        {{-- Block Dropdown --}}
        <div>
            <label>Block</label>
            <select name="block_name" class="form-select" onchange="this.form.submit()">
                <option value="">All Blocks</option>
                @foreach ($blocks as $blk)
                    <option value="{{ $blk->block_id }}" {{ request('block_id') == $blk ? 'selected' : '' }}>
                         {{ $blk->block_name }}
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
    </form>

    {{-- Data Table --}}
    <div class="table-responsive-wrapper">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Block Name</th>
                    <th>Plot No.</th>
                    <th>Area (Acre)</th>
                    <th>Area Covered (Acre)</th>
                    <th>Manual Season</th>
                    <th>Date</th>
                    <th>Seed Name</th>
                    <th>Total Green Fodder Yield (MT)</th>
                    <th>Fodder Used for Silage Making (MT)</th>
                    <th>Processed Output Yield (MT)(20%)</th>
                    <th>Unprocessed Fodder Remaining (MT)</th>
                    <th>Method of Harvest</th>
                    <th>Machine Used</th>
                    <th>Tractor Used</th>
                    <th>HSD Consumption (Ltr)</th>
                    <th>Time (Hrs)</th>
                    <th>Manpower Type</th>
                    <th>Manpower Categories</th>
                    
                    <th>Major Maintenance (Rs)</th>
                    <th>Total Cost (Rs)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($silagemakings as $i => $row)
               
                    <tr>
                        <td>{{ $silagemakings->firstItem() + $i }}</td>
                        <td>{{ $row->block_name ?? 'N/A' }}</td>
                        <td>{{ $row->plot_name ?? '-' }}</td>
                        <td>{{ $row->area ?? '-' }}</td>
                        <td>{{ $row->area_covered ?? '-' }}</td>
                        <td>{{ trim((string)($row->season_name ?? '')) ?: (trim((string)($row->manual_season ?? '')) ?: (trim((string)($row->manual_session ?? '')) ?: '--')) }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->harvest_date)->format('d-m-Y') }}</td>
                        <td>{{ $row->seed_name ?? '-' }}</td>
                        <td>{{ $row->total_available_yield ?? 0 }}</td>
                        <td>{{ $row->required_yield_mt ?? 0 }}</td>
                        <td>{{ $row->adjusted_yield_mt ?? 0 }}</td>
                        <td>{{ number_format(($row->total_available_yield ?? 0) - ($row->required_yield_mt ?? 0), 2) }}</td>
                        <td>{{ $row->silage_making_method ?? '-' }}</td>
                        <td>@if($row->machine_names_display)
                            @foreach (explode(',', $row->machine_names_display) as $machine)
                                 <span class="badge bg-warning-subtle">
                                    <i class="fas fa-tractor"></i> {{ trim($machine) }}
                                </span></br>
                                @endforeach
                        @else
                        -
                        @endif
                        <td> @if ($row->tractor_names_display)
                                @foreach (explode(',', $row->tractor_names_display) as $tractor)
                                 <span class="badge bg-warning-subtle">
                                    <i class="fas fa-tractor"></i> {{ trim($tractor) }}
                                </span></br>
                                @endforeach
                            @else
                                <span class="badge bg-secondary-subtle">-</span>
                            @endif
                                </td>
                        <td>{{ $row->hsd_consumption ?? '-' }}</td>
                        <td>{{ $row->hours_used ?? '-' }}</td>
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
                            @if (!empty($row->parsed_major_maintenance))
                                @foreach ($row->parsed_major_maintenance as $item)
                                    {{ $item['spare_part'] ?? '-' }}: ₹{{ $item['value'] ?? '0' }}<br>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $row->total_cost ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="text-center text-muted py-4">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex pagination">
        {{ $silagemakings->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
