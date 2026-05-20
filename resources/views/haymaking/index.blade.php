@extends('layouts.app')

@section('content')
<style>
    body {
        background-color: #f0f2f5;
        overflow-x: auto;
    }
    .table td {
        white-space: nowrap;
    }
    .container-block {
        background: white;
        padding: 30px;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    .top-sticky-bar {
        position: sticky;
        top: 0;
        z-index: 1020;
        background-color: #fff;
        padding: 15px 30px;
        margin: -30px -30px 20px -30px;
        border-radius: 18px 18px 0 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .form-inline-custom {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .form-inline-custom .left-group,
    .form-inline-custom .right-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    @media (max-width: 768px) {
        .form-inline-custom {
            flex-direction: column;
            align-items: flex-start;
        }
        .form-inline-custom .left-group,
        .form-inline-custom .right-group {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    .form-select.custom-select {
        min-width: 161px;
        padding: 6px 15px;
        border-radius: 8px;
        background-color: #fff;
        color: #212529;
        border: 1px solid #ced4da;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='gray' class='bi bi-caret-down-fill' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658A.5.5 0 0 1 2.825 5h10.35a.5.5 0 0 1 .374.84l-4.796 5.48a.5.5 0 0 1-.756 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 16px 16px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }
    .search-box input {
        width: 250px;
        padding: 10px 15px;
        border-radius: 50px;
        border: 1px solid #ced4da;
    }
    .badge {
        font-size: 0.85rem;
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        margin: 2px;
    }
    .bg-success-subtle {
        background-color: #e0f7e9;
        color: #2e7d32;
        border: 1px solid #81c784;
    }
    .bg-warning-subtle {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffecb5;
    }
    .bg-info-subtle {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #b6d4ea;
    }
    .pagination {
        justify-content: center;
        margin-top: 20px;
    }
    .table-responsive {
        overflow-y: auto;
        max-height: calc(100vh - 250px);
    }
    .table thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
    }
    .bg-kharif { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
.bg-rabi { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
.bg-zaid { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; }
</style>

<div class="container-block my-3">
    <div class="top-sticky-bar">
        <form method="GET" class="form-inline-custom">
            <div class="d-flex flex-wrap align-items-end w-100 gap-3">
                <div class="form-group-compact">
                   <label class="filter-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                   
                        <select name="site_id" id="site_id" class="form-select custom-select" onchange="this.form.submit()">
                             @if ($role == 1)
                            <option value="">All Sites</option>
                            @foreach ($sites as $id => $name)
                        <option value="{{ $id }}" {{ $selectedSiteId == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        
                    @else
                    <option value="" >
                                    {{ $sites[$selectedSiteId] ?? 'N/A' }}
                                </option>
                       
                    @endif
                    </select>
                </div>

                <div class="form-group-compact">
                    <label for="block_name" class="filter-label"><i class="fas fa-th-large"></i> Block</label>
                    <select name="block_name" id="block_name" class="form-select custom-select" onchange="this.form.submit()">
                        <option value="">Select Block</option>
                        @foreach ($blocks as $block)
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

                <div class="ms-auto mb-2">
                    <label for="liveSearch" class="form-label visually-hidden">Search</label>
                    <div class="input-group search-box shadow-sm rounded-pill" style="width: 284px;">
                        <input
                            type="text"
                            id="liveSearch"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control border-0 rounded-pill"
                            placeholder="Search..."
                        >
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="haymakingTable">
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
                        <th>Date</th>
                        <th>Seed Name</th>
                        <th>Total Green Fodder Yield (MT)</th>
                        <th>Fodder Used for Hay Making (MT)</th>
                        <th>Processed Output Yield (MT)(80%)</th>
                        <th>Unprocessed Fodder Remaining (MT)</th>
                        <th>Machine Used</th>
                        <th>Tractor Used</th>
                        <th>HSD Consumption (Ltr)</th>
                        <th>Time (Hrs)</th>
                        <th>Manpower Type</th>
                        <th>Manpower Categories</th>
                         
                        <th>Major Maintenance (Rs)</th>
                        <th>Total Cost (Rs)</th>
                        <th>Supervisor Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($haymakings as $i => $row)
                    
    
                    <tr>
                        <td>{{ $haymakings->firstItem() + $i }}</td>
                        <td>{{ $row->block_name ?? 'N/A' }}</td>
                        <td>{{ $row->plot_name }}</td>
                        <td>{{ $row->area }}</td>
                        <td>{{ $row->area_covered ?? 'N/A' }}</td>
                        <td>{{ trim((string)($row->season_name ?? '')) ?: (trim((string)($row->manual_season ?? '')) ?: (trim((string)($row->manual_session ?? '')) ?: '--')) }}</td>
                        <td>{{ $row->harvest_date ? \Carbon\Carbon::parse($row->harvest_date)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $row->seed_name ?? 'N/A' }}</td>
                        <td>{{ number_format($row->total_available_yield, 2) }}</td>
                        <td>{{ number_format($row->required_yield_mt, 2) }}</td>
                        <td>{{ number_format($row->adjusted_yield_mt, 2) }}</td>
                        <td>
                            {{ number_format(($row->total_available_yield ?? 0) - ($row->required_yield_mt ?? 0), 2) }}
                        </td>
                        <td>
                            @if (!empty($row->machine_names_display) && $row->machine_names_display !== '-')
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    @foreach (explode(',', $row->machine_names_display) as $name)
                                        <span class="badge bg-success-subtle">
                                            <i class="fas fa-cogs me-1"></i> {{ trim($name) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if (!empty($row->tractor_names_display) && $row->tractor_names_display !== '-')
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    @foreach (explode(',', $row->tractor_names_display) as $name)
                                        <span class="badge bg-warning-subtle">
                                            <i class="fas fa-tractor me-1"></i> {{ trim($name) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                -
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
                                @foreach ($row->parsed_major_maintenance as $maintenance)
                                    {{ $maintenance['spare_part'] ?? '-' }}: {{ number_format($maintenance['value'] ?? 0, 2) }}<br>
                                @endforeach
                            @else
                                {{ $row->major_cost ? number_format($row->major_cost, 2) : '-' }}
                            @endif
                        </td>
                        <td>{{ number_format($row->total_cost ?? 0, 2) }}</td>
                        <td>{{ $row->user_name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="20" class="text-center py-4 text-muted">No silage processing records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-center">
            {{ $haymakings->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<script>
let debounceTimer;
document.getElementById('liveSearch').addEventListener('keyup', function () {
    clearTimeout(debounceTimer);
    const query = this.value;
    debounceTimer = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', query);

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('#haymakingTable');
                document.querySelector('#haymakingTable').innerHTML = newTable.innerHTML;
            });
    }, 300);
});
</script>
@endsection
