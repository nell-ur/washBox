@extends('staff.layouts.app')

@section('title', 'Promotion Details')
@section('page-title', 'Promotion Details')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $promotion->name }}</h4>
            <p class="text-muted small mb-0">Promotion details and usage statistics</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Promotions
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column - Promotion Info --}}
        <div class="col-lg-4">
            {{-- Promotion Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    {{-- Icon --}}
                    <div class="mb-3">
                        <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                            style="width: 150px; height: 150px; background: linear-gradient(135deg, {{ $promotion->is_active ? '#0d6efd' : '#6B7280' }} 0%, {{ $promotion->is_active ? '#0a58ca' : '#9CA3AF' }} 100%);">
                            <i class="bi bi-megaphone text-white" style="font-size: 4rem;"></i>
                        </div>
                    </div>

                    {{-- Name & Code --}}
                    <h5 class="fw-bold mb-1">{{ $promotion->name }}</h5>
                    @if($promotion->code)
                        <p class="text-muted mb-3">Code: <span class="badge bg-light text-dark border">{{ $promotion->code }}</span></p>
                    @endif

                    {{-- Status Badge --}}
                    <span class="badge mb-3" style="background: {{ $promotion->is_active ? '#198754' : '#6B7280' }}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                        <i class="bi bi-{{ $promotion->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $promotion->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>

                    {{-- Discount Badge --}}
                    <div class="mb-3">
                        <div class="badge bg-light text-dark border fs-5 py-2 px-3">
                            @if($promotion->discount_type === 'percentage')
                                <i class="bi bi-percent me-1 text-info"></i>
                                {{ $promotion->discount_value }}% OFF
                            @else
                                <i class="bi bi-tag me-1 text-warning"></i>
                                ₱{{ number_format($promotion->discount_value, 2) }} OFF
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Promotion Details --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Promotion Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if($promotion->description)
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block mb-1">Description</small>
                            <p class="mb-0">{{ $promotion->description }}</p>
                        </div>
                    @endif

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Application Type</small>
                        <strong class="d-block">
                            @if($promotion->application_type === 'per_load_override')
                                <span class="badge bg-primary">Per Load Override</span>
                                @if($promotion->display_price)
                                    <div class="mt-2">Display Price: ₱{{ number_format($promotion->display_price, 2) }}/load</div>
                                @endif
                            @elseif($promotion->application_type === 'all_services')
                                <span class="badge bg-success">All Services</span>
                            @elseif($promotion->application_type === 'specific_services')
                                <span class="badge bg-info">Specific Services</span>
                                @if($promotion->services->count() > 0)
                                    <div class="mt-2">
                                        <small class="text-muted">Applicable to:</small>
                                        <ul class="mt-1 mb-0">
                                            @foreach($promotion->services as $service)
                                                <li>{{ $service->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </strong>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Valid Period</small>
                        <strong class="d-block">
                            From: {{ $promotion->start_date ? $promotion->start_date->format('F d, Y') : 'Not set' }}<br>
                            To: {{ $promotion->end_date ? $promotion->end_date->format('F d, Y') : 'Not set' }}
                        </strong>
                        @if($promotion->end_date && $promotion->end_date->isPast())
                            <span class="badge bg-secondary mt-2">Expired</span>
                        @endif
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Created</small>
                        <strong class="d-block">{{ $promotion->created_at->format('F d, Y') }}</strong>
                        <small class="text-muted">{{ $promotion->created_at->diffForHumans() }}</small>
                    </div>

                    <div>
                        <small class="text-muted d-block mb-1">Last Updated</small>
                        <strong class="d-block">{{ $promotion->updated_at->format('F d, Y') }}</strong>
                        <small class="text-muted">{{ $promotion->updated_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Stats & Usage --}}
        <div class="col-lg-8">
            {{-- Statistics --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-arrow-repeat fs-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $times_used }}</h4>
                            <small class="text-muted">Times Used</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-cash-stack fs-4 text-success"></i>
                            </div>
                            <h4 class="fw-bold mb-0">₱{{ number_format($total_discount_given, 2) }}</h4>
                            <small class="text-muted">Total Discount Given</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-calendar-check fs-4 text-info"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $promotion->end_date ? $promotion->end_date->diffInDays(now()) : '∞' }}</h4>
                            <small class="text-muted">Days Remaining</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders using this Promotion --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2 text-primary"></i>
                            Recent Orders with this Promotion
                        </h6>
                        <a href="{{ route('staff.laundries.index', ['promotion_id' => $promotion->id]) }}" class="btn btn-sm btn-outline-secondary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($recent_orders as $order)
                    <div class="p-3 border-bottom {{ $loop->last ? 'border-0' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block mb-1">#{{ $order->tracking_number ?? $order->id }}</strong>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>{{ $order->customer->name ?? 'N/A' }}
                                </small>
                                @if($order->branch)
                                    <span class="mx-1">•</span>
                                    <small class="text-muted">{{ $order->branch->name }}</small>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'warning' : 'primary') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                                <div class="small text-muted mt-1">{{ $order->created_at->format('M d, Y') }}</div>
                                <small class="text-success">Saved: ₱{{ number_format($order->discount_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p class="text-muted mb-0 mt-2">No orders have used this promotion yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
