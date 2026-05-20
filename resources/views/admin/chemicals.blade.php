@extends('layouts.app')

@section('title', 'Chemical Management')

@section('content')

@php
$fields = [
    'chemical_type' => 'Chemical Type',
    'brand_name' => 'Brand Name',
    'supplier_name' => 'Supplier Name',
];
@endphp

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<style>
.btn-add {
    color: white;
    background-color: #6b8e23;
    padding: 8px 14px;
    font-weight: 600;
    border-radius: 8px;
    font-size: 14px;
    border: none;
}

.modal-content {
    border-radius: 12px;
    padding: 20px;
}

table thead {
    background-color: #6b8e23;
    color: white;
}

table tbody tr:nth-child(even) {
    background-color: #f0f4e6;
}

table tfoot {
    background-color: #e9ecef;
    font-weight: bold;
}

.form-label {
    font-weight: 500;
}

.filter-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
 button.btn.btn-warning.dt-button {
    color: #fff !important;
}
.mt-4 {
    margin-top: 3.5rem !important;
}
</style>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(Auth::check() && Auth::user()->role == 1)
<div class="filter-section">
    <form method="GET" action="{{ route('chemicals.index') }}">
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
                <a href="{{ route('chemicals.index') }}" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </div>
    </form>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mt-4">
    @if(Auth::check() && Auth::user()->role != 1)
    <button class="btn-add ms-auto" data-bs-toggle="modal" data-bs-target="#addChemicalModal">
        <i class="fas fa-plus"></i> Add Chemical
    </button>
    @endif
</div>

@if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

