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
        text-transform: capitalize;
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

    /* New: Style for individual maintenance items */
    .maintenance-item {
        display: block; /* Make each item appear on a new line */
        margin-bottom: 4px; /* Space between items */
        font-size: 0.88rem; /* Slightly larger text */
        color: #4a5a6a; /* A bit darker for readability */
        white-space: normal; /* Allow text to wrap if needed */
    }

    .maintenance-item strong {
        color: #34495e; /* Stronger color for the spare part name */
    }

    .maintenance-item .price {
        font-weight: 600; /* Bold the price */
        color: #27ae60; /* Green for cost */
        margin-left: 5px; /* Space from the item name */
    }

    /* Enhanced Total Cost Display */
    .total-cost-display {
        font-size: 1.05rem; /* Slightly larger font for total cost */
        font-weight: 700; /* Make it bold */
       
        display: flex;
        align-items: center;
        justify-content: center; /* Center if the column allows */
        white-space: nowrap;
    }

    .total-cost-display .fas {
        margin-right: 6px;
        color: #c0392b; /* Icon color */
        font-size: 0.9em;
    }

    /* Date styling for Days After Sowing */
    .date-display {
        font-weight: 600;
        color: #2c3e50;
        background-color: #f8f9fa;
        padding: 4px 8px;
        border-radius: 6px;
        border-left: 3px solid #3498db;
        font-size: 0.9rem;
        white-space: nowrap;
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

{{-- Rest of your table and pagination code remains unchanged --}}
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
                <th>Type of Irrigation</th>
                <th>Irrigation No.</th>
                <th>Irrigation Date</th>
                <th>Source of Water</th>
                <th>Capacity (HP)</th>
                <th>Days After Sowing</th>
                <th>Electricity Consumption (Units)</th>
                <th>Time (Hrs)</th>
                <th>Manpower Type</th>
                <th>Manpower Categories</th>
                
                <th>Major Maintenance (Rs)</th>
                <th>Total Cost (Rs)</th>
                <th>Supervisor Name</th>
            </tr>
        </thead>
        <tbody>
            @forelse($irrigationData as $index => $data)
            @php
            // Season detection logic
            $dateField = $data->date ?? $data->created_at ?? now();
            $date = \Carbon\Carbon::parse($dateField);
            $month = $date->month;
            
             
        @endphp
                <tr class="{{ $index % 2 == 0 ? 'bg-light-green' : '' }}">
                    <td>{{ ($irrigationData->currentPage() - 1) * $irrigationData->perPage() + $loop->iteration }}</td>
                    <td>{{ $data->block_name ?? 'N/A' }}</td>
                    <td>{{ $data->plot_name }}</td>
                    <td>{{ $data->area}}</td>
                    <td>{{ round($data->area_covered) }}</td>
                    <td>{{ trim((string)($data->season_name ?? '')) ?: (trim((string)($data->manual_season ?? '')) ?: (trim((string)($data->manual_session ?? '')) ?: '--')) }}</td>
                    <td>
                        @php
                            $seedName = DB::table('seed')->where('id', $data->crop_id)->value('name');
                        @endphp
                        {{ $seedName ?? '--' }}
                    </td>
                    <td>{{ $data->irrigation_type }}</td>
                    <td>{{ $data->irrigation_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($data->date)->format('d-m-Y') }}</td>
                    <td>{{ $data->water_source }}</td>
                    <td>{{ $data->capacity }}</td>
                    <td>
                        @if($data->day_ofter_swowing)
                            <div class="date-displayy">
                                {{ \Carbon\Carbon::parse($data->day_ofter_swowing)->format('d-m-Y') }}
                            </div>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>{{ $data->electricity_units }} Units (₹{{ number_format($data->electricity_cost ?? 0, 2) }})</td>
                    <td>{{ $data->consumption_time }}</td>
                    <td>{{ $data->manpower_type }}</td>
                    <td>
                    @php
                        $manpowerRows = [];
                
                        // Case 1: category_id is JSON string
                        if (is_string($data->manpower_category_id)) {
                            $decoded = json_decode($data->manpower_category_id, true);
                            if (is_array($decoded)) {
                                $manpowerRows = $decoded;
                            }
                        }
                        // Case 2: category_id is array
                        elseif (is_array($data->manpower_category_id)) {
                            $manpowerRows = $data->manpower_category_id;
                        }
                        // Case 3: category_id is single INT
                        elseif (is_numeric($data->manpower_category_id)) {
                            $manpowerRows[] = [
                                'category_id'   => $data->manpower_category_id,
                                'no_of_person'  => $data->no_of_person ?? 0
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
                        {{-- major_maintenance is already an array (or empty array) from the controller --}}
                        @if(!empty($data->major_maintenance))
                            @foreach($data->major_maintenance as $item)
                                <div class="maintenance-item">
                                    <strong>{{ $item['spare_part'] ?? 'N/A' }}</strong>
                                    <span class="price">₹{{ number_format($item['value'] ?? 0, 2) }}</span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                       {{ number_format($data->total_cost ?? 0, 2) }}
                    </td>
                    <td>{{ $data->user_name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="text-center text-muted">No irrigation records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-center">
    {{ $irrigationData->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 on the block selection dropdown
        $('#blockSelect').select2({
            width: '200px', // Adjust width as needed
            theme: 'classic' // Use classic theme for consistency
        });

        // The form is set to submit on change, so no need for explicit JS submit handler here.
        // If you want live search without full page reload, that would require AJAX.
    });
</script>

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
@endpush
@endsection
