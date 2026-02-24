@extends('staff.layouts.staff')

@section('title', 'Unclaimed History')
@section('page-title', 'Unclaimed History')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-clock-history text-primary me-2"></i>Recovery History
            </h4>
            <p class="text-muted mb-0">Track recovered laundries and reminder effectiveness</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.unclaimed.index') }}" class="btn btn-outline-warning">
                <i class="bi bi-exclamation-triangle me-1"></i> Active Unclaimed
            </a>
        </div>
    </div>

    {{-- Recovery Stats --}}
    <div class="row g-3 mb-4">
        {{-- Recovered Count --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['recovered_count'] }}</h3>
                    <small class="text-muted">Recovered (30 days)</small>
                </div>
            </div>
        </div>

        {{-- Revenue Recovered --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-cash-stack fs-4 text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-0">₱{{ number_format($stats['recovered_value'], 0) }}</h3>
                    <small class="text-muted">Revenue Recovered</small>
                </div>
            </div>
        </div>

        {{-- Recovery Rate --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-graph-up fs-4 text-info"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $stats['recovery_rate'] }}%</h3>
                    <small class="text-muted">Recovery Rate</small>
                </div>
            </div>
        </div>

        {{-- Avg Days to Claim --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-clock fs-4 text-warning"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $stats['avg_days_to_claim'] }}</h3>
                    <small class="text-muted">Avg Days to Claim</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Tracking #, customer name..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">From Date</label>
                    <input type="date" name="from_date" class="form-control"
                           value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">To Date</label>
                    <input type="date" name="to_date" class="form-control"
                           value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('staff.unclaimed.history') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- History Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Laundry</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Days Unclaimed</th>
                            <th class="text-center">Reminders</th>
                            <th class="text-center">Claimed Date</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laundries as $laundry)
                            @php
                                $daysUnclaimed = ($laundry->ready_at && $laundry->paid_at)
                                    ? $laundry->ready_at->diffInDays($laundry->paid_at)
                                    : ($laundry->ready_at
                                        ? $laundry->ready_at->diffInDays($laundry->updated_at)
                                        : 0);
                                $badgeColor = match(true) {
                                    $daysUnclaimed >= 14 => 'danger',
                                    $daysUnclaimed >= 7  => 'warning',
                                    $daysUnclaimed >= 3  => 'info',
                                    default              => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('staff.laundries.show', $laundry) }}"
                                       class="fw-semibold text-decoration-none">
                                        {{ $laundry->tracking_number }}
                                    </a>
                                    <div class="small text-muted">
                                        Ready: {{ $laundry->ready_at?->format('M d, Y') ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $laundry->customer->name ?? 'Unknown' }}</div>
                                    <div class="small text-muted">{{ $laundry->customer->phone ?? '' }}</div>
                                </td>
                                <td>{{ $laundry->service->name ?? 'N/A' }}</td>
                                <td class="text-end fw-bold">₱{{ number_format($laundry->total_amount, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badgeColor }}">{{ $daysUnclaimed }} days</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $laundry->reminder_count ?? 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <div>{{ $laundry->paid_at?->format('M d, Y') ?? $laundry->updated_at->format('M d, Y') }}</div>
                                    <div class="small text-muted">{{ $laundry->paid_at?->format('h:i A') ?? $laundry->updated_at->format('h:i A') }}</div>
                                </td>
                                <td class="text-center">
                                    @if($laundry->status === 'completed')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Completed</span>
                                    @elseif($laundry->status === 'paid')
                                        <span class="badge bg-primary"><i class="bi bi-credit-card me-1"></i>Paid</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($laundry->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2" style="opacity: 0.3;"></i>
                                    <h6 class="text-muted">No recovery history found</h6>
                                    <p class="text-muted small mb-0">Recovered laundries will appear here once unclaimed laundries are claimed.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($laundries->hasPages())
            <div class="card-footer bg-white">
                {{ $laundries->links() }}
            </div>
        @endif
    </div>

    {{-- Monthly Recovery Trend --}}
    @if($monthlyTrend->isNotEmpty())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-primary"></i>Monthly Recovery Trend (Last 6 Months)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm text-center mb-0">
                    <thead>
                        <tr>
                            @foreach($monthlyTrend as $month)
                                <th class="text-muted small border-0">{{ $month->month_label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($monthlyTrend as $month)
                                <td class="border-0">
                                    @php
                                        $maxCount = $monthlyTrend->max('count') ?: 1;
                                        $barHeight = max(8, ($month->count / $maxCount) * 80);
                                    @endphp
                                    <div class="d-flex justify-content-center align-items-end" style="height: 90px;">
                                        <div class="bg-success rounded-top"
                                             style="width: 32px; height: {{ $barHeight }}px; opacity: {{ 0.4 + ($month->count / $maxCount) * 0.6 }};">
                                        </div>
                                    </div>
                                    <div class="fw-bold small mt-1">{{ $month->count }}</div>
                                    <div class="text-muted small">₱{{ number_format($month->value, 0) }}</div>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Recovery Tips --}}
    <div class="card border-0 shadow-sm mt-4 bg-light">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb text-warning me-2"></i>Recovery Insights</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-success me-2">📊</span>
                        <div class="small">
                            <strong>Best recovery</strong> happens within the first 3 days of reminders being sent.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-success me-2">📞</span>
                        <div class="small">
                            <strong>Phone calls</strong> have 3x higher recovery rate than push notifications alone.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-success me-2">🚚</span>
                        <div class="small">
                            <strong>Offering delivery</strong> for 14+ day laundries recovers up to 40% of critical cases.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
