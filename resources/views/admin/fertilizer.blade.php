@extends('layouts.app')

@section('title', 'Fertilizer Management')

@section('content')

@php
$fields = [
    'fertilizer_type' => 'Fertilizer Type',
    'brand_name' => 'Brand Name',
    'supplier_name' => 'Supplier Name',
];

// Calculate totals
$totalStock = $fertilizers->sum('stock_kg');
$totalAmount = $fertilizers->sum('calculated_amount');
@endphp

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<style>
/* target the specific table to avoid Bootstrap conflicts */
#myDataTable {
  width: 100%;
  border-collapse: separate; /* helps sticky behave consistently */
}

/* scrolling wrapper (keep this on the wrapper, not the table itself) */
.table-wrapper {
  max-height: 1100;      /* height of scroll area */
  overflow-y: auto;       /* THIS creates the scroll context */
  -webkit-overflow-scrolling: touch;
}

/* make the thead behave as header-group (ensures proper layout) */
#myDataTable thead {
  display: table-header-group;
}

/* sticky header cells */
#myDataTable thead th {
  position: sticky;
  top: 0;
  z-index: 999;                 /* large so it sits above table body elements */
  background: #6b8e23;          /* header color (must be opaque) */
  color: #fff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08); /* optional slight shadow */
}

/* remove conflicting rules that set header background to white */
.table-wrapper table th { background: transparent; }

/* rest of your styles (kept intact) */
.table tbody tr:nth-child(even) {
  background-color: #f0f4e6;
}

.table thead th, .table tbody td {
  vertical-align: middle;
}

   button.btn.btn-warning.dt-button {
    color: #fff !important;
}

/* small adjustments for image width inside the table */
#myDataTable img { max-width: 100px; height: auto; display: block; margin: 0 auto; }
</style>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Filter Section for Admin -->
@if(Auth::check() && Auth::user()->role == 1)
<div class="filter-section">
    <form method="GET" action="{{ route('fertilizer.index') }}">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Site</label>
                <select name="site_id" class="form-select">
                    <option value="">-- All Sites --</option>
                    @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                        {{ $site->site_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('fertilizer.index') }}" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </div>
    </form>
</div>
@endif
<div class="container-fluid mt-4">
    
<!-- Chart Section -->
<div class="chart-container" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
    <div class="card shadow-sm mb-4">
    <div class="card-body">
    <h3 style="margin-bottom: 15px;">📊 Quarterly Fertilizer Performance</h3>
    
     
    <div style="display:flex;gap:10px;margin-bottom:15px;">
        <select id="yearFilter" class="form-control" style="width:150px;">
            <option value="">Loading...</option>
        </select>
        <select id="fertilizerFilter" class="form-control" style="width:200px;">
            <option value="All">All Fertilizers</option>
        </select>
    </div>
    <div id="quarterlyFertilizerChart" style="width:100%;height:500px; background: white; border-radius: 8px;"></div>
</div>
</div>
</div>
</div>

<!-- Action Buttons and Search -->
<div class="d-flex justify-content-between align-items-center mt-4">
    @if(Auth::check() && Auth::user()->role != 1)
    <button class="btn-add ms-auto" data-bs-toggle="modal" data-bs-target="#addFertilizerModal" style="background-color: #6b8e23;">
        <i class="fas fa-plus"></i> Add Fertilizer
    </button>
    @endif

     
</div>

