@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="margin-top: -37px;">
    <div class="main-card shadow-lg border-0 rounded-xl overflow-hidden">
        <div class="card-header text-white py-4 position-relative" style="background: linear-gradient(135deg, #2d5016 0%, #6b8e23 50%, #8fbc34 10%); height: 74px;">
            <div class="header-overlay"></div>
            <div class="d-flex align-items-center justify-content-center position-relative">
                <i class="fas fa-chart-line me-3 fs-3"></i>
                <h3 class="mb-0">Production Analysis Dashboard</h3>
            </div>
            <div class="header-decoration"></div>
        </div>

        <div class="card-body p-0">
            <div class="filter-section p-4 bg-gradient-light">
                <div class="filter-header mb-3">
                    <h5 class="text-primary fw-bold mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Options
                    </h5>
                    <small class="text-muted">Select your criteria to generate the report</small>
                </div>

                <form method="GET" class="filter-form">
                    <div class="row g-3">
                        {{-- Site Dropdown (Visible only for Role 1 users) --}}
                        @if ($loggedInUserRole == 1)
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="form-group-enhanced">
                                    <label for="site_id" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Select Site
                                    </label>
                                    <select name="site_id" id="site_id" class="form-select form-select-enhanced">
                                        @foreach ($sites as $id => $name)
                                            <option value="{{ $id }}" {{ (isset($sel['selectedSiteId']) && $sel['selectedSiteId'] == $id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            {{-- For non-admin users, show their site name but make it non-selectable --}}
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="form-group-enhanced">
                                    <label for="site_id" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Location
                                    </label>
                                    @foreach ($sites as $id => $name)
                                        <input type="text" class="form-control form-control-enhanced" value="{{ $name }}" disabled>
                                        <input type="hidden" name="site_id" value="{{ $id }}">
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="block" class="form-label">
                                    <i class="fas fa-th-large me-1"></i>Block Select
                                </label>
                                <select name="block" id="block" class="form-select form-select-enhanced">
                                    <option value="">Select Block</option>
                                    @foreach($blocks as $block)
                                        <option value="{{ $block->block_id }}" {{ $sel['block'] == $block->block_id ? 'selected' : '' }}>{{ $block->block_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="plot" class="form-label">
                                    <i class="fas fa-map me-1"></i>Plot Select
                                </label>
                                <select name="plot" id="plot" class="form-select form-select-enhanced">
                                    <option value="">Select Plot</option>
                                    @if(isset($sel['block']) && !empty($sel['block']) && isset($plotsByBlock[$sel['block']]))
                                        @foreach($plotsByBlock[$sel['block']] as $plot)
                                            <option value="{{ $plot }}" {{ $sel['plot'] == $plot ? 'selected' : '' }}>{{ $plot }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Financial Year Dropdown --}}
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="financial_year" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Financial Year
                                </label>
                                <select name="financial_year" id="financial_year" class="form-select form-select-enhanced">
                                    <option value="">All Years</option>
                                    <option value="2023-24" {{ ($sel['financialYear'] ?? '') == '2023-24' ? 'selected' : '' }}>2023-24</option>
                                    <option value="2024-25" {{ ($sel['financialYear'] ?? '') == '2024-25' ? 'selected' : '' }}>2024-25</option>
                                    <option value="2025-26" {{ ($sel['financialYear'] ?? '') == '2025-26' ? 'selected' : '' }}>2025-26</option>
                                </select>
                            </div>
                        </div>

                        {{-- Season Dropdown --}}
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="season_filter" class="form-label">
                                    <i class="fas fa-cloud-sun me-1"></i>Season
                                </label>
                                <select name="season_filter" id="season_filter" class="form-select form-select-enhanced">
                                    <option value="">All Seasons</option>
                                    <option value="Kharif" {{ ($sel['seasonFilter'] ?? '') == 'Kharif' ? 'selected' : '' }}>Kharif</option>
                                    <option value="Rabi" {{ ($sel['seasonFilter'] ?? '') == 'Rabi' ? 'selected' : '' }}>Rabi</option>
                                    <option value="Zaid" {{ ($sel['seasonFilter'] ?? '') == 'Zaid' ? 'selected' : '' }}>Zaid</option>
                                </select>
                            </div>
                        </div>

                        {{-- Crop-Wise Filter --}}
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="crop_id" class="form-label">
                                    <i class="fas fa-leaf me-1"></i>Crop
                                </label>
                                <select name="crop_id" id="crop_id" class="form-select form-select-enhanced">
                                    <option value="">All Crops</option>
                                    @foreach($crops ?? [] as $crop)
                                        <option value="{{ $crop->id }}" {{ ($sel['crop_id'] ?? '') == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="col-lg-4 col-md-5 col-sm-6">
                            <div class="form-group-enhanced me-lg-4">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Date Range
                                </label>
                                <div class="date-range-wrapper">
                                    <input type="date" name="from" value="{{ $sel['from'] }}" class="form-control form-control-enhanced">
                                    <span class="date-separator">to</span>
                                    <input type="date" name="to" value="{{ $sel['to'] }}" class="form-control form-control-enhanced">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="form-group-enhanced">
                                <label for="activity" class="form-label">
                                    <i class="fas fa-tasks me-1"></i>Select Activity
                                </label>
                                <select name="activity" id="activity" class="form-select form-select-enhanced">
                                    <option value="">All Activities</option>
                                    @foreach($orderedActivities as $activity)
                                        <option value="{{ $activity['table'] }}" {{ old('activity', $sel['activity'] ?? '') == $activity['table'] ? 'selected' : '' }}>
                                            {{ $activity['display'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-3 col-sm-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary-enhanced w-100">
                                <i class="fas fa-search me-2"></i>Generate Report
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-6 d-flex align-items-end">
                            <button type="submit" name="download" value="Excel" class="btn btn-primary-enhanced w-100">
                                <i class="fas fa-download me-2"></i>Download Excel
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-6 d-flex align-items-end">
                            <button type="button" class="btn btn-info-enhanced w-100" data-bs-toggle="modal" data-bs-target="#previewModal">
                                <i class="fas fa-eye me-2"></i>Preview & Copy
                            </button>
                        </div>
                    </div>
                </form>
            </div>
</div>
<div class="card-body p-0">
            <div class="table-section">
                <div class="table-header p-3 bg-light border-bottom">
                    <h5 class="mb-0 text-dark fw-bold">
                        <i class="fas fa-table me-2"></i>Production Data Analysis
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-enhanced" id="activityTable">
                        <thead class="table-header-enhanced sticky-top">
                            <tr>
                                <th data-column-name="sr_no" class="table-header-cell"><i class="fas fa-hashtag me-1"></i>Sr. No.</th>
                                <th data-column-name="activity_type" class="table-header-cell"><i class="fas fa-cogs me-1"></i>Activity Type</th>
                                <th data-column-name="block_name" class="table-header-cell"><i class="fas fa-th-large me-1"></i>Block Name</th>
                                <th data-column-name="plot_name" class="table-header-cell"><i class="fas fa-map me-1"></i>Plot No</th>
                                <th data-column-name="date" class="table-header-cell"><i class="fas fa-calendar me-1"></i>Date</th>
                                <th data-column-name="area" class="table-header-cell"><i class="fas fa-expand-arrows-alt me-1"></i>Area (Acre)</th>
                                <th data-column-name="area_covered" class="table-header-cell"><i class="fas fa-layer-group me-1"></i>Area Covered (Acre)</th>
                                <th data-column-name="seed_name" class="table-header-cell"><i class="fas fa-seedling me-1"></i>Seed Name</th>
                                <th data-column-name="seed_consumption" class="table-header-cell"><i class="fas fa-weight me-1"></i>Seed Consumption</th>
                                <th data-column-name="yield_production" class="table-header-cell"><i class="fas fa-chart-bar me-1"></i>Yield (MT)</th>
                                <!--<th data-column-name="price_per_mt" class="table-header-cell"><i class=""></i>Price/MT (Rs)</th>-->
                                <!--<th data-column-name="total_sale_price" class="table-header-cell"><i class="fas fa-money-bill-wave me-1"></i>Total Sale Price (Rs)</th>-->
                                <th data-column-name="machine_tractor" class="table-header-cell"><i class="fas fa-tractor me-1"></i>Machine Used</th>
                                <th data-column-name="tractor_used" class="table-header-cell"><i class="fas fa-tractor me-1"></i>Tractor Used</th>
                                <th data-column-name="hsd_consumption" class="table-header-cell"><i class="fas fa-gas-pump me-1"></i>HSD Consumption (Ltr)</th>
                                <th data-column-name="hsd_cost" class="table-header-cell"><i class=""></i>HSD Cost (Rs)</th>
                                <th data-column-name="electricity_units" class="table-header-cell"><i class="fas fa-bolt me-1"></i>Electricity Units</th>
                                <th data-column-name="electricity_cost" class="table-header-cell"><i class=""></i>Electricity Cost (Rs)</th>
                                <th data-column-name="fertilizer_name" class="table-header-cell"><i class="fas fa-flask me-1"></i>Fertilizer Name</th>
                                <th data-column-name="fertilizer_used" class="table-header-cell"><i class="fas fa-weight me-1"></i>Fertilizer Used</th>
                                <th data-column-name="fertilizer_cost" class="table-header-cell"><i class=""></i>Fertilizer Cost (Rs)</th>
                                <th data-column-name="manpower_type" class="table-header-cell"><i class="fas fa-users me-1"></i>Manpower Type</th>
                                <th data-column-name="unskilled" class="table-header-cell"><i class="fas fa-user me-1"></i>Unskilled</th>
                                <th data-column-name="semi_skilled_1" class="table-header-cell"><i class="fas fa-user-tie me-1"></i>Semi-Skilled 1</th>
                                <th data-column-name="semi_skilled_2" class="table-header-cell"><i class="fas fa-user-graduate me-1"></i>Semi-Skilled 2</th>
                                <th data-column-name="manpower_cost" class="table-header-cell"><i class=""></i>Manpower Cost (Rs)</th>
                                <th data-column-name="maintenance_cost" class="table-header-cell"><i class="fas fa-tools me-1"></i>Maintenance Cost (Rs)</th>
                                <th data-column-name="chemical_name" class="table-header-cell"><i class="fas fa-vial me-1"></i>Chemical Name</th>
                                <th data-column-name="chemical_used" class="table-header-cell"><i class="fas fa-weight me-1"></i>Chemical Used</th>
                                <th data-column-name="total_overall_cost" class="table-header-cell"><i class="fas fa-calculator me-1"></i>Total Cost (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $displayRows = collect($summary);

                                // Harvest Store Management Totals
                                if ( (empty($sel['activity']) || $sel['activity'] == 'harvesting_update') && isset($harvestStoreManageTotals) && (count($harvestStoreManageTotals->seedTotals) > 0 || $harvestStoreManageTotals->grandTotal > 0) ) {
                                    $displayRows->push((object)['is_section_header' => true, 'text' => 'YIELD PRODUCTION SUMMARY', 'bg_class' => 'bg-harvest', 'icon' => 'fas fa-warehouse']);
                                    foreach ($harvestStoreManageTotals->seedTotals as $item) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => false,
                                            'activity_type' => 'Harvest Store Management',
                                            'seed_name_summary' => $item->seed_name,
                                            'yield_mt' => $item->total_yield_mt,
                                            'sold_mt' => $item->total_sold_mt ?? 0,
                                            'total_price' => $item->total_price ?? null,
                                            'label_text' => 'Total Yield for ' . $item->seed_name
                                        ]);
                                    }
                                    if ($harvestStoreManageTotals->grandTotal > 0) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => true,
                                            'activity_type' => 'Harvest Store Management',
                                            'seed_name_summary' => 'GRAND TOTAL',
                                            'yield_mt' => $harvestStoreManageTotals->grandTotal,
                                            'sold_mt' => $harvestStoreManageTotals->grandTotalSold ?? 0,
                                            'total_price' => $harvestStoreManageTotals->grandTotalPrice ?? null,
                                            'label_text' => 'TOTAL'
                                        ]);
                                    }
                                }

                                // Silage Making Totals
                                if ( (empty($sel['activity']) || $sel['activity'] == 'silage_making') && isset($silageTotals) && (count($silageTotals->seedTotals) > 0 || $silageTotals->grandTotal > 0) ) {
                                    $displayRows->push((object)['is_section_header' => true, 'text' => ' SILAGE PRODUCTION SUMMARY', 'bg_class' => 'bg-silage', 'icon' => 'fas fa-leaf']);
                                    foreach ($silageTotals->seedTotals as $seed) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => false,
                                            'activity_type' => 'Silage Making',
                                            'seed_name_summary' => $seed->seed_name,
                                            'yield_mt' => $seed->total_yield_mt,
                                            'sold_mt' => $seed->total_sold_mt ?? 0,
                                            'total_price' => $seed->total_price ?? null,
                                            'label_text' => 'Total Yield for ' . $seed->seed_name
                                        ]);
                                    }
                                    if ($silageTotals->grandTotal > 0) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => true,
                                            'activity_type' => 'Silage Making',
                                            'seed_name_summary' => 'GRAND TOTAL',
                                            'yield_mt' => $silageTotals->grandTotal,
                                            'sold_mt' => $silageTotals->grandTotalSold ?? 0,
                                            'total_price' => $silageTotals->grandTotalPrice ?? null,
                                            'label_text' => 'TOTAL'
                                        ]);
                                    }
                                }

                                // Hay Making Totals
                                if ( (empty($sel['activity']) || $sel['activity'] == 'hay_making') && isset($hayTotals) && (count($hayTotals->seedTotals) > 0 || $hayTotals->grandTotal > 0) ) {
                                    $displayRows->push((object)['is_section_header' => true, 'text' => ' HAY PRODUCTION SUMMARY', 'bg_class' => 'bg-hay', 'icon' => 'fas fa-wheat']);
                                    foreach ($hayTotals->seedTotals as $seed) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => false,
                                            'activity_type' => 'Hay Making',
                                            'seed_name_summary' => $seed->seed_name,
                                            'yield_mt' => $seed->total_yield_mt,
                                            'sold_mt' => $seed->total_sold_mt ?? 0,
                                            'total_price' => $seed->total_price ?? null,
                                            'label_text' => 'Total Yield for ' . $seed->seed_name
                                        ]);
                                    }
                                    if ($hayTotals->grandTotal > 0) {
                                        $displayRows->push((object)[
                                            'is_summary_total' => true,
                                            'is_grand_total' => true,
                                            'activity_type' => 'Hay Making',
                                            'seed_name_summary' => 'GRAND TOTAL',
                                            'yield_mt' => $hayTotals->grandTotal,
                                            'sold_mt' => $hayTotals->grandTotalSold ?? 0,
                                            'total_price' => $hayTotals->grandTotalPrice ?? null,
                                            'label_text' => 'TOTAL'
                                        ]);
                                    }
                                }
                            @endphp

                            @forelse($displayRows as $index => $row)
                                @if(isset($row->is_section_header) && $row->is_section_header)
                                    <tr class="section-header-row {{ $row->bg_class ?? 'bg-info' }}">
                                        <th class="section-header-cell section-title" colspan="9">
                                            <i class="{{ $row->icon ?? 'fas fa-chart-line' }} me-2"></i>
                                            <span class="fw-bold">{{ $row->text }}</span>
                                        </th>
                                        <th class="section-header-cell section-yield-header" data-column="yield_production">
                                            <i class="fas fa-chart-bar me-1"></i>Yield (MT)
                                        </th>
                                        <th class="section-header-cell section-sold-header" data-column="sold_mt">
                                            <i class="fas fa-shipping-fast me-1"></i>Sold (MT)
                                        </th>
                                        <th class="section-header-cell section-sale-header" data-column="total_sale_price">
                                            <i class="fas fa-money-bill-wave me-1"></i>Total Sale (Rs)
                                        </th>
                                        <th class="section-header-cell" colspan="18"></th>
                                    </tr>
                                @elseif(isset($row->is_summary_total) && $row->is_summary_total)
                                    <tr class="summary-total-row @if($row->is_grand_total) grand-total-row @endif">
                                        <td class="col-sr_no summary-cell summary-hidden"></td>
                                        <td class="text-start ps-3 col-activity_type summary-label summary-cell" colspan="9">
                                            <i class="fas fa-calculator me-2 text-primary"></i>
                                            <strong>{{ $row->label_text }}</strong>
                                        </td>
                                        <td class="text-end col-yield_production summary-value summary-cell">
                                            <strong>{{ is_numeric($row->yield_mt) && (float)$row->yield_mt > 0 ? number_format((float)$row->yield_mt, 2) . ' MT' : '--' }}</strong>
                                        </td>
                                        <td class="text-end col-sold_mt summary-value summary-cell">
                                            <strong>{{ is_numeric($row->sold_mt) && (float)$row->sold_mt > 0 ? number_format((float)$row->sold_mt, 2) . ' MT' : '--' }}</strong>
                                        </td>
                                        <td class="text-end col-total_sale_price summary-value summary-cell">
                                            <strong>{{ is_numeric($row->total_price) && (float)$row->total_price > 0 ? '₹' . number_format((float)$row->total_price, 2) : '--' }}</strong>
                                        </td>
                                        <td class="summary-cell summary-hidden" colspan="17"></td>
                                    </tr>
                                @else
                                    <tr class="data-row">
                                        <td class="col-sr_no data-cell">{{ ($paginator->currentPage() - 1) * $paginator->perPage() + ($loop->index + 1) }}</td>
                                        @php
                                            $displayName = collect($orderedActivities)->firstWhere('table', $row['table'])['display'] ?? '-';
                                        @endphp
                                        <?php $block = DB::table('blocks')->where('id', $row['block_name'])->first();
                                         $plot = DB::table('master_plots')->where('id', $row['plot_name'])->first();
                                        ?>
                                        <td class="col-activity_type data-cell">{{ $displayName }}</td>
                                        <td class="col-block_name data-cell">{{ $block->block_name ?? '-' }}</td>
                                        <td class="col-plot_name data-cell">{{ $plot->plot_name ?? '-' }}</td>
                                        <td style="vertical-align: middle; height: 40px; text-align: center; font-size: 14px; padding: 8px; white-space: nowrap;">
                                            @php
                                                $date = $row['date'] ?? null;
                                            @endphp

                                            @if ($date && strtotime($date) !== false)
                                                {{ \Carbon\Carbon::parse($date)->format('d-m-y') }}
                                            @else
                                                &nbsp;
                                            @endif
                                        </td>
                                        <td class="text-end col-area data-cell">{{ $row['area'] ?? '-' }}</td>
                                        <td class="text-end col-area_covered data-cell">{{ $row['area_covered'] ?? '-' }}</td>
                                        <td class="col-seed_name data-cell">{{ $row['seed_name'] ?? '-' }}</td>
                                        <td class="text-end col-seed_consumption data-cell">{{ $row['seed_consumption'] ?? '-' }}</td>
                                        <td class="text-end col-yield_production data-cell">
                                            @if(isset($row['table']) && in_array($row['table'], ['harvesting_update', 'harvestings', 'silage_making', 'hay_making', 'harvest_store_manage']))
                                                {{ is_numeric($row['yield_mt']) && (float)$row['yield_mt'] > 0 ? number_format((float)$row['yield_mt'], 2) : '--' }}
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <!--<td class="text-end col-price_per_mt data-cell">-->
                                        <!--    @if(isset($row['table']) && in_array($row['table'], ['harvesting_update', 'harvestings', 'silage_making', 'hay_making', 'harvest_store_manage']))-->
                                        <!--        {{ is_numeric($row['price_per_mt']) && (float)$row['price_per_mt'] > 0 ? '₹' . number_format((float)$row['price_per_mt'], 2) : '--' }}-->
                                        <!--    @else-->
                                        <!--        ---->
                                        <!--    @endif-->
                                        <!--</td>-->
                                        <!--<td class="text-end col-total_sale_price data-cell">-->
                                        <!--    @if(isset($row['table']) && in_array($row['table'], ['harvesting_update', 'harvestings', 'silage_making', 'hay_making', 'harvest_store_manage']))-->
                                        <!--        {{ is_numeric($row['production_value']) && (float)$row['production_value'] > 0 ? '₹' . number_format((float)$row['production_value'], 2) : '--' }}-->
                                        <!--    @else-->
                                        <!--        ---->
                                        <!--    @endif-->
                                        <!--</td>-->
                                        <td class="col-machine_tractor data-cell">{{ $row['machine_id'] ?? '-' }}</td>
                                        <td class="col-tractor_used data-cell">{{ $row['tractor_id'] ?? '-' }}</td>
                                        <td class="text-end col-hsd_consumption data-cell">{{ $row['hsd_consumption'] ?? '-' }}</td>
                                        <td class="text-end col-hsd_cost data-cell">{{ $row['hsd_cost'] ?? '-' }}</td>
                                        <td class="text-end col-electricity_units data-cell">{{ $row['electricity_units'] ?? '-' }}</td>
                                        <td class="text-end col-electricity_cost data-cell">{{ $row['electricity_cost'] ?? '-' }}</td>
                                        <td class="col-fertilizer_name data-cell">{{ $row['fertilizer_name'] ?? '-' }}</td>
                                        <td class="text-end col-fertilizer_used data-cell">{{ $row['fertilizer_used'] ?? '-' }}</td>
                                        <td class="text-end col-fertilizer_cost data-cell">{{ $row['fertilizer_cost'] ?? '-' }}</td>
                                        <td class="col-manpower_type data-cell">{{ $row['manpower_type'] ?? '-' }}</td>
                                        <td class="text-end col-unskilled data-cell">{{ $row['unskilled'] ?? '-' }}</td>
                                        <td class="text-end col-semi_skilled_1 data-cell">{{ $row['semi_skilled_1'] ?? '-' }}</td>
                                        <td class="text-end col-semi_skilled_2 data-cell">{{ $row['semi_skilled_2'] ?? '-' }}</td>
                                        <td class="text-end col-manpower_cost data-cell">{{ $row['manpower_cost'] ?? '-' }}</td>
                                        @php
                                            $maintenance = $row['major_maintenance'] ?? null;
                                            $items = is_string($maintenance) ? json_decode($maintenance, true) : null;
                                        @endphp
                                        <td class="text-end col-maintenance_cost data-cell">
                                            @if (is_array($items))
                                                @foreach ($items as $item)
                                                    {{ $item['spare_part'] ?? '-' }}: {{ $item['value'] ?? '-' }}<br>
                                                @endforeach
                                            @else
                                                {{ is_numeric($maintenance) ? number_format((float)$maintenance, 2) : '-' }}
                                            @endif
                                        </td>
                                        <td class="col-chemical_name data-cell">{{ $row['chemical_name'] ?? '-' }}</td>
                                        <td class="text-end col-chemical_used data-cell">{{ $row['chemical_used'] ?? '-' }}</td>
                                        <td class="text-end col-total_overall_cost data-cell">{{ $row['total_cost'] ?? '-' }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="30" class="text-center py-5 text-muted">
                                        <i class="fas fa-search fa-3x mb-3 opacity-50"></i>
                                        <h5>No data found for the selected filters</h5>
                                        <p class="mb-0">Try adjusting your filter criteria</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="summary-section p-4">
                <div class="summary-card">
                    <div class="summary-header">
                        <h4 class="fw-bold text-white mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Financial Overview
                        </h4>
                    </div>
                    <div class="summary-body">
                        <div class="row g-4">
                            <div class="col-lg-4 col-md-6">
                                <div class="metric-card cost-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-money-bill-alt"></i>
                                    </div>
                                    <div class="metric-content">
                                        <h6 class="metric-label">Total Cost</h6>
                                        <h3 class="metric-value text-danger">
                                            ₹{{ is_numeric($totalStats['totalCost']) ? number_format((float)$totalStats['totalCost'], 2) : '0.00' }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="metric-card revenue-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="metric-content">
                                        <h6 class="metric-label">Total Revenue</h6>
                                        <h3 class="metric-value text-success">
                                            ₹{{ is_numeric($overallTotalSalePrice) ? number_format((float)$overallTotalSalePrice, 2) : '0.00' }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                @php
                                    $totalCost = is_numeric($totalStats['totalCost']) ? (float)$totalStats['totalCost'] : 0;
                                    $currentOverallSalePrice = is_numeric($overallTotalSalePrice) ? (float)$overallTotalSalePrice : 0;
                                    $profitLoss = $currentOverallSalePrice - $totalCost;
                                @endphp
                                <div class="metric-card {{ $profitLoss >= 0 ? 'profit-card' : 'loss-card' }}">
                                    <div class="metric-icon">
                                        <i class="fas {{ $profitLoss >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' }}"></i>
                                    </div>
                                    <div class="metric-content">
                                        <h6 class="metric-label">{{ $profitLoss >= 0 ? 'Net Profit' : 'Net Loss' }}</h6>
                                        <h3 class="metric-value {{ $profitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                            ₹{{ number_format(abs($profitLoss), 2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pagination-section p-4 bg-light">
                <div class="d-flex justify-content-center">
                    {{ $paginator->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced CSS for Professional UI with COMPLETELY FIXED Header Colors */
:root {
    --primary-color: #2d5016;
    --secondary-color: #6b8e23;
    --accent-color: #8fbc34;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
    --border-radius: 12px;
    --box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Main Card Styling */
.main-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

/* Enhanced Header */
.card-header {
    position: relative;
    overflow: hidden;
}

.header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.header-decoration {
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}

/* Filter Section */
.filter-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.filter-header {
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.form-group-enhanced {
    position: relative;
}

.form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.form-label i {
    color: var(--secondary-color);
}

.form-select-enhanced,
.form-control-enhanced {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    transition: var(--transition);
    background: white;
}

.form-select-enhanced:focus,
.form-control-enhanced:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 0.2rem rgba(107, 142, 35, 0.25);
    outline: none;
}

.date-range-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-separator {
    color: var(--secondary-color);
    font-weight: 600;
    padding: 0 5px;
}

.btn-primary-enhanced {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(45, 80, 22, 0.3);
}

.btn-primary-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(45, 80, 22, 0.4);
    background: linear-gradient(135deg, #1a3009 0%, #5a7a1e 100%);
}

/* Table Enhancements - COMPLETELY FIXED HEADER COLORS */
.table-section {
    background: white;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid var(--primary-color);
}

.table-enhanced {
    margin-bottom: 0;
    font-size: 13px;
}

/* COMPLETELY FIXED: Main Table Headers with Dark Background and White Text */
.table-header-enhanced {
    background: #2d5016 !important;
}

.table-header-cell {
    background: #2d5016 !important;
    color: #ffffff !important;
    border: 1px solid #1a3009 !important;
    padding: 16px 12px !important;
    font-weight: 700 !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 10 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    font-size: 13px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.table-header-cell i {
    opacity: 1 !important;
    color: #ffffff !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

/* Data Cells */
.data-cell {
    padding: 12px 8px;
    vertical-align: middle;
    border: 1px solid #e9ecef;
    background: #ffffff;
    color: #495057;
    font-size: 13px;
}

.data-row {
    transition: var(--transition);
}

.data-row:hover {
    background-color: rgba(107, 142, 35, 0.05) !important;
}

.data-row:hover .data-cell {
    background-color: rgba(107, 142, 35, 0.05) !important;
}

/* COMPLETELY FIXED: Section Headers with Dark Background and White Text */
.section-header-row {
    background: #17a2b8 !important;
}

.section-header-row.bg-silage {
    background: #6c757d !important;
}

.section-header-row.bg-hay {
    background: #e0a800 !important;
}

.section-header-row.bg-harvest {
    background: #17a2b8 !important;
}

.section-header-cell {
    background: inherit !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
    padding: 16px !important;
    text-align: center !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 14px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
    vertical-align: middle !important;
}

.section-header-cell i {
    color: #ffffff !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.section-title {
    font-size: 16px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}

.section-sold-header {
    background: inherit !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
    padding: 16px !important;
    text-align: center !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 14px !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
    vertical-align: middle !important;
}

.section-sold-header i {
    color: #ffffff !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

/* FIXED: Summary Rows with Better Visibility and Perfect Alignment */
.summary-total-row {
    background: #f8f9fa !important;
    border-top: 2px solid #dee2e6 !important;
    border-bottom: 2px solid #dee2e6 !important;
}

.summary-cell {
    padding: 14px 12px !important;
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    color: #343a40 !important;
    vertical-align: middle !important;
}

.summary-label {
    color: #343a40 !important;
    font-weight: 600 !important;
}

.summary-label i {
    color: #2d5016 !important;
}

.summary-value {
    font-weight: 700 !important;
    color: #2d5016 !important;
}

.summary-hidden {
    display: none !important;
}

.col-sold_mt {
    padding: 12px 8px;
    vertical-align: middle;
    border: 1px solid #e9ecef;
    background: #ffffff;
    color: #495057;
    font-size: 13px;
    text-align: right;
}

.summary-total-row .col-sold_mt {
    padding: 14px 12px !important;
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    color: #2d5016 !important;
    vertical-align: middle !important;
    font-weight: 700 !important;
}

.grand-total-row {
    background: #d1e7dd !important;
    border: 2px solid #28a745 !important;
}

.grand-total-row .summary-cell {
    background: #d1e7dd !important;
    border-color: #28a745 !important;
    color: #0f5132 !important;
}

.grand-total-row .summary-label,
.grand-total-row .summary-value {
    color: #0f5132 !important;
    font-weight: 700 !important;
}

.grand-total-row .summary-label i {
    color: #0f5132 !important;
}

.grand-total-row .summary-value {
    font-size: 16px !important;
}

.grand-total-row .col-sold_mt {
    background: #d1e7dd !important;
    border-color: #28a745 !important;
    color: #0f5132 !important;
    font-weight: 700 !important;
    font-size: 16px !important;
}

/* Summary Section */
.summary-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
}

.summary-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.summary-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    padding: 20px;
    text-align: center;
}

.summary-body {
    padding: 30px;
}

.metric-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: var(--transition);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 20px;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.cost-card {
    border-left-color: var(--danger-color);
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
}

.revenue-card {
    border-left-color: var(--success-color);
    background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
}

.profit-card {
    border-left-color: var(--success-color);
    background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
}

.loss-card {
    border-left-color: var(--danger-color);
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.cost-card .metric-icon {
    background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
}

.revenue-card .metric-icon,
.profit-card .metric-icon {
    background: linear-gradient(135deg, var(--success-color) 0%, #1e7e34 100%);
}

.loss-card .metric-icon {
    background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
}

.metric-content {
    flex: 1;
}

.metric-label {
    font-size: 14px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

/* Pagination */
.pagination-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .date-range-wrapper {
        flex-direction: column;
        gap: 8px;
    }

    .date-separator {
        display: none;
    }

    .metric-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .metric-value {
        font-size: 24px;
    }

    .table-enhanced {
        font-size: 12px;
    }

    .table-header-cell {
        padding: 12px 8px !important;
        font-size: 12px !important;
    }
}

/* Hidden columns */
.hidden-column {
    display: none !important;
}

/* Font Awesome and Bootstrap Icons */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css");

/* Animation for loading states */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.main-card {
    animation: fadeIn 0.6s ease-out;
}

/* Scrollbar styling */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--secondary-color);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {
    $('#myTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Download Excel',
                title: 'MyDataTableExport'
            }
        ]
    });
    $('#block').on('change', function() {
    let blockName = $(this).val();
    $('#plot').html('<option value="">Loading...</option>');

    fetch(`${getPlotsUrl}?block=${encodeURIComponent(blockName)}`)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">Select Plot</option>';
            data.forEach(plot => {
                options += `<option value="${plot.id}">${plot.plot_name}</option>`;
            });
            $('#plot').html(options);
        });
});
});
document.addEventListener('DOMContentLoaded', function() {
    const blockSelect = document.querySelector('select[name="block"]');
    const plotSelect = document.querySelector('select[name="plot"]');
    const activitySelect = document.getElementById('activity');
    const table = document.getElementById('activityTable');
    const tableHeaders = table.querySelectorAll('thead th');
    const tableBodyRows = table.querySelectorAll('tbody tr');
    const plotsByBlock = @json($plotsByBlock ?? []);

    function updatePlotOptions(blockName) {
        plotSelect.innerHTML = '<option value="">Select Plot</option>';
        if (!blockName || !plotsByBlock[blockName]) {
            return;
        }
        plotsByBlock[blockName].forEach(function(plot) {
            const option = document.createElement('option');
            option.value = plot;
            option.textContent = plot;
            if ("{{ $sel['plot'] ?? '' }}" === plot) {
                option.selected = true;
            }
            plotSelect.appendChild(option);
        });
    }

    function isCellTrulyEmptyForDisplay(value) {
        const trimmedValue = value.toLowerCase().trim();
        const emptyPlaceholders = ['-', '', '0.00', '0'];
        if (emptyPlaceholders.includes(trimmedValue)) {
            return true;
        }
        if (!isNaN(parseFloat(value)) && isFinite(value) && parseFloat(value) !== 0) {
            return false;
        }
        if (parseFloat(value) === 0) {
            return true;
        }
        return false;
    }

    function hideEmptyColumns() {
        const selectedActivity = activitySelect.value;
        const columnVisibilityMap = {};

        // Initialize column visibility
        tableHeaders.forEach(header => {
            const columnName = header.dataset.columnName;
            if (columnName) {
                // Always visible columns unless explicitly hidden by data
                if (['sr_no', 'activity_type', 'block_name', 'plot_name', 'date', 'area', 'yield_production', 'sold_mt', 'total_sale_price'].includes(columnName)) {
                    columnVisibilityMap[columnName] = true;
                } else {
                    columnVisibilityMap[columnName] = false;
                }
            }
        });

        // Check data rows for content
        tableBodyRows.forEach(row => {
            if (row.classList.contains('section-header-row') || row.classList.contains('summary-total-row')) {
                return; // Skip section/summary headers
            }

            row.querySelectorAll('td').forEach((cell, cellIndex) => {
                const header = tableHeaders[cellIndex];
                const columnName = header ? header.dataset.columnName : null;
                if (columnName && !['sr_no', 'activity_type', 'block_name', 'plot_name', 'date', 'area'].includes(columnName)) {
                    const cellValue = cell.textContent.trim();
                    if (!isCellTrulyEmptyForDisplay(cellValue)) {
                        columnVisibilityMap[columnName] = true;
                    }
                }
            });
        });

        // Apply visibility to headers and cells
        tableHeaders.forEach((header, index) => {
            const columnName = header.dataset.columnName;
            if (columnName) {
                const isVisible = columnVisibilityMap[columnName];
                header.classList.toggle('hidden-column', !isVisible);

                // Apply to all data cells
                tableBodyRows.forEach(row => {
                    const cell = row.querySelector(`.col-${columnName}`);
                    if (cell) {
                        cell.classList.toggle('hidden-column', !isVisible);
                    }
                });
            }
        });

        // Update section header visibility
        updateSectionHeaderVisibility(columnVisibilityMap);
    }

    function updateSectionHeaderVisibility(columnVisibilityMap) {
        const sectionHeaders = table.querySelectorAll('.section-header-row');

        sectionHeaders.forEach(row => {
            const yieldHeader = row.querySelector('.section-yield-header');
            const soldHeader = row.querySelector('.section-sold-header');
            const saleHeader = row.querySelector('.section-sale-header');

            // Always show Yield, Sold MT and Total Sale headers in summary sections
            if (yieldHeader) {
                yieldHeader.classList.remove('hidden-column');
            }
            if (soldHeader) {
                soldHeader.classList.remove('hidden-column');
            }
            if (saleHeader) {
                saleHeader.classList.remove('hidden-column');
            }
        });
    }

    // Initial calls
    updatePlotOptions(blockSelect.value);
    hideEmptyColumns(); // Call on page load

    // Event listeners
    blockSelect.addEventListener('change', function() {
        updatePlotOptions(this.value);
    });

    activitySelect.addEventListener('change', hideEmptyColumns);
});

{{-- Blade: safe route template (we replace BLOCK_ID at runtime) --}}

document.addEventListener('DOMContentLoaded', function () {
    const blockSelect = document.getElementById('block');
    const plotSelect  = document.getElementById('plot');

    // route template from Laravel (replace BLOCK_ID later)
    const getPlotsUrlTemplate = '{{ route("get.plots", ["blockId" => "BLOCK_ID"]) }}';

    if (!blockSelect || !plotSelect) return;

    blockSelect.addEventListener('change', function () {
        loadPlots(this.value);
    });

    // If block is pre-selected on page load, load its plots
    if (blockSelect.value) {
        loadPlots(blockSelect.value);
    }

    async function loadPlots(blockId) {
        // reset
        plotSelect.innerHTML = '<option value="">Select Plot</option>';
        if (!blockId) return;

        const url = getPlotsUrlTemplate.replace('BLOCK_ID', encodeURIComponent(blockId));

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            // handle different shapes:
            // 1) array of objects: [{id:1, plot_name:"a1"}, ...]
            // 2) array of strings: ["a1","a2"]
            // 3) mapping object: { "1": "a1", "2":"a2" }
            if (Array.isArray(data)) {
                data.forEach(item => {
                    let value, label;
                    if (item && typeof item === 'object') {
                        // try common keys
                        value = item.id ?? item.plot_id ?? item.plotId ?? item.plot_name ?? item.name ?? '';
                        label = item.plot_name ?? item.name ?? item.plotName ?? value;
                    } else {
                        // plain string
                        value = item;
                        label = item;
                    }
                    if (value === undefined || value === null) value = label;
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    plotSelect.appendChild(opt);
                });
            } else if (data && typeof data === 'object') {
                Object.entries(data).forEach(([k, v]) => {
                    const opt = document.createElement('option');
                    opt.value = k;
                    opt.textContent = v;
                    plotSelect.appendChild(opt);
                });
            } else {
                console.warn('Unexpected get-plots response:', data);
            }
        } catch (err) {
            console.error('Error loading plots for block', blockId, err);
            // optionally show user-friendly message
            // e.g. plotSelect.innerHTML = '<option value="">Unable to load plots</option>';
        }
    }
});


</script>

<!-- Preview & Copy Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #2d5016 0%, #6b8e23 100%);">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-table me-2"></i>Report Data Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">This preview shows the data ready for copying to Excel.</span>
                    <button id="copyAllData" class="btn btn-success btn-sm shadow-sm">
                        <i class="fas fa-copy me-2"></i>Copy All Data
                    </button>
                </div>
                <div class="table-responsive rounded shadow-sm bg-white">
                    <table class="table table-bordered table-hover mb-0" id="previewTable" style="font-size: 11px; white-space: nowrap;">
                        <thead class="bg-light">
                            <tr>
                                <th>Sr. No.</th>
                                <th>Activity Type</th>
                                <th>Block Name</th>
                                <th>Plot No</th>
                                <th>Date</th>
                                <th>Area (Acre)</th>
                                <th>Area Covered (Acre)</th>
                                <th>Seed Name</th>
                                <th>Seed Consumption</th>
                                <th>Yield (MT)</th>
                                <th>Machine Used</th>
                                <th>Tractor Used</th>
                                <th>HSD Consumption (Ltr)</th>
                                <th>HSD Cost (Rs)</th>
                                <th>Electricity Units</th>
                                <th>Electricity Cost (Rs)</th>
                                <th>Fertilizer Name</th>
                                <th>Fertilizer Used</th>
                                <th>Fertilizer Cost (Rs)</th>
                                <th>Manpower Type</th>
                                <th>Unskilled</th>
                                <th>Semi-Skilled 1</th>
                                <th>Semi-Skilled 2</th>
                                <th>Manpower Cost (Rs)</th>
                                <th>Maintenance Cost (Rs)</th>
                                <th>Chemical Name</th>
                                <th>Chemical Used</th>
                                <th>Total Cost (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $modalRows = collect($summary);
                                // (Logic for totals is same as main table)
                                if ( (empty($sel['activity']) || $sel['activity'] == 'harvesting_update') && isset($harvestStoreManageTotals) && (count($harvestStoreManageTotals->seedTotals) > 0 || $harvestStoreManageTotals->grandTotal > 0) ) {
                                    $modalRows->push((object)['is_section_header' => true, 'text' => 'YIELD PRODUCTION SUMMARY']);
                                    foreach ($harvestStoreManageTotals->seedTotals as $item) {
                                        $modalRows->push((object)['is_summary_total' => true, 'label_text' => 'Total Yield for ' . $item->seed_name, 'yield_mt' => $item->total_yield_mt, 'sold_mt' => $item->total_sold_mt ?? 0, 'total_price' => $item->total_price ?? null]);
                                    }
                                }
                                // ... other totals can be added if needed, but keeping it simple for now to match main data
                            @endphp

                            @forelse($full_summary as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    @php
                                        $displayActivity = $activityMap[$row['table']] ?? $row['table'];
                                        $displayBlock = $blockMap[$row['block_name']] ?? ($row['block_name'] ?? '-');
                                        $displayPlot = $plotMap[$row['plot_name']] ?? ($row['plot_name'] ?? '-');
                                        
                                        $rawDate = $row['date'] ?? null;
                                        $date = ($rawDate && $rawDate !== '-' && strtotime($rawDate)) ? \Carbon\Carbon::parse($rawDate)->format('d-m-Y') : '-';
                                        
                                        // Handle machines (can be multiple)
                                        $mIds = array_map('trim', explode(',', $row['machine_id'] ?? ''));
                                        $displayMachine = !empty($row['machine_id']) ? implode(', ', array_map(fn($id) => $machineMap[$id] ?? $id, $mIds)) : '-';
                                        
                                        // Handle tractors (can be multiple)
                                        $tIds = array_map('trim', explode(',', $row['tractor_id'] ?? ''));
                                        $displayTractor = !empty($row['tractor_id']) ? implode(', ', array_map(fn($id) => $tractorMap[$id] ?? $id, $tIds)) : '-';
                                    @endphp
                                    <td>{{ $displayActivity }}</td>
                                    <td>{{ $displayBlock }}</td>
                                    <td>{{ $displayPlot }}</td>
                                    <td>{{ $date }}</td>
                                    <td class="text-end">{{ $row['area'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['area_covered'] ?? '-' }}</td>
                                    <td>{{ $row['seed_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['seed_consumption'] ?? '-' }}</td>
                                    <td class="text-end">{{ is_numeric($row['yield_mt'] ?? null) ? number_format((float)$row['yield_mt'], 2) : '-' }}</td>
                                    <td>{{ $displayMachine }}</td>
                                    <td>{{ $displayTractor }}</td>
                                    <td class="text-end">{{ $row['hsd_consumption'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['hsd_cost'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['electricity_units'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['electricity_cost'] ?? '-' }}</td>
                                    <td>{{ $row['fertilizer_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['fertilizer_used'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['fertilizer_cost'] ?? '-' }}</td>
                                    <td>{{ $row['manpower_type'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['unskilled'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['semi_skilled_1'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['semi_skilled_2'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['manpower_cost'] ?? '-' }}</td>
                                    <td class="text-end">
                                        @php
                                            $maintenance = $row['major_maintenance'] ?? null;
                                            $items = is_string($maintenance) ? json_decode($maintenance, true) : null;
                                        @endphp
                                        @if (is_array($items))
                                            @foreach ($items as $m_item)
                                                {{ $m_item['spare_part'] ?? '-' }}: {{ $m_item['value'] ?? '-' }}
                                            @endforeach
                                        @else
                                            {{ is_numeric($maintenance) ? number_format((float)$maintenance, 2) : '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $row['chemical_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['chemical_used'] ?? '-' }}</td>
                                    <td class="text-end">{{ $row['total_cost'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="28" class="text-center">No records to preview</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* New Button Style */
.btn-info-enhanced {
    background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    color: white;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}
.btn-info-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copyAllData');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const table = document.getElementById('previewTable');
            let copyText = "";
            
            // Get Headers
            const headers = table.querySelectorAll('thead th');
            headers.forEach((th, i) => {
                copyText += th.innerText.trim() + (i === headers.length - 1 ? "" : "\t");
            });
            copyText += "\n";
            
            // Get Rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) { // Skip "No data" row
                    cells.forEach((td, i) => {
                        // Remove Rupee symbol and commas for better Excel compatibility in Cost column
                        let text = td.innerText.trim().replace('₹', '').replace(/,/g, '');
                        copyText += text + (i === cells.length - 1 ? "" : "\t");
                    });
                    copyText += "\n";
                }
            });
            
            // Copy to clipboard
            navigator.clipboard.writeText(copyText).then(() => {
                const originalContent = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-primary');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalContent;
                    copyBtn.classList.remove('btn-primary');
                    copyBtn.classList.add('btn-success');
                }, 2000);
            }).catch(err => {
                alert('Failed to copy data: ' + err);
            });
        });
    }
});
</script>

@endsection
