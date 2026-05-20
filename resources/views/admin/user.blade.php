@extends('layouts.app')

@section('content')
<div class="containerr" style="margin-top: 20px;">
    <div class="d-flex justify-content-between mb-3">
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
        <div class="w-25">
            <form action="{{ route('users.index') }}" method="GET">
                <input type="text" name="search" id="search" placeholder="Search by name or email" class="form-control" value="{{ request('search') }}">
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Sr. No.</th>
                <th>User ID</th>
                <th>User Name</th>
                <th>User Type</th>
                <th>Location</th>
                <th>Login Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $index => $user)
            <tr>
                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $index + 1 }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->name }}</td>
                <?php if($user->role == "4"){
                $usertype = "Supervisor";
                }elseif($user->role == "3"){
                $usertype = "Fodder Crop Officer";
                 }elseif($user->role == "2"){
                $usertype = "Admin";
                }else{
                    $usertype = "--";
                }
                ?>
                <td>{{ $usertype }}</td>
                 
                <td>
                    @if($user->site_name)
                        @php
                            $siteIds = explode(',', $user->site_name);
                            $siteNames = [];
                            foreach($siteIds as $siteId) {
                                $site = DB::table('master_sites')->where('id', $siteId)->first();
                                if($site) {
                                    $siteNames[] = $site->site_name;
                                }
                            }
                        @endphp
                        {{ implode(', ', $siteNames) }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    {{ $user->last_login ? \Carbon\Carbon::parse($user->last_login)->format('d-m-Y') : Carbon\Carbon::parse($user->last_activity_at)->format('d-m-Y')}}
                </td>
                <td>{{ $user->status ?? 'Active' }}</td>
                <td class="text-center">
                    <!-- View Button -->
                    <button type="button" class="btn btn-sm btn-success me-1 view-btn" data-id="{{ $user->id }}" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                
                    <!-- Edit Button -->
                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn" data-id="{{ $user->id }}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                     <button type="button" class="btn btn-sm btn-info me-1 change-password-btn" data-id="{{ $user->id }}" title="Change Password">
                        <i class="fas fa-key"></i>
                    </button>
                    <!-- Delete Button (inline form) -->
                    <!--<form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">-->
                    <!--    @csrf-->
                    <!--    @method('DELETE')-->
                    <!--    <button type="submit" class="btn btn-sm btn-danger" title="Delete">-->
                    <!--        <i class="fas fa-trash-alt"></i>-->
                    <!--    </button>-->
                    <!--</form>-->
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $users->links('pagination::bootstrap-5') }}

