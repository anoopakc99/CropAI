@extends('layouts.app')

@section('title', 'Diesel Management')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
.btn-add {
    color: white;
    background-color: #28a745;
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
    background-color: #28a745;
    color: white;
}

table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.form-label {
    font-weight: 500;
}
</style>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert" id="successAlert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

 
<!-- Add/Edit Modal -->
<div class="modal fade" id="dieselModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add / Edit Diesel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dieselForm" method="POST">
                @csrf
                <input type="hidden" id="diesel_id" name="id">
                <div class="modal-body">
                    <!--<div class="mb-3">-->
                    <!--    <label class="form-label">Type</label>-->
                    <!--    <select name="type" id="type" class="form-select" required>-->
                    <!--        <option value="">Select Type</option>-->
                    <!--        <option value="in">In (Purchase)</option>-->
                    <!--        <option value="out">Out (Consumption)</option>-->
                    <!--    </select>-->
                    <!--</div>-->
                      @php
                        $user = Auth::user();
                        $userSite = $sites->firstWhere('id', $user->site_id);
                    @endphp
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" value="{{ $userSite->site_name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="site_id" id="site_id" value="{{ $user->site_id }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diesel Stock (Ltr)</label>
                        <input type="number" step="0.01" name="diesel_stock" id="diesel_stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_of_entry" id="date_of_entry" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price / Ltr (Rs)</label>
                        <input type="number" step="0.0001" name="rate_per_liter" id="rate_per_liter" class="form-control">
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
<!-- Add/Edit Modal -->
<div class="modal fade" id="consumptionModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Diesel Consumption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="consumtionForm" method="POST" action="{{ route('diesel.consumption.store') }}">
                @csrf
                <input type="hidden" id="diesel_id" name="id">
                <div class="modal-body">
                    <!--<div class="mb-3">-->
                    
                      @php
                        $user = Auth::user();
                        $userSite = $sites->firstWhere('id', $user->site_id);
                    @endphp
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" value="{{ $userSite->site_name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="site_id" id="site_id" value="{{ $user->site_id }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diesel Consumption (Ltr)</label>
                        <input type="number" step="0.01" name="diesel_consumption" id="diesel_stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_of_entry" id="date_of_entry" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"></label>Activity</label>
                        <input type="text" name="remark" id="remark" class="form-control">
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

<!-- Diesel Stock History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historyModalTitle">Diesel Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card p-3 mb-3">
                    <p><strong>Current Stock:</strong> <span class="text-success fw-bold" id="historyCurrentStock">Loading...</span></p>
                </div>

                <h6 class="text-primary">Stock History</h6>
                <div id="loadingSpinner" class="text-center my-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading history...</p>
                </div>
                <div id="historyMessage" class="alert alert-info text-center d-none"></div>
                <table class="table table-bordered table-striped">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>Date</th>
                            <th>Previous Stock (Ltr)</th>
                            <th>Added Quantity (Ltr)</th>
                            <th>Consumed Quantity (Ltr)</th>
                            <th>Price / Ltr (Rs)</th>
                      
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- History records will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // History Modal
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    const viewHistoryButtons = document.querySelectorAll('.view-history-btn');
    const historyModalTitle = document.getElementById('historyModalTitle');

    const historyCurrentStock = document.getElementById('historyCurrentStock');
    const historyTableBody = document.getElementById('historyTableBody');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const historyMessage = document.getElementById('historyMessage');

    viewHistoryButtons.forEach(button => {
        button.addEventListener('click', async function () {
            const dieselId = encodeURIComponent(this.dataset.dieselId);

            historyTableBody.innerHTML = '';
            historyMessage.classList.add('d-none');
            loadingSpinner.classList.remove('d-none');

            historyCurrentStock.textContent = 'Loading...';

            try {
                const dieselResponse = await fetch(`{{ route('diesel.details', ':id') }}`.replace(':id', dieselId));
                if (!dieselResponse.ok) throw new Error(`HTTP error! status: ${dieselResponse.status}`);
                const dieselData = await dieselResponse.json();

                historyModalTitle.textContent = `Diesel Stock History`;
                historyCurrentStock.textContent = `${dieselData.diesel_stock} Ltr`;

                const historyResponse = await fetch(`{{ route('diesel.history', ':id') }}`.replace(':id', dieselId));
                if (!historyResponse.ok) throw new Error(`HTTP error! status: ${historyResponse.status}`);
                const historyRecords = await historyResponse.json();

                loadingSpinner.classList.add('d-none');

                  if (historyRecords.length > 0) {
                historyRecords.forEach(record => {
                    const date = new Date(record.date_of_entry);
                    const formattedDate = `${String(date.getDate()).padStart(2, '0')}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getFullYear()).slice(2)}`;
            
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formattedDate}</td>
                        <td>${record.stock_before_addition || 'N/A'}</td>
                        <td>${record.added_quantity || 'N/A'}</td>
                        <td>${record.consumed_quantity || 'N/A'}</td>
                        <td>₹${record.rate_per_liter || 'N/A'}</td>
                    `;
                    historyTableBody.appendChild(row);
                });
            } else {
                historyMessage.textContent = 'No history found for this diesel stock.';
                historyMessage.classList.remove('d-none');
            }


            } catch (error) {
                console.error('Error fetching diesel history:', error);
                loadingSpinner.classList.add('d-none');
                historyMessage.textContent = 'Failed to load history. Please try again.';
                historyMessage.classList.remove('d-none');
            } finally {
                historyModal.show();
            }
        });
    });

    // Add/Edit Modal Form Handling
    const dieselForm = document.getElementById('dieselForm');
    dieselForm.addEventListener('submit', function (e) {
        const dieselId = document.getElementById('diesel_id').value;
        if (dieselId) {
            dieselForm.action = `{{ url('/diesels/update') }}/${dieselId}`;
            dieselForm.querySelector('input[name="_method"]').value = 'PUT';
        } else {
            dieselForm.action = '{{ route('diesels.store') }}';
            dieselForm.querySelector('input[name="_method"]')?.remove();
        }
    });

    window.clearForm = function () {
        document.getElementById('dieselForm').reset();
        document.getElementById('diesel_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Diesel';
        dieselForm.action = '{{ route('diesels.store') }}';
        dieselForm.querySelector('input[name="_method"]')?.remove();
    };

    window.editDiesel = function (diesel) {
        document.getElementById('modalTitle').textContent = 'Edit Diesel';
        document.getElementById('diesel_id').value = diesel.id;
        document.getElementById('type').value = diesel.type;
        document.getElementById('diesel_stock').value = diesel.diesel_stock;
        document.getElementById('date_of_entry').value = diesel.date_of_entry;
        document.getElementById('rate_per_liter').value = diesel.rate_per_liter || '';
        document.getElementById('site_id').value = diesel.site_id;
    };

    // Pagination and Search (Existing Logic)
    let currentPage = 1;
    let entriesPerPage = 10;

    function changeEntriesPerPage() {
        entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
        currentPage = 1;
        updateTable();
    }

    function searchTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#dieselTable tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const matches = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(search));
            row.style.display = matches ? '' : 'none';
        });
        updatePagination();
    }

    function updateTable() {
        const rows = document.querySelectorAll('#dieselTable tbody tr');
        rows.forEach((row, index) => {
            row.style.display = (index >= (currentPage - 1) * entriesPerPage && index < currentPage * entriesPerPage) ? '' : 'none';
        });
        document.getElementById('showingInfo').textContent = `Showing ${(currentPage - 1) * entriesPerPage + 1} to ${Math.min(currentPage * entriesPerPage, rows.length)} of ${rows.length} entries`;
        updatePagination();
    }

    function updatePagination() {
        const rows = document.querySelectorAll('#dieselTable tbody tr:not([style*="display: none"])');
        const totalPages = Math.ceil(rows.length / entriesPerPage);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        if (totalPages <= 1) return;

        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = '<a class="page-link" href="#">Previous</a>';
        prevLi.onclick = () => { if (currentPage > 1) { currentPage--; updateTable(); } };
        pagination.appendChild(prevLi);

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.onclick = () => { currentPage = i; updateTable(); };
            pagination.appendChild(li);
        }

        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = '<a class="page-link" href="#">Next</a>';
        nextLi.onclick = () => { if (currentPage < totalPages) { currentPage++; updateTable(); } };
        pagination.appendChild(nextLi);
    }

    updateTable();
});
$('#dieselForm').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: '{{ route("diesel.consumption.store") }}', // ✅ This route name must match web.php
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            alert(response.message);
            $('#consumptionModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Server error');
        }
    });
});

</script>
   <!-- Filter Bar Start -->
    <!-- Move the filter bar to be directly above the Stock Consumed For Diesel table, remove extra margin-bottom and margin-top -->
    <!--<div class="diesel-filter-bar mb-0" style="margin-bottom:0;">-->
    <!--    <form class="d-flex flex-wrap align-items-center gap-3 justify-content-between">-->
    <!--        <div class="d-flex align-items-center gap-2">-->
    <!--            <label for="siteFilter" class="form-label mb-0 fw-semibold">Site:</label>-->
    <!--            <select id="siteFilter" class="form-select form-select-sm" style="min-width: 120px;">-->
    <!--                <option>All Sites</option>-->
    <!--                <option>Site 1</option>-->
    <!--                <option>Site 2</option>-->
    <!--            </select>-->
    <!--        </div>-->
    <!--        <div class="d-flex align-items-center gap-2">-->
    <!--            <label for="activityFilter" class="form-label mb-0 fw-semibold">Activity:</label>-->
    <!--            <select id="activityFilter" class="form-select form-select-sm" style="min-width: 160px;">-->
    <!--                <option>All Activities</option>-->
    <!--                <option>Area Levelling</option>-->
    <!--                <option>Pre Field Prep. of Land</option>-->
    <!--                <option>Land Preparation</option>-->
    <!--                <option>Sowing</option>-->
    <!--                <option>Fertilizers</option>-->
    <!--                <option>Inter Culture</option>-->
    <!--                <option>Crop Protection</option>-->
    <!--                <option>Harvest</option>-->
    <!--                <option>Hay Making</option>-->
    <!--                <option>Silage Making</option>-->
    <!--            </select>-->
    <!--        </div>-->
    <!--        <div class="d-flex align-items-center gap-2">-->
    <!--            <label for="dateFrom" class="form-label mb-0 fw-semibold">From:</label>-->
    <!--            <input type="date" id="dateFrom" class="form-control form-control-sm">-->
    <!--        </div>-->
    <!--        <div class="d-flex align-items-center gap-2">-->
    <!--            <label for="dateTo" class="form-label mb-0 fw-semibold">To:</label>-->
    <!--            <input type="date" id="dateTo" class="form-control form-control-sm">-->
    <!--        </div>-->
    <!--        <button type="submit" class="btn btn-primary btn-sm px-4">Filter</button>-->
    <!--    </form>-->
    <!--</div>-->
    <!-- Filter Bar End -->
<!-- Ticket Dashboard Section Start (Business names, activities, table headers) -->
<div class="ticket-dashboard-section mt-5">
    <div class="row g-3 mb-4 align-items-stretch">
        <!-- Left: 2x2 small cards, flex column for perfect height match -->
        <div class="col-lg-12 col-md-12 d-flex flex-column h-100">
            <div class="d-flex flex-column h-100 justify-content-between" style="height:100%; min-height:200px;">
                <div class="d-flex flex-row flex-fill" style="height:50%;">
                     <div class="flex-fill p-2">
                        <div class="card ticket-compact-card text-center shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center h-100">
                                <div class="mb-2"><i class="fas fa-tachometer-alt fa-2x text-success"></i></div>
                                <h6 class="mb-1">Total Purchased Stock</h6>
                                <h3 class="fw-bold text-success">{{ $totalPurchased }} L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="flex-fill p-2">
                        <div class="card ticket-compact-card text-center shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center h-100">
                                <div class="mb-2"><i class="fas fa-gas-pump fa-2x text-info"></i></div>
                                <h6 class="mb-1">Total HSD Consumed</h6>
                                <h3 class="fw-bold text-info">{{ $totalConsumed }} L</h3>
                            </div>
                        </div>
                    </div>
                   <div class="flex-fill p-2">
                        <div class="card ticket-compact-card text-center shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center h-100">
                                <div class="mb-2"><i class="fas fa-oil-can fa-2x text-warning"></i></div>
                                <h6 class="mb-1">Total Consumed Stock Cost</h6>
                                <h3 class="fw-bold text-warning">₹{{ $totalConsumedCost }}</h3>
                            </div>
                        </div>
                    </div> 
                    <div class="flex-fill p-2">
                        <div class="card ticket-compact-card text-center shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center h-100">
                                <div class="mb-2"><i class="fas fa-money-bill-wave fa-2x text-primary"></i></div>
                                <h6 class="mb-1">Total Cost Of the HSD</h6>
                                <h3 class="fw-bold text-primary">₹{{ $totalPurchasedCost}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                 
            </div>
        </div>
       
    </div>
 
    <div class="row g-3">
        <div class="col-12">
            <!-- Centered Bold Paragraph -->
            <div class="text-center mb-3 mt-2">
                <!-- Remove this span from outside the table -->
                <!-- <span class="fw-bold" style="font-size: 1.15rem;">Stock Consumed For Diesel</span> -->
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                     <!-- Header section with caption & button -->
              <!-- Button aligned to right -->
            <div class="d-flex justify-content-end mb-3">
                @if(Auth::check() && Auth::user()->role != 1)
                 <button class="btn btn-success btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#consumptionModal" 
                            onclick="clearForm()" style="margin-right: 10px;">
                        Add Consumption
                    </button>
                    <button class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#dieselModal" 
                            onclick="clearForm()">
                        Add Diesel Entry
                    </button>
                    
                @endif
            </div>
                    <div class="table-responsive">
                     <table class="table table-bordered align-middle" id="stockConsumedTable">
                           
    <caption style="caption-side: top; text-align: center; font-weight: bold; font-size: 1.15rem; padding-bottom: 0.5rem;">Stock Consumed For Diesel</caption>
    <thead class="table-light">
        <tr>
            <th>Sr. No.</th>
            <th>Date</th>
            <th>Opening Stock (LTR)</th>
            <th>Purchased Stock (LTR)</th>
            <th>Issued HSD Consumption (LTR)</th>
            <th>Closing Stock (LTR)</th>
             
        </tr>
    </thead>
    <tbody>
        @foreach($stockReport as $row)
        <tr>
            <td>{{ $row['sr_no'] }}</td>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td>
            <td>{{ $row['opening_stock'] }}</td>
            <td>
            <a href="#" class="purchased-hsd-link" 
                   data-date="{{ $row['date'] }}" 
                   data-activities=''
                   data-bs-toggle="modal" 
                   data-bs-target="#purchasedStockModal">
                   {{ $row['purchased_stock'] }}
                </a>
            </td>
            <td>
                <a href="#" class="issued-hsd-link" 
                   data-date="{{ $row['date'] }}" 
                   data-activities=''
                   data-bs-toggle="modal" 
                   data-bs-target="#issuedStockModal">
                   {{ $row['consumed_stock'] }}
                </a>
            </td>
            <td>{{ $row['closing_stock'] }}</td>
             
        </tr>
        @endforeach
    </tbody>
</table>

                        
                        <!-- Remove the old manual pagination/info below the table -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Ticket Dashboard Section End -->

<!-- Issued Stock Details Modal -->
<div class="modal fade" id="issuedStockModal" tabindex="-1" aria-labelledby="issuedStockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="issuedStockModalLabel">Issued Stock Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle" id="issuedStockTable">
            <thead class="table-success">
              <tr>
                <th>Sr. No.</th>
                <th>Issued HSD Consumption</th>
                <th>Price/LTR (Rs.)</th>
                <th>Diesel Cost (Rs.)</th>
                <th>Used Tractor</th>
                <th>Activity Performed</th>
                <th>Supervisor Name</th>
              </tr>
            </thead>
            <tbody>
              <!-- Data will be loaded here via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Issued Stock Details Modal -->
<div class="modal fade" id="purchasedStockModal" tabindex="-1" aria-labelledby="purchasedStockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="pucrhasedStockModalLabel">Purchased Stock Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle" id="purchasedStockTable">
            <thead class="table-success">
              <tr>
                <th>Sr. No.</th>
                <th>Purchased Stock (LTR)</th>
                <th>Price/LTR (Rs.)</th>
                <th>Diesel Cost (Rs.)</th>
              </tr>
            </thead>
            <tbody>
              <!-- Data will be loaded here via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DataTables JS & CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // DataTable for modal table (already present)
    $('#issuedStockTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        lengthChange: true,
        pageLength: 5,
        ordering: true,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                previous: "Previous",
                next: "Next"
            }
        }
    });
    // DataTable for Stock Consumed For Diesel table
    $('#stockConsumedTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        ordering: true,
        order: [[0, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                previous: "Previous",
                next: "Next"
            }
        }
    });
    // Set modal title dynamically if you want to show date
    $('.issued-hsd-link').on('click', function() {
        var date = $(this).data('date');
        $('#issuedStockModalLabel').text('Issued Stock Details (' + date + ')');
    });
});
</script>
<!-- Chart.js CDN for Pie Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Fresh Pie Chart
        var ctx = document.getElementById('freshPieChart').getContext('2d');
        var pieColors = [
            '#0d6efd', // Area Levelling
            '#198754', // Pre Field Prep. of Land
            '#0dcaf0', // Land Preparation
            '#ffc107', // Sowing
            '#dc3545', // Fertilizers
            '#6c757d', // Inter Culture
            '#212529', // Crop Protection
            '#198754', // Harvest
            '#0d6efd', // Hay Making
            '#ffc107'  // Silage Making
        ];
        var pieLabels = [
            'Area Levelling',
            'Pre Field Prep. of Land',
            'Land Preparation',
            'Sowing',
            'Fertilizers',
            'Inter Culture',
            'Crop Protection',
            'Harvest',
            'Hay Making',
            'Silage Making'
        ];
        var pieData = [120, 80, 100, 60, 70, 50, 40, 30, 25, 20];
        var pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                responsive: false,
                maintainAspectRatio: false
            }
        });

        // Render custom legend in 2 columns for compactness
        var legendContainer = document.getElementById('fresh-pie-legend');
        if (legendContainer) {
            var legendHtml = '<div class="fresh-pie-legend-row-horizontal" style="flex-wrap: wrap;">';
            for (var i = 0; i < pieLabels.length; i++) {
                legendHtml += '<div class="fresh-pie-legend-item" style="width: 48%;">'
                    + '<span class="fresh-pie-legend-color" style="background:' + pieColors[i] + ';"></span>'
                    + '<span class="fresh-pie-legend-label">' + pieLabels[i] + '</span>'
                    + '</div>';
            }
            legendHtml += '</div>';
            legendContainer.innerHTML = legendHtml;
        }
    });
 $(document).on("click", ".issued-hsd-link", function(e) {
    e.preventDefault();
    let date = $(this).data("date"); // clicked date

    $.get("/diesel-consumption-by-date", { date: date }, function(data) {
        let tbody = $("#issuedStockTable tbody");
        tbody.empty();

        let filteredData = data.filter(item => item.activity_date === date); // ✅ filter here

      if (filteredData.length > 0) {
            filteredData.forEach((item, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.liters_used}</td>
                        <td>${item.rate}</td>
                        <td>${item.cost}</td>
                        <td>${item.tractor_name ?? '-'}</td>
                        <td>${item.activity}</td>
                        <td>${item.supervisor_name ?? '-'}</td>
                    </tr>
                `);
            });
        }else {
            tbody.append(`
                <tr><td colspan="8" class="text-center">No consumption found for this date</td></tr>
            `);
        }
    });

   });
 $(document).on("click", ".purchased-hsd-link", function(e) {
    e.preventDefault();
    let date = $(this).data("date"); // clicked date
      $('#pucrhasedStockModalLabel').text('Purchased Stock Details (' + date + ')');
    $.get("/diesel-purchased-by-date", { date: date }, function(data) {
        let tbody = $("#purchasedStockTable tbody");
        tbody.empty();

        let filteredData = data.filter(item => item.activity_date === date); // ✅ filter here
  if (filteredData.length > 0) {
            filteredData.forEach((item, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td
                        <td>${item.added_quantity}</td>
                         <td>${item.added_quantity}</td>
                        <td>${item.rate_per_liter}</td>
                        <td>${item.added_quantity*item.rate_per_liter}</td>
                    </tr>
                `);
            });
        } 
         else {
            tbody.append(`
                <tr><td colspan="8" class="text-center">No purchased stock found for this date</td></tr>
            `);
        }
    });
});

</script>
<style>
.ticket-dashboard-section .fresh-pie-legend-row-horizontal {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: flex-start;
    align-items: flex-start;
    gap: 0.7rem;
    width: 100%;
    margin-bottom: 0.2rem;
}
.ticket-dashboard-section .fresh-pie-legend-item {
    display: flex;
    align-items: center;
    gap: 0.4em;
    font-size: 0.78rem;
    font-weight: 500;
    min-width: 90px;
    margin-bottom: 0.1rem;
}
.ticket-dashboard-section .fresh-pie-legend-color {
    display: inline-block;
    width: 14px;
    height: 10px;
    border-radius: 3px;
    margin-right: 0.3em;
}
.ticket-dashboard-section .fresh-pie-legend-label {
    display: inline-block;
    vertical-align: middle;
    white-space: nowrap;
}
.ticket-dashboard-section .ticket-graph-card .card-body {
    min-height: 320px;
    height: 100%;
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
}
.diesel-filter-bar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    border: 1px solid #e3e6ea;
}
.diesel-filter-bar label {
    font-size: 0.98rem;
}
.diesel-filter-bar .form-select,
.diesel-filter-bar .form-control {
    min-width: 100px;
    font-size: 0.97rem;
}
@media (max-width: 991px) {
    .ticket-dashboard-section .fresh-pie-legend-row-horizontal {
        gap: 1.2rem;
    }
    .ticket-dashboard-section .fresh-pie-legend-item {
        min-width: 70px;
        font-size: 0.85rem;
    }
}
</style>

@endsection