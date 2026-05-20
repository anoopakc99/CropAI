@extends('layouts.app')

@section('title', 'Manpower Management')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" id="success-alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" id="error-alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between mb-3">
    @if(Auth::check() && Auth::user()->role != 1)
        <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#manpowerModal" onclick="resetForm()">
            Add Man Power
        </button>
    @else
        <div></div>
    @endif

    <div class="d-flex align-items-center gap-2">
        @if(Auth::check() && Auth::user()->role == 1)
            <form method="GET" action="{{ route('master.manpower') }}" class="d-flex align-items-center gap-2">
                <select name="site_filter" class="form-select" style="width: 200px;" onchange="this.form.submit()">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ request('site_filter') == $site->id ? 'selected' : '' }}>
                            {{ $site->site_name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="search" value="{{ $search ?? '' }}">
            </form>
        @endif

        <form method="GET" action="{{ route('master.manpower') }}" class="d-flex">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search..." class="form-control me-2" style="width:200px">
            @if(Auth::check() && Auth::user()->role == 1)
                <input type="hidden" name="site_filter" value="{{ request('site_filter') }}">
            @endif
            <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered text-center">
        <thead class="table-success">
            <tr>
                <th>Sr.No.</th>
                <th>Category of Man Power</th>
                <th>Type of Man Power</th>
                <th>No. of Person</th>
                <th>Wages (Rs)</th>
                @if(Auth::check() && Auth::user()->role == 1)
                    <th>Location</th>
                @endif
                @if(Auth::check() && Auth::user()->role != 1)
                    <th>Action</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($manPowers as $index => $manpower)
                <tr>
                    <td>{{ $manPowers->firstItem() + $index }}</td>
                    <td>{{ $manpower->category }}</td>
                    <td>{{ $manpower->type_name }}</td>
                    <td>{{ $manpower->no_of_person }}</td>
                    <td>{{ number_format($manpower->rate, 2) }}</td>
                    @if(Auth::check() && Auth::user()->role == 1)
                        <td>{{ $manpower->site_name ?? 'N/A' }}</td>
                    @endif
                    @if(Auth::check() && Auth::user()->role != 1)
                        <td>
                            <button class="btn btn-sm btn-warning me-1" onclick="editManPower({{ $manpower->id }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!--<button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('master.manpower.delete', $manpower->id) }}')">-->
                            <!--    <i class="fas fa-trash-alt"></i>-->
                            <!--</button>-->
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No manpower records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $manPowers->appends(request()->query())->links() }}

<!-- Modal -->
<div class="modal fade" id="manpowerModal" tabindex="-1" aria-labelledby="manpowerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form method="POST" id="manpowerForm" action="{{ route('master.manpower.store') }}">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-content p-4 rounded shadow">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 id="modalTitle">Add Man Power</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="row g-3">
                         <div class="col-md-6">
                            <label class="form-label">Type of Man Power <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category of ManPower <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                 
                                    <option value="Unskilled">Unskilled</option>
                                    <option value="Skilled">Skilled</option>
                                    <option value="Semi Skilled 1">Semi Skilled 1</option>
                                    <option value="Semi Skilled 2">Semi Skilled 2</option>
                                    <option value="Semi Skilled 3">Semi Skilled 3</option>
                                
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            @php
                                $userSiteName = DB::table('master_sites')->where('id', Auth::user()->site_id)->value('site_name');
                            @endphp
                            @if(Auth::check() && Auth::user()->role != 1)
                                <input type="text" class="form-control" value="{{ $userSiteName ?? 'Unknown Site' }}" readonly>
                                <input type="hidden" name="site_id" id="site_id" value="{{ Auth::user()->site_id }}">
                            @else
                                <select name="site_id" id="site_id" class="form-select" required>
                                    <option value="">Select Site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                            {{ $site->site_name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                       

                        <div class="col-md-4">
                            <label class="form-label">No. of Person <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_person" class="form-control" required min="1">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Price (Rs) <span class="text-danger">*</span></label>
                            <input type="number" name="rate" class="form-control" step="0.01" required min="0">
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success">Submit</button>
                        <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this entry?
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    function editManPower(id) {
        fetch(`/master-manpower/edit/${id}`)
            .then(res => res.json())
            .then(data => {
                const form = document.getElementById('manpowerForm');
                form.action = `/master-manpower/update/${id}`;
                form.querySelector('[name=category]').value = data.category;
                form.querySelector('[name=type]').value = data.type;
                form.querySelector('[name=no_of_person]').value = data.no_of_person;
                form.querySelector('[name=rate]').value = data.rate;
                form.querySelector('[name=site_id]').value = data.site_id;
                document.getElementById('modalTitle').innerText = "Edit Man Power";
                new bootstrap.Modal(document.getElementById('manpowerModal')).show();
            });
    }

    function resetForm() {
        const form = document.getElementById('manpowerForm');
        form.reset();
        form.action = "{{ route('master.manpower.store') }}";
        document.getElementById('modalTitle').innerText = "Add Man Power";
    }

    function confirmDelete(url) {
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.setAttribute('action', url);
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            ['success-alert', 'error-alert'].forEach(id => {
                let alert = document.getElementById(id);
                if (alert) {
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 3000);
    });
</script>