</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @if(Auth::user()->role == 1)
                        <!-- Sites selection for superadmin - FIRST -->
                        <div class="col-md-6">
                            <label>Sites</label>
                            <select name="site_ids[]" class="form-control" multiple required>
                                @isset($sites)
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                            <small class="text-muted">Hold Ctrl to select multiple sites</small>
                        </div>
                        
                        <!-- Role selection for superadmin -->
                        <div class="col-md-6">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="1">Super Admin</option>
                                <option value="2">Admin</option>
                                <option value="3">Fodder Crop Officer</option>
                                <option value="4">User</option>
                                
                            </select>
                        </div>
                        @else
                        <!-- Single site selection for other roles - FIRST -->
                        <div class="col-md-6">
                            <label>Site Location</label>
                            <select id="site_name" name="site_name" class="form-control" required>
                                <option value="">Select Site</option>
                                @isset($sites)
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-6"></div> <!-- Empty column for spacing -->
                        @endif

                        <!-- User details - SECOND -->
                        <div class="col-md-6">
                            <label>User Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>User ID (Email)</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>User Type</label>
                            <select name="user_type" id="user_type" class="form-control" required>
                                <option value="">Select User Type</option>
                                <option value="3">Fodder Crop Officer</option>
                                <option value="4">Supervisor</option>
                            </select>
                        </div>
                        <div class="col-md-12" id="blocks-section">
                        <label><strong>Select Blocks & Plots</strong> (<small class="text-muted">Hold Ctrl/Cmd to select multiple plots</small>)</label>
                            <div id="blocks-container" class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                @foreach ($blocks as $block)
                                    <div class="mb-2">
                                        <label class="form-check-label">
                                            <input type="checkbox" name="block_ids[]" class="form-check-input block-checkbox" 
                                                   data-block-id="{{ $block->id }}">
                                            {{ $block->block_name }}
                                        </label>
                                        <div class="plots-container ms-4 mt-2" id="plots-{{ $block->id }}" style="display:none;">
                                            <select name="plots[{{ $block->id }}][]" class="form-control plot-select" multiple>
                                                <option value="">Loading plots...</option>
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Check a block to load its plots</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-12">
                            <label>User Name</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label>User ID (Email)</label>
                            <input type="email" id="edit_email" name="email" class="form-control" required>
                        </div>
                         

                        <div class="col-md-12" id="blockplots">
                            <label><strong>Select Blocks & Plots</strong> (<small class="text-muted">Hold Ctrl/Cmd to select multiple plots</small>)</label>
                            <div id="edit-blocks-container" class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                <!-- Blocks + plots will load here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content shadow-lg rounded-3 border-0">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="fas fa-user-circle me-2"></i> User Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- User Info -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <h6 class="text-muted">Basic Information</h6>
            <hr>
            <p><strong>Name:</strong> <span id="view_name" class="badge bg-success text-white"></span></p>
            <p><strong>Email:</strong> <span id="view_email" class="text-primary"></span></p>
            <p><strong>User Type:</strong> 
              <span id="view_type" class="badge bg-info text-dark"></span>
            </p>
          </div>
        </div>
        

        <!-- Blocks & Plots -->
        <div class="card border-0 shadow-sm" id="blocksSection">
          <div class="card-body">
            <h6 class="text-muted">Assigned Blocks & Plots</h6>
            <hr>
            <div id="blocks_container">
                <div class="card-body">
              <ul class="list-group list-group-flush" id="view_blocks">
                <!-- Dynamic content from JS -->
              </ul>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="changePasswordForm">
            @csrf
            <input type="hidden" id="password_user_id" name="user_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Change Password</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    
   $(function () {
    function toggleBlockSection() {
        var userType = $('#user_type').val();
        if (userType === "3") {  
            // Fodder Crop Officer -> hide
            $('#blocks-section').hide();
        } else {
            // Others -> show
            $('#blocks-section').show();
        }
    }

    // Run on page load
    toggleBlockSection();

    // Run on dropdown change
    $('#user_type').on('change', function () {
        toggleBlockSection();
    });
});

    // On block checkbox change
    $('.block-checkbox').on('change', function () {
        let blockId = $(this).data('block-id');
        let container = $('#plots-' + blockId);

        if ($(this).is(':checked')) {
            container.show();

            // Load plots via AJAX
            $.ajax({
                url: '/get-plots-by-block',
                type: 'GET',
                data: { block_id: blockId },
                success: function (data) {
                    let options = '';
                    if (data && data.length > 0) {
                        data.forEach(function (plot) {
                            options += `<option value="${plot.id}" data-area="${plot.area}">
                                            ${plot.plot_name} (${plot.area} Acre)
                                        </option>`;
                        });
                    } else {
                        options = '<option value="">No plots available</option>';
                    }
                    container.find('select').html(options);
                },
                error: function () {
                    container.find('select').html('<option value="">Error loading plots</option>');
                }
            });
        } else {
            container.hide();
            container.find('select').val('');
            calculateTotalArea();
        }
    });

    // Recalculate area whenever plots are selected
    $(document).on('change', '.plot-select', function () {
        calculateTotalArea();
    });

  
});

