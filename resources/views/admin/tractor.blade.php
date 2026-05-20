@extends('layouts.app')

@section('title', 'Tractor Management')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

 
@section('content')
<style>
.table-wrapper {
    max-height: 1100px;
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa; /* Light background to stand out */
    z-index: 2;
}

.small-stats-card {
    background-color: #6b8e23;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(107, 142, 35, 0.3);
}

.small-stats-card i {
    font-size: 16px;
}
   button.btn.btn-warning.dt-button {
    color: #fff !important;
}
</style>
<div class="containerr mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            @if(Auth::check() && Auth::user()->role != 1)
            <button class="btn-add" onclick="openModal()">Add Tractor</button>
            @endif
        </div>
        
        <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-end">
                {{-- Admin Site Filter - Only visible for role 1 --}}
                @if(Auth::check() && Auth::user()->role == 1)
                <form action="{{ route('tractors.index') }}" method="GET" class="d-flex">
                    <select name="site_id" class="form-select me-2" onchange="this.form.submit()">
                        <option value="">All Sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->site_name }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Preserve search query when filtering by site --}}
                    @if(request('query'))
                        <input type="hidden" name="query" value="{{ request('query') }}">
                    @endif
                </form>
                @endif
               
            </div>
        </div>
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
    
    <div class="table-wrapper">
        <table class="table table-bordered" id="myDataTable">
            <thead class="">
                <tr>
                    <th>Sr. No.</th>
                    <th>Tractor Name</th>
                    <th>Asset No.</th>
                    <th>Tractor Type</th>
                    <th>Brand & Model No.</th>
                    <th>Purchase Date</th>
                    {{-- Show Site column only for admin --}}
                    @if(Auth::check() && Auth::user()->role == 1)
                    <th>Location</th>
                    @endif
                    <th>Document</th>
                    <th>Status</th>
                    <th>Service Previous / Upcoming</th>
                    <th>Tractor Image</th>
                    @if(Auth::check() && Auth::user()->role != 1)
                    <th>Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($tractors as $index => $tractor)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $tractor->tractor_name }}</td>
                        <td>{{ $tractor->tractor_no  }}</td>
                        <td>{{ $tractor->tractor_type }}</td>
                        <td>{{ $tractor->brand_model }}</td>
                        <td>{{ date('d-m-Y', strtotime($tractor->purchase_date)) }}</td>
                        {{-- Show Site column only for admin --}}
                        @if(Auth::check() && Auth::user()->role == 1)
                        <td>{{ $tractor->site_name ?? 'N/A' }}</td>
                        @endif
                        <td>
                            @if ($tractor->upload_document)
                                <a href="{{ asset('tractor/documents/' . $tractor->upload_document) }}" target="_blank">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $tractor->machine_status }}</td>
                        <td>
                            {{ $tractor->service_prev ? date('d-m-Y', strtotime($tractor->service_prev)) : 'N/A' }} /
                            {{ $tractor->service_upco ? date('d-m-Y', strtotime($tractor->service_upco)) : 'N/A' }}
                        </td>
                        <td>
                            @if ($tractor->tractor_image)
                                <img src="{{ asset('tractor/images/' . $tractor->tractor_image) }}" alt="Tractor" width="50" height="50" class="img-thumbnail">
                            @else
                                <span class="text-muted">No Image</span>
                            @endif
                        </td>
                        @if(Auth::check() && Auth::user()->role != 1)
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                <button class="btn btn-sm btn-warning" onclick="editTractor({{ $tractor->id }})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <form action="{{ route('tractors.destroy', $tractor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tractor?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ Auth::check() && Auth::user()->role == 1 ? '11' : '10' }}" class="text-center">No tractors found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Small Total Tractor Count UI --}}
    <!--<div class="d-flex justify-content-center mt-4">-->
    <!--    <div class="small-stats-card">-->
    <!--        <i class="fas fa-tractor"></i>-->
    <!--        <span>Total Tractors: <strong>{{ $tractors->count() }}</strong></span>-->
    <!--    </div>-->
    <!--</div>-->
</div>

<!-- Modal Form -->
<div class="modal fade" id="tractorModal" tabindex="-1" aria-labelledby="tractorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tractorModalLabel">Add / Edit Tractor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <form id="tractorForm" method="POST" action="{{ route('tractors.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="tractor_id" id="tractorId">

    <div class="mb-3">
        <label for="tractorName" class="form-label">Tractor Name With Tractor No</label>
        <input type="text" class="form-control" name="tractor_name" id="tractorName"
               placeholder="Please enter tractor name with tractor No." required>
    </div>
    <div class="mb-3">
        <label for="tractorName" class="form-label">Asset Number</label>
        <input type="text" class="form-control" name="asset_no" id="tractor_no"
               placeholder="Please enter Asset No." required>
    </div>

@php
    $userSite = Auth::user()->site_id;
@endphp

<div class="col-md-6 mb-3">
    <label class="form-label">Location <span class="text-danger">*</span></label>
    <select name="site_id" class="form-select" readonly>
        @foreach($sites as $site)
            @if($site->id == $userSite)
                <option value="{{ $site->id }}" selected>{{ $site->site_name }}</option>
            @endif
        @endforeach
    </select>
</div>

    <div class="mb-3">
        <label for="tractorType" class="form-label">Tractor Type</label>
        <input type="text" class="form-control" name="tractor_type" id="tractorType"
               placeholder="Enter tractor type">
    </div>

    <div class="mb-3">
        <label for="brandModel" class="form-label">Brand & Model No.</label>
        <input type="text" class="form-control" name="brand_model" id="brandModel"
               placeholder="Enter brand and model number">
    </div>

    <div class="mb-3">
        <label for="purchaseDate" class="form-label">Purchase Date</label>
        <input type="date" class="form-control" name="purchase_date" id="purchaseDate"
               placeholder="Select purchase date">
    </div>

    <div class="mb-3">
        <label for="uploadDocument" class="form-label">Document (PDF/DOC)</label>
        <input type="file" class="form-control" name="upload_document" id="uploadDocument"
               placeholder="Upload tractor document">
        <div id="documentPreview" class="mt-2"></div>
    </div>

    <div class="mb-3">
        <label for="machineStatus" class="form-label">Status</label>
        <input type="text" class="form-control" name="machine_status" id="machineStatus"
               placeholder="Enter current status" required>
    </div>

    <div class="mb-3">
        <label for="servicePrevious" class="form-label">Previous Service Date</label>
        <input type="date" class="form-control" name="service_prev" id="servicePrevious"
               placeholder="Select previous service date">
    </div>

    <div class="mb-3">
        <label for="serviceUpcoming" class="form-label">Upcoming Service Date</label>
        <input type="date" class="form-control" name="service_upco" id="serviceUpcoming"
               placeholder="Select upcoming service date">
    </div>

    <div class="mb-3">
        <label for="tractorImage" class="form-label">Tractor Image</label>
        <input type="file" class="form-control" name="tractor_image" id="tractorImage"
               placeholder="Upload tractor image">
        <div id="imagePreview" class="mt-2"></div>
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-success">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

            </div>
        </div>
    </div>
</div>
@endsection
 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#myDataTable').DataTable({
        dom: "<'row mb-3'<'col-sm-6'B><'col-sm-6'f>>" +
             "rt" + 
             "<'row mt-3'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                title: 'tractor-excel',
                className: 'btn btn-warning'
            }
        ],
        pageLength: 10,
        responsive: false,
        autoWidth: false
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Initialize Bootstrap modal variable
var tractorModal;

