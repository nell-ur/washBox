@extends('staff.layouts.staff')

@section('title', 'Add-on Details')
@section('page-title', 'Add-on Details')
@section('page-icon', 'bi-plus-circle')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $addon->name }}</h4>
            <p class="text-muted small mb-0">Add-on Service Details</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.addons.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Add-ons
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column - Add-on Info --}}
        <div class="col-lg-4">
            {{-- Add-on Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    {{-- Icon/Placeholder --}}
                    <div class="mb-3">
                        <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                            style="width: 150px; height: 150px; background: linear-gradient(135deg, {{ $addon->is_active ? '#10B981' : '#6B7280' }} 0%, {{ $addon->is_active ? '#34D399' : '#9CA3AF' }} 100%);">
                            <i class="bi bi-plus-circle text-white" style="font-size: 4rem;"></i>
                        </div>
                    </div>

                    {{-- Name & Slug --}}
                    <h5 class="fw-bold mb-1">{{ $addon->name }}</h5>
                    <p class="text-muted mb-3">Slug: {{ $addon->slug }}</p>

                    {{-- Status Badge --}}
                    <span class="badge mb-3" style="background: {{ $addon->is_active ? '#10B981' : '#6B7280' }}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                        <i class="bi bi-{{ $addon->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $addon->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>

                    {{-- Price Badge --}}
                    <div class="mb-3">
                        <div class="badge bg-light text-dark border fs-5 py-2 px-3">
                            <i class="bi bi-tag me-1 text-success"></i>
                            ₱{{ number_format($addon->price, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Add-on Details --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-success"></i>
                        Add-on Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if($addon->description)
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block mb-1">Description</small>
                            <p class="mb-0">{{ $addon->description }}</p>
                        </div>
                    @endif

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Price</small>
                        <strong class="d-block fs-5 text-success">₱{{ number_format($addon->price, 2) }}</strong>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Created</small>
                        <strong class="d-block">{{ $addon->created_at->format('F d, Y') }}</strong>
                        <small class="text-muted">{{ $addon->created_at->diffForHumans() }}</small>
                    </div>

                    <div>
                        <small class="text-muted d-block mb-1">Last Updated</small>
                        <strong class="d-block">{{ $addon->updated_at->format('F d, Y') }}</strong>
                        <small class="text-muted">{{ $addon->updated_at->diffForHumans() }}</small>
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
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-arrow-repeat fs-4 text-success"></i>
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
                            <h4 class="fw-bold mb-0">₱{{ number_format($total_revenue, 2) }}</h4>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-calendar-check fs-4 text-info"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ number_format($total_revenue / max($times_used, 1), 2) }}</h4>
                            <small class="text-muted">Avg. per Use</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders using this Add-on --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2 text-success"></i>
                            Recent Orders with this Add-on
                        </h6>
                        <a href="{{ route('staff.laundries.index', ['addon_id' => $addon->id]) }}" class="btn btn-sm btn-outline-secondary">
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
                                <small class="fw-semibold text-success">+₱{{ number_format($order->pivot->price_at_purchase ?? 0, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p class="text-muted mb-0 mt-2">No orders have used this add-on yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
