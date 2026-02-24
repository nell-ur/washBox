@extends('staff.layouts.staff')

@section('page-title', 'Unclaimed Laundry')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/unclaimed.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Unclaimed Laundry
            </h4>
            <p class="text-muted mb-0">Manage and follow up on unclaimed laundries to recover revenue for {{ $currentBranch->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.unclaimed.export') }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <button type="button" class="btn btn-primary" id="bulkReminderBtn" disabled>
                <i class="bi bi-send me-1"></i> Send Selected
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="stats-grid">
        {{-- Total Unclaimed --}}
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper warning me-3">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Total Unclaimed</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-trend">
                        Value: ₱{{ number_format($stats['total_value'], 0) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Critical (14+ days) --}}
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper danger me-3">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Critical (14+ days)</div>
                    <div class="stat-value text-danger">{{ $stats['critical'] }}</div>
                    <div class="stat-trend">
                        ₱{{ number_format($stats['critical_value'] ?? 0, 0) }} at risk
                    </div>
                </div>
            </div>
        </div>

        {{-- Urgent (7-13 days) --}}
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper warning me-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Urgent (7-13 days)</div>
                    <div class="stat-value">{{ $stats['urgent'] }}</div>
                    <div class="stat-trend">
                        Needs attention
                    </div>
                </div>
            </div>
        </div>

        {{-- Reminders Today --}}
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper success me-3">
                    <i class="bi bi-send-check"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Reminders Today</div>
                    <div class="stat-value">{{ $stats['reminders_today'] }}</div>
                    <div class="stat-trend">
                        Notifications sent
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Urgency Summary --}}
    <div class="urgency-card">
        <div class="urgency-grid">
            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'critical']) }}"
               class="urgency-item {{ request('urgency') == 'critical' ? 'active' : '' }}">
                <span class="urgency-badge critical">{{ $stats['critical'] }}</span>
                <div class="urgency-label">🚨 Critical</div>
                <div class="urgency-desc">14+ days</div>
            </a>
            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'urgent']) }}"
               class="urgency-item {{ request('urgency') == 'urgent' ? 'active' : '' }}">
                <span class="urgency-badge urgent">{{ $stats['urgent'] }}</span>
                <div class="urgency-label">⚠️ Urgent</div>
                <div class="urgency-desc">7-13 days</div>
            </a>
            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'warning']) }}"
               class="urgency-item {{ request('urgency') == 'warning' ? 'active' : '' }}">
                <span class="urgency-badge warning">{{ $stats['warning'] }}</span>
                <div class="urgency-label">⏰ Warning</div>
                <div class="urgency-desc">3-6 days</div>
            </a>
            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'pending']) }}"
               class="urgency-item {{ request('urgency') == 'pending' ? 'active' : '' }}">
                <span class="urgency-badge pending">{{ $stats['pending'] }}</span>
                <div class="urgency-label">📌 Pending</div>
                <div class="urgency-desc">1-2 days</div>
            </a>
            <a href="{{ route('staff.unclaimed.index') }}"
               class="urgency-item {{ !request('urgency') ? 'active' : '' }}">
                <span class="urgency-badge all">{{ $stats['total'] }}</span>
                <div class="urgency-label">📊 All</div>
                <div class="urgency-desc">Total</div>
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="filter-label">Search</label>
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="z-index: 10;"></i>
                    <input type="text" name="search" class="filter-input"
                           placeholder="Tracking #, customer name, phone..."
                           value="{{ request('search') }}"
                           style="padding-left: 2.5rem;">
                </div>
            </div>
            <div class="col-md-2">
                <label class="filter-label">Urgency</label>
                <select name="urgency" class="filter-select">
                    <option value="">All</option>
                    <option value="critical" {{ request('urgency') == 'critical' ? 'selected' : '' }}>🚨 Critical (14+)</option>
                    <option value="urgent" {{ request('urgency') == 'urgent' ? 'selected' : '' }}>⚠️ Urgent (7-13)</option>
                    <option value="warning" {{ request('urgency') == 'warning' ? 'selected' : '' }}>⏰ Warning (3-6)</option>
                    <option value="pending" {{ request('urgency') == 'pending' ? 'selected' : '' }}>📌 Pending (1-2)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label">Min Days</label>
                <input type="number" name="min_days" class="filter-input"
                       placeholder="0" min="0" value="{{ request('min_days') }}">
            </div>
            <div class="col-md-2">
                <label class="filter-label">Max Days</label>
                <input type="number" name="max_days" class="filter-input"
                       placeholder="30" min="0" value="{{ request('max_days') }}">
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="filter-btn filter-btn-primary flex-grow-1">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('staff.unclaimed.index') }}" class="filter-btn filter-btn-clear">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- Active Filters Display --}}
        @if(request()->anyFilled(['search', 'urgency', 'min_days', 'max_days']))
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small">Active filters:</span>
                    @if(request('search'))
                        <span class="filter-badge">
                            <i class="bi bi-search me-1"></i> "{{ request('search') }}"
                            <a href="{{ request()->fullUrlWithoutQuery(['search']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('urgency'))
                        <span class="filter-badge">
                            <i class="bi bi-{{ request('urgency') == 'critical' ? 'exclamation-octagon' : (request('urgency') == 'urgent' ? 'clock-history' : (request('urgency') == 'warning' ? 'exclamation-triangle' : 'inbox')) }} me-1"></i>
                            {{ ucfirst(request('urgency')) }}
                            <a href="{{ request()->fullUrlWithoutQuery(['urgency']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('min_days'))
                        <span class="filter-badge">
                            <i class="bi bi-calendar2-week me-1"></i> Min: {{ request('min_days') }} days
                            <a href="{{ request()->fullUrlWithoutQuery(['min_days']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('max_days'))
                        <span class="filter-badge">
                            <i class="bi bi-calendar2-week me-1"></i> Max: {{ request('max_days') }} days
                            <a href="{{ request()->fullUrlWithoutQuery(['max_days']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    <a href="{{ route('staff.unclaimed.index') }}" class="btn btn-sm btn-link text-danger p-0 ms-2">
                        Clear all
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Orders Table --}}
    <div class="table-card">
        <div class="table-header">
            <h6>
                <i class="bi bi-list-ul"></i> Unclaimed Laundry - {{ $currentBranch->name }}
            </h6>
            <span class="badge">{{ $laundries->total() }} total</span>
        </div>

        <div class="table-responsive">
            <form id="bulkForm" action="{{ route('staff.unclaimed.bulk-reminders') }}" method="POST">
                @csrf
                <table class="unclaimed-table">
                    <thead>
                        <tr>
                            <th width="40" class="ps-4">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Laundry</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Days</th>
                            <th>Status</th>
                            <th class="text-center">Reminders</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laundries as $laundry)
                            @php
                                $days = $laundry->days_unclaimed ?? 0;
                                $urgency = $laundry->unclaimed_status ?? 'pending';
                                $color = $laundry->unclaimed_color ?? 'secondary';
                            @endphp
                            <tr class="{{ $urgency === 'critical' ? 'table-danger' : ($urgency === 'urgent' ? 'table-warning' : '') }}">
                                <td class="ps-4">
                                    <input type="checkbox" class="form-check-input laundry-checkbox"
                                           name="laundry_ids[]" value="{{ $laundry->id }}">
                                </td>
                                <td>
                                    <a href="{{ route('staff.unclaimed.show', $laundry) }}" class="tracking-link">
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
                                <td>{{ $laundry->service->name ?? 'N/A' }}</td>
                                <td class="text-end fw-bold">₱{{ number_format($laundry->total_amount, 2) }}</td>
                                <td class="text-center">
                                    <span class="days-badge bg-{{ $color }}">{{ $days }}</span>
                                </td>
                                <td>
                                    <span class="urgency-status {{ $urgency }}">
                                        @switch($urgency)
                                            @case('critical')
                                                <i class="bi bi-exclamation-octagon"></i> Critical
                                                @break
                                            @case('urgent')
                                                <i class="bi bi-clock-history"></i> Urgent
                                                @break
                                            @case('warning')
                                                <i class="bi bi-exclamation-triangle"></i> Warning
                                                @break
                                            @default
                                                <i class="bi bi-inbox"></i> Pending
                                        @endswitch
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $laundry->reminder_count ?? 0 }}</span>
                                    @if($laundry->last_reminder_at)
                                        <div class="small text-muted">{{ $laundry->last_reminder_at->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="action-group">
                                        {{-- Call Customer --}}
                                        <a href="tel:{{ $laundry->customer->phone ?? '' }}"
                                           class="action-btn call" title="Call Customer">
                                            <i class="bi bi-telephone"></i>
                                        </a>

                                        {{-- Send Reminder --}}
                                        <form action="{{ route('staff.unclaimed.send-reminder', $laundry->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-btn notify" title="Send Push Notification">
                                                <i class="bi bi-bell"></i>
                                            </button>
                                        </form>

                                        {{-- View Details --}}
                                        <a href="{{ route('staff.unclaimed.show', $laundry) }}"
                                           class="action-btn" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        {{-- Mark Claimed --}}
                                        <form action="{{ route('staff.unclaimed.mark-claimed', $laundry->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-btn claim"
                                                    title="Mark as Claimed"
                                                    onclick="return confirm('Mark this laundry as claimed/paid?')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state success">
                                        <i class="bi bi-emoji-smile"></i>
                                        <h5 class="text-success">Great news!</h5>
                                        <p class="text-muted mb-0">No unclaimed laundry at the moment.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($laundries->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing {{ $laundries->firstItem() }} to {{ $laundries->lastItem() }} of {{ $laundries->total() }} items
                </div>
                <div>
                    {{ $laundries->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Recovery Tips --}}
    @if($stats['total'] > 0)
        <div class="tips-card">
            <h6 class="tips-title">
                <i class="bi bi-lightbulb"></i> Recovery Tips
            </h6>
            <div class="tips-grid">
                <div class="tip-item">
                    <span class="tip-number">1</span>
                    <div class="tip-content">
                        <strong>Call first</strong>
                        <p>Personal calls have higher success rates than notifications.</p>
                    </div>
                </div>
                <div class="tip-item">
                    <span class="tip-number">2</span>
                    <div class="tip-content">
                        <strong>Be friendly</strong>
                        <p>Remind customers their laundry is waiting, don't pressure.</p>
                    </div>
                </div>
                <div class="tip-item">
                    <span class="tip-number">3</span>
                    <div class="tip-content">
                        <strong>Offer delivery</strong>
                        <p>For critical cases, offer delivery service.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Call Log Modal --}}
<div class="modal fade" id="callLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Call Attempt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="callLogLaundryId">
                <div class="mb-3">
                    <label class="form-label">Call Result</label>
                    <select class="form-select" id="callResult" required>
                        <option value="">Select result...</option>
                        <option value="answered">✅ Answered - Will pickup</option>
                        <option value="no_answer">📵 No Answer</option>
                        <option value="busy">📞 Busy</option>
                        <option value="wrong_number">❌ Wrong Number</option>
                        <option value="voicemail">📼 Voicemail</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea class="form-control" id="callNotes" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCallLog()">Save Log</button>
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

// Call log modal
function openCallLog(laundryId) {
    document.getElementById('callLogLaundryId').value = laundryId;
    document.getElementById('callResult').value = '';
    document.getElementById('callNotes').value = '';
    new bootstrap.Modal(document.getElementById('callLogModal')).show();
}

function submitCallLog() {
    const laundryId = document.getElementById('callLogLaundryId').value;
    const result = document.getElementById('callResult').value;
    const notes = document.getElementById('callNotes').value;

    if (!result) {
        alert('Please select a call result');
        return;
    }

    fetch(`/staff/unclaimed/${laundryId}/log-call`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ result, notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('callLogModal')).hide();
            location.reload();
        }
    });
}
</script>
@endpush
