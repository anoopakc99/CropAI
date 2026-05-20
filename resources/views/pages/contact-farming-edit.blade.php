@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary-color: #2E7D32;
        --secondary-color: #4CAF50;
        --accent-color: #81C784;
        --background-light: #F1F8E9;
        --text-dark: #1B5E20;
    }

    body {
        background: linear-gradient(135deg, #E8F5E8 0%, #F1F8E9 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

    .main-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 1200px;
    }

    .header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 5px;
        text-align: center;
        position: relative;
    }

    .header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" patternUnits="userSpaceOnUse" width="100" height="100"><circle cx="25" cy="25" r="2" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="2" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1.5" fill="white" opacity="0.08"/><circle cx="10" cy="60" r="1.5" fill="white" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    }

    .header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }

    .header .subtitle {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    .section-header {
        background: linear-gradient(90deg, #667e06, transparent);
        padding: 8px 15px;
        margin: 20px -15px 15px 0px;
        border-radius: 6px;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        border-left: 3px solid var(--primary-color);
    }

    .section-header i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }

    /* .row > * {
        margin-bottom: 0.8rem;
    } */

    .mb-3 {
        margin-bottom: 0.8rem !important;
    }

    .form-control, .form-select {
        border: 1px solid #E0E0E0;
        border-radius: 4px;
        padding: 6px 10px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: #FAFAFA;
        height: auto;
    }

    .input-group {
        max-width: 100%;
    }

    .input-group-text {
        padding: 6px 10px;
        font-size: 0.9rem;
        background-color: #f8f9fa;
        border: 1px solid #E0E0E0;
    }

    .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--secondary-color);
        background: white;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
        background-color: #fff5f5;
    }

    .form-control.is-valid, .form-select.is-valid {
        border-color: #28a745;
        background-color: #f8fff8;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
    }

    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .required {
        color: #D32F2F;
    }

    .input-group-text {
        background: var(--accent-color);
        border: 2px solid var(--accent-color);
        color: white;
        font-weight: 600;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
    }

    .btn-secondary {
        background: #6C757D;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
    }

    .auto-calc {
        background: linear-gradient(45deg, #FFF9C4, #F0F4C3) !important;
        border-color: #CDDC39 !important;
        position: relative;
        font-weight: 600;
        color: #333;
    }

    .calculation-info {
        font-size: 0.8rem;
        color: #666;
        margin-top: 4px;
        font-style: italic;
    }

    .info-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .loading-spinner {
        display: none;
        margin-left: 10px;
        color: var(--secondary-color);
    }

    .tooltip-icon {
        color: var(--secondary-color);
        margin-left: 5px;
        cursor: help;
    }

    .highlight-calculation {
        border: 2px solid #ff9800 !important;
        background: linear-gradient(45deg, #fff3e0, #ffe0b2) !important;
        animation: pulse-calc 2s infinite;
    }

    @keyframes pulse-calc {
        0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255, 152, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
    }

    .block-checkbox-list, .plots-checkbox-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .plot-block-group {
        background: var(--background-light);
        border-radius: 6px;
        padding: 10px;
    }

    .plot-block-group h6 {
        color: var(--primary-color);
        border-bottom: 1px solid var(--accent-color);
        padding-bottom: 5px;
    }

    .plots-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .plot-check-item {
        flex: 0 0 auto;
        min-width: 100px;
        /* margin-right: 10px; */
    }
      .file-upload-area {
            border: 2px dashed var(--accent-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: var(--background-light);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            background: white;
            border-color: var(--secondary-color);
        }

        .file-upload-area i {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        .header a.btn {
    z-index: 10;
    position: relative;
}
</style>
<div class="container-fluid">
    <div class="main-containerr" style="margin-top: 10px;">

        <div class="header">
             <a class="btn btn-success" href="{{ route('contact-farming.index') }}">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h4> Edit Contract Farming</h4>
            <p class="subtitle">Crop-AI Management System</p>
        </div>

        <div class="form-container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ isset($record) ? route('contact-farming.update', $record->id) : route('contact-farming.store') }}" enctype="multipart/form-data" class="needs-validation">
                @csrf
                @if(isset($record))
                    @method('PUT')
                @endif
                <div class="card">
                    <div class="card-body">
                         <div class="section-header">
                                        <i class="fas fa-handshake"></i> Contract Details
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Contractor Name <span class="required">*</span></label>
                                              <input type="text"
                                                  name="contractor_name"
                                                  class="form-control @error('contractor_name') is-invalid @enderror"
                                                  placeholder="Enter contractor name"
                                                  required
                                                  value="{{ old('contractor_name', $formData['contractor_name'] ?? '') }}">
                                            @error('contractor_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date <span class="required">*</span></label>
                                            <input type="date"
                                                   name="start_date"
                                                   class="form-control @error('start_date') is-invalid @enderror"
                                                   required
                                                   value="{{ old('start_date', $formData['start_date'] ?? '') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date <span class="required">*</span></label>
                                            <input type="date"
                                                   name="end_date"
                                                   class="form-control @error('end_date') is-invalid @enderror"
                                                   required
                                                   value="{{ old('end_date', $formData['end_date'] ?? '') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>

                        <!-- Basic Information Section -->
                        <div class="section-header">
                            <i class="fas fa-map-marker-alt"></i> Basic Crop & Plot Details
                        </div>

                        <!-- Blocks and Plots Row -->
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="form-label">Select Blocks <span class="required">*</span></label>
                                <div class="block-checkbox-list border rounded p-3">
                                    @if(isset($blocks))
                                        @foreach($blocks as $block)
                                            <div class="form-check mb-2">
                                                <input type="checkbox"
                                                       name="blocks[]"
                                                       class="form-check-input block-checkbox"
                                                       value="{{ $block->id }}"
                                                       id="block_{{ $block->id }}"
                                                       {{ in_array($block->id, old('blocks', $selectedBlocks ?? [])) ? 'checked' : '' }}
                                                       onchange="updatePlots()">
                                                <label class="form-check-label" for="block_{{ $block->id }}">
                                                    {{ $block->block_name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                @error('blocks')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="loading-spinner" id="blockLoadingSpinner">
                                    <i class="fas fa-spinner fa-spin"></i> Loading plots...
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Select Plots <span class="required">*</span></label>
                                <div id="plotsContainer" class="plots-checkbox-list border rounded p-3">
                                    <div class="text-muted">Select blocks to see available plots</div>
                                </div>
                                @error('plots')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                             <div class="col-md-2">
                                <label class="form-label">Area (Acre)
                                    <i class="fas fa-info-circle tooltip-icon" title="Auto-calculated from selected plots"></i>
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                           name="area"
                                           id="area_input"
                                           class="form-control auto-calc"
                                           value="{{ old('area', $formData['area'] ?? '0.00') }}"
                                           >
                                    <span class="input-group-text">Acre</span>
                                </div>
                            </div>
                        </div>
                            <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Seed Name <span class="required">*</span></label>
                                        <input type="text" name="seed_name" class="form-control" value="{{ old('seed_name', $formData['seed_name'] ?? '') }}" required>
                                        @error('seed_name.0')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                        <!-- Production and Sale Details Section -->
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-industry"></i> Variety, Production & Sale Details
                            </div>
                            <button type="button" class="btn btn-sm text-white" style="background-color: #2e7d32" onclick="addNewVarietyRow()">
                                <i class="fas fa-plus"></i> Add Variety
                            </button>
                        </div>

                        <div id="variety-container">
                            @if(isset($formVarieties) && count($formVarieties) > 0)
                                @foreach($formVarieties as $vIndex => $v)
                                    <div class="variety-row border border-success rounded p-3 mb-3" data-variety-index="{{ $vIndex }}">
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Seed Variety <span class="required">*</span></label>
                                                <input type="text" name="variety[]" class="form-control" value="{{ old('variety.' . $vIndex, $v['seed_variety']) }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Production Quantity (MT) <span class="required">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" name="production[]" class="form-control production-qty" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('production.' . $vIndex, $v['production']) }}">
                                                    <span class="input-group-text">MT</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Production Price/MT (Rs) <span class="required">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" name="production_price[]" class="form-control production-price" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('production_price.' . $vIndex, $v['production_price']) }}">
                                                    <span class="input-group-text">MT</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Production Amount (Rs)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" name="product_amount[]" class="form-control auto-calc product-amount" readonly value="{{ old('product_amount.' . $vIndex, $v['product_amount']) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Handling Loss (MT)</label>
                                                <div class="input-group">
                                                    <input type="number" name="handling_loss[]" class="form-control handling-loss" step="0.01" placeholder="0.00" onchange="calculateRowAmounts(this)" value="{{ old('handling_loss.' . $vIndex, $v['handling_loss']) }}">
                                                    <span class="input-group-text">MT</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Amount Recovery (Rs)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" name="amount_recovery[]" class="form-control amount-recovery" step="0.01" placeholder="0.00" onchange="calculateRowAmounts(this)" value="{{ old('amount_recovery.' . $vIndex, $v['amount_recovery']) }}">
                                                </div>
                                                <small class="text-muted">Electricity, water, etc.</small>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Sale Quantity by Production (MT)</label>
                                                <div class="input-group">
                                                    <input type="number" name="quantity_for_sale[]" class="form-control auto-calc" step="0.01" readonly value="{{ old('quantity_for_sale.' . $vIndex, $v['quantity_for_sale']) }}">
                                                    <span class="input-group-text">MT</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Remaining Sale Quantity (MT)</label>
                                                <div class="input-group">
                                                    <input type="number" name="remaining_sale_quantity[]" class="form-control auto-calc remaining-sale-quantity" step="0.01" readonly value="{{ old('remaining_sale_quantity.' . $vIndex, $v['remaining_sale_quantity']) }}">
                                                    <span class="input-group-text">MT</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sale-details-container">
                                            <div class="section-header d-flex justify-content-between align-items-center mt-3 mb-3">
                                                <div style="font-size: 0.9rem;"><i class="fas fa-shopping-cart"></i> Sale Entries</div>
                                                <button type="button" class="btn btn-sm text-white" style="background-color: #067e66" onclick="addNewSaleRow(this)">
                                                    <i class="fas fa-plus"></i> Add Sale
                                                </button>
                                            </div>
                                            <div class="sale-entry-wrapper">
                                                @php
                                                    $sales = $v['sales'] ?? ['quantity_sold_actual'=>[], 'sale_price'=>[], 'total_sale_amount'=>[], 'sale_part_to'=>[], 'sale_date'=>[]];
                                                    $saleCount = count($sales['quantity_sold_actual']);
                                                @endphp
                                                @if($saleCount > 0)
                                                    @for($si=0;$si<$saleCount;$si++)
                                                        <div class="sale-entry border border-info rounded p-3 mb-2 position-relative">
                                                            <div class="row mb-3">
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Sale Quantity (MT) <span class="required">*</span></label>
                                                                    <div class="input-group">
                                                                        <input type="hidden" name="sales[{{ $vIndex }}][sale_id][]" value="{{ $sales['sale_id'][$si] ?? '' }}">
                                                                        <input type="number" class="form-control quantity-sold-actual" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('sales.' . $vIndex . '.quantity_sold_actual.' . $si, $sales['quantity_sold_actual'][$si]) }}">
                                                                        <span class="input-group-text">MT</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-1 d-flex align-items-end">
                                                                    <div>
                                                                        <label class="form-label">Net Sold</label>
                                                                        <div class="input-group">
                                                                            <input type="text" readonly class="form-control net-sold-display" name="sales[{{ $vIndex }}][quantity_sold_actual][]" value="{{ old('sales.' . $vIndex . '.quantity_sold_actual.' . $si, $sales['quantity_sold_actual'][$si]) }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label">Sale Price/MT (Rs) <span class="required">*</span></label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">₹</span>
                                                                        <input type="number" name="sales[{{ $vIndex }}][sale_price][]" class="form-control sale-price" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('sales.' . $vIndex . '.sale_price.' . $si, $sales['sale_price'][$si]) }}">
                                                                        <span class="input-group-text">MT</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Total Sale Amount (Rs)</label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">₹</span>
                                                                        <input type="number" name="sales[{{ $vIndex }}][total_sale_amount][]" class="form-control auto-calc total-sale-amount" readonly value="{{ old('sales.' . $vIndex . '.total_sale_amount.' . $si, $sales['total_sale_amount'][$si]) }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                     <label class="form-label">Buyer Name <span class="required">*</span></label>
                                                                    <div class="input-group">

                                                                    <input type="text" name="sales[{{ $vIndex }}][sale_part_to][]" class="form-control" required value="{{ old('sales.' . $vIndex . '.sale_part_to.' . $si, $sales['sale_part_to'][$si]) }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Sale Date <span class="required">*</span></label>
                                                                    <div class="input-group">

                                                                    <input type="date" name="sales[{{ $vIndex }}][sale_date][]" class="form-control" required value="{{ old('sales.' . $vIndex . '.sale_date.' . $si, $sales['sale_date'][$si]) }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn btn-danger btn-sm position-absolute delete-sale-entry" style="right: 10px; top: 10px;" onclick="removeSaleEntry(this)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        @if(!in_array(strtolower($v['seed_variety']), ['hay','silage']))
                                                        @php
                                                            $parentSaleIdForCheck = $sales['sale_id'][$si] ?? null;
                                                            $hasDbReverse = $parentSaleIdForCheck ? \DB::table('contact_farming_sales')->where('parent_sale_id', $parentSaleIdForCheck)->where('is_reverse',1)->exists() : false;
                                                        @endphp
                                                        @if(empty($sales['is_reversed'][$si]) && !$hasDbReverse)
                                                            <div class="reverse-entry border border-warning rounded p-3 mb-2 position-relative">
                                                                <div class="row mb-3">
                                                                    <h6>Reverse Undersize Quantiy (MT)</h6>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Return Qty (MT)</label>
                                                                        <div class="input-group">
                                                                            <input type="hidden" name="sales[{{ $vIndex }}][parent_sale_id][]" value="{{ $sales['sale_id'][$si] ?? '' }}">
                                                                            <input type="number" name="sales[{{ $vIndex }}][quantity_sold_actual][]" class="form-control quantity-sold-actual reverse-quantity" step="0.01" onchange="calculateRowAmounts(this)" value="">
                                                                            <span class="input-group-text">MT</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Sale Price/MT (Rs)</label>
                                                                        <div class="input-group">
                                                                            <span class="input-group-text">₹</span>
                                                                            <input type="number" name="sales[{{ $vIndex }}][sale_price][]" class="form-control sale-price" step="0.01" onchange="calculateRowAmounts(this)" value="">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Sale Amount (Rs)</label>
                                                                        <div class="input-group">
                                                                            <span class="input-group-text">₹</span>
                                                                            <input type="number" name="sales[{{ $vIndex }}][total_sale_amount][]" class="form-control auto-calc total-sale-amount" readonly value="">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Buyer Name</label>
                                                                        <div class="input-group">
                                                                            <input type="text" name="sales[{{ $vIndex }}][sale_part_to][]" class="form-control" value="">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <label class="form-label small">Sale Date</label>
                                                                        <div class="input-group">
                                                                            <input type="date" name="sales[{{ $vIndex }}][sale_date][]" class="form-control sale-date" value="">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="sales[{{ $vIndex }}][is_reverse][]" value="1">
                                                            </div>
                                                        @endif
                                                        @endif
                                                    @endfor
                                                @else
                                                    <div class="sale-entry border border-info rounded p-3 mb-2 position-relative">
                                                        <div class="row mb-3">
                                                            <div class="col-md-3">
                                                                <label class="form-label">Sale Quantity (MT) <span class="required">*</span></label>
                                                                <div class="input-group">
                                                                    <input type="hidden" name="sales[{{ $vIndex }}][sale_id][]" value="">
                                                                    <input type="number" name="sales[{{ $vIndex }}][quantity_sold_actual][]" class="form-control quantity-sold-actual" step="0.01" required onchange="calculateRowAmounts(this)" value="0.00">
                                                                    <span class="input-group-text">MT</span>
                                                                </div>
                                                            </div>
                                                                <div class="col-md-1 d-flex align-items-end">
                                                                    <div>
                                                                        <label class="form-label">Net Sold</label>
                                                                        <div class="input-group">
                                                                            <input type="text" readonly class="form-control net-sold-display" value="0.00">
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Sale Price/MT (Rs) <span class="required">*</span></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number" name="sales[{{ $vIndex }}][sale_price][]" class="form-control sale-price" step="0.01" required onchange="calculateRowAmounts(this)" value="0.00">
                                                                    <span class="input-group-text">MT</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label">Total Sale Amount (Rs)</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number" name="sales[{{ $vIndex }}][total_sale_amount][]" class="form-control auto-calc total-sale-amount" readonly value="0.00">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label">Buyer Name <span class="required">*</span></label>
                                                                <input type="text" name="sales[{{ $vIndex }}][sale_part_to][]" class="form-control" required value="">
                                                            </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label small">Sale Date</label>
                                                                    <input type="date" name="sales[{{ $vIndex }}][sale_date][]" class="form-control sale-date" value="">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-danger btn-sm position-absolute delete-sale-entry" style="right: 10px; top: 10px;" onclick="removeSaleEntry(this)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div class="reverse-entry border border-warning rounded p-3 mb-2 position-relative">
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Reverse Undersize Quantity (MT)</label>
            <div class="input-group">
                <input type="hidden" name="sales[{{ $vIndex }}][parent_sale_id][]" value="">
                <input type="number" name="sales[{{ $vIndex }}][quantity_sold_actual][]" class="form-control quantity-sold-actual reverse-quantity" step="0.01" onchange="calculateRowAmounts(this)" value="">
                <span class="input-group-text">MT</span>
            </div>
        </div>

        <div class="col-md-2">
            <label class="form-label">Sale Price/MT (Rs)</label>
            <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="sales[{{ $vIndex }}][sale_price][]" class="form-control sale-price" step="0.01" onchange="calculateRowAmounts(this)" value="">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Sale Amount (Rs)</label>
            <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="sales[{{ $vIndex }}][total_sale_amount][]" class="form-control auto-calc total-sale-amount" readonly value="">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Buyer Name</label>
             <div class="input-group">
            <input type="text" name="sales[{{ $vIndex }}][sale_part_to][]" class="form-control" value="">
             </div>
        </div>
            <div class="col-md-2">
                <label class="form-label small">Sale Date</label>
                 <div class="input-group">
                <input type="date" name="sales[{{ $vIndex }}][sale_date][]" class="form-control sale-date" value="">
            </div>
        </div>
    </div>
    <input type="hidden" name="sales[{{ $vIndex }}][is_reverse][]" value="1">
</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="variety-row border border-success rounded p-3 mb-3">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Seed Variety <span class="required">*</span></label>
                                            <input type="text" name="variety[]" class="form-control" value="{{ old('variety.0') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Production Quantity (MT) <span class="required">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="production[]" class="form-control production-qty" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('production.0') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Production Price/MT (Rs) <span class="required">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" name="production_price[]" class="form-control production-price" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('production_price.0') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Production Amount (Rs)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" name="product_amount[]" class="form-control auto-calc product-amount" readonly value="{{ old('product_amount.0') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Handling Loss (MT)</label>
                                            <div class="input-group">
                                                <input type="number" name="handling_loss[]" class="form-control handling-loss" step="0.01" placeholder="0.00" onchange="calculateRowAmounts(this)" value="{{ old('handling_loss.0') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Amount Recovery (Rs)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" name="amount_recovery[]" class="form-control amount-recovery" step="0.01" placeholder="0.00" onchange="calculateRowAmounts(this)" value="{{ old('amount_recovery.0') }}">
                                            </div>
                                            <small class="text-muted">Electricity, water, etc.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Sale Quantity by Production (MT)</label>
                                            <div class="input-group">
                                                <input type="number" name="quantity_for_sale[]" class="form-control auto-calc" step="0.01" readonly value="{{ old('quantity_for_sale.0') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Remaining Sale Quantity (MT)</label>
                                            <div class="input-group">
                                                <input type="number" name="remaining_sale_quantity[]" class="form-control auto-calc remaining-sale-quantity" step="0.01" readonly value="{{ old('remaining_sale_quantity.0') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sale-details-container">
                                        <div class="section-header d-flex justify-content-between align-items-center mt-3 mb-3">
                                            <div style="font-size: 0.9rem;"><i class="fas fa-shopping-cart"></i> Sale Entries</div>
                                            <button type="button" class="btn btn-sm text-white" style="background-color: #067e66" onclick="addNewSaleRow(this)">
                                                <i class="fas fa-plus"></i> Add Sale
                                            </button>
                                        </div>
                                        <div class="sale-entry-wrapper">
                                            <div class="sale-entry border border-info rounded p-3 mb-2 position-relative">
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sale Quantity (MT) <span class="required">*</span></label>
                                                        <div class="input-group">
                                                            <input type="number"  class="form-control quantity-sold-actual" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('sales.0.quantity_sold_actual.0') }}">
                                                            <span class="input-group-text">MT</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-end">
                                                        <di
                                                        v>
                                                            <label class="form-label">Net Sold</label>
                                                            <div class="input-group">
                                                                <input type="text" readonly class="form-control net-sold-display" name="sales[0][quantity_sold_actual][]" value="{{ old('sales.0.quantity_sold_actual.0') }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sale Price/MT (Rs) <span class="required">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number" name="sales[0][sale_price][]" class="form-control sale-price" step="0.01" required onchange="calculateRowAmounts(this)" value="{{ old('sales.0.sale_price.0') }}">
                                                            <span class="input-group-text">MT</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Total Sale Amount (Rs)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number" name="sales[0][total_sale_amount][]" class="form-control auto-calc total-sale-amount" readonly value="{{ old('sales.0.total_sale_amount.0') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Buyer Name <span class="required">*</span></label>
                                                        <input type="text" name="sales[0][sale_part_to][]" class="form-control" required value="{{ old('sales.0.sale_part_to.0') }}">
                                                        <div class="mt-2">
                                                            <label class="form-label small">Sale Date</label>
                                                            <input type="date" name="sales[0][sale_date][]" class="form-control sale-date" value="{{ old('sales.0.sale_date.0') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-danger btn-sm position-absolute delete-sale-entry" style="right: 10px; top: 10px;" onclick="removeSaleEntry(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                         <!-- Calculation Summary -->
                                    <div class="section-header">
                                        <i class="fas fa-chart-line"></i> Financial Summary
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="row text-center">
                                                        <div class="col-md-3">
                                                            <div class="border rounded p-3">
                                                                <div class="text-muted small">Production Cost (Rs)</div>
                                                                <div class="fw-bold text-primary fs-5" id="production_cost_display">₹0.00</div>
                                                                <small class="text-muted">Qty × Price</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="border rounded p-3">
                                                                <div class="text-muted small">Recovery Amount (Rs)</div>
                                                                <div class="fw-bold text-warning fs-5" id="recovery_display">₹0.00</div>
                                                                <small class="text-muted">Additional costs</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="border rounded p-3">
                                                                <div class="text-muted small">Sale Revenue (Rs)</div>
                                                                <div class="fw-bold text-success fs-5" id="sale_revenue_display">₹0.00</div>
                                                                <small class="text-muted">After loss deduction</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="border rounded p-3" id="profit_container">
                                                                <div class="text-muted small">Net Profit/Loss (Rs)</div>
                                                                <div class="fw-bold fs-4" id="profit_display">₹0.00</div>
                                                                <small class="text-muted">Revenue - Cost + Recovery</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        <!-- Additional Information Section -->
                        <div class="section-header">
                            <i class="fas fa-info-circle"></i> Additional Information
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remark"
                                          class="form-control @error('remark') is-invalid @enderror"
                                          rows="3">{{ old('remark', $formData['remark'] ?? '') }}</textarea>
                                @error('remark')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                     <div class="section-header">
                                        <i class="fas fa-paperclip"></i> Attachments
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Production Attachment</label>
                                            <div class="file-upload-area" onclick="document.getElementById('production_file').click()">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p class="mb-1"><strong>Click to upload</strong></p>
                                                <p class="mb-0 text-muted small">Image or PDF files (Max: 5MB)</p>
                                                <input type="file" id="production_file" name="production_attachment" accept="image/*,.pdf" style="display: none;" onchange="updateFileName(this, 'production-filename')">
                                            </div>
                                            <small id="production-filename" class="text-muted"></small>
                                            @error('production_attachment')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Sale Attachment</label>
                                            <div class="file-upload-area" onclick="document.getElementById('sale_file').click()">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p class="mb-1"><strong>Click to upload</strong></p>
                                                <p class="mb-0 text-muted small">Image or PDF files (Max: 5MB)</p>
                                                <input type="file" id="sale_file" name="sale_attachment" accept="image/*,.pdf" style="display: none;" onchange="updateFileName(this, 'sale-filename')">
                                            </div>
                                            <small id="sale-filename" class="text-muted"></small>
                                            @error('sale_attachment')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>
                        <!-- Submit Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success me-2">
                                    <i class="fas fa-save"></i> {{ isset($record) ? 'Update Record' : 'Save Record' }}
                                </button>
                                <a href="{{ route('contact-farming.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const plotData = @json($allPlots ?? []);
    const seedData = @json($allSeedVarieties ?? []);
    const preSelectedPlots = @json(old('plots', $selectedPlots ?? []));

    function updatePlots() {
    const blockCheckboxes = document.querySelectorAll('.block-checkbox:checked');
    const plotsContainer = document.getElementById('plotsContainer');
    const loadingSpinner = document.getElementById('blockLoadingSpinner');

    // Store currently selected plot IDs before rebuilding
    let selectedPlotIds = Array.from(document.querySelectorAll('.plot-checkbox:checked')).map(cb => cb.value);
    if (selectedPlotIds.length === 0 && typeof preSelectedPlots !== 'undefined') {
        selectedPlotIds = (preSelectedPlots || []).map(String);
    }

    plotsContainer.innerHTML = '<div class="text-muted">Loading plots...</div>';
    loadingSpinner.style.display = 'block';

    const selectedBlockIds = Array.from(blockCheckboxes).map(cb => cb.value);

    if (selectedBlockIds.length > 0) {
        let plotsHtml = '';

        selectedBlockIds.forEach(blockId => {
            if (plotData[blockId]) {
                const blockName = document.querySelector(`label[for="block_${blockId}"]`).textContent.trim();

                plotsHtml += `
                    <div class="plot-block-group mb-3">
                        <h6 class="mb-2">${blockName}</h6>
                        <div class="plots-row">
                `;

                plotData[blockId].forEach(plot => {
                    // Check if this plot was previously selected
                    const isChecked = selectedPlotIds.includes(plot.id.toString());

                    plotsHtml += `
                        <div class="plot-check-item">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="plots[]"
                                       class="form-check-input plot-checkbox"
                                       value="${plot.id}"
                                       data-area="${plot.area}"
                                       id="plot_${plot.id}"
                                       ${isChecked ? 'checked' : ''}
                                       onchange="calculateTotalArea()">
                                <label class="form-check-label" for="plot_${plot.id}">
                                    ${plot.plot_name} (${plot.area} acres)
                                </label>
                            </div>
                        </div>
                    `;
                });

                plotsHtml += `
                        </div>
                    </div>
                `;
            }
        });

        setTimeout(() => {
            plotsContainer.innerHTML = plotsHtml || '<div class="text-muted">No plots available for selected blocks</div>';
            loadingSpinner.style.display = 'none';
            calculateTotalArea();
        }, 300);
    } else {
        plotsContainer.innerHTML = '<div class="text-muted">Select blocks to see available plots</div>';
        loadingSpinner.style.display = 'none';
        calculateTotalArea();
    }
}

function calculateTotalArea() {
    const selectedPlots = document.querySelectorAll('.plot-checkbox:checked');
    const areaInput = document.getElementById('area_input');

    let totalArea = 0;
    selectedPlots.forEach(plot => {
        totalArea += parseFloat(plot.dataset.area || 0);
    });

    if (areaInput) {
        areaInput.value = totalArea.toFixed(2);
        // Add a brief highlight effect to show the value has been updated
        areaInput.classList.add('highlight-calculation');
        setTimeout(() => {
            areaInput.classList.remove('highlight-calculation');
        }, 1500);
    }
}
    function formatNumber(num) {
        return new Intl.NumberFormat('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    function calculateAllAmounts() {
        // Get input values
        const productionQty = parseFloat(document.getElementById('production_qty').value) || 0;
        const productionPrice = parseFloat(document.getElementById('production_price').value) || 0;
        const amountRecovery = parseFloat(document.getElementById('amount_recovery').value) || 0;
        const handlingLoss = parseFloat(document.getElementById('handling_loss').value) || 0;
        const salePrice = parseFloat(document.getElementById('sale_price').value) || 0;

        // Calculate Sale Quantity (Production Qty - Handling Loss)
        const saleQty = Math.max(0, productionQty - handlingLoss);
        document.getElementById('sale_qty').value = saleQty.toFixed(2);

        // Calculate Total Sale Amount (Sale Qty × Sale Price)
        const totalSaleAmount = saleQty * salePrice;
        document.getElementById('total_sale_amount').value = totalSaleAmount.toFixed(2);

        // Calculate Product Amount (Production Qty × Production Price)
        const productAmount = productionQty * productionPrice;
        document.getElementById('product_amount').value = productAmount.toFixed(2);

        // Update Financial Summary
        updateFinancialSummary(productAmount, amountRecovery, totalSaleAmount);

        // Highlight calculated fields
        highlightCalculatedFields();
    }

    function highlightCalculatedFields() {
        const autoFields = ['sale_qty', 'total_sale_amount', 'product_amount', 'roi_percentage'];

        autoFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.add('highlight-calculation');
                setTimeout(() => {
                    field.classList.remove('highlight-calculation');
                }, 1500);
            }
        });
    }

    // function updateFinancialSummary(productionCost, recoveryAmount, saleRevenue) {
    //     // Update display values
    //     document.getElementById('production_cost_display').textContent = '₹' + productionCost.toFixed(2);
    //     document.getElementById('recovery_display').textContent = '₹' + recoveryAmount.toFixed(2);
    //     document.getElementById('sale_revenue_display').textContent = '₹' + saleRevenue.toFixed(2);

    //     // Calculate and display net profit/loss
    //     const netProfit = saleRevenue - productionCost + recoveryAmount;
    //     const profitDisplay = document.getElementById('profit_display');
    //     const profitContainer = document.getElementById('profit_container');

    //     profitDisplay.textContent = '₹' + Math.abs(netProfit).toFixed(2);

    //     // Update profit/loss styling
    //     if (netProfit > 0) {
    //         profitContainer.classList.remove('bg-danger', 'text-white');
    //         profitContainer.classList.add('bg-success', 'text-white');
    //         profitDisplay.classList.remove('text-danger');
    //         profitDisplay.classList.add('text-white');
    //     } else if (netProfit < 0) {
    //         profitContainer.classList.remove('bg-success', 'text-white');
    //         profitContainer.classList.add('bg-danger', 'text-white');
    //         profitDisplay.classList.remove('text-success');
    //         profitDisplay.classList.add('text-white');
    //     } else {
    //         profitContainer.classList.remove('bg-success', 'bg-danger', 'text-white');
    //         profitDisplay.classList.remove('text-success', 'text-danger', 'text-white');
    //     }

    //     // Calculate and update ROI
    //     const roi = productionCost > 0 ? ((netProfit) / productionCost) * 100 : 0;
    //     document.getElementById('roi_percentage').value = roi.toFixed(2);

    //     // Add highlight effects
    //     ['production_cost_display', 'recovery_display', 'sale_revenue_display', 'profit_display'].forEach(id => {
    //         const element = document.getElementById(id);
    //         element.classList.add('highlight-calculation');
    //         setTimeout(() => {
    //             element.classList.remove('highlight-calculation');
    //         }, 1500);
    //     });
    // }
function updateFinancialSummary(productionAmount, recoveryAmount, saleAmount) {

    // Display base values
    document.getElementById('production_cost_display').textContent = '₹' + productionAmount.toFixed(2);
    document.getElementById('recovery_display').textContent = '₹' + recoveryAmount.toFixed(2);
    document.getElementById('sale_revenue_display').textContent = '₹' + saleAmount.toFixed(2);

    // STEP 1: Calculate remaining (production - sale)
    let remainingAmount = productionAmount - saleAmount;

    // Remove minus (convert to positive)
    remaining_amount = Math.abs(remainingAmount)

    // STEP 2: Always use absolute value for net calculation
    let netProfit = remaining_mount + recoveryAmount;

    // STEP 3: Display profit always positive
    const profitDisplay = document.getElementById('profit_display');
    const profitContainer = document.getElementById('profit_container');

    profitDisplay.textContent = '₹' + netProfit.toFixed(2);

    // STEP 4: UI styling based on real sign (profit/loss)
    if (remainingAmount >= 0) {
        // real loss (production > sale)
        profitContainer.classList.remove('bg-success');
        profitContainer.classList.add('bg-danger', 'text-white');
    } else {
        // real profit (sale > production)
        profitContainer.classList.remove('bg-danger');
        profitContainer.classList.add('bg-success', 'text-white');
    }

    // STEP 5: ROI Calculation
    let roi = productionAmount > 0 ? (netProfit / productionAmount) * 100 : 0;
    document.getElementById('roi_percentage').value = roi.toFixed(2);

    // Highlight animation
    ['production_cost_display', 'recovery_display', 'sale_revenue_display', 'profit_display']
        .forEach(id => {
            const el = document.getElementById(id);
            el.classList.add('highlight-calculation');
            setTimeout(() => el.classList.remove('highlight-calculation'), 1500);
        });
}



    let varietyIndex = {{ isset($formVarieties) ? (count($formVarieties) - 1) : 0 }}; // To keep track of variety rows for naming inputs

    function addNewVarietyRow() {
        const container = document.getElementById('variety-container');
        const firstVarietyRow = container.querySelector('.variety-row');
        const newRow = firstVarietyRow.cloneNode(true);

        varietyIndex++; // Increment for new variety row

        // Clear all input values and update names for the new variety row
        newRow.querySelectorAll('input, textarea').forEach(input => {
            const oldName = input.name;
            if (oldName) {
                // Update name for variety-level inputs
                if (oldName.includes('[]')) {
                    input.name = oldName.replace('[]', `[${varietyIndex}]`);
                } else if (oldName.includes('sales[0]')) {
                    // This is a sale input, will be handled by addNewSaleRow
                } else {
                    input.name = oldName.replace(/\d+/, varietyIndex);
                }
            }
            input.value = '';
            if (input.type === 'number') {
                input.value = '0.00';
            }
            input.classList.remove('is-invalid', 'is-valid'); // Clear validation states
        });

        // Ensure the remaining sale quantity input is correctly named for the new variety row
        const remainingSaleQtyInput = newRow.querySelector('.remaining-sale-quantity');
        if (remainingSaleQtyInput) {
            remainingSaleQtyInput.name = `remaining_sale_quantity[${varietyIndex}]`;
            remainingSaleQtyInput.value = '0.00';
        }

        // Reset sale entries for the new variety row
        const saleEntryWrapper = newRow.querySelector('.sale-entry-wrapper');
        saleEntryWrapper.innerHTML = ''; // Clear existing sale entries
        addNewSaleRow(newRow.querySelector('.sale-details-container button')); // Add one fresh sale entry

        // Update event listeners for production/recovery inputs
        newRow.querySelectorAll('.production-qty, .production-price, .handling-loss, .amount-recovery').forEach(input => {
            input.onchange = () => calculateRowAmounts(input);
        });

        // Add delete button if it's not the first row
        if (container.children.length > 0) {
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-danger btn-sm position-absolute';
            deleteButton.style.right = '10px';
            deleteButton.style.top = '10px';
            deleteButton.innerHTML = '<i class="fas fa-times"></i>';
            deleteButton.onclick = function() {
                this.closest('.variety-row').remove();
                calculateAllTotals();
            };
            newRow.style.position = 'relative';
            newRow.appendChild(deleteButton);
        }

        container.appendChild(newRow);
        calculateAllTotals(); // Recalculate totals after adding a new row
    }

    function addNewSaleRow(button) {
        // Simplified: use helper to create sale + reverse elements and append them
        const varietyRow = button.closest('.variety-row');
        const saleEntryWrapper = varietyRow.querySelector('.sale-entry-wrapper');
        const allVarietyRows = Array.from(document.querySelectorAll('.variety-row'));
        const idx = allVarietyRows.indexOf(varietyRow);

        const newSale = createSaleEntryElement(idx, false);
        saleEntryWrapper.appendChild(newSale);

        // Only add reverse entry for varieties other than Hay or Silage
        const varietyNameInput = varietyRow.querySelector('input[name="variety[]"]');
        const varietyName = varietyNameInput ? (varietyNameInput.value || '').trim().toLowerCase() : '';
        if (varietyName !== 'hay' && varietyName !== 'silage') {
            const newReverse = createSaleEntryElement(idx, true);
            saleEntryWrapper.appendChild(newReverse);
        }
        calculateAllTotals();
    }

    // Helper: create a sale or reverse DOM element for given variety index
    function createSaleEntryElement(varietyIdx, isReverse = false) {
        const wrapper = document.createElement('div');
        wrapper.className = isReverse ? 'reverse-entry border border-warning rounded p-3 mb-2 position-relative' : 'sale-entry border border-info rounded p-3 mb-2 position-relative';

        const qtyName = `sales[${varietyIdx}][quantity_sold_actual][]`;
        const priceName = `sales[${varietyIdx}][sale_price][]`;
        const amtName = `sales[${varietyIdx}][total_sale_amount][]`;
        const buyerName = `sales[${varietyIdx}][sale_part_to][]`;
        const dateName = `sales[${varietyIdx}][sale_date][]`;

        wrapper.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">${isReverse ? 'Reverse Undersize Quantity (MT)' : 'Sale Quantity (MT)'} ${isReverse ? '' : '<span class="required">*</span>'}</label>
                    <div class="input-group">
                        <input type="number" name="${qtyName}" class="form-control quantity-sold-actual ${isReverse ? 'reverse-quantity' : ''}" step="0.01" ${isReverse ? '' : 'required'} onchange="calculateRowAmounts(this)" value="${isReverse ? '' : '0.00'}">
                        <span class="input-group-text">MT</span>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div>
                        <label class="form-label">Net Sold</label>
                        <div class="input-group">
                            <input type="text" readonly class="form-control net-sold-display" value="0.00">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Price/MT (Rs) ${isReverse ? '' : '<span class="required">*</span>'}</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="${priceName}" class="form-control sale-price" step="0.01" ${isReverse ? '' : 'required'} onchange="calculateRowAmounts(this)" value="${isReverse ? '0.00' : '0.00'}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Sale Amount (Rs)</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="${amtName}" class="form-control auto-calc total-sale-amount" readonly value="0.00">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buyer Name</label>
                    <input type="text" name="${buyerName}" class="form-control" value="">
                    <div class="mt-2">
                        <label class="form-label small">Sale Date</label>
                        <input type="date" name="${dateName}" class="form-control sale-date" value="">
                    </div>
                </div>
            </div>
            ${isReverse ? `<input type="hidden" name="sales[${varietyIdx}][parent_sale_id][]" value="">` : `<input type="hidden" name="sales[${varietyIdx}][sale_id][]" value="">`}
            <input type="hidden" name="sales[${varietyIdx}][is_reverse][]" value="${isReverse ? 1 : 0}">
            <button type="button" class="btn btn-danger btn-sm position-absolute delete-sale-entry" style="right: 10px; top: 10px;" onclick="removeSaleEntry(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Attach onchange listeners on cloned inputs where needed
        wrapper.querySelectorAll('.quantity-sold-actual, .sale-price').forEach(input => {
            input.onchange = () => calculateRowAmounts(input);
        });

        return wrapper;
    }

    function removeSaleEntry(button) {
        let saleEntry = button.closest('.sale-entry');
        if (!saleEntry) {
            saleEntry = button.closest('.reverse-entry');
            if (saleEntry) {
                saleEntry.remove();
                calculateAllTotals();
                return;
            }
            return;
        }

        const next = saleEntry.nextElementSibling;
        if (next && next.classList && next.classList.contains('reverse-entry')) {
            next.remove();
        }

        saleEntry.remove();
        calculateAllTotals(); // Recalculate totals after removing a sale row
    }

    function calculateRowAmounts(input) {
        const varietyRow = input.closest('.variety-row');
        const productionQty = parseFloat(varietyRow.querySelector('.production-qty').value) || 0;
        const productionPrice = parseFloat(varietyRow.querySelector('.production-price').value) || 0;
        const handlingLoss = parseFloat(varietyRow.querySelector('.handling-loss').value) || 0;
        const amountRecovery = parseFloat(varietyRow.querySelector('.amount-recovery').value) || 0;

        const quantityForSaleInput = varietyRow.querySelector('input[name^="quantity_for_sale"]');
        const saleQtyByProduction = Math.max(0, productionQty - handlingLoss);
        quantityForSaleInput.value = saleQtyByProduction.toFixed(2);

        // Calculate Production Amount
        const productAmount = productionQty * productionPrice;
        varietyRow.querySelector('.product-amount').value = productAmount.toFixed(2);

        let totalSoldQuantityForVariety = 0;
        let totalPositiveSold = 0;
        let totalSaleAmountForVariety = 0;
        varietyRow.querySelectorAll('.sale-entry, .reverse-entry').forEach(saleEntry => {
            let qtyInput = saleEntry.querySelector('.quantity-sold-actual');
            if (!qtyInput) return;
            let rawQty = parseFloat(qtyInput.value) || 0;
            const salePrice = parseFloat(saleEntry.querySelector('.sale-price').value) || 0;

            const isReverse = saleEntry.classList.contains('reverse-entry');

            if (isReverse) {
                // Find the corresponding original sale (previous sibling)
                const prev = saleEntry.previousElementSibling;
                if (prev && prev.classList && prev.classList.contains('sale-entry')) {
                    const saleQtyInput = prev.querySelector('.quantity-sold-actual');
                    const saleQty = parseFloat(saleQtyInput.value) || 0;
                    // Clamp return qty between 0 and saleQty
                    if (rawQty < 0) rawQty = 0;
                    if (rawQty > saleQty) rawQty = saleQty;
                    // update reverse input to clamped value to keep UI consistent
                    qtyInput.value = rawQty.toFixed(2);

                    // update reverse remaining (remaining of original sale)
                    const reverseRemaining = saleQty - rawQty;
                    const revRemInput = saleEntry.querySelector('.reverse-remaining');
                    if (revRemInput) revRemInput.value = reverseRemaining.toFixed(2);

                    // Do not change the original sale input value; update net display only
                    try {
                        const netDisp = prev.querySelector('.net-sold-display');
                        if (netDisp) netDisp.value = reverseRemaining.toFixed(2);

                        // update previous sale's displayed total amount according to net (remaining) quantity
                        const prevSalePrice = parseFloat(prev.querySelector('.sale-price').value) || 0;
                        const prevTotalAmtInput = prev.querySelector('.total-sale-amount');
                        if (prevTotalAmtInput) prevTotalAmtInput.value = (reverseRemaining * prevSalePrice).toFixed(2);
                    } catch (e) {
                        // ignore
                    }
                }
            }

            const signedQty = isReverse ? -Math.abs(rawQty) : Math.abs(rawQty);

            totalSoldQuantityForVariety += signedQty;
            if (!isReverse) totalPositiveSold += signedQty;

            const saleAmount = signedQty * salePrice;
            const amtInput = saleEntry.querySelector('.total-sale-amount');
            if (amtInput) amtInput.value = Math.abs(saleAmount).toFixed(2);
            totalSaleAmountForVariety += saleAmount;
        });

        // Validate total Sale Quantity for Sale against total available for sale for this variety
        // Allow a small tolerance for floating-point rounding so equal values don't trigger the alert
        const EPS = 0.00001;
        if (totalPositiveSold > saleQtyByProduction + EPS) {
            alert('Total Sale Quantity for all sales cannot be greater than Sale Quantity by Production for this variety.');
            const lastSaleEntry = Array.from(varietyRow.querySelectorAll('.sale-entry')).pop();
            if (lastSaleEntry) {
                const lastQuantityInput = lastSaleEntry.querySelector('.quantity-sold-actual');
                const prevPositiveTotal = totalPositiveSold - (parseFloat(lastQuantityInput.value) || 0);
                const newLastQuantity = saleQtyByProduction - prevPositiveTotal;
                lastQuantityInput.value = Math.max(0, newLastQuantity).toFixed(2);
                totalPositiveSold = saleQtyByProduction;
                totalSoldQuantityForVariety = totalPositiveSold + Array.from(varietyRow.querySelectorAll('.reverse-entry')).reduce((acc, re) => acc - (parseFloat(re.querySelector('.quantity-sold-actual').value) || 0), 0);
            }
        }

        // Calculate Remaining Sale Quantity
        const remainingSaleQuantity = Math.max(0, saleQtyByProduction - totalSoldQuantityForVariety);
        varietyRow.querySelector('.remaining-sale-quantity').value = remainingSaleQuantity.toFixed(2);

        Array.from(varietyRow.querySelectorAll('.sale-entry')).forEach(saleEntry => {
            const next = saleEntry.nextElementSibling;
            const saleQty = parseFloat(saleEntry.querySelector('.quantity-sold-actual').value) || 0;
            let reverseQty = 0;
            if (next && next.classList && next.classList.contains('reverse-entry')) {
                reverseQty = parseFloat(next.querySelector('.quantity-sold-actual').value) || 0;
            }
            const net = Math.max(0, saleQty - reverseQty);
            const disp = saleEntry.querySelector('.net-sold-display');
            if (disp) disp.textContent = net.toFixed(2);
        });

        // Update financial summary
        calculateAllTotals();
    }

    function calculateAllTotals() {
        let totalProductionCost = 0;
        let totalRecovery = 0;
        let totalSaleRevenue = 0;

        document.querySelectorAll('.variety-row').forEach(varietyRow => {
            totalProductionCost += parseFloat(varietyRow.querySelector('.product-amount').value) || 0;
            totalRecovery += parseFloat(varietyRow.querySelector('.amount-recovery').value) || 0;

            varietyRow.querySelectorAll('.sale-entry, .reverse-entry').forEach(saleEntry => {
                totalSaleRevenue += parseFloat(saleEntry.querySelector('.total-sale-amount').value) || 0;
            });

            // Recalculate remaining sale quantity for each variety row
            const productionQty = parseFloat(varietyRow.querySelector('.production-qty').value) || 0;
            const handlingLoss = parseFloat(varietyRow.querySelector('.handling-loss').value) || 0;
            const saleQtyByProduction = Math.max(0, productionQty - handlingLoss);

            let totalSoldQuantityForVariety = 0;
            varietyRow.querySelectorAll('.sale-entry, .reverse-entry').forEach(saleEntry => {
                const qty = parseFloat(saleEntry.querySelector('.quantity-sold-actual').value) || 0;
                if (saleEntry.classList && saleEntry.classList.contains('reverse-entry')) {
                    totalSoldQuantityForVariety -= Math.abs(qty);
                } else {
                    totalSoldQuantityForVariety += Math.abs(qty);
                }
            });

            const remainingSaleQuantity = Math.max(0, saleQtyByProduction - totalSoldQuantityForVariety);
            varietyRow.querySelector('.remaining-sale-quantity').value = remainingSaleQuantity.toFixed(2);
        });

        // Update financial summary displays
        document.getElementById('production_cost_display').textContent = '₹' + totalProductionCost.toFixed(2);
        document.getElementById('recovery_display').textContent = '₹' + totalRecovery.toFixed(2);
        document.getElementById('sale_revenue_display').textContent = '₹' + totalSaleRevenue.toFixed(2);

        // Calculate and update net profit/loss
        //const netProfit1 = totalSaleRevenue - totalProductionCost
        const netProfit = totalSaleRevenue - totalProductionCost + totalRecovery;
        const profitDisplay = document.getElementById('profit_display');
        const profitContainer = document.getElementById('profit_container');

        profitDisplay.textContent = '₹' + Math.abs(netProfit).toFixed(2);

        // Update styling based on profit/loss
        if (netProfit > 0) {
            profitContainer.style.backgroundColor = '#667e06';
            profitContainer.style.backgroundColor = 'rgba(102, 126, 6, 0.1)';
            profitContainer.classList.add('text-white');
            profitDisplay.style.color = '#667e06';
        } else if (netProfit < 0) {
            profitContainer.style.backgroundColor = '#dc3545';
            profitContainer.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
            profitContainer.classList.add('text-white');
            profitDisplay.style.color = '#dc3545';
        } else {
            profitContainer.style.backgroundColor = '';
            profitDisplay.style.color = '';
            profitContainer.classList.remove('text-white');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Financial Summary calculations
        const totalInvestmentInput = document.getElementById('total_investment');
        const expectedReturnInput = document.getElementById('expected_return');

        if (totalInvestmentInput && expectedReturnInput) {
            totalInvestmentInput.addEventListener('input', calculateFinancialSummary);
            expectedReturnInput.addEventListener('input', calculateFinancialSummary);
            // Calculate initial values
            calculateFinancialSummary();
        }

        // Form validation
        document.querySelectorAll('input[required], select[required]').forEach(function(input) {
            input.addEventListener('blur', function() {
                if (!this.value || this.value.trim() === '') {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });

        // Number input validation
        document.querySelectorAll('input[type="number"]').forEach(function(input) {
            input.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });

        // Populate plots for any preselected blocks and calculate totals
        try {
            updatePlots();
        } catch (e) {
            // ignore if updatePlots not available
        }
        // Initial calculation for the first variety row and its sale entries
        calculateAllTotals();
    });
</script>
@endsection
