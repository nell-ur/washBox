@extends('admin.layouts.app')

@section('title', 'Unclaimed Laundry')
@section('page-title', 'Unclaimed Laundry')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Unclaimed Laundry
            </h4>
            <p class="text-muted small mb-0">Monitor unclaimed Laundry across all branches</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.unclaimed.export') }}" class="btn btn-outline-success shadow-sm">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <a href="{{ route('admin.unclaimed.history') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-clock-history me-1"></i>History
            </a>
            <a href="{{ route('admin.unclaimed.remindAll') }}" class="btn btn-danger shadow-sm"
               onclick="return confirm('Send reminders to all customers with unclaimed laundry (3+ days)?')">
                <i class="bi bi-bell-fill me-1"></i>Remind All
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Key Metrics --}}
    <div class="row g-3 mb-4">
        {{-- Total at Risk --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-2 opacity-75">Total Value at Risk</h6>
                            <h2 class="fw-bold mb-1">₱{{ number_format($stats['total_value'], 0) }}</h2>
                            <small class="opacity-75">{{ $stats['total'] }} laundries</small>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-3">
                            <i class="bi bi-currency-dollar fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Critical --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2 small">Critical (14+ days)</h6>
                            <h2 class="fw-bold text-danger mb-1">{{ $stats['critical'] }}</h2>
                            <small class="text-muted">₱{{ number_format($stats['critical_value'], 0) }}</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-exclamation-octagon fs-3 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recovered This Month --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2 small">Recovered (This Month)</h6>
                            <h2 class="fw-bold text-success mb-1">₱{{ number_format($stats['recovered_this_month'], 0) }}</h2>
                            <small class="text-muted">Revenue saved</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-graph-up-arrow fs-3 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reminders Today --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2 small">Reminders Today</h6>
                            <h2 class="fw-bold mb-1">{{ $stats['reminders_today'] }}</h2>
                            <small class="text-muted">Notifications sent</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-bell fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Urgency Breakdown --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row text-center g-2">
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'critical']) }}"
                       class="text-decoration-none d-block p-3 rounded {{ request('urgency') == 'critical' ? 'bg-danger bg-opacity-10' : '' }}">
                        <span class="badge bg-danger fs-5 mb-2">{{ $stats['critical'] }}</span>
                        <div class="small text-muted fw-semibold">🚨 Critical</div>
                        <div class="small text-muted">14+ days</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'urgent']) }}"
                       class="text-decoration-none d-block p-3 rounded {{ request('urgency') == 'urgent' ? 'bg-warning bg-opacity-10' : '' }}">
                        <span class="badge bg-warning text-dark fs-5 mb-2">{{ $stats['urgent'] }}</span>
                        <div class="small text-muted fw-semibold">⚠️ Urgent</div>
                        <div class="small text-muted">7-13 days</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'warning']) }}"
                       class="text-decoration-none d-block p-3 rounded {{ request('urgency') == 'warning' ? 'bg-info bg-opacity-10' : '' }}">
                        <span class="badge bg-info fs-5 mb-2">{{ $stats['warning'] }}</span>
                        <div class="small text-muted fw-semibold">⏰ Warning</div>
                        <div class="small text-muted">3-6 days</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'pending']) }}"
                       class="text-decoration-none d-block p-3 rounded {{ request('urgency') == 'pending' ? 'bg-secondary bg-opacity-10' : '' }}">
                        <span class="badge bg-secondary fs-5 mb-2">{{ $stats['pending'] }}</span>
                        <div class="small text-muted fw-semibold">📌 Pending</div>
                        <div class="small text-muted">1-2 days</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index') }}"
                       class="text-decoration-none d-block p-3 rounded {{ !request('urgency') && !request('branch_id') ? 'bg-primary bg-opacity-10' : '' }}">
                        <span class="badge bg-primary fs-5 mb-2">{{ $stats['total'] }}</span>
                        <div class="small text-muted fw-semibold">📊 All</div>
                        <div class="small text-muted">Total</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Content --}}
        <div class="col-lg-9">
            {{-- Filters --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small text-muted mb-1 fw-semibold">Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Tracking #, customer, phone..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label small text-muted mb-1 fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label small text-muted mb-1 fw-semibold">Min Days</label>
                            <input type="number" name="min_days" class="form-control"
                                   placeholder="0" min="0" value="{{ request('min_days') }}">
                        </div>
                        <div class="col-lg-3 col-md-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="{{ route('admin.unclaimed.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Unclaimed Laundry Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-list-ul me-2 text-primary"></i>Unclaimed Laundry
                    </h6>
                    <button type="button" class="btn btn-sm btn-primary" id="bulkReminderBtn" disabled>
                        <i class="bi bi-send me-1"></i>Send Selected
                    </button>
                </div>
                <div class="card-body p-0">
                    <form id="bulkForm" action="{{ route('admin.unclaimed.bulk-reminders') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40" class="ps-4">
                                            <input type="checkbox" class="form-check-input laundry-checkbox" id="selectAll">
                                        </th>
                                        <th>Laundry</th>
                                        <th>Customer</th>
                                        <th>Branch</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Days</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($laundries as $laundry)
                                        @php
                                            $days = $laundry->days_unclaimed ?? 0;
                                            $urgency = $laundry->unclaimed_status ?? 'normal';
                                            $color = $laundry->unclaimed_color ?? 'secondary';
                                        @endphp
                                        <tr class="{{ $urgency === 'critical' ? 'table-danger' : ($urgency === 'urgent' ? 'table-warning' : '') }}">
                                            <td class="ps-4">
                                                <input type="checkbox" class="form-check-input laundry-checkbox"
                                                       name="laundry_ids[]" value="{{ $laundry->id }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.unclaimed.show', $laundry) }}" class="fw-semibold text-decoration-none text-primary">
                                                    {{ $laundry->tracking_number }}
                                                </a>
                                                <div class="small text-muted">
                                                    Ready: {{ $laundry->ready_at?->format('M d, Y') ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $laundry->customer->name ?? 'N/A' }}</div>
                                                <div class="small">
                                                    <a href="tel:{{ $laundry->customer->phone ?? '' }}" class="text-decoration-none text-muted">
                                                        <i class="bi bi-telephone me-1"></i>{{ $laundry->customer->phone ?? 'N/A' }}
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $laundry->branch->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-semibold">₱{{ number_format($laundry->total_amount, 2) }}</div>
                                                @php $storageFee = $laundry->calculated_storage_fee ?? 0; @endphp
                                                @if($storageFee > 0)
                                                    <div class="small text-warning">
                                                        +₱{{ number_format($storageFee, 2) }} fee
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $color }} px-3 fs-6">{{ $days }}</span>
                                            </td>
                                            <td class="text-center">
                                                @switch($urgency)
                                                    @case('critical')
                                                        <span class="badge bg-danger">🚨 Critical</span>
                                                        @break
                                                    @case('urgent')
                                                        <span class="badge bg-warning text-dark">⚠️ Urgent</span>
                                                        @break
                                                    @case('warning')
                                                        <span class="badge bg-info">⏰ Warning</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">📌 Pending</span>
                                                @endswitch
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    {{-- Call --}}
                                                    <a href="tel:{{ $laundry->customer->phone ?? '' }}"
                                                       class="btn btn-outline-success" title="Call">
                                                        <i class="bi bi-telephone"></i>
                                                    </a>

                                                    {{-- Send Reminder --}}
                                                    <form action="{{ route('admin.unclaimed.send-reminder', $laundry->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-primary" title="Notify">
                                                            <i class="bi bi-bell"></i>
                                                        </button>
                                                    </form>

                                                    {{-- Mark Claimed --}}
                                                    <form action="{{ route('admin.unclaimed.mark-claimed', $laundry->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success"
                                                                title="Mark Claimed"
                                                                onclick="return confirm('Mark as claimed?')">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>

                                                    {{-- Dispose (only if 30+ days) --}}
                                                    <form action="{{ route('admin.unclaimed.mark-disposed', $laundry->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-outline-danger"
                                                                title="Dispose"
                                                                {{ $days < ($disposalThreshold ?? 30) ? 'disabled' : '' }}
                                                                onclick="return confirm('Dispose this laundry? This cannot be undone.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="py-4">
                                                    <i class="bi bi-emoji-smile fs-1 text-success d-block mb-3"></i>
                                                    <h5 class="text-success fw-bold">Excellent!</h5>
                                                    <p class="text-muted mb-0">No unclaimed laundry found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>

                @if($laundries->hasPages())
                    <div class="card-footer bg-white border-top">
                        {{ $laundries->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-3">
            {{-- Branch Comparison --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-building me-2 text-primary"></i>By Branch
                    </h6>
                </div>
                <div class="card-body p-0">
                    @foreach($branchStats as $branch)
                        <a href="{{ route('admin.unclaimed.index', ['branch_id' => $branch['id']]) }}"
                           class="d-flex justify-content-between align-items-center p-3 border-bottom text-decoration-none {{ request('branch_id') == $branch['id'] ? 'bg-light' : '' }}">
                            <div>
                                <div class="fw-semibold text-dark">{{ $branch['name'] }}</div>
                                <small class="text-muted">₱{{ number_format($branch['value'], 0) }} at risk</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $branch['critical'] > 0 ? 'danger' : 'secondary' }} px-3">
                                    {{ $branch['total'] }}
                                </span>
                                @if($branch['critical'] > 0)
                                    <div class="small text-danger fw-semibold">{{ $branch['critical'] }} critical</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Storage Fee Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-cash-stack me-2 text-primary"></i>Potential Revenue
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Laundry Value</span>
                        <span class="fw-bold">₱{{ number_format($stats['total_value'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Storage Fees</span>
                        <span class="fw-bold text-warning">₱{{ number_format($stats['storage_fees'], 2) }}</span>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Potential</span>
                        <span class="fw-bold text-success fs-5">₱{{ number_format($stats['potential_total'], 2) }}</span>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2 px-3 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Storage: ₱{{ config('unclaimed.storage_fee_per_day', 10) }}/day after 7 days
                    </div>
                </div>
            </div>

            {{-- This Month Summary --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-calendar3 me-2 text-primary"></i>This Month
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <div class="text-muted small">Recovered</div>
                            <div class="fw-bold text-success">₱{{ number_format($stats['recovered_this_month'], 0) }}</div>
                        </div>
                        <i class="bi bi-arrow-up-circle-fill text-success fs-3"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <div class="text-muted small">Disposed</div>
                            <div class="fw-bold text-secondary">{{ $stats['disposed_this_month'] }} laundries</div>
                        </div>
                        <i class="bi bi-trash-fill text-secondary fs-3"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Lost Revenue</div>
                            <div class="fw-bold text-danger">₱{{ number_format($stats['loss_this_month'] ?? 0, 0) }}</div>
                        </div>
                        <i class="bi bi-arrow-down-circle-fill text-danger fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.laundry-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    updateBulkButton();
});

// Individual checkboxes
document.querySelectorAll('.laundry-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkButton);
});

function updateBulkButton() {
    const checked = document.querySelectorAll('.laundry-checkbox:checked').length;
    const btn = document.getElementById('bulkReminderBtn');
    btn.disabled = checked === 0;
    btn.innerHTML = checked > 0
        ? `<i class="bi bi-send me-1"></i> Send (${checked})`
        : `<i class="bi bi-send me-1"></i> Send Selected`;
}

// Bulk send
document.getElementById('bulkReminderBtn')?.addEventListener('click', function() {
    if (confirm('Send reminders to all selected customers?')) {
        document.getElementById('bulkForm').submit();
    }
});
</script>
@endpush

