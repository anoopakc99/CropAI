@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
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

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
    }

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
        white-space: nowrap;
    }

    .table tbody td {
        padding: 5px 8px;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: var(--background-light);
    }

    .btn-group .btn {
        margin: 0 1px;
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

    .badge {
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
    }

    .badge.bg-success {
        background: #28a745 !important;
    }

    .badge.bg-danger {
        background: #dc3545 !important;
    }

    /* View Modal Styles */
    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
    }

    .modal-body {
        padding: 25px;
    }

    .info-group {
        margin-bottom: 25px;
    }

    .info-group h6 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid var(--accent-color);
        padding-bottom: 8px;
    }

    .info-row {
        display: flex;
        margin-bottom: 12px;
    }

    .info-label {
        font-weight: 600;
        color: var(--text-dark);
        min-width: 180px;
    }

    .info-value {
        flex: 1;
    }

    .value-highlight {
        background: var(--background-light);
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
      button.btn.btn-warning.dt-button {
    color: #fff !important;
    background-color: #ffc107 !important;
}
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">



<div class="container-fluid">
    <div class="main-containerr" style="margin-top: 10px">
        <div class="header">
            <h3>
                <i class="fas fa-seedling"></i> Contract Farming </h3>
            <p class="subtitle">Crop-AI Management System</p>

        </div>

        <div class="form-container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="row mb-4">
                 <div class="col-md-6">

                </div>
                <div class="col-md-6" style="text-align: right;">
                    <a href="{{ route('contact-farming.create') }}" class="btn btn-success me-2">
                        <i class="fas fa-plus"></i> Add New Record
                    </a>
                    {{-- <a href="{{ route('contact-farming.export.csv') }}" class="btn btn-primary" id="downloadCsv">
                        <i class="fas fa-download"></i> Export to CSV
                    </a> --}}
                </div>
            </div>

            <!-- Records Table -->
            <div class="records-table">
                {{-- <h4 class="mb-3">
                    <i class="fas fa-table text-success"></i> Contract Farming Records
                </h4> --}}

                <div class="table-responsive">
                    <table class="table table-striped" id="myDataTable">
                        <thead>
                            <tr>
                                <th>Block Name</th>
                                <th>Plot Name</th>
                                <th><center>Seed Name & Variety</center></th>
                                <th>Contractor Name</th>
                                <th>Area (Acre)</th>
                                <th>Production (MT)</th>
                                <th>Amount (Rs)</th>
                                <th>Handling Loss (MT)</th>
                                <th>Sale Quantity (MT)</th>
                                <th>Sale Amount (Rs)</th>
                                <th>Remaining Quantity (MT)</th>
                                <th>Profit/Loss (Rs)</th>
                                <th>Start Date</th>
                                <th>End Date</th>
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
                                        $netProfit = $profit + $record->amount_recovery;
                                    } else {
                                        $class = "badge bg-success";
                                        $netProfit = $profit + $record->amount_recovery;
                                    }
                                ?>
                                    <tr>
                                       <td><strong>{{ $record->block_names ?? 'N/A' }}</strong></td>
                                       <td><small class="text-muted">{{ $record->plot_names ?? 'N/A' }}</small></td>
                                      <td>
                                        <strong>{{ $record->seed_name ?? 'N/A' }}</strong>
                                        @if($record->varieties)
                                            <br><small class="text-muted">({{ $record->varieties }})</small>
                                        @endif
                                        </td>
                                        <td>{{ $record->contractor_name ?? 'N/A' }}</td>
                                        <td>{{ $record->area_hectares ?? $record->plot_area ?? $record->area ?? 'N/A' }}</td>
                                        <td>{{ number_format($record->production_mt ?? $record->production ?? 0, 2) }}</td>

                                        <td>{{ number_format($record->product_amount ?? 0, 2) }}</td>
                                        <td>{{ number_format($record->handling_loss_mt ?? 0, 2) }}</td>
                                        <td>{{ number_format($record->sale_quantity ?? 0, 2) }}</td>
                                        <td>{{ number_format($record->sale_amount ?? 0, 2) }}</td>
                                         <td>{{ number_format($record->production_mt - $record->sale_quantity - $record->handling_loss_mt ?? 0, 2) }}</td>
                                        <td>
                                            <span class="{{ $class }}">
                                                {{ number_format(abs($netProfit), 2) }}
                                            </span>
                                        </td>

                                        <td style="white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($record->contract_start_date)->format('d-m-y') }}
                                        </td>
                                        <td style="white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($record->contract_end_date)->format('d-m-y') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('contact-farming.show', $record->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('contact-farming.destroy', $record->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Are you sure you want to delete this record?')"
                                                      style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                  <td colspan="16" class="text-center">No records found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {{-- @if(isset($records))
                    <div class="d-flex justify-content-center mt-4">
                        {{ $records->links() }}
                    </div>
                @endif --}}
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
@if(isset($records) && $records->count() > 0)
<script>
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
                title: 'contract-farming',
                className: 'btn btn-warning'
            }
        ],

        pageLength: 10,
        responsive: false,  // TURN OFF responsive – your table is already inside .table-responsive
        autoWidth: false
    });
});
</script>
@endif
<script>

    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
 
</script>

@endsection
