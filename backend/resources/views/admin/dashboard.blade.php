@extends('admin.layouts.app')

@section('title', 'Dashboard Overview')

@section('content')

<div class="container-fluid px-4 py-4 dashboard-modern-wrapper">

    {{-- Enhanced Dashboard Header --}}
    <div class="glass-header mb-4 shadow-sm">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3">

                    <div>
                        <p class="text-muted small mb-0 d-flex align-items-center gap-2">
                            <span class="badge-status-live">
                                <span class="pulse-dot"></span> LIVE
                            </span>
                            <span class="v-divider"></span>
                            <i class="bi bi-calendar-check text-primary-blue"></i>
                            <span id="current-date" class="fw-600">{{ now()->format('l, F j, Y') }}</span>
                            <span class="v-divider"></span>
                            <span class="text-success fw-bold" style="font-size: 0.75rem;">
                                <i class="bi bi-arrow-repeat me-1"></i><span id="last-sync">Live Sync</span>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="d-flex gap-2 justify-content-lg-end align-items-center flex-wrap">
                    <button onclick="refreshDashboard()" class="btn btn-sm rounded-pill btn-outline-primary d-flex align-items-center" id="refresh-btn">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        <span>Refresh</span>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm rounded-pill btn-danger d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-2"></i>
                            Export
                        </button>
                        <ul class="dropdown-menu border-0 shadow-lg mt-2">
                            <li><a class="dropdown-item py-2" href="{{ route('admin.reports.index') }}"><i class="bi bi-file-pdf me-2 text-danger"></i>View Reports</a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="exportData('excel')"><i class="bi bi-file-excel me-2 text-success"></i>Export to Excel</a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="exportData('csv')"><i class="bi bi-file-text me-2 text-info"></i>Export to CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced System Status Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="status-card-modern grad-blue shadow-glow-blue">
                <div class="d-flex align-items-center">
                    <div class="status-icon-box shadow-sm">
                        <i class="bi bi-database"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-blue-100 text-uppercase tracking-wider fw-700" style="font-size: 0.65rem;">Database</small>
                        <h5 class="mb-0 text-white fw-800">{{ $stats['system_pulse']['db_connected'] ? 'Connected' : 'Offline' }}</h5>
                    </div>
                </div>
                <div class="status-indicator-bar {{ $stats['system_pulse']['db_connected'] ? 'status-active' : 'status-inactive' }}"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card-modern grad-indigo shadow-glow-indigo">
                <div class="d-flex align-items-center">
                    <div class="status-icon-box shadow-sm">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-blue-100 text-uppercase tracking-wider fw-700" style="font-size: 0.65rem;">Notifications</small>
                        <h5 class="mb-0 text-white fw-800">{{ $stats['fcm_ready'] ? 'Ready' : 'Setup' }}</h5>
                    </div>
                </div>
                <div class="status-indicator-bar status-warning"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card-modern grad-cyan shadow-glow-cyan">
                <div class="d-flex align-items-center">
                    <div class="status-icon-box shadow-sm">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-blue-100 text-uppercase tracking-wider fw-700" style="font-size: 0.65rem;">Avg. Processing</small>
                        <h5 class="mb-0 text-white fw-800">{{ $stats['avgProcessingTime'] ?? '0 days' }}</h5>
                    </div>
                </div>
                <div class="status-indicator-bar status-active"></div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.laundries.index', ['filter' => 'errors']) }}" class="text-decoration-none d-block"
               title="{{ ($stats['dataQuality']['data_entry_errors'] ?? 0) > 0 ? 'Click to view laundries with data errors' : 'No data errors found' }}">
                <div class="status-card-modern grad-navy shadow-glow-navy"
                     style="cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                     onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(15,23,42,0.55)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div class="d-flex align-items-center">
                        <div class="status-icon-box shadow-sm">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-blue-100 text-uppercase tracking-wider fw-700" style="font-size: 0.65rem;">Data Errors</small>
                            <h5 class="mb-0 text-white fw-800">{{ $stats['dataQuality']['data_entry_errors'] ?? 0 }}</h5>
                        </div>
                        <div class="ms-auto">
                            <i class="bi bi-arrow-right-circle text-white opacity-50 fs-5"></i>
                        </div>
                    </div>
                    <div class="status-indicator-bar {{ ($stats['dataQuality']['data_entry_errors'] ?? 0) > 0 ? 'status-warning' : 'status-inactive' }}"></div>
                </div>
            </a>
        </div>
    </div>

    {{-- Enhanced Main KPI Cards --}}
    <div class="row g-4 mb-4">
        {{-- Today's Laundries --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="laundries">
            <div class="kpi-card-modern shadow-sm">
                <div class="kpi-card-inner">
                    <div class="d-flex justify-content-between">
                        <div class="kpi-icon-glow icon-blue">
                            <i class="bi bi-basket3 text-primary-blue"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.laundries.index') }}?date={{ now()->format('Y-m-d') }}">View Today's Laundries</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.laundries.create') }}">Create New Laundry</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.reports.laundries') }}">Laundry Reports</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="kpi-label">Today Laundries</span>
                        <h2 class="kpi-value text-slate-800" data-kpi="todayLaundries">{{ $stats['todayLaundries'] }}</h2>
                        <div class="kpi-trend {{ $stats['laundriesChange'] >= 0 ? 'up' : 'down' }}">
                            <i class="bi {{ $stats['laundriesChange'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                            <span>{{ abs($stats['laundriesChange']) }}% vs yesterday</span>
                        </div>
                        <small class="text-muted d-block mt-1">Total: {{ $stats['totalLaundries'] ?? 0 }} laundries in system</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today's Revenue --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="revenue">
            <div class="kpi-card-modern shadow-sm">
                <div class="kpi-card-inner">
                    <div class="d-flex justify-content-between">
                        <div class="kpi-icon-glow icon-green">
                            <i class="bi bi-cash-coin text-success"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}?period=today">Today's Revenue Report</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}?period=month">Monthly Report</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.laundries.index') }}?status=paid">View Paid Laundries</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="kpi-label">Today's Revenue</span>
                        <h2 class="kpi-value text-slate-800" data-kpi="todayRevenue">₱{{ number_format($stats['todayRevenue'], 0) }}</h2>
                        <div class="kpi-trend {{ $stats['revenueChange'] >= 0 ? 'up' : 'down' }}">
                            <i class="bi {{ $stats['revenueChange'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                            <span>{{ abs($stats['revenueChange']) }}% vs yesterday</span>
                        </div>
                        <small class="text-muted d-block mt-1">Month: ₱{{ number_format($stats['thisMonthRevenue'] ?? 0, 0) }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Customers --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="customers">
            <div class="kpi-card-modern shadow-sm">
                <div class="kpi-card-inner">
                    <div class="d-flex justify-content-between">
                        <div class="kpi-icon-glow icon-indigo">
                            <i class="bi bi-people text-indigo"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.customers.index') }}">View All Customers</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.customers.create') }}">Add New Customer</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.customers.index') }}?new=this_month">New This Month</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="kpi-label">Active Customers</span>
                        <h2 class="kpi-value text-slate-800" data-kpi="activeCustomers">{{ number_format($stats['activeCustomers']) }}</h2>
                        <div class="kpi-trend up">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span>+{{ $stats['newCustomersThisMonth'] ?? 0 }} this month</span>
                        </div>
                        <small class="text-muted d-block mt-1">
                            @if(isset($stats['customerRegistrationSource']['app']))
                                {{ $stats['customerRegistrationSource']['app'] }} app users
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Unclaimed Items (Critical) --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="unclaimed">
            <a href="{{ route('admin.unclaimed.index') }}" class="text-decoration-none">
                <div class="kpi-card-modern shadow-sm border-danger-soft">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="kpi-icon-glow icon-red">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                        </div>
                        <span class="badge rounded-pill bg-danger-soft text-danger">CRITICAL</span>
                    </div>
                    <span class="kpi-label">Unclaimed Items</span>
                    <h2 class="kpi-value text-danger" data-kpi="unclaimedLaundry">{{ $stats['unclaimedLaundry'] }}</h2>
                    <div class="kpi-trend down">
                        <i class="bi bi-clock me-1"></i>
                        <span>Est. Loss: ₱{{ number_format($stats['estimatedUnclaimedLoss'] ?? 0, 0) }}</span>
                    </div>
                    <small class="text-muted d-block mt-1">Click to manage unclaimed items</small>
                    <div class="mt-3">
                        <a href="{{ route('admin.unclaimed.remindAll') }}" class="btn btn-sm btn-danger w-100 rounded-pill">
                            <i class="bi bi-bell-fill me-1"></i>Send Reminders
                        </a>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Enhanced Quick Actions Grid --}}
    <div class="modern-card shadow-sm mb-4">
        <div class="card-header-modern border-0 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-800 text-slate-800">Quick Actions</h6>
                <small class="text-muted">Frequently accessed functions</small>
            </div>
            <span class="badge bg-primary-blue bg-opacity-10 text-primary-blue">
                <i class="bi bi-lightning me-1"></i>Instant Access
            </span>
        </div>
        <div class="card-body-modern">
            <div class="row g-3">
                @php
                    $quickActions = [
                        ['route' => 'admin.laundries.create', 'icon' => 'bi-plus-lg', 'label' => 'Create Laundry', 'desc' => 'New laundry', 'color' => 'blue'],
                        ['route' => 'admin.customers.create', 'icon' => 'bi-person-plus', 'label' => 'New Customer', 'desc' => 'Register', 'color' => 'indigo'],
                        ['route' => 'admin.pickups.index', 'icon' => 'bi-truck', 'label' => 'Pickups', 'desc' => 'Delivery', 'color' => 'cyan'],
                        ['route' => 'admin.unclaimed.index', 'icon' => 'bi-box-seam', 'label' => 'Unclaimed', 'desc' => 'Inventory', 'color' => 'red'],
                        ['route' => 'admin.promotions.create', 'icon' => 'bi-percent', 'label' => 'Promotions', 'desc' => 'Marketing', 'color' => 'purple'],
                        ['route' => 'admin.reports.index', 'icon' => 'bi-graph-up', 'label' => 'Reports', 'desc' => 'Analytics', 'color' => 'navy'],
                    ];
                @endphp
                @foreach($quickActions as $action)
                    @if(Route::has($action['route']))
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <a href="{{ route($action['route']) }}" class="launch-action-btn {{ $action['color'] }}">
                            <div class="launch-icon shadow-sm"><i class="bi {{ $action['icon'] }}"></i></div>
                            <h6 class="action-label mb-1">{{ $action['label'] }}</h6>
                            <small class="text-muted">{{ $action['desc'] }}</small>
                        </a>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ✅ STICKY TABS - NATURALLY POSITIONED BELOW QUICK ACTIONS --}}
    <div class="modern-tabs-sticky">
        <ul class="nav nav-segmented" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                    <i class="bi bi-speedometer2 me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="laundries-tab" data-bs-toggle="pill" data-bs-target="#laundries" type="button">
                    <i class="bi bi-basket me-2"></i>Laundries
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="customers-tab" data-bs-toggle="pill" data-bs-target="#customers" type="button">
                    <i class="bi bi-people me-2"></i>Customers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="operations-tab" data-bs-toggle="pill" data-bs-target="#operations" type="button">
                    <i class="bi bi-gear me-2"></i>Operations
                </button>
            </li>
        </ul>
    </div>

    {{-- Tabs Content --}}
    <div class="tab-content pt-4">
        {{-- Enhanced Overview Tab --}}
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-4">
                {{-- Enhanced Laundry Pipeline --}}
                <div class="col-lg-8">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <h6 class="mb-0 fw-800 text-slate-800">Laundry Pipeline</h6>
                            <small class="text-muted">Current status of all laundries</small>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-3">
                                @php
                                    $pipelineStatuses = [
                                        ['status' => 'received', 'label' => 'Received', 'icon' => 'bi-inbox', 'color' => 'blue', 'description' => 'Laundries received and awaiting processing'],
                                        ['status' => 'processing', 'label' => 'Processing', 'icon' => 'bi-gear', 'color' => 'indigo', 'description' => 'Currently being processed'],
                                        ['status' => 'ready', 'label' => 'Ready', 'icon' => 'bi-check-circle', 'color' => 'cyan', 'description' => 'Ready for pickup/delivery'],
                                        ['status' => 'completed', 'label' => 'Completed', 'icon' => 'bi-check2-all', 'color' => 'green', 'description' => 'Completed laundries'],
                                        ['status' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'bi-x-circle', 'color' => 'red', 'description' => 'Cancelled laundries']
                                    ];
                                @endphp
                                @foreach($pipelineStatuses as $status)
                                    <div class="col-md-4">
                                        <div class="pipeline-tile {{ $status['color'] }} shadow-sm">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="p-icon">
                                                    <i class="bi {{ $status['icon'] }}"></i>
                                                </div>
                                                <span class="p-count">{{ $stats['laundryPipeline'][$status['status']] ?? 0 }}</span>
                                            </div>
                                            <h6 class="p-label">{{ $status['label'] }}</h6>
                                            <p class="pipeline-desc small text-muted mb-0">{{ $status['description'] }}</p>
                                            <div class="mt-3">
                                                <a href="{{ route('admin.laundries.index') }}?status={{ $status['status'] }}" class="btn btn-sm btn-{{ $status['color'] }}-light w-100">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Enhanced Unclaimed Breakdown --}}
                <div class="col-lg-4">
                    <div class="modern-card shadow-sm border-danger-soft">
                        <div class="card-header-modern bg-danger-soft">
                            <h6 class="mb-0 fw-800 text-danger">Unclaimed Items</h6>
                            <small class="text-danger">Requires immediate attention</small>
                        </div>
                        <div class="card-body-modern">
                            @php
                                $unclaimedCategories = [
                                    ['label' => '0-7 Days', 'key' => 'within_7_days', 'color' => 'success', 'icon' => 'bi-clock'],
                                    ['label' => '1-2 Weeks', 'key' => '1_to_2_weeks', 'color' => 'warning', 'icon' => 'bi-clock-history'],
                                    ['label' => '2-4 Weeks', 'key' => '2_to_4_weeks', 'color' => 'orange', 'icon' => 'bi-exclamation-circle'],
                                    ['label' => '>1 Month', 'key' => 'over_1_month', 'color' => 'danger', 'icon' => 'bi-exclamation-triangle'],
                                ];
                            @endphp
                            @foreach($unclaimedCategories as $category)
                                <div class="unclaimed-row mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="u-indicator bg-{{ $category['color'] }} me-3">
                                                <i class="bi {{ $category['icon'] }} text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $category['label'] }}</h6>
                                                <small class="text-muted">Time since completion</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-0 text-{{ $category['color'] }}">{{ $stats['unclaimedBreakdown'][$category['key']] ?? 0 }}</h4>
                                            <small class="text-muted">items</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="alert alert-danger mt-4 bg-danger-soft border-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-3 fs-4 text-danger"></i>
                                    <div>
                                        <strong>Estimated Financial Impact:</strong>
                                        <h5 class="mb-0 mt-1 text-danger">₱{{ number_format($stats['estimatedUnclaimedLoss'] ?? 0, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Enhanced Revenue Chart --}}
                <div class="col-lg-12">
                    <div class="modern-card shadow-sm">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Revenue Trend</h6>
                                <small class="text-muted">Last 7 days performance</small>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="chart-container">
                                <canvas id="revenueChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ADVANCED: Pipeline by Branch + Customer Breakdown --}}
                <div class="col-lg-12">
                    <div class="bpb-wrapper modern-card shadow-sm overflow-hidden">

                        {{-- Animated gradient header --}}
                        <div class="bpb-main-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bpb-header-icon">
                                        <i class="bi bi-shop"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-800 text-white">Pipeline by Branch</h5>
                                        <small class="text-white" style="opacity:.75;">Laundry status breakdown per branch</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @php
                                        $bpbDefs = [
                                            'received'   => ['label'=>'Received',   'hex'=>'#60a5fa', 'icon'=>'bi-inbox-fill'],
                                            'processing' => ['label'=>'Processing', 'hex'=>'#818cf8', 'icon'=>'bi-gear-fill'],
                                            'ready'      => ['label'=>'Ready',      'hex'=>'#22d3ee', 'icon'=>'bi-check-circle-fill'],
                                            'completed'  => ['label'=>'Completed',  'hex'=>'#34d399', 'icon'=>'bi-check2-all'],
                                            'cancelled'  => ['label'=>'Cancelled',  'hex'=>'#f87171', 'icon'=>'bi-x-circle-fill'],
                                        ];
                                    @endphp
                                    <div class="bpb-legend d-none d-lg-flex">
                                        @foreach($bpbDefs as $key => $def)
                                            <div class="bpb-legend-item">
                                                <span class="bpb-legend-dot" style="background:{{ $def['hex'] }};"></span>
                                                <span>{{ $def['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('admin.branches.index') }}"
                                       class="btn btn-sm btn-light rounded-pill fw-600">
                                        <i class="bi bi-shop me-1"></i>Manage Branches
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Branch cards --}}
                        <div class="card-body-modern">
                            @if(empty($stats['branchPipeline']))
                                <div class="text-center py-5">
                                    <div class="bpb-empty-icon mb-3"><i class="bi bi-shop"></i></div>
                                    <h6 class="text-muted fw-600">No branch data available</h6>
                                </div>
                            @else
                                <div class="row g-4">
                                    @php
                                        $bpbAccentColors = ['#3b82f6','#6366f1','#06b6d4','#10b981','#f59e0b','#ef4444'];
                                    @endphp
                                    @foreach($stats['branchPipeline'] as $branch)
                                        @php
                                            $accent     = $bpbAccentColors[$loop->index % count($bpbAccentColors)];
                                            $branchTotal = max($branch['total'], 1);
                                        @endphp
                                        <div class="col-xl-4 col-md-6">
                                            <div class="bpb-card" style="--bpb-accent: {{ $accent }};">
                                                <div class="bpb-accent-bar"></div>

                                                {{-- Card header --}}
                                                <div class="bpb-card-header">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="bpb-avatar"
                                                             style="background: linear-gradient(135deg, {{ $accent }}, {{ $accent }}aa);">
                                                            {{ strtoupper(substr($branch['name'], 0, 1)) }}
                                                        </div>
                                                        <div class="flex-grow-1 min-w-0">
                                                            <h6 class="mb-0 fw-700 bpb-branch-name">{{ $branch['name'] }}</h6>
                                                            <span class="bpb-total-badge mt-1 d-inline-block">
                                                                <i class="bi bi-basket me-1"></i>
                                                                {{ $branch['total'] }} laundries
                                                            </span>
                                                        </div>
                                                        <a href="{{ route('admin.laundries.index', ['branch' => $branch['id']]) }}"
                                                           class="bpb-view-link flex-shrink-0">
                                                            <i class="bi bi-box-arrow-up-right"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                {{-- Stacked progress bar --}}
                                                <div class="bpb-stacked-bar-wrap">
                                                    <div class="bpb-stacked-bar">
                                                        @foreach($bpbDefs as $statusKey => $def)
                                                            @php $pct = round(($branch['statuses'][$statusKey] / $branchTotal) * 100, 1); @endphp
                                                            @if($pct > 0)
                                                                <div class="bpb-bar-seg"
                                                                     style="width:{{ $pct }}%;background:{{ $def['hex'] }};"
                                                                     data-bs-toggle="tooltip"
                                                                     data-bs-title="{{ $def['label'] }}: {{ $branch['statuses'][$statusKey] }} ({{ $pct }}%)">
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>

                                                {{-- 5-tile status grid --}}
                                                <div class="bpb-status-grid">
                                                    @foreach($bpbDefs as $statusKey => $def)
                                                        @php
                                                            $cnt     = $branch['statuses'][$statusKey];
                                                            $pctTile = $branchTotal > 1 ? round(($cnt / $branchTotal) * 100) : 0;
                                                        @endphp
                                                        <a href="{{ route('admin.laundries.index', ['branch' => $branch['id'], 'status' => $statusKey]) }}"
                                                           class="bpb-status-tile text-decoration-none"
                                                           style="--tile-color:{{ $def['hex'] }};">
                                                            <div class="bpb-tile-icon">
                                                                <i class="bi {{ $def['icon'] }}"></i>
                                                            </div>
                                                            <div class="bpb-tile-count">{{ $cnt }}</div>
                                                            <div class="bpb-tile-label">{{ $def['label'] }}</div>
                                                            <div class="bpb-tile-pct">{{ $pctTile }}%</div>
                                                        </a>
                                                    @endforeach
                                                </div>

                                            </div>{{-- /bpb-card --}}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced Laundries Tab --}}
        <div class="tab-pane fade" id="laundries" role="tabpanel">
            <div class="row g-4">

                {{-- ── Service Type Pie Charts ─────────────────────── --}}
                @php
                    $scd = $stats['serviceChartData'] ?? [
                        'drop_off'     => ['labels'=>[],'counts'=>[],'revenues'=>[],'total'=>0],
                        'self_service' => ['labels'=>[],'counts'=>[],'revenues'=>[],'total'=>0],
                        'addon'        => ['labels'=>[],'counts'=>[],'revenues'=>[],'total'=>0],
                        'all_services' => [],
                        'grand_total'  => ['count'=>0,'revenue'=>0],
                    ];
                    $svcTypeConfig = [
                        'drop_off'     => ['label'=>'Full Service',  'icon'=>'bi-stars',       'gradient'=>'linear-gradient(135deg,#1e3a8a,#3b82f6)', 'hex'=>'#3b82f6','bg'=>'#eff6ff','border'=>'#bfdbfe'],
                        'self_service' => ['label'=>'Self Service',  'icon'=>'bi-person-gear', 'gradient'=>'linear-gradient(135deg,#4c1d95,#8b5cf6)', 'hex'=>'#8b5cf6','bg'=>'#faf5ff','border'=>'#ddd6fe'],
                        'addon'        => ['label'=>'Add-On',        'icon'=>'bi-plus-circle', 'gradient'=>'linear-gradient(135deg,#92400e,#f59e0b)', 'hex'=>'#f59e0b','bg'=>'#fffbeb','border'=>'#fde68a'],
                    ];
                @endphp

                {{-- Summary strips --}}
                @foreach(['drop_off','self_service','addon'] as $typeKey)
                    @php $tc = $svcTypeConfig[$typeKey]; $ts = $scd[$typeKey]; @endphp
                    <div class="col-md-4">
                        <div class="svc-summary-strip" style="--svc-hex:{{ $tc['hex'] }};--svc-bg:{{ $tc['bg'] }};--svc-border:{{ $tc['border'] }};">
                            <div class="svc-strip-icon-wrap" style="background:{{ $tc['gradient'] }};">
                                <i class="bi {{ $tc['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="svc-strip-label">{{ $tc['label'] }}</div>
                                <div class="svc-strip-count">{{ number_format($ts['total']) }} laundries</div>
                            </div>
                            <div class="text-end">
                                @php $gc = $scd['grand_total']['count']; $pct = $gc > 0 ? round(($ts['total']/$gc)*100) : 0; @endphp
                                <div class="svc-strip-pct" style="color:{{ $tc['hex'] }};">{{ $pct }}%</div>
                                <div class="svc-strip-pct-label">of all laundries</div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Full Service doughnut --}}
                <div class="col-lg-4">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="svc-chart-header-icon" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Full Service</h6>
                                    <small class="text-muted">Volume by service name</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="svc-chart-total-badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                                        {{ number_format($scd['drop_off']['total']) }} orders
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(!empty($scd['drop_off']['labels']))
                                <div class="svc-donut-wrap">
                                    <canvas id="fullServiceChart"></canvas>
                                    <div class="svc-donut-center">
                                        <div class="svc-donut-num">{{ number_format($scd['drop_off']['total']) }}</div>
                                        <div class="svc-donut-lbl">Full Service</div>
                                    </div>
                                </div>
                            @else
                                <div class="svc-empty-state">
                                    <i class="bi bi-stars"></i>
                                    <p>No full service data</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Self Service doughnut --}}
                <div class="col-lg-4">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="svc-chart-header-icon" style="background:linear-gradient(135deg,#4c1d95,#8b5cf6);">
                                    <i class="bi bi-person-gear"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Self Service</h6>
                                    <small class="text-muted">Volume by service name</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="svc-chart-total-badge" style="background:#faf5ff;color:#5b21b6;border-color:#ddd6fe;">
                                        {{ number_format($scd['self_service']['total']) }} orders
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(!empty($scd['self_service']['labels']))
                                <div class="svc-donut-wrap">
                                    <canvas id="selfServiceChart"></canvas>
                                    <div class="svc-donut-center">
                                        <div class="svc-donut-num">{{ number_format($scd['self_service']['total']) }}</div>
                                        <div class="svc-donut-lbl">Self Service</div>
                                    </div>
                                </div>
                            @else
                                <div class="svc-empty-state">
                                    <i class="bi bi-person-gear"></i>
                                    <p>No self service data</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Add-On doughnut --}}
                <div class="col-lg-4">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="svc-chart-header-icon" style="background:linear-gradient(135deg,#92400e,#f59e0b);">
                                    <i class="bi bi-plus-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Add-On</h6>
                                    <small class="text-muted">Volume by service name</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="svc-chart-total-badge" style="background:#fffbeb;color:#92400e;border-color:#fde68a;">
                                        {{ number_format($scd['addon']['total']) }} orders
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(!empty($scd['addon']['labels']))
                                <div class="svc-donut-wrap">
                                    <canvas id="addonServiceChart"></canvas>
                                    <div class="svc-donut-center">
                                        <div class="svc-donut-num">{{ number_format($scd['addon']['total']) }}</div>
                                        <div class="svc-donut-lbl">Add-On</div>
                                    </div>
                                </div>
                            @else
                                <div class="svc-empty-state">
                                    <i class="bi bi-plus-circle"></i>
                                    <p>No add-on data</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- All Service Types table --}}
                <div class="col-lg-8">
                    <div class="modern-card shadow-sm">
                        <div class="card-header-modern bg-transparent border-0 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">All Service Types</h6>
                                <small class="text-muted">Ranked by order volume</small>
                            </div>
                            <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="bi bi-gear me-1"></i>Manage Services
                            </a>
                        </div>
                        <div class="card-body-modern p-0">
                            @if(!empty($scd['all_services']))
                                <div class="table-responsive">
                                    <table class="table svc-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;">#</th>
                                                <th>Service</th>
                                                <th>Category</th>
                                                <th class="text-center">Orders</th>
                                                <th class="text-end">Revenue</th>
                                                <th style="width:130px;">Volume</th>
                                                <th style="width:32px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($scd['all_services'] as $i => $svc)
                                                @php $tc = $svcTypeConfig[$svc['category']] ?? $svcTypeConfig['drop_off']; @endphp
                                                <tr class="svc-table-row">
                                                    <td class="svc-rank-cell">{{ $i+1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="svc-icon-sm" style="background:{{ $tc['bg'] }};color:{{ $tc['hex'] }};">
                                                                <i class="bi bi-tag-fill"></i>
                                                            </div>
                                                            <span class="svc-name-text">{{ $svc['name'] }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="svc-type-pill"
                                                              style="background:{{ $tc['bg'] }};color:{{ $tc['hex'] }};border-color:{{ $tc['border'] }};">
                                                            <i class="bi {{ $tc['icon'] }}"></i>{{ $tc['label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-700">{{ number_format($svc['count']) }}</span>
                                                        <div class="svc-sub-pct">{{ $svc['count_pct'] }}%</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-700 text-success">₱{{ number_format($svc['revenue'],0) }}</span>
                                                        <div class="svc-sub-pct">{{ $svc['revenue_pct'] }}%</div>
                                                    </td>
                                                    <td>
                                                        <div class="svc-bar-track">
                                                            <div class="svc-bar-fill" style="width:{{ $svc['count_pct'] }}%;background:{{ $tc['hex'] }};"></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('admin.laundries.index', ['service'=>$svc['id']]) }}"
                                                           class="svc-row-link" title="View laundries">
                                                            <i class="bi bi-arrow-right-circle"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="svc-empty-state m-4">
                                    <i class="bi bi-tag"></i>
                                    <p>No services configured yet</p>
                                    <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-primary rounded-pill mt-2">Add Services</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Recent Laundries (small, beside table) --}}
                <div class="col-lg-4">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Latest</h6>
                                <small class="text-muted">Most recent laundries</small>
                            </div>
                            <a href="{{ route('admin.laundries.index') }}" class="btn btn-sm btn-outline-primary">All</a>
                        </div>
                        <div class="card-body-modern">
                            @php $latestLaundries = \App\Models\Laundry::with('customer')->latest()->limit(6)->get(); @endphp
                            @forelse($latestLaundries as $laundry)
                                <div class="recent-laundry-item {{ !$loop->last ? 'mb-2' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="laundry-status-badge status-{{ $laundry->status }} me-3">
                                                <i class="bi bi-circle-fill"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0" style="font-size:.82rem;">#{{ $laundry->laundry_number ?? $laundry->id }}</h6>
                                                <small class="text-muted">{{ $laundry->customer->name ?? 'Guest' }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div style="font-size:.82rem;font-weight:700;">₱{{ number_format($laundry->total_amount,0) }}</div>
                                            <small class="text-capitalize text-muted">{{ $laundry->status }}</small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-basket text-muted fs-1 d-block mb-2"></i>
                                    <p class="text-muted">No laundries yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Enhanced Customers Tab --}}
        <div class="tab-pane fade" id="customers" role="tabpanel">

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- CUSTOMER PIPELINE BY BRANCH                           --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="cbp-wrapper modern-card shadow-sm overflow-hidden">

                        {{-- Gradient header --}}
                        <div class="cbp-main-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="cbp-header-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-800 text-white">Customer Pipeline by Branch</h5>
                                        <small class="text-white" style="opacity:.75;">
                                            Walk-in vs Mobile customers per branch
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="cbp-legend d-none d-lg-flex">
                                        <div class="cbp-legend-item">
                                            <span class="cbp-legend-dot" style="background:#34d399;"></span>
                                            <span>Walk-In</span>
                                        </div>
                                        <div class="cbp-legend-item">
                                            <span class="cbp-legend-dot" style="background:#818cf8;"></span>
                                            <span>Self-Registered</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.customers.index') }}"
                                       class="btn btn-sm btn-light rounded-pill fw-600">
                                        <i class="bi bi-arrow-right-circle me-1"></i>All Customers
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Branch cards + pie chart --}}
                        <div class="card-body-modern">
                            @if(empty($stats['customerBranchPipeline']))
                                <div class="text-center py-5">
                                    <div class="cbp-empty-icon mb-3"><i class="bi bi-people"></i></div>
                                    <h6 class="text-muted fw-600">No customer branch data</h6>
                                    <small class="text-muted">Customer data will appear here once laundries are created.</small>
                                </div>
                            @else
                                <div class="row g-4 align-items-stretch">

                                    {{-- Branch cards (left) --}}
                                    <div class="col-lg-8">
                                        <div class="row g-3">
                                            @php
                                                $cbpAccents = ['#10b981','#6366f1','#06b6d4','#f59e0b','#ef4444','#8b5cf6'];
                                            @endphp
                                            @foreach($stats['customerBranchPipeline'] as $branch)
                                                @php
                                                    $accent   = $cbpAccents[$loop->index % count($cbpAccents)];
                                                    $bTotal   = max($branch['total'], 1);
                                                    $wiPct    = round(($branch['walk_in'] / $bTotal) * 100);
                                                    $mobPct   = 100 - $wiPct;
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="cbp-card" style="--cbp-accent: {{ $accent }};">
                                                        <div class="cbp-accent-bar"></div>

                                                        {{-- Card header --}}
                                                        <div class="cbp-card-header">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="cbp-avatar"
                                                                     style="background:linear-gradient(135deg,{{ $accent }},{{ $accent }}aa);">
                                                                    {{ strtoupper(substr($branch['name'], 0, 1)) }}
                                                                </div>
                                                                <div class="flex-grow-1 min-w-0">
                                                                    <h6 class="mb-0 fw-700 cbp-branch-name">{{ $branch['name'] }}</h6>
                                                                    <span class="cbp-total-badge mt-1 d-inline-block">
                                                                        <i class="bi bi-people me-1"></i>
                                                                        {{ $branch['total'] }} customers
                                                                    </span>
                                                                </div>
                                                                <a href="{{ route('admin.customers.index', ['branch' => $branch['id']]) }}"
                                                                   class="cbp-view-link flex-shrink-0">
                                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                                </a>
                                                            </div>
                                                        </div>

                                                        {{-- Stacked progress bar --}}
                                                        <div class="cbp-bar-wrap">
                                                            <div class="cbp-stacked-bar">
                                                                @if($wiPct > 0)
                                                                    <div class="cbp-bar-seg"
                                                                         style="width:{{ $wiPct }}%;background:#34d399;"
                                                                         data-bs-toggle="tooltip"
                                                                         data-bs-title="Walk-In: {{ $branch['walk_in'] }} ({{ $wiPct }}%)">
                                                                    </div>
                                                                @endif
                                                                @if($mobPct > 0)
                                                                    <div class="cbp-bar-seg"
                                                                         style="width:{{ $mobPct }}%;background:#818cf8;"
                                                                         data-bs-toggle="tooltip"
                                                                         data-bs-title="Mobile: {{ $branch['mobile'] }} ({{ $mobPct }}%)">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- 2-tile grid --}}
                                                        <div class="cbp-status-grid">
                                                            <a href="{{ route('admin.customers.index', ['branch_id' => $branch['id'], 'registration_type' => 'walk_in']) }}"
                                                               class="cbp-status-tile text-decoration-none"
                                                               style="--tile-color:#34d399;">
                                                                <div class="cbp-tile-icon"><i class="bi bi-person-walking"></i></div>
                                                                <div class="cbp-tile-count">{{ $branch['walk_in'] }}</div>
                                                                <div class="cbp-tile-label">Walk-In</div>
                                                                <div class="cbp-tile-pct">{{ $wiPct }}%</div>
                                                            </a>
                                                            <a href="{{ route('admin.customers.index', ['branch_id' => $branch['id'], 'registration_type' => 'self_registered']) }}"
                                                               class="cbp-status-tile text-decoration-none"
                                                               style="--tile-color:#818cf8;">
                                                                <div class="cbp-tile-icon"><i class="bi bi-phone-fill"></i></div>
                                                                <div class="cbp-tile-count">{{ $branch['mobile'] }}</div>
                                                                <div class="cbp-tile-label">Self-Reg</div>
                                                                <div class="cbp-tile-pct">{{ $mobPct }}%</div>
                                                            </a>
                                                        </div>

                                                    </div>{{-- /cbp-card --}}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Overall pie chart (right) --}}
                                    <div class="col-lg-4">
                                        <div class="cbp-chart-card h-100">
                                            <div class="cbp-chart-header">
                                                <div class="cbp-chart-icon">
                                                    <i class="bi bi-pie-chart-fill"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-800">Overall Split</h6>
                                                    <small class="text-muted">Walk-in vs Mobile</small>
                                                </div>
                                            </div>
                                            <div class="cbp-donut-wrap">
                                                <canvas id="customerBranchChart"></canvas>
                                                <div class="cbp-donut-center">
                                                    @php
                                                        $totalWalkIn = collect($stats['customerBranchPipeline'])->sum('walk_in');
                                                        $totalMobile = collect($stats['customerBranchPipeline'])->sum('mobile');
                                                        $grandTotal  = $totalWalkIn + $totalMobile;
                                                    @endphp
                                                    <div class="cbp-donut-num">{{ number_format($grandTotal) }}</div>
                                                    <div class="cbp-donut-lbl">Customers</div>
                                                </div>
                                            </div>
                                            {{-- Legend --}}
                                            <div class="cbp-chart-legend">
                                                <div class="cbp-chart-legend-item">
                                                    <span class="cbp-chart-legend-dot" style="background:#34d399;"></span>
                                                    <span class="cbp-chart-legend-label">Walk-In</span>
                                                    <span class="cbp-chart-legend-val">{{ number_format($totalWalkIn) }}</span>
                                                </div>
                                                <div class="cbp-chart-legend-item">
                                                    <span class="cbp-chart-legend-dot" style="background:#818cf8;"></span>
                                                    <span class="cbp-chart-legend-label">Self-Registered</span>
                                                    <span class="cbp-chart-legend-val">{{ number_format($totalMobile) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- ══════════════════════════════════════════════════════ --}}

            {{-- Top Customers + Top Rated ──────────────────────────── --}}
            <div class="row g-4 mt-2">
                {{-- Enhanced Top Customers --}}
                <div class="col-lg-6">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Top Customers</h6>
                                <small class="text-muted">By lifetime value</small>
                            </div>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body-modern">
                            @php $topCustomers = $stats['topCustomers'] ?? []; @endphp
                            @forelse($topCustomers as $customer)
                                <div class="top-customer-item mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                {{ substr($customer['name'], 0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $customer['name'] }}</h6>
                                                <small class="text-muted">{{ $customer['laundries_count'] }} laundries</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-0 text-success">₱{{ number_format($customer['total_spent'], 0) }}</h6>
                                            <small class="text-muted">Lifetime value</small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="bi bi-people text-muted fs-1 mb-3"></i>
                                    <p class="text-muted">No customer data available</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Top Rated Customers --}}
                <div class="col-lg-6">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Top Rated Customers</h6>
                                <small class="text-muted">Highest average rating given</small>
                            </div>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <div class="card-body-modern">
                            @php
                                $topRatedCustomers = $stats['topRatedCustomers'] ?? [];
                                $starColors = [5=>'#f59e0b',4=>'#f59e0b',3=>'#94a3b8',2=>'#ef4444',1=>'#ef4444'];
                            @endphp
                            @forelse($topRatedCustomers as $index => $customer)
                                @php
                                    $stars     = (int) round($customer['avg_rating']);
                                    $starColor = $starColors[$stars] ?? '#94a3b8';
                                @endphp
                                <div class="top-customer-item top-rated-item mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="trc-rank">{{ $index + 1 }}</div>
                                        <div class="customer-avatar flex-shrink-0"
                                             style="background:linear-gradient(135deg,{{ $starColor }},{{ $starColor }}cc);">
                                            {{ substr($customer['name'], 0, 1) }}
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <h6 class="mb-0 text-truncate">{{ $customer['name'] }}</h6>
                                            <div class="trc-star-bar mt-1">
                                                <div class="trc-star-fill"
                                                     style="width:{{ ($customer['avg_rating'] / 5) * 100 }}%;background:{{ $starColor }};"
                                                     data-width="{{ ($customer['avg_rating'] / 5) * 100 }}">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ $customer['ratings_count'] }} {{ $customer['ratings_count'] == 1 ? 'rating' : 'ratings' }}</small>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="trc-score" style="color:{{ $starColor }};">
                                                {{ $customer['avg_rating'] }}
                                                <i class="bi bi-star-fill ms-1" style="font-size:0.75rem;"></i>
                                            </div>
                                            <div class="trc-stars">
                                                @for($s = 1; $s <= 5; $s++)
                                                    <i class="bi bi-star{{ $s <= $stars ? '-fill' : '' }}"
                                                       style="color:{{ $s <= $stars ? $starColor : '#e2e8f0' }};"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="bi bi-star text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted">No ratings submitted yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            {{-- ────────────────────────────────────────────────────── --}}
        </div>

        {{-- Operations Tab --}}
        <div class="tab-pane fade" id="operations" role="tabpanel">
            <div class="row g-4">

                {{-- ══════════════════════════════════════════════════ --}}
                {{-- PICKUP PIPELINE BY BRANCH                         --}}
                {{-- ══════════════════════════════════════════════════ --}}
                <div class="col-lg-12">
                    <div class="pbp-wrapper modern-card shadow-sm overflow-hidden">

                        {{-- Gradient header --}}
                        <div class="pbp-main-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="pbp-header-icon">
                                        <i class="bi bi-truck"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-800 text-white">Pickup Pipeline by Branch</h5>
                                        <small class="text-white" style="opacity:.75;">
                                            Live pickup request status per branch
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    {{-- Status legend --}}
                                    @php
                                        $pbpDefs = [
                                            'pending'   => ['label'=>'Pending',   'hex'=>'#fbbf24', 'icon'=>'bi-clock-fill'],
                                            'accepted'  => ['label'=>'Accepted',  'hex'=>'#22d3ee', 'icon'=>'bi-check-circle-fill'],
                                            'en_route'  => ['label'=>'En Route',  'hex'=>'#818cf8', 'icon'=>'bi-truck'],
                                            'picked_up' => ['label'=>'Picked Up', 'hex'=>'#34d399', 'icon'=>'bi-bag-check-fill'],
                                            'cancelled' => ['label'=>'Cancelled', 'hex'=>'#f87171', 'icon'=>'bi-x-circle-fill'],
                                        ];
                                    @endphp
                                    <div class="pbp-legend d-none d-lg-flex">
                                        @foreach($pbpDefs as $key => $def)
                                            <div class="pbp-legend-item">
                                                <span class="pbp-legend-dot" style="background:{{ $def['hex'] }};"></span>
                                                <span>{{ $def['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('admin.pickups.index') }}"
                                       class="btn btn-sm btn-light rounded-pill fw-600">
                                        <i class="bi bi-arrow-right-circle me-1"></i>All Pickups
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Branch cards --}}
                        <div class="card-body-modern">
                            @if(empty($stats['pickupBranchPipeline']) || collect($stats['pickupBranchPipeline'])->sum('total') === 0)
                                <div class="text-center py-5">
                                    <div class="pbp-empty-icon mb-3"><i class="bi bi-truck"></i></div>
                                    <h6 class="text-muted fw-600">No pickup request data</h6>
                                    <small class="text-muted">Pickup requests will appear here once created.</small>
                                </div>
                            @else
                                <div class="row g-4">
                                    @php
                                        $pbpAccents = ['#f59e0b','#6366f1','#06b6d4','#10b981','#ef4444','#8b5cf6'];
                                    @endphp
                                    @foreach($stats['pickupBranchPipeline'] as $branch)
                                        @php
                                            $accent  = $pbpAccents[$loop->index % count($pbpAccents)];
                                            $bTotal  = max($branch['total'], 1);
                                        @endphp
                                        <div class="col-xl-4 col-md-6">
                                            <div class="pbp-card" style="--pbp-accent: {{ $accent }};">

                                                <div class="pbp-accent-bar"></div>

                                                {{-- Branch header --}}
                                                <div class="pbp-card-header">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="pbp-avatar"
                                                             style="background:linear-gradient(135deg,{{ $accent }},{{ $accent }}aa);">
                                                            {{ strtoupper(substr($branch['name'], 0, 1)) }}
                                                        </div>
                                                        <div class="flex-grow-1 min-w-0">
                                                            <h6 class="mb-0 fw-700 pbp-branch-name">{{ $branch['name'] }}</h6>
                                                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                                <span class="pbp-total-badge">
                                                                    <i class="bi bi-truck me-1"></i>
                                                                    {{ $branch['total'] }} requests
                                                                </span>
                                                                @if($branch['active'] > 0)
                                                                    <span class="pbp-active-badge">
                                                                        <span class="pbp-pulse-dot"></span>
                                                                        {{ $branch['active'] }} active
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('admin.pickups.index', ['branch' => $branch['id']]) }}"
                                                           class="pbp-view-link flex-shrink-0">
                                                            <i class="bi bi-box-arrow-up-right"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                {{-- Stacked progress bar --}}
                                                <div class="pbp-bar-wrap">
                                                    <div class="pbp-stacked-bar">
                                                        @foreach($pbpDefs as $statusKey => $def)
                                                            @php $pct = $bTotal > 1 ? round(($branch['statuses'][$statusKey] / $bTotal) * 100, 1) : 0; @endphp
                                                            @if($pct > 0)
                                                                <div class="pbp-bar-seg"
                                                                     style="width:{{ $pct }}%;background:{{ $def['hex'] }};"
                                                                     data-bs-toggle="tooltip"
                                                                     data-bs-title="{{ $def['label'] }}: {{ $branch['statuses'][$statusKey] }} ({{ $pct }}%)">
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>

                                                {{-- Status tiles — 5 columns --}}
                                                <div class="pbp-status-grid">
                                                    @foreach($pbpDefs as $statusKey => $def)
                                                        @php
                                                            $cnt     = $branch['statuses'][$statusKey];
                                                            $pctTile = $bTotal > 1 ? round(($cnt / $bTotal) * 100) : 0;
                                                        @endphp
                                                        <a href="{{ route('admin.pickups.index', ['branch' => $branch['id'], 'status' => $statusKey]) }}"
                                                           class="pbp-status-tile text-decoration-none"
                                                           style="--tile-color:{{ $def['hex'] }};">
                                                            <div class="pbp-tile-icon">
                                                                <i class="bi {{ $def['icon'] }}"></i>
                                                            </div>
                                                            <div class="pbp-tile-count">{{ $cnt }}</div>
                                                            <div class="pbp-tile-label">{{ $def['label'] }}</div>
                                                            <div class="pbp-tile-pct">{{ $pctTile }}%</div>
                                                        </a>
                                                    @endforeach
                                                </div>

                                            </div>{{-- /pbp-card --}}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- ══════════════════════════════════════════════════ --}}

                {{-- Left: Pickup Management Panel --}}
                <div class="col-lg-5">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Pickup Management</h6>
                                <small class="text-muted">Select multiple pickups for optimized route</small>
                            </div>
                            <div>
                                <span id="selectedPickupCount" class="badge bg-purple" style="display: none;">0</span>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            {{-- Multi-Route Action Buttons --}}
                            <div id="multiRouteBtn" class="d-grid mb-4" style="display: none;">
                                <button class="btn btn-purple shadow-sm" onclick="getOptimizedMultiRoute()">
                                    <i class="bi bi-route me-2"></i>Optimize Route (<span id="selectedCount">0</span> selected)
                                </button>
                            </div>

                            {{-- Auto-Optimize Button --}}
                            <div class="d-grid mb-4">
                                <button class="btn btn-primary shadow-sm" onclick="autoRouteAllVisible()">
                                    <i class="bi bi-magic me-2"></i> Auto-Optimize All Pending
                                </button>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="d-flex gap-2 mb-4">
                                <button class="btn btn-sm btn-outline-purple flex-fill" onclick="selectAllPending()">
                                    <i class="bi bi-check-square me-1"></i> Select All Pending
                                </button>
                                <button class="btn btn-sm btn-outline-danger flex-fill" onclick="clearSelections()">
                                    <i class="bi bi-x-circle me-1"></i> Clear All
                                </button>
                            </div>

                            {{-- Pickup Status Summary --}}
                            <h6 class="mb-3 fw-800 text-slate-600">Pickup Status Summary</h6>
                            @foreach([
                                'pending'    => 'Pending',
                                'accepted'   => 'Accepted',
                                'en_route'   => 'En Route',
                                'picked_up'  => 'Picked Up',
                                'cancelled'  => 'Cancelled',
                            ] as $statusKey => $label)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="pickup-status-indicator status-{{ $statusKey }} me-3"></div>
                                        <div>
                                            <h6 class="mb-0">{{ $label }}</h6>
                                            <small class="text-muted">Pickup requests</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="mb-0">{{ $stats['pickupStats'][$statusKey] ?? 0 }}</h4>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right: Map View --}}
                <div class="col-lg-7">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-800 text-slate-800">Logistics Map</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-purple" id="multiRouteTopBtn" style="display: none;"
                                        onclick="getOptimizedMultiRoute()">
                                    <i class="bi bi-route"></i> Optimize (<span id="selectedCountTop">0</span>)
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshMapMarkers()">
                                    <i class="bi bi-geo-alt"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                                    <i class="bi bi-arrows-fullscreen"></i> Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="card-body-modern p-0 position-relative">
                            {{-- ADDRESS SEARCH OVERLAY --}}
                            <div id="address-search-overlay" style="position: absolute; top: 15px; right: 15px; z-index: 1000; max-width: 380px;">
                                <div class="card shadow-lg border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi bi-search text-primary"></i>
                                            <h6 class="mb-0 fw-bold">Search Location</h6>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="text"
                                                   id="map-address-search"
                                                   class="form-control"
                                                   placeholder="e.g., 183 Dr. V Locsin Street, Dumaguete City"
                                                   style="font-size: 13px;">
                                            <button class="btn btn-primary" onclick="searchMapAddress()">
                                                <i class="bi bi-geo-alt-fill"></i>
                                            </button>
                                        </div>
                                        <div id="search-result-display" class="mt-2" style="display: none;">
                                            <div class="alert alert-success mb-0 py-2 px-2 small">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <strong id="result-address-text" class="d-block mb-1"></strong>
                                                        <small class="text-muted d-block" id="result-coords-text"></small>
                                                    </div>
                                                    <button class="btn btn-sm btn-link p-0 text-decoration-none"
                                                            onclick="document.getElementById('search-result-display').style.display='none'">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="logisticsMap" style="height: 500px; width: 100%; border-radius: 0 0 12px 12px;"></div>
                            <div id="map-controls-container" style="position: absolute; top: 10px; left: 10px; z-index: 1000;">
                                <div id="eta-display-container" style="display: none; margin-bottom: 10px;"></div>
                                <div class="route-controls" style="display: none;">
                                    <button class="route-btn btn-clear-route" onclick="clearRoute()">
                                        <i class="bi bi-x-circle"></i> Clear Route
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Route Details Panel (Initially Hidden) --}}
    <div id="routeDetailsPanel" class="route-details-panel" style="display: none;"></div>

    {{-- Fullscreen Map Modal --}}
    <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header border-bottom shadow-sm bg-navy text-white">
                    <h5 class="modal-title fw-bold">Logistics Command Center</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="modalLogisticsMap" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced System Metrics --}}
    <div class="row g-4 mt-4">
        <div class="col-lg-12">
            <div class="modern-card shadow-sm">
                <div class="card-header-modern bg-transparent border-0">
                    <h6 class="mb-0 fw-800 text-slate-800">System Performance Metrics</h6>
                    <small class="text-muted">Performance indicators</small>
                </div>
                <div class="card-body-modern">
                    <div class="row g-3">
                        @php
                            $systemStats = [
                                ['label' => 'Data Accuracy', 'value' => ($stats['dataQuality']['info_accuracy'] ?? 0) . '%', 'color' => 'blue', 'icon' => 'bi-check-circle'],
                                ['label' => 'Avg Laundry Value', 'value' => '₱' . number_format(($stats['todayRevenue'] ?? 0) / max($stats['todayLaundries'], 1), 0), 'color' => 'green', 'icon' => 'bi-currency-dollar'],
                                ['label' => 'Processing Time', 'value' => $stats['avgProcessingTime'] ?? '0 days', 'color' => 'indigo', 'icon' => 'bi-clock'],
                                ['label' => 'System Uptime', 'value' => '100%', 'color' => 'cyan', 'icon' => 'bi-server'],
                            ];
                        @endphp
                        @foreach($systemStats as $stat)
                            <div class="col-6 col-md-3">
                                <div class="metric-tile shadow-sm border-{{ $stat['color'] }}">
                                    <div class="m-icon icon-{{ $stat['color'] }}"><i class="bi {{ $stat['icon'] }}"></i></div>
                                    <div class="mt-3">
                                        <small class="text-muted">{{ $stat['label'] }}</small>
                                        <h4 class="mb-0 fw-800">{{ $stat['value'] }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
@endpush

@push('scripts')
    {{-- Load Chart.js from CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    {{-- Load Leaflet from CDN --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Load Leaflet MarkerCluster from CDN --}}
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    {{-- Pass PHP data to JavaScript --}}
    <script>
        // Branch data
        window.BRANCHES = @json($stats['branches'] ?? []);

        // Pending pickups data
        window.PENDING_PICKUPS = @json($stats['pendingPickups'] ?? []);

        // Revenue chart data
        window.REVENUE_DATA = {
            labels: @json($stats['revenueLabels'] ?? []),
            values: @json($stats['last7DaysRevenue'] ?? [])
        };

        // Customer source data
        window.CUSTOMER_SOURCE_DATA = @json($stats['customerRegistrationSource'] ?? []);

        // Customer branch pipeline data (for pie chart)
        window.CUSTOMER_BRANCH_DATA = @json($stats['customerBranchPipeline'] ?? []);

        // Top rated customers (for animated bars)
        window.TOP_RATED_DATA = @json($stats['topRatedCustomers'] ?? []);

        // Service chart data (full service + self service pie charts)
        window.SERVICE_CHART_DATA = @json($stats['serviceChartData'] ?? []);

        // Dashboard stats (for refresh functionality)
        window.DASHBOARD_STATS = @json($stats ?? []);
    </script>

    {{-- Initialize dashboard with server data --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initializeDashboardData === 'function') {
                window.initializeDashboardData(window.BRANCHES, window.DASHBOARD_STATS);
            }
        });
    </script>

    {{-- Main admin dashboard JavaScript --}}
    <script src="{{ asset('assets/js/admin.js') }}"></script>
@endpush
