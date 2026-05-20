@extends('layouts.app')

@section('title', 'Machine Management')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
 
  <div class="container-fluid mt-4 px-4">
  <div class="card shadow-lg p-4 rounded-4 w-100">
    <div class="card-body">
      <!-- Heading -->
      <h3 class="card-title text-center mb-5 fw-bold text-primary" style="font-size: 2rem;">
        Machine Usage Summary
      </h3>

      <!-- ✅ Three-column layout -->
      <div style="
        display: flex; 
        justify-content: center; 
        align-items: flex-start; 
        gap: 40px; 
        flex-wrap: wrap;
      ">
        
        <!-- 🟢 Left: Pie Chart -->
        <div style="width: 380px; height: 360px; flex-shrink: 0;">
          <canvas id="machineUsageChart"></canvas>
        </div>

        <!-- 🟡 Middle: Scrollable Legends (only used machines) -->
        <div style="flex: 1; max-width: 420px;">
          <h6 class="text-center fw-semibold text-secondary mb-2">Usage Legends</h6>
          <div id="machineLegendContainer"
               style="
                 max-height: 360px; 
                 overflow-y: auto; 
                 overflow-x: hidden; 
                 border: 1px solid #ddd; 
                 border-radius: 10px; 
                 padding: 12px 18px; 
                 background: #fafafa; 
                 box-shadow: inset 0 0 6px rgba(0,0,0,0.08); 
                 scrollbar-width: thin;
               ">
          </div>
        </div>

        <!-- 🔴 Right: Scrollable “No Used Machines” -->
        <div style="flex: 1; max-width: 360px;">
          <h6 class="text-center fw-semibold text-danger mb-2">
            <i class="fas fa-exclamation-circle"></i> Inactive Machines
          </h6>
          <div id="noUsageBox"
               style="
                 max-height: 360px; 
                 overflow-y: auto; 
                 border: 2px dashed #dc3545; 
                 border-radius: 10px; 
                 padding: 12px 16px; 
                 background: #fff5f5; 
                 box-shadow: inset 0 0 6px rgba(0,0,0,0.05);
                 scrollbar-width: thin;
               ">
            <div id="noUsageList" style="font-size: 0.9rem; color: #333;"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var addMachineModal = new bootstrap.Modal(document.getElementById('addMachineModal'));
            addMachineModal.show();
        });
    </script>
@endif

<div class="d-flex justify-content-between align-items-center mt-4" style="margin-right: 20px">
    @if(Auth::check() && Auth::user()->role != 1)
        <button class="btn-add ms-auto" data-bs-toggle="modal" data-bs-target="#addMachineModal">
            Add Machine
        </button>
    @endif
</div>

    <form action="{{ route('machines.index') }}" method="GET" class="d-flex">
        @if(Auth::check() && Auth::user()->role == 1)
            <select name="site_id" class="form-select me-2" onchange="this.form.submit()">
                <option value="">Select Site</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                        {{ $site->site_name }}
                    </option>
                @endforeach
            </select>
        @endif
         
    </form>
</div>


<div class="table-responsive mt-3 table-wrapper myDataTable" style="margin: 5px;">
    <table class="table table-bordered text-center align-middle" id="myDataTable">
        <thead>
            <tr>
                <th>Sr.No.</th>
                <th>Machine Name</th>
                <th>Asset Number</th>
                <th>Location</th>
                <th>Machine Type</th>
                <th>Model No.</th>
                <th>Purchase Date</th>
                <th>Document</th>
                <th>Status</th>
                <th>Service Prev./Upco.</th>
                <th>Machine Image</th>
                   @if(Auth::check() && Auth::user()->role != 1)
                <th>Action</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($machines as $index => $machine)
            <tr>
                <td>{{ ($machines->currentPage() - 1) * $machines->perPage() + $index + 1 }}</td>
                <td>{{ $machine->machine_name }}</td>
                <td>{{ $machine->machine_no }}</td>
                <td>{{ $machine->site_name ?? 'N/A' }}</td>
                <td>{{ $machine->machine_type }}</td>
                <td>{{ $machine->brand_model_no }}</td>
                <td>{{ $machine->purchase_date ? \Carbon\Carbon::parse($machine->purchase_date)->format('d-m-Y') : 'N/A' }}</td>
               <td>
    @if($machine->document_path)
        <a href="{{ asset($machine->document_path) }}" target="_blank" class="btn btn-sm btn-primary">
            View
        </a>
    @else
        <span class="text-muted">N/A</span>
    @endif
