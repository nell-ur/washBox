@extends('staff.layouts.staff')

@section('title', 'Add-ons Management')
@section('page-title', 'Laundry Add-ons')
@section('page-icon', 'bi-plus-circle')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Laundry Add-ons</h4>
            <p class="text-muted small mb-0">View all available add-on services</p>
        </div>
        <div class="d-flex gap-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactiveAddons" checked>
                <label class="form-check-label small fw-semibold" for="showInactiveAddons">Show Inactive</label>
            </div>
            <div class="input-group" style="max-width: 250px;">
                <span class="input-group-text bg-light border-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control bg-light border-0" id="searchAddons" placeholder="Search add-ons...">
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-plus-circle text-success fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Total Add-ons</h6>
                            <h3 class="fw-bold mb-0">{{ $addons->count() }}</h3>
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
                            <i class="bi bi-check-circle text-success fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Active Add-ons</h6>
                            <h3 class="fw-bold mb-0">{{ $addons->where('is_active', true)->count() }}</h3>
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
                            <i class="bi bi-tag text-info fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Price Range</h6>
                            <h3 class="fw-bold mb-0">
                                ₱{{ number_format($addons->min('price'), 0) }} - ₱{{ number_format($addons->max('price'), 0) }}
                            </h3>
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
                            <i class="bi bi-arrow-repeat text-warning fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Total Uses</h6>
                            <h3 class="fw-bold mb-0">{{ $addons->sum('times_used') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add-ons Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-plus-circle me-2 text-success"></i>All Add-ons
            </h6>
        </div>
        <div class="card-body p-0">
            @if($addons->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="addonsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Add-on Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Usage Count</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($addons as $addon)
                        <tr class="addon-row" data-active="{{ $addon->is_active }}" data-id="{{ $addon->id }}">
                            <td class="ps-4 fw-bold text-success">#{{ $addon->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $addon->name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $addon->slug }}
                                </span>
                            </td>
                            <td>
                                @if($addon->description)
                                    <div class="small text-muted">{{ Str::limit($addon->description, 60) }}</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold text-success">
                                ₱{{ number_format($addon->price, 2) }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $addon->is_active ? 'success' : 'secondary' }}">
                                    {{ $addon->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $usageCount = $addon->times_used ?? 0;
                                @endphp
                                <span class="badge bg-{{ $usageCount > 0 ? 'info' : 'light' }} text-{{ $usageCount > 0 ? 'white' : 'dark' }} border">
                                    <i class="bi bi-{{ $usageCount > 0 ? 'check-circle-fill' : 'dash-circle' }} me-1"></i>
                                    {{ $usageCount }} {{ Str::plural('use', $usageCount) }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('staff.addons.show', $addon) }}" class="btn btn-outline-info" title="View Details">
                                        <i class="bi bi-eye"></i>
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
                    <i class="bi bi-plus-circle display-1 text-muted opacity-25"></i>
                    <h5 class="text-muted mb-2 mt-3">No add-ons available</h5>
                    <p class="text-muted mb-0">Add-ons will appear here once created by admin.</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchAddons');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#addonsTable tbody tr');

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

    // Show/Hide inactive addons
    const showInactive = document.getElementById('showInactiveAddons');
    if (showInactive) {
        showInactive.addEventListener('change', function() {
            const addonRows = document.querySelectorAll('.addon-row');
            addonRows.forEach(row => {
                if (!this.checked && row.dataset.active === '0') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        showInactive.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection
