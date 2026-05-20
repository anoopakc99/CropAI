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
            padding: 25px;
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

        .form-container {
            padding: 30px;
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
            padding: 12px 30px;
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
            margin-top: 30px;
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="main-containerr" style="margin-top: 19px;">
             
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

                
 
                <!-- View Record Modal -->
                <div class="modal fade view-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="viewModalLabel">
                                    <i class="fas fa-eye"></i> Crop Cycle Record Details
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
                        <i class="fas fa-table text-success"></i> Crop Analysis Closure
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="myTable">
                            <thead>
                                <tr>
                                    <th>Sowing Start Date</th>
                                    <th>Sowing End Date</th>
                                    <th>Block </th>
                                    <th>Plot </th>
                                    <th><center>Seed Name</center></th>
                                    <th>Closed Date</th>
                                    <th>Harvest Start Date</th>
                                    <th>Harvest End Date</th>
                                    <th>Yield (MT)</th>
                                    <th>Status</th>
                                    <th>Supervisor</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($records) && $records->count() > 0)
                                    @foreach($records as $record)
                                   
                                        <tr>
                                            <td style="white-space: nowrap;">
                                                {{ \Carbon\Carbon::parse($record->first_sowing_date)->format('d-m-Y') ?? '--' }}
                                            </td>
                                            <td style="white-space: nowrap;">
                                                {{ \Carbon\Carbon::parse($record->last_sowing_date)->format('d-m-Y') ?? '--' }} 
                                            </td>
                                            <td><strong>{{ $record->block_name ?? 'N/A' }}</strong></td>
                                            <td><small class="text-muted">{{ $record->plot_name ?? 'N/A' }}</small></td>
                                            <td>
                                                <strong>{{ $record->seed_name ?? 'N/A' }}</strong>
                                            </td>
                                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($record->closed_date)->format('d-m-Y') ?? '--' }} </td>
                                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($record->first_harvest_date)->format('d-m-Y') ?? '--' }}  </td>
                                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($record->last_harvest_date)->format('d-m-Y') ?? '--' }} </td>
                                            <td>{{ $record->total_yield ?? '--' }}</td>
                                            <td>{{ $record->status ?? '--' }}</td>
                                            <td>{{ $record->user_name }}</td>
                                            
                                             </td>
                                            
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button onclick="viewRecord({{ $record->id }})" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                      @if($record->status == 'Active')
                                                        <button onclick="closeCycle({{ $record->id }})" class="btn btn-sm btn-outline-success" title="Close Cycle">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                  
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
 
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
     
    $('#myTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50],
        "order": [[0, "desc"]], // Default sort by ID desc
         
    });
});
 
        function viewRecord(id) {
        // Loading spinner
        $("#viewModalBody").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading details...</p>
            </div>
        `);
        $("#viewModal").modal('show');
    
        $.ajax({
            url: `/crop-cycle/${id}/details`,
            type: 'GET',
            success: function(response) {
                let cycle = response.cycle;
                let sowing = response.sowing;
                let harvesting = response.harvesting;
    
                let sowingRows = '';
                sowing.forEach((row, i) => {
                    sowingRows += `
                        <tr>
                            <td>${i+1}</td>
                            <td>${row.date}</td>
                            <td>${row.area_used ?? '--'}</td>
                            <td>${row.seed_quantity ?? '--'} </td>
                        </tr>
                    `;
                });
    
                let harvestRows = '';
                harvesting.forEach((row, i) => {
                    harvestRows += `
                        <tr>
                            <td>${i+1}</td>
                            <td>${row.date}</td>
                            <td>${row.area_used ?? '--'}</td>
                            <td>${row.yield_mt ?? '--'} </td>
                           
                        </tr>
                    `;
                });
    
                let html = `
                    <h5 class="mb-3">Cycle Information</h5>
                    <span><strong>Seed:</strong> ${cycle.seed_name}</span>
                    <span><strong>Block:</strong> ${cycle.block_name} | <strong>Plot:</strong> ${cycle.plot_name}</span>
                    <span><strong>Supervisor:</strong> ${cycle.user_name}</span>
    
                    <hr>
                    <h6>Sowing Details</h6>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr><th>#</th><th>Date</th><th>Area Covered (Acre)</th><th>Seed Qty (Kg)</th></tr>
                        </thead>
                        <tbody>${sowingRows || `<tr><td colspan="4" class="text-center">No sowing records found</td></tr>`}</tbody>
                    </table>
    
                    <h6>Harvesting Details</h6>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr><th>#</th><th>Date</th><th>Area Covered (Acre)</th><th>Yield (MT)</th></tr>
                        </thead>
                        <tbody>${harvestRows || `<tr><td colspan="4" class="text-center">No harvesting records found</td></tr>`}</tbody>
                    </table>
                `;
    
                $("#viewModalBody").html(html);
            },
            error: function() {
                $("#viewModalBody").html("<p class='text-danger'>Failed to load details.</p>");
            }
        });
    }
    
    function closeCycle(id) {
    if (!confirm("Are you sure you want to close this crop cycle?")) {
        return;
    }

    $.ajax({
        url: `/crop-cycle/${id}/close`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            alert(response.message);
            location.reload(); // refresh table
        },
        error: function(xhr) {
            alert("Error: " + (xhr.responseJSON?.message ?? 'Something went wrong'));
        }
    });
}
 
    </script>
    
   
</body>
</html>
@endsection