</td>

                <td>
                    <span class="badge bg-{{ $machine->status == 'active' ? 'success' : ($machine->status == 'inactive' ? 'danger' : 'warning') }}">
                        {{ ucfirst($machine->status) }}
                    </span>
                </td>
                <td>
                    <small>
                        {{ $machine->last_service_date ? \Carbon\Carbon::parse($machine->last_service_date)->format('d-m-Y') : 'N A' }}
                        <br>/
                        {{ $machine->next_service_date ? \Carbon\Carbon::parse($machine->next_service_date)->format('d-m-Y') : 'N A' }}
                    </small>
                </td>
            <td>
                @if($machine->image_path)
                    <img src="{{ asset($machine->image_path) }}" alt="Machine Image" width="80">
                @else
                    N/A
                @endif
            </td>
               @if(Auth::check() && Auth::user()->role != 1)
                <td>
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-sm btn-warning edit-machine" 
                                data-id="{{ $machine->id }}"
                                data-machine-name="{{ $machine->machine_name }}"
                                data-machine-no="{{ $machine->machine_no }}"
                                data-machine-type="{{ $machine->machine_type }}"
                                data-brand-model-no="{{ $machine->brand_model_no }}"
                                data-purchase-date="{{ $machine->purchase_date }}"
                                data-status="{{ $machine->status }}"
                                data-site-id="{{ $machine->site_id }}"
                                data-last-service-date="{{ $machine->last_service_date }}"
                                data-next-service-date="{{ $machine->next_service_date }}"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('machines.destroy', $machine->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this machine?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-muted">No machines found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
 

{{-- Small Total Machine Count UI --}}
<!--<div class="d-flex justify-content-center mt-2">-->
<!--    <div class="small-stats-card">-->
<!--        <i class="fas fa-cogs"></i>-->
<!--        <span>Total Machineries: <strong>{{ $machines->total() }}</strong></span>-->
<!--    </div>-->
<!--</div>-->

{{-- Add Machine Modal --}}
<div class="modal fade" id="addMachineModal" tabindex="-1" aria-labelledby="addMachineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('machines.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Name <span class="text-danger">*</span></label>
                            <input type="text" name="machine_name" class="form-control" placeholder="Enter machine name" value="{{ old('machine_name') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Number <span class="text-danger">*</span></label>
                            <input type="text" name="machine_no" class="form-control" placeholder="Enter Asset Number" value="{{ old('machine_no') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="site_id" class="form-select" required>
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->site_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Type <span class="text-danger">*</span></label>
                            <input type="text" name="machine_type" class="form-control" placeholder="Enter machine type" value="{{ old('machine_type') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand & Model No. <span class="text-danger">*</span></label>
                            <input type="text" name="brand_model_no" class="form-control" placeholder="Enter brand & model" value="{{ old('brand_model_no') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Service Date</label>
                            <input type="date" name="last_service_date" class="form-control" value="{{ old('last_service_date') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Next Service Date</label>
                            <input type="date" name="next_service_date" class="form-control" value="{{ old('next_service_date') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">Accepted: PDF, DOC, DOCX, JPG, PNG</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Accepted: Image files only</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Machine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Edit Machine Modal --}}
