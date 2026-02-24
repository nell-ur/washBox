@extends('admin.layouts.app')

@section('title', 'Service Details')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ $service->name }}</h2>
            <p class="text-muted small mb-0">
                @php
                    $catLabels = ['drop_off' => 'Drop Off', 'self_service' => 'Self Service', 'addon' => 'Add-on'];
                    $catColors = ['drop_off' => '#3D3B6B',  'self_service' => '#10B981',      'addon' => '#F59E0B'];
                    $cat       = $service->category ?? 'drop_off';
                @endphp
                <span class="badge me-2" style="background:{{ $catColors[$cat] ?? '#6B7280' }};">
                    {{ $catLabels[$cat] ?? ucfirst($cat) }}
                </span>
                @if($service->serviceType)
                    <span class="badge bg-info me-2">{{ $service->serviceType->name }}</span>
                @else
                    {{ $service->service_type ? ucfirst(str_replace('_', ' ', $service->service_type)) : 'Laundry Service' }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
                <i class="bi bi-pencil me-2"></i>Edit Service
            </a>
            <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Services
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column - Service Info --}}
        <div class="col-lg-4">
            {{-- Service Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    {{-- Icon --}}
                    <div class="mb-3">
                        <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                            style="width: 150px; height: 150px; background: linear-gradient(135deg, {{ $service->is_active ? '#3D3B6B' : '#6B7280' }} 0%, {{ $service->is_active ? '#6366F1' : '#9CA3AF' }} 100%);">
                            @if($service->icon_path)
                                <img src="{{ asset('storage/' . $service->icon_path) }}"
                                    class="w-100 h-100 rounded-3" style="object-fit: contain; background: white; padding: 10px;">
                            @else
                                <i class="bi bi-droplet text-white" style="font-size: 4rem;"></i>
                            @endif
                        </div>
                    </div>

                    {{-- Name & Type --}}
                    <h5 class="fw-bold mb-1">{{ $service->name }}</h5>
                    @if($service->serviceType || $service->service_type)
                        <p class="text-muted mb-3">
                            @php
                                $catLabels = ['drop_off' => '🛍 Drop Off', 'self_service' => '🧺 Self Service', 'addon' => '➕ Add-on'];
                            @endphp
                            <span class="fw-semibold">
                                {{ $catLabels[$service->category ?? 'drop_off'] ?? ucfirst($service->category ?? 'Drop Off') }}
                            </span>
                            <span class="text-muted mx-1">·</span>
                            @if($service->serviceType)
                                <span class="fw-semibold" style="color: #3D3B6B;">{{ $service->serviceType->name }}</span>
                            @else
                                {{ ucfirst(str_replace('_', ' ', $service->service_type)) }}
                            @endif
                        </p>
                    @endif

                    {{-- Status Badge --}}
                    <span class="badge mb-3" style="background: {{ $service->is_active ? '#10B981' : '#6B7280' }}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                        <i class="bi bi-{{ $service->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $service->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>

                    {{-- Price Badge --}}
                    <div class="mb-3">
                        <div class="badge bg-light text-dark border fs-5 py-2 px-3">
                            <i class="bi bi-tag me-1" style="color: #3D3B6B;"></i>
    @php
                                $pt        = $service->pricing_type ?? 'per_load';
                                $showPrice = $pt === 'per_piece'
                                    ? ($service->price_per_piece ?? 0)
                                    : ($service->price_per_load  ?? 0);
                                $showUnit  = $pt === 'per_piece' ? 'piece' : 'load';
                            @endphp
                            ₱{{ number_format($showPrice, 2) }}/{{ $showUnit }}
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('admin.services.toggle-status', $service) }}" method="POST" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-outline-{{ $service->is_active ? 'warning' : 'success' }} btn-sm w-100">
                                <i class="bi bi-{{ $service->is_active ? 'pause' : 'play' }}-circle"></i>
                                {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Service Details --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2" style="color: #3D3B6B;"></i>
                        Service Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Description</small>
                        <strong class="d-block">{{ $service->description ?: 'No description provided' }}</strong>
                    </div>

                    @if($service->serviceType)
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Service Type</small>
                        <strong class="d-block">{{ $service->serviceType->name }}</strong>
                        @if($service->serviceType->description)
                            <small class="text-muted">{{ $service->serviceType->description }}</small>
                        @endif
                    </div>
                    @endif

                    @if($service->turnaround_time)
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Turnaround Time</small>
                        <strong class="d-block">{{ $service->turnaround_time }} hours</strong>
                    </div>
                    @endif

                    @if($service->min_weight || $service->max_weight)
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Weight Limits</small>
                        <strong class="d-block">
                            @if($service->min_weight && $service->max_weight)
                                {{ number_format($service->min_weight, 1) }}kg - {{ number_format($service->max_weight, 1) }}kg
                            @elseif($service->min_weight)
                                Min: {{ number_format($service->min_weight, 1) }}kg
                            @elseif($service->max_weight)
                                Max: {{ number_format($service->max_weight, 1) }}kg
                            @endif
                        </strong>
                    </div>
                    @endif

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Created</small>
                        <strong class="d-block">{{ $service->created_at->format('F d, Y') }}</strong>
                    </div>

                    <div>
                        <small class="text-muted d-block mb-1">Last Updated</small>
                        <strong class="d-block">{{ $service->updated_at->diffForHumans() }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Stats & Orders --}}
        <div class="col-lg-8">
            {{-- Statistics --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $stats['total_laundries'] ?? 0 }}</h4>
                            <small class="text-muted">Total Laundries</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-check-circle fs-4 text-success"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $stats['completed_laundries'] ?? 0 }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-cash-stack fs-4 text-info"></i>
                            </div>
                            <h4 class="fw-bold mb-0">₱{{ number_format($stats['total_revenue'] ?? 0, 0) }}</h4>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-warning bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-speedometer fs-4 text-warning"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['total_weight'] ?? 0, 1) }}</h4>
                            <small class="text-muted">Total Kg</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Summary --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2" style="color: #3D3B6B;"></i>
                        Performance Summary
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Completion Rate</span>
                                <strong>{{ ($stats['total_laundries'] ?? 0) > 0 ? round((($stats['completed_laundries'] ?? 0)/($stats['total_laundries'] ?? 1))*100) : 0 }}%</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ ($stats['total_laundries'] ?? 0) > 0 ? (($stats['completed_laundries'] ?? 0)/($stats['total_laundries'] ?? 1))*100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Average Order Value</span>
                                <strong>₱{{ number_format($stats['avg_order_value'] ?? 0, 2) }}</strong>
                            </div>
                            <small class="text-muted">Per completed order</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2" style="color: #3D3B6B;"></i>
                            Recent Orders
                        </h6>
                        <a href="{{ route('admin.laundries.index', ['service_id' => $service->id]) }}" class="btn btn-sm btn-outline-secondary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse(($recent_laundries ?? []) as $order)
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
                                <strong class="d-block" style="color: #3D3B6B;">
                                    {{ number_format($order->weight, 1) }}kg • ₱{{ number_format($order->total_amount, 2) }}
                                </strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p class="text-muted mb-0 mt-2">No orders yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection