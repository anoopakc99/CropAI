@extends('layouts.app')

@section('content')
<div class="conCCtainer">
    <div class="row mb-3">
        <div class="col-md-6">
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addSeedModal">Add Seed Stock</button>
        </div>
        <div class="col-md-6">
            <form action="{{ route('seeds.search') }}" method="GET" class="float-end">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="bg-success text-white">
                <tr>
                    <th>Sr. No.</th>
                    <th>Seed Name</th>
                    <th>Production Type</th>
                    <th>Variety of Seed</th>
                    <th>Purpose</th>
                    <th>Sowing Method</th>
                    <th>Type of Packing</th>
                    <th>Packing Size</th>
                    <th>Date of Packing</th>
                    <th>Seed Stock</th>
                    <th>Rate of Seed</th>
                    <th>Location</th>
                    <th>UOM</th>
                    <th>Amount (Rs.)</th>
                    <th>Remark</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seeds as $index => $seed)
                    <tr class="{{ $index % 2 == 0 ? 'table-light' : 'table-success' }}">
                        <td>{{ ($seeds->currentPage() - 1) * $seeds->perPage() + $loop->iteration }}</td>
                        <td>{{ $seed->seed_name }}</td>
                        <td>{{ $seed->production_type }}</td>
                        <td>{{ $seed->variety_of_seed }}</td>
                        <td>{{ $seed->purpose }}</td>
                        <td>{{ $seed->sowing_method }}</td>
                        <td>{{ $seed->type_of_packing }}</td>
                        <td>{{ $seed->packing_size }}</td>
                        <td>{{ date('d-m-Y', strtotime($seed->date_of_packing)) }}</td>
                        <td>{{ $seed->seed_stock_kg }}</td>
                        <td>{{ $seed->rate_of_seed }}</td>
                        <td>{{ $seed->location }}</td>
                        <td>{{ $seed->uom }}</td>
                        <td>{{ number_format($seed->seed_stock_kg * $seed->rate_of_seed, 2) }}</td>
                        <td>{{ $seed->remark }}</td>
                        <td>
    <button class="btn btn-sm p-0 border-0 bg-transparent text-primary edit-seed"
            title="Edit"
            data-id="{{ $seed->id }}"
            data-seed-name="{{ $seed->seed_name }}"
            data-variety="{{ $seed->variety_of_seed }}"
            data-purpose="{{ $seed->purpose }}"
            data-sowing-method="{{ $seed->sowing_method }}"
            data-type-packing="{{ $seed->type_of_packing }}"
            data-packing-size="{{ $seed->packing_size }}"
            data-date-packing="{{ $seed->date_of_packing }}"
            data-seed-stock-kg="{{ $seed->seed_stock_kg }}"
            data-rate="{{ $seed->rate_of_seed }}"
            data-location="{{ $seed->location }}"
            data-remark="{{ $seed->remark }}"
            data-uom="{{ $seed->uom }}"
            data-production-type="{{ $seed->production_type }}"
            data-sowing-source="{{ $seed->sowing_source }}">
        <i class="fa fa-edit"></i>
    </button>

    <button class="btn btn-sm p-0 border-0 bg-transparent text-danger delete-seed"
            title="Delete"
            data-id="{{ $seed->id }}">
        <i class="fa fa-trash"></i>
    </button>
</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center">No seeds found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-center">
    {{ $seeds->links('pagination::bootstrap-5') }}
</div>

</div>