$(document).ready(function () {

    // When clicking edit button
  $(document).on('click', '.edit-btn', function () {
    let userId = $(this).data('id');

    $.ajax({
        url: '/users/' + userId + '/edit',
        type: 'GET',
        success: function (response) {
            // Fill user data
            $('#edit_name').val(response.user.name);
            $('#edit_email').val(response.user.email);
            $('#edit_user_type').val(response.user.user_type);

            // Show/hide blocks & plots based on user type
            if (response.user.role == 2 || response.user.role == 3) {
                // Hide blocks & plots for Supervisor
                $('#blockplots').hide();
            } else {
                $('#blockplots').show();
                
                // Reset blocks container
                $('#edit-blocks-container').html('');

                // Loop blocks
                response.blocks.forEach(function (block) {
                    let checked = response.user_blocks.includes(block.id) ? 'checked' : '';
                    let plotsHtml = '';

                    // Loop plots for this block
                    if (response.plots[block.id]) {
                        response.plots[block.id].forEach(function (plot) {
                            let selected = response.user_plots.includes(plot.id) ? 'selected' : '';
                            plotsHtml += `<option value="${plot.id}" data-area="${plot.area}" ${selected}>
                                            ${plot.plot_name} (${plot.area} Acre)
                                          </option>`;
                        });
                    }

                    $('#edit-blocks-container').append(`
                        <div class="mb-2">
                            <label>
                                <input type="checkbox" name="block_ids[]" value="${block.id}" 
                                    class="form-check-input edit-block-checkbox" 
                                    data-block-id="${block.id}" ${checked}>
                                ${block.block_name}
                            </label>

                            <div class="plots-container ms-4 mt-2" id="edit-plots-${block.id}" style="display:${checked ? 'block':'none'};">
                                <select name="plots[${block.id}][]" class="form-control edit-plot-select" multiple>
                                    ${plotsHtml}
                                </select>
                            </div>
                        </div>
                    `);
                });

               
            }

            // Show modal
            $('#editUserModal').modal('show');

            // Set form action dynamically
            $('#editUserForm').attr('action', '/users/' + userId);
        }
    });
});


    // Show/hide plots when block checkbox changes
    $(document).on('change', '.edit-block-checkbox', function () {
        let blockId = $(this).data('block-id');
        let container = $('#edit-plots-' + blockId);
        if ($(this).is(':checked')) {
            container.show();
        } else {
            container.hide();
            container.find('select').val('');
            calculateEditTotalArea();
        }
    });
 
});

$(document).on('click', '.view-btn', function () {
    var userId = $(this).data('id');

    $.ajax({
        url: '/users/show/' + userId,
        type: 'GET',
        success: function (res) {
            if (res.success) {
                $('#view_name').text(res.data.name);
                $('#view_email').text(res.data.email);

                // Convert user_type into readable label
                let userTypeText = '';
                switch (res.data.user_type) {
                    case 1: userTypeText = 'Super Admin'; break;
                    case 2: userTypeText = 'Admin'; break;
                    case 3: userTypeText = 'Fodder Crop Officer'; break;
                    case 4: userTypeText = 'Supervisor'; break;
                    default: userTypeText = 'Unknown';
                }
                $('#view_type').text(res.data.user_type);

                // Clear old blocks
                $('#view_blocks').empty();

                // Show blocks/plots only for Supervisor (role 4)
                if (res.data.role == 4) {
                    if (res.data.blocks.length > 0) {
                        $.each(res.data.blocks, function (i, block) {
                            let plotsHtml = '';
                            $.each(block.plots, function (j, plot) {
                                plotsHtml += `<span class="badge bg-info me-1 mb-1">${plot.plot_name}</span>`;
                            });

                            $('#view_blocks').append(`
                                <div class="card shadow-sm mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="mb-2 text-success">
                                            <i class="fas fa-th-large me-1"></i> ${block.block_name}
                                        </h6>
                                        <div>${plotsHtml || '<span class="text-muted">No plots assigned</span>'}</div>
                                    </div>
                                </div>
                            `);
                        });
                    } else {
                        $('#view_blocks').append('<p class="text-muted">No blocks assigned</p>');
                    }
                    $('#blocksSection').show(); // show section
                } else {
                    $('#blocksSection').hide(); // hide section if not Supervisor
                }

                $('#viewUserModal').modal('show');
            } else {
                alert('User not found!');
            }
        },
        error: function () {
            alert('Something went wrong.');
        }
    });
});

// Open Change Password Modal
$(document).on('click', '.change-password-btn', function() {
    var userId = $(this).data('id');
    $('#password_user_id').val(userId);
    $('#changePasswordModal').modal('show');
});

// Submit Change Password Form
$('#changePasswordForm').submit(function(e) {
    e.preventDefault();

    var formData = $(this).serialize();

    $.ajax({
        url: '/users/change-password',  // Route
        type: 'POST',
        data: formData,
        success: function(res) {
            if(res.success) {
                alert(res.message);
                $('#changePasswordModal').modal('hide');
                $('#changePasswordForm')[0].reset();
            } else {
                alert(res.message);
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let msg = '';
            $.each(errors, function(key, value) { msg += value + '\n'; });
            alert(msg);
        }
    });
});

</script>
@endsection