// Open modal for adding new tractor
function openModal() {
    // Initialize modal if not already done
    if (!tractorModal) {
        tractorModal = new bootstrap.Modal(document.getElementById('tractorModal'));
    }
    
    // Reset form
    document.getElementById('tractorForm').reset();
    document.getElementById('tractorId').value = '';
    document.getElementById('documentPreview').innerHTML = '';
    document.getElementById('imagePreview').innerHTML = '';
    
    // Remove any existing _method field
    const existingMethodField = document.querySelector('#tractorForm input[name="_method"]');
    if (existingMethodField) {
        existingMethodField.remove();
    }
    
    // Set form action for adding
    document.getElementById('tractorForm').action = "{{ route('tractors.store') }}";
    document.getElementById('tractorModalLabel').textContent = 'Add New Tractor';
    
    // Show modal
    tractorModal.show();
}

// Open modal for editing tractor
function editTractor(id) {
    // Initialize modal if not already done
    if (!tractorModal) {
        tractorModal = new bootstrap.Modal(document.getElementById('tractorModal'));
    }
    
    // Fetch tractor data
    fetch(`/api/tractors/${id}`)
        .then(response => response.json())
        .then(data => {
            // Fill form with tractor data
            document.getElementById('tractorId').value = data.id ?? '';
            document.getElementById('tractorName').value = data.tractor_name ?? '';
            document.getElementById('tractor_no').value = data.asset_no ?? '';
            document.getElementById('tractorType').value = data.tractor_type ?? '';
            document.getElementById('brandModel').value = data.brand_model ?? '';
            document.getElementById('purchaseDate').value = data.purchase_date ?? '';
            document.getElementById('machineStatus').value = data.machine_status ?? '';
            document.getElementById('servicePrevious').value = data.service_prev ?? '';
            document.getElementById('serviceUpcoming').value = data.service_upco ?? '';
            
            // Show document preview if exists
            if (data.upload_document) {
                document.getElementById('documentPreview').innerHTML = 
                    `<p>Current document: <a href="/tractor/documents/${data.upload_document}" target="_blank">${data.upload_document}</a></p>`;
            } else {
                document.getElementById('documentPreview').innerHTML = '';
            }
            
            // Show image preview if exists
            if (data.tractor_image) {
                document.getElementById('imagePreview').innerHTML = 
                    `<p>Current image:</p><img src="/tractor/images/${data.tractor_image}" alt="Tractor" width="100" class="img-thumbnail">`;
            } else {
                document.getElementById('imagePreview').innerHTML = '';
            }
            
            // Set form action for updating
            document.getElementById('tractorForm').action = `/master/tractor/update/${data.id}`;
            document.getElementById('tractorForm').method = 'POST';
            
            // Add or update method field
            let methodField = document.querySelector('#tractorForm input[name="_method"]');
            if (methodField) {
                methodField.value = 'POST';
            } else {
                methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'POST';
                document.getElementById('tractorForm').appendChild(methodField);
            }
            
            document.getElementById('tractorModalLabel').textContent = 'Edit Tractor';
            
            // Show modal
            tractorModal.show();
        })
        .catch(error => {
            console.error('Error fetching tractor data:', error);
            alert('Failed to load tractor data. Please try again.');
        });
}
</script>