<!-- Data Table -->
<div class="table-responsive mt-4">
    <table class="table table-bordered text-center align-middle" id="myDataTable">
        <thead>
            <tr>
                <th>Sr.No.</th>
                @if(Auth::check() && Auth::user()->role == 1)
                <th>Location</th>
                @endif
                <th>Fertilizer Name</th>
                <th>Type</th>
                <th>Brand</th>
                <th>Stock</th>
                <th>UOM</th>
                <th>Purchase Date</th>
                <th>Expiry</th>
                <th>Supplier</th>
                <th>Price/(UOM) (Rs)</th>
                <th>Amount (Rs)</th>

                @if(Auth::check() && Auth::user()->role != 1)
                <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($fertilizers as $index => $fertilizer)
            <tr>
                <td>{{ $index + 1 }}</td>
                @if(Auth::check() && Auth::user()->role == 1)
                <td>{{ $fertilizer->site_name }}</td>
                @endif
                <td>{{ $fertilizer->fertilizer_name }}</td>
                <td>{{ $fertilizer->fertilizer_type }}</td>
                <td>{{ $fertilizer->brand_name }}</td>
                <td>{{ $fertilizer->stock_kg }}</td>
                <td>{{ $fertilizer->uom }}</td>
                <td>{{ \Carbon\Carbon::parse($fertilizer->purchase_date)->format('d-m-Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($fertilizer->expiry_date)->format('d-m-Y') }}</td>
                <td>{{ $fertilizer->supplier_name }}</td>
                <td>{{ $fertilizer->rate }}</td>
                <td>{{ number_format($fertilizer->calculated_amount, 2) }}</td>

                @if(Auth::check() && Auth::user()->role != 1)
                <td class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm" style="background-color: #FFD700; color: black;" data-bs-toggle="modal" data-bs-target="#editFertilizerModal{{ $fertilizer->id }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStockModal{{ $fertilizer->id }}">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn btn-info btn-sm view-history-btn" data-fertilizer-id="{{ $fertilizer->id }}" data-bs-toggle="modal" data-bs-target="#historyModal">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
                @endif
            </tr>

            <!-- Edit Modal -->
            @if(Auth::check() && Auth::user()->role != 1)
            <div class="modal fade" id="editFertilizerModal{{ $fertilizer->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('fertilizer.update', $fertilizer->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Fertilizer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    @if(Auth::user()->role == 1)
                                    <div class="col-md-4">
                                        <label class="form-label">Location</label>
                                        <select name="site_id" class="form-select" required>
                                            <option value="">-- Select Site --</option>
                                            @foreach($sites as $site)
                                            <option value="{{ $site->id }}" {{ $fertilizer->site_id == $site->id ? 'selected' : '' }}>{{ $site->site_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @else
                                    <div class="col-md-4">
                                        <label class="form-label">Location</label>
                                        @php
                                        $userSite = $sites->where('id', Auth::user()->site_id)->first();
                                        @endphp
                                        <input type="text" class="form-control" value="{{ $userSite ? $userSite->site_name : 'N/A' }}" readonly>
                                        <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                                    </div>
                                    @endif

                                    @foreach($fields as $field => $label)
                                    <div class="col-md-4">
                                        <label class="form-label">{{ $label }}</label>
                                        <input type="text" class="form-control" name="{{ $field }}" value="{{ $fertilizer->$field }}" required>
                                    </div>
                                    @endforeach
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" class="form-control" name="purchase_date" value="{{ $fertilizer->purchase_date }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" name="expiry_date" value="{{ $fertilizer->expiry_date }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success w-100">Update Fertilizer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Add Stock Modal -->
            <div class="modal fade" id="addStockModal{{ $fertilizer->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('fertilizers.addStock', $fertilizer->id) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Add Stock for {{ $fertilizer->fertilizer_name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Stock (UOM)</label>
                                    <input type="number" class="form-control" value="{{ $fertilizer->stock_kg }}" readonly>
                                    <small class="text-muted">This is the current stock before adding.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Added Quantity (UOM)</label>
                                    <input type="number" step="0.01" name="quantity_to_add" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Price (Rs)</label>
                                    <input type="number" step="0.01" name="new_rate" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Supplier Name</label>
                                    <input type="text" name="supplier" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success w-100">Add Stock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <tr>
                <td colspan="{{ Auth::check() && Auth::user()->role == 1 ? '12' : '11' }}">No fertilizers found.</td>
            </tr>
            @endforelse
        </tbody>
        @if ($fertilizers->count() > 0)
        <tfoot>
            <tr class="fw-bold">
                <td colspan="{{ Auth::check() && Auth::user()->role == 1 ? '5' : '4' }}">Total</td>
                <td>{{ number_format($totalStock, 2) }}</td>
                <td colspan="5"></td>
                <td>{{ number_format($totalAmount, 2) }}</td>
                @if(Auth::check() && Auth::user()->role != 1)
                <td></td>
                @endif
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<!-- Add Fertilizer Modal -->
@if(Auth::check() && Auth::user()->role != 1)
<div class="modal fade" id="addFertilizerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('fertilizer.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Fertilizer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @if(Auth::user()->role == 1)
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <select name="site_id" class="form-select" required>
                                <option value="">-- Select Site --</option>
                                @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            @php
                            $userSite = $sites->where('id', Auth::user()->site_id)->first();
                            @endphp
                            <input type="text" class="form-control" value="{{ $userSite ? $userSite->site_name : 'N/A' }}" readonly>
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                        </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Fertilizers</label>
                            <select class="form-select" name="fertilizer_id" id="fertilizer_id">
                                <option value="">Select Fertilizer</option>
                                @foreach($fertilizer_masters as $ferti)
                                <option value="{{ $ferti->id }}">{{ $ferti->fertilizer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach($fields as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <input type="text" class="form-control" name="{{ $field }}" required>
                        </div>
                        @endforeach
                        <div class="col-md-4">
                            <label class="form-label">UOM</label>
                            <select class="form-select" name="uom" id="uom" required>
                                <option value="">Select UOM</option>
                                <option value="Kg">Kg</option>
                                <option value="Ltr">Ltr</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price/UOM (Rs)</label>
                            <input type="number" class="form-control" name="rate" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Initial Stock</label>
                            <input type="number" class="form-control" name="stock_kg" step="any" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-add">Add Fertilizer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historyModalTitle">Fertilizer Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card p-3 mb-3">
                    <div class="row text-left fw-bold">
                        <div class="col-md-6 col-12">
                            <h6 id="historyFertilizerName"><strong>Fertilizer Name:</strong> Loading...</h6>
                        </div>
                        <div class="col-md-6 col-12">
                            <p id="historyFertilizerDetails"><strong>Type:</strong> Loading... | <strong>Brand:</strong> Loading...</p>
                        </div>
                    </div>
                    <div class="row text-left">
                        <div class="col-md-6 col-12">
                            <strong>Total Stock:</strong>
                            <span class="text-success" id="historyTotalStock">Loading...</span>
                        </div>
                        <div class="col-md-6 col-12">
                            <strong>Total Stock Price:</strong>
                            <span class="text-success" id="historyTotalStockPrice">Loading...</span>
                        </div>
                        <div class="col-md-6 col-12">
                            <strong>Current Stock:</strong>
                            <span class="text-success" id="historyCurrentStock">Loading...</span>
                        </div>
                        <div class="col-md-6 col-12">
                            <strong>Current Stock Price:</strong>
                            <span class="text-success" id="historyCurrentStockPrice">Loading...</span>
                        </div>
                    </div>
                </div>

                <h6 class="text-primary">Stock History</h6>
                <div id="loadingSpinner" class="text-center my-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading history...</p>
                </div>
                <div id="historyMessage" class="alert alert-info text-center d-none"></div>
                <table class="table table-bordered table-striped">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>Date</th>
                            <th>Added Quantity (kg)</th>
                            <th>Consumed Quantity (Kg)</th>
                            <th>Price (Rs)</th>
                            <th>Total Price (Rs)</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
 

<!-- Make sure ApexCharts + Bootstrap are loaded BEFORE this script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Convert PHP data to JavaScript
const allData = @json($quarterlyData ?? []);

 
console.log('=== CHART DEBUG ===');
console.log('Total records:', allData.length);
console.log('All data:', allData);
console.log('Sample:', allData.slice(0, 5));

// Populate filter dropdowns dynamically
function populateFilters() {
    if (!allData || allData.length === 0) {
        console.warn('❌ No data available for chart');
        document.getElementById('yearFilter').innerHTML = '<option value="">No Data</option>';
        document.getElementById('fertilizerFilter').innerHTML = '<option value="All">No Data</option>';
        return;
    }

    // Get unique years and sort descending
    const years = [...new Set(allData.map(d => d.year))].sort((a, b) => b - a);
    console.log('Years found:', years);
    
    const yearFilter = document.getElementById('yearFilter');
    if (years.length === 0) {
        yearFilter.innerHTML = '<option value="">No Data</option>';
    } else {
        yearFilter.innerHTML = years.map(y => 
            `<option value="${y}" ${y == new Date().getFullYear() ? 'selected' : ''}>${y}</option>`
        ).join('');
    }

    // Get unique fertilizers
    const fertilizers = [...new Set(allData.map(d => d.fertilizer))].filter(f => f).sort();
    console.log('Fertilizers found:', fertilizers);
    
    const fertilizerFilter = document.getElementById('fertilizerFilter');
    fertilizerFilter.innerHTML = '<option value="All">All Fertilizers</option>' + 
        fertilizers.map(f => `<option value="${f}">${f}</option>`).join('');
}

function filterAndRender() {
    console.log('=== FILTER AND RENDER ===');
    
    if (!allData || allData.length === 0) {
        document.getElementById('quarterlyFertilizerChart').innerHTML = 
            '<div style="text-align:center;padding:100px;color:#999;"><i class="fas fa-chart-bar fa-3x"></i><br><br><h4>No Data Available</h4><p>Please add purchase or consumption records to view the chart.</p></div>';
        return;
    }

    const year = document.getElementById('yearFilter').value;
    const fert = document.getElementById('fertilizerFilter').value;

    console.log('Selected filters:', { year, fert });

    const filtered = allData.filter(d =>
        (year === "All" || year === "" || d.year === year) &&
        (fert === "All" || d.fertilizer === fert)
    );

    console.log('Filtered data count:', filtered.length);
    console.log('Filtered data:', filtered);

    if (filtered.length === 0) {
        document.getElementById('quarterlyFertilizerChart').innerHTML = 
            '<div style="text-align:center;padding:100px;color:#999;"><h4>No data available for selected filters</h4><p>Try selecting different year or fertilizer.</p></div>';
        return;
    }

    const quarters = ["Q1 (Apr–Jun)", "Q2 (Jul–Sep)", "Q3 (Oct–Dec)", "Q4 (Jan–Mar)"];
    let series = [];
    let chartType = 'bar';
    let horizontal = false;

    if (fert === "All") {
        // Compare all fertilizers → stacked vertical bars
        const fertilizers = [...new Set(filtered.map(d => d.fertilizer))];
        console.log('Rendering all fertilizers:', fertilizers);
        
        series = fertilizers.map(f => ({
            name: f,
            data: quarters.map(q => {
                const entry = filtered.find(e => e.fertilizer === f && e.quarter === q);
                const value = entry ? (entry.purchased - entry.consumed) : 0;
                console.log(`${f} - ${q}:`, value, entry);
                return value;
            })
        }));
    } else {
        // Single fertilizer → compare Purchased vs Current Inventory
        console.log('Rendering single fertilizer:', fert);
        
        const purchased = quarters.map(q => {
            const entry = filtered.find(e => e.quarter === q);
            console.log(`${q} - Purchased:`, entry?.purchased || 0);
            return entry ? entry.purchased : 0;
        });
        
        const remaining = quarters.map(q => {
            const entry = filtered.find(e => e.quarter === q);
            const value = entry ? (entry.purchased - entry.consumed) : 0;
            console.log(`${q} - Remaining:`, value);
            return value;
        });

        series = [
            { name: "New Purchase", data: purchased },
            { name: "Current Inventory", data: remaining }
        ];

        horizontal = true;
    }

    console.log('Chart series:', series);

    const options = {
        series: series,
        chart: {
            type: chartType,
            height: 500,
            stacked: fert === "All",
            toolbar: { show: true },
            foreColor: '#333',
            dropShadow: {
                enabled: true,
                top: 6,
                left: 4,
                blur: 10,
                opacity: 0.4,
                color: '#000'
            }
        },
        plotOptions: {
            bar: {
                horizontal: horizontal,
                columnWidth: horizontal ? '70%' : '55%',
                barHeight: horizontal ? '60%' : undefined,
                borderRadius: 10,
                endingShape: 'rounded'
            }
        },
        colors: ['#00C853', '#FFD600', '#29B6F6', '#8D6E63', '#E91E63', '#9C27B0'],
        dataLabels: {
            enabled: true,
            formatter: val => val > 0 ? val.toFixed(0) + " Kg" : "",
            style: { colors: ['#fff'], fontSize: '12px', fontWeight: 600 }
        },
        stroke: { show: true, width: 2, colors: ['#fff'] },
        xaxis: {
            categories: quarters,
            title: { text: horizontal ? "Quantity (Kg)" : "Quarter", style: { fontWeight: 700 } },
            labels: { style: { fontSize: '13px', fontWeight: 600 } }
        },
        yaxis: {
            title: { text: horizontal ? "Quarter" : "Quantity (Kg)" },
            labels: { 
                style: { fontSize: '13px' },
                formatter: val => val.toFixed(0)
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.6,
                gradientToColors: ['#66BB6A', '#FFEE58', '#4FC3F7', '#A1887F', '#F06292', '#BA68C8'],
                opacityFrom: 0.95,
                opacityTo: 0.85,
                stops: [0, 100]
            }
        },
        tooltip: {
            theme: 'dark',
            y: { formatter: val => val.toFixed(2) + " Kg" }
        },
        grid: {
            borderColor: '#e0e0e0',
            strokeDashArray: 4
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '14px',
            fontWeight: 600
        },
        title: {
            text: fert === "All"
                ? "Quarterly Fertilizer Stock Comparison"
                : `${fert} – New Purchase vs Current Inventory`,
            align: 'left',
            style: { fontSize: '18px', fontWeight: '700', color: '#1B5E20' }
        }
    };

    console.log('Rendering chart with options:', options);

    const chartDiv = document.querySelector("#quarterlyFertilizerChart");
    chartDiv.innerHTML = '';
    const chart = new ApexCharts(chartDiv, options);
    chart.render();
    
    console.log('✅ Chart rendered successfully');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Page loaded, initializing chart...');
    populateFilters();
    filterAndRender();
    
    // Re-render on filter change
    ['yearFilter', 'fertilizerFilter'].forEach(id =>
        document.getElementById(id).addEventListener('change', () => {
            console.log('Filter changed:', id);
            filterAndRender();
        })
    );
});
//History Data
document.addEventListener('DOMContentLoaded', function () {
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    const viewHistoryButtons = document.querySelectorAll('.view-history-btn');
    const historyModalTitle = document.getElementById('historyModalTitle');
    const historyFertilizerName = document.getElementById('historyFertilizerName');
    const historyFertilizerDetails = document.getElementById('historyFertilizerDetails');
    const historyCurrentStock = document.getElementById('historyCurrentStock');
    const historyTableBody = document.getElementById('historyTableBody');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const historyMessage = document.getElementById('historyMessage');

    // ✅ Function to format date as DD-MM-YY
    function formatDateDDMMYY(dateStr) {
        const dateObj = new Date(dateStr);
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = String(dateObj.getFullYear()).slice(-2);
        return `${day}-${month}-${year}`;
    }

   viewHistoryButtons.forEach(button => {
    button.addEventListener('click', async function () {
        const fertilizerId = encodeURIComponent(this.dataset.fertilizerId);

        // Clear previous data and show loading spinner
        historyTableBody.innerHTML = '';
        historyMessage.classList.add('d-none');
        loadingSpinner.classList.remove('d-none');

        // Reset header info
        historyModalTitle.textContent = 'Fertilizer Stock History';
        historyFertilizerName.innerHTML = '<strong>Fertilizer Name:</strong> Loading...';
        historyFertilizerDetails.innerHTML = '<strong>Type:</strong> Loading... | <strong>Brand:</strong> Loading...';
        historyCurrentStock.textContent = 'Loading...';
        document.getElementById('historyTotalStock').textContent = 'Loading...';
        document.getElementById('historyTotalStockPrice').textContent = 'Loading...';
        document.getElementById('historyCurrentStockPrice').textContent = 'Loading...';

        try {
            // Fetch fertilizer details
            const fertilizerResponse = await fetch(`/api/fertilizers/${fertilizerId}`);
            const data = await fertilizerResponse.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // ✅ match with backend response structure
            const fertilizerData = data; 
          //  const historyRecords = data.history || [];

            // Fill details
            historyModalTitle.textContent = `Fertilizer Stock History for ${fertilizerData.fertilizer_name}`;
            historyFertilizerName.innerHTML = `<strong>Fertilizer Name:</strong> ${fertilizerData.fertilizer_name}`;
            historyFertilizerDetails.innerHTML = `<strong>Type:</strong> ${fertilizerData.fertilizer_type} | <strong>Brand:</strong> ${fertilizerData.brand_name}`;

            // Stock details
            document.getElementById('historyTotalStock').textContent = `${fertilizerData.total_stock} kg`;
            document.getElementById('historyTotalStockPrice').textContent = `₹${fertilizerData.total_stock_amount}`;
            historyCurrentStock.textContent = `${fertilizerData.current_stock} kg`;
            document.getElementById('historyCurrentStockPrice').textContent = `₹${fertilizerData.current_stock_amount}`;
    
             // Fetch history records
            const historyResponse = await fetch(`/api/fertilizers/${fertilizerId}/history`);
            if (!historyResponse.ok) throw new Error(`HTTP error! status: ${historyResponse.status}`);
            const historyRecords = await historyResponse.json();
            // Populate history table
            historyTableBody.innerHTML = '';
            if (historyRecords.length > 0) {
                historyRecords.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formatDateDDMMYY(record.created_at)}</td>
                        <td>${record.added_qty ?? '-'}</td>
                        <td>${record.consumed_qty ?? '-'}</td>
                        <td>${parseFloat(record.rate).toFixed(2) }</td>
                        <td>${parseFloat(record.total_price).toFixed(2)}</td>
                    `;
                    historyTableBody.appendChild(row);
                });
            } else {
                historyMessage.textContent = 'No history found for this fertilizer.';
                historyMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error fetching fertilizer history:', error);
            historyMessage.textContent = 'Failed to load history. Please try again.';
            historyMessage.classList.remove('d-none');
        } finally {
            loadingSpinner.classList.add('d-none');
            historyModal.show();
        }
    });
});

});


$(document).ready(function() {

    // Remove Bootstrap's table-responsive wrapper effect for DataTable
    $('#myDataTable').DataTable({
        dom: "<'row mb-3'<'col-sm-6'B><'col-sm-6'f>>" +  // Export left, Search right
             "rt" + 
             "<'row mt-3'<'col-sm-6'i><'col-sm-6'p>>",  // Info left, Pagination right

        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                title: 'Fertilizer Stock',
                className: 'btn btn-warning'
            }
        ],

        pageLength: 10,
        responsive: false,  // TURN OFF responsive – your table is already inside .table-responsive
        autoWidth: false
    });
});
</script>






@endsection