<div class="table-responsive mt-2">
    <table class="table table-bordered text-center align-middle" id="myDataTable">
        <thead>
            <tr>
                <th>Sr.No.</th>
                @if(Auth::check() && Auth::user()->role == 1)
                <th>Location</th>
                @endif
                <th>Chemical Name</th>
                <th>Type</th>
                <th>Brand</th>
                <th>Stock</th>
                <th>UOM</th>
                <th>Purchase Date</th>
                <th>Expiry</th>
                <th>Supplier</th>
                <th>Chemical Price (Rs)</th>
                <th>Amount (Rs)</th>
                @if(Auth::check() && Auth::user()->role != 1)
                <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($paginatedChemicals as $index => $chemical)
            <tr>
                <td>{{ $index + 1 }}</td>
                @if(Auth::check() && Auth::user()->role == 1)
                <td>{{ $chemical->site_name }}</td>
                @endif
                <td>{{ $chemical->chemicalNname }}</td>
                <td>{{ $chemical->chemical_type }}</td>
                <td>{{ $chemical->brand_name }}</td>
                <td>{{ $chemical->stock_qty }}</td>
                <td>{{ $chemical->uom }}</td>
                <td>{{ \Carbon\Carbon::parse($chemical->purchase_date)->format('d-m-Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($chemical->expiry_date)->format('d-m-Y') }}</td>
                <td>{{ $chemical->supplier_name }}</td>
                <td>{{ $chemical->rate }}</td>
                <td>{{ number_format($chemical->calculated_amount, 2) }}</td>
                @if(Auth::check() && Auth::user()->role != 1)
                <td class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm" style="background-color: #FFD700; color: black;" data-bs-toggle="modal" data-bs-target="#editChemicalModal{{ $chemical->id }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStockModal{{ $chemical->id }}">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn btn-info btn-sm view-history-btn" data-chemical-id="{{ $chemical->id }}" data-bs-toggle="modal" data-bs-target="#historyModal">
                        <i class="fas fa-history"></i>
                    </button>
                    <!--<form action="{{ route('chemicals.destroy', $chemical->id) }}" method="POST">-->
                    <!--    @csrf-->
                    <!--    @method('DELETE')-->
                    <!--    <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">-->
                    <!--        <i class="fas fa-trash-alt"></i>-->
                    <!--    </button>-->
                    <!--</form>-->
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::check() && Auth::user()->role == 1 ? '13' : '12' }}">No chemicals found.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                @if(Auth::check() && Auth::user()->role == 1)
                <td colspan="5">Total</td>
                @else
                <td colspan="4">Total</td>
                @endif
                <td>{{ $paginatedChemicals->sum('stock_qty') }}</td>
                <td></td>
                <td colspan="3"></td>
                <td></td>
                <td>{{ number_format($paginatedChemicals->sum('calculated_amount'), 2) }}</td>
                @if(Auth::check() && Auth::user()->role != 1)
                <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    {{ $paginatedChemicals->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>

<!-- Add Chemical Modal -->
@if(Auth::check() && Auth::user()->role != 1)
<div class="modal fade" id="addChemicalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('chemicals.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Chemical</h5>
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
                                <option value="{{ $site->id }}">{{ $site->chemical_name }}</option>
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
                             <label class="form-label">Chemical</label>
                              <select class="form-select" name="chemical_id" id="chemical_id">
                            <option value="">Select Chemical</option>
                            @foreach($masterChemical as $chemi)
                                <option value="{{ $chemi->id }}">{{ $chemi->chemical_name }}</option>
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
                             <select class="form-select" name="UOM" id="uom" required>
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
                            <input type="number" class="form-control" name="rate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock_qty" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-add">Add Chemical</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Edit Modal -->
@if(Auth::check() && Auth::user()->role != 1)
@foreach($paginatedChemicals as $chemical)
<div class="modal fade" id="editChemicalModal{{ $chemical->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('chemicals.update', $chemical->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Chemical</h5>
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
                                <option value="{{ $site->id }}" {{ $chemical->site_id == $site->id ? 'selected' : '' }}>{{ $site->site_name }}</option>
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
                         <!--<div class="col-md-4">-->
                         <!--     <label class="form-label">Chemical</label>-->
                         <!--     <select class="form-select" name="chemical_id" id="chemical_id">-->
                         <!--       <option value="">Select Chemical</option>-->
                         <!--        @foreach($masterChemical as $chemi)-->
                         <!--           <option value="{{ $chemi->id }}" {{ $chemical->chemical_id == $chemi->id ? 'selected' : '' }}>{{ $chemi->chemical_name }}</option>-->
                         <!--       @endforeach-->
                         <!--       </select>-->
                         <!--</div>-->
                        @foreach($fields as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <input type="text" class="form-control" name="{{ $field }}" value="{{ $chemical->$field }}" required>
                        </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" value="{{ $chemical->purchase_date }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" value="{{ $chemical->expiry_date }}" required>
                        </div>
                        <!--<div class="col-md-6">-->
                        <!--    <label class="form-label">Rate (Rs)</label>-->
                        <!--    <input type="number" class="form-control" name="rate" value="{{ $chemical->rate }}" required>-->
                        <!--</div>-->
                        <!--<div class="col-md-6">-->
                        <!--    <label class="form-label">Current Stock</label>-->
                        <!--    <input type="number" class="form-control" name="stock_qty" value="{{ $chemical->stock_qty }}" readonly>-->
                        <!--    <small class="text-muted">Managed separately</small>-->
                        <!--</div>-->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Update Chemical</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal{{ $chemical->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('chemicals.addStock', $chemical->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock for {{ $chemical->chemical_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Stock </label>
                        <input type="number" class="form-control" value="{{ $chemical->stock_qty }}" readonly>
                        <small class="text-muted">This is the current stock before adding.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Added Quantity</label>
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
                        <label class="form-label">Storage Location</label>
                        <input type="text" name="storage_location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif

<!-- Chemical Stock History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historyModalTitle">Chemical Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
           <div class="modal-body">
     <div class="card p-3 mb-3">
        <div class="row text-left fw-bold">
            <div class="col-md-6 col-12">
        <h6 id="historyChemicalName"><strong>Chemical Name:</strong> Loading...</h6>
        </div>
          <div class="col-md-6 col-12">
        <p id="historyChemicalDetails">
            <strong>Type:</strong> Loading... | <strong>Brand:</strong> Loading...
        </p>
        </div>
          </div>
          

        <div class="row text-left fw-bold">
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
                <th>Consumption Stock</th>
                <th>Added Stock()</th>
                <th>Price (Rs)</th>
                <th>Total Price (Rs)</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody id="historyTableBody">
            <!-- History records will be loaded here via AJAX -->
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    const viewHistoryButtons = document.querySelectorAll('.view-history-btn');
    const historyModalTitle = document.getElementById('historyModalTitle');
    const historyChemicalName = document.getElementById('historyChemicalName');
    const historyChemicalDetails = document.getElementById('historyChemicalDetails');
    const historyCurrentStock = document.getElementById('historyCurrentStock');
    const historyTotalStock = document.getElementById('historyTotalStock');
    const historyCurrentStockPrice = document.getElementById('historyCurrentStockPrice');
    const historyTotalStockPrice = document.getElementById('historyTotalStockPrice');
    const historyTableBody = document.getElementById('historyTableBody');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const historyMessage = document.getElementById('historyMessage');

    // Function to format date as DD-MM-YY
    function formatDateDDMMYY(dateStr) {
        const dateObj = new Date(dateStr);
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = String(dateObj.getFullYear()).slice(-2);
        return `${day}-${month}-${year}`;
    }

   viewHistoryButtons.forEach(button => {
    button.addEventListener('click', async function () {
        const chemicalId = encodeURIComponent(this.dataset.chemicalId);

        // Clear previous data and show loading spinner
        historyTableBody.innerHTML = '';
        historyMessage.classList.add('d-none');
        loadingSpinner.classList.remove('d-none');

        // Reset header info
        historyModalTitle.textContent = 'Chemical Stock History';
        historyChemicalName.innerHTML = '<strong>Chemical Name:</strong> Loading...';
        historyChemicalDetails.innerHTML = '<strong>Type:</strong> Loading... | <strong>Brand:</strong> Loading...';
        historyCurrentStock.textContent = 'Loading...';

        try {
            // Fetch chemical details
            const chemicalResponse = await fetch(`/api/chemicals/${chemicalId}`);
            if (!chemicalResponse.ok) throw new Error(`HTTP error! status: ${chemicalResponse.status}`);
            const chemicalData = await chemicalResponse.json();

            // Populate chemical details
            historyModalTitle.textContent = `Chemical Stock History for ${chemicalData.chemical_name}`;
            historyChemicalName.innerHTML = `<strong>Chemical Name:</strong> ${chemicalData.chemical_name}`;
            historyChemicalDetails.innerHTML = `<strong>Type:</strong> ${chemicalData.chemical_type} | <strong>Brand:</strong> ${chemicalData.brand_name}`;
            historyCurrentStock.textContent = `${chemicalData.stock_qty}`;
            historyCurrentStockPrice.textContent = `${chemicalData.current_stock_amount}`;
            historyTotalStock.textContent = `${chemicalData.total_stock}`;
            historyTotalStockPrice.textContent = `${chemicalData.total_stock_amount}`;
            

            // Fetch history records
            const historyResponse = await fetch(`/api/chemicals/${chemicalId}/history`);
            if (!historyResponse.ok) throw new Error(`HTTP error! status: ${historyResponse.status}`);
            const historyRecords = await historyResponse.json();

            loadingSpinner.classList.add('d-none');

            if (historyRecords.length > 0) {
                historyRecords.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.date_formatted ?? 'N/A'}</td>
                        <td>${record.consumed_qty > 0 ? record.consumed_qty : '-'}</td>
                        <td>${record.added_qty > 0 ? record.added_qty : '-'}</td>
                        <td>${record.rate ?? '-'}</td>
                       <td>${record.total_price ? parseFloat(record.total_price).toFixed(2) : '-'}</td>
                        <td>${record.location ?? '-'}</td>
                    `;
                    historyTableBody.appendChild(row);
                });
            } else {
                historyMessage.textContent = 'No history found for this chemical.';
                historyMessage.classList.remove('d-none');
            }

        } catch (error) {
            console.error('Error fetching chemical history:', error);
            loadingSpinner.classList.add('d-none');
            historyMessage.textContent = 'Failed to load history. Please try again.';
            historyMessage.classList.remove('d-none');
        } finally {
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
                title: 'Chemical Stock',
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