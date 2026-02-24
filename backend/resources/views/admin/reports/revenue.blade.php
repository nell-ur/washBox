@extends('admin.layouts.app')

@section('title', 'Revenue Report')

@section('content')
<div class="container-fluid px-4 py-5">
    {{-- Header Section --}}
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <h1 class="mb-2 fw-bold text-dark" style="font-size: 2rem; letter-spacing: -0.5px;">Revenue Report</h1>
                <p class="text-muted mb-0" style="font-size: 0.95rem;">View revenue trends, daily earnings, and financial analytics</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
                <form method="POST" action="{{ route('admin.reports.export') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="type" value="revenue">
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Key Metrics Cards --}}
    @php
        $totalRevenue = $data->sum('revenue');
        $totalLaundries = $data->sum('laundries');
        $avgRevenuePerDay = $data->count() > 0 ? round($totalRevenue / $data->count(), 2) : 0;
        $avgPerLaundry = $totalLaundries > 0 ? round($totalRevenue / $totalLaundries, 2) : 0;

        // Calculate trend
        $midPoint = ceil($data->count() / 2);
        $firstHalf = $data->take($midPoint)->sum('revenue');
        $secondHalf = $data->skip($midPoint)->sum('revenue');
        $trendPercentage = $firstHalf > 0 ? round((($secondHalf - $firstHalf) / $firstHalf) * 100, 1) : 0;
        $trendDirection = $trendPercentage >= 0 ? 'up' : 'down';
    @endphp

    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL REVENUE</p>
                            <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-currency-peso"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <span>{{ $data->count() }} days tracked</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL LAUNDRIES</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalLaundries) }}</h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-basket"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-dash me-1"></i>
                        <span>Orders processed</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">AVG DAILY REVENUE</p>
                            <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($avgRevenuePerDay, 2) }}</h3>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-dash me-1"></i>
                        <span>Per day average</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">AVG PER LAUNDRY</p>
                            <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($avgPerLaundry, 2) }}</h3>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-tag"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-{{ $trendDirection }}-right me-1"></i>
                        <span>{{ abs($trendPercentage) }}% {{ $trendDirection == 'up' ? 'increase' : 'decrease' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter Card --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.reports.revenue') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-600 text-dark mb-2">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600 text-dark mb-2">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Revenue Trends Chart --}}
    <div class="row g-3 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up text-primary me-2"></i>Revenue Trend
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if($data->count() > 0)
                        <canvas id="revenueChart" height="80"></canvas>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted">No data available for chart</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Financial Analytics --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-pie-chart text-success me-2"></i>Financial Analytics
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Highest Daily Revenue</span>
                            <span class="fw-bold">₱{{ number_format($data->max('revenue'), 2) }}</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Lowest Daily Revenue</span>
                            <span class="fw-bold">₱{{ number_format($data->min('revenue') ?? 0, 2) }}</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-warning" style="width: {{ $data->count() > 0 ? round(($data->min('revenue') / $data->max('revenue')) * 100, 0) : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Revenue Trend</span>
                            <span class="fw-bold {{ $trendPercentage >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-arrow-{{ $trendDirection }}-right me-1"></i>{{ abs($trendPercentage) }}%
                            </span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar {{ $trendPercentage >= 0 ? 'bg-success' : 'bg-danger' }}" style="width: {{ min(abs($trendPercentage), 100) }}%"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-1">Peak Day</p>
                                <small class="fw-bold text-dark d-block">
                                    @php
                                        $peakDay = $data->sortByDesc('revenue')->first();
                                        echo $peakDay ? \Carbon\Carbon::parse($peakDay->date)->format('M d') : 'N/A';
                                    @endphp
                                </small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-1">Lowest Day</p>
                                <small class="fw-bold text-dark d-block">
                                    @php
                                        $lowestDay = $data->sortBy('revenue')->first();
                                        echo $lowestDay ? \Carbon\Carbon::parse($lowestDay->date)->format('M d') : 'N/A';
                                    @endphp
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Data Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 p-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-table text-dark me-2"></i>Daily Revenue Details
            </h6>
            <span class="badge bg-light text-dark">{{ $data->count() }} days</span>
        </div>
        <div class="card-body p-0">
            @if($data->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-600" style="width: 35%;">Date</th>
                                <th class="text-center fw-600" style="width: 35%;">Laundries</th>
                                <th class="text-end fw-600" style="width: 30%;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr class="align-middle">
                                    <td>
                                        <span class="fw-500">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($row->date)->format('l') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark" style="font-size: 0.9rem;">{{ $row->laundries }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-600 text-dark">₱{{ number_format($row->revenue, 2) }}</span>
                                        <br>
                                        <small class="text-muted">₱{{ number_format($row->revenue / max($row->laundries, 1), 2) }}/order</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>
                                    <i class="bi bi-check-circle text-success me-2"></i>Total
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $totalLaundries }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-success">₱{{ number_format($totalRevenue, 2) }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-graph-down text-muted" style="font-size: 3rem; opacity: 0.6;"></i>
                    <p class="text-muted mt-3 mb-0">No revenue data for this period</p>
                    <small class="text-muted">Try adjusting your date range filters</small>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.stat-card {
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--bs-success), transparent);
    border-radius: 12px 12px 0 0;
}

.stat-card:hover {
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important;
    transform: translateY(-2px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.bg-success { background: linear-gradient(135deg, #198754, #146c43); }
.stat-icon.bg-primary { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
.stat-icon.bg-info { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
.stat-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }

.table-light { background-color: #f8f9fa; }
.fw-600 { font-weight: 600; }

table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

table tbody tr:hover {
    background-color: #f8f9fa;
}

.card {
    transition: all 0.2s ease;
}

.card:hover {
    border-color: #dee2e6;
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($data->count() > 0)
            const ctx = document.getElementById('revenueChart')?.getContext('2d');
            if (ctx) {
                const chartData = {
                    labels: [
                        @foreach($data as $row)
                            '{{ \Carbon\Carbon::parse($row->date)->format('M d') }}',
                        @endforeach
                    ],
                    datasets: [
                        {
                            label: 'Daily Revenue (₱)',
                            data: [
                                @foreach($data as $row)
                                    {{ $row->revenue }},
                                @endforeach
                            ],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#198754',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7,
                        },
                        {
                            label: 'Daily Average (₱)',
                            data: Array({{ $data->count() }}).fill({{ $avgRevenuePerDay }}),
                            borderColor: '#0dcaf0',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                        }
                    ]
                };

                new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: { size: 13, weight: 500 },
                                    padding: 15,
                                    usePointStyle: true,
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                titleFont: { size: 14, weight: 600 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString('en-US');
                                    },
                                    font: { size: 12 }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    borderColor: '#e9ecef'
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 12 } }
                            }
                        }
                    }
                });
            }
        @endif
    });
</script>
@endpush
@endsection
