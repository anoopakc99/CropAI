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

    /* Custom list styling for table cells */
    .table-list {
        padding-left: 0; /* Remove default padding for ul */
        list-style: none; /* Remove bullet points */
        margin-bottom: 0;
    }
    .table-list li {
        margin-bottom: 3px; /* Small space between items */
    }

    /* New Badge Style for List Items */
    .machine-item-badge {
        display: inline-block;
        background-color: #e0f2f7; /* Light blue/teal */
        color: #007bff; /* Darker blue text */
        padding: 4px 8px;
        border-radius: 5px;
        margin: 2px 4px 2px 0; /* Top, Right, Bottom, Left */
        font-size: 0.8em; /* Slightly smaller text */
        font-weight: 600;
        white-space: nowrap; /* Prevent breaking */
        border: 1px solid #cce9f5;
        transition: all 0.2s ease;
    }
    .machine-item-badge:hover {
        background-color: #d1ecf1;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Flex container for wrapping items in cells */
    .table-list-items { 
        padding-left: 0;
        list-style: none;
        margin-bottom: 0;
        display: flex; 
        flex-wrap: wrap;
        gap: 5px; 
    }
    .table-list-items li {
        margin-bottom: 0; /* Remove li-specific margin if flex gap is used */
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

<div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                     <th>Date</th>
                    <th>Block Name</th>
                    <th>Plot No.</th>
                    <th>Area (Acre)</th>
                     <th>Area Covered (Acre)</th>
                     <th>Manual Season</th>
                    <th>Machine Used</th>
                    <th>Tractor Used</th>
                    <th>HSD Consumption (Ltr)</th>
                    <th>Application Method</th>
                    <th>Chemicals Applied</th>
                    <th>Application Stage/Source</th>
                    <th>Time (Hrs)</th>
                    <th>Manpower Type</th>
                    <th>Unskilled</th>
                    <th>Semi-Skilled 1</th>
                    <th>Semi-Skilled 2</th>
                    <th>Total Cost (Rs)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($query as $key => $row)
                @php
    // Season detection logic
            $dateField = $row->date ?? $row->created_at ?? now();
            $date = \Carbon\Carbon::parse($dateField);
            $month = $date->month;
            
            if ($month >= 6 && $month <= 10) {
                $season = 'kharif';
                $seasonLabel = 'Kharif';
                $seasonClass = 'bg-kharif';
            } elseif ($month >= 11 || $month <= 3) {
                $season = 'rabi';
                $seasonLabel = 'Rabi';
                $seasonClass = 'bg-rabi';
            } else {
                $season = 'zaid';
                $seasonLabel = 'Zaid';
                $seasonClass = 'bg-zaid';
            }
            
            // Frontend filter
            $sessionFilter = request('session');
            if ($sessionFilter && $sessionFilter !== $season) {
                continue;
            }
        @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td style="white-space: nowrap;">{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '--' }}</td>
                        <td>{{ $row->block_name ?? 'N/A' }}</td>
                        <td>{{ $row->plot_name }}</td>
                        <td>{{ $row->area }}</td>
                        <td>{{ $row->area_covered }}</td>
                        <td>{{ trim((string)($row->season_name ?? '')) ?: (trim((string)($row->manual_season ?? '')) ?: (trim((string)($row->manual_session ?? '')) ?: '--')) }}</td>
                        {{-- Machine --}}
                        <td>
                            @php
                                $machines = array_filter(explode(',', $row->machine_id ?? ''));
                                $machines = array_map('trim', $machines); // Trim spaces
                            @endphp
                            @if (!empty($machines))
                                <ul class="table-list-items">
                                    @foreach ($machines as $machineId)
                                        <li>
                                            <span class="machine-item-badge">
                                                {{ $allMachines[$machineId] ?? $machineId }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>

                        {{-- Tractor --}}
                        <td>
                            @php
                                $tractors = array_filter(explode(',', $row->tractor_id ?? ''));
                                $tractors = array_map('trim', $tractors); // Trim spaces
                            @endphp
                            @if (!empty($tractors))
                                <ul class="table-list-items">
                                    @foreach ($tractors as $tractorId)
                                        <li>
                                            <span class="machine-item-badge">
                                                {{ $allTractors[$tractorId] ?? $tractorId }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>

                        {{-- HSD Consumption --}}
                        <td>
                            @php
                                $hsdList = array_filter(explode(',', $row->hsd_consumption ?? ''));
                                $hsdList = array_map('trim', $hsdList); // Trim spaces
                            @endphp
                            @if (!empty($hsdList))
                                <ul class="table-list-items">
                                    @foreach ($hsdList as $hsd)
                                        <li>
                                            <span class="machine-item-badge">
                                                {{ $hsd }} Ltr
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>

                        <td>{{ $row->application_method ?? 'N/A' }}</td>
                        <td>
                            @php
                                $chemicalIds = array_filter(explode(',', $row->chemical_id ?? ''));
                                $doses = array_filter(explode(',', $row->dose ?? ''));
                                $uoms = array_filter(explode(',', $row->uom ?? ''));
                                $companies = array_filter(explode(',', $row->company ?? ''));

                                $hasChemicals = false;
                                for ($i = 0; $i < max(count($chemicalIds), count($doses), count($uoms), count($companies)); $i++) {
                                    if (isset($chemicalIds[$i]) || isset($doses[$i]) || isset($uoms[$i]) || isset($companies[$i])) {
                                        $hasChemicals = true;
                                        break;
                                    }
                                }
                            @endphp
                            @if($hasChemicals)
                                <ul class="table-list">
                                    @for ($i = 0; $i < max(count($chemicalIds), count($doses), count($uoms), count($companies)); $i++)
                                        <li>
                                            <strong>Company:</strong> {{ $companies[$i] ?? 'N/A' }} <br>
                                            <strong>Dose:</strong> {{ ($doses[$i] ?? 'N/A') . ' ' . ($uoms[$i] ?? '') }}
                                        </li>
                                    @endfor
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $row->application_stage_source ?? 'N/A' }}</td>
                        <td>
                            @if ($row->start_time && $row->end_time)
                                {{$row->hours_used }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $row->manpower_type ?? 'N/A' }}</td>
                        <td>{{ $row->unskilled ?? 0 }}</td>
                        <td>{{ $row->semi_skilled_1 ?? 0 }}</td>
                        <td>{{ $row->semi_skilled_2 ?? 0 }}</td>
                        <td>{{ $row->total_cost ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> No records found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> 
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $query->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>

<script>
    $(document).ready(function() {
        // Apply Select2 to both dropdowns with enhanced styling
      // Current line update karo:
$('#blockSelect, #site_id, #sessionSelect').select2({
            width: '100%',
            theme: 'bootstrap-5',
            dropdownCssClass: 'shadow-lg',
            selectionCssClass: 'shadow-none',
            minimumResultsForSearch: Infinity, 
            placeholder: function() {
                return $(this).attr('data-placeholder') || ''; 
            }
        });

        // Set placeholders from options if not explicitly set
        $('#blockSelect').attr('data-placeholder', $('#blockSelect option:first').text());
        $('#site_id').attr('data-placeholder', $('#site_id option:first').text());

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
            const originalIconClass = icon.attr('class'); 
            icon.removeClass().addClass('fas fa-spinner fa-spin'); 
            
            // This setTimeout is for visual demonstration.
            // In a live application, you'd reset the icon after form submission or AJAX completion.
            setTimeout(() => {
                icon.removeClass().addClass(originalIconClass); 
            }, 1000); 
        });
    });
</script>
@endsection
