@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">


    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f8f7; /* Very light green/gray */
        }

        .summary-container-wrapper {
            padding: 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dde2e1;
        }

        .summary-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #3A4D39; /* Dark Green */
        }

        .stats-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card-summary {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.07);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .stat-card-summary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card-summary .icon-area {
            font-size: 1.8rem; /* Larger icon */
            padding: 15px;
            border-radius: 10px;
            margin-right: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .stat-card-summary.sites .icon-area { background-color: #5E8C6A; } /* Medium Green */
        .stat-card-summary.area .icon-area { background-color: #8AB895; } /* Lighter Green */
        .stat-card-summary.production .icon-area { background-color: #A8D5BA; } /* Even Lighter Green */
        .stat-card-summary.machines .icon-area { background-color: #C9E8D4; color: #3A4D39} /* Palest Green, dark icon */


        .stat-card-summary .info-area .title {
            font-size: 0.95rem;
            color: #6c757d; /* Muted text color */
            margin-bottom: 4px;
            font-weight: 500;
        }
        .stat-card-summary .info-area .value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #3A4D39; /* Dark Green */
        }

        .summary-table-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        .table-header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table-header-controls h2 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #3A4D39;
        }
        .search-bar-summary {
            max-width: 300px;
        }
        .search-bar-summary .form-control {
            border-radius: 8px;
            border: 1px solid #dde2e1;
        }
        .search-bar-summary .form-control:focus {
            border-color: #5E8C6A;
            box-shadow: 0 0 0 0.2rem rgba(94, 140, 106, 0.25);
        }


        .table-summary {
            width: 100%;
            border-collapse: separate; /* Allows for border-radius on cells if needed */
            border-spacing: 0;
        }
        .table-summary th,
        .table-summary td {
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef; /* Light border for rows */
        }
        .table-summary thead th {
            background-color: #f8f9fa; /* Very light gray for header */
            color: #495057; /* Darker text for header */
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-summary tbody tr:hover {
            background-color: #f1f5f3; /* Slight hover effect */
        }
        .table-summary tbody td {
            font-size: 0.95rem;
            color: #343a40;
        }
        .table-summary .site-name-link {
            color: #5E8C6A; /* Medium Green for links */
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .table-summary .site-name-link:hover {
            color: #3A4D39; /* Darker Green on hover */
            text-decoration: underline;
        }

        /* First and last cells for rounded corners on table if desired */
        .table-summary thead th:first-child { border-top-left-radius: 8px; }
        .table-summary thead th:last-child { border-top-right-radius: 8px; }
        /* .table-summary tbody tr:last-child td:first-child { border-bottom-left-radius: 8px; } */
        /* .table-summary tbody tr:last-child td:last-child { border-bottom-right-radius: 8px; } */
        /* .table-summary tbody tr:last-child td { border-bottom: none; } */


        /* Responsive */
        @media (max-width: 768px) {
            .summary-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .summary-header h1 {
                margin-bottom: 10px;
            }
            .table-header-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            .table-header-controls h2 { margin-bottom: 10px; }
            .search-bar-summary { max-width: 100%; margin-bottom: 15px; }
            .table-summary th, .table-summary td {
                font-size: 0.85rem;
                padding: 8px 10px;
            }
            .stat-card-summary .info-area .value {
                font-size: 1.4rem;
            }
        }
        @media (max-width: 576px) {
             .stat-card-summary {
                flex-direction: column;
                align-items: flex-start;
            }
            .stat-card-summary .icon-area {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

    </style>
</head>
<body>
    {{-- Basic Navigation (Can be replaced with your @extends('layouts.app') if this page uses the main layout) --}}


    <div class="summary-container-wrapper">
        <div class="summary-header">
            <h1>Site Overview</h1>
            {{-- Optional: Add a button or link here if needed --}}
        </div>

        <div class="stats-cards-grid">
            <div class="stat-card-summary sites">
                <div class="icon-area"><i class="fas fa-map-marked-alt"></i></div>
                <div class="info-area">
                    <div class="title">Sites</div>
                    <div class="value">{{ $stats['total_sites'] ?? 0 }}</div>
                </div>
            </div>
            <div class="stat-card-summary area">
                <div class="icon-area"><i class="fas fa-chart-area"></i></div>
                <div class="info-area">
                    <div class="title">Total Area (Acre)</div>
                    <div class="value">{{ number_format($stats['total_area'] ?? 0, 0) }}</div>
                </div>
            </div>
            <div class="stat-card-summary production">
                <div class="icon-area"><i class="fas fa-seedling"></i></div>
                <div class="info-area">
                    <div class="title">Total Production (MT)</div>
                    <div class="value">{{ number_format($stats['total_production'] ?? 0, 0) }}</div>
                </div>
            </div>
            <div class="stat-card-summary machines">
                <div class="icon-area"><i class="fas fa-tractor"></i></div>
                <div class="info-area">
                    <div class="title">Total Machines</div>
                    <div class="value">{{ $stats['total_machines'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="summary-table-container">
            <div class="table-header-controls">
                <h2>Site Details</h2>
                <div class="search-bar-summary">
                    <input type="text" id="siteSearchInput" class="form-control" placeholder="Search sites...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-summary text-white" id="sitesTable">
                    <thead>
                        <tr>
                            <th style="color: white;">Sr. No.</th>
                            <th style="color: white;">Site Name</th>
                            <th style="color: white;">Unit Incharge</th>
                            <th style="color: white;">Area (Acre)</th>
                            <th style="color: white;">Fertilizer Used</th>
                            <th style="color: white;">Year</th>
                            <th style="color: white;">Crop Prod. (MT)</th>
                            <th style="color: white;">Fertilizer Stock (kg)</th>
                            <th style="color: white;">Seed Stock (kg)</th>
                            <th style="color: white;">Machines</th>
                            <th style="color: white;">Tractors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $displayIndex = 1; @endphp {{-- Initialize a counter for Sr. No. --}}
                        @forelse ($sites_data as $site)
                            @if (!empty($site['site_name'])) {{-- Only process and show row if site_name is not empty/null --}}
                                <tr>
                                    <td>{{ $displayIndex++ }}</td> {{-- Use the custom counter --}}
                                    <td>
                                        <a href="{{ route('dashboard') }}?site_name={{ urlencode($site['site_id']) }}" class="site-name-link">
                                           
                                                {{ $site['site_name'] }} {{-- It's guaranteed non-empty here --}}
                                            
                                        </a>
                                    </td>
                                    <td>
                                        
                                            {{ !empty($site['unit_incharge']) ? $site['unit_incharge'] : '-' }}
                                         
                                    </td>
                                    <td>{{ ($val = $site['area_acre'] ?? null) && $val != 0 ? number_format($val, 0) : '-' }}</td>
                                    <td>{{ !empty($site['fertilizer_used']) ? $site['fertilizer_used'] : '-' }}</td>
                                    <td>{{ !empty($site['year']) ? $site['year'] : '-' }}</td>
                                    <td>{{ ($val = $site['crop_production_mt'] ?? null) && $val != 0 ? number_format($val, 0) : '-' }}</td>
                                    <td>{{ ($val = $site['fertilizer_stock_kg'] ?? null) && $val != 0 ? number_format($val, 0) : '-' }}</td>
                                    <td>{{ ($val = $site['seed_stock_kg'] ?? null) && $val != 0 ? number_format($val, 0) : '-' }}</td>
                                    <td>{{ ($val = $site['machines_count'] ?? null) && $val != 0 ? $val : '-' }}</td>
                                    <td>{{ ($val = $site['tractors_count'] ?? null) && $val != 0 ? $val : '-' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No site data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side search for the table
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('siteSearchInput');
            const table = document.getElementById('sitesTable');
            const tableRows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const filter = searchInput.value.toLowerCase();
                    for (let i = 0; i < tableRows.length; i++) {
                        let rowVisible = false;
                        const cells = tableRows[i].getElementsByTagName('td');
                        for (let j = 0; j < cells.length; j++) {
                            if (cells[j]) {
                                if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                                    rowVisible = true;
                                    break;
                                }
                            }
                        }
                        tableRows[i].style.display = rowVisible ? '' : 'none';
                    }
                });
            }
        });
    </script>


    @endsection

@section('scripts')
    {{-- This section can be used for additional page-specific scripts if needed --}}
@endsection
