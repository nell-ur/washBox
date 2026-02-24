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
            </div>
        </div>

        {{-- Enhanced Laundries Tab --}}
        <div class="tab-pane fade" id="laundries" role="tabpanel">
            <div class="row g-4">
                {{-- Enhanced Laundries by Service Type --}}
                <div class="col-lg-6">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <h6 class="mb-0 fw-800 text-slate-800">Revenue by Service</h6>
                            <small class="text-muted">Breakdown by service type</small>
                        </div>
                        <div class="card-body-modern">
                            @if(isset($stats['revenueByService']) && count($stats['revenueByService']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>Service</th>
                                                <th class="text-end">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stats['revenueByService'] as $service)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="service-icon me-2">
                                                                <i class="bi bi-tag"></i>
                                                            </div>
                                                            {{ $service['service'] }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">₱{{ number_format($service['revenue'], 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-basket text-muted fs-1 mb-3"></i>
                                    <p class="text-muted">No service data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Enhanced Recent Laundries --}}
                <div class="col-lg-6">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-800 text-slate-800">Recent Laundries</h6>
                                <small class="text-muted">Latest activities</small>
                            </div>
                            <a href="{{ route('admin.laundries.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body-modern">
                            <div class="recent-laundries">
                                @php
                                    $recentLaundries = \App\Models\Laundry::with('customer')->latest()->limit(5)->get();
                                @endphp
                                @forelse($recentLaundries as $laundry)
                                    <div class="recent-laundry-item mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="laundry-status-badge status-{{ $laundry->status }} me-3">
                                                    <i class="bi bi-circle-fill"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Laundry #{{ $laundry->laundry_number ?? $laundry->id }}</h6>
                                                    <small class="text-muted">
                                                        {{ $laundry->customer->name ?? 'Guest' }} •
                                                        {{ $laundry->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <h6 class="mb-0">₱{{ number_format($laundry->total_amount, 0) }}</h6>
                                                <small class="text-capitalize text-muted">{{ $laundry->status }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <i class="bi bi-basket text-muted fs-1 mb-3"></i>
                                        <p class="text-muted">No recent laundries</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced Customers Tab --}}
        <div class="tab-pane fade" id="customers" role="tabpanel">
            <div class="row g-4">
                {{-- Customer Registration Sources Chart --}}
                <div class="col-lg-6">
                    <div class="modern-card shadow-sm h-100">
                        <div class="card-header-modern bg-transparent border-0">
                            <h6 class="mb-0 fw-800 text-slate-800">Registration Sources</h6>
                            <small class="text-muted">Where customers come from</small>
                        </div>
                        <div class="card-body-modern">
                            <div class="chart-container">
                                <canvas id="customerSourceChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

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
                            @php
                                $topCustomers = \App\Models\Customer::withSum('laundries', 'total_amount')
                                    ->withCount('laundries')
                                    ->orderBy('laundries_sum_total_amount', 'desc')
                                    ->limit(5)
                                    ->get();
                            @endphp
                            @forelse($topCustomers as $customer)
                                <div class="top-customer-item mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                {{ substr($customer->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $customer->name }}</h6>
                                                <small class="text-muted">{{ $customer->laundries_count }} laundries</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-0 text-success">₱{{ number_format($customer->laundries_sum_total_amount, 0) }}</h6>
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
            </div>
        </div>

        {{-- Operations Tab --}}
        <div class="tab-pane fade" id="operations" role="tabpanel">
            <div class="row g-4">
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
