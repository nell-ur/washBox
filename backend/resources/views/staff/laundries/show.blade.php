@extends('staff.layouts.staff')

@section('title', 'Laundry Details')
@section('page-title', 'Laundry #' . $laundry->tracking_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/laundry.css') }}">
@endpush

@section('content')
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Order Information -->
            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Laundry Information</h5>
                    <span
                        class="badge {{ $laundry->status === 'completed' ? 'bg-success' : ($laundry->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} fs-6">
                        {{ $laundry->status_label }}
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Tracking Number</label>
                        <div class="fw-semibold">{{ $laundry->tracking_number }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Branch</label>
                        <div><span class="badge bg-secondary">{{ $laundry->branch->name }}</span></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Service</label>
                        <div class="fw-semibold">
                            {{ $laundry->service ? $laundry->service->name : 'Promotion Only' }}
                            @if($laundry->service)
                                <small class="text-muted d-block mt-1">
                                    {{ $laundry->service->service_type_label }}
                                </small>
                            @endif
                        </div>
                    </div>

                    {{-- Loads / Pieces --}}
                    @if($laundry->number_of_loads)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">
                                @if($laundry->service && $laundry->service->service_type === 'special_item')
                                    Number of Pieces
                                @else
                                    Number of Loads
                                @endif
                            </label>
                            <div class="fw-semibold">
                                {{ $laundry->number_of_loads }}
                                @if($laundry->weight)
                                    <small class="text-muted d-block mt-1">
                                        ({{ number_format($laundry->weight, 1) }} kg total)
                                    </small>
                                @endif
                            </div>
                        </div>

                        @if($laundry->service && $laundry->service->service_type === 'full_service' && $laundry->service->max_weight)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Max Weight per Load</label>
                                <div class="fw-semibold">
                                    {{ number_format($laundry->service->max_weight, 1) }} kg
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created By</label>
                        <div>{{ $laundry->createdBy->name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created At</label>
                        <div>{{ $laundry->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    @if($laundry->staff)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assigned Staff</label>
                            <div>{{ $laundry->staff->name }}</div>
                        </div>
                    @endif

                    @if($laundry->pickup_request_id)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Pickup Request</label>
                            <div>
                                <a href="{{ route('staff.pickups.show', $laundry->pickup_request_id) }}" class="badge bg-info text-decoration-none">
                                    #{{ $laundry->pickup_request_id }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                @if($laundry->notes)
                    <div class="alert alert-info mt-3">
                        <strong>Notes:</strong> {{ $laundry->notes }}
                    </div>
                @endif
            </div>

            <!-- Customer Information -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Customer Information</h5>
                <div class="d-flex align-items-center mb-3">
                    @if($laundry->customer->profile_photo_url)
                        <img src="{{ $laundry->customer->profile_photo_url }}" alt="{{ $laundry->customer->name }}"
                            class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                    @else
                        <div class="rounded-circle me-3 d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background: #E5E7EB; font-size: 1.5rem; font-weight: 600;">
                            {{ strtoupper(substr($laundry->customer->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h6 class="mb-0">{{ $laundry->customer->name }}</h6>
                        <div class="text-muted small">{{ $laundry->customer->phone }}</div>
                        @if($laundry->customer->email)
                            <div class="text-muted small">{{ $laundry->customer->email }}</div>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Registration Type</label>
                        <div>
                            <span class="badge bg-{{ $laundry->customer->isWalkIn() ? 'secondary' : 'primary' }}">
                                {{ $laundry->customer->registration_type_label }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Total Laundries</label>
                        <div class="fw-semibold">{{ $laundry->customer->laundries()->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Pricing Breakdown -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Pricing Breakdown</h5>
                <table class="table table-borderless mb-0">
                    @if($laundry->service)
                        {{-- All services use per_load; special_item shows pieces --}}
                        <tr>
                            <td>
                                <strong>{{ $laundry->service->name }}</strong>
                                @php
                                    $isSpecial = $laundry->service->service_type === 'special_item';
                                    $loads = $laundry->number_of_loads ?? 1;
                                    $unit  = $isSpecial ? 'piece' : 'load';
                                    $units = $isSpecial ? 'pieces' : 'loads';
                                @endphp
                                <div class="small text-muted mt-1">
                                    {{ $loads }} {{ $loads > 1 ? $units : $unit }}
                                    × ₱{{ number_format($laundry->service->price_per_load ?? 0, 2) }}/{{ $unit }}
                                    @if($laundry->weight && !$isSpecial)
                                        &bull; {{ number_format($laundry->weight, 1) }} kg total
                                        @if($laundry->service->max_weight)
                                            ({{ number_format($laundry->service->max_weight, 1) }} kg per load)
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold">₱{{ number_format($laundry->subtotal, 2) }}</td>
                        </tr>
                    @elseif($laundry->promotion)
                        <tr>
                            <td>
                                {{ $laundry->promotion->name }} (Promotion Only)
                                @if($laundry->number_of_loads)
                                    ({{ $laundry->number_of_loads }} load{{ $laundry->number_of_loads > 1 ? 's' : '' }})
                                @endif
                            </td>
                            <td class="text-end fw-semibold">₱{{ number_format($laundry->subtotal, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Add-ons --}}
                    @if($laundry->addons && $laundry->addons->count())
                        <tr>
                            <td colspan="2" class="pt-3">
                                <strong>Add-ons:</strong>
                            </td>
                        </tr>
                        @foreach($laundry->addons as $addon)
                            <tr class="small">
                                <td>
                                    <i class="bi bi-plus-circle text-success me-1"></i>
                                    {{ $addon->name }}
                                    <span class="text-muted">({{ $addon->pivot->quantity }} × ₱{{ number_format($addon->pivot->price_at_purchase, 2) }})</span>
                                </td>
                                <td class="text-end">₱{{ number_format($addon->pivot->price_at_purchase * $addon->pivot->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="small border-top">
                            <td class="text-end"><strong>Add-ons Total:</strong></td>
                            <td class="text-end fw-semibold">₱{{ number_format($laundry->addons_total, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Pickup & Delivery Fees --}}
                    @if($laundry->pickup_fee > 0 || $laundry->delivery_fee > 0)
                        <tr class="border-top">
                            <td colspan="2" class="pt-3">
                                <strong>Pickup & Delivery:</strong>
                            </td>
                        </tr>
                        @if($laundry->pickup_fee > 0)
                            <tr class="small">
                                <td><i class="bi bi-truck text-primary me-1"></i> Pickup Fee</td>
                                <td class="text-end">₱{{ number_format($laundry->pickup_fee, 2) }}</td>
                            </tr>
                        @endif
                        @if($laundry->delivery_fee > 0)
                            <tr class="small">
                                <td><i class="bi bi-truck text-success me-1"></i> Delivery Fee</td>
                                <td class="text-end">₱{{ number_format($laundry->delivery_fee, 2) }}</td>
                            </tr>
                        @endif
                    @endif

                    @if($laundry->promotion)
                        <tr class="text-success">
                            <td>
                                <i class="bi bi-tag"></i> Promotion: {{ $laundry->promotion->name }}
                                @if($laundry->promotion->promo_code)
                                    <small class="text-muted">({{ $laundry->promotion->promo_code }})</small>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">-₱{{ number_format($laundry->discount_amount, 2) }}</td>
                        </tr>
                    @endif

                    <tr class="border-top">
                        <td class="fs-5 fw-bold">Total Amount</td>
                        <td class="text-end fs-5 fw-bold text-primary">₱{{ number_format($laundry->total_amount, 2) }}</td>
                    </tr>

                    @if($laundry->payment_status === 'paid')
                        <tr class="text-success">
                            <td>
                                <i class="bi bi-check-circle"></i> Payment Status
                                <small class="text-muted d-block">Paid via {{ $laundry->payment_method }}</small>
                            </td>
                            <td class="text-end fw-semibold">
                                ₱{{ number_format($laundry->total_amount, 2) }}
                                @if($laundry->paid_at)
                                    <small class="text-muted d-block">{{ $laundry->paid_at->format('M d, Y h:i A') }}</small>
                                @endif
                            </td>
                        </tr>
                    @else
                        <tr class="text-danger">
                            <td><i class="bi bi-exclamation-circle"></i> Payment Status</td>
                            <td class="text-end fw-semibold">Unpaid</td>
                        </tr>
                    @endif
                </table>
            </div>

            <!-- Activity Log -->
            <div class="table-container">
                <h5 class="mb-3">Status History</h5>
                <div class="timeline">
                    @foreach($laundry->statusHistories as $history)
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; background: #E5E7EB;">
                                    @php
                                        $icon = match($history->status) {
                                            'received' => 'inbox',
                                            'processing' => 'gear',
                                            'ready' => 'check-circle',
                                            'paid' => 'currency-dollar',
                                            'completed' => 'check-all',
                                            'cancelled' => 'x-circle',
                                            default => 'clock'
                                        };
                                    @endphp
                                    <i class="bi bi-{{ $icon }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    {{ ucfirst($history->status) }}
                                    @if($history->status === 'paid')
                                        <span class="badge bg-success ms-2">Paid</span>
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    {{ $history->changedBy ? 'by ' . $history->changedBy->name : 'System' }} •
                                    {{ $history->created_at->format('M d, Y h:i A') }}
                                </div>
                                @if($history->notes)
                                    <div class="mt-1 small text-muted">{{ $history->notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Timeline -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Order Timeline</h5>
                <div class="timeline-vertical">
                    @php
                        $timeline = $laundry->getTimeline();
                        $currentReached = false;
                    @endphp

                    @foreach(['received', 'processing', 'ready', 'paid', 'completed'] as $stage)
                        @php
                            $isActive = $timeline[$stage] !== null;
                            if (!$isActive)
                                $currentReached = true;
                        @endphp
                        <div class="timeline-item {{ $isActive ? 'active' : ($currentReached ? 'pending' : '') }}">
                            <div class="timeline-marker {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                <i class="bi bi-{{ $isActive ? 'check' : 'circle' }}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="fw-semibold">{{ ucfirst($stage) }}</div>
                                @if($isActive)
                                    <small class="text-muted">{{ $timeline[$stage]->format('M d, Y h:i A') }}</small>
                                @else
                                    <small class="text-muted">Pending</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="d-grid gap-2">
                    @if($laundry->status === 'received')
                        <form action="{{ route('staff.laundries.update-status', $laundry) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="processing">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle"></i> Start Processing
                            </button>
                        </form>
                    @endif

                    @if($laundry->status === 'processing')
                        <form action="{{ route('staff.laundries.update-status', $laundry) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="ready">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Mark as Ready
                            </button>
                        </form>
                    @endif

                    @if($laundry->status === 'ready' && $laundry->payment_status !== 'paid')
                        <a href="#" onclick="event.preventDefault(); document.getElementById('record-payment-form').submit();" class="btn btn-primary w-100">
                            <i class="bi bi-currency-dollar"></i> Record Payment
                        </a>
                        <form id="record-payment-form" action="{{ route('staff.laundries.record-payment', $laundry) }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    @endif

                    @if($laundry->status === 'paid')
                        <form action="{{ route('staff.laundries.update-status', $laundry) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-all"></i> Mark as Completed
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('staff.laundries.receipt', $laundry) }}" class="btn btn-outline-primary w-100"
                        target="_blank">
                        <i class="bi bi-receipt"></i> View Receipt
                    </a>

                    @if(!in_array($laundry->status, ['completed', 'cancelled']))
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                            data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Order
                        </button>
                    @endif
                </div>
            </div>

            <!-- Order Stats -->
            <div class="table-container">
                <h5 class="mb-3">Order Summary</h5>
                <div class="mb-3">
                    <small class="text-muted">Category</small>
                    <div class="fw-semibold">
                        @if($laundry->service)
                            @php
                                $catColor = match($laundry->service->category ?? 'drop_off') {
                                    'drop_off'     => '#3D3B6B',
                                    'self_service' => '#10B981',
                                    'addon'        => '#F59E0B',
                                    default        => '#6B7280',
                                };
                            @endphp
                            <span class="badge" style="background:{{ $catColor }};">
                                {{ $laundry->service->category_label ?? ucfirst($laundry->service->category ?? 'Drop Off') }}
                            </span>
                        @else
                            <span class="badge bg-secondary">N/A</span>
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Service Type</small>
                    <div class="fw-semibold">
                        @if($laundry->service)
                            @php
                                $typeColors = [
                                    'regular_clothes' => 'primary',
                                    'full_service'    => 'primary',
                                    'self_service'    => 'success',
                                    'special_item'    => 'warning',
                                    'addon'           => 'info',
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$laundry->service->service_type] ?? 'secondary' }}">
                                {{ $laundry->service->service_type_label }}
                            </span>
                        @else
                            <span class="badge bg-secondary">Promotion</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Pricing Type</small>
                    <div class="fw-semibold">
                        @if($laundry->service)
                            @if($laundry->service->service_type === 'special_item')
                                Per Piece
                            @else
                                Per Load
                            @endif
                        @else
                            Promotion Fixed Price
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Turnaround Time</small>
                    <div class="fw-semibold">
                        @if($laundry->service)
                            {{ $laundry->service->turnaround_time }} hours
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                @if($laundry->promotion)
                    <div class="mb-3">
                        <small class="text-muted">Promotion Applied</small>
                        <div class="fw-semibold text-success">
                            {{ $laundry->promotion->name }}
                            @if($laundry->promotion->promo_code)
                                <div class="small text-muted">({{ $laundry->promotion->promo_code }})</div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <small class="text-muted">Order Age</small>
                    <div class="fw-semibold">{{ $laundry->created_at->diffForHumans() }}</div>
                </div>

                @if($laundry->payment_status !== 'paid')
                    <div class="mb-3">
                        <small class="text-muted">Days Unclaimed</small>
                        <div class="fw-semibold {{ $laundry->days_unclaimed >= 3 ? 'text-danger' : '' }}">
                            {{ $laundry->days_unclaimed }} days
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('staff.laundries.update-status', $laundry) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">

                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Laundry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This action cannot be undone.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" required
                                placeholder="Please provide a reason for cancellation"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel Laundry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
