@extends('layouts.app')

@section('title', 'Fertilzer Management')

@section('content')

@php
$fields = [
    'fertilizer_name' => 'Fertilzer Name',
    'fertilizer_type' => 'Fertilzer Type',
    'brand_name' => 'Brand Name',
    'supplier_name' => 'Supplier Name',
    'uom' => 'UOM',
];
@endphp

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
.btn-add {
    color: white;
    background-color: #6b8e23;
    padding: 8px 14px;
    font-weight: 600;
    border-radius: 8px;
    font-size: 14px;
    border: none;
}

.modal-content {
    border-radius: 12px;
    padding: 20px;
}

table thead {
    background-color: #6b8e23;
    color: white;
}

table tbody tr:nth-child(even) {
    background-color: #f0f4e6;
}

table tfoot {
    background-color: #e9ecef;
    font-weight: bold;
}

.form-label {
    font-weight: 500;
}

.filter-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(Auth::check() && Auth::user()->role == 1)
<div class="filter-section">
    <form method="GET" action="{{ route('master-fertilizer.index') }}">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Site</label>
                <select name="site_id" class="form-select">
                    <option value="">-- All Sites --</option>
                    @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                        {{ $site->site_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-filter"></i> Filter
                </button>
                {{-- <a href="{{ route('fertilizer.index') }}" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a> --}}
            </div>
        </div>
    </form>
</div>
@endif

<div class="d-flex align-items-center mt-4">
    @if(Auth::check() && Auth::user()->role != 1)
    <form method="GET" action="{{ route('master-fertilizer.index') }}" class="input-group shadow-sm rounded-pill w-25">
        <input type="text" name="search" class="form-control border-0" placeholder="Search..." value="{{ request('search') }}">
        <button class="btn btn-light border-0" type="submit">
            <i class="fas fa-search text-secondary"></i>
        </button>
    </form>
    @endif

    @if(Auth::check() && Auth::user()->role != 1)
    <button class="btn-add ms-auto" data-bs-toggle="modal" data-bs-target="#addfertilizerModal">
        <i class="fas fa-plus"></i> Add Fertilzer
    </button>
    @endif
</div>

<div class="table-responsive mt-4">
    <table class="table table-bordered text-center align-middle">
        <thead>
            <tr>
                <th class="text-center">Sr.No.</th>
                @if(Auth::check() && Auth::user()->role == 1)
                <th class="text-center">Location</th>
                @endif
                <th class="text-center">Fertilzer Name</th>

                @if(Auth::check() && Auth::user()->role != 1)
                <th class="text-center">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($fertilizers as $index => $fertilizer)
            <tr>
                <td>{{ $index + 1 }}</td>
                @if(Auth::check() && Auth::user()->role == 1)
                <td>{{ $fertilizer->site_name }}</td>
                @endif
                <td>{{ $fertilizer->fertilizer_name }}</td>

                @if(Auth::check() && Auth::user()->role != 1)
                <td class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm" style="background-color: #FFD700; color: black;" data-bs-toggle="modal" data-bs-target="#editfertilizerModal{{ $fertilizer->id }}">
                        <i class="fas fa-edit"></i>
                    </button>


                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::check() && Auth::user()->role == 1 ? '13' : '12' }}">No Fertilzers found.</td>
            </tr>
            @endforelse
        </tbody>

    </table>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    {{ $paginatedfertilizers->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>

<!-- Add fertilizer Modal -->
@if(Auth::check() && Auth::user()->role != 1)
<div class="modal fade" id="addfertilizerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('master-fertilizer.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Fertilzer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @if(Auth::user()->role == 1)
                        <div class="col-md-12">
                            <label class="form-label">Location</label>
                            <select name="site_id" class="form-select" required>
                                <option value="">-- Select Site --</option>
                                @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-md-12">
                            <label class="form-label">Location</label>
                            @php
                            $userSite = $sites->where('id', Auth::user()->site_id)->first();
                            @endphp
                            <input type="text" class="form-control" value="{{ $userSite ? $userSite->site_name : 'N/A' }}" readonly>
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                        </div>
                        @endif
                        <div class="col-md-12">
                            <label class="form-label">Fertilzer Name</label>
                            <input type="text" class="form-control" name="fertilizer_name" required>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-add">Add Fertilzer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Edit Modal -->
@if(Auth::check() && Auth::user()->role != 1)
@foreach($paginatedfertilizers as $fertilizer)
<div class="modal fade" id="editfertilizerModal{{ $fertilizer->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('fertilizer-master.update', $fertilizer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fertilzer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @if(Auth::user()->role == 1)
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <select name="site_id" class="form-select" required>
                                <option value="">-- Select Site --</option>
                                @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ $fertilizer->site_id == $site->id ? 'selected' : '' }}>{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-md-12">
                            <label class="form-label">Location</label>
                            @php
                            $userSite = $sites->where('id', Auth::user()->site_id)->first();
                            @endphp
                            <input type="text" class="form-control" value="{{ $userSite ? $userSite->site_name : 'N/A' }}" readonly>
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                        </div>
                        @endif
                        <div class="col-md-12">
                            <label class="form-label">Fertilzer name</label>
                            <input type="text" class="form-control" name="fertilizer_name" value="{{ $fertilizer->fertilizer_name }}" required>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Update Fertilzer</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endforeach
@endif



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

</script>

@endsection
