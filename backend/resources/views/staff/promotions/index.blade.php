@extends('staff.layouts.staff')

@section('title', 'Promotions Management')
@section('page-title', 'Promotions & Discounts')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Promotions & Discounts</h4>
            <p class="text-muted small mb-0">View and manage active promotions for laundry services</p>
        </div>
        <div class="d-flex gap-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactivePromotions" checked>
                <label class="form-check-label small fw-semibold" for="showInactivePromotions">Show Inactive</label>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-megaphone text-primary fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Total Promotions</h6>
                            <h3 class="fw-bold mb-0">{{ $promotions->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-tag text-success fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Active Promotions</h6>
                            <h3 class="fw-bold mb-0">{{ $promotions->where('is_active', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-percent text-info fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Percentage Off</h6>
                            <h3 class="fw-bold mb-0">{{ $promotions->where('discount_type', 'percentage')->where('is_active', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-coin text-warning fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Fixed Amount</h6>
                            <h3 class="fw-bold mb-0">{{ $promotions->where('discount_type', 'fixed')->where('is_active', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Promotions Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-megaphone me-2 text-primary"></i>All Promotions
                    </h6>
                    <p class="text-muted small mb-0">List of all promotions created by admin</p>
                </div>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-light border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control bg-light border-0" id="searchPromotions" placeholder="Search promotions...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($promotions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="promotionsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Promotion Name</th>
                            <th>Description</th>
                            <th>Discount Type</th>
                            <th>Discount Value</th>
                            <th>Application</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Usage</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($promotions as $promotion)
                        <tr class="promotion-row" data-active="{{ $promotion->is_active }}" data-id="{{ $promotion->id }}">
                            <td class="ps-4 fw-bold text-primary">#{{ $promotion->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $promotion->name }}</div>
                                <small class="text-muted">Code: <span class="badge bg-light text-dark border">{{ $promotion->code ?? 'N/A' }}</span></small>
                            </td>
                            <td>
                                @if($promotion->description)
                                    <div class="small text-muted">{{ Str::limit($promotion->description, 60) }}</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $promotion->discount_type === 'percentage' ? 'info' : 'warning' }}">
                                    {{ ucfirst($promotion->discount_type) }}
                                </span>
                            </td>
                            <td class="fw-semibold">
                                @if($promotion->discount_type === 'percentage')
                                    <span class="text-info">{{ $promotion->discount_value }}% OFF</span>
                                @else
                                    <span class="text-warning">₱{{ number_format($promotion->discount_value, 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($promotion->application_type === 'per_load_override')
                                    <span class="badge bg-primary">Per Load Override</span>
                                    <div class="small text-muted mt-1">₱{{ number_format($promotion->display_price, 2) }}/load</div>
                                @elseif($promotion->application_type === 'all_services')
                                    <span class="badge bg-success">All Services</span>
                                @elseif($promotion->application_type === 'specific_services')
                                    <span class="badge bg-info">Specific Services</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    <div><strong>From:</strong> {{ $promotion->start_date ? $promotion->start_date->format('M d, Y') : '—' }}</div>
                                    <div><strong>To:</strong> {{ $promotion->end_date ? $promotion->end_date->format('M d, Y') : '—' }}</div>
                                    @if($promotion->end_date && $promotion->end_date->isPast())
                                        <span class="badge bg-secondary mt-1">Expired</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-{{ $promotion->is_active ? 'success' : 'secondary' }}">
                                        {{ $promotion->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    {{ $promotion->times_used ?? 0 }} uses
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-info view-promotion" title="View Promotion Details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewPromotionModal"
                                            data-promotion-id="{{ $promotion->id }}"
                                            data-promotion-name="{{ $promotion->name }}"
                                            data-promotion-code="{{ $promotion->code ?? 'N/A' }}"
                                            data-promotion-description="{{ $promotion->description }}"
                                            data-promotion-discount-type="{{ $promotion->discount_type }}"
                                            data-promotion-discount-value="{{ $promotion->discount_value }}"
                                            data-promotion-application-type="{{ $promotion->application_type }}"
                                            data-promotion-display-price="{{ $promotion->display_price }}"
                                            data-promotion-start-date="{{ $promotion->start_date ? $promotion->start_date->format('F d, Y') : 'Not set' }}"
                                            data-promotion-end-date="{{ $promotion->end_date ? $promotion->end_date->format('F d, Y') : 'Not set' }}"
                                            data-promotion-is-active="{{ $promotion->is_active }}"
                                            data-promotion-created="{{ $promotion->created_at->format('F d, Y') }}"
                                            data-promotion-usage="{{ $promotion->times_used ?? 0 }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('staff.promotions.show', $promotion) }}" class="btn btn-outline-primary" title="Edit Promotion">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <div class="py-4">
                    <i class="bi bi-megaphone display-1 text-muted opacity-25"></i>
                    <h5 class="text-muted mb-2 mt-3">No promotions yet</h5>
                    <p class="text-muted mb-4">Admin hasn't created any promotions yet. Check back later!</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- View Promotion Modal --}}
<div class="modal fade" id="viewPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye me-2"></i>Promotion Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <!-- Icon/Placeholder -->
                    <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center mb-3"
                        style="width: 100px; height: 100px; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                        <i class="bi bi-megaphone text-white" style="font-size: 3rem;"></i>
                    </div>

                    <!-- Status Badge -->
                    <span id="viewPromotionStatus" class="badge mb-2" style="font-size: 0.9rem; padding: 0.5rem 1rem; background: #198754;">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="viewPromotionStatusText">ACTIVE</span>
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Promotion Name</small>
                            <h5 id="viewPromotionName" class="fw-bold mb-0">-</h5>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Promotion Code</small>
                            <code id="viewPromotionCode" class="fw-semibold">-</code>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Description</small>
                            <p id="viewPromotionDescription" class="mb-0">-</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Discount Type</small>
                            <div id="viewPromotionDiscountType" class="mb-0">-</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Discount Value</small>
                            <strong id="viewPromotionDiscountValue" class="d-block fs-5">-</strong>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Application Type</small>
                            <div id="viewPromotionApplicationType" class="mb-0">-</div>
                            <div id="viewPromotionDisplayPrice" class="small text-muted mt-1" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Valid From</small>
                            <strong id="viewPromotionStartDate" class="d-block">-</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Valid Until</small>
                            <strong id="viewPromotionEndDate" class="d-block">-</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Usage Count</small>
                            <strong id="viewPromotionUsage" class="d-block fs-5">-</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <small class="text-muted d-block mb-1">Created</small>
                            <strong id="viewPromotionCreated" class="d-block">-</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchPromotions');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#promotionsTable tbody tr');

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Show/Hide inactive promotions
    const showInactive = document.getElementById('showInactivePromotions');
    if (showInactive) {
        showInactive.addEventListener('change', function() {
            const promotionRows = document.querySelectorAll('.promotion-row');
            promotionRows.forEach(row => {
                if (!this.checked && row.dataset.active === '0') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        showInactive.dispatchEvent(new Event('change'));
    }

    // View promotion modal handler
    const viewPromotionModal = document.getElementById('viewPromotionModal');
    if (viewPromotionModal) {
        viewPromotionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            // Get data from button
            const name = button.getAttribute('data-promotion-name');
            const code = button.getAttribute('data-promotion-code');
            const description = button.getAttribute('data-promotion-description') || 'No description provided';
            const discountType = button.getAttribute('data-promotion-discount-type');
            const discountValue = button.getAttribute('data-promotion-discount-value');
            const applicationType = button.getAttribute('data-promotion-application-type');
            const displayPrice = button.getAttribute('data-promotion-display-price');
            const startDate = button.getAttribute('data-promotion-start-date');
            const endDate = button.getAttribute('data-promotion-end-date');
            const isActive = button.getAttribute('data-promotion-is-active') === '1';
            const created = button.getAttribute('data-promotion-created');
            const usage = button.getAttribute('data-promotion-usage') || '0';

            // Update modal content
            document.getElementById('viewPromotionName').textContent = name;
            document.getElementById('viewPromotionCode').textContent = code;
            document.getElementById('viewPromotionDescription').textContent = description;

            // Discount type and value
            document.getElementById('viewPromotionDiscountType').innerHTML =
                `<span class="badge bg-${discountType === 'percentage' ? 'info' : 'warning'}">${discountType.charAt(0).toUpperCase() + discountType.slice(1)}</span>`;

            const discountValueElement = document.getElementById('viewPromotionDiscountValue');
            if (discountType === 'percentage') {
                discountValueElement.textContent = `${discountValue}% OFF`;
                discountValueElement.className = 'd-block fs-5 text-info';
            } else {
                discountValueElement.textContent = `₱${parseFloat(discountValue).toFixed(2)} OFF`;
                discountValueElement.className = 'd-block fs-5 text-warning';
            }

            // Application type
            const appTypeElement = document.getElementById('viewPromotionApplicationType');
            const displayPriceElement = document.getElementById('viewPromotionDisplayPrice');

            if (applicationType === 'per_load_override') {
                appTypeElement.innerHTML = '<span class="badge bg-primary">Per Load Override</span>';
                if (displayPrice) {
                    displayPriceElement.style.display = 'block';
                    displayPriceElement.textContent = `Display Price: ₱${parseFloat(displayPrice).toFixed(2)}/load`;
                }
            } else if (applicationType === 'all_services') {
                appTypeElement.innerHTML = '<span class="badge bg-success">All Services</span>';
                displayPriceElement.style.display = 'none';
            } else if (applicationType === 'specific_services') {
                appTypeElement.innerHTML = '<span class="badge bg-info">Specific Services</span>';
                displayPriceElement.style.display = 'none';
            }

            // Dates
            document.getElementById('viewPromotionStartDate').textContent = startDate;
            document.getElementById('viewPromotionEndDate').textContent = endDate;

            // Usage and created
            document.getElementById('viewPromotionUsage').textContent = `${usage} time${usage != 1 ? 's' : ''}`;
            document.getElementById('viewPromotionCreated').textContent = created;

            // Update status
            const statusSpan = document.getElementById('viewPromotionStatus');
            const statusText = document.getElementById('viewPromotionStatusText');

            if (isActive) {
                statusSpan.style.background = '#198754';
                statusText.textContent = 'ACTIVE';
            } else {
                statusSpan.style.background = '#6c757d';
                statusText.textContent = 'INACTIVE';
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Card Hover Effects */
    .card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    /* Table Hover */
    .table-hover tbody tr {
        transition: background-color 0.2s;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    /* Badge Styles */
    .badge {
        font-size: 0.75em;
        padding: 0.4em 0.8em;
        font-weight: 600;
    }

    /* Button Group */
    .btn-group-sm .btn {
        border-radius: 0.375rem !important;
        margin-left: 0.1rem;
        padding: 0.25rem 0.5rem;
    }

    .btn-group-sm .btn i {
        font-size: 0.875rem;
    }

    /* Table Headers */
    .table th {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
    }

    /* Modal */
    .modal-content {
        border-radius: 12px;
    }

    /* Alert Positioning */
    .alert-position-fixed {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Search Input */
    .input-group-text {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .form-control.bg-light:focus {
        background-color: #fff !important;
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>
@endpush
@endsection
