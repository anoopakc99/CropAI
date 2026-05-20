@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    :root {
        --primary-color: #4CAF50;
        --primary-dark: #388E3C;
        --secondary-color: #607D8B;
        --background-light: #f0f2f5;
        --card-background: #ffffff;
        --text-dark: #333333;
        --text-light: #666666;
        --border-color: #e0e0e0;
        --shadow-light: rgba(0, 0, 0, 0.08);
        --shadow-medium: rgba(0, 0, 0, 0.12);
        --success-color: #28a745;
        --export-color: #007bff;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--background-light);
        line-height: 1.6;
        color: var(--text-light);
    }

    .dashboard-container {
        padding: 30px;
        min-height: 100vh;
    }

    .info-cards-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .info-card {
        background: var(--card-background);
        border-radius: 12px;
        padding: 0;
        box-shadow: 0 4px 15px var(--shadow-light);
        border: none;
        overflow: hidden;
        transition: all 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
    }

    .info-card:hover {
        box-shadow: 0 8px 25px var(--shadow-medium);
        transform: translateY(-5px);
    }

    .card-header {
        background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        height: 6px;
        width: 100%;
    }

    .card-content {
        padding: 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-grow: 1;
    }

    .card-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .card-icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        object-fit: contain;
    }

    .card-title {
        font-size: 16px;
        font-weight: 500;
        color: var(--text-light);
        margin: 0;
        letter-spacing: 0.5px;
    }

    .card-count {
        font-size: 27px;
        font-weight: 700;
        color: var(--primary-dark);
        line-height: 1;
        font-family: 'Poppins', sans-serif;
        margin-left: 18px;
    }

    .controls-section {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .controls-left {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .select-block {
        flex: 1;
        max-width: 250px;
    }

    .search-wrapper {
        flex: 1;
        max-width: 300px;
    }

    .export-btn {
        background: linear-gradient(135deg, var(--export-color) 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        outline: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    }

    .export-btn:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
        transform: translateY(-2px);
    }

    .custom-select {
        appearance: none;
        background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
        color: white;
        border: none;
        padding: 12px 40px 12px 20px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        outline: none;
        transition: all 0.3s ease;
        width: 100%;
        box-shadow: 0 2px 8px var(--shadow-light);
    }

    .custom-select:hover,
    .custom-select:focus {
        background: var(--primary-dark);
        box-shadow: 0 4px 12px var(--shadow-medium);
    }

    .select-arrow {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: white;
        pointer-events: none;
        font-size: 14px;
    }

    .search-input {
        width: 150%;
        padding: 12px 18px 12px 50px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 15px;
        background: var(--card-background);
        transition: all 0.3s ease;
        outline: none;
        box-shadow: 0 2px 8px var(--shadow-light);
        color: var(--text-dark);
    }

    .search-input::placeholder {
        color: var(--text-light);
    }

    .search-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
    }

    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        font-size: 16px;
    }

    .table-wrapper {
        background: var(--card-background);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px var(--shadow-light);
        border: 1px solid var(--border-color);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .data-table thead th {
        background: #6b8e23 !important;
        color: white;
        font-weight: 600;
        text-align: center; /* Center aligned headers */
        padding: 15px 20px;
        font-size: 13px;
        text-transform: capitalize;
        letter-spacing: 0.8px;
    }

    .data-table thead th:first-child {
        border-top-left-radius: 10px;
    }

    .data-table thead th:last-child {
        border-top-right-radius: 10px;
    }

    .data-table tbody td {
        padding: 14px 20px;
        text-align: center; /* Center aligned data */
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
        color: var(--text-dark);
        font-weight: 500;
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    .data-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:hover {
        background-color: #f7f9fc;
    }

    .data-table tbody tr:nth-child(even) {
        background-color: #fcfcfc;
    }

    .data-table tbody tr:nth-child(even):hover {
        background-color: #f0f2f5;
    }

    .block-summary {
        background: #e8f5e9 !important;
        color: var(--primary-dark) !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .block-summary td {
        color: var(--primary-dark) !important;
        border-bottom: 2px solid var(--primary-color) !important;
        padding: 15px 20px !important;
        font-weight: 700;
    }

    .block-summary td:first-child {
        text-align: left !important;
    }

    .grand-total {
        background: var(--secondary-color) !important;
        color: white !important;
        font-weight: 700;
        font-size: 16px;
        letter-spacing: 1px;
    }

    .grand-total td {
        color: white !important;
        border-bottom: none !important;
        padding: 18px 20px !important;
        font-weight: 700;
    }

    .grand-total td:first-child {
        text-align: left !important;
    }

    .grand-total:hover {
        background: #546E7A !important;
    }

    /* Professional styling improvements */
    .table-header {
        background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
        color: white;
        padding: 20px;
        text-align: center;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        display: none;
    }

    .loading-spinner {
        background: white;
        padding: 30px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid var(--border-color);
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 20px;
        }

        .info-cards-row {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .controls-section {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }

        .controls-left {
            flex-direction: column;
            gap: 15px;
        }

        .select-block,
        .search-wrapper {
            max-width: 100%;
        }

        .export-btn {
            width: 100%;
            justify-content: center;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            min-width: 600px;
        }

        .data-table thead th,
        .data-table tbody td {
            padding: 12px 15px;
            font-size: 13px;
        }

        .card-content {
            padding: 20px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
        }

        .card-count {
            font-size: 28px;
        }

        .card-title {
            font-size: 15px;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <div class="info-cards-row">
     @php
    $cards = [
        ['icon' => 'fas fa-seedling', 'color' => '#66BB6A', 'title' => 'Area (acre)', 'count' => number_format($grandTotal, 2)],
        ['icon' => 'fas fa-leaf', 'color' => '#8BC34A', 'title' => 'Seed Type', 'count' => $seedsCount],
        ['icon' => 'fas fa-tractor', 'color' => '#FF9800', 'title' => 'Tractors', 'count' => $tractorsCount],
        ['icon' => 'fas fa-cogs', 'color' => '#FFC107', 'title' => 'Total Machineries', 'count' => $machinesCount],
        ['icon' => 'fas fa-flask', 'color' => '#2196F3', 'title' => 'Fertilizer Type', 'count' => $fertilizersCount],
    ];
@endphp

        @foreach($cards as $card)
        <div class="info-card">
            <div class="card-header"></div>
            <div class="card-content">
                <div class="card-left">
                    <i class="{{ $card['icon'] }} card-icon" style="color: {{ $card['color'] }}"></i>
                    <div class="card-title">{{ $card['title'] }}</div>
                </div>
                <div class="card-count">{{ $card['count'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="controls-section">
        <div class="controls-left">
            <div class="select-block">
                <select class="custom-select" id="blockSelect" name="block">
                    <option value="">All Blocks</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->block_name }}">  {{ $block->block_name }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
            </div>

            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search plots, blocks..." id="searchInput">
            </div>
        </div>

        <button class="export-btn" onclick="exportToExcel()">
            <i class="fas fa-download"></i>
            Export to Excel
        </button>
    </div>

    <div class="table-wrapper">
        {{-- <div class="table-header">
            Land Plot Management Report
        </div> --}}
        <table class="data-table" id="dataTable">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Block Name</th>
                    <th>Plot No.</th>
                    <th>Area (Acre)</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @php $sr = 1; $grandTotal = 0; @endphp
                @foreach($groupedPlots as $blockName => $plots)
                    @php
                        $blockTotal = $plots->sum('area');
                        $plotCount = $plots->count(); // Count of plots in this block
                        $grandTotal += $blockTotal;
                    @endphp
                    @foreach($plots as $plot)
                    <tr data-block="{{ $blockName }}">
                        <td>{{ $sr++ }}</td>
                        <td>{{ $blockName }}</td>
                        <td>{{ $plot->plot_name }}</td>
                        <td>{{ number_format($plot->area, 2) }}</td>
                    </tr>
                    @endforeach
<tr class="block-summary" data-block="{{ $blockName }}">
    <td colspan="3"><strong>Total - (Block {{ $blockName }})</strong></td>
    <td><strong>{{ number_format($blockTotal, 2) }}</strong></td>
</tr>

                @endforeach
                <tr class="grand-total">
                    <td colspan="3"><strong>GRAND TOTAL</strong></td>
                    <td><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Generating Excel file...</p>
    </div>
</div>
@endsection

 
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('tableBody');
    const blockSelect = document.getElementById('blockSelect');
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const dataRows = allRows.filter(row => !row.classList.contains('block-summary') && !row.classList.contains('grand-total'));
    const summaryRows = allRows.filter(row => row.classList.contains('block-summary'));
    const grandTotalRow = allRows.find(row => row.classList.contains('grand-total'));

    function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const selectedBlock = blockSelect.value.trim();
    const visibleBlocks = new Set();
    let filteredTotal = 0;

    dataRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const blockName = row.getAttribute('data-block');
        const matchesSearch = searchTerm === '' || text.includes(searchTerm);
        const matchesBlock = selectedBlock === '' || blockName === selectedBlock;
        const isVisible = matchesSearch && matchesBlock;
        row.style.display = isVisible ? '' : 'none';

        if (isVisible) {
            visibleBlocks.add(blockName);
            const areaCell = parseFloat(row.cells[3].textContent.replace(/,/g, '')) || 0;
            filteredTotal += areaCell;
        }
    });

    summaryRows.forEach(row => {
        const blockName = row.getAttribute('data-block');
        row.style.display = visibleBlocks.has(blockName) ? '' : 'none';
    });

    // Update Grand Total dynamically
    const anyVisible = dataRows.some(row => row.style.display !== 'none');
    if (anyVisible) {
        grandTotalRow.style.display = '';
        grandTotalRow.querySelector('td:last-child strong').textContent = filteredTotal.toFixed(2);
    } else {
        grandTotalRow.style.display = 'none';
    }
}

    searchInput.addEventListener('input', applyFilters);
    blockSelect.addEventListener('change', applyFilters);
});

function exportToExcel() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'flex';
    
    setTimeout(() => {
        try {
            const table = document.getElementById('dataTable');
            const visibleRows = [];
            
            // Add header
            visibleRows.push(['Sr.No.', 'Block', 'Plot', 'Area (Acre)']);
            
            // Get all visible rows
            const tableBody = document.getElementById('tableBody');
            const rows = tableBody.querySelectorAll('tr');
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        const rowData = [];
                        cells.forEach(cell => {
                            rowData.push(cell.textContent.trim());
                        });
                        visibleRows.push(rowData);
                    }
                }
            });
            
            // Create workbook and worksheet
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(visibleRows);
            
            // Set column widths
            const colWidths = [
                { wch: 10 }, // Sr.No.
                { wch: 10 }, // Block
                { wch: 15 }, // Plot
                { wch: 15 }  // Area
            ];
            ws['!cols'] = colWidths;
            
            // Style the header row
            const headerStyle = {
                fill: { fgColor: { rgb: "667E06" } },
                font: { bold: true, color: { rgb: "FFFFFF" } },
                alignment: { horizontal: "center", vertical: "center" }
            };
            
            // Apply header styling
            for (let col = 0; col < 4; col++) {
                const cellRef = XLSX.utils.encode_cell({ r: 0, c: col });
                if (!ws[cellRef]) ws[cellRef] = {};
                ws[cellRef].s = headerStyle;
            }
            
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(wb, ws, "Land Plot Report");
            
            // Generate filename with current date
            const currentDate = new Date().toISOString().split('T')[0];
            const filename = `Land_Plot_Report_${currentDate}.xlsx`;
            
            // Save the file
            XLSX.writeFile(wb, filename);
            
            // Show success message
            showNotification('Excel file downloaded successfully!', 'success');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error generating Excel file. Please try again.', 'error');
        } finally {
            loadingOverlay.style.display = 'none';
        }
    }, 1000);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        .notification.success {
            background: #28a745;
        }
        .notification.error {
            background: #dc3545;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
        style.remove();
    }, 3000);
}
</script>
 