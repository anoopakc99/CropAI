@extends('layouts.app')

@section('content')

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contract Farming â€“ Crop-AI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            padding: 15px;
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
            background: linear-gradient(90deg, var(--accent-color), transparent);
            padding: 12px 20px;
            margin: 25px -15px 20px -15px;
            border-radius: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.1rem;
            border-left: 4px solid var(--primary-color);
        }

        .section-header i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .form-control, .form-select {
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #FAFAFA;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
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

        .date-range-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-range-separator {
            font-weight: 600;
            color: var(--primary-color);
            padding: 0 5px;
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 5px 10px;
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

        .auto-calc::after {
            content: 'ðŸ§®';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
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

        /*.action-buttons {*/
        /*    background: var(--background-light);*/
        /*    padding: 25px;*/
        /*    border-top: 1px solid #E0E0E0;*/
        /*    text-align: center;*/
        /*    margin: 0 -30px -30px -30px;*/
        /*}*/

        .records-table {
            margin-top: 20px;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: var(--background-light);
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

        .is-valid {
            border-color: #28a745 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.7-.08 1.53-1.99L6.46 2.4l.47.43-1.92 2.4-1.02 1.32-1.87-1.79.46-.43z'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(.375em + .1875rem) center !important;
            background-size: calc(.75em + .375rem) calc(.75em + .375rem) !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4M8.2 4.6l-2.4 2.4'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(.375em + .1875rem) center !important;
            background-size: calc(.75em + .375rem) calc(.75em + .375rem) !important;
        }

        .pagination {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-link {
            border: none;
            padding: 8px 16px;
            margin: 0 2px;
            border-radius: 6px;
            color: var(--primary-color);
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--accent-color);
            color: white;
            transform: translateY(-1px);
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-group .btn {
            margin: 0 1px;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* View Modal Styles */
        .view-modal .modal-dialog {
            max-width: 900px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }

        .info-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-dark);
            min-width: 180px;
            margin-right: 15px;
            font-size: 0.95rem;
        }

        .info-value {
            color: #333;
            flex: 1;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .currency-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .value-highlight {
            background: linear-gradient(45deg, #fff3cd, #f8d7da);
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
        }

        .date-range {
            background: var(--background-light);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid var(--secondary-color);
        }

        .badge-success {
            background: var(--secondary-color);
            color: white;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .current-file {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
            }

            .form-container {
                padding: 20px;
            }

            .date-range-container {
                flex-direction: column;
                align-items: stretch;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                margin: 1px 0;
                border-radius: 4px !important;
            }

            .modal-xl {
                max-width: 95%;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-label {
                min-width: auto;
                margin-bottom: 5px;
                font-size: 0.9rem;
            }
        }

        @media print {
            .btn, .modal, .action-buttons {
                display: none !important;
            }

            .table {
                font-size: 12px;
            }

            .header {
                background: #2E7D32 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-containerr" style="margin-top: 19px;">
            <div class="header">
                <h1><i class="fas fa-seedling"></i> Contract Farming</h1>
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
                        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
               
                <div class="mb-4 text-end">
                       <button id="downloadCsv" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download
                        </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#farmingModal">
                        <i class="fas fa-plus"></i> Add New Record
                    </button>
                </div>

                <!-- Add Record Modal -->
                <div class="modal fade" id="farmingModal" tabindex="-1" aria-labelledby="farmingModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <form id="contractForm" method="POST" action="{{ route('contact-farming.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="farmingModalLabel">
                                        <i class="fas fa-leaf text-success"></i> Add Contract Farming Record
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="section-header">
                                        <i class="fas fa-map-marker-alt"></i> Basic Crop & Plot Details
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Block Name <span class="required">*</span></label>
                                            <select name="block" class="form-select @error('block') is-invalid @enderror" required onchange="updatePlots()">
                                                <option value="">Select Block</option>
                                                @if(isset($blocks))
                                                    @foreach($blocks as $block)
                                                        <option value="{{ $block->id }}" {{ old('block') == $block->id ? 'selected' : '' }}>{{ $block->block_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('block')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="loading-spinner" id="blockLoadingSpinner">
                                                <i class="fas fa-spinner fa-spin"></i> Loading plots...
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Plot Name <span class="required">*</span></label>
                                            <select name="plot" class="form-select @error('plot') is-invalid @enderror" required onchange="updateArea()">
                                                <option value="">First select a Block</option>
                                            </select>
                                            @error('plot')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Area (Acre)
                                                <i class="fas fa-info-circle tooltip-icon" title="Auto-filled based on selected plot"></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="text" name="area" class="form-control auto-calc" value="{{ old('area') }}">
                                                <span class="input-group-text">Acre</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Seed Name <span class="required">*</span></label>
                                            <input type="text" name="seed_name" class="form-control" value="{{ old('seed_name') }}">
                                            <!--<select name="seed_name" class="form-select @error('seed_name') is-invalid @enderror" required onchange="updateSeedVarieties()">-->
                                            <!--    <option value="">Select Seed</option>-->
                                            <!--    @if(isset($seeds))-->
                                            <!--        @foreach($seeds as $seed)-->
                                            <!--            <option value="{{ $seed->seed_name }}" {{ old('seed_name') == $seed->seed_name ? 'selected' : '' }}>{{ $seed->seed_name }}</option>-->
                                            <!--        @endforeach-->
                                            <!--    @endif-->
                                            <!--</select>-->
                                            @error('seed_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="loading-spinner" id="seedLoadingSpinner">
                                                <i class="fas fa-spinner fa-spin"></i> Loading varieties...
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Seed Variety</label>
                                            <input type="text" name="seed_variety" class="form-control" value="{{ old('seed_variety') }}">
                                            <!--<select name="seed_variety" class="form-select @error('seed_variety') is-invalid @enderror">-->
                                            <!--    <option value="">First select a Seed Name</option>-->
                                            <!--</select>-->
                                            @error('seed_variety')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="section-header">
                                        <i class="fas fa-handshake"></i> Contract Details
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Date <span class="required">*</span></label>
                                            <div class="date-range-container">
                                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" required value="{{ old('start_date') }}">
                                                <span class="date-range-separator">to</span>
                                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" required value="{{ old('end_date') }}">
                                            </div>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Contractor Name <span class="required">*</span></label>
                                            <input type="text" name="contractor_name" class="form-control @error('contractor_name') is-invalid @enderror" placeholder="Enter contractor name" required value="{{ old('contractor_name') }}">
                                            @error('contractor_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>



                                    <div class="section-header">
                                        <i class="fas fa-industry"></i> Production Details
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Production Quantity (MT) <span class="required">*</span></label>
                                            <div class="input-group">
                                                <input type="number"
                                                       name="production"
                                                       id="production_qty"
                                                       class="form-control @error('production') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       required
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('production') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                            @error('production')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Production Price/MT (Rs) <span class="required">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number"
                                                       name="production_price"
                                                       id="production_price"
                                                       class="form-control @error('production_price') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       required
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('production_price') }}">
                                                <span class="input-group-text"> MT</span>
                                            </div>
                                            @error('production_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Amount (Rs)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number"
                                                       name="product_amount"
                                                       id="product_amount"
                                                       class="form-control @error('product_amount') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('amount_recovery') }}">
                                            </div>
                                            @error('amount_recovery')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Amount Recovery (Rs)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number"
                                                       name="amount_recovery"
                                                       id="amount_recovery"
                                                       class="form-control @error('amount_recovery') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('amount_recovery') }}">
                                            </div>
                                            <small class="text-muted">Electricity, water, etc.</small>
                                            @error('amount_recovery')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Handling Loss (MT)</label>
                                            <div class="input-group">
                                                <input type="number"
                                                       name="handling_loss"
                                                       id="handling_loss"
                                                       class="form-control @error('handling_loss') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('handling_loss') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                            <div class="calculation-info">
                                                <i class="fas fa-info-circle"></i> This will be deducted from production quantity
                                            </div>
                                            @error('handling_loss')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Sale Quantity (MT)
                                                <i class="fas fa-calculator tooltip-icon" title="Auto-calculated: Production Qty - Handling Loss"></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number"
                                                       name="quantity_sold"
                                                       id="sale_qty"
                                                        step="0.01"
                                                       class="form-control auto-calc @error('quantity_sold') is-invalid @enderror"
                                                       value="{{ old('quantity_sold') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                            <div class="calculation-info">
                                                <span class="info-badge">AUTO</span> Production Qty - Handling Loss
                                            </div>
                                            @error('quantity_sold')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="section-header">
                                        <i class="fas fa-shopping-cart"></i> Sales Details
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Sale Price/MT (Rs) <span class="required">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number"
                                                       name="sale_price"
                                                       id="sale_price"
                                                       class="form-control @error('sale_price') is-invalid @enderror"
                                                       step="0.01"
                                                       placeholder="0.00"
                                                       required
                                                       onchange="calculateAllAmounts()"
                                                       value="{{ old('sale_price') }}">
                                                <span class="input-group-text">MT</span>
                                            </div>
                                            @error('sale_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Buyer Name <span class="required">*</span></label>
                                            <input type="text"
                                                   name="sale_part_to"
                                                   class="form-control @error('sale_part_to') is-invalid @enderror"
                                                   placeholder="Enter buyer name"
                                                   required
                                                   value="{{ old('sale_part_to') }}">
                                            @error('sale_part_to')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Total Sale Amount (Rs)
                                                <i class="fas fa-calculator tooltip-icon" title="Auto-calculated: Sale Qty Ã— Sale Price"></i>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number"
                                                       name="sale_amount"
                                                       id="total_sale_amount"
                                                       class="form-control auto-calc"
                                                       readonly
                                                       value="{{ old('sale_amount') }}">
                                            </div>
                                            <div class="calculation-info">
                                                <span class="info-badge">AUTO</span> Sale Qty Ã— Sale Price
                                            </div>
                                        </div>
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
                                                                <small class="text-muted">Qty Ã— Price</small>
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

                                    <div class="section-header">
                                        <i class="fas fa-comment"></i> Additional Information
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Remarks</label>
                                            <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3" placeholder="Any additional notes or comments...">{{ old('remark') }}</textarea>
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
                                       <div class="action-buttons">
                                    <button type="button" class="btn btn-secondary me-3" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <button class="btn btn-primary" type="submit" id="submitBtn">
                                        <i class="fas fa-save"></i> Save Contract Record
                                    </button>
                                </div>
                                </div>


                            </form>
                        </div>
                    </div>
                </div>

                <!-- View Record Modal -->
                <div class="modal fade view-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="viewModalLabel">
                                    <i class="fas fa-eye"></i> Contract Farming Record Details
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="viewModalBody">
                                <!-- Content will be loaded here -->
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading record details...</p>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Close
                                </button>
                                <!--<button type="button" class="btn btn-primary" onclick="printModalContent()">-->
                                <!--    <i class="fas fa-print"></i> Print-->
                                <!--</button>-->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Record Modal -->
                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title" id="editModalLabel">
                                    <i class="fas fa-edit"></i> Edit Contract Farming Record
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="editModalBody">
                                <!-- Content will be loaded here -->
                                <div class="text-center">
                                    <div class="spinner-border text-warning" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading edit form...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Records Table -->
                <div class="records-table">
                    <h4 class="mb-3">
                        <i class="fas fa-table text-success"></i> Contract Farming Records
                    </h4>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Block Name</th>
                                    <th>Plot Name</th>
                                    <th><center>Seed Name & Variety</center></th>
                                    <th>Contractor Name</th>
                                    <th>Area (Acre)</th>
                                    <th>Production (MT)</th>
                                    <th>Production Price/MT(Rs)</th>
                                    <th>Amount (Rs)</th>
                                    <th>Handling Loss (MT) (Rs)</th>
                                    <th>Sale Quantity (MT)</th>
                                    <th>Sale Rate (Rs)</th>
                                    <th>Sale Amount (Rs)</th>
                                    <th>Profit/Loss (Rs)</th>
                                    <th>Buyer</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($records) && $records->count() > 0)
                                    @foreach($records as $record)
                                    <?php
                                      $profit = $record->sale_amount - $record->product_amount;
                                    if ($profit < 0) {
                                        $class = "badge bg-danger";
                                        $netProfit = $profit - $record->amount_recovery;
                                    } else {
                                        $class = "badge bg-success";
                                        $netProfit = $profit + $record->amount_recovery;
                                    }
                                    ?>
                                        <tr>
                                            <td><strong>{{ $record->block_name ?? 'N/A' }}</strong></td>
                                            <td><small class="text-muted">{{ $record->plot_name ?? 'N/A' }}</small></td>
                                            <td>
                                                <strong>{{ $record->seed_name ?? 'N/A' }}</strong>
                                                @if(isset($record->seed_variety) && $record->seed_variety)
                                                    <br><small class="text-muted">{{ $record->seed_variety }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $record->contractor_name ?? 'N/A' }}</td>
                                            <td>{{ $record->area_hectares ?? $record->plot_area ?? $record->area ?? 'N/A' }}</td>
                                            <td>{{ number_format($record->production_mt ?? $record->production ?? 0, 2) }} </td>
                                            <td>{{ number_format($record->production_price_per_mt ?? $record->production_price_per_mt ?? 0, 2) }}</td>
                                            <td>{{ number_format($record->product_amount ?? $record->product_amount ?? 0, 2) }}</td>
                                            <td>{{ number_format($record->handling_loss_mt ?? $record->handling_loss_mt ?? 0, 2) }}</td>
                                            <td>{{ number_format($record->quantity_sold_mt ?? $record->quantity_sold_mt ?? 0, 2) }}</td>
                                            <td>{{ number_format($record->sale_price_per_mt ?? $record->sale_price_per_mt ?? 0, 2) }}</td>
                                            <td>{{ number_format($record->sale_amount ?? 0, 2) }}</td>
                                            <td>

                                            <span class="{{ $class }}">
                                                {{ number_format($netProfit, 2) }}
                                            </span>

                                            <td>{{ $record->sale_part_to ?? 'N/A' }}</td>
                                            <td style="white-space: nowrap;">
                                                <small>
                                                    @if(isset($record->contract_start_date) || isset($record->start_date))
                                                        {{ \Carbon\Carbon::parse($record->contract_start_date ?? $record->start_date)->format('d-m-y') }}<br>
                                                        to<br>
                                                        {{ \Carbon\Carbon::parse($record->contract_end_date ?? $record->end_date)->format('d-m-y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button onclick="viewRecord({{ $record->id }})" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteRecord({{ $record->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No contract records found. Add your first record above.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($records) && $records->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $records->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this contract farming record?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <script>
        const plotData = @json($allPlots ?? []);
        const seedData = @json($allSeedVarieties ?? []);

        // ============= MAIN CALCULATION FUNCTION =============
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

            // Calculate Total Sale Amount (Sale Qty Ã— Sale Price)
            const totalSaleAmount = saleQty * salePrice;
            document.getElementById('total_sale_amount').value = totalSaleAmount.toFixed(2);
            const productAmount = productionQty * productionPrice;
            document.getElementById('product_amount').value = productAmount.toFixed(2);

            // Update Financial Summary
            updateFinancialSummary(productionQty, productionPrice, amountRecovery, totalSaleAmount);

            // Highlight auto-calculated fields
            highlightCalculatedFields();
        }
        $(document).ready(function () {
            $("#sale_qty").on("blur", function () {
                let productionPrice = parseFloat($("#production_qty").val()) || 0;
                let saleQty = parseFloat($(this).val()) || 0;
                let handling_loss = parseFloat($("#handling_loss").val()) || 0;

                if (saleQty > productionPrice) {
                    alert("Quantity Sold cannot be greater than Production Quantity.");
                    $(this).val(productionPrice-handling_loss);
                }
            });
        });

        function updateFinancialSummary(productionQty, productionPrice, amountRecovery, totalSaleAmount) {
            // Calculate Production Cost
            const productionCost = productionQty * productionPrice;
            document.getElementById('production_cost_display').textContent = `₹${formatNumber(productionCost)}`;

            // Display Recovery Amount
            document.getElementById('recovery_display').textContent = `₹${formatNumber(amountRecovery)}`;

            // Display Sale Revenue
            document.getElementById('sale_revenue_display').textContent = `₹${formatNumber(totalSaleAmount)}`;
             // Calculate Profit
            const Profit = totalSaleAmount - productionCost;

            // ✅ Declare netProfit outside so it's available later
            let netProfit;
            if (Profit < 0) {
                netProfit = Profit - amountRecovery;
            } else {
                netProfit = Profit + amountRecovery;
            }
            // Calculate Net Profit/Loss
            ///const netProfit = totalSaleAmount + productionCost + amountRecovery;
            const profitContainer = document.getElementById('profit_container');
            const profitDisplay = document.getElementById('profit_display');

            if (netProfit >= 0) {
                profitDisplay.className = 'fw-bold text-success fs-4';
                profitContainer.className = 'border rounded p-3 bg-success bg-opacity-10';
                profitDisplay.innerHTML = `<i class="fas fa-arrow-up"></i> ₹${formatNumber(netProfit)}`;
            } else {
                profitDisplay.className = 'fw-bold text-danger fs-4';
                profitContainer.className = 'border rounded p-3 bg-danger bg-opacity-10';
                profitDisplay.innerHTML = `<i class="fas fa-arrow-down"></i> ₹${formatNumber(Math.abs(netProfit))}`;
            }
        }


        function formatNumber(num) {
            return new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }

        function highlightCalculatedFields() {
            const autoFields = ['sale_qty', 'total_sale_amount'];

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

        // ============= ORIGINAL FUNCTIONS =============
        function updatePlots() {
            const blockSelect = document.querySelector('select[name="block"]');
            const plotSelect = document.querySelector('select[name="plot"]');
            const areaInput = document.querySelector('input[name="area"]');
            const loadingSpinner = document.getElementById('blockLoadingSpinner');

            const selectedBlockId = blockSelect.value;

            plotSelect.innerHTML = '<option value="">Select Plot</option>';
            areaInput.value = '';

            plotSelect.classList.remove('is-valid', 'is-invalid');

            if (selectedBlockId && plotData[selectedBlockId]) {
                loadingSpinner.style.display = 'block';

                setTimeout(() => {
                    loadingSpinner.style.display = 'none';

                    plotData[selectedBlockId].forEach(plot => {
                        const option = document.createElement('option');
                        option.value = plot.id;
                        option.textContent = plot.plot_name;
                        option.dataset.area = plot.area;
                        plotSelect.appendChild(option);
                    });

                    const oldPlot = '{{ old("plot") }}';
                    if (oldPlot) {
                        plotSelect.value = oldPlot;
                        updateArea();
                    }
                }, 300);
            } else {
                loadingSpinner.style.display = 'none';
            }
        }

        function updateSeedVarieties() {
            const seedSelect = document.querySelector('select[name="seed_name"]');
            const varietySelect = document.querySelector('select[name="seed_variety"]');
            const loadingSpinner = document.getElementById('seedLoadingSpinner');

            const selectedSeedName = seedSelect.value;

            varietySelect.innerHTML = '<option value="">Select Variety</option>';

            varietySelect.classList.remove('is-valid', 'is-invalid');

            if (selectedSeedName && seedData[selectedSeedName]) {
                loadingSpinner.style.display = 'block';

                setTimeout(() => {
                    loadingSpinner.style.display = 'none';

                    seedData[selectedSeedName].forEach(variety => {
                        const option = document.createElement('option');
                        option.value = variety.variety_of_seed;
                        option.textContent = variety.variety_of_seed;
                        varietySelect.appendChild(option);
                    });

                    const oldVariety = '{{ old("seed_variety") }}';
                    if (oldVariety) {
                        varietySelect.value = oldVariety;
                    }
                }, 300);
            } else {
                loadingSpinner.style.display = 'none';
            }
        }

        function updateArea() {
            const plotSelect = document.querySelector('select[name="plot"]');
            const areaInput = document.querySelector('input[name="area"]');

            const selectedOption = plotSelect.options[plotSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.area) {
                areaInput.value = selectedOption.dataset.area;
            } else {
                areaInput.value = '';
            }
        }

        // Legacy function for backward compatibility
        function calculateAmounts() {
            calculateAllAmounts();
        }

        function updateFileName(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                target.textContent = `Selected: ${file.name} (${fileSize}MB)`;

                if (file.size > 5 * 1024 * 1024) {
                    target.textContent += ' - File too large!';
                    target.style.color = '#dc3545';
                    input.value = '';
                } else {
                    target.style.color = '#28a745';
                }
            } else {
                target.textContent = '';
                target.style.color = '';
            }
        }

        // View Record Function
        function viewRecord(id) {
            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            const viewModalBody = document.getElementById('viewModalBody');

            // Show loading
            viewModalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading record details...</p>
                </div>
            `;

            viewModal.show();

            // Fetch record data via AJAX
            fetch(`/contact-farming/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(record => {
                if (record.error) {
                    throw new Error(record.error);
                }

                // Build the view content
                const viewContent = buildViewContent(record);
                viewModalBody.innerHTML = viewContent;
            })
            .catch(error => {
                console.error('Error:', error);
                viewModalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Error loading record details:</strong><br>
                        ${error.message || 'Please try again later.'}
                    </div>
                `;
            });
        }

        function buildViewContent(record) {
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            };

            const formatNumber = (value, decimals = 2) => {
                const num = parseFloat(value);
                return isNaN(num) ? '0.00' : num.toFixed(decimals);
            };

            const formatCurrency = (amount) => {
                const num = parseFloat(amount);
                if (isNaN(num)) return '₹0.00';
                return new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    minimumFractionDigits: 2
                }).format(num);
            };

            // Calculate profit/loss
            const totalProduction = parseFloat(record.production_mt || record.production || 0) * parseFloat(record.production_price_per_mt || record.production_price || 0);
            const totalSale = parseFloat(record.sale_amount || 0);
            const recovery = parseFloat(record.amount_recovery || 0);
            const product_amount = parseFloat(record.product_amount || 0);
             const Profit = totalSale - product_amount;
            let netProfit;
            console.log(Profit);
            if (Profit < 0) {
                netProfit = Profit - recovery;
            } else {
                netProfit = Profit + recovery;
            }
            console.log(netProfit);
           // const profit = totalSale - totalProduction - recovery;

            return `
                <div style="max-height: 600px; overflow-y: auto; padding: 0 15px;">
                    <!-- Record Header -->
                    <div class="text-center mb-4 p-3" style="background: linear-gradient(135deg, #4CAF50, #2E7D32); border-radius: 10px; color: white;">
                        <h4 class="mb-1"><i class="fas fa-file-contract"></i> Contract Record #${record.id}</h4>
                        <small>Contract Farming Details</small>
                    </div>

                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td class="fw-bold text-muted" style="width: 40%;">Block Name:</td>
                                                    <td><span class="badge bg-primary">${record.block_name || 'N/A'}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold text-muted">Plot Name:</td>
                                                    <td><strong>${record.plot_name || 'N/A'}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold text-muted">Area:</td>
                                                    <td><span class="badge bg-warning text-dark">${record.area_hectares || record.plot_area || record.area || 'N/A'} Acre</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td class="fw-bold text-muted" style="width: 40%;">Seed Name:</td>
                                                    <td><strong class="text-success">${record.seed_name || 'N/A'}</strong></td>
                                                </tr>
                                                ${record.seed_variety ? `
                                                <tr>
                                                    <td class="fw-bold text-muted">Seed Variety:</td>
                                                    <td>${record.seed_variety}</td>
                                                </tr>
                                                ` : ''}
                                                <tr>
                                                    <td class="fw-bold text-muted">Contractor:</td>
                                                    <td><strong class="text-primary">${record.contractor_name || 'N/A'}</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Period -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-calendar-alt"></i> Date</h6>
                                </div>
                                <div class="card-body text-center">
                                    ${record.contract_start_date || record.start_date ? `
                                        <div class="d-flex justify-content-center align-items-center">
                                            <div class="text-center">
                                                <div class="badge bg-success fs-6 p-2">
                                                    <i class="fas fa-play"></i> ${formatDate(record.contract_start_date || record.start_date)}
                                                </div>
                                                <div class="small text-muted mt-1">Start Date</div>
                                            </div>
                                            <div class="mx-4">
                                                <i class="fas fa-arrow-right text-primary fs-4"></i>
                                            </div>
                                            <div class="text-center">
                                                <div class="badge bg-danger fs-6 p-2">
                                                    <i class="fas fa-stop"></i> ${formatDate(record.contract_end_date || record.end_date)}
                                                </div>
                                                <div class="small text-muted mt-1">End Date</div>
                                            </div>
                                        </div>
                                    ` : '<span class="text-muted">No date information available</span>'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Production & Sales Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-industry"></i> Production Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold text-muted">Production (MT):</td>
                                            <td><span class="badge bg-success fs-6">${formatNumber(record.production_mt || record.production || 0)} MT</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Price per MT (₹):</td>
                                            <td><strong class="text-success">${formatCurrency(record.production_price_per_mt || record.production_price || 0)}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Total Cost (₹):</td>
                                            <td><strong class="text-primary">${formatCurrency(totalProduction)}</strong></td>
                                        </tr>
                                        ${record.amount_recovery && parseFloat(record.amount_recovery) > 0 ? `
                                        <tr>
                                            <td class="fw-bold text-muted">Recovery (₹):</td>
                                            <td><span class="text-warning">${formatCurrency(record.amount_recovery)}</span></td>
                                        </tr>
                                        ` : ''}
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-shopping-cart"></i> Sales Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold text-muted">Sale Price (₹):</td>
                                            <td><strong class="text-primary">${formatCurrency(record.sale_price_per_mt || record.sale_price || 0)}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Quantity Sold:</td>
                                            <td><span class="badge bg-primary fs-6">${formatNumber(record.quantity_sold_mt || record.quantity_sold || 0)} MT</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Sale Amount (₹):</td>
                                            <td><strong class="text-success fs-5">${formatCurrency(record.sale_amount || 0)}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Buyer:</td>
                                            <td><strong class="text-info">${record.sale_part_to || 'N/A'}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit/Loss Analysis -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header ${netProfit >= 0 ? 'bg-success' : 'bg-danger'} text-white">
                                    <h6 class="mb-0">
                                        <i class="fas ${netProfit >= 0 ? 'fa-chart-line' : 'fa-chart-line-down'}"></i>
                                        Financial Summary
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <div class="text-muted small">Production Cost (₹)</div>
                                                <div class="fw-bold text-warning fs-5">${formatCurrency(totalProduction)}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <div class="text-muted small">Sale Revenue (₹)</div>
                                                <div class="fw-bold text-success fs-5">${formatCurrency(totalSale)}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3">
                                                <div class="text-muted small">Recovery (₹)</div>
                                                <div class="fw-bold text-info fs-5">${formatCurrency(recovery)}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-3 ${Profit > 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10'}">
                                                <div class="text-muted small">Net ${Profit > 0 ? 'Profit' : 'Loss'}  ${netProfit}</div>
                                                <div class="fw-bold ${Profit >= 0 ? 'text-success' : 'text-danger'} fs-4">
                                                    ${Profit > 0 ? '+' : ''}${formatCurrency(Math.abs(netProfit))}
                                                    <i class="fas ${Profit > 0 ? 'fa-arrow-up' : 'fa-arrow-down'} ms-1"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    ${record.handling_loss_mt || record.remark || record.production_attachment || record.sale_attachment ? `
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Additional Information</h6>
                                </div>
                                <div class="card-body">
                                    ${record.handling_loss_mt && parseFloat(record.handling_loss_mt) > 0 ? `
                                    <div class="alert alert-warning">
                                        <strong>Handling Loss:</strong> ${formatNumber(record.handling_loss_mt)} MT
                                    </div>
                                    ` : ''}

                                    ${record.remark ? `
                                    <div class="alert alert-info">
                                        <strong>Remarks:</strong><br>
                                        ${record.remark}
                                    </div>
                                    ` : ''}

                                    ${record.production_attachment || record.sale_attachment ? `
                                    <div class="row">
                                        ${record.production_attachment ? `
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <a href="/storage/${record.production_attachment}" target="_blank" class="btn btn-outline-success">
                                                    <i class="fas fa-file-pdf"></i> View Production Document
                                                </a>
                                            </div>
                                        </div>
                                        ` : ''}
                                        ${record.sale_attachment ? `
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <a href="/storage/${record.sale_attachment}" target="_blank" class="btn btn-outline-primary">
                                                    <i class="fas fa-file-pdf"></i> View Sale Document
                                                </a>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Record Metadata -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-dark text-white">
                                    <h6 class="mb-0"><i class="fas fa-clock"></i> Record Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Created On:</small><br>
                                            <strong>${record.created_at ? formatDate(record.created_at) : 'N/A'}</strong>
                                        </div>
                                        ${record.updated_at && record.updated_at !== record.created_at ? `
                                        <div class="col-md-6">
                                            <small class="text-muted">Last Updated:</small><br>
                                            <strong>${formatDate(record.updated_at)}</strong>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function deleteRecord(id) {
            const deleteForm = document.getElementById('deleteForm');
            deleteForm.action = `/contact-farming/${id}`;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function printModalContent() {
            const content = document.getElementById('viewModalBody').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Contract Farming Record</title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .btn, .action-buttons { display: none !important; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Form submission handler
        document.getElementById('contractForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        });

        // Modal reset handler
        document.getElementById('farmingModal').addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('contractForm');
            form.reset();

            // Reset auto-calculated fields
            document.querySelector('input[name="area"]').value = '';
            document.getElementById('sale_qty').value = '0.00';
            document.getElementById('total_sale_amount').value = '0.00';

            // Reset financial summary
            document.getElementById('production_cost_display').textContent = '₹0.00';
            document.getElementById('recovery_display').textContent = '₹0.00';
            document.getElementById('sale_revenue_display').textContent = '₹0.00';
            document.getElementById('profit_display').textContent = '₹0.00';
            document.getElementById('profit_display').className = 'fw-bold fs-4';
            document.getElementById('profit_container').className = 'border rounded p-3';

            document.getElementById('production-filename').textContent = '';
            document.getElementById('sale-filename').textContent = '';

            document.querySelector('select[name="plot"]').innerHTML = '<option value="">First select a Block</option>';
            document.querySelector('select[name="seed_variety"]').innerHTML = '<option value="">First select a Seed Name</option>';

            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Contract Record';
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    try {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } catch(e) {
                        console.log('Alert already closed or not found');
                    }
                }, 8000);
            });

            // Date validation
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');

            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function() {
                    const startDate = this.value;
                    if (startDate) {
                        endDateInput.min = startDate;
                        if (endDateInput.value && endDateInput.value < startDate) {
                            endDateInput.value = '';
                            endDateInput.classList.add('is-invalid');
                        } else if (endDateInput.value) {
                            endDateInput.classList.remove('is-invalid');
                            endDateInput.classList.add('is-valid');
                        }
                    }
                });
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

                input.addEventListener('input', function() {
                    if (this.value && this.value.trim() !== '') {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });

                input.addEventListener('change', function() {
                    if (this.value && this.value.trim() !== '') {
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

                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && (!this.value || this.value <= 0)) {
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else if (this.value && this.value > 0) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            });

            // Initialize old values
            const oldBlock = '{{ old("block") }}';
            const oldSeed = '{{ old("seed_name") }}';

            if (oldBlock) {
                document.querySelector('select[name="block"]').value = oldBlock;
                setTimeout(() => {
                    updatePlots();
                }, 100);
            }

            if (oldSeed) {
                document.querySelector('select[name="seed_name"]').value = oldSeed;
                setTimeout(() => {
                    updateSeedVarieties();
                }, 100);
            }

            // Calculate amounts if values exist
            const oldQuantity = '{{ old("quantity_sold") }}';
            const oldPrice = '{{ old("sale_price") }}';

            if (oldQuantity && oldPrice) {
                setTimeout(() => {
                    calculateAllAmounts();
                }, 100);
            }

            // Initial calculation
            calculateAllAmounts();
        });

        function scrollToFirstError() {
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstError.focus();
            }
        }

        window.addEventListener('load', function() {
            if (document.querySelector('.is-invalid')) {
                setTimeout(scrollToFirstError, 500);
            }
        });
        
       
    document.getElementById('downloadCsv').addEventListener('click', function() {
        // Redirect to the CSV export route
        window.location.href = "{{ route('contact-farming.export.csv') }}";
    });
    </script>
</body>
</html>
@endsection
