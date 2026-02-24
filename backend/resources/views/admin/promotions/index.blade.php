@extends('admin.layouts.app')

@section('page-title', 'Promotions Management')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small">Manage discounts and banners for Sibulan, Dumaguete, and Bais branches.</p>
        </div>

        {{-- Enhanced Create Button with Dropdown --}}
        <div class="dropdown">
            <button class="btn btn-primary px-4 shadow-sm dropdown-toggle"
                style="background: #3D3B6B; border: none;"
                type="button"
                data-bs-toggle="dropdown">
                <i class="bi bi-plus-lg me-2"></i>Create Promotion
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                <li>
                    <a class="dropdown-item py-2" href="{{ route('admin.promotions.create', ['mode' => 'poster']) }}">
                        <i class="bi bi-image text-primary me-2"></i>
                        <strong>Poster Promotion</strong>
                        <small class="d-block text-muted ms-4">Visual poster with pricing (₱179, ₱209, etc.)</small>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('admin.promotions.create', ['mode' => 'simple']) }}">
                        <i class="bi bi-percent text-success me-2"></i>
                        <strong>Simple Discount</strong>
                        <small class="d-block text-muted ms-4">Percentage or fixed discount (20% OFF, etc.)</small>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-success border-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                        <i class="bi bi-check-circle fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $stats['active'] }}</h3>
                        <small class="text-muted fw-semibold">Active Now</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-primary border-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                        <i class="bi bi-calendar-event fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $stats['scheduled'] }}</h3>
                        <small class="text-muted fw-semibold">Scheduled</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-warning border-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $stats['total_usage'] }}</h3>
                        <small class="text-muted fw-semibold">Total Redemptions</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger p-3 rounded-3 me-3">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $stats['expired'] }}</h3>
                        <small class="text-muted fw-semibold">Expired</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name or code..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Promotion Types</option>
                        <option value="percentage_discount" {{ request('type') === 'percentage_discount' ? 'selected' : '' }}>Percentage Discount</option>
                        <option value="fixed_discount" {{ request('type') === 'fixed_discount' ? 'selected' : '' }}>Fixed Amount</option>
                        <option value="poster_promo" {{ request('type') === 'poster_promo' ? 'selected' : '' }}>Poster Promotion</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-sm btn-light border text-secondary w-100">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 300px;">Campaign Details</th>
                        <th>Branch Scope</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promotions as $promo)
