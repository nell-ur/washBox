@extends('admin.layouts.app')

@section('page-title', 'Unclaimed Laundry Details')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <a href="{{ route('admin.unclaimed.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Unclaimed List
            </a>
            <h3 class="fw-bold mt-2 mb-1">Order #{{ $laundry->tracking_number }}</h3>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @php
                    $days = $laundry->days_unclaimed ?? 0;
                    $urgency = $laundry->unclaimed_status ?? 'normal';
                    $color = $laundry->unclaimed_color ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $color }} fs-6">{{ $days }} Days Unclaimed</span>
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
                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $laundry->branch->name ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="tel:{{ $laundry->customer->phone ?? '' }}" class="btn btn-success">
                <i class="bi bi-telephone me-1"></i> Call
            </a>
            <form action="{{ route('admin.unclaimed.send-reminder', $laundry->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-bell me-1"></i> Send Reminder
                </button>
            </form>
            <form action="{{ route('admin.unclaimed.mark-claimed', $laundry->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Mark as claimed?')">
                    <i class="bi bi-check-lg me-1"></i> Mark Claimed
                </button>
            </form>
            @if($days >= 30)
                <form action="{{ route('admin.unclaimed.mark-disposed', $laundry->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Dispose? This cannot be undone.')">
                        <i class="bi bi-trash me-1"></i> Dispose
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if($urgency === 'critical')
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Critical Alert!</strong> This laundry has been unclaimed for {{ $days }} days.
                @if($days >= 30)
                    <strong class="text-danger">Eligible for disposal now.</strong>
                @else
                    <strong>{{ 30 - $days }} days until disposal.</strong>
                @endif
            </div>
        </div>
    @elseif($urgency === 'urgent')
        <div class="alert alert-warning d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-circle-fill fs-4 me-3"></i>
            <div>
                <strong>Urgent!</strong> Unclaimed for {{ $days }} days.
                @php $storageFee = $laundry->calculated_storage_fee ?? 0; @endphp
                Storage fee: <strong>₱{{ number_format($storageFee, 2) }}</strong>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Order Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-box me-2"></i>Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small">Tracking Number</label>
                            <p class="fw-bold mb-2">{{ $laundry->tracking_number }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Branch</label>
                            <p class="mb-2">{{ $laundry->branch->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Service</label>
                            <p class="mb-2">{{ $laundry->service->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Weight</label>
                            <p class="mb-2">{{ $laundry->formatted_weight ?? ($laundry->weight . ' kg') }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Received</label>
                            <p class="mb-2">{{ $laundry->received_at?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Ready</label>
                            <p class="mb-2">{{ $laundry->ready_at?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end">₱{{ number_format($laundry->subtotal ?? $laundry->total_amount, 2) }}</td>
                                </tr>
                                @if(($laundry->discount_amount ?? 0) > 0)
                                    <tr>
                                        <td class="text-muted">Discount</td>
                                        <td class="text-end text-success">-₱{{ number_format($laundry->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-top">
                                    <td class="fw-bold">Order Total</td>
                                    <td class="text-end fw-bold fs-5">₱{{ number_format($laundry->total_amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                        @php $storageFee = $laundry->calculated_storage_fee ?? 0; @endphp
                        @if($storageFee > 0)
                            <div class="col-md-6">
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td class="text-muted">Storage Fee ({{ $days - 7 }} days)</td>
                                            <td class="text-end text-warning fw-bold">₱{{ number_format($storageFee, 2) }}</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">Total with Fees</td>
                                            <td class="text-end fw-bold text-danger fs-5">₱{{ number_format($laundry->total_amount + $storageFee, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Customer Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Name</label>
                            <p class="fw-bold mb-2">{{ $laundry->customer->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Phone</label>
                            <p class="mb-2">
                                <a href="tel:{{ $laundry->customer->phone ?? '' }}" class="text-decoration-none">
                                    <i class="bi bi-telephone text-success me-1"></i>{{ $laundry->customer->phone ?? 'N/A' }}
                                </a>
                            </p>
                        </div>
                        @if($laundry->customer->email ?? null)
                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <p class="mb-2">
                                    <a href="mailto:{{ $laundry->customer->email }}">{{ $laundry->customer->email }}</a>
                                </p>
                            </div>
                        @endif
                        @if($laundry->customer->address ?? null)
                            <div class="col-12">
                                <label class="text-muted small">Address</label>
                                <p class="mb-2">{{ $laundry->customer->address }}</p>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="tel:{{ $laundry->customer->phone ?? '' }}" class="btn btn-success">
                            <i class="bi bi-telephone me-1"></i> Call Now
                        </a>
                        <a href="sms:{{ $laundry->customer->phone ?? '' }}?body=Hi {{ $laundry->customer->name ?? 'Customer' }}, this is WashBox {{ $laundry->branch->name ?? '' }}. Your laundry (Order #{{ $laundry->tracking_number }}) has been ready for {{ $days }} days. Please pick it up soon. Thank you!"
                           class="btn btn-outline-primary">
                            <i class="bi bi-chat-dots me-1"></i> Send SMS
                        </a>
                        @if($laundry->customer)
                            <a href="{{ route('admin.customers.show', $laundry->customer) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-person me-1"></i> View Profile
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Quick Stats --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Days Unclaimed</span>
                        <span class="fw-bold text-{{ $color }}">{{ $days }} days</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Reminders Sent</span>
                        <span class="fw-bold">{{ $laundry->reminder_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Last Reminder</span>
                        <span>{{ $laundry->last_reminder_at?->diffForHumans() ?? 'Never' }}</span>
                    </div>
                    @php $storageFee = $laundry->calculated_storage_fee ?? 0; @endphp
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Storage Fee</span>
                        <span class="fw-bold text-warning">₱{{ number_format($storageFee, 2) }}</span>
                    </div>
                    <hr>
                    @php $daysUntilDisposal = max(0, 30 - $days); @endphp
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Days Until Disposal</span>
                        <span class="fw-bold {{ $daysUntilDisposal <= 7 ? 'text-danger' : '' }}">
                            {{ $daysUntilDisposal }} days
                        </span>
                    </div>
                </div>
            </div>

            {{-- Reminder History --}}
            <div class="card blaundry-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-bell-history me-2"></i>Reminder History</h6>
                    <span class="badge bg-secondary">{{ $reminderHistory->count() }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    @forelse($reminderHistory as $reminder)
                        <div class="p-3 blaundry-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold small">{{ $reminder->title }}</div>
                                    <div class="text-muted small">{{ Str::limit($reminder->body, 50) }}</div>
                                </div>
                                @if(($reminder->fcm_status ?? '') === 'sent')
                                    <span class="badge bg-success">Sent</span>
                                @elseif(($reminder->fcm_status ?? '') === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-clock me-1"></i>{{ $reminder->created_at->format('M d, Y h:i A') }}
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                            <small>No reminders sent yet</small>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Activity Log --}}
            <div class="card blaundry-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Activity Log</h6>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                    @forelse($laundry->statusHistories->take(10) as $history)
                        <div class="p-3 blaundry-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary">{{ ucfirst($history->status) }}</span>
                                <small class="text-muted">{{ $history->created_at->diffForHumans() }}</small>
                            </div>
                            @if($history->notes)
                                <div class="small mt-1">{{ $history->notes }}</div>
                            @endif
                            @if($history->changedBy)
                                <div class="small text-muted">By: {{ $history->changedBy->name }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">
                            <small>No activity logged</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
