@extends('layouts.app')

@section('title', 'Land Management')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-12">
                <h2>Land Management System</h2>
            </div>
        </div>

        <div class="row mb-3">
              @if(Auth::check() && Auth::user()->role == 1)
                        <div class="col-md-2">
                            <select class="form-select" name="site_id" id="filterSite">
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->site_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
            <div class="col-md-2">
                <form id="filterForm" method="get" action="{{ route('land.index') }}">
                    <select class="form-select" name="block" id="filterBlock">
                        <option value="">Select Block</option>
                        @foreach($blocks as $block)
                            <option value="{{ $block->id }}" {{ request('block') == $block->id ? 'selected' : '' }}>{{ $block->block_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <!-- Modified Year Selector -->
                    <select class="form-select" name="year" id="filterYear">
                        <option value="">Select Year</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}-{{ $year + 1 }}
                            </option>
                        @endforeach
                    </select>
                </div>
               
@if(Auth::check() && Auth::user()->role != 1)
    <div class="col-md-2">
        <button type="button" class="btn-add" id="addLandBtn">Add Land Detail</button>
    </div>
@endif

                <!--<div class="col-md-4 ms-auto">-->
                <!--    <div class="input-group">-->
                <!--        <input type="text" class="form-control" placeholder="Search..." name="search" value="{{ request('search') }}">-->
                <!--        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>-->
                <!--    </div>-->
                <!--</div>-->
            </form>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-bordered" id="myTable">
                        <thead>
                            <tr>
                                <th>Sr.No.</th>
                                <th>Block Name</th> <!-- Added Block Name Column -->
                                <th>Plot No.</th>
                                <th>Area (Acre)</th>
                                <th>Supervisor Name</th>
                                <th>Soil Type</th>
                                <th>Previous Crop</th>
                                <th>Current Crop</th>
                                   @if(Auth::check() && Auth::user()->role != 1)
                                <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lands as $key => $land)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $land->block }}</td> <!-- Added Block Name Data -->
                                    <td>{{ $land->plot }}</td>
                                    <td>{{ $land->area_ha }}</td>
                                    <td>{{ $land->superviser }}</td>
                                    <td>{{ $land->soil_type }}</td>
                                    <td>{{ $land->previous_crop }}</td>
                                    <td>{{ $land->current_crop }}</td>
                                   @if(Auth::check() && Auth::user()->role != 1)
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" data-id="{{ $land->id }}"  data-block_id="{{ $land->block_name }}"
                                            data-plot_id="{{ $land->plot_no }}"
                                            data-supervisor_id="{{ $land->super_wiser_name }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                       
                                    </td>
                                    @endif
                                </tr>
                          
                            @endforeach
                        </tbody>
                    </table>
                </div>

                 
            </div>
        </div>
    </div>

    <div class="modal fade" id="addLandModal" tabindex="-1" aria-labelledby="addLandModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header header">
                    <h5 class="modal-title" id="addLandModalLabel">Add Land Detail</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addLandForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="site_id" class="form-label">Site</label>
                                <select name="site_id" id="site_id" class="form-select" required>
                                    <option value=""> Select Site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" {{ old('site_id', $land->site_id ?? '') == $site->id ? 'selected' : '' }}>
                                            {{ $site->site_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="block_name" class="form-label">Block</label>
                                <select class="form-select" id="block_name" name="block_name" required>
                                    <option value="">Select Block</option>
                                    @foreach($blocks as $block)
                                        <option value="{{ $block->id }}">{{ $block->block_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="plot_no" class="form-label">Plot No.</label>
                                <select class="form-select" id="plot_no" name="plot_no" required disabled>
                                    <option value="">Select Plot</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="area_ha" class="form-label">Area (Acre)</label>
                                <input type="number" step="0.01" class="form-control" id="area_ha" name="area_ha" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="super_wiser_name" class="form-label">Super Wiser Name</label>
                                 
                                <select class="form-select" id="super_wiser_name" name="super_wiser_name">
                                    <option value="">Select Supervisor</option>
                                    @foreach($supervisor as $row)
                                        <option value="{{ $row->id }}">{{ $row->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="soil_type" class="form-label">Soil Type</label>
                                <select class="form-select" id="soil_type" name="soil_type" required>
                                    <option value="">Select Soil</option>
                                    <option value="Clay">Clay</option>
                                    <option value="Sandy">Sandy</option>
                                    <option value="Loamy">Loamy</option>
                                    <option value="Silty">Silty</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="previous_crop" class="form-label">Previous Crop</label>
                                <input type="text" class="form-control" id="previous_crop" name="previous_crop" required>
                            </div>
                            <div class="col-md-4">
                                <label for="current_crop" class="form-label">Current Crop</label>
                                <input type="text" class="form-control" id="current_crop" name="current_crop" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 <div class="modal fade" id="editLandModal" tabindex="-1" aria-labelledby="editLandModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header header">
                    <h5 class="modal-title" id="editLandModalLabel">Edit Land Detail</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editLandForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_land_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="edit_site_id" class="form-label">Site</label>
                                <select name="site_id" id="edit_site_id" class="form-select" required>
                                    <option value=""> Select Site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="edit_block_name" class="form-label">Block</label>
                                <select class="form-select" id="edit_block_name" name="block_name" required>
                                    <option value="">Select Block</option>
                                    @foreach($blocks as $block)
                                        <option value="{{ $block->id }}">{{ $block->block_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_plot_no" class="form-label">Plot No.</label>
                                <select class="form-select" id="edit_plot_no" name="plot_no" required>
                                    <option value="">Select Plot</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_area_ha" class="form-label">Area (Acre)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_area_ha" name="area_ha" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="edit_super_wiser_name" class="form-label">Super Wiser Name</label>
                                 
                                <select class="form-select" id="edit_super_wiser_name" name="super_wiser_name">
                                    <option value="">Select Supervisor</option>
                                    @foreach($supervisor as $row)
                                        <option value="{{ $row->id }}">{{ $row->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_soil_type" class="form-label">Soil Type</label>
                                <select class="form-select" id="edit_soil_type" name="soil_type" required>
                                    <option value="">Select Soil</option>
                                    <option value="Clay">Clay</option>
                                    <option value="Sandy">Sandy</option>
                                    <option value="Loamy">Loamy</option>
                                    <option value="Silty">Silty</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="edit_previous_crop" class="form-label">Previous Crop</label>
                                <input type="text" class="form-control" id="edit_previous_crop" name="previous_crop" required>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_current_crop" class="form-label">Current Crop</label>
                                <input type="text" class="form-control" id="edit_current_crop" name="current_crop" required>
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

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this land record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

     
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
    
    document.addEventListener("DOMContentLoaded", function () {
    // Initialize DataTable
    $('#myTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        responsive: true,
        language: {
            search: "Search Block:"
        }
    });
});
        $(document).ready(function() {
            // Set up CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Filter form submission
            $('#filterBlock, #filterYear').change(function() {
                $('#filterForm').submit();
            });

            // Show Add Land Modal
            $('#addLandBtn').click(function() {
                $('#addLandForm')[0].reset();
                $('#plot_no').prop('disabled', true);
                $('#addLandModal').modal('show');
            });

            // Load plots based on block selection for Add form
            $('#block_name').change(function() {
                var block_id = $(this).val();
                if (block_id) {
                    $.ajax({
                        url: "{{ route('get.plots.by.block') }}",
                        type: "GET",
                        data: { block_id: block_id },
                        success: function(data) {
                            $('#plot_no').empty();
                            $('#plot_no').append('<option value="">Select Plot</option>');
                            $.each(data, function(key, value) {
                                $('#plot_no').append('<option value="' + value.id + '" data-area="' + value.area + '">' + value.plot_name + '</option>');
                            });
                            $('#plot_no').prop('disabled', false);
                        }
                    });
                } else {
                    $('#plot_no').empty();
                    $('#plot_no').append('<option value="">Select Plot</option>');
                    $('#plot_no').prop('disabled', true);
                    $('#area_ha').val('');
                }
            });

            // Auto-fill area when plot is selected in Add form
            $('#plot_no').change(function() {
                var selected_option = $(this).find('option:selected');
                var area = selected_option.data('area');
                $('#area_ha').val(area);
            });

            // Load plots based on block selection for Edit form
            $('#edit_block_name').change(function() {
                var block_name = $(this).val();
                if (block_name) {
                    $.ajax({
                        url: "{{ route('get.plots.by.block') }}",
                        type: "GET",
                        data: { block_name: block_name },
                        success: function(data) {
                            $('#edit_plot_no').empty();
                            $('#edit_plot_no').append('<option value="">Select Plot</option>');
                            $.each(data, function(key, value) {
                                $('#edit_plot_no').append('<option value="' + value.id + '" data-area="' + value.area + '">' + value.plot_name + '</option>');
                            });
                        }
                    });
                } else {
                    $('#edit_plot_no').empty();
                    $('#edit_plot_no').append('<option value="">Select Plot</option>');
                    $('#edit_area_ha').val('');
                }
            });

            // Auto-fill area when plot is selected in Edit form
            $('#edit_plot_no').change(function() {
                var selected_option = $(this).find('option:selected');
                var area = selected_option.data('area');
                $('#edit_area_ha').val(area);
            });

            // Add Land Form Submit
            $('#addLandForm').submit(function(e) {
                e.preventDefault();

                // Clear previous error messages
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    url: "{{ route('land.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addLandModal').modal('hide');
                        alert(response.success);
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#' + key).addClass('is-invalid');
                                $('#' + key).after('<div class="invalid-feedback">' + value[0] + '</div>');
                            });
                        } else if (xhr.status === 419) {
                            alert('CSRF token mismatch. Please refresh the page and try again.');
                        } else {
                            alert('An error occurred: ' + xhr.responseJSON.message);
                        }
                    }
                });
            });

            // Edit Land Button Click
// Edit Land Button Click
$(document).on('click', '.edit-btn', function() {
    var land_id = $(this).data('id');
    var block_id = $(this).data('block_id');
    var plot_id = $(this).data('plot_id');
     

    // Hidden ID field
    $('#edit_land_id').val(land_id);

    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    $.ajax({
        url: "{{ url('land') }}/" + land_id + "/edit",
        type: "GET",
        success: function(response) {
            var land = response.land;

            // Set Site
            $('#edit_site_id').val(land.site_id);
                
            // Set Block
            $('#edit_block_name').val(land.block_name).trigger('change');
            
            // Once block change triggers plot load, select the correct plot
            $(document).one('plotsLoaded', function() {
                $('#edit_plot_no').val(land.plot_no).trigger('change');
            });
            console.log(land.super_wiser_name);
            // Set supervisor
            $('#edit_super_wiser_name').val(land.super_wiser_name).trigger('change');

            // Set area
            $('#edit_area_ha').val(land.area_ha);

            // Soil type
            $('#edit_soil_type').val(land.soil_type);

            // Previous & current crops
            $('#edit_previous_crop').val(land.previous_crop);
            $('#edit_current_crop').val(land.current_crop);

            // Show modal
            $('#editLandModal').modal('show');
        },
        error: function(xhr) {
            if (xhr.status === 419) {
                alert('CSRF token mismatch. Please refresh the page and try again.');
            } else {
                alert('Failed to load land record: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        }
    });
});

$('#edit_block_name').on('change', function() {
    var blockId = $(this).val();
    if (!blockId) {
        $('#edit_plot_no').html('<option value="">Select Plot</option>');
        return;
    }

    $.ajax({
        url: '/get-plots/' + blockId,
        type: 'GET',
        success: function(data) {
            $('#edit_plot_no').empty().append('<option value="">Select Plot</option>');
            $.each(data, function(key, value) {
                $('#edit_plot_no').append(
                    '<option value="' + value.id + '" data-area="' + value.area + '">' + value.plot_name + '</option>'
                );
            });

            // Trigger custom event after plots loaded
            $(document).trigger('plotsLoaded');
        }
    });
});

            // Edit Land Form Submit
            $('#editLandForm').submit(function(e) {
                e.preventDefault();

                var land_id = $('#edit_land_id').val();

                // Clear previous error messages
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    url: "{{ url('land') }}/" + land_id,
                    type: "PUT",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#editLandModal').modal('hide');
                        alert(response.success);
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#edit_' + key).addClass('is-invalid');
                                $('#edit_' + key).after('<div class="invalid-feedback">' + value[0] + '</div>');
                            });
                        } else if (xhr.status === 419) {
                            alert('CSRF token mismatch. Please refresh the page and try again.');
                        } else {
                            alert('An error occurred: ' + xhr.responseJSON.message);
                        }
                    }
                });
            });

            // Delete Land Button Click
            $(document).on('click', '.delete-btn', function() {
                var land_id = $(this).data('id');
                $('#confirmDelete').data('id', land_id);
                $('#deleteModal').modal('show');
            });

            // Confirm Delete Button Click
            $('#confirmDelete').click(function() {
                var land_id = $(this).data('id');

                $.ajax({
                    url: "{{ url('land') }}/" + land_id,
                    type: "DELETE",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        alert(response.success);
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 419) {
                            alert('CSRF token mismatch. Please refresh the page and try again.');
                        } else {
                            alert('Failed to delete land record: ' + xhr.responseJSON.message);
                        }
                    }
                });
            });
        });
    </script>
      <script>
        document.getElementById('filterBlock').addEventListener('change', () => document.getElementById('filterForm').submit());
        document.getElementById('filterYear').addEventListener('change', () => document.getElementById('filterForm').submit());
        const siteSelect = document.getElementById('filterSite');
        if (siteSelect) siteSelect.addEventListener('change', () => document.getElementById('filterForm').submit());
    </script>
@endsection