<!-- Add Seed Modal -->
<div class="modal fade" id="addSeedModal" tabindex="-1" aria-labelledby="addSeedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSeedModalLabel">Add Seed Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('seeds.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="seed_name" class="form-label">Seed Name</label>
                            <input type="text" class="form-control" id="seed_name" name="seed_name" placeholder="Seed Name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="variety_of_seed" class="form-label">Variety of Seed</label>
                            <input type="text" class="form-control" id="variety_of_seed" name="variety_of_seed" placeholder="Variety of Seed" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="purpose" class="form-label">Purpose</label>
                            <select class="form-select" id="purpose" name="purpose">
                                <option value="" selected disabled>Select</option>
                                <option value="Fodder Crop">Fodder Crop</option>
                                <option value="Cash Crop">Cash Crop</option>
                                <option value="Seed Crop">Seed Crop</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="sowing_method" class="form-label">Sowing Method</label>
                            <input type="text" class="form-control" id="sowing_method" name="sowing_method" placeholder="Sowing Method">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="type_of_packing" class="form-label">Type of Packing</label>
                            <input type="text" class="form-control" id="type_of_packing" name="type_of_packing" placeholder="Type of Packing" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="packing_size" class="form-label">Packing Size</label>
                            <input type="text" class="form-control" id="packing_size" name="packing_size" placeholder="Packing Size" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="date_of_packing" class="form-label">Date of Packing</label>
                            <input type="date" class="form-control" id="date_of_packing" name="date_of_packing" placeholder="dd-mm-yyyy" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="seed_stock_kg" class="form-label">Seed Stock (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="seed_stock_kg" name="seed_stock_kg" placeholder="Seed Stock (kg)" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="rate_of_seed" class="form-label">Rate of Seed</label>
                            <input type="number" step="0.01" class="form-control" id="rate_of_seed" name="rate_of_seed" placeholder="Rate of Seed" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="Location" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <input type="text" class="form-control" id="remark" name="remark" placeholder="Remark">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="uom" class="form-label">UOM</label>
                            <input type="text" class="form-control" id="uom" name="uom" placeholder="Unit of Measurement" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="production_type" class="form-label">Production Type</label>
                            <select class="form-select" id="production_type" name="production_type" required>
                                <option value="" disabled selected>Select</option>
                                <option value="Own Production">Own Production</option>
                                <option value="Purchase">Purchase</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sowing_source" class="form-label">Sowing Source</label>
                            <input type="text" class="form-control" id="sowing_source" name="sowing_source" placeholder="Sowing Source">
                        </div>
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

<!-- Edit Seed Modal -->
<div class="modal fade" id="editSeedModal" tabindex="-1" aria-labelledby="editSeedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSeedModalLabel">Edit Seed Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSeedForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_seed_name" class="form-label">Seed Name</label>
                            <input type="text" class="form-control" id="edit_seed_name" name="seed_name" placeholder="Seed Name" required disabled>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_variety_of_seed" class="form-label">Variety of Seed</label>
                            <input type="text" class="form-control" id="edit_variety_of_seed" name="variety_of_seed" placeholder="Variety of Seed" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_purpose" class="form-label">Purpose</label>
                            <select class="form-select" id="edit_purpose" name="purpose">
                                <option value="" selected disabled>Select</option>
                                <option value="Fodder Crop">Fodder Crop</option>
                                <option value="Cash Crop">Cash Crop</option>
                                <option value="Seed Crop">Seed Crop</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_sowing_method" class="form-label">Sowing Method</label>
                            <input type="text" class="form-control" id="edit_sowing_method" name="sowing_method" placeholder="Sowing Method">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_type_of_packing" class="form-label">Type of Packing</label>
                            <input type="text" class="form-control" id="edit_type_of_packing" name="type_of_packing" placeholder="Type of Packing" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_packing_size" class="form-label">Packing Size</label>
                            <input type="text" class="form-control" id="edit_packing_size" name="packing_size" placeholder="Packing Size" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_date_of_packing" class="form-label">Date of Packing</label>
                            <input type="date" class="form-control" id="edit_date_of_packing" name="date_of_packing" placeholder="dd-mm-yyyy" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_seed_stock_kg" class="form-label">Seed Stock (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_seed_stock_kg" name="seed_stock_kg" placeholder="Seed Stock (kg)" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_rate_of_seed" class="form-label">Rate of Seed</label>
                            <input type="number" step="0.01" class="form-control" id="edit_rate_of_seed" name="rate_of_seed" placeholder="Rate of Seed" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location" placeholder="Location" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_remark" class="form-label">Remark</label>
                            <input type="text" class="form-control" id="edit_remark" name="remark" placeholder="Remark">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_uom" class="form-label">UOM</label>
                            <input type="text" class="form-control" id="edit_uom" name="uom" placeholder="Unit of Measurement" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_production_type" class="form-label">Production Type</label>
                            <select class="form-select" id="edit_production_type" name="production_type" required>
                                <option value="" disabled>Select</option>
                                <option value="Own Production">Own Production</option>
                                <option value="Purchase">Purchase</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_sowing_source" class="form-label">Sowing Source</label>
                            <input type="text" class="form-control" id="edit_sowing_source" name="sowing_source" placeholder="Sowing Source">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSeedModal" tabindex="-1" aria-labelledby="deleteSeedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSeedModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this seed?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSeedForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit seed
        const editButtons = document.querySelectorAll('.edit-seed');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const seedName = this.getAttribute('data-seed-name');
                const variety = this.getAttribute('data-variety');
                const purpose = this.getAttribute('data-purpose');
                const sowingMethod = this.getAttribute('data-sowing-method');
                const typePacking = this.getAttribute('data-type-packing');
                const packingSize = this.getAttribute('data-packing-size');
                const datePacking = this.getAttribute('data-date-packing');
                const seedStockKg = this.getAttribute('data-seed-stock-kg');
                const rate = this.getAttribute('data-rate');
                const location = this.getAttribute('data-location');
                const remark = this.getAttribute('data-remark');
                const uom = this.getAttribute('data-uom');
                const productionType = this.getAttribute('data-production-type');
                const sowingSource = this.getAttribute('data-sowing-source');
                
                document.getElementById('edit_seed_name').value = seedName;
                document.getElementById('edit_variety_of_seed').value = variety;
                document.getElementById('edit_purpose').value = purpose;
                document.getElementById('edit_sowing_method').value = sowingMethod;
                document.getElementById('edit_type_of_packing').value = typePacking;
                document.getElementById('edit_packing_size').value = packingSize;
                document.getElementById('edit_date_of_packing').value = datePacking;
                document.getElementById('edit_seed_stock_kg').value = seedStockKg;
                document.getElementById('edit_rate_of_seed').value = rate;
                document.getElementById('edit_location').value = location;
                document.getElementById('edit_remark').value = remark;
                document.getElementById('edit_uom').value = uom;
                document.getElementById('edit_production_type').value = productionType;
                document.getElementById('edit_sowing_source').value = sowingSource;
                
                document.getElementById('editSeedForm').action = `/seeds/${id}`;
                
                const editModal = new bootstrap.Modal(document.getElementById('editSeedModal'));
                editModal.show();
            });
        });
        
        // Delete seed
        const deleteButtons = document.querySelectorAll('.delete-seed');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('deleteSeedForm').action = `/seeds/${id}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteSeedModal'));
                deleteModal.show();
            });
        });
    });
</script>
@endsection