@extends('admin.layouts.app')
@section('page-title', 'Promotion Details')
@section('title', $promotion->name)

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ $promotion->name }}</h2>
            <p class="text-muted small mb-0">
                <span class="badge bg-{{ $promotion->getStatusBadgeClass() }} me-2">
                    {{ $promotion->getStatus() }}
                </span>
                {{ $promotion->getTypeLabel() }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Promotion Details --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2" style="color: #3D3B6B;"></i>
                        Promotion Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        {{-- Promotion Value --}}
                        <div class="col-md-12">
                            <div class="text-center py-4 rounded" style="background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);">
                                <div class="text-white">
                                    <div class="display-3 fw-bold mb-2">
                                        {{ $promotion->formatted_price }}
                                    </div>
                                    <h5 class="mb-0 opacity-75">{{ $promotion->getTypeLabel() }}</h5>
                                </div>
                            </div>
                        </div>

                        {{-- Basic Information --}}
                        <div class="col-md-6">
                            <label class="text-muted small">Promotion Name</label>
                            <p class="fw-bold mb-0">{{ $promotion->name }}</p>
                        </div>

                        @if($promotion->promo_code)
                            <div class="col-md-6">
                                <label class="text-muted small">Promo Code</label>
                                <p class="mb-0">
                                    <code class="fs-6 fw-bold text-primary">{{ $promotion->promo_code }}</code>
                                </p>
                            </div>
                        @endif

                        @if($promotion->description)
                            <div class="col-12">
                                <label class="text-muted small">Description</label>
                                <p class="mb-0">{{ $promotion->description }}</p>
                            </div>
                        @endif

                        {{-- Pricing Details --}}
                        @if($promotion->application_type === 'per_load_override')
                            <div class="col-md-6">
                                <label class="text-muted small">Fixed Price Per Load</label>
                                <p class="fw-bold mb-0 fs-5">₱{{ number_format($promotion->display_price, 2) }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Price Unit</label>
                                <p class="fw-bold mb-0">{{ $promotion->price_unit }}</p>
                            </div>
                        @else
                            @if($promotion->discount_type === 'percentage')
                                <div class="col-md-6">
                                    <label class="text-muted small">Discount Percentage</label>
                                    <p class="fw-bold mb-0 fs-5">{{ $promotion->discount_value }}%</p>
                                </div>
                            @elseif($promotion->discount_type === 'fixed')
                                <div class="col-md-6">
                                    <label class="text-muted small">Discount Amount</label>
                                    <p class="fw-bold mb-0 fs-5">₱{{ number_format($promotion->discount_value, 2) }}</p>
                                </div>
                            @endif

                            @if($promotion->min_amount > 0)
                                <div class="col-md-6">
                                    <label class="text-muted small">Minimum Order Amount</label>
                                    <p class="fw-bold mb-0">₱{{ number_format($promotion->min_amount, 2) }}</p>
                                </div>
                            @endif
                        @endif

                        {{-- Validity Period --}}
                        <div class="col-md-6">
                            <label class="text-muted small">Start Date</label>
                            <p class="fw-bold mb-0">
                                <i class="bi bi-calendar-event text-success me-1"></i>
                                {{ $promotion->start_date->format('F d, Y') }}
                            </p>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">End Date</label>
                            <p class="fw-bold mb-0">
                                <i class="bi bi-calendar-x text-danger me-1"></i>
                                {{ $promotion->end_date->format('F d, Y') }}
                            </p>
                        </div>

                        {{-- Applicability --}}
                        <div class="col-md-6">
                            <label class="text-muted small">Applicable Branch</label>
                            <p class="mb-0">
                                @if($promotion->branch)
                                    <i class="bi bi-shop me-1"></i> {{ $promotion->branch->name }}
                                @else
                                    <i class="bi bi-globe me-1"></i> All Branches
                                @endif
                            </p>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Display Order</label>
                            <p class="mb-0">{{ $promotion->display_order }}</p>
                        </div>

                        {{-- Status Badges --}}
                        <div class="col-12">
                            <label class="text-muted small d-block mb-2">Status</label>
                            <span class="badge bg-{{ $promotion->is_active ? 'success' : 'secondary' }} me-2">
                                <i class="bi bi-{{ $promotion->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                {{ $promotion->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($promotion->featured)
                                <span class="badge bg-warning me-2">
                                    <i class="bi bi-star-fill"></i> Featured
                                </span>
                            @endif
                            @if($promotion->is_maxed_out)
                                <span class="badge bg-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Maximum Usage Reached
                                </span>
                            @endif
                        </div>

                        {{-- Banner Image --}}
                        @if($promotion->banner_image)
                            <div class="col-12">
                                <label class="text-muted small d-block mb-2">Banner Image</label>
                                <img src="{{ Storage::url($promotion->banner_image) }}"
                                     class="img-fluid rounded"
                                     style="max-height: 300px;">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Usage History --}}
            @if($promotion->orders->count() > 0)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2" style="color: #3D3B6B;"></i>
                            Recent Usage
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Discount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($promotion->orders()->latest()->take(10)->get() as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                                    {{ $order->tracking_number }}
                                                </a>
                                            </td>
                                            <td>{{ $order->customer->name }}</td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                            <td>₱{{ number_format($order->total_amount, 2) }}</td>
                                            <td class="text-success">
                                                -₱{{ number_format($order->discount_amount ?? 0, 2) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $order->status == 'completed' ? 'success' : 'primary' }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($promotion->orders->count() > 10)
                        <div class="card-footer bg-light text-center py-2">
                            <small class="text-muted">
                                Showing 10 of {{ $promotion->orders->count() }} orders
                            </small>
                        </div>
                    @endif
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No Usage Yet</h5>
                        <p class="text-muted mb-0">This promotion hasn't been used in any orders yet.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Statistics --}}
        <div class="col-lg-4">
            {{-- Usage Statistics --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header text-white py-3" style="background: #3D3B6B;">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-graph-up me-2"></i>Usage Statistics
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Usage</span>
                            <h3 class="mb-0 fw-bold" style="color: #3D3B6B;">{{ $promotion->usage_count }}</h3>
                        </div>
                        @if($promotion->max_usage)
                            <div class="progress" style="height: 8px;">
                                @php
                                    $percent = min(100, ($promotion->usage_count / $promotion->max_usage) * 100);
                                @endphp
                                <div class="progress-bar"
                                     role="progressbar"
                                     style="width: {{ $percent }}%; background: #3D3B6B;"
                                     aria-valuenow="{{ $percent }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">
                                {{ $promotion->usage_count }} / {{ $promotion->max_usage }} uses
                                @if($promotion->remaining_usage)
                                    ({{ $promotion->remaining_usage }} remaining)
                                @endif
                            </small>
                        @else
                            <small class="text-muted">Unlimited usage</small>
                        @endif
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Created</label>
                        <p class="mb-0 fw-semibold">{{ $promotion->created_at->format('M d, Y') }}</p>
                        <small class="text-muted">{{ $promotion->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Last Updated</label>
                        <p class="mb-0 fw-semibold">{{ $promotion->updated_at->format('M d, Y') }}</p>
                        <small class="text-muted">{{ $promotion->updated_at->diffForHumans() }}</small>
                    </div>

                    @if($promotion->orders->count() > 0)
                        <div class="mb-3">
                            <label class="text-muted small d-block mb-1">Last Used</label>
                            @php
                                $lastOrder = $promotion->orders()->latest()->first();
                            @endphp
                            <p class="mb-0 fw-semibold">{{ $lastOrder->created_at->format('M d, Y') }}</p>
                            <small class="text-muted">{{ $lastOrder->created_at->diffForHumans() }}</small>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">Total Discount Given</label>
                            <p class="mb-0 fw-bold text-success fs-5">
                                ₱{{ number_format($promotion->orders->sum('discount_amount'), 2) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-lightning me-2" style="color: #3D3B6B;"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.promotions.edit', $promotion) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Promotion
                        </a>

                        <form action="{{ route('admin.promotions.toggleStatus', $promotion) }}"
                              method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="btn btn-outline-{{ $promotion->is_active ? 'warning' : 'success' }} w-100">
                                <i class="bi bi-{{ $promotion->is_active ? 'pause' : 'play' }}"></i>
                                {{ $promotion->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        <form action="{{ route('admin.promotions.destroy', $promotion) }}"
                              method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this promotion? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-1"></i> Delete Promotion
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Time Remaining --}}
            @if($promotion->is_active)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-hourglass-split display-4 text-muted mb-3"></i>
                        <h6 class="text-muted mb-2">Time Remaining</h6>
                        @if($promotion->end_date->isFuture())
                            <h4 class="fw-bold mb-0" style="color: #3D3B6B;">
                                {{ $promotion->end_date->diffForHumans(null, true) }}
                            </h4>
                            <small class="text-muted">
                                Ends {{ $promotion->end_date->format('M d, Y') }}
                            </small>
                        @else
                            <h4 class="fw-bold mb-0 text-danger">Expired</h4>
                            <small class="text-muted">
                                Ended {{ $promotion->end_date->format('M d, Y') }}
                            </small>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .progress {
        background-color: rgba(61, 59, 107, 0.1);
    }
</style>
@endpush
@endsection
