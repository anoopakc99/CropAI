@extends('layouts.app')

@section('title', 'Master Capacities Management')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="contajiner mt-4">
    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#capacityModal" onclick="clearForm()">Add Capacity</button>
    <input type="text" id="searchInput" onkeyup="searchTable()" class="form-control w-25 float-end mb-3" placeholder="Search">

    <table class="table table-bordered mt-3" id="capacityTable">
        <thead class="bg-success text-white">
            <tr>
                <th>Sr.No.</th>
                <th>Capacity Value</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($capacities as $index => $capacity)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $capacity->capacity_value }}</td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#capacityModal"
                        onclick='editCapacity(@json($capacity))' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>

                    <form action="{{ route('capacities.destroy', $capacity->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="capacityModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add / Edit Capacity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="capacityForm" method="POST">
                @csrf
                <input type="hidden" id="capacity_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Capacity Value</label>
                        <input type="text" name="capacity_value" id="capacity_value" class="form-control" placeholder="e.g. 500L, 1000L">
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

<script>
    function editCapacity(capacity) {
        document.getElementById('capacityForm').action = `/capacities/update/${capacity.id}`;
        document.getElementById('capacity_id').value = capacity.id;
        document.getElementById('capacity_value').value = capacity.capacity_value;
    }

    function clearForm() {
        document.getElementById('capacityForm').action = '/capacities/store';
        document.getElementById('capacityForm').reset();
        document.getElementById('capacity_id').value = '';
    }

    function searchTable() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let rows = document.querySelectorAll("#capacityTable tbody tr");
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