<div class="modal fade" id="editMachineModal" tabindex="-1" aria-labelledby="editMachineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMachineForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Name <span class="text-danger">*</span></label>
                            <input type="text" name="machine_name" id="edit_machine_name" class="form-control" placeholder="Enter machine name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Number <span class="text-danger">*</span></label>
                            <input type="text" name="machine_no" id="edit_machine_no" class="form-control" placeholder="Enter Asset Number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="site_id" id="edit_site_id" class="form-select" required>
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Type <span class="text-danger">*</span></label>
                            <input type="text" name="machine_type" id="edit_machine_type" class="form-control" placeholder="Enter machine type" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand & Model No. <span class="text-danger">*</span></label>
                            <input type="text" name="brand_model_no" id="edit_brand_model_no" class="form-control" placeholder="Enter brand & model" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Under Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Service Date</label>
                            <input type="date" name="last_service_date" id="edit_last_service_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Next Service Date</label>
                            <input type="date" name="next_service_date" id="edit_next_service_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">Leave empty to keep current document</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Machine Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Machine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Chart.js + DataLabels Plugin -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
// PHP data
const machines = {!! json_encode($usageSummary) !!};

// Debug: Log the first machine to see available properties
console.log('Sample machine data:', machines[0]);

// Create combined name for display
// Try multiple possible property names for machine number
const labels = machines.map(m => {
    const machineName = m.machine_name || 'Unknown';
    const machineNo = m.machine_no || m.model_no || m.asset_number || 'N/A';
    return `${machineName} - ${machineNo}`;
});

const rawData = machines.map(m => m.total_usage);

// Calculate percentages
const total = rawData.reduce((sum, v) => sum + v, 0);
const percentData = rawData.map(v => total ? ((v / total) * 100).toFixed(1) : 0);

// Used machines (>0)
const usedMachines = machines.map((m, i) => {
    const machineName = m.machine_name || 'Unknown';
    const machineNo = m.machine_no || m.model_no || m.asset_number || 'N/A';
    return {
        name: `${machineName} - ${machineNo}`,
        value: percentData[i],
        usage: rawData[i]
    };
}).filter(m => Number(m.usage) > 0);

// No-used machines (=0)
const noUsedMachines = machines.map((m, i) => {
    const machineName = m.machine_name || 'Unknown';
    const machineNo = m.machine_no || m.model_no || m.asset_number || 'N/A';
    return {
        name: `${machineName} - ${machineNo}`,
        value: percentData[i],
        usage: rawData[i]
    };
}).filter(m => Number(m.usage) === 0);

// PIE CHART
const ctx = document.getElementById('machineUsageChart').getContext('2d');
const myChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: usedMachines.map(m => m.name),
        datasets: [{
            data: usedMachines.map(m => m.value),
            backgroundColor: [
                '#FF6384','#36A2EB','#FFCD56','#4BC0C0','#9966FF',
                '#FF9F40','#C9CBCF','#FF66B2','#5AD3D1','#FFB347'
            ],
            borderColor: '#fff',
            borderWidth: 2,
            hoverOffset: 20
        }]
    },
    plugins: [ChartDataLabels],
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            datalabels: {
                color: '#fff',
                font: { weight: 'bold', size: 11 },
                formatter: val => val > 0 ? val + '%' : ''
            }
        }
    }
});

// LEGENDS
const legendContainer = document.getElementById('machineLegendContainer');
legendContainer.innerHTML = '';
usedMachines.forEach((m, i) => {
    const color = myChart.data.datasets[0].backgroundColor[i % 10];
    const item = document.createElement('div');
    item.style.display = 'flex';
    item.style.alignItems = 'center';
    item.style.margin = '6px 0';
    item.innerHTML = `
        <div style="width:14px; height:14px; background:${color}; border-radius:3px; margin-right:8px;"></div>
        <span>${m.name}</span>
    `;
    legendContainer.appendChild(item);
});

// NO USED LIST
const noUsageList = document.getElementById('noUsageList');
noUsageList.innerHTML = '';
if (noUsedMachines.length > 0) {
    noUsedMachines.forEach(m => {
        const item = document.createElement('div');
        item.textContent = `• ${m.name}`;
        noUsageList.appendChild(item);
    });
} else {
    noUsageList.innerHTML = `<em>All machines in use.</em>`;
}

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
                title: 'machines',
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