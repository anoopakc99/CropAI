@extends('layouts.app')

@section('title', 'Block Management')

@section('content')
<style>
.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 2;
}

.small-stats-card {
    background-color: #28a745;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-add {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-add:hover {
    background-color: #218838;
}
</style>

<div class="containerr mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-end">
                {{-- Admin Site Filter --}}
                @if(Auth::check() && Auth::user()->role == 1)
                <form action="{{ route('master.blocks') }}" method="GET" class="d-flex">
                    <select name="site_id" class="form-select me-2" onchange="this.form.submit()">
                        <option value="">All Sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->site_name }}
                            </option>
                        @endforeach
                    </select>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Block List</h4>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addBlockModal">
            <i class="fas fa-plus"></i> Add Block
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="MyTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Site</th>
                    <th>Created At</th>
                    @if(Auth::check() && Auth::user()->role != 1)
                        <th class="text-center">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($masterBlocks as $block)
                    <tr>
                        <td>{{ $block->id }}</td>
                        <td><strong>{{ $block->block_name }}</strong></td>
                        <td>
                            @php
                                $siteName = DB::table('master_sites')->where('id', $block->site_id)->value('site_name');
                            @endphp
                            {{ $siteName ?? 'N/A' }}
                        </td>
                        <td>{{ \Carbon\Carbon::parse($block->created_at)->format('d-m-Y') }}</td>
                        @if(Auth::check() && Auth::user()->role != 1)
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-warning"
                                    onclick="editBlock(this)"
                                    data-show-url="{{ url('block-show/'.$block->id) }}"
                                    data-update-url="{{ url('block-update/'.$block->id) }}"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No Block found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ================= Add Block Modal ================= -->
    <div class="modal fade" id="addBlockModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('master-block.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Block</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="site_id" class="form-select" required>
                                @if(Auth::check() && Auth::user()->role == 1)
                                    <option value="">Select Site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                    @endforeach
                                @else
                                    @foreach($sites as $site)
                                        @if($site->id == Auth::user()->site_id)
                                            <option value="{{ $site->id }}" selected>{{ $site->site_name }}</option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Block Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="block_name" placeholder="Enter Block name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Plots <span class="text-danger">*</span></label>
                            <div id="addPlotWrapper">
                                <div class="input-group mb-2 flex-wrap gap-1">
                                    <input type="text" name="plots[]" class="form-control" placeholder="Plot Name" required>
                                    <input type="text" name="areas[]" class="form-control" placeholder="Area" required>
                                    <input type="text" name="latitudes[]" class="form-control" placeholder="Latitude" required>
                                    <input type="text" name="longitudes[]" class="form-control" placeholder="Longitude" required>
                                    <button type="button" class="btn btn-success addPlot"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= Edit Block Modal ================= -->
    <div class="modal fade" id="editBlockModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editBlockForm">
                    @csrf
                    <input type="hidden" name="Block_id" id="editBlockId">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Block</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <select name="site_id" id="editSiteSelect" class="form-select" required>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Block Name</label>
                            <input type="text" class="form-control" name="block_name" id="editBlockName" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Block Plots</label>
                            <div id="editPlotWrapper"></div>
                            <button type="button" class="btn btn-sm btn-success mt-2" onclick="addEditPlotInput()">
                                <i class="bi bi-plus"></i> Add Plot
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
function editBlock(buttonElem) {
    const showUrl = buttonElem.getAttribute('data-show-url');
    const updateUrl = buttonElem.getAttribute('data-update-url');

    fetch(showUrl, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        const rec = data.record || data.block || data;
        const id = rec.id;
        const name = rec.block_name;
        const siteId = rec.site_id;

        document.getElementById('editBlockId').value = id;
        document.getElementById('editBlockName').value = name;
        document.getElementById('editSiteSelect').value = siteId;

        const wrapper = document.getElementById('editPlotWrapper');
        wrapper.innerHTML = '';

        const plots = data.plots || [];
        plots.forEach(p => {
            const div = document.createElement('div');
            div.className = 'input-group mb-2 flex-wrap gap-1';
            div.innerHTML = `
                <input type="hidden" name="plot_ids[]" value="${p.id || ''}">
                <input type="text" name="plots[]" class="form-control" value="${p.plot_name || ''}" required>
                <input type="text" name="areas[]" class="form-control" value="${p.area || ''}" required>
                <input type="text" name="latitudes[]" class="form-control" value="${p.lattitude || ''}" >
                <input type="text" name="longitudes[]" class="form-control" value="${p.longitude || ''}" >
               
            `;
            wrapper.appendChild(div);
        });
 // <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">x</button>
        const form = document.getElementById('editBlockForm');
        form.action = updateUrl || `/block-update/${id}`;
        new bootstrap.Modal(document.getElementById('editBlockModal')).show();
    });
}

function addEditPlotInput() {
    const wrapper = document.getElementById('editPlotWrapper');
    const div = document.createElement('div');
    div.className = 'input-group mb-2 flex-wrap gap-1';
    div.innerHTML = `
        <input type="text" name="plots[]" class="form-control" placeholder="Plot Name" required>
        <input type="text" name="areas[]" class="form-control" placeholder="Area" required>
        <input type="text" name="latitudes[]" class="form-control" placeholder="Latitude" >
        <input type="text" name="longitudes[]" class="form-control" placeholder="Longitude">
        <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">x</button>
    `;
    wrapper.appendChild(div);
}

document.addEventListener("DOMContentLoaded", function () {
    $('#MyTable').DataTable();

    let wrapper = document.getElementById("addPlotWrapper");
    wrapper.addEventListener("click", function (e) {
        if (e.target.closest(".addPlot")) {
            let field = document.createElement("div");
            field.classList.add("input-group", "mb-2", "flex-wrap", "gap-1");
            field.innerHTML = `
                <input type="text" name="plots[]" class="form-control" placeholder="Plot Name" required>
                <input type="text" name="areas[]" class="form-control" placeholder="Area" required>
                <input type="text" name="latitudes[]" class="form-control" placeholder="Latitude" required>
                <input type="text" name="longitudes[]" class="form-control" placeholder="Longitude" required>
                <button type="button" class="btn btn-danger removePlot"><i class="bi bi-dash"></i></button>
            `;
            wrapper.appendChild(field);
        }
        if (e.target.closest(".removePlot")) e.target.closest(".input-group").remove();
    });
});
</script>
