@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary-color: #2E7D32;
        --secondary-color: #4CAF50;
        --accent-color: #81C784;
        --background-light: #F1F8E9;
        --text-dark: #1B5E20;
    }
    body {
        background: linear-gradient(135deg, #E8F5E8 0%, #F1F8E9 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }
    .main-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 1200px;
    }
    .header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 15px;
        text-align: center;
        position: relative;
    }
    .header h4 { margin: 0; }
    .info-group h6 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid var(--accent-color);
        padding-bottom: 8px;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid" id="recordView">
    <div class="main-container mt-3">
        <div class="header d-flex justify-content-between">
            <a class="btn btn-success" href="{{ route('contact-farming.index') }}">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h4> View Contract Farming Record</h4>
            {{-- <button class="btn btn-danger" id="downloadPdfBtn">
                <i class="fas fa-file-pdf me-1"></i> Download PDF
            </button> --}}
        </div>


        <div class="card-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <h6>Block & Plot Details</h6>
                            <table class="table table-sm table-bordered">
                                <tr><td class="fw-bold">Blocks:</td><td>{{ $record->block_names ?? 'N/A' }}</td></tr>
                                <tr><td class="fw-bold">Plots:</td><td>{{ $record->plot_names ?? 'N/A' }}</td></tr>
                                <tr><td class="fw-bold">Area:</td><td>{{ $record->area_hectares ?? 'N/A' }} acres</td></tr>
                                 <tr><td class="fw-bold">Seed Name:</td><td>{{ $record->seed_name ?? 'N/A' }} </td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <h6>Contract Details</h6>
                            <table class="table table-sm">
                                <tr><td class="fw-bold">Contractor Name:</td><td>{{ $record->contractor_name ?? 'N/A' }}</td></tr>
                                <tr><td class="fw-bold">Contract Period:</td><td>
                                    {{ $record->contract_start_date ? \Carbon\Carbon::parse($record->contract_start_date)->format('d-m-Y') . ' to ' . \Carbon\Carbon::parse($record->contract_end_date)->format('d-m-Y') : 'N/A' }}
                                </td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="info-group">
                            <h6>Seed Varieties & Production Details</h6>
                            @if($record->varieties && $record->varieties->count() > 0)
                                <div class="table-responsive mt-2">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Variety</th>
                                                <th>Prod Qty (MT)</th>
                                                <th>Price/MT</th>
                                                <th>Prod Amount (Rs)</th>
                                                <th>Handling Loss (MT)</th>
                                                <th>Amount Recovery (Rs)</th>
                                                <th>Sold Qty (MT)</th>
                                                <th>Sale Price/MT</th>
                                                <th>Sale Amount (Rs)</th>
                                                <th>Remaining Qty (MT)</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($record->varieties as $v)
                                                @php
                                                    $production = (float)($v->production_quantity_mt - $v->handling_loss ?? 0);
                                                    $totalSold = $v->sales->sum(function($s) { return (float)($s->quantity_sold ?? 0); });
                                                    $remainingTotal = $production - $totalSold;
                                                @endphp
                                                @if ($v->sales && $v->sales->count() > 0)
                                                    @php $cumulativeSold = 0; @endphp
                                                    @foreach($v->sales as $index => $s)
                                                        @php $cumulativeSold += (float)($s->quantity_sold ?? 0); @endphp
                                                        <tr>
                                                            @if($index === 0)
                                                                <td rowspan="{{ $v->sales->count() }}">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fw-semibold">{{ $v->seed_variety }}</span>
                                                                        @if($remainingTotal > 0)
                                                                            <button class="btn btn-sm btn-success mt-2 sellRemainingBtn"
                                                                                    data-variety="{{ $v->seed_variety }}"
                                                                                    data-seed-id="{{ $v->id }}"
                                                                                    data-remaining="{{ number_format($remainingTotal, 2) }}">
                                                                                <i class="fas fa-shopping-cart me-1"></i> Sell Remaining
                                                                            </button>
                                                                        @else
                                                                            <span class="text-muted small">(Sold Out)</span>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td rowspan="{{ $v->sales->count() }}">{{ number_format($v->production_quantity_mt, 2) }}</td>
                                                                <td rowspan="{{ $v->sales->count() }}">{{ $v->production_price_mt ?? '' }}</td>
                                                                <td rowspan="{{ $v->sales->count() }}">{{ $v->production_amount ?? 0 }}</td>
                                                                <td rowspan="{{ $v->sales->count() }}">{{ $v->handling_loss ?? '' }}</td>
                                                                <td rowspan="{{ $v->sales->count() }}">{{ $v->amount_recovery ?? 0 }}</td>
                                                                
                                                            @endif

                                                            <td>{{ number_format((float)($s->quantity_sold ?? 0), 2) }}</td>
                                                            <td>{{ $s->sale_price ?? '' }}</td>
                                                            <td>{{ $s->total_amount ?? 0 }}</td>
                                                            <td>{{ number_format($production - $cumulativeSold, 2) }}</td>
                                                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($s->created_at)->format('d-m-y') }}</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td>{{ $v->seed_variety }}</td>
                                                        <td>{{ number_format($production, 2) }}</td>
                                                        <td>{{ $v->production_price_mt ?? '' }}</td>
                                                        <td>{{ $v->production_amount ?? 0 }}</td>
                                                        <td>{{ $v->handling_loss ?? '' }}</td>
                                                        <td>{{ $v->amount_recovery ?? 0 }}</td>
                                                        <td>0.00</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>{{ number_format($remainingTotal, 2) }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted">No variety details available.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="info-group">
                            <h6>Overall Financial Summary</h6>
                           @php
                            $profit = ($record->sale_amount ?? 0) - ($record->product_amount ?? 0); // can be negative or positive
                        
                            // Convert negative profit to positive loss
                            $adjustedProfit = $profit < 0 ? abs($profit) * -1 : $profit;
                        
                            // Add recovery
                            $netProfit = $adjustedProfit + ($record->amount_recovery ?? 0);
                        
                            // Determine final status
                            $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                        @endphp
                            <table class="table">
                                <tr><td>Production Total:</td><td>₹{{ $record->product_amount ?? 0 }}</td></tr>
                                <tr><td>Sales Total:</td><td>₹ {{ $record->sale_amount ?? 0 }}</td></tr>
                                <tr><td>Handling Loss:</td><td>{{ $record->handling_loss_mt ?? 0 }} MT</td></tr>
                                <tr><td>Amount Recovery:</td><td>₹ {{ $record->amount_recovery ?? 0 }}</td></tr>
                                <tr><td class="fw-bold">Net Profit/Loss:</td><td class="{{ $profitClass }} fw-bold">₹ {{ number_format(abs($netProfit), 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="info-group">
                            <h6>Additional Information</h6>
                            <table class="table">
                                <tr><td>Remarks:</td><td>{{ $record->remark ?? 'No remarks' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                 <div class="row" style="margin-bottom:20px;">
                    <div class="col-md-6">
                        @if($record->production_attachment)
                        <div class="info-group">
                            <h6>Production Attachments</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="d-grid">
                                 <a href="{{ asset($record->production_attachment) }}" target="_blank" class="btn btn-outline-success">
                                    <i class="fas fa-file-pdf"></i> View Production Document
                                </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if($record->sale_attachment)
                        <div class="info-group">
                            <h6>Sale Attahments</h6>
                            <div class="d-flex flex-wrap gap-2">
                                  <div class="d-grid">
                               <a href="{{ asset($record->sale_attachment) }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-file-pdf"></i> View Sale Document
                                </a>
                            </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sell Remaining Modal -->
<div class="modal fade" id="sellRemainingModal" tabindex="-1" aria-labelledby="sellRemainingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sellRemainingModalLabel">Sell Remaining Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sellRemainingForm">
                    @csrf
                    <input type="hidden" id="varietyId" name="variety_id">
                    <div class="mb-3">
                        <label for="varietyName" class="form-label">Variety</label>
                        <input type="text" class="form-control" id="varietyName" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="remainingQty" class="form-label">Remaining Quantity (MT)</label>
                        <input type="text" class="form-control" id="remainingQty" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="quantitySold" class="form-label">Quantity to Sell (MT)</label>
                        <input type="number" class="form-control" id="quantitySold" name="quantity_sold" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="salePrice" class="form-label">Sale Price/MT</label>
                        <input type="number" class="form-control" id="salePrice" name="sale_price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="buyerName" class="form-label">Buyer Name</label>
                        <input type="text" class="form-control" id="buyerName" name="buyer_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitSaleBtn">Submit Sale</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ✅ PDF Download
    const btn = document.getElementById('downloadPdfBtn');
    const element = document.getElementById('recordView');

    if (btn && element) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            console.log('Download PDF clicked ✅');

            const options = {
                margin: 0.5,
                filename: 'Contract_Farming_Record.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(options).from(element).save();
        });
    }

    // ✅ Modal Logic for Selling Remaining Quantity
    const sellRemainingModal = new bootstrap.Modal(document.getElementById('sellRemainingModal'));

    document.querySelectorAll('.sellRemainingBtn').forEach(button => {
        button.addEventListener('click', function () {
             
            document.getElementById('varietyName').value = this.dataset.variety;
            document.getElementById('varietyId').value = this.dataset.seedId;
            document.getElementById('remainingQty').value = this.dataset.remaining;
            sellRemainingModal.show();
        });
    });

    document.getElementById('submitSaleBtn').addEventListener('click', function () {
        const form = document.getElementById('sellRemainingForm');
        const formData = new FormData(form);

        fetch('{{ route("contact-farming.sell") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sale recorded successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    });
});
</script>
