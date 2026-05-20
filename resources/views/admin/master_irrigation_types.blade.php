@extends('layouts.app')

@section('title', 'Master Irrigation Types Management')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="containNNNer mt-4">
    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#irrigationModal" onclick="clearForm()">Add Irrigation Type</button>
    <input type="text" id="searchInput" onkeyup="searchTable()" class="form-control w-25 float-end" placeholder="Search">

    <table class="table table-bordered mt-3" id="irrigationTable">
        <thead class="bg-success text-white">
            <tr>
                <th>Sr.No.</th>
                <th>Type Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($master_irrigation_types as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->type_name }}</td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#irrigationModal"
                        onclick='editIrrigation(@json($item))' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>

                    <form action="{{ route('master_irrigation_types.destroy', $item->id) }}" method="POST" class="d-inline">
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
<div class="modal fade" id="irrigationModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add / Edit Irrigation Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="irrigationForm" method="POST">
                @csrf
                <input type="hidden" id="irrigation_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" id="type_name" class="form-control" placeholder="Enter Type Name" required>
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
    function editIrrigation(item) {
        document.getElementById('irrigationForm').action = `/master_irrigation_types/update/${item.id}`;
        document.getElementById('irrigation_id').value = item.id;
        document.getElementById('type_name').value = item.type_name;
    }

    function clearForm() {
        document.getElementById('irrigationForm').action = '/master_irrigation_types/store';
        document.getElementById('irrigationForm').reset();
        document.getElementById('irrigation_id').value = '';
    }

    function searchTable() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let rows = document.querySelectorAll("#irrigationTable tbody tr");
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
