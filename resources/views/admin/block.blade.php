@extends('layouts.app')

@section('title', 'Block')

@section('content')


@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>                                                                                                                                                                                                                                                                         
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif


<div class="contaiiner">
    <div class="row mb-3">
        <div class="col-md-6">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#blocModal">+ Add New</button>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" id="searchBox" class="form-control me-2" placeholder="Search..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="blocTable">
                    @foreach ($masterBlocs as $bloc)
                    <tr>
                        <td>{{ $bloc->id }}</td>
                        <td>{{ $bloc->name }}</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editModal({{ $bloc->id }})">Edit</button>
                            <form action="{{ route('master_bloc.destroy', $bloc->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $masterBlocs->links() }}
        </div>
    </div>
</div>


<div class="modal fade" id="blocModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg- text-">
                <h5 class="modal-title" id="modalTitle">Add Master Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="blocForm">
                    @csrf
                    <input type="hidden" id="blocId">
                    <div class="mb-3">
                        <label class="form-label">Name:</label>
                        <input type="text" id="blocName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save</button>
                </form>
            </div>`
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById("blocForm").reset();
    document.getElementById("blocId").value = "";
    document.getElementById("modalTitle").innerText = "Add Master Bloc";
    var myModal = new bootstrap.Modal(document.getElementById('blocModal'));
    myModal.show();
}

function editModal(id) {
    fetch(`/master-bloc/edit/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById("blocId").value = data.id;
            document.getElementById("blocName").value = data.name;
            document.getElementById("modalTitle").innerText = "Edit Master Bloc";
            var myModal = new bootstrap.Modal(document.getElementById('blocModal'));
            myModal.show();
        });
}

document.getElementById("blocForm").addEventListener("submit", function(event) {
    event.preventDefault();
    let id = document.getElementById("blocId").value;
    let name = document.getElementById("blocName").value;
    let url = id ? `/master-bloc/update/${id}` : `/master-bloc/store`;
    
    fetch(url, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value, "Content-Type": "application/json" },
        body: JSON.stringify({ name: name })
    })
    .then(() => location.reload());
});
</script>
@endsection
