@extends('admin.layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
<div class="analytics-root">

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div class="an-page-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="mb-1 fw-800 text-slate-800">Business Analytics</h4>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                    <span class="mx-1 text-muted">→</span>
                    {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </p>
            </div>
            <button class="btn an-date-btn" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                <i class="bi bi-calendar-range me-2"></i>Change Range
                <span class="an-date-badge ms-2">{{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1 }}d</span>
            </button>
            <div class="an-live-indicator" id="liveIndicator" title="Auto-refreshing every 60 seconds">
                <span class="an-live-dot" id="liveDot"></span>
                <span class="an-live-label" id="liveLabel">Live</span>
            </div>
        </div>
    </div>

    {{-- ── KPI Row ──────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        {{-- Revenue --}}
        <div class="col-xl-3 col-md-6">
            <div class="an-kpi-card an-kpi-revenue">
                <div class="an-kpi-glow"></div>
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="an-kpi-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <span id="kpi-rev-growth" class="an-growth-badge {{ $revenueAnalytics['growth_percentage'] >= 0 ? 'positive' : 'negative' }}">
                        <i class="bi bi-arrow-{{ $revenueAnalytics['growth_percentage'] >= 0 ? 'up' : 'down' }}-right"></i>
                        {{ abs($revenueAnalytics['growth_percentage']) }}%
                    </span>
                </div>
                <div class="an-kpi-label">Total Revenue</div>
                <div id="kpi-rev-value" class="an-kpi-value">₱{{ number_format($revenueAnalytics['total'], 0) }}</div>
                <div id="kpi-rev-sub" class="an-kpi-sub">Avg ₱{{ number_format($revenueAnalytics['average_laundry_value'], 0) }} / order</div>
                <div class="an-sparkline-wrap">
                    <canvas id="sparkRevenue" height="40"></canvas>
                </div>
            </div>
        </div>

        {{-- Laundries --}}
        <div class="col-xl-3 col-md-6">
            <div class="an-kpi-card an-kpi-laundry">
                <div class="an-kpi-glow"></div>
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="an-kpi-icon">
                        <i class="bi bi-basket3-fill"></i>
                    </div>
                    <span id="kpi-laundry-rate" class="an-kpi-rate">{{ $laundryAnalytics['completion_rate'] }}%</span>
                </div>
                <div class="an-kpi-label">Total Laundries</div>
                <div id="kpi-laundry-value" class="an-kpi-value">{{ number_format($laundryAnalytics['total']) }}</div>
                <div id="kpi-laundry-sub" class="an-kpi-sub">{{ number_format($laundryAnalytics['completed']) }} completed</div>
                <div class="an-kpi-progress mt-2">
                    <div id="kpi-laundry-bar" class="an-kpi-progress-fill" style="width:{{ $laundryAnalytics['completion_rate'] }}%;"></div>
                </div>
            </div>
        </div>

        {{-- Customers --}}
        <div class="col-xl-3 col-md-6">
            <div class="an-kpi-card an-kpi-customers">
                <div class="an-kpi-glow"></div>
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="an-kpi-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span id="kpi-cust-new" class="an-growth-badge positive">
                        <i class="bi bi-plus"></i>{{ number_format($customerAnalytics['new']) }}
                    </span>
                </div>
                <div class="an-kpi-label">Total Customers</div>
                <div id="kpi-cust-value" class="an-kpi-value">{{ number_format($customerAnalytics['total']) }}</div>
                <div id="kpi-cust-sub" class="an-kpi-sub">{{ $customerAnalytics['avg_laundries_per_customer'] }} avg orders / customer</div>
                <div class="an-sparkline-wrap">
                    <canvas id="sparkCustomers" height="40"></canvas>
                </div>
            </div>
        </div>

        {{-- Processing --}}
        <div class="col-xl-3 col-md-6">
            <div class="an-kpi-card an-kpi-ops">
                <div class="an-kpi-glow"></div>
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="an-kpi-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <span id="kpi-ops-rate" class="an-kpi-rate">{{ $revenueAnalytics['growth_percentage'] > 0 ? '↑' : '↓' }}</span>
                </div>
                <div class="an-kpi-label">Avg Processing Time</div>
                <div id="kpi-ops-value" class="an-kpi-value">{{ $laundryAnalytics['avg_processing_time_hours'] }}<span class="an-kpi-unit">h</span></div>
                <div id="kpi-ops-sub" class="an-kpi-sub">Revenue growth: {{ $revenueAnalytics['growth_percentage'] }}% vs prev</div>
            </div>
        </div>
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────── --}}
    <div class="an-tab-nav-card">
        <ul class="nav an-tab-nav" id="analyticsTabs" role="tablist">
            <li class="nav-item">
                <button class="an-tab-btn active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#anOverview" type="button">
                    <i class="bi bi-grid-1x2-fill me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item">
                <button class="an-tab-btn" id="branches-tab" data-bs-toggle="pill" data-bs-target="#anBranches" type="button">
                    <i class="bi bi-building me-2"></i>Branches
                </button>
            </li>
            <li class="nav-item">
                <button class="an-tab-btn" id="services-tab" data-bs-toggle="pill" data-bs-target="#anServices" type="button">
                    <i class="bi bi-layers me-2"></i>Services
                </button>
            </li>
            <li class="nav-item">
                <button class="an-tab-btn" id="customers-tab" data-bs-toggle="pill" data-bs-target="#anCustomers" type="button">
                    <i class="bi bi-people me-2"></i>Customers
                </button>
            </li>
            <li class="nav-item">
                <button class="an-tab-btn" id="promotions-tab" data-bs-toggle="pill" data-bs-target="#anPromotions" type="button">
                    <i class="bi bi-megaphone me-2"></i>Promotions
                </button>
            </li>
        </ul>

        <div class="tab-content an-tab-content" id="analyticsTabsContent">

            {{-- ══════════════════════════════════════════════ --}}
            {{-- OVERVIEW TAB                                   --}}
            {{-- ══════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="anOverview" role="tabpanel">
                <div class="row g-4">

                    {{-- Revenue Area Chart --}}
                    <div class="col-lg-8">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue Trend</h6>
                                    <small class="text-muted">Daily revenue over selected period</small>
                                </div>
                                <div class="an-chart-badge blue">Area Chart</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="revenueChart" height="120"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Laundry Status Doughnut --}}
                    <div class="col-lg-4">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Order Status</h6>
                                    <small class="text-muted">Breakdown by current state</small>
                                </div>
                                <div class="an-chart-badge purple">Doughnut</div>
                            </div>
                            <div class="an-chart-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                                <canvas id="laundryStatusChart" style="max-height:220px;"></canvas>
                            </div>
                            <div class="an-status-stats">
                                <div class="an-stat-pill green">
                                    <span class="an-stat-num">{{ $laundryAnalytics['completed'] }}</span>
                                    <span class="an-stat-lbl">Completed</span>
                                </div>
                                <div class="an-stat-pill amber">
                                    <span class="an-stat-num">{{ $laundryAnalytics['total'] - $laundryAnalytics['completed'] }}</span>
                                    <span class="an-stat-lbl">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Revenue Waterfall --}}
                    <div class="col-lg-6">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue by Branch</h6>
                                    <small class="text-muted">Contribution waterfall</small>
                                </div>
                                <div class="an-chart-badge teal">Waterfall</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="revenueWaterfallChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Service Mix Pie --}}
                    <div class="col-lg-6">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Service Revenue Mix</h6>
                                    <small class="text-muted">Revenue share by service</small>
                                </div>
                                <div class="an-chart-badge orange">Pie</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="serviceMixChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Performance Metrics Row --}}
                    <div class="col-12">
                        <div class="an-metrics-strip">
                            <div class="an-metric-item">
                                <div class="an-metric-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <div class="an-metric-val">{{ $laundryAnalytics['avg_processing_time_hours'] }}h</div>
                                    <div class="an-metric-lbl">Avg Processing</div>
                                </div>
                            </div>
                            <div class="an-metric-divider"></div>
                            <div class="an-metric-item">
                                <div class="an-metric-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div>
                                    <div class="an-metric-val">{{ $laundryAnalytics['completion_rate'] }}%</div>
                                    <div class="an-metric-lbl">Completion Rate</div>
                                </div>
                            </div>
                            <div class="an-metric-divider"></div>
                            <div class="an-metric-item">
                                <div class="an-metric-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <div>
                                    <div class="an-metric-val">{{ $customerAnalytics['avg_laundries_per_customer'] }}</div>
                                    <div class="an-metric-lbl">Orders / Customer</div>
                                </div>
                            </div>
                            <div class="an-metric-divider"></div>
                            <div class="an-metric-item">
                                <div class="an-metric-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <div>
                                    <div class="an-metric-val {{ ($revenueAnalytics['growth_percentage'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ ($revenueAnalytics['growth_percentage'] ?? 0) > 0 ? '+' : '' }}{{ $revenueAnalytics['growth_percentage'] ?? 0 }}%
                                    </div>
                                    <div class="an-metric-lbl">Revenue Growth</div>
                                </div>
                            </div>
                            <div class="an-metric-divider"></div>
                            <div class="an-metric-item">
                                <div class="an-metric-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                                    <i class="bi bi-currency-exchange"></i>
                                </div>
                                <div>
                                    <div class="an-metric-val">₱{{ number_format($revenueAnalytics['average_laundry_value'], 0) }}</div>
                                    <div class="an-metric-lbl">Avg Order Value</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- BRANCHES TAB                                   --}}
            {{-- ══════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="anBranches" role="tabpanel">
                <div class="row g-4">

                    {{-- Grouped Bar --}}
                    <div class="col-lg-8">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Branch Performance</h6>
                                    <small class="text-muted">Revenue vs laundry volume per branch</small>
                                </div>
                                <div class="an-chart-badge blue">Grouped Bar</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="branchGroupedChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Branch Radar --}}
                    <div class="col-lg-4">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Branch Overview</h6>
                                    <small class="text-muted">Multi-metric radar</small>
                                </div>
                                <div class="an-chart-badge purple">Radar</div>
                            </div>
                            <div class="an-chart-body d-flex align-items-center justify-content-center">
                                <canvas id="branchRadarChart" style="max-height:260px;"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Branch revenue horizontal bar --}}
                    <div class="col-lg-6">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue Ranking</h6>
                                    <small class="text-muted">Sorted by total revenue</small>
                                </div>
                                <div class="an-chart-badge teal">Horizontal Bar</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="branchHorizChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Branch stat cards --}}
                    <div class="col-lg-6">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Branch Breakdown</h6>
                                    <small class="text-muted">Individual stats</small>
                                </div>
                            </div>
                            <div class="an-chart-body">
                                @php $maxRev = collect($branchPerformance['branches'])->max('revenue') ?: 1; @endphp
                                @foreach($branchPerformance['branches'] as $i => $branch)
                                    @php
                                        $pct = round(($branch['revenue'] / $maxRev) * 100);
                                        $bColors = ['#3b82f6','#8b5cf6','#06b6d4','#f59e0b','#ef4444','#10b981'];
                                        $bc = $bColors[$i % count($bColors)];
                                    @endphp
                                    <div class="an-branch-row mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="an-branch-dot" style="background:{{ $bc }};"></div>
                                                <span class="fw-700 text-slate-800" style="font-size:.85rem;">{{ $branch['name'] }}</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="fw-800" style="color:{{ $bc }};">₱{{ number_format($branch['revenue'], 0) }}</span>
                                                <span class="text-muted ms-2" style="font-size:.75rem;">{{ $branch['laundries'] }} orders</span>
                                            </div>
                                        </div>
                                        <div class="an-branch-bar-track">
                                            <div class="an-branch-bar-fill" style="width:{{ $pct }}%;background:{{ $bc }};"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- SERVICES TAB                                   --}}
            {{-- ══════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="anServices" role="tabpanel">
                <div class="row g-4">

                    {{-- Funnel (horizontal sorted bar) --}}
                    <div class="col-lg-7">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Service Popularity Funnel</h6>
                                    <small class="text-muted">Orders per service (descending)</small>
                                </div>
                                <div class="an-chart-badge orange">Funnel</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="serviceFunnelChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Service revenue pie --}}
                    <div class="col-lg-5">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue Share</h6>
                                    <small class="text-muted">By service type</small>
                                </div>
                                <div class="an-chart-badge purple">Doughnut</div>
                            </div>
                            <div class="an-chart-body d-flex align-items-center justify-content-center" style="min-height:240px;">
                                <canvas id="serviceRevChart" style="max-height:240px;"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Service table with custom bars --}}
                    <div class="col-12">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Service Detail</h6>
                                    <small class="text-muted">All services ranked by usage</small>
                                </div>
                            </div>
                            <div class="an-chart-body p-0">
                                <div class="table-responsive">
                                    <table class="an-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Service</th>
                                                <th>Usage</th>
                                                <th>Volume Bar</th>
                                                <th class="text-end">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $maxSvc = collect($servicePopularity['services'])->max('laundries') ?: 1; @endphp
                                            @foreach($servicePopularity['services'] as $i => $svc)
                                            @php $sp = round(($svc['laundries'] / $maxSvc) * 100); @endphp
                                            <tr>
                                                <td><span class="an-table-rank">{{ $i + 1 }}</span></td>
                                                <td><span class="fw-700">{{ $svc['name'] }}</span></td>
                                                <td><span class="an-usage-badge">{{ $svc['laundries'] }}</span></td>
                                                <td style="width:38%;">
                                                    <div class="an-table-bar-track">
                                                        <div class="an-table-bar-fill" style="width:{{ $sp }}%;"></div>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-700" style="color:#2563eb;">₱{{ number_format($svc['revenue'], 0) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- CUSTOMERS TAB                                  --}}
            {{-- ══════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="anCustomers" role="tabpanel">
                <div class="row g-4">

                    {{-- Customer Growth Area --}}
                    <div class="col-lg-8">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Customer Growth</h6>
                                    <small class="text-muted">New registrations over time</small>
                                </div>
                                <div class="an-chart-badge green">Area</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="customerGrowthChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Registration type doughnut --}}
                    <div class="col-lg-4">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Customer Type</h6>
                                    <small class="text-muted">Walk-in vs Self-registered</small>
                                </div>
                                <div class="an-chart-badge teal">Doughnut</div>
                            </div>
                            <div class="an-chart-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                                <canvas id="customerTypeChart" style="max-height:220px;"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Top customers horizontal bar --}}
                    <div class="col-lg-7">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Top Customers by Spend</h6>
                                    <small class="text-muted">Highest lifetime value</small>
                                </div>
                                <div class="an-chart-badge blue">Bar</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="topCustomersChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Top customers list --}}
                    <div class="col-lg-5">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Leaderboard</h6>
                                    <small class="text-muted">Ranked by total spend</small>
                                </div>
                            </div>
                            <div class="an-chart-body p-0">
                                <div class="an-leaderboard">
                                    @foreach($customerAnalytics['top_customers']->take(8) as $idx => $cust)
                                    <div class="an-lb-row">
                                        <div class="an-lb-rank {{ $idx < 3 ? 'top' : '' }}">{{ $idx + 1 }}</div>
                                        <div class="an-lb-avatar">{{ strtoupper(substr($cust->name, 0, 1)) }}</div>
                                        <div class="an-lb-info">
                                            <div class="an-lb-name">{{ $cust->name }}</div>
                                            <div class="an-lb-sub">{{ $cust->laundries_count ?? 0 }} orders</div>
                                        </div>
                                        <div class="an-lb-value">₱{{ number_format($cust->laundries_sum_total_amount ?? 0, 0) }}</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- PROMOTIONS TAB                                 --}}
            {{-- ══════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="anPromotions" role="tabpanel">
                <div class="row g-4">

                    {{-- Promotion usage bar --}}
                    <div class="col-lg-7">
                        <div class="an-chart-card">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Promotion Usage</h6>
                                    <small class="text-muted">Times each promotion was applied</small>
                                </div>
                                <div class="an-chart-badge orange">Column</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="promoUsageChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Promo ROI doughnut --}}
                    <div class="col-lg-5">
                        <div class="an-chart-card h-100">
                            <div class="an-chart-header">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue vs Discount</h6>
                                    <small class="text-muted">Return on investment</small>
                                </div>
                                <div class="an-chart-badge purple">Grouped Bar</div>
                            </div>
                            <div class="an-chart-body">
                                <canvas id="promoRoiChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Promotions table --}}
                    <div class="col-12">
                        <div class="an-chart-card">
                            <div class="an-chart-header d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Promotion Detail</h6>
                                    <small class="text-muted">All promotions with ROI analysis</small>
                                </div>
                                <a href="{{ route('admin.promotions.index') }}" class="btn btn-sm btn-primary rounded-pill">
                                    <i class="bi bi-plus-circle me-1"></i>New Promotion
                                </a>
                            </div>
                            <div class="an-chart-body p-0">
                                <div class="table-responsive">
                                    <table class="an-table">
                                        <thead>
                                            <tr>
                                                <th>Promotion</th>
                                                <th class="text-center">Type</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-end">Usage</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">Discount</th>
                                                <th class="text-end">ROI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($promotionEffectiveness['promotions'] as $promo)
                                            @php
                                                $roi = ($promo['total_discount'] > 0)
                                                    ? $promo['revenue'] / $promo['total_discount']
                                                    : ($promo['revenue'] > 0 ? 100 : 0);
                                                $roiClass = $roi > 3 ? 'success' : ($roi > 1.5 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-700">{{ $promo['name'] }}</div>
                                                    <small class="text-muted">{{ ucfirst(str_replace('_',' ',$promo['type'])) }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="an-type-badge">{{ ucfirst(str_replace('_',' ',$promo['type'])) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($promo['is_active'])
                                                        <span class="an-status-active">Active</span>
                                                    @else
                                                        <span class="an-status-inactive">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-700">{{ number_format($promo['usage_count']) }}</td>
                                                <td class="text-end fw-700 text-primary">₱{{ number_format($promo['revenue'], 0) }}</td>
                                                <td class="text-end text-danger">-₱{{ number_format($promo['total_discount'], 0) }}</td>
                                                <td class="text-end">
                                                    <span class="an-roi-badge {{ $roiClass }}">{{ number_format($roi, 1) }}x</span>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="bi bi-megaphone text-muted d-block mb-2" style="font-size:2rem;opacity:.3;"></i>
                                                    <p class="text-muted mb-2">No promotions in this period</p>
                                                    <a href="{{ route('admin.promotions.create') }}" class="btn btn-sm btn-primary">Create First Promotion</a>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /tab-content --}}
    </div>{{-- /an-tab-nav-card --}}

</div>{{-- /analytics-root --}}

{{-- Date Range Modal --}}
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:1.4rem 1.5rem;">
                <h5 class="modal-title fw-800 text-white mb-0">
                    <i class="bi bi-calendar-range me-2"></i>Date Range
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('admin.analytics') }}">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing:.5px;">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}" style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing:.5px;">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}" style="border-radius:10px;">
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'] as $k=>$l)
                        <button type="button" class="btn btn-sm btn-outline-secondary an-quick-btn" onclick="setQuickRange('{{ $k }}')">{{ $l }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary flex-fill">Apply Range</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ================================================================ */
/* ANALYTICS PAGE STYLES                                            */
/* ================================================================ */
.analytics-root { padding: 0 0 2rem; }

/* ── Page Header ── */
.an-page-header {
    background: white;
    border: 1px solid var(--slate-100);
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 6px rgba(0,0,0,0.04);
}
.an-date-btn {
    background: white;
    border: 1.5px solid var(--slate-200);
    border-radius: 50px;
    padding: 8px 18px;
    font-weight: 700;
    font-size: .85rem;
    color: var(--slate-800);
    transition: all .2s ease;
}
.an-date-btn:hover { border-color: #2563eb; color: #2563eb; }
.an-date-badge {
    background: #eff6ff; color: #2563eb;
    border-radius: 50px; padding: 1px 8px;
    font-size: .7rem; font-weight: 800;
}

/* ── KPI Cards ── */
.an-kpi-card {
    border-radius: 20px; padding: 1.4rem;
    position: relative; overflow: hidden;
    min-height: 175px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: transform .25s ease, box-shadow .25s ease;
}
.an-kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.14); }
.an-kpi-glow {
    position: absolute; width: 160px; height: 160px;
    border-radius: 50%; top: -40px; right: -40px;
    background: rgba(255,255,255,0.12); pointer-events: none;
}
.an-kpi-revenue  { background: linear-gradient(135deg, #1d4ed8 0%, #7c3aed 100%); color: white; }
.an-kpi-laundry  { background: linear-gradient(135deg, #065f46 0%, #0f766e 100%); color: white; }
.an-kpi-customers{ background: linear-gradient(135deg, #9a3412 0%, #c2410c 100%); color: white; }
.an-kpi-ops      { background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%); color: white; }

.an-kpi-icon {
    width: 44px; height: 44px; border-radius: 13px;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: white;
}
.an-growth-badge {
    display: flex; align-items: center; gap: 3px;
    border-radius: 50px; padding: 3px 10px;
    font-size: .7rem; font-weight: 800; white-space: nowrap;
}
.an-growth-badge.positive { background: rgba(74,222,128,0.25); color: #4ade80; }
.an-growth-badge.negative { background: rgba(248,113,113,0.25); color: #f87171; }
.an-kpi-rate {
    background: rgba(255,255,255,0.18); color: white;
    border-radius: 50px; padding: 3px 10px;
    font-size: .7rem; font-weight: 800;
}
.an-kpi-label { font-size: .72rem; font-weight: 600; opacity: .8; text-transform: uppercase; letter-spacing: .5px; }
.an-kpi-value { font-size: 1.8rem; font-weight: 900; line-height: 1.1; margin: 4px 0; }
.an-kpi-unit  { font-size: 1rem; font-weight: 700; opacity: .7; }
.an-kpi-sub   { font-size: .72rem; opacity: .75; }
.an-kpi-progress { height: 4px; border-radius: 99px; background: rgba(255,255,255,0.2); }
.an-kpi-progress-fill { height: 100%; border-radius: 99px; background: rgba(255,255,255,0.8); transition: width 1s ease; }
.an-sparkline-wrap { margin-top: 10px; opacity: .7; }

/* ── Tab Nav ── */
.an-tab-nav-card {
    background: white;
    border: 1px solid var(--slate-100);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.an-tab-nav {
    display: flex; border-bottom: 1px solid var(--slate-100);
    padding: .5rem .75rem 0; gap: 2px; overflow-x: auto;
    scrollbar-width: none;
}
.an-tab-nav::-webkit-scrollbar { display: none; }
.an-tab-btn {
    border: none; background: transparent;
    border-radius: 10px 10px 0 0;
    padding: .75rem 1.2rem;
    font-size: .82rem; font-weight: 700;
    color: var(--slate-500);
    transition: all .2s ease;
    white-space: nowrap;
    border-bottom: 3px solid transparent;
}
.an-tab-btn:hover { color: #2563eb; background: #eff6ff; }
.an-tab-btn.active { color: #2563eb; background: #eff6ff; border-bottom-color: #2563eb; }
.an-tab-content { padding: 1.5rem; }

/* ── Chart Cards ── */
.an-chart-card {
    background: white;
    border: 1px solid var(--slate-100);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: box-shadow .2s ease;
}
.an-chart-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.09); }
.an-chart-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 1.1rem 1.25rem .75rem; border-bottom: 1px solid var(--slate-100);
}
.an-chart-body { padding: 1.1rem 1.25rem; }
.an-chart-badge {
    display: inline-flex; align-items: center;
    border-radius: 50px; padding: 3px 11px;
    font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
    white-space: nowrap;
}
.an-chart-badge.blue   { background: #eff6ff; color: #2563eb; }
.an-chart-badge.purple { background: #f5f3ff; color: #7c3aed; }
.an-chart-badge.teal   { background: #ecfeff; color: #0e7490; }
.an-chart-badge.orange { background: #fff7ed; color: #c2410c; }
.an-chart-badge.green  { background: #f0fdf4; color: #15803d; }

/* ── Status pills ── */
.an-status-stats { display: flex; gap: 8px; padding: .75rem 1.25rem 1rem; }
.an-stat-pill {
    flex: 1; border-radius: 12px; padding: 8px 12px; text-align: center;
}
.an-stat-pill.green  { background: #f0fdf4; }
.an-stat-pill.amber  { background: #fffbeb; }
.an-stat-num {
    display: block; font-size: 1.1rem; font-weight: 800; color: var(--slate-800);
}
.an-stat-lbl { font-size: .68rem; font-weight: 600; color: var(--slate-500); text-transform: uppercase; }

/* ── Metrics strip ── */
.an-metrics-strip {
    background: white; border: 1px solid var(--slate-100);
    border-radius: 18px; padding: 1rem 1.5rem;
    display: flex; align-items: center; flex-wrap: wrap; gap: .5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.an-metric-item { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 140px; }
.an-metric-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: white; flex-shrink: 0;
}
.an-metric-val { font-size: 1.1rem; font-weight: 800; color: var(--slate-800); }
.an-metric-lbl { font-size: .68rem; font-weight: 600; color: var(--slate-500); }
.an-metric-divider { width: 1px; height: 36px; background: var(--slate-100); flex-shrink: 0; }

/* ── Branch rows ── */
.an-branch-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.an-branch-bar-track { height: 6px; border-radius: 99px; background: var(--slate-100); overflow: hidden; }
.an-branch-bar-fill { height: 100%; border-radius: 99px; transition: width 0.8s cubic-bezier(.4,0,.2,1); }

/* ── Service table ── */
.an-table { width: 100%; border-collapse: collapse; }
.an-table thead tr { border-bottom: 2px solid var(--slate-100); }
.an-table th {
    padding: .75rem 1.25rem; font-size: .7rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .5px; color: var(--slate-500);
    white-space: nowrap;
}
.an-table td { padding: .85rem 1.25rem; border-bottom: 1px solid var(--slate-100); vertical-align: middle; }
.an-table tr:last-child td { border-bottom: none; }
.an-table tr:hover td { background: #f8fafc; }
.an-table-rank {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 7px;
    background: var(--slate-100); color: var(--slate-500);
    font-size: .7rem; font-weight: 800;
}
.an-usage-badge {
    background: #eff6ff; color: #2563eb; border-radius: 50px;
    padding: 2px 10px; font-size: .75rem; font-weight: 700;
}
.an-table-bar-track { height: 7px; border-radius: 99px; background: var(--slate-100); overflow: hidden; }
.an-table-bar-fill  { height: 100%; border-radius: 99px; background: linear-gradient(90deg,#3b82f6,#8b5cf6); }

/* ── Leaderboard ── */
.an-leaderboard { padding: .5rem 0; }
.an-lb-row {
    display: flex; align-items: center; gap: 10px;
    padding: .65rem 1.25rem; transition: background .15s ease;
}
.an-lb-row:hover { background: #f8fafc; }
.an-lb-rank {
    width: 22px; height: 22px; border-radius: 7px;
    background: var(--slate-100); color: var(--slate-500);
    font-size: .68rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.an-lb-rank.top { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; }
.an-lb-avatar {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: white; font-weight: 800; font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
}
.an-lb-info { flex: 1; min-width: 0; }
.an-lb-name { font-weight: 700; font-size: .83rem; color: var(--slate-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.an-lb-sub  { font-size: .68rem; color: var(--slate-500); }
.an-lb-value { font-weight: 800; color: #2563eb; font-size: .83rem; flex-shrink: 0; }

/* ── Promo table badges ── */
.an-type-badge {
    background: var(--slate-100); color: var(--slate-600);
    border-radius: 50px; padding: 2px 9px; font-size: .7rem; font-weight: 700;
}
.an-status-active   { background: #f0fdf4; color: #15803d; border-radius: 50px; padding: 2px 9px; font-size: .7rem; font-weight: 700; }
.an-status-inactive { background: var(--slate-100); color: var(--slate-500); border-radius: 50px; padding: 2px 9px; font-size: .7rem; font-weight: 700; }
.an-roi-badge { border-radius: 50px; padding: 2px 10px; font-size: .72rem; font-weight: 800; }
.an-roi-badge.success { background: #f0fdf4; color: #15803d; }
.an-roi-badge.warning { background: #fffbeb; color: #b45309; }
.an-roi-badge.danger  { background: #fef2f2; color: #b91c1c; }

.an-quick-btn { border-radius: 50px; font-size: .75rem; font-weight: 700; }

/* ── Live Indicator ── */
.an-live-indicator {
    display: flex; align-items: center; gap: 6px;
    background: white; border: 1.5px solid var(--slate-100);
    border-radius: 50px; padding: 6px 14px;
    font-size: .75rem; font-weight: 700; color: var(--slate-500);
    cursor: default; user-select: none;
    transition: all .3s ease;
}
.an-live-indicator.syncing { border-color: #f59e0b; color: #b45309; }
.an-live-indicator.updated { border-color: #10b981; color: #065f46; }
.an-live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10b981;
    animation: an-pulse 2s infinite;
}
.an-live-indicator.syncing .an-live-dot { background: #f59e0b; animation: none; }
.an-live-indicator.updated .an-live-dot { background: #10b981; }
@keyframes an-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .5; transform: scale(.8); }
}

/* ── Number flip on update ── */
@keyframes an-flip-in {
    0%   { opacity: 0; transform: translateY(-8px); }
    100% { opacity: 1; transform: translateY(0); }
}
.an-kpi-updating { animation: an-flip-in .4s ease forwards; }

/* ── Dark mode additions ── */
[data-theme="dark"] .an-live-indicator { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .an-live-indicator.updated { border-color: #10b981; }

/* ── Dark Mode ── */
[data-theme="dark"] .an-page-header,
[data-theme="dark"] .an-tab-nav-card,
[data-theme="dark"] .an-chart-card,
[data-theme="dark"] .an-metrics-strip { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .an-tab-nav  { border-color: #334155; }
[data-theme="dark"] .an-tab-btn  { color: #94a3b8; }
[data-theme="dark"] .an-tab-btn:hover, [data-theme="dark"] .an-tab-btn.active { background: rgba(37,99,235,.15); }
[data-theme="dark"] .an-chart-header { border-color: #334155; }
[data-theme="dark"] .an-table th { color: #64748b; }
[data-theme="dark"] .an-table td { border-color: #334155; }
[data-theme="dark"] .an-table tr:hover td { background: #253348; }
[data-theme="dark"] .an-table-rank, [data-theme="dark"] .an-lb-rank { background: #334155; color: #94a3b8; }
[data-theme="dark"] .an-branch-bar-track, [data-theme="dark"] .an-table-bar-track { background: #334155; }
[data-theme="dark"] .an-metric-divider { background: #334155; }
[data-theme="dark"] .an-lb-row:hover { background: #253348; }
[data-theme="dark"] .an-stat-pill.green  { background: rgba(16,185,129,.1); }
[data-theme="dark"] .an-stat-pill.amber  { background: rgba(245,158,11,.1); }
[data-theme="dark"] .an-metrics-strip   { background: #1e293b; }
[data-theme="dark"] .an-usage-badge     { background: rgba(37,99,235,.15); }
[data-theme="dark"] .an-date-btn        { background: #1e293b; border-color: #334155; color: #f1f5f9; }
[data-theme="dark"] .an-type-badge, [data-theme="dark"] .an-status-inactive { background: #334155; color: #94a3b8; }

@media (max-width: 768px) {
    .an-metrics-strip { gap: 1rem; }
    .an-metric-divider { display: none; }
    .an-metric-item { min-width: 45%; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Shared Helpers ──────────────────────────────────────────
    const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark'
                      || document.body.getAttribute('data-theme') === 'dark';

    function theme() {
        const dark = isDark();
        return {
            grid      : dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)',
            tick      : dark ? '#94a3b8' : '#6b7280',
            legend    : dark ? '#cbd5e1' : '#374151',
            tooltipBg : dark ? '#1e293b' : '#1f2937',
            border    : dark ? '#334155' : 'rgba(255,255,255,0.8)',
        };
    }

    const COLORS = ['#3b82f6','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#6366f1','#14b8a6','#f97316'];

    // ── Data from PHP ───────────────────────────────────────────
    const revLabels   = @json($revenueAnalytics['labels'] ?? []);
    const revData     = @json($revenueAnalytics['data'] ?? []);
    const statusLbls  = @json($laundryAnalytics['status_labels'] ?? []);
    const statusData  = @json($laundryAnalytics['status_data'] ?? []);
    const branchLbls  = @json($branchPerformance['labels'] ?? []);
    const branchRev   = @json($branchPerformance['revenue_data'] ?? []);
    const branchOrds  = @json($branchPerformance['laundry_data'] ?? []);
    const branchNames = @json(array_column($branchPerformance['branches'] ?? [], 'name'));
    const svcLbls     = @json($servicePopularity['labels'] ?? []);
    const svcOrds     = @json($servicePopularity['laundry_data'] ?? []);
    const svcRev      = @json($servicePopularity['revenue_data'] ?? []);
    const custGrowthL = @json($customerAnalytics['growth_labels'] ?? []);
    const custGrowthD = @json($customerAnalytics['growth_data'] ?? []);
    const topCustN    = @json($customerAnalytics['top_customers']->pluck('name')->take(8)->toArray());
    @php
        $topCustVData = $customerAnalytics['top_customers']->pluck('laundries_sum_total_amount')->take(8)->map(function($v) { return (float)($v ?? 0); })->toArray();
    @endphp
    const topCustV    = @json($topCustVData);
    const promoLbls   = @json($promotionEffectiveness['labels'] ?? []);
    const promoUsage  = @json($promotionEffectiveness['usage_data'] ?? []);
    const promoRevArr = @json(array_column($promotionEffectiveness['promotions'] ?? [], 'revenue'));
    const promoDisArr = @json(array_column($promotionEffectiveness['promotions'] ?? [], 'total_discount'));
    @php
        $custRegSrcData = $customerAnalytics['registration_source'] ?? ['walk_in' => 0, 'self_registered' => 0];
    @endphp
    const custRegSrc  = @json($custRegSrcData);

    const t = theme();

    function axisDefaults(opts = {}) {
        return {
            grid : { color: t.grid, drawBorder: false },
            ticks: { color: t.tick, font: { size: 11 } },
            ...opts
        };
    }
    function tooltipDefaults() {
        return {
            backgroundColor: t.tooltipBg,
            titleColor: '#fff',
            bodyColor: '#e5e7eb',
            padding: 12,
            cornerRadius: 10,
            mode: 'index',
            intersect: false,
        };
    }

    // ── Sparklines ─────────────────────────────────────────────
    function miniLine(id, data, color) {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map((_,i) => i),
                datasets: [{ data, borderColor: 'rgba(255,255,255,0.7)', borderWidth: 2,
                    pointRadius: 0, fill: false, tension: 0.4 }]
            },
            options: {
                responsive: true, animation: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    }
    miniLine('sparkRevenue', revData, '#fff');
    miniLine('sparkCustomers', custGrowthD, '#fff');

    // ── OVERVIEW: Revenue Area Chart ────────────────────────────
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        const revChart = new Chart(revCtx, {
            type: 'line',
            data: {
                labels: revLabels,
                datasets: [{
                    label: 'Revenue',
                    data: revData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.12)',
                    borderWidth: 3, fill: true, tension: 0.45,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff', pointBorderWidth: 2,
                    pointRadius: 5, pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: {
                        label: ctx => ' ₱' + ctx.parsed.y.toLocaleString()
                    }}
                },
                scales: {
                    y: { ...axisDefaults(), beginAtZero: true, ticks: { ...axisDefaults().ticks, callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } },
                    x: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
        registerChart('revenueChart', revChart);
    }

    // ── OVERVIEW: Order Status Doughnut ─────────────────────────
    const statusCtx = document.getElementById('laundryStatusChart');
    if (statusCtx) {
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLbls,
                datasets: [{ data: statusData, backgroundColor: COLORS, borderColor: t.border, borderWidth: 2, hoverOffset: 10 }]
            },
            options: {
                responsive: true, maintainAspectRatio: true, cutout: '68%',
                animation: { animateScale: true },
                plugins: {
                    legend: { position: 'bottom', labels: { color: t.legend, padding: 14, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }
                }
            }
        });
        registerChart('laundryStatus', statusChart);
    }
    const waterfallCtx = document.getElementById('revenueWaterfallChart');
    if (waterfallCtx) {
        // Build offset dataset + actual bar dataset
        const sortedBranches = [...branchRev.map((v,i)=>({name:branchNames[i]||branchLbls[i],rev:v}))].sort((a,b)=>b.rev-a.rev);
        const sorted = sortedBranches;
        const total = sorted.reduce((s,b)=>s+b.rev,0);
        const offsets = [];
        let running = 0;
        sorted.forEach((b, i) => {
            offsets.push(running);
            running += b.rev;
        });
        new Chart(waterfallCtx, {
            type: 'bar',
            data: {
                labels: sorted.map(b => b.name),
                datasets: [
                    { label: 'Base', data: offsets, backgroundColor: 'transparent', borderWidth: 0, stack: 'w' },
                    { label: 'Revenue', data: sorted.map(b => b.rev), backgroundColor: COLORS.slice(0, sorted.length), borderRadius: 6, borderSkipped: false, stack: 'w' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipDefaults(),
                        filter: item => item.datasetIndex === 1,
                        callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() }
                    }
                },
                scales: {
                    y: { ...axisDefaults(), beginAtZero: true, stacked: true, ticks: { ...axisDefaults().ticks, callback: v => '₱' + (v>=1000?(v/1000).toFixed(0)+'k':v) } },
                    x: { ...axisDefaults(), grid: { display: false }, stacked: true }
                }
            }
        });
    }

    // ── OVERVIEW: Service Mix Pie ───────────────────────────────
    const svcMixCtx = document.getElementById('serviceMixChart');
    if (svcMixCtx) {
        new Chart(svcMixCtx, {
            type: 'pie',
            data: {
                labels: svcLbls,
                datasets: [{ data: svcRev, backgroundColor: COLORS, borderColor: t.border, borderWidth: 2, hoverOffset: 10 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { animateScale: true },
                plugins: {
                    legend: { position: 'right', labels: { color: t.legend, padding: 14, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ₱${ctx.raw.toLocaleString()}` } }
                }
            }
        });
    }

    // ── BRANCHES: Grouped Bar ───────────────────────────────────
    const bGroupCtx = document.getElementById('branchGroupedChart');
    if (bGroupCtx) {
        new Chart(bGroupCtx, {
            type: 'bar',
            data: {
                labels: branchNames.length ? branchNames : branchLbls,
                datasets: [
                    { label: 'Revenue (₱)', data: branchRev, backgroundColor: 'rgba(59,130,246,0.85)', borderRadius: 6, yAxisID: 'yRev' },
                    { label: 'Orders', data: branchOrds, backgroundColor: 'rgba(139,92,246,0.85)', borderRadius: 6, yAxisID: 'yOrd' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: t.legend, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: {
                        label: ctx => ctx.datasetIndex === 0 ? ` Revenue: ₱${ctx.raw.toLocaleString()}` : ` Orders: ${ctx.raw}`
                    }}
                },
                scales: {
                    yRev: { ...axisDefaults(), beginAtZero: true, position: 'left', ticks: { ...axisDefaults().ticks, callback: v => '₱'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                    yOrd: { ...axisDefaults(), beginAtZero: true, position: 'right', grid: { display: false } },
                    x: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }

    // ── BRANCHES: Radar ─────────────────────────────────────────
    const bRadarCtx = document.getElementById('branchRadarChart');
    if (bRadarCtx && branchRev.length) {
        const maxRev = Math.max(...branchRev) || 1;
        const maxOrd = Math.max(...branchOrds) || 1;
        new Chart(bRadarCtx, {
            type: 'radar',
            data: {
                labels: ['Revenue', 'Orders', 'Avg Value', 'Share', 'Volume'],
                datasets: (branchNames.length ? branchNames : branchLbls).map((name, i) => ({
                    label: name,
                    data: [
                        Math.round((branchRev[i] / maxRev) * 100),
                        Math.round((branchOrds[i] / maxOrd) * 100),
                        branchOrds[i] > 0 ? Math.round((branchRev[i] / branchOrds[i]) / 100) : 0,
                        Math.round((branchRev[i] / (branchRev.reduce((s,v)=>s+v,0)||1)) * 100),
                        Math.round((branchOrds[i] / (branchOrds.reduce((s,v)=>s+v,0)||1)) * 100),
                    ],
                    borderColor: COLORS[i], backgroundColor: COLORS[i] + '22',
                    borderWidth: 2, pointRadius: 4,
                }))
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { labels: { color: t.legend, font: { size: 10 }, usePointStyle: true } }, tooltip: tooltipDefaults() },
                scales: { r: {
                    grid: { color: t.grid }, pointLabels: { color: t.tick, font: { size: 10 } },
                    ticks: { display: false }, angleLines: { color: t.grid }
                }}
            }
        });
    }

    // ── BRANCHES: Horizontal Bar ────────────────────────────────
    const bHorizCtx = document.getElementById('branchHorizChart');
    if (bHorizCtx) {
        new Chart(bHorizCtx, {
            type: 'bar',
            data: {
                labels: branchNames.length ? branchNames : branchLbls,
                datasets: [{
                    label: 'Revenue',
                    data: branchRev,
                    backgroundColor: COLORS.slice(0, branchRev.length),
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ₱${ctx.raw.toLocaleString()}` } }
                },
                scales: {
                    x: { ...axisDefaults(), beginAtZero: true, ticks: { ...axisDefaults().ticks, callback: v => '₱'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                    y: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }

    // ── SERVICES: Funnel (horizontal sorted bar) ────────────────
    const funnelCtx = document.getElementById('serviceFunnelChart');
    if (funnelCtx) {
        const sortIdx = [...svcOrds.map((v,i)=>({v,i}))].sort((a,b)=>b.v-a.v);
        new Chart(funnelCtx, {
            type: 'bar',
            data: {
                labels: sortIdx.map(s => svcLbls[s.i] || ''),
                datasets: [{
                    label: 'Orders',
                    data: sortIdx.map(s => s.v),
                    backgroundColor: sortIdx.map((_,i) => COLORS[i % COLORS.length]),
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ${ctx.raw} orders` } }
                },
                scales: {
                    x: { ...axisDefaults(), beginAtZero: true },
                    y: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }

    // ── SERVICES: Revenue Doughnut ──────────────────────────────
    const svcRevCtx = document.getElementById('serviceRevChart');
    if (svcRevCtx) {
        new Chart(svcRevCtx, {
            type: 'doughnut',
            data: {
                labels: svcLbls,
                datasets: [{ data: svcRev, backgroundColor: COLORS, borderColor: t.border, borderWidth: 2, hoverOffset: 10 }]
            },
            options: {
                responsive: true, maintainAspectRatio: true, cutout: '65%',
                animation: { animateScale: true },
                plugins: {
                    legend: { position: 'bottom', labels: { color: t.legend, padding: 10, usePointStyle: true, font: { size: 10 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ₱${ctx.raw.toLocaleString()}` } }
                }
            }
        });
    }

    // ── CUSTOMERS: Growth Area ──────────────────────────────────
    const custCtx = document.getElementById('customerGrowthChart');
    if (custCtx) {
        const custGrowthChart = new Chart(custCtx, {
            type: 'line',
            data: {
                labels: custGrowthL,
                datasets: [{
                    label: 'New Customers',
                    data: custGrowthD,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.12)',
                    borderWidth: 3, fill: true, tension: 0.45,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff', pointBorderWidth: 2,
                    pointRadius: 5, pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ${ctx.raw} new customers` } }
                },
                scales: {
                    y: { ...axisDefaults(), beginAtZero: true },
                    x: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
        registerChart('customerGrowth', custGrowthChart);
    }

    // ── CUSTOMERS: Type Doughnut ────────────────────────────────
    const custTypeCtx = document.getElementById('customerTypeChart');
    if (custTypeCtx) {
        const custTypeChart = new Chart(custTypeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Walk-In', 'Self-Registered'],
                datasets: [{
                    data: [custRegSrc.walk_in || 0, custRegSrc.self_registered || 0],
                    backgroundColor: ['#34d399','#818cf8'],
                    borderColor: t.border, borderWidth: 2, hoverOffset: 10
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true, cutout: '68%',
                animation: { animateScale: true },
                plugins: {
                    legend: { position: 'bottom', labels: { color: t.legend, padding: 14, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }
                }
            }
        });
        registerChart('customerType', custTypeChart);
    }

    // ── CUSTOMERS: Top Customers Horizontal Bar ─────────────────
    const topCustCtx = document.getElementById('topCustomersChart');
    if (topCustCtx) {
        new Chart(topCustCtx, {
            type: 'bar',
            data: {
                labels: topCustN,
                datasets: [{
                    label: 'Total Spend',
                    data: topCustV,
                    backgroundColor: topCustN.map((_,i) => COLORS[i % COLORS.length]),
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ₱${ctx.raw.toLocaleString()}` } }
                },
                scales: {
                    x: { ...axisDefaults(), beginAtZero: true, ticks: { ...axisDefaults().ticks, callback: v => '₱'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                    y: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }

    // ── PROMOTIONS: Usage Column Bar ────────────────────────────
    const promoUsageCtx = document.getElementById('promoUsageChart');
    if (promoUsageCtx) {
        new Chart(promoUsageCtx, {
            type: 'bar',
            data: {
                labels: promoLbls,
                datasets: [{
                    label: 'Usage Count',
                    data: promoUsage,
                    backgroundColor: COLORS.slice(0, promoLbls.length),
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ${ctx.raw} uses` } }
                },
                scales: {
                    y: { ...axisDefaults(), beginAtZero: true },
                    x: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }

    // ── PROMOTIONS: Revenue vs Discount Grouped Bar ─────────────
    const promoRoiCtx = document.getElementById('promoRoiChart');
    if (promoRoiCtx) {
        new Chart(promoRoiCtx, {
            type: 'bar',
            data: {
                labels: promoLbls,
                datasets: [
                    { label: 'Revenue', data: promoRevArr, backgroundColor: 'rgba(59,130,246,0.85)', borderRadius: 6 },
                    { label: 'Discount', data: promoDisArr, backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: t.legend, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { ...tooltipDefaults(), callbacks: { label: ctx => ` ₱${ctx.raw.toLocaleString()}` } }
                },
                scales: {
                    y: { ...axisDefaults(), beginAtZero: true, ticks: { ...axisDefaults().ticks, callback: v => '₱'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                    x: { ...axisDefaults(), grid: { display: false } }
                }
            }
        });
    }
});

// ── AJAX POLLING ─────────────────────────────────────────────────
const POLL_INTERVAL = 60000; // 60 seconds
const REFRESH_URL   = '{{ url("admin/analytics/refresh") }}?start_date={{ $startDate }}&end_date={{ $endDate }}';

// Chart registry — filled during DOMContentLoaded
const LIVE_CHARTS = {};

// Register a chart after creation so the poller can update its datasets
function registerChart(key, chartInstance) {
    LIVE_CHARTS[key] = chartInstance;
}

// Update a chart's dataset(s) and re-render
function updateChart(key, newDatasets) {
    const chart = LIVE_CHARTS[key];
    if (!chart) return;
    newDatasets.forEach((nd, i) => {
        if (chart.data.datasets[i]) {
            chart.data.datasets[i].data   = nd.data;
            if (nd.labels) chart.data.labels = nd.labels;
        }
    });
    chart.update('active');
}

function setLive(state, label) {
    const ind  = document.getElementById('liveIndicator');
    const dot  = document.getElementById('liveDot');
    const lbl  = document.getElementById('liveLabel');
    if (!ind) return;
    ind.className = 'an-live-indicator ' + state;
    if (lbl) lbl.textContent = label;
}

function flipUpdate(el, newText) {
    if (!el) return;
    el.classList.remove('an-kpi-updating');
    void el.offsetWidth; // reflow
    el.textContent = newText;
    el.classList.add('an-kpi-updating');
}

async function pollAnalytics() {
    setLive('syncing', 'Syncing…');
    try {
        const res  = await fetch(REFRESH_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const d = await res.json();

        // ── KPI: Revenue ──────────────────────────────────────────
        flipUpdate(document.getElementById('kpi-rev-value'),
            '₱' + Number(d.revenue.total).toLocaleString('en-PH', { maximumFractionDigits: 0 }));
        flipUpdate(document.getElementById('kpi-rev-sub'),
            'Avg ₱' + Number(d.revenue.average_laundry_value).toLocaleString('en-PH', { maximumFractionDigits: 0 }) + ' / order');

        const gBadge = document.getElementById('kpi-rev-growth');
        if (gBadge) {
            const g = d.revenue.growth_percentage;
            gBadge.className = 'an-growth-badge ' + (g >= 0 ? 'positive' : 'negative');
            gBadge.innerHTML = `<i class="bi bi-arrow-${g >= 0 ? 'up' : 'down'}-right"></i> ${Math.abs(g)}%`;
        }

        // ── KPI: Laundries ────────────────────────────────────────
        flipUpdate(document.getElementById('kpi-laundry-value'),
            Number(d.laundry.total).toLocaleString());
        flipUpdate(document.getElementById('kpi-laundry-sub'),
            Number(d.laundry.completed).toLocaleString() + ' completed');
        const lBar = document.getElementById('kpi-laundry-bar');
        if (lBar) lBar.style.width = d.laundry.completion_rate + '%';
        const lRate = document.getElementById('kpi-laundry-rate');
        if (lRate) lRate.textContent = d.laundry.completion_rate + '%';

        // ── KPI: Customers ────────────────────────────────────────
        flipUpdate(document.getElementById('kpi-cust-value'),
            Number(d.customer.total).toLocaleString());
        flipUpdate(document.getElementById('kpi-cust-sub'),
            d.customer.avg_laundries_per_customer + ' avg orders / customer');
        const cNew = document.getElementById('kpi-cust-new');
        if (cNew) cNew.innerHTML = `<i class="bi bi-plus"></i>${Number(d.customer.new).toLocaleString()}`;

        // ── KPI: Ops ──────────────────────────────────────────────
        const opsVal = document.getElementById('kpi-ops-value');
        if (opsVal) {
            opsVal.innerHTML = d.laundry.avg_processing_time_hours + '<span class="an-kpi-unit">h</span>';
            opsVal.classList.remove('an-kpi-updating');
            void opsVal.offsetWidth;
            opsVal.classList.add('an-kpi-updating');
        }
        flipUpdate(document.getElementById('kpi-ops-sub'),
            'Revenue growth: ' + d.revenue.growth_percentage + '% vs prev');
        const opsRate = document.getElementById('kpi-ops-rate');
        if (opsRate) opsRate.textContent = d.revenue.growth_percentage > 0 ? '↑' : '↓';

        // ── Charts ────────────────────────────────────────────────
        updateChart('revenueChart',    [{ data: d.revenue.data,   labels: d.revenue.labels }]);
        updateChart('laundryStatus',   [{ data: d.laundry.status_data }]);
        updateChart('customerGrowth',  [{ data: d.customer.growth_data, labels: d.customer.growth_labels }]);
        updateChart('customerType',    [{ data: [d.customer.registration_source.walk_in, d.customer.registration_source.self_registered] }]);

        setLive('updated', 'Updated ' + new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }));
        setTimeout(() => setLive('', 'Live'), 4000);

    } catch (err) {
        console.warn('Analytics poll failed:', err);
        setLive('', 'Live');
    }
}

// Start polling after page fully loads
document.addEventListener('DOMContentLoaded', function () {
    // Slight delay so charts are registered first
    setTimeout(() => {
        setInterval(pollAnalytics, POLL_INTERVAL);
    }, 2000);
});
function setQuickRange(range) {
    const now = new Date();
    const s = document.querySelector('input[name="start_date"]');
    const e = document.querySelector('input[name="end_date"]');
    let start, end = new Date();
    switch(range) {
        case 'today': start = new Date(); break;
        case 'week':  start = new Date(now); start.setDate(now.getDate() - now.getDay()); break;
        case 'month': start = new Date(now.getFullYear(), now.getMonth(), 1); break;
        case 'year':  start = new Date(now.getFullYear(), 0, 1); break;
    }
    if (s && e) {
        s.value = start.toISOString().split('T')[0];
        e.value = end.toISOString().split('T')[0];
    }
}
</script>
@endpush
@endsection
