@extends('layouts.app')
@section('title', 'Master Water Sources Management')


@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="contajiner mt-4">

    @if(Auth::check() && Auth::user()->role != 1)
   <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-success btn-add" 
            data-bs-toggle="modal" 
            data-bs-target="#waterSourceModal" 
            onclick="clearForm()" style="background-color: #6b8e23;">
        Add Water Source
    </button>
</div>
    @endif

    @if(Auth::check() && Auth::user()->role == 1)
    <form method="GET" action="{{ route('water_sources.index') }}" class="mb-3 w-25 float-start">
        <select name="site_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Filter by Site --</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                    {{ $site->site_name }}
                </option>
            @endforeach
        </select>
    </form>
    @endif

    <!--<input type="text" id="searchInput" onkeyup="searchTable()" class="form-control w-25 float-end mb-3" placeholder="Search">-->

    <table class="table table-bordered" id="waterSourceTable">
        <thead class="bg-success text-white">
            <tr>
                <th>Sr.No.</th>
                <th>Borewell No</th>
                <th>Motor(HP)</th>
                <th>Borewell Type</th>
                <th>Plot No.</th>
                @if(Auth::check() && Auth::user()->role != 1)
                <th>Action</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($water_sources as $index => $source)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $source->borewell_no }}</td>
                <td>{{ $source->capacity_lph }}</td>
                <td>{{ $source->name }}</td>
                <td>{{ $source->plot_name ? $source->plot_name : '-' }}</td>
                @if(Auth::check() && Auth::user()->role != 1)
                <td>
                    <!-- Edit Button -->
                    <button
                        class="btn btn-sm btn-warning me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#waterSourceModal"
                        onclick='editWaterSource(@json($source))'
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>

                    <!-- Delete Button -->
                    <form
                        action="{{ route('water_sources.destroy', $source->id) }}"
                        method="POST"
                        class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Are you sure?')"
                            title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="waterSourceModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add / Edit Water Source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="waterSourceForm" method="POST">
                @csrf
                <input type="hidden" id="water_source_id" name="id">

                <div class="modal-body">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        @if(Auth::check() && Auth::user()->role == 1)
                        <select name="site_id" id="modal_site_id" class="form-select" required>
                            <option value="">Select Site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->site_name }}
                                </option>
                            @endforeach
                        </select>
                        @else
                        <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                        <input type="text" class="form-control" value="{{ $sites->where('id', Auth::user()->site_id)->first()->site_name ?? 'My Site' }}" readonly>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Borewell No</label>
                        <input type="text" name="borewell_no" id="borewell_no" class="form-control" placeholder="e.g. BW001, BW002" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motor HP</label>
                        <input type="number" step="0.01" name="capacity_lph" id="capacity_lph" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Source Name</label>
                        <input type="text" name="name" id="source_name" class="form-control" placeholder="e.g. Borewell, Canal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plot Name</label>
                        <input type="text" name="plot_name" id="plot_name" class="form-control" placeholder="e.g. Plot-1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Power Consumption (KW)</label>
                        <input type="number" step="0.01" name="power_consumption_kw" id="power_consumption_kw" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cost Per Unit</label>
                        <input type="number" step="0.01" name="cost_per_unit" id="cost_per_unit" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional info..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- DataTables CSS -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script>

   $(document).ready(function() {

    // Remove Bootstrap's table-responsive wrapper effect for DataTable
    $('#waterSourceTable').DataTable({
        dom: "<'row mb-3'<'col-sm-6'B><'col-sm-6'f>>" +  // Export left, Search right
             "rt" + 
             "<'row mt-3'<'col-sm-6'i><'col-sm-6'p>>",  // Info left, Pagination right

        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                title: 'Water Source',
                className: 'btn btn-warning'
            }
        ],

        pageLength: 10,
        responsive: false,  // TURN OFF responsive – your table is already inside .table-responsive
        autoWidth: false
    });
});
 
function editWaterSource(source) {
    document.getElementById('waterSourceForm').action = `/water_sources/update/${source.id}`;
    document.getElementById('water_source_id').value = source.id || '';
    document.getElementById('borewell_no').value = source.borewell_no || '';
    document.getElementById('capacity_lph').value = source.capacity_lph !== null ? String(source.capacity_lph) : '';
    document.getElementById('source_name').value = source.name || '';
    document.getElementById('plot_name').value = source.plot_name || '';

    // ✅ Ensure decimals show up
    document.getElementById('power_consumption_kw').value = source.power_consumption_kw !== null ? String(source.power_consumption_kw) : '';
    document.getElementById('cost_per_unit').value = source.cost_per_unit !== null ? String(source.cost_per_unit) : '';

    document.getElementById('notes').value = source.notes || '';
    document.getElementById('is_active').value = String(source.is_active ?? 1); // force to string
    if (document.getElementById('modal_site_id')) {
        document.getElementById('modal_site_id').value = source.site_id || '';
    }
}

function clearForm() {
    document.getElementById('waterSourceForm').action = '/water_sources/store';
    document.getElementById('waterSourceForm').reset();
    document.getElementById('water_source_id').value = '';
}

function searchTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#waterSourceTable tbody tr");
    rows.forEach(row => {
        let cells = row.getElementsByTagName("td");
        let match = false;
        for (let i = 0; i < cells.length; i++) {
            if (cells[i].innerText.toLowerCase().includes(input)) {
                match = true;
                break;
            }
        }
        row.style.display = match ? "" : "none";
    });
}
</script>
@endsection
