@extends('layouts.app')

@section('title', 'Seed Management')

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

.small-stats-card i {
    font-size: 16px;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
</style>

<div class="containerr mt-4">
    <div class="row mb-3">

        <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-end">
                {{-- Admin Site Filter - Only visible for role 1 --}}
                @if(Auth::check() && Auth::user()->role == 1)
                <form action="{{ route('master.seed.index') }}" method="GET" class="d-flex">
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

                {{-- Search Form --}}

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Seed List</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSeedModal">
        <i class="fas fa-plus"></i> Add Seed
    </button>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-center">ID</th>
                <th class="text-center">Name</th>
                <th class="text-center">Status</th>
                <th class="text-center">Site</th>
                <th class="text-center">Created At</th>
                   @if(Auth::check() && Auth::user()->role != 1)
                <th class="text-center">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($mseeds as $seed)
                <tr>
                    <td>{{ $seed->id }}</td>
                    <td>{{ $seed->name }}</td>
                    <td>
                        <span class="badge bg-{{ $seed->status == 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($seed->status) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $siteName = DB::table('master_sites')->where('id', $seed->site_id)->value('site_name');
                        @endphp
                        {{ $siteName ?? 'N/A' }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($seed->created_at)->format('d-m-Y') }}</td>
                     @if(Auth::check() && Auth::user()->role != 1)
                    <td class="text-center">

                        <div class="d-flex justify-content-center gap-1">
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning" onclick="editSeed({{ $seed->id }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <!-- Delete -->
                            <!--<form action="{{ route('seed.destroy', $seed->id) }}" method="POST" -->
                            <!--      onsubmit="return confirm('Are you sure you want to delete this seed?')" class="d-inline">-->
                            <!--    @csrf-->
                            <!--    @method('DELETE')-->
                            <!--    <button class="btn btn-sm btn-danger" type="submit" title="Delete">-->
                            <!--        <i class="fas fa-trash-alt"></i>-->
                            <!--    </button>-->
                            <!--</form>-->
                        </div>

                    </td>
                      @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No seeds found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- ================= Add Seed Modal ================= -->
<div class="modal fade" id="addSeedModal" tabindex="-1" aria-labelledby="addSeedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form method="POST" action="{{ route('seed.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addSeedModalLabel">Add Seed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Location --}}
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

                    {{-- Seed Name --}}
                    <div class="mb-3">
                        <label class="form-label">Seed Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="seed_name" placeholder="Enter seed name" required>
                    </div>

                    {{-- Varieties --}}
                    <div class="mb-3">
                        <label class="form-label">Seed Varieties <span class="text-danger">*</span></label>
                        <div id="addVarietyWrapper">
                            <div class="input-group mb-2">
                                <input type="text" name="varieties[]" class="form-control" placeholder="Enter variety" required>
                                <button type="button" class="btn btn-success addVariety"><i class="bi bi-plus"></i></button>
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

<!-- ================= Edit Seed Modal ================= -->
<div class="modal fade" id="editSeedModal" tabindex="-1" aria-labelledby="editSeedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form method="POST" id="editSeedForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="seed_id" id="editSeedId">

                <div class="modal-header">
                    <h5 class="modal-title" id="editSeedModalLabel">Edit Seed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Location --}}
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="site_id" id="editSiteSelect" class="form-select" required>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Seed Name --}}
                    <div class="mb-3">
                        <label class="form-label">Seed Name</label>
                        <input type="text" class="form-control" name="seed_name" id="editSeedName" required>
                    </div>

                    {{-- Varieties --}}
                    <div class="mb-3">
                        <label class="form-label">Seed Varieties</label>
                        <div id="editVarietyWrapper"></div>
                        <button type="button" class="btn btn-sm btn-success mt-2" onclick="addEditVarietyInput()">
                            <i class="bi bi-plus"></i> Add Variety
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


 
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script>
   document.addEventListener("DOMContentLoaded", function () {
        let wrapper = document.getElementById("addVarietyWrapper");

        // Add variety input
        wrapper.addEventListener("click", function (e) {
            if (e.target.closest(".addVariety")) {
                let field = document.createElement("div");
                field.classList.add("input-group", "mb-2");
                field.innerHTML = `
                    <input type="text" name="varieties[]" class="form-control" placeholder="Enter variety" required>
                    <button type="button" class="btn btn-danger removeVariety"><i class="bi bi-dash"></i></button>
                `;
                wrapper.appendChild(field);
            }

            // Remove variety input
            if (e.target.closest(".removeVariety")) {
                e.target.closest(".input-group").remove();
            }
        });
    });
function editSeed(id, url = null) {
    // If you prefer blade-generated URL, pass it as second argument from button.
    const fetchUrl = url || `/seed-show/${id}`;

    fetch(fetchUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // helps Laravel know it's AJAX
        },
        credentials: 'same-origin' // include session cookies
    })
    .then(async response => {
        const text = await response.text(); // read raw response
        // try to parse JSON, otherwise print HTML for debugging
        try {
            const data = JSON.parse(text);

            if (!response.ok) {
                console.error('Server returned non-OK status', response.status, data);
                alert('Error loading seed data (check console).');
                return;
            }

            if (data.record) {
                // fill edit modal fields (example)
                document.getElementById('editSeedId').value = data.record.id;
                document.getElementById('editSeedName').value = data.record.name || '';
                document.getElementById('editSiteSelect').value = data.record.site_id || '';


                // fill varieties
                let wrapper = document.getElementById('editVarietyWrapper');
                wrapper.innerHTML = '';
                if (data.varieties && data.varieties.length) {
                    data.varieties.forEach(v => {
                        const div = document.createElement('div');
                        div.className = 'input-group mb-2';
                        div.innerHTML = `
                            <input type="text" name="varieties[]" class="form-control" value="${(v.variety_name||'').replace(/"/g, '&quot;')}" required>
                             
                        `;
                        wrapper.appendChild(div);
                    });
                } else {
                    wrapper.innerHTML = `
                        <div class="input-group mb-2">
                            <input type="text" name="varieties[]" class="form-control" placeholder="Enter variety" required>
                            <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">x</button>
                        </div>
                    `;
                }

                // set form action
                document.getElementById('editSeedForm').action = `/seed-update/${data.record.id}`;

                // show modal
                new bootstrap.Modal(document.getElementById('editSeedModal')).show();
            } else {
                alert('Seed not found');
            }
        } catch (err) {
            // response was not JSON (probably HTML). Log full response to console for debugging.
            console.error('Expected JSON but got:', text);
            alert('Error loading seed data — server returned HTML or an error. Check console/network tab.');
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Network error while loading seed data. Check console.');
    });
}


    function addEditVarietyInput() {
        let wrapper = document.getElementById('editVarietyWrapper');

        // create new div
        let div = document.createElement('div');
        div.className = 'input-group mb-2';

        div.innerHTML = `
            <input type="text" name="varieties[]" class="form-control" placeholder="Enter variety" required>
            <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">x</button>
        `;

        wrapper.appendChild(div); // append instead of replacing innerHTML
    }


    // Helper function to show alerts
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Insert alert at the top of the container
        const container = document.querySelector('.containerr');
        container.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }

    // Auto-dismiss existing alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
 

@endsection
