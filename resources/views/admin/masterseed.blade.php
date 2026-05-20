@extends('layouts.app')

@section('styles')
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h5 {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0;
            font-size: 1.25rem;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #e9ecef;
            color: #fff;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem 0.75rem;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table tbody tr:hover {
            background-color: #f0f4f7;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 0.75rem;
            border-top: 1px solid #e9ecef;
        }
        .table tfoot td {
            font-weight: 700;
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }
        .action-buttons .btn {
            margin-right: 8px;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }
        .action-buttons .btn i {
            font-size: 0.9rem;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-1px);
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            transform: translateY(-1px);
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-1px);
        }
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 2rem;
            background-color: #6c757d;
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            position: relative;
        }
        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
        }
        .modal-header .modal-title i {
            margin-right: 10px;
            font-size: 1.6rem;
        }
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
            transition: opacity 0.2s ease-in-out;
        }
        .modal-header .btn-close:hover {
            opacity: 1;
        }
        .modal-body {
            padding: 2rem;
            color: #343a40;
        }
        .modal-body h6 {
            font-weight: 600;
            color: #555;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        .seed-info p {
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .seed-info strong {
            color: #212529;
        }
        .form-control-plaintext {
            padding-left: 0;
            padding-right: 0;
            color: #495057;
            font-weight: 500;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .table-striped th, .table-striped td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .loading-spinner {
            text-align: center;
            padding: 20px;
            color: #007bff;
        }
        .loading-spinner i {
            font-size: 2.5em;
            animation: spin 1.5s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #noHistoryMessage {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        .modal-header.bg-info {
            background-color: #17a2b8 !important;
        }
        .btn.shadow-sm {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .alert {
            border-radius: 8px;
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
        }
        .alert .fa-check-circle, .alert .fa-exclamation-triangle, .alert .fa-exclamation-circle {
            font-size: 1.1rem;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            @if(Auth::check() && Auth::user()->role == 1)
                <div class="col-md-2">
                    <form method="GET" action="{{ route('seeds.index') }}">
                        <select class="form-select" name="site_id" id="filterSite" onchange="this.form.submit()">
                            <option value="">Select Site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->site_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
            @if(Auth::check() && Auth::user()->role != 1)
                <button class="btn shadow-sm" data-bs-toggle="modal" data-bs-target="#addSeedModal" style="color: white !important; background-color: #6b8e23 !important; text-decoration: none !important; border: none !important;">
                    <i class="fas fa-plus-circle me-1" style="color: white !important;"></i> Add New Seed Stock
                </button>
            @endif
        </div>
        
        <div class="col-md-6">
            <form action="{{ route('seeds.search') }}" method="GET" class="float-md-end">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by Name, Variety, Location..." value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit" title="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5><i class="fas fa-exclamation-circle me-2"></i>Validation Errors:</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-border table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Seed Name</th>
                            <th>Variety</th>
                            <th>Prod. Type</th>
                            <th>Purpose</th>
                            <th>Packing Type</th>
                            <th>Packing Size(kg)</th>
                            <th>Packing Date</th>
                            <th class="text-end">Stock (Kg)</th>
                            <th class="text-end">Seed Price/Kg</th>
                            <th class="text-end">Amount (Rs)</th>
                            <th>Location</th>
                            <th>Remark</th>
                            @if(Auth::check() && Auth::user()->role != 1)
                            <th class="text-center">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalStock = 0;
                            $totalAmount = 0;
                        @endphp
                        @forelse($seeds as $index => $seed)
                            <tr>
                                <td>{{ ($seeds->currentPage() - 1) * $seeds->perPage() + $loop->iteration }}</td>
                                <td>{{ $seed->seed_name }}</td>
                                <td>{{ $seed->variety_of_seed }}</td>
                                <td>{{ $seed->production_type }}</td>
                                <td>{{ $seed->purpose ?: '-' }}</td>
                                <td>{{ $seed->type_of_packing }}</td>
                                <td>{{ $seed->packing_size }}</td>
                                <td>{{ date('d-m-Y', strtotime($seed->date_of_packing)) }}</td>
                                <td class="text-end">{{ number_format($seed->seed_stock_kg, 2) }}</td>
                                <td class="text-end">{{ number_format($seed->rate_of_seed, 2) }}</td>
                                <td class="text-end">{{ number_format($seed->calculated_amount, 2) }}</td>
                                <td>{{ $seed->location }}</td>
                                <td>{{ Str::limit($seed->remark, 30) ?: '-' }}</td>
                                @if(Auth::check() && Auth::user()->role != 1)
                                <td class="text-center action-buttons">
                                    <button class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#addStockModal" 
                                            data-id="{{ $seed->id }}" 
                                            data-seed-name="{{ $seed->seed_name }}" 
                                            data-current-stock="{{ $seed->seed_stock_kg }}"
                                            data-uom="{{ $seed->uom }}"
                                            data-rate-of-seed="{{ $seed->rate_of_seed }}"
                                            data-sale-price="{{ $seed->sale_price }}"
                                            data-location="{{ $seed->location }}"
                                            title="Add More Stock">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewSeedHistoryModal" 
                                        data-seed-id="{{ $seed->id }}" 
                                        data-seed-name="{{ $seed->seed_name }}"
                                        data-variety="{{ $seed->variety_of_seed }}"
                                        data-current-stock="{{ $seed->seed_stock_kg }}"
                                        data-current-stock-price="{{ $seed->calculated_amount }}"
                                        data-uom="{{ $seed->uom }}"
                                        title="View Stock History">
                                    <i class="fas fa-database"></i>
                                </button>
                                    
                                </td>
                                @endif
                                @php
                                    $totalStock += $seed->seed_stock_kg;
                                    $totalAmount += $seed->calculated_amount;
                                @endphp
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No seed stock found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8" class="text-center"><strong>Total:</strong></td>
                            <td colspan="1" class="text-end">{{ number_format($totalStock, 2) }}</td>
                           
                            <td colspan="2" class="text-end">{{ number_format($totalAmount, 2) }}</td>
                            <!--<td colspan="{{ Auth::check() && Auth::user()->role != 1 ? 3 : 2 }}"></td>-->
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @if ($seeds->hasPages())
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-center">
                {{ $seeds->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addSeedModal" tabindex="-1" aria-labelledby="addSeedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header text-white" style="background-color: #6b8e23">
                <h5 class="modal-title" id="addSeedModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Seed Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('seeds.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Fields marked with <span class="text-danger">*</span> are required.</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="add_site_id" class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="site_id" id="add_site_id" class="form-select" required>
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->site_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_seed_name" class="form-label">Seed Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_seed_name" name="seed_name" placeholder="Enter Seed Name" required value="{{ old('seed_name') }}">
                            @error('seed_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_variety_of_seed" class="form-label">Variety of Seed <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_variety_of_seed" name="variety_of_seed" placeholder="Enter Variety" required value="{{ old('variety_of_seed') }}">
                            @error('variety_of_seed')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_production_type" class="form-label">Production Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_production_type" name="production_type" required>
                                <option value="" disabled selected>Select Production Type</option>
                                <option value="Own Production" {{ old('production_type') == 'Own Production' ? 'selected' : '' }}>Own Production</option>
                                <option value="Purchase" {{ old('production_type') == 'Purchase' ? 'selected' : '' }}>Purchase</option>
                            </select>
                            @error('production_type')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="add_purpose" class="form-label">Purpose</label>
                            <select class="form-select" id="add_purpose" name="purpose">
                                <option value="" selected>Select Purpose (Optional)</option>
                                <option value="Fodder Crop" {{ old('purpose') == 'Fodder Crop' ? 'selected' : '' }}>Fodder Crop</option>
                                <option value="Cash Crop" {{ old('purpose') == 'Cash Crop' ? 'selected' : '' }}>Cash Crop</option>
                                <option value="Seed Crop" {{ old('purpose') == 'Seed Crop' ? 'selected' : '' }}>Seed Crop</option>
                            </select>
                            @error('purpose')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_type_of_packing" class="form-label">Type of Packing <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_type_of_packing" name="type_of_packing" placeholder="e.g., Bag, Pouch" required value="{{ old('type_of_packing') }}">
                            @error('type_of_packing')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_packing_size" class="form-label">Packing Size <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_packing_size" name="packing_size" placeholder="e.g., 5kg, 250gm" required value="{{ old('packing_size') }}">
                            @error('packing_size')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="add_date_of_packing" class="form-label">Date of Packing <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="add_date_of_packing" name="date_of_packing" required value="{{ old('date_of_packing', date('Y-m-d')) }}">
                            @error('date_of_packing')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="add_seed_stock_kg" class="form-label">Seed Stock (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="add_seed_stock_kg" name="seed_stock_kg" placeholder="Enter Stock in kg" required value="{{ old('seed_stock_kg') }}">
                            @error('seed_stock_kg')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="add_rate_of_seed" class="form-label">Seed Price/Kg<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="add_rate_of_seed" name="rate_of_seed" placeholder="Enter Seed Price/Kg" required value="{{ old('rate_of_seed') }}">
                            @error('rate_of_seed')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="add_location" class="form-label">Store Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_location" name="location" placeholder="e.g., Warehouse A, Rack 5" required value="{{ old('location') }}">
                            @error('location')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_uom" class="form-label">Unit of Measurement (UOM) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_uom" name="uom" value="Kg" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_sowing_method" class="form-label">Sowing Method</label>
                            <input type="text" class="form-control" id="add_sowing_method" name="sowing_method" placeholder="e.g., Drilling (Optional)" value="{{ old('sowing_method') }}">
                            @error('sowing_method')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="add_remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="add_remark" name="remark" rows="2" placeholder="Any additional notes (Optional)">{{ old('remark') }}</textarea>
                            @error('remark')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_sowing_source" class="form-label">Sowing Source</label>
                            <input type="text" class="form-control" id="add_sowing_source" name="sowing_source" placeholder="Source of sowing (Optional)" value="{{ old('sowing_source') }}">
                            @error('sowing_source')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Add Seed Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSeedModal" tabindex="-1" aria-labelledby="editSeedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSeedModalLabel"><i class="fas fa-edit me-2"></i>Edit Seed Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSeedForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="text-muted small mb-3">Fields marked with <span class="text-danger">*</span> are required.</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_site_id" class="form-label">Site <span class="text-danger">*</span></label>
                            <select name="site_id" id="edit_site_id" class="form-select" required>
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                            @error('site_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_seed_name" class="form-label">Seed Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_seed_name" name="seed_name" required>
                            @error('seed_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_variety_of_seed" class="form-label">Variety of Seed <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_variety_of_seed" name="variety_of_seed" required>
                            @error('variety_of_seed')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_production_type" class="form-label">Production Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_production_type" name="production_type" required>
                                <option value="Own Production">Own Production</option>
                                <option value="Purchase">Purchase</option>
                            </select>
                            @error('production_type')
                                <div class="text-danger small">{{ $message }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_purpose" class="form-label">Purpose</label>
                            <select class="form-select" id="edit_purpose" name="purpose">
                                <option value="">Select Purpose</option>
                                <option value="Fodder Crop">Fodder Crop</option>
                                <option value="Cash Crop">Cash Crop</option>
                                <option value="Seed Crop">Seed Crop</option>
                            </select>
                            @error('purpose')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_type_of_packing" class="form-label">Type of Packing <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_type_of_packing" name="type_of_packing" required>
                            @error('type_of_packing')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_packing_size" class="form-label">Packing Size <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_packing_size" name="packing_size" required>
                            @error('packing_size')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="edit_date_of_packing" class="form-label">Date of Packing <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_date_of_packing" name="date_of_packing" required>
                            @error('date_of_packing')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_seed_stock_kg" class="form-label">Seed Stock (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_seed_stock_kg" name="seed_stock_kg" required>
                            @error('seed_stock_kg')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_rate_of_seed" class="form-label">Base Rate (per UOM) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_rate_of_seed" name="rate_of_seed" required>
                            @error('rate_of_seed')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_sale_price" class="form-label">Sale Price (per UOM) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_sale_price" name="sale_price" required>
                            @error('sale_price')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_location" name="location" required>
                            @error('location')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_uom" class="form-label">Unit of Measurement (UOM) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_uom" name="uom" required>
                            @error('uom')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_sowing_method" class="form-label">Sowing Method</label>
                            <input type="text" class="form-control" id="edit_sowing_method" name="sowing_method">
                            @error('sowing_method')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="edit_remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="edit_remark" name="remark" rows="2"></textarea>
                            @error('remark')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_sowing_source" class="form-label">Sowing Source</label>
                            <input type="text" class="form-control" id="edit_sowing_source" name="sowing_source">
                            @error('sowing_source')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Seed Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addStockModalLabel"><i class="fas fa-plus me-2"></i>Add Stock to Seed</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStockForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Enter the quantity to add to the existing stock.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seed Name:</label>
                        <p id="add_stock_seed_name" class="form-control-plaintext"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Stock:</label>
                        <p id="add_stock_current_stock" class="form-control-plaintext"></p>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_to_add" class="form-label">Add New Seed Stock (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="quantity_to_add" name="quantity_to_add" placeholder="Enter quantity to add" required value="{{ old('quantity_to_add') }}">
                        @error('quantity_to_add')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new_rate_of_seed" class="form-label">Add New Seed Price/Kg  <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="new_rate_of_seed" name="new_rate_of_seed" placeholder="Enter new rate" required value="{{ old('new_rate_of_seed') }}">
                        @error('new_rate_of_seed')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewSeedHistoryModal" tabindex="-1" aria-labelledby="viewSeedHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewSeedHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Seed Stock History for <span id="modalSeedName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="seed-info mb-4 p-3 bg-light rounded shadow-sm-light">
                    <h6 class="text-primary mb-3">Current Seed Information ðŸŒ±</h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <p class="mb-0"><strong>Seed Name:</strong> <span id="modalSeedNameDetail" class="fw-normal"></span></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="mb-0"><strong>Variety:</strong> <span id="modalVariety" class="fw-normal"></span></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="mb-0"><strong>Current Stock:</strong> <span id="modalCurrentStock" class="fw-normal text-success fs-5"></span> <span id="modalUOM" class="fw-normal text-success fs-5"></span></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="mb-0"><strong>Current Stock Price:</strong> <span id="modalCurrentStockPrice" class="fw-normal"></span></p>
                        </div>
                    </div>
                </div>

                <h6 class="text-primary mb-3">Stock History ðŸ“ˆ</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Stock Type</th>
                                <th class="text-end">Consumed Seed Stock (kg)</th>
                                <th class="text-end">Added Stock (kg)</th>
                                <th class="text-end">Price/Kg</th>
                                <th class="text-end">Total Price (kg)</th>
                                
                                
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="noHistoryMessage" class="text-muted text-center mt-3" style="display: none;">
                    <i class="fas fa-box-open me-2"></i>No historical entries found for this seed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
    // Check if jQuery is loaded
    if (typeof jQuery == 'undefined') {
        console.error('jQuery failed to load. Please check your internet connection or CDN.');
        document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"><\/script>');
    } else {
        console.log('jQuery loaded successfully.');
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<script>
    $('#viewSeedHistoryModal').on('show.bs.modal', function (event) {
        console.log('Modal opened for seed_id:', $(event.relatedTarget).data('seed-id')); // Debug log
        var button = $(event.relatedTarget);
        var seedId = button.data('seed-id');
        var seedName = button.data('seed-name');
        var variety = button.data('variety');
        var currentStock = button.data('current-stock');
        var currentStockPrice = button.data('current-stock-price');
        var uom = button.data('uom');
       
        var modal = $(this);
        modal.find('#modalSeedName').text(seedName);
        modal.find('#modalSeedNameDetail').text(seedName);
        modal.find('#modalVariety').text(variety);
        modal.find('#modalCurrentStock').text(currentStock);
        modal.find('#modalCurrentStockPrice').text(parseFloat(currentStockPrice).toFixed(2));
        modal.find('#modalUOM').text(uom);

        // Show loading spinner
        $('#historyTableBody').html('<tr><td colspan="10" class="text-center py-4"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading history...</div></td></tr>');
        $('#noHistoryMessage').hide();

        $.ajax({
            url: '{{ url('/get-seed-history') }}/' + seedId,
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                var tableBody = $('#historyTableBody');
                
                tableBody.empty(); // Clear loading spinner or previous data

                if (response.success && response.history.length > 0) {
                    var history = response.history;
                    history.forEach(function(item) {
                        
                        let totalCost = '0.00';
                        if (item.type === 'Add Stock' && item.type !== null) {
                            totalCost = (parseFloat(item.add_new_seed_stock) * parseFloat(item.rate_of_seed)).toFixed(2);
                        } else if (item.type === 'Consumption' && item.type !== null) {
                            totalCost = (parseFloat(item.consumed_stock) * parseFloat(item.rate_of_seed)).toFixed(2);
                        }
                        let rate = '0.00';
                        if (item.type === 'Add Stock') {
                            rate = parseFloat(item.add_new_seed_rate).toFixed(2);
                        } else if (item.type === 'Consumption') {
                            rate = parseFloat(item.rate_of_seed).toFixed(2);
                        }
                        console.log('rate:'+rate)
                        tableBody.append(`
                            <tr>
                                <td style="white-space: nowrap;">${item.date_of_packing ||'N/A'}</td>
                                <td>${item.type || 'N/A'}</td>
                                <td class="text-end">${item.consumed_stock !== null ? parseFloat(item.consumed_stock).toFixed(2) : 'N/A'}</td>
                                <td class="text-end">${item.add_new_seed_stock !== null ? parseFloat(item.add_new_seed_stock).toFixed(2) : 'N/A'}</td>
                                <td class="text-end">${rate}</td>
                                <td class="text-end">${totalCost !== null ? parseFloat(totalCost).toFixed(2) : 'N/A'}</td>
                            </tr>
                        `);
                    });
                    $('#noHistoryMessage').hide(); 
                } else {
                    $('#noHistoryMessage').show(); 
                    tableBody.append('<tr><td colspan="9" class="text-center">No history available</td></tr>'); // Also add to table body for completeness
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error, 'Status:', status, 'XHR:', xhr);
                $('#historyTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error loading history. Please try again later.</td></tr>');
                $('#noHistoryMessage').hide(); 
            }
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var addStockModal = document.getElementById('addStockModal');
    addStockModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var seedId = button.getAttribute('data-id');
        var seedName = button.getAttribute('data-seed-name');
        var currentStock = button.getAttribute('data-current-stock');
        var uom = button.getAttribute('data-uom');
        var rateOfSeed = button.getAttribute('data-rate-of-seed');
        var salePrice = button.getAttribute('data-sale-price');
        var location = button.getAttribute('data-location');

        // Set the form action dynamically
        var form = document.getElementById('addStockForm');
        form.action = '/seeds/' + seedId + '/add-stock';

        // Populate the modal fields
        document.getElementById('add_stock_seed_name').textContent = seedName;
        document.getElementById('add_stock_current_stock').textContent = currentStock + ' ' + uom;
 
        document.getElementById('new_rate_of_seed').value = rateOfSeed;
        document.getElementById('new_sale_price').value = salePrice;
        document.getElementById('new_location').value = location;
    });

    // Reset form on modal close
    addStockModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('addStockForm').reset();
        document.getElementById('addStockForm').action = '';
    });
});
</script>
@endsection