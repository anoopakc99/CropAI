<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Sidebar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    {{-- Assuming you are using Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* All of your new, modern CSS styles go here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        #layoutSidenav_nav {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #667E06 transparent;
        }

        #layoutSidenav_nav::-webkit-scrollbar { width: 6px; }
        #layoutSidenav_nav::-webkit-scrollbar-track { background: transparent; }
        #layoutSidenav_nav::-webkit-scrollbar-thumb { background: #667E06; border-radius: 3px; }

        .sb-sidenav {
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fc 100%);
            border-right: 1px solid #e3e6f0;
            box-shadow: 0 0 35px 0 rgba(154, 161, 171, 0.15);
        }

        .sb-sidenav-menu { padding: 20px 0; }
        .nav { padding: 0px; }

        .nav-link {
            margin: 3px 0;
            padding: 7px 15px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            color: #5a5c69;
        }

        .nav-link:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 6, 0.2);
            background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
            color: #fff!important;
        }

        .nav-link:hover .sb-nav-link-icon i,
        .nav-link:hover .master-chevron {
            color: white !important;
            transform: scale(1.1);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
            color: #fff!important;
            box-shadow: 0 8px 25px rgba(102, 126, 6, 0.3);
            transform: translateX(3px);
        }

        .nav-link.active .sb-nav-link-icon i,
        .nav-link.active .master-chevron {
            color: white !important;
        }

        .sb-nav-link-icon {
            width: 24px; height: 15;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            background: rgba(102, 126, 6, 0.1);
            transition: all 0.3s ease;
        }

        .nav-link:hover .sb-nav-link-icon,
        .nav-link.active .sb-nav-link-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .sb-nav-link-icon i { transition: all 0.3s ease; }
        .master-chevron { transition: transform 0.3s ease; font-size: 12px; }

        .submenu {
            background: rgba(102, 126, 6, 0.05);
            border-radius: 12px;
            margin: 5px 0;
            padding: 8px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .submenu .nav-link {
            margin: 3px 0; 
            font-size: 14x;
            padding-left: 20px !important;
            border-radius: 8px;
        }

        .submenu .nav-link:hover {
            background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
            transform: translateX(8px);
        }

        .submenu .nav-link .fas { font-size: 14px; margin-right: 12px; }

        /* Nested submenu styles */
        .nested-submenu {
            background: rgba(102, 126, 6, 0.08);
            border-radius: 8px;
            margin: 3px 0;
            padding: 5px;
            margin-left: 15px;
        }

        .nested-submenu .nav-link {
            font-size: 12px;
            padding: 8px 12px !important;
            margin: 2px 0;
        }

        .nested-submenu .nav-link:hover {
            transform: translateX(5px);
        }

        .sidebar-brand {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 10px;
        }

        .sidebar-brand h4 {
            color: #667E06; font-weight: 700;
            margin: 0; font-size: 18px;
        }
        .icon-space {
          margin-right: 8px; /* adjust spacing */
        }

        .sidebar-brand .subtitle { color: #858796; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div id="layoutSidenav_nav">
       <nav class="sb-sidenav accordion" id="sidenavAccordion">
     

            <div class="sb-sidenav-menu">
                <div class="nav">
                    {{-- PHP Logic to determine visibility and active states --}}
                    @php
                        $showManageUser = false;
                        $showMasterMenu = false;
                        if (Auth::check()) {
                            $userRole = Auth::user()->role;
                            $userSiteId = Auth::user()->site_id; // This is expected to be a string like "1,2,3"

                            if ($userRole == 1) {
                             
                                $showManageUser = true;
                                $showMasterMenu = true;
                            } elseif ($userRole == 2) {
                            
                                $showManageUser = true;
                                $showMasterMenu = true;
                            } elseif ($userRole == 3) {
                             
                                $showManageUser = true;
                                $showMasterMenu = true;
                            }
                            
                        }

                        // FIXED: Check if any master link is active to expand the menu
                        // Only check for actual master submenu routes, not main menu routes
                        $isMasterActive = request()->is([
                            'land', 'seeds*', 'machines', 'master/tractor', 'fertilizers',
                            'diesels', 'master-manpower', 'chemicals', 'water_sources','master/consolidated', 'master-seed','seed-veriety',
                            'master-fertilizer','land', 'block-master'
                            
                        ]);

                        // Check if seed stock submenu should be active
                        $isSeedStockActive = request()->is(['seeds*', 'master-seeds']);
                        $isChemicalStockActive = request()->is(['chemicals*', 'master-chemicals']);
                        $isFertilizerStockActive = request()->is(['fertilizers*', 'master-fertilizer']);
                        $isBlockStockActive = request()->is(['land*', 'master-blocks']);
                    @endphp

                    <a class="nav-link d-flex align-items-center {{ request()->is('site-summary') ? 'active' : '' }}" href="{{ route('site.summary') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-th-large"></i></div>
                        <div>Dashboard </div>
                    </a>

                    @if($showManageUser)
                        <a class="nav-link d-flex align-items-center {{ request()->is('user') ? 'active' : '' }}" href="{{ url('user') }}">
                            <div class="sb-nav-link-icon me-3"><i class="fas fa-user"></i></div>
                            <div>Manage User</div>
                        </a>
                    @endif

                    @if($showMasterMenu)
                        <a class="nav-link d-flex align-items-center justify-content-between master-dropdown {{ $isMasterActive ? 'active' : '' }}" href="#" onclick="toggleMasterMenu(event)">
                            <div class="d-flex align-items-center">
                                <div class="sb-nav-link-icon me-3"><i class="fas fa-table"></i></div>
                                <div>Master</div>
                            </div>
                            <i id="masterChevron" class="fas fa-chevron-down master-chevron icon-space"></i>
                        </a>

                        <div id="masterSubmenu" class="submenu" style="display: none;">
                            <a class="nav-link d-flex align-items-center {{ request()->is('master/consolidated') ? 'active' : '' }}" href="{{ url('master/consolidated') }}">
                                <i class="fas fa-cogs icon-space"></i><span> Consolidated</span>
                            </a>
                            
                             {{-- Block  with nested submenu --}}
                            <a class="nav-link d-flex align-items-center justify-content-between block-stock-dropdown {{ $isBlockStockActive ? 'active' : '' }}" href="#" onclick="toggleBlockMenu(event)">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map icon-space"></i>
                                    <span>Blocks</span>
                                </div>
                                <i id="blockChevron" class="fas fa-chevron-down master-chevron icon-space"></i>
                            </a>
                            
                            {{-- Nested Submenu under Block --}}
                            <div id="blockSubmenu" class="nested-submenu" style="display: none;">
                                <a class="nav-link d-flex align-items-center {{ request()->is('block-master') ? 'active' : '' }}" href="{{ url('master-blocks') }}">
                                    <i class="fas fa-map icon-space"></i> <span> Master Block</span>
                                <!--</a>-->
                                
                                <a class="nav-link d-flex align-items-center {{ request()->is('land') ? 'active' : '' }}" href="{{ url('land') }}">
                                    <i class="fas fa-map icon-space"></i><span>Land Details</span>
                                </a>
                            </div>
                            
                            
 
                            {{-- Seed Stock with nested submenu --}}
                            <a class="nav-link d-flex align-items-center justify-content-between seed-stock-dropdown {{ $isSeedStockActive ? 'active' : '' }}" href="#" onclick="toggleSeedStockMenu(event)">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-seedling icon-space"></i>
                                    <span>Seeds</span>
                                </div>
                                <i id="seedStockChevron" class="fas fa-chevron-down master-chevron icon-space"></i>
                            </a>
                            
                            {{-- Nested Submenu under Seed Stock --}}
                            <div id="seedStockSubmenu" class="nested-submenu" style="display: none;">
                                <a class="nav-link d-flex align-items-center {{ request()->is('master-seed') ? 'active' : '' }}" href="{{ url('master-seed') }}">
                                    <i class="fas fa-leaf icon-space"></i><span>Master Seed</span>
                                <a class="nav-link d-flex align-items-center {{ request()->is('seeds') ? 'active' : '' }}" href="{{ url('seeds') }}">
                                    <i class="fas fa-leaf icon-space"></i><span>Seed Stock</span>
                                </a>
                            </div>
                            

                            <a class="nav-link d-flex align-items-center {{ request()->is('machines') ? 'active' : '' }}" href="{{ url('machines') }}">
                                <i class="fas fa-cogs icon-space"></i><span>Machineries</span>
                            </a>
                            <a class="nav-link d-flex align-items-center {{ request()->is('master/tractor') ? 'active' : '' }}" href="{{ url('master/tractor') }}">
                                <i class="fas fa-tractor icon-space"></i><span>Tractors</span>
                            </a>
                             
                              {{-- Fertilizer Stock with nested submenu --}}
                            <a class="nav-link d-flex align-items-center justify-content-between fertilizer-stock-dropdown {{ $isFertilizerStockActive ? 'active' : '' }}" href="#" onclick="toggleFertilzerStockMenu(event)">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tint icon-space"></i>
                                    <span> Fertilizer</span>
                                </div>
                                <i id="fertilizerStockChevron" class="fas fa-chevron-down master-chevron icon-space"></i>
                            </a>
                            
                            {{-- Nested Submenu under Fertilizer Stock --}}
                            <div id="fertilizerStockSubmenu" class="nested-submenu" style="display: none;">
                                <a class="nav-link d-flex align-items-center {{ request()->is('master-fertilizer') ? 'active' : '' }}" href="{{ url('/master-fertilizer/index') }}">
                                    <i class="fas fa-tint icon-space"></i> <span> Master Fertilizer</span>
                                </a>
                                <a class="nav-link d-flex align-items-center {{ request()->is('fertilizer') ? 'active' : '' }}" href="{{ url('fertilizers') }}">
                                    <i class="fas fa-tint icon-space"></i> <span> Fertilizer Stock</span>
                                </a>
                            </div>
                            <a class="nav-link d-flex align-items-center {{ request()->is('diesels') ? 'active' : '' }}" href="{{ url('diesels') }}">
                                <i class="fas fa-gas-pump icon-space"></i><span>Diesel</span>
                            </a>
                            <a class="nav-link d-flex align-items-center {{ request()->is('master-manpower') ? 'active' : '' }}" href="{{ url('master-manpower') }}">
                                <i class="fas fa-users icon-space"></i><span>Man Power</span>
                            </a>
                            <!--<a class="nav-link d-flex align-items-center {{ request()->is('chemicals') ? 'active' : '' }}" href="{{ url('chemicals') }}">-->
                            <!--    <i class="fas fa-tint icon-space"></i><span>Chemical</span>-->
                            <!--</a>-->
                             {{-- Chemical Stock with nested submenu --}}
                            <a class="nav-link d-flex align-items-center justify-content-between chemical-stock-dropdown {{ $isChemicalStockActive ? 'active' : '' }}" href="#" onclick="toggleChemicalStockMenu(event)">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tint icon-space"></i>
                                    <span> Chemical Stock</span>
                                </div>
                                <i id="chemicalStockChevron" class="fas fa-chevron-down master-chevron icon-space"></i>
                            </a>
                            
                            {{-- Nested Submenu under Seed Stock --}}
                            <div id="chemicalStockSubmenu" class="nested-submenu" style="display: none;">
                                <a class="nav-link d-flex align-items-center {{ request()->is('master-chemical') ? 'active' : '' }}" href="{{ url('/master-chemical/index') }}">
                                    <i class="fas fa-tint icon-space"></i> <span> Master Chemical</span>
                                <a class="nav-link d-flex align-items-center {{ request()->is('chemicals') ? 'active' : '' }}" href="{{ url('chemicals') }}">
                                    <i class="fas fa-tint icon-space"></i> <span> Chemical Stock</span>
                                </a>
                            </div>
                            <a class="nav-link d-flex align-items-center {{ request()->is('water_sources') ? 'active' : '' }}" href="{{ url('water_sources') }}">
                                <i class="fas fa-water icon-space"></i><span>Water Sources</span>
                            </a>
                        </div>
                    @endif

                    <a class="nav-link d-flex align-items-center {{ request()->is('area-leveling') ? 'active' : '' }}" href="{{ url('area-leveling') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-mountain"></i></div>
                        <div>Area Leveling</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('pre-land-preparation') ? 'active' : '' }}" href="{{ url('pre-land-preparation') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-trowel-bricks"></i></div>
                        <div>Pre field Prep. of Land</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('pre-irrigation') ? 'active' : '' }}" href="{{ url('pre-irrigation') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-tint"></i></div>
                        <div>Pre Irrigation</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('land-preparation') ? 'active' : '' }}" href="{{ url('land-preparation') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-tractor"></i></div>
                        <div>Land Preparation</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('sowing-operations') ? 'active' : '' }}" href="{{ url('sowing-operations') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-seedling"></i></div>
                        <div>Sowing</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/post-irrigation') ? 'active' : '' }}" href="{{ url('admin/post-irrigation') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-water"></i></div>
                        <div>Post Irrigation</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/fertilizers-app') ? 'active' : '' }}" href="{{ url('admin/fertilizers-app') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-flask"></i></div>
                        <div>Fertilizers</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('inter-culture') ? 'active' : '' }}" href="{{ url('inter-culture') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-leaf"></i></div>
                        <div>Inter Culture</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('crop-protection') ? 'active' : '' }}" href="{{ url('crop-protection') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-shield-alt"></i></div>
                        <div>Crop Protection</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('activity-monitoring') ? 'active' : '' }}" href="{{ url('activity-monitoring') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-chart-line"></i></div>
                        <div>Activity Monitoring</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/harvest') ? 'active' : '' }}" href="{{ url('admin/harvest') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-wheat-awn"></i></div>
                        <div>Harvest</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/hay-making') ? 'active' : '' }}" href="{{ url('admin/hay-making') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-wheat-awn"></i></div>
                        <div>Hay Making</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/silage-making') ? 'active' : '' }}" href="{{ url('admin/silage-making') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-flask"></i></div>
                        <div>Silage Making</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('harvest-store-manage') ? 'active' : '' }}" href="{{ url('harvest-store-manage') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-flask"></i></div>
                        <div>Yield Production Summary</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('admin/cost-analysis') ? 'active' : '' }}" href="{{ url('admin/cost-analysis') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-calculator"></i></div>
                        <div>Cost Analysis</div>
                    </a>
                     <a class="nav-link d-flex align-items-center {{ request()->is('contact-farming') ? 'active' : '' }}" href="{{ url('contact-farming') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-calculator"></i></div>
                        <div>Contract Farming</div>
                    </a>
                    <a class="nav-link d-flex align-items-center {{ request()->is('crop-cycle') ? 'active' : '' }}" href="{{ url('crop-cycle') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-calculator"></i></div>
                        <div>Crop Cycle</div>
                    </a>
                
                    <a class="nav-link d-flex align-items-center {{ request()->is('notification/create') ? 'active' : '' }}" href="{{ url('notification/create') }}">
                        <div class="sb-nav-link-icon me-3"><i class="fas fa-bell"></i></div>
                        <div>Notification</div>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <script>
        function toggleMasterMenu(event) {
            event.preventDefault();
            const submenu = document.getElementById('masterSubmenu');
            const chevron = document.getElementById('masterChevron');
            
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        function toggleSeedStockMenu(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent parent menu from closing
            const submenu = document.getElementById('seedStockSubmenu');
            const chevron = document.getElementById('seedStockChevron');
            
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            }
        }
        function toggleBlockMenu(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent parent menu from closing
            const submenu = document.getElementById('blockSubmenu');
            const chevron = document.getElementById('blockChevron');
            
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            }
        }
        
        function toggleChemicalStockMenu(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent parent menu from closing
            const submenu = document.getElementById('chemicalStockSubmenu');
            const chevron = document.getElementById('chemicalStockChevron');
            
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            }
        }
        
          function toggleFertilzerStockMenu(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent parent menu from closing
            const submenu = document.getElementById('fertilizerStockSubmenu');
            const chevron = document.getElementById('fertilizerStockChevron');
            
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        // Initialize submenu state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const masterSubmenu = document.getElementById('masterSubmenu');
            const masterChevron = document.getElementById('masterChevron');
            const seedStockSubmenu = document.getElementById('seedStockSubmenu');
            const seedStockChevron = document.getElementById('seedStockChevron');
            
            // PHP variables for active states
            const isMasterActive = {{ $isMasterActive ? 'true' : 'false' }};
            const isSeedStockActive = {{ $isSeedStockActive ? 'true' : 'false' }};
            
            if (isMasterActive) {
                masterSubmenu.style.display = 'block';
                masterChevron.style.transform = 'rotate(180deg)';
            }

            if (isSeedStockActive) {
                // If seed stock is active, also show master menu
                masterSubmenu.style.display = 'block';
                masterChevron.style.transform = 'rotate(180deg)';
                
                // Show seed stock submenu
                seedStockSubmenu.style.display = 'block';
                seedStockChevron.style.transform = 'rotate(180deg)';
            }
        });
    </script>
</body>
</html>