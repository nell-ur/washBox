@extends('admin.layouts.app')

@section('title', 'Laundry Details')
@section('page-title', 'Laundry #' . $laundry->tracking_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/laundry.css') }}">
@endpush

@section('content')
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Laundry Information -->
            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Laundry Information</h5>
                    <span class="badge {{ $laundry->status === 'completed' ? 'bg-success' : ($laundry->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} fs-6">
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
                        <div><span class="badge bg-secondary">{{ $laundry->branch->name ?? 'N/A' }}</span></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Service</label>
                        <div class="fw-semibold">
                            {{ $laundry->service ? $laundry->service->name : 'Promotion Only' }}
                            @if($laundry->service)
                                <small class="text-muted d-block mt-1">{{ $laundry->service->service_type_label }}</small>
                            @endif
                        </div>
                    </div>
                    {{-- Weight (always shown when available) --}}
                    @if($laundry->weight)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Weight</label>
                            <div class="fw-semibold">{{ number_format($laundry->weight, 1) }} kg</div>
                        </div>
                    @endif

                    {{-- Loads (shown for per_load services) --}}
                    @if($laundry->number_of_loads)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">
                                @if($laundry->service && $laundry->service->service_type === 'special_item')
                                    Number of Pieces
                                @else
                                    Number of Loads
                                @endif
                            </label>
                            <div class="fw-semibold">{{ $laundry->number_of_loads }}</div>
                        </div>
                    @endif
                    @if($laundry->service && $laundry->service->service_type === 'full_service' && $laundry->service->max_weight)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Max Weight per Load</label>
                            <div class="fw-semibold">{{ number_format($laundry->service->max_weight, 1) }} kg</div>
                        </div>
                    @endif
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created By</label>
                        <div>{{ $laundry->createdBy->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created At</label>
                        <div>{{ $laundry->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($laundry->staff)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assigned Staff</label>
                            <div>{{ $laundry->staff->name ?? 'N/A' }}</div>
                        </div>
                    @endif
                    @if($laundry->pickup_request_id)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Pickup Request</label>
                            <div>
                                <a href="{{ route('admin.pickups.show', $laundry->pickup_request_id) }}" class="badge bg-info text-decoration-none">
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
                        <h6 class="mb-0">
                            <a href="{{ route('admin.customers.show', $laundry->customer) }}">{{ $laundry->customer->name }}</a>
                        </h6>
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
                    @if($laundry->promotion && $laundry->promotion->application_type === 'per_load_override')
                        <tr>
                            <td colspan="2" class="pb-2">
                                <strong class="text-primary">
                                    <i class="bi bi-tag-fill me-1"></i>{{ $laundry->promotion->name }}
                                </strong>
                                @if($laundry->promotion->promo_code)
                                    <span class="badge bg-primary ms-2">{{ $laundry->promotion->promo_code }}</span>
                                @endif
                                <div class="small text-muted mt-1">Fixed price promotion: ₱{{ number_format($laundry->promotion->display_price, 2) }}/load</div>
                            </td>
                        </tr>
                        @if($laundry->number_of_loads)
                            @for($i = 1; $i <= $laundry->number_of_loads; $i++)
                                <tr class="small">
                                    <td class="ps-4">
                                        <i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i>
                                        Promotion Load {{ $i }}
                                    </td>
                                    <td class="text-end">₱{{ number_format($laundry->promotion->display_price, 2) }}</td>
                                </tr>
                            @endfor
                            <tr class="small border-top">
                                <td class="ps-4"><strong>Total ({{ $laundry->number_of_loads }} load{{ $laundry->number_of_loads > 1 ? 's' : '' }}):</strong></td>
                                <td class="text-end fw-semibold text-success">₱{{ number_format($laundry->number_of_loads * $laundry->promotion->display_price, 2) }}</td>
                            </tr>
                        @endif

                    @elseif($laundry->service)
                        {{-- All services use per_load; special_item shows "pieces" label --}}
                        <tr>
                                <td colspan="2" class="pb-2">
                                    <strong>{{ $laundry->service->name }}</strong>
                                    <div class="small text-muted mt-1">
                                        @if($laundry->service->service_type === 'special_item')
                                            {{ $laundry->number_of_loads }} piece{{ $laundry->number_of_loads > 1 ? 's' : '' }} × ₱{{ number_format($laundry->service->price_per_load, 2) }}/piece
                                        @else
                                            {{ $laundry->number_of_loads }} load{{ $laundry->number_of_loads > 1 ? 's' : '' }} × ₱{{ number_format($laundry->service->price_per_load, 2) }}/load
                                        @endif
                                        @if($laundry->service->service_type === 'full_service')
                                            <br>{{ number_format($laundry->weight ?? 0, 1) }} kg total
                                            @if($laundry->service->max_weight)
                                                ({{ number_format($laundry->service->max_weight, 1) }} kg per load)
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($laundry->number_of_loads > 1)
                                @for($i = 1; $i <= $laundry->number_of_loads; $i++)
                                    <tr class="small">
                                        <td class="ps-4">
                                            <i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i>
                                            Service Load {{ $i }}
                                        </td>
                                        <td class="text-end">₱{{ number_format($laundry->service->price_per_load, 2) }}</td>
                                    </tr>
                                @endfor
                                <tr class="small border-top">
                                    <td class="ps-4"><strong>Service Total ({{ $laundry->number_of_loads }} load{{ $laundry->number_of_loads > 1 ? 's' : '' }}):</strong></td>
                                    <td class="text-end fw-semibold text-success">₱{{ number_format($laundry->subtotal, 2) }}</td>
                                </tr>
                            @else
                                <tr class="small">
                                    <td class="ps-4">Service Subtotal</td>
                                    <td class="text-end fw-semibold">₱{{ number_format($laundry->subtotal, 2) }}</td>
                                </tr>
                            @endif
                    @elseif($laundry->promotion)
                        <tr>
                            <td>
                                <strong>{{ $laundry->promotion->name }}</strong>
                                <div class="small text-muted mt-1">Promotion applied to laundry</div>
                            </td>
                            <td class="text-end fw-semibold">₱{{ number_format($laundry->subtotal, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Add-ons --}}
                    @if($laundry->addons && $laundry->addons->count())
                        <tr>
                            <td colspan="2" class="pt-3 border-top">
                                <strong><i class="bi bi-plus-circle text-success me-1"></i> Add-ons</strong>
                            </td>
                        </tr>
                        @foreach($laundry->addons as $addon)
                            <tr class="small">
                                <td class="ps-4">
                                    <i class="bi bi-circle-fill text-success me-2" style="font-size: 0.5rem;"></i>
                                    {{ $addon->name }}
                                    <span class="text-muted">({{ $addon->pivot->quantity }} × ₱{{ number_format($addon->pivot->price_at_purchase, 2) }})</span>
                                </td>
                                <td class="text-end">₱{{ number_format($addon->pivot->price_at_purchase * $addon->pivot->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="small border-top">
                            <td class="ps-4 text-end"><strong>Add-ons Total:</strong></td>
                            <td class="text-end fw-semibold text-success">₱{{ number_format($laundry->addons_total, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Pickup & Delivery Fees --}}
                    @if($laundry->pickup_fee > 0 || $laundry->delivery_fee > 0)
                        <tr>
                            <td colspan="2" class="pt-3 border-top">
                                <strong><i class="bi bi-truck text-primary me-1"></i> Pickup & Delivery</strong>
                            </td>
                        </tr>
                        @if($laundry->pickup_fee > 0)
                            <tr class="small">
                                <td class="ps-4"><i class="bi bi-arrow-down-circle text-primary me-2"></i>Pickup Fee</td>
                                <td class="text-end">₱{{ number_format($laundry->pickup_fee, 2) }}</td>
                            </tr>
                        @endif
                        @if($laundry->delivery_fee > 0)
                            <tr class="small">
                                <td class="ps-4"><i class="bi bi-arrow-up-circle text-success me-2"></i>Delivery Fee</td>
                                <td class="text-end">₱{{ number_format($laundry->delivery_fee, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="small border-top">
                            <td class="ps-4 text-end"><strong>Fees Total:</strong></td>
                            <td class="text-end fw-semibold">₱{{ number_format($laundry->pickup_fee + $laundry->delivery_fee, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Promotion Discount (non-override) --}}
                    @if($laundry->promotion && $laundry->promotion->application_type !== 'per_load_override' && $laundry->discount_amount > 0)
                        <tr class="text-success border-top pt-2">
                            <td class="pt-3">
                                <i class="bi bi-tag-fill me-1"></i>
                                <strong>Promotion Discount:</strong> {{ $laundry->promotion->name }}
                                @if($laundry->promotion->promo_code)
                                    <span class="badge bg-success ms-2">{{ $laundry->promotion->promo_code }}</span>
                                @endif
                                <div class="small text-muted mt-1">
                                    @if($laundry->promotion->discount_type === 'percentage')
                                        {{ $laundry->promotion->discount_value }}% discount applied
                                    @else
                                        Fixed discount of ₱{{ number_format($laundry->promotion->discount_value, 2) }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold pt-3">-₱{{ number_format($laundry->discount_amount, 2) }}</td>
                        </tr>
                    @endif

                    {{-- Grand Total --}}
                    <tr class="border-top">
                        <td class="fs-5 fw-bold pt-3">
                            <i class="bi bi-calculator me-2" style="color: #3D3B6B;"></i>Grand Total
                        </td>
                        <td class="text-end fs-5 fw-bold text-primary pt-3">₱{{ number_format($laundry->total_amount, 2) }}</td>
                    </tr>

                    {{-- Payment Status --}}
                    @if($laundry->payment_status === 'paid')
                        <tr class="text-success bg-success bg-opacity-10">
                            <td class="pt-3">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Payment Status</strong>
                                <div class="small text-muted mt-1">
                                    Paid via {{ ucfirst($laundry->payment_method) }}
                                    @if($laundry->paid_at) on {{ $laundry->paid_at ? $laundry->paid_at->format('M d, Y h:i A') : '' }} @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold pt-3">
                                <span class="badge bg-success fs-6">PAID</span>
                            </td>
                        </tr>
                    @else
                        <tr class="text-danger bg-danger bg-opacity-10">
                            <td class="pt-3">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <strong>Payment Status</strong>
                            </td>
                            <td class="text-end fw-semibold pt-3">
                                <span class="badge bg-danger fs-6">UNPAID</span>
                            </td>
                        </tr>
                    @endif
                </table>
            </div>

            <!-- Payment Status Banner -->
            @if($laundry->payment_status === 'paid')
                <div class="alert alert-success mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                        <div>
                            <strong class="d-block">Payment Completed</strong>
                            <div class="small mt-1">
                                {{ ucfirst($laundry->payment_method) }} •
                                @if($laundry->paid_at)
                                    {{ $laundry->paid_at ? $laundry->paid_at->format('M d, Y h:i A') : '' }}
                                @else
                                    Payment date not recorded
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Delivery Information -->
            @if($laundry->delivery_address || $laundry->expected_delivery_date)
                <div class="table-container mb-4">
                    <h5 class="mb-3">Delivery Information</h5>
                    <div class="row">
                        @if($laundry->delivery_address)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Delivery Address</label>
                                <div class="fw-semibold">{{ $laundry->delivery_address }}</div>
                            </div>
                        @endif
                        @if($laundry->expected_delivery_date)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Expected Delivery</label>
                                <div class="fw-semibold">{{ $laundry->expected_delivery_date ? $laundry->expected_delivery_date->format('M d, Y') : '' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Laundry Items -->
            @if($laundry->items && $laundry->items->count())
                <div class="table-container mb-4">
                    <h5 class="mb-3">Laundry Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($laundry->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->pivot->quantity }}</td>
                                        <td>₱{{ number_format($item->pivot->unit_price, 2) }}</td>
                                        <td>₱{{ number_format($item->pivot->quantity * $item->pivot->unit_price, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $item->pivot->status === 'completed' ? 'success' : ($item->pivot->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($item->pivot->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Status History -->
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
                                            'received'   => 'inbox',
                                            'processing' => 'gear',
                                            'ready'      => 'check-circle',
                                            'paid'       => 'currency-dollar',
                                            'completed'  => 'check-all',
                                            'cancelled'  => 'x-circle',
                                            default      => 'clock'
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
                <h5 class="mb-3">Laundry Timeline</h5>
                <div class="timeline-vertical">
                    @php
                        $timeline = $laundry->getTimeline();
                        $currentReached = false;
                    @endphp
                    @foreach(['received', 'processing', 'ready', 'paid', 'completed'] as $stage)
                        @php
                            $isActive = $timeline[$stage] !== null;
                            if (!$isActive) $currentReached = true;
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
                        <form action="{{ route('admin.laundries.update-status', $laundry) }}" method="POST" class="status-change-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="processing">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle"></i> Start Processing
                            </button>
                        </form>
                    @endif
                    @if($laundry->status === 'processing')
                        <form action="{{ route('admin.laundries.update-status', $laundry) }}" method="POST" class="status-change-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ready">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Mark as Ready
                            </button>
                        </form>
                    @endif
                    @if($laundry->status === 'ready' && $laundry->payment_status !== 'paid')
                        <form action="{{ route('admin.laundries.record-payment', $laundry) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-currency-dollar"></i> Record Payment
                            </button>
                        </form>
                    @endif
                    @if($laundry->status === 'paid')
                        <form action="{{ route('admin.laundries.update-status', $laundry) }}" method="POST" class="status-change-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-all"></i> Mark as Completed
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.receipts.show', $laundry) }}" class="btn btn-outline-primary w-100" target="_blank">
                        <i class="bi bi-receipt"></i> View Receipt
                    </a>
                    <a href="{{ route('admin.laundries.edit', $laundry) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-pencil"></i> Edit Laundry
                    </a>
                    @if(!in_array($laundry->status, ['completed', 'cancelled']))
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Laundry
                        </button>
                    @endif
                </div>
            </div>

            <!-- Laundry Summary -->
            <div class="table-container">
                <h5 class="mb-3">Laundry Summary</h5>
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
                                $typeColors = ['regular_clothes' => 'primary', 'full_service' => 'primary', 'self_service' => 'success', 'special_item' => 'warning', 'addon' => 'info'];
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
                        @if($laundry->service) {{ $laundry->service->turnaround_time }} hours @else N/A @endif
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
                    <small class="text-muted">Laundry Age</small>
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

    <!-- Cancel Laundry Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.laundries.update-status', $laundry) }}" method="POST">
                    @csrf
                    @method('PUT')
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-change-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to change the laundry status?')) {
                        e.preventDefault();
                    }
                });
            });
            const recordPaymentForm = document.querySelector('form[action*="record-payment"]');
            if (recordPaymentForm) {
                recordPaymentForm.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to record payment for this laundry?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
@endpush
