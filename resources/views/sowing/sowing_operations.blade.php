@extends('layouts.app')

@section('content')

 <style>
    /* General UI Enhancements */
    body {
        background-color: #f0f2f5;
    }

    /* Enhanced Top Sticky Bar - Left-Right Layout */
    .top-sticky-bar {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 15px 25px;
        border-bottom: none;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 0;
        border-radius: 12px 12px 0 0;
        position: sticky;
        top: 10px;
        z-index: 100;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-bottom: none;
    }

    /* Left-Right Form Layout */
    .filter-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 20px;
    }

    /* Left Side Controls */
    .left-controls {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 0 0 auto;
    }

    /* Right Side Search */
    .right-controls {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
    }

    /* Form Groups - Compact */
    .form-group-compact {
        display: flex;
        flex-direction: column;
        min-width: 160px;
    }

    .form-group-compact label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 6px;
        /*display: flex;*/
        align-items: center;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-group-compact label i {
        margin-right: 6px;
        color: #3498db;
        font-size: 0.9rem;
    }

    /* Enhanced Input Styling */
    .custom-select-enhanced {
        height: 42px;
        padding: 0.6rem 1rem;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 0.9rem;
        color: #2c3e50;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        min-width: 160px;
    }

    .custom-select-enhanced:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1);
        outline: 0;
        transform: translateY(-1px);
    }

    .custom-select-enhanced:hover {
        border-color: #74b9ff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Enhanced Search Box */
    .search-box-enhanced {
        position: relative;
        min-width: 280px;
    }

    .search-box-enhanced input {
        width: 100%;
        height: 42px;
        padding: 0.6rem 3.5rem 0.6rem 2.5rem;
        border: 2px solid #e1e8ed;
        border-radius: 25px;
        font-size: 0.9rem;
        color: #2c3e50;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
    }

    .search-box-enhanced input:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1);
        outline: 0;
        transform: translateY(-1px);
    }

    .search-box-enhanced input:hover {
        border-color: #74b9ff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .search-box-enhanced .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #74b9ff;
        font-size: 1rem;
        z-index: 2;
    }

    .search-box-enhanced .btn {
        position: absolute;
        right: 6px;
        top: 6px;
        height: 30px;
        width: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #3498db, #74b9ff);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3);
    }

    .search-box-enhanced .btn:hover {
        background: linear-gradient(135deg, #2980b9, #3498db);
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
    }

    /* Select2 Enhanced Styling */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 2px solid #e1e8ed !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: #ffffff !important;
    }

    .select2-container .select2-selection--single:hover {
        border-color: #74b9ff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }

    .select2-container--open .select2-selection--single,
    .select2-container .select2-selection--single:focus {
        border-color: #3498db !important;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        transform: translateY(-1px) !important;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 15px !important;
        color: #2c3e50 !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        width: 35px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border: 2px solid #3498db !important;
        border-radius: 8px !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    /* Table Styling Improvements */
    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        background-color: #ffffff;
        margin-top: 0;
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-top: none;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }

    .table thead th {
        background: #6b8e23 !important;
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
        font-weight: 600;
        /*padding: 16px 20px;*/
        border-bottom: 3px solid #5a7a1e;
        text-align: left;
        white-space: nowrap;
        font-size: 0.9rem;
        /*text-transform: capitalize;*/
        letter-spacing: 0.5px;
    }

    .table tbody tr:nth-of-type(even) {
        background-color: #fafbf9;
    }

    .table tbody tr:hover {
        background-color: #f4f7ed;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .table td {
        padding: 14px 20px;
        vertical-align: middle;
        border-top: 1px solid #f0f3eb;
        font-size: 0.9rem;
        color: #2c3e50;
        font-weight: 500;
    }

    /* Badge Enhancements */
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        line-height: 1.2;
        white-space: nowrap;
        margin: 2px;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .badge .fas {
        margin-right: 4px;
        font-size: 0.7em;
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

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .filter-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        
        .left-controls {
            justify-content: space-between;
            width: 100%;
        }
        
        .right-controls {
            width: 100%;
            justify-content: center;
        }
        
        .search-box-enhanced {
            min-width: 100%;
        }
        
        .form-group-compact {
            min-width: 48%;
        }
    }

    @media (max-width: 576px) {
        .left-controls {
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group-compact {
            min-width: 100%;
        }
        
        .top-sticky-bar {
            padding: 15px;
            border-radius: 8px;
        }
    }

    /* Pagination Styling */
    .pagination {
        justify-content: center;
        margin-top: 30px;
    }

    .pagination .page-item .page-link {
        border-radius: 8px;
        margin: 0 4px;
        color: #3498db;
        border: 2px solid #e1e8ed;
        transition: all 0.3s ease;
        font-weight: 500;
        padding: 8px 12px;
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #3498db, #74b9ff);
        border-color: #3498db;
        color: white;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    .pagination .page-item .page-link:hover {
        background-color: #ebf3fd;
        border-color: #74b9ff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .bg-kharif { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
.bg-rabi { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
.bg-zaid { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; }
</style>

<div class="top-sticky-bar">
    <form method="GET" class="w-100">
        <div class="filter-controls">
            <!-- Left Side Controls -->
            <div class="left-controls">
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

                <div class="form-group-compact">
                    <label for="blockSelect" class="form-label">
                        <i class="fas fa-th-large"></i>
                        Block
                    </label>
                    <select id="blockSelect" name="block_name" class="form-select custom-select-enhanced" onchange="this.form.submit()">
                        <option value="">All Blocks</option>
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
            </div>

            <!-- Right Side Search -->
            <div class="right-controls">
                <div class="search-box-enhanced">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search records..."
                        value="{{ request('search') }}"
                    >
                    <button class="btn" type="submit">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="table-responsive">
        <table class="table table-bordered">
            <thead>
        <tr>
       <th>Sr. No.</th>
        <th>Block Name</th>
        <th>Plot No.</th> <!-- Added period -->
        <th>Area (Acre)</th>
        <th>Area Covered (Acre)</th>
        <th>Manual Season</th>
        <th>Name of Seed</th>
        <th>Variety of Seed</th>
        <th>Sowing Method</th>
        <th>Sowing Date</th>
        <th>Purpose</th>
        <th>Seed Consumed (Kg)</th> <!-- Suggested unit -->
        <th>Seed Rate (per Acre)</th>
        <th>Sowing Source</th>
        <th>Row-to-Row Distance (cm)</th> <!-- Lowercased for consistency -->
        <th>Sowing Depth (cm)</th>
       
        <th>Machine Used</th>
        <th>Tractor Used</th>
        <th>HSD Consumption (Ltr)</th> <!-- Added unit -->
        <th>Time (Hrs)</th> <!-- Standardized unit -->
        <th>Manpower Type</th> <!-- Better than "Man Power" -->
        <th>Manpower Categories</th>
        <th>Major Maintenance (Rs)</th> <!-- Added unit -->
        <th>Total Cost (Rs)</th>
        <th>Supervisor Name</th>
        </tr>
    </thead>
<tbody>
    @foreach($operations as $index => $op)
     
        @php
            $machineIds = $op->machine_id ? explode(',', $op->machine_id) : [];
            $tractorIds = $op->tractor_id ? explode(',', $op->tractor_id) : [];
            $maxRows = max(count($machineIds), count($tractorIds));
        @endphp

        @for ($i = 0; $i < $maxRows; $i++)
            <tr>
                @if($i == 0)
                    <td rowspan="{{ $maxRows }}">{{ $index + $operations->firstItem() }}</td>
                   
                    <td rowspan="{{ $maxRows }}">{{ $op->block_name ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->plot_name ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->area ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->area_covered ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ trim((string)($op->season_name ?? '')) ?: (trim((string)($op->manual_season ?? '')) ?: (trim((string)($op->manual_session ?? '')) ?: '--')) }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->seed_name ?? 'Seed ID: ' . ($op->seed_id ?? '--') }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->variety ?? $op->variety_of_seed ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->sowing_method ?? 'Method ID: ' . ($op->sowing_method_id ?? '--') }}</td>
                    <td rowspan="{{ $maxRows }}" style="white-space: nowrap;">{{ \Carbon\Carbon::parse($op->date)->format('d-m-Y') }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->purpose_name ?? $op->purpose ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->seed_consumption ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">
                        {{ ($op->seed_consumption && $op->area_covered) ? number_format($op->seed_consumption / $op->area_covered, 2) : '--' }}
                    </td>
                    <td rowspan="{{ $maxRows }}">{{ $op->sowing_source ?? ' ' . ($op->swowing_source_id ?? '--') }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->row_distance ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->sowing_depth ?? '--' }}</td>
                    <!--<td rowspan="{{ $maxRows }}">{{ number_format($op->total_cost ?? 0, 2) }}</td>-->
                @endif

                <td>
                    @php
                        $mid = trim($machineIds[$i] ?? '');
                        
                    @endphp
                    
                <span class="badge bg-success-subtle">
                    <i class="fas fa-cogs"></i> {{ $mid && isset($machines[$mid]) ? $machines[$mid] : '--' }}
                </span>
                </td>

                <td>
                @php
                    $tid = trim($tractorIds[$i] ?? '');
                @endphp
                
               <span class="badge bg-warning-subtle">
                <i class="fas fa-tractor"></i> {{ $tid && isset($tractors[$tid]) ? $tractors[$tid] : '--' }}
            </span>
                </td>

                @if($i == 0)
                    <td rowspan="{{ $maxRows }}">{{ $op->hsd_consumption ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->hours_used ?? '--' }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->manpower_type ?? '--' }}</td>
                @endif
               
                <td>
                    @php
                        $manpowerRows = [];
                
                        // Case 1: category_id is JSON string
                        if (is_string($op->category_id)) {
                            $decoded = json_decode($op->category_id, true);
                            if (is_array($decoded)) {
                                $manpowerRows = $decoded;
                            }
                        }
                        // Case 2: category_id is array
                        elseif (is_array($op->category_id)) {
                            $manpowerRows = $op->category_id;
                        }
                        // Case 3: category_id is single INT
                        elseif (is_numeric($op->category_id)) {
                            $manpowerRows[] = [
                                'category_id'   => $op->category_id,
                                'no_of_person'  => $op->no_of_person ?? 0
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
                
                @if($i == 0)
                    <td rowspan="{{ $maxRows }}">
                        @php
                            $majorMaintenance = json_decode($op->major_maintenance, true);
                        @endphp
                        @if(!empty($majorMaintenance))
                            @foreach($majorMaintenance as $item)
                                {{ $item['spare_part'] ?? '-' }} - ₹{{ number_format((float)($item['value'] ?? 0), 2) }}<br>
                            @endforeach
                        @else
                            <span class="text-muted">No maintenance</span>
                        @endif
                    </td>
                    <td rowspan="{{ $maxRows }}">{{ number_format($op->total_cost ?? 0, 2) }}</td>
                    <td rowspan="{{ $maxRows }}">{{ $op->user_name ?? '--' }}</td>
                @endif
            </tr>
        @endfor
    @endforeach
</tbody>

</table>


    </div>
    <div class="d-flex justify-content-center mt-3">
    {{ $operations->withQueryString()->links('pagination::bootstrap-5') }}
</div>
<script>
    $(document).ready(function() {
        // Apply Select2 to both dropdowns with enhanced styling
        $('#blockSelect, #site_id').select2({
            width: '100%',
            theme: 'bootstrap-5',
            dropdownCssClass: 'shadow-lg',
            selectionCssClass: 'shadow-none',
            minimumResultsForSearch: Infinity,
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });

        // Add smooth animations to form elements
        $('.custom-select-enhanced, .search-box-enhanced input').on('focus', function() {
            $(this).parent().addClass('focused');
        }).on('blur', function() {
            $(this).parent().removeClass('focused');
        });

        // Add loading state to search button
        $('.search-box-enhanced .btn').on('click', function() {
            const btn = $(this);
            const icon = btn.find('i');
            icon.removeClass('fa-arrow-right').addClass('fa-spinner fa-spin');
            
            setTimeout(() => {
                icon.removeClass('fa-spinner fa-spin').addClass('fa-arrow-right');
            }, 1000);
        });
    });
</script>

@endsection
