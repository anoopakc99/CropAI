@extends('layouts.app')
@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvest Sales History Report</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .main-container {
            min-height: 100vh;
            padding: 2rem 0;
        }

        /* Professional Header */
        .report-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1b6621 100%);
            color: var(--white);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .report-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .report-header h2 {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--white);
        }

        .report-header p {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
        }

        /* Professional Cards */
        .report-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            background: var(--white);
        }

        .stats-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .stats-card p {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Stock Summary Card */
        .stock-summary-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .stock-summary-header {
            background: linear-gradient(135deg, #050609 0%, #1d4ed8 100%);
            padding: 1rem 1.5rem;
        }

        .stock-summary-body {
            padding: 1.5rem;
        }

        .stock-metric {
            text-align: center;
            padding: 0.5rem;
        }

        .stock-metric-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
            display: block;
        }

        .stock-metric-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0;
        }

        .stock-metric-sub {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 0.25rem;
        }

        /* Professional Table */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: var(--white);
        }

        .modern-table thead th {
            background: var(--text-primary);
            color: var(--white);
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 1rem;
            /*text-transform: uppercase;*/
            letter-spacing: 0.05em;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table td {
            padding: 1rem;
            vertical-align: middle;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Professional Badges */
        .badge-professional {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .badge-success {
            background-color: var(--success-color);
            color: var(--white);
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: var(--white);
        }

        .badge-info {
            background-color: var(--info-color);
            color: var(--white);
        }

        .badge-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .price-display {
            font-weight: 700;
            color: var(--success-color);
            font-size: 1rem;
        }

        .quantity-display {
            font-weight: 600;
            color: var(--text-primary);
        }

        .buyer-name {
            font-weight: 600;
            color: var(--text-primary);
            background-color: #f1f5f9;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
        }

        .date-display {
            font-weight: 500;
            color: var(--text-secondary);
            background-color: #fef3c7;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        /* Professional Buttons */
        .btn-professional {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary-professional {
            background-color: #4b8b3e;
            color: var(--white);
        }

        .btn-primary-professional:hover {
            background-color: #4b8b3e;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: var(--white);
        }

        .btn-secondary-professional {
            background-color: var(--white);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary-professional:hover {
            background-color: #f8fafc;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* Summary Cards */
        .summary-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
        }

        .summary-card h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .summary-card .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
        }

        .summary-card .progress-bar {
            border-radius: 4px;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: var(--text-muted);
        }

        .no-data h5 {
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .no-data p {
            color: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0;
            }

            .report-header {
                padding: 1.5rem;
            }

            .report-header h2 {
                font-size: 1.5rem;
            }

            .stats-card h3 {
                font-size: 1.75rem;
            }

            .modern-table {
                font-size: 0.875rem;
            }

            .modern-table th,
            .modern-table td {
                padding: 0.75rem 0.5rem;
            }

            .stock-metric {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .stats-card {
                margin-bottom: 0.75rem;
            }

            .buyer-name,
            .date-display {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
            }

            .btn-professional {
                display: none !important;
            }

            .report-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            .stats-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid main-container">
        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ url('harvest-store-manage') }}" class="btn btn-professional btn-primary-professional">
                <i class="fas fa-arrow-left me-2"></i>Back to Sales History
            </a>
        </div>

        <!-- Report Card -->
        <div class="card report-card">
            <!-- Header -->
            <div class="report-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-seedling me-3"></i>
                            Sales Report for: <span class="fw-bold">{{ $harvest->seed_name ?? 'N/A' }}</span>
                        </h2>
                        <p class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Generated on: {{ date('F j, Y') }}
                        </p>
                    </div>
                    <!--<div class="col-md-4 text-end mt-3 mt-md-0">-->
                    <!--    <button class="btn btn-professional btn-secondary-professional" onclick="window.print()">-->
                    <!--        <i class="fas fa-print me-2"></i>Print Report-->
                    <!--    </button>-->
                    <!--</div>-->
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
                <!-- Stock Summary Card -->
                @if($totalSales > 0)
                <div class="p-4 pb-0">
                    <div class="stock-summary-card">
                        <div class="stock-summary-header">
                            <div class="row align-items-center">
                                <div class="col-md-3 col-6">
                                    <div class="stock-metric">
                                        <span class="stock-metric-label">
                                            <i class="fas fa-seedling me-1"></i>Total Harvest
                                        </span>
                                        <div class="stock-metric-value">{{ number_format($harvest->total_mt, 2) }} MT</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="stock-metric">
                                        <span class="stock-metric-label">
                                            <i class="fas fa-shipping-fast me-1"></i>Sold
                                        </span>
                                        <div class="stock-metric-value text-warning">{{ number_format($totalQuantity, 2) }} MT</div>
                                        <div class="stock-metric-sub">({{ number_format(($totalQuantity / $harvest->total_mt) * 100, 1) }}%)</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="stock-metric">
                                        <span class="stock-metric-label">
                                            <i class="fas fa-warehouse me-1"></i>Remaining
                                        </span>
                                        <div class="stock-metric-value text-success"><div class="stock-metric-value text-success">{{ number_format($harvest->yield_mt, 2) }} MT</div></div>
                                        <div class="stock-metric-sub">({{ number_format((($harvest->total_mt - $totalQuantity) / $harvest->total_mt) * 100, 1) }}%)</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="stock-metric">
                                        <span class="stock-metric-label">
                                <!--             <h3>₹{{ number_format($totalRevenue, 0) }}</h3>-->
                                <!--<p>Total Revenue</p>-->
                                            <i class=""></i>Total Revenue (Rs)
                                        </span>
                                        <div class="stock-metric-value" style="color:white;">{{ number_format($totalRevenue, 0) }}</div>
                                        
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Statistics Cards -->
                <!--<div class="p-4">-->
                <!--    <div class="row">-->
                <!--        <div class="col-lg-3 col-md-6 col-12">-->
                <!--            <div class="stats-card">-->
                <!--                <h3>{{ $totalSales }}</h3>-->
                <!--                <p>Total Sales</p>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--        <div class="col-lg-3 col-md-6 col-12">-->
                <!--            <div class="stats-card">-->
                <!--                <h3>₹{{ number_format($totalRevenue, 0) }}</h3>-->
                <!--                <p>Total Revenue</p>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--        <div class="col-lg-3 col-md-6 col-12">-->
                <!--            <div class="stats-card">-->
                <!--                <h3>{{ number_format($totalQuantity, 2) }} MT</h3>-->
                <!--                <p>Total Sold</p>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--        <div class="col-lg-3 col-md-6 col-12">-->
                <!--            <div class="stats-card">-->
                <!--                <h3>₹{{ number_format($avgPrice, 0) }}</h3>-->
                <!--                <p>Avg. Price/MT</p>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!-- Sales Details Table -->
                <div class="px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table modern-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <i class="fas fa-hashtag me-2"></i>Sale #
                                    </th>
                                    <th>
                                        <i class="fas fa-user me-2"></i>Buyer
                                    </th>
                                    <th>
                                        <i class="fas fa-calendar me-2"></i>Date
                                    </th>
                                    <th class="text-end">
                                        <i class="fas fa-weight-hanging me-2"></i>Quantity (MT)
                                    </th>
                                    <th class="text-end">
                                        <i class="fas fa-tag me-2"></i>Price/MT (Rs)
                                    </th>
                                    <th class="text-end">
                                        <i class=""></i>Total Amount (Rs)
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-comment me-2"></i>Remark
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $index => $sale)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge-professional badge-primary">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <div class="buyer-name">{{ $sale->buyer_name ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            <div class="date-display">{{ date('d-m-y', strtotime($sale->sale_date)) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <span class="quantity-display">{{ number_format($sale->sold_mt, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="price-display">{{ number_format($sale->sale_price_per_mt, 0) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="price-display fs-5">{{ number_format($sale->total_price, 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-professional {{ strtolower($sale->remarks) === 'ok' ? 'badge-success' : 'badge-warning' }}">
                                                {{ strtoupper($sale->remarks ?? 'OK') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="no-data">
                                            <i class="fas fa-search"></i>
                                            <h5>No sales records found</h5>
                                            <p>This harvest item has not been sold yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Footer -->
              
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
@endsection