<tr>
    <td class="ps-4">
        <div class="d-flex align-items-center">
            {{-- Enhanced Image Display for Poster Promotions --}}
            @php
                // Fix: Check if it's a poster promotion using type and display_price
                $isPosterPromo = $promo->type === 'poster_promo' && $promo->display_price;
                // Fix: Get color gradient or use default
                $colorGradient = $promo->color_theme == 'blue'
                    ? 'linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%)'
                    : ($promo->color_theme == 'purple'
                        ? 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)'
                        : ($promo->color_theme == 'green'
                            ? 'linear-gradient(135deg, #10B981 0%, #059669 100%)'
                            : 'linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%)'));
            @endphp

            @if($isPosterPromo)
                {{-- Show poster preview --}}
                <div class="rounded me-3 border d-flex align-items-center justify-content-center text-white position-relative overflow-hidden"
                    style="width: 55px; height: 35px; background: {{ $colorGradient }}; font-size: 0.5rem;">
                    <div class="text-center">
                        <div class="fw-bold">{{ $promo->display_price ? '₱' . number_format($promo->display_price, 0) : '' }}</div>
                    </div>
                    {{-- Poster badge --}}
                    <span class="position-absolute top-0 start-0 badge bg-dark" style="font-size: 0.45rem; padding: 1px 3px;">
                        <i class="bi bi-image"></i>
                    </span>
                </div>
            @elseif($promo->banner_image)
                <img src="{{ asset('storage/' . $promo->banner_image) }}" alt="Promo Banner"
                     class="rounded me-3" style="width: 55px; height: 35px; object-fit: cover;">
            @else
                <div class="rounded me-3 bg-light d-flex align-items-center justify-content-center"
                     style="width: 55px; height: 35px;">
                    <i class="bi bi-tag text-muted"></i>
                </div>
            @endif

            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="fw-bold text-dark">{{ $promo->name }}</div>
                    {{-- Type Badge --}}
                    @if($isPosterPromo)
                        <span class="badge badge-sm bg-primary bg-opacity-10 text-primary" style="font-size: 0.65rem;">
                            <i class="bi bi-image"></i> Poster
                        </span>
                    @endif
                </div>
                <div class="text-muted x-small font-monospace">
                    {{ $promo->promo_code ?? 'Internal Auto-Apply' }}
                    @if($promo->featured) <span class="text-warning ms-1"><i class="bi bi-star-fill"></i></span> @endif
                </div>
            </div>
        </div>
    </td>
    <td>
        @if($promo->branch)
            <span class="badge bg-light text-dark border">{{ $promo->branch->name }}</span>
        @else
            <span class="badge bg-dark text-white">Network Wide</span>
        @endif
    </td>
    <td>
        {{-- Enhanced Display for Poster Promotions --}}
        @if($isPosterPromo)
            <div class="fw-bold text-primary">₱{{ number_format($promo->display_price, 0) }}</div>
            <div class="x-small text-muted">{{ $promo->price_unit }}</div>
        @else
            <div class="fw-bold text-primary">
                @if($promo->discount_type === 'percentage')
                    {{ $promo->discount_value }}% OFF
                @elseif($promo->discount_type === 'fixed')
                    ₱{{ number_format($promo->discount_value, 0) }} OFF
                @else
                    {{ $promo->formatted_price ?? $promo->type }}
                @endif
            </div>
            <div class="x-small text-muted">{{ $promo->start_date->format('M d') }} - {{ $promo->end_date->format('M d, Y') }}</div>
        @endif
    </td>
    <td>
        <div class="small fw-bold">{{ $promo->usage_count }} <span class="text-muted fw-normal">/ {{ $promo->max_usage ?? '∞' }}</span></div>
        <div class="progress mt-1" style="height: 4px; width: 80px;">
            @php $usePercent = $promo->max_usage ? ($promo->usage_count / $promo->max_usage) * 100 : 0; @endphp
            <div class="progress-bar bg-info" style="width: {{ $usePercent }}%"></div>
        </div>
    </td>
    <td>
        @php
            // Fix: Calculate status directly
            $status = 'Active';
            if (!$promo->is_active) {
                $status = 'Inactive';
            } elseif (now() < $promo->start_date) {
                $status = 'Scheduled';
            } elseif (now() > $promo->end_date) {
                $status = 'Expired';
            } elseif ($promo->max_usage && $promo->usage_count >= $promo->max_usage) {
                $status = 'Maxed Out';
            }

            $statusBadge = match($status) {
                'Active' => 'bg-success',
                'Scheduled' => 'bg-info',
                'Expired' => 'bg-secondary',
                'Maxed Out' => 'bg-warning',
                'Inactive' => 'bg-danger',
                default => 'bg-dark'
            };
        @endphp
        <span class="badge {{ $statusBadge }} rounded-pill px-3">{{ $status }}</span>
    </td>
    <td class="text-end pe-4">
        <div class="btn-group shadow-sm rounded-3 overflow-hidden">
            <a href="{{ route('admin.promotions.show', $promo) }}" class="btn btn-white btn-sm border-end" title="View Report">
                <i class="bi bi-bar-chart text-primary"></i>
            </a>
            <a href="{{ route('admin.promotions.edit', $promo) }}" class="btn btn-white btn-sm border-end" title="Edit">
                <i class="bi bi-pencil-square text-secondary"></i>
            </a>
            <form action="{{ route('admin.promotions.toggleStatus', $promo) }}" method="POST" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-white btn-sm border-end" title="Toggle Status">
                    <i class="bi {{ $promo->is_active ? 'bi-pause-circle text-warning' : 'bi-play-circle text-success' }}"></i>
                </button>
            </form>
            <form action="{{ route('admin.promotions.destroy', $promo) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this promotion?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-white btn-sm" title="Delete">
                    <i class="bi bi-trash3 text-danger"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-megaphone display-4 opacity-25"></i></div>
                            <h6 class="fw-bold">No Promotions Found</h6>
                            <p class="small text-muted mb-3">Try adjusting your filters or create a new campaign.</p>
                            <div class="btn-group">
                                <a href="{{ route('admin.promotions.create', ['mode' => 'poster']) }}"
                                    class="btn btn-sm btn-primary" style="background: #3D3B6B; border: none;">
                                    <i class="bi bi-image"></i> Poster Promotion
                                </a>
                                <a href="{{ route('admin.promotions.create', ['mode' => 'simple']) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-percent"></i> Simple Discount
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promotions->hasPages())
            <div class="card-footer bg-white py-3 border-top-0">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .btn-white { background: #fff; }
    .btn-white:hover { background: #f8f9fa; }
    .table-hover tbody tr:hover { background-color: rgba(61, 59, 107, 0.02); }
    .stats-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
    .badge-sm { padding: 0.25rem 0.5rem; }

    /* Dropdown Menu Styling */
    .dropdown-menu {
        min-width: 300px;
        padding: 0.5rem 0;
    }

    .dropdown-item:hover {
        background-color: rgba(61, 59, 107, 0.05);
    }
</style>
@endsection
