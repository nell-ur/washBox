@extends('staff.layouts.staff')

@section('page-title', 'Customer Profile - ' . $customer->name )
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/customers.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid px-4 py-5">
    
    {{-- Hero Profile Card --}}
    <div class="profile-hero-card mb-4">
        <div class="hero-bg-pattern"></div>
        <div class="row g-4 align-items-center position-relative">
            <div class="col-auto">
                <div class="profile-avatar-lg">
                    <span class="avatar-text">{{ substr($customer->name, 0, 1) }}</span>
                    <div class="avatar-status {{ $customer->is_active ? 'active' : 'inactive' }}"></div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                    <h2 class="fw-800 mb-0">{{ $customer->name }}</h2>
                    <span class="badge-soft {{ $customer->is_active ? 'active' : 'inactive' }}">
                        <i class="bi bi-{{ $customer->is_active ? 'check-circle-fill' : 'x-circle-fill' }} me-1"></i>
                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    @if($customer->email)
                    <div class="contact-chip">
                        <i class="bi bi-envelope-fill"></i>
                        <span>{{ $customer->email }}</span>
                    </div>
                    @endif
                    <div class="contact-chip">
                        <i class="bi bi-telephone-fill"></i>
                        <span>{{ $customer->phone }}</span>
                    </div>
                    @if($customer->preferredBranch)
                    <div class="contact-chip">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $customer->preferredBranch->name }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-auto text-end">
                <div class="reg-badge-lg {{ $customer->registration_type }}">
                    <i class="bi bi-{{ $customer->registration_type == 'walk_in' ? 'person-walking' : 'phone-fill' }}"></i>
                    {{ $customer->registration_type == 'walk_in' ? 'Walk-in' : 'Mobile App' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column - Account Details --}}
        <div class="col-xl-4 col-lg-5">
            <div class="info-card-modern h-100">
                <div class="card-accent-line"></div>
                <div class="card-header-modern">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-wrapper">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-700">Account Information</h5>
                            <small class="text-muted-soft">Personal details and settings</small>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="info-grid-modern">
                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Phone Number</span>
                                <span class="info-value">{{ $customer->phone }}</span>
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Email Address</span>
                                <span class="info-value">{{ $customer->email ?? 'Not provided' }}</span>
                                @if(!$customer->email)
                                    <span class="badge-soft-warning mt-1">Missing</span>
                                @endif
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Preferred Branch</span>
                                <span class="info-value">{{ $customer->preferredBranch->name ?? 'Not Set' }}</span>
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Registration Type</span>
                                <span class="reg-badge-mini {{ $customer->registration_type }}">
                                    {{ $customer->registration_type == 'walk_in' ? 'Walk-in' : 'Mobile' }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-envelope-check"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Email Verification</span>
                                @if($customer->email_verified_at)
                                    <span class="badge-soft-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>Verified
                                    </span>
                                    <small class="text-muted-soft d-block">{{ $customer->email_verified_at->format('M d, Y') }}</small>
                                @else
                                    <span class="badge-soft-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Unverified
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-house-door-fill"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Address</span>
                                <span class="info-value">{{ $customer->address ?? 'No address provided' }}</span>
                            </div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Account Created</span>
                                <span class="info-value">{{ $customer->created_at->format('F d, Y') }}</span>
                                <small class="text-muted-soft">{{ $customer->created_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        @if($customer->updated_at != $customer->created_at)
                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Last Updated</span>
                                <span class="info-value">{{ $customer->updated_at->format('F d, Y') }}</span>
                                <small class="text-muted-soft">{{ $customer->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Stats & Activity --}}
        <div class="col-xl-8 col-lg-7">
            {{-- KPI Cards Row --}}
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <div class="kpi-card-premium total-orders">
                        <div class="kpi-overlay"></div>
                        <div class="kpi-content">
                            <div class="kpi-icon-wrapper">
                                <i class="bi bi-basket3-fill"></i>
                            </div>
                            <div class="kpi-stats">
                                <span class="kpi-label">Total Laundries</span>
                                <span class="kpi-value">{{ $customer->getTotalLaundriesCount() }}</span>
                                <span class="kpi-trend">
                                    <i class="bi bi-check-circle-fill me-1"></i>Orders placed
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="kpi-card-premium total-spent">
                        <div class="kpi-overlay"></div>
                        <div class="kpi-content">
                            <div class="kpi-icon-wrapper">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="kpi-stats">
                                <span class="kpi-label">Lifetime Spent</span>
                                <span class="kpi-value">₱{{ number_format($customer->getTotalSpent(), 0) }}</span>
                                <span class="kpi-trend">
                                    <i class="bi bi-graph-up-arrow me-1"></i>Total revenue
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Secondary Metrics Row --}}
            <div class="row g-4 mb-4">
                <div class="col-sm-4">
                    <div class="metric-card">
                        <div class="metric-icon avg-order">
                            <i class="bi bi-calculator-fill"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Avg Order Value</span>
                            <span class="metric-value">₱{{ number_format($customer->getTotalSpent() / max($customer->getTotalLaundriesCount(), 1), 0) }}</span>
                            <span class="metric-sub">per order</span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="metric-card">
                        <div class="metric-icon member-since">
                            <i class="bi bi-calendar-heart-fill"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Member Since</span>
                            <span class="metric-value">{{ $customer->created_at->format('M Y') }}</span>
                            <span class="metric-sub">{{ $customer->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="metric-card">
                        <div class="metric-icon last-order">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="metric-details">
                            <span class="metric-label">Last Order</span>
                            @php $lastOrder = $customer->laundries->sortByDesc('created_at')->first(); @endphp
                            @if($lastOrder)
                                <span class="metric-value">{{ $lastOrder->created_at->format('M d') }}</span>
                                <span class="metric-sub">{{ $lastOrder->created_at->diffForHumans() }}</span>
                            @else
                                <span class="metric-value">Never</span>
                                <span class="metric-sub">No orders yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders Section with Enhanced Empty State --}}
            <div class="orders-section-card">
                <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon-wrapper">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-700">Recent Laundry Orders</h5>
                            <small class="text-muted-soft">Last 5 transactions</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-modern btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#viewAllOrdersModal">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>View All
                        </button>
                        <form method="POST" action="#" class="d-inline">
                            @csrf
                            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                            <button type="submit" class="btn btn-modern btn-success rounded-pill">
                                <i class="bi bi-filetype-csv me-2"></i>Export
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body-modern p-0">
                    @if($customer->laundries->count() > 0)
                        <div class="table-responsive">
                            <table class="table modern-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Order Details</th>
                                        <th>Tracking</th>
                                        <th>Date & Time</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->laundries->sortByDesc('created_at')->take(5) as $laundry)
                                        <tr class="order-row">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="order-id-badge">#{{ $laundry->id }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="tracking-code-modern">
                                                    <i class="bi bi-upc-scan me-1"></i>
                                                    {{ $laundry->tracking_number }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-time">
                                                    <div class="date">{{ $laundry->created_at->format('M d, Y') }}</div>
                                                    <div class="time">{{ $laundry->created_at->format('h:i A') }}</div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-pill {{ $laundry->status }}">
                                                    <i class="bi bi-{{
                                                        $laundry->status === 'completed' ? 'check-circle-fill' :
                                                        ($laundry->status === 'pending' ? 'hourglass-split' :
                                                        ($laundry->status === 'processing' ? 'gear-fill' :
                                                        ($laundry->status === 'cancelled' ? 'x-circle-fill' : 'info-circle-fill')))
                                                    }} me-1"></i>
                                                    {{ ucfirst($laundry->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="amount-badge">₱{{ number_format($laundry->total_amount, 2) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- Enhanced Empty State --}}
                        <div class="empty-state-enhanced">
                            <div class="empty-state-icon-wrapper">
                                <i class="bi bi-basket3"></i>
                                <i class="bi bi-x-lg empty-state-cross"></i>
                            </div>
                            <h5 class="fw-700 mb-2">No Orders Yet</h5>
                            <p class="text-muted-soft mb-4">This customer hasn't placed any laundry orders</p>
                            <div class="empty-state-actions">
                                <a href="{{ route('staff.laundries.create', ['customer_id' => $customer->id]) }}" class="btn btn-modern btn-primary rounded-pill">
                                    <i class="bi bi-plus-circle me-2"></i>Create New Order
                                </a>
                                <button class="btn btn-modern btn-outline-secondary rounded-pill" onclick="refreshProfile()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                @if($customer->laundries->count() > 0)
                <div class="card-footer-modern text-center py-3">
                    <button type="button" class="btn btn-link text-primary fw-600" data-bs-toggle="modal" data-bs-target="#viewAllOrdersModal">
                        View All {{ $customer->laundries->count() }} Orders <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Enhanced View All Orders Modal --}}
<div class="modal fade" id="viewAllOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content modal-content-premium">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-700">All Laundry Orders</h5>
                        <small class="text-muted-soft">Complete order history for {{ $customer->name }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                @if($customer->laundries->count() > 0)
                    <div class="table-responsive">
                        <table class="table modern-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order #</th>
                                    <th>Tracking</th>
                                    <th>Date & Time</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Payment</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->laundries->sortByDesc('created_at') as $laundry)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="order-id-badge">#{{ $laundry->id }}</span>
                                        </td>
                                        <td>
                                            <div class="tracking-code-modern">
                                                <i class="bi bi-upc-scan me-1"></i>
                                                {{ $laundry->tracking_number }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-time">
                                                <div class="date">{{ $laundry->created_at->format('M d, Y') }}</div>
                                                <div class="time">{{ $laundry->created_at->format('h:i A') }}</div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-pill {{ $laundry->status }}">
                                                <i class="bi bi-{{
                                                    $laundry->status === 'completed' ? 'check-circle-fill' :
                                                    ($laundry->status === 'pending' ? 'hourglass-split' :
                                                    ($laundry->status === 'processing' ? 'gear-fill' :
                                                    ($laundry->status === 'cancelled' ? 'x-circle-fill' : 'info-circle-fill')))
                                                }} me-1"></i>
                                                {{ ucfirst($laundry->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="payment-pill {{ $laundry->payment_status }}">
                                                <i class="bi bi-{{ $laundry->payment_status === 'paid' ? 'check-circle-fill' : 'hourglass-split' }} me-1"></i>
                                                {{ ucfirst($laundry->payment_status) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <span class="amount-badge-lg">₱{{ number_format($laundry->total_amount, 2) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>Close
                </button>
                <form method="POST" action="#" class="d-inline">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <button type="submit" class="btn btn-modern btn-success rounded-pill">
                        <i class="bi bi-filetype-csv me-2"></i>Export All Orders
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshProfile() {
    const btn = document.getElementById('refresh-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Refreshing...';
    setTimeout(() => location.reload(), 500);
}

window.refreshProfile = refreshProfile;

// Add animation on scroll
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.kpi-card-premium, .metric-card, .info-item-modern');
    cards.forEach((card, index) => {
        card.style.animation = `fadeInUp 0.5s ease forwards ${index * 0.1}s`;
    });
});
</script>
@endpush