@extends('admin.layouts.app')

@section('page-title', 'Branch Details')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.branches.index') }}" class="me-3 text-dark">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0">{{ $branch->name }}</h4>
                <div class="text-muted">
                    <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }} me-2">
                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="badge bg-light text-dark">{{ $branch->code }}</span>
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit Branch
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column - Details -->
        <div class="col-lg-8">
            <!-- Quick Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-cart-check text-primary fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Laundries</div>
                                    <h4 class="fw-bold mb-0">{{ number_format($stats['total_laundries']) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-currency-dollar text-success fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Revenue</div>
                                    <h4 class="fw-bold mb-0">₱{{ number_format($stats['total_revenue'], 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-people text-info fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Active Staff</div>
                                    <h4 class="fw-bold mb-0">{{ $stats['active_staff'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-building me-2"></i>Branch Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Branch Name</label>
                            <div class="fw-semibold">{{ $branch->name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Branch Code</label>
                            <div class="fw-semibold">{{ $branch->code }}</div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Complete Address</label>
                            <div class="fw-semibold">
                                {{ $branch->address }}
                                @if($branch->barangay)
                                    , {{ $branch->barangay }}
                                @endif
                            </div>
                            <div class="text-muted">
                                {{ $branch->city }},
                                @if($branch->zip_code)
                                    {{ $branch->zip_code }}
                                @endif
                                {{ $branch->province }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone</label>
                            <div class="fw-semibold">
                                <i class="bi bi-telephone me-2"></i>{{ $branch->phone }}
                            </div>
                        </div>
                        @if($branch->email)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <div class="fw-semibold">
                                <i class="bi bi-envelope me-2"></i>{{ $branch->email }}
                            </div>
                        </div>
                        @endif
                        @if($branch->manager || $branch->manager_name)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Branch Manager</label>
                            <div class="fw-semibold">
                                <i class="bi bi-person-badge me-2"></i>{{ $branch->manager ?? $branch->manager_name }}
                            </div>
                        </div>
                        @endif
                        @if($branch->operating_hours)
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Operating Hours</label>
                            <div class="fw-semibold">
                                <i class="bi bi-clock me-2"></i>
                                @if(is_array($branch->operating_hours))
                                    {{ $branch->operating_hours_text ?? 'Not set' }}
                                @else
                                    {{ $branch->operating_hours }}
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Location Map - Using Leaflet (matching create form) -->
            @if($branch->latitude && $branch->longitude)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Branch Location</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Map Container -->
                    <div id="branch-location-map" style="height: 350px; width: 100%;"></div>

                    <!-- Location Details -->
                    <div class="p-3 bg-light border-top">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2 mb-md-0">
                                    <i class="bi bi-geo-alt-fill text-primary me-2 fs-5"></i>
                                    <div>
                                        <strong>Coordinates:</strong>
                                        <span class="text-muted">{{ $branch->latitude }}, {{ $branch->longitude }}</span>
                                    </div>
                                </div>
                                @if($branch->plus_code)
                                <div class="d-flex align-items-center small text-muted mt-1">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>Plus Code: <code>{{ $branch->plus_code }}</code></span>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $branch->latitude }},{{ $branch->longitude }}"
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Open in Google Maps
                                </a>
                                <a href="https://www.openstreetmap.org/?mlat={{ $branch->latitude }}&mlon={{ $branch->longitude }}#map=17/{{ $branch->latitude }}/{{ $branch->longitude }}"
                                   target="_blank" class="btn btn-sm btn-outline-secondary mt-2 mt-md-0 ms-md-2">
                                    <i class="bi bi-map me-1"></i>OpenStreetMap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Laundries -->
            @if($recent_laundries->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Recent Laundries</h5>
                    <span class="badge bg-light text-dark">Last 10</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Laundry ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_laundries as $laundry)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.laundries.show', $laundry) }}" class="text-decoration-none">
                                            #{{ $laundry->id }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($laundry->customer)
                                            {{ $laundry->customer->name }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($laundry->service)
                                            {{ $laundry->service->name }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">₱{{ number_format($laundry->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $laundry->status_color ?? 'secondary' }}">
                                            {{ ucfirst($laundry->status) }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $laundry->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No laundries yet for this branch</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column - Stats & Actions -->
        <div class="col-lg-4">
            <!-- Monthly Performance -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calendar-month me-2"></i>This Month</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Laundries</span>
                            <span class="fw-bold">{{ number_format($stats['laundries_mtd']) }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: {{ min(($stats['laundries_mtd'] / max($stats['total_laundries'], 1)) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Revenue</span>
                            <span class="fw-bold text-success">₱{{ number_format($stats['revenue_mtd'], 2) }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ min(($stats['revenue_mtd'] / max($stats['total_revenue'], 1)) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Avg Laundry Value</span>
                            <span class="fw-bold">₱{{ number_format($stats['avg_laundry_value'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Staff</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Staff</span>
                        <span class="fw-bold">{{ $stats['staff_count'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Active</span>
                        <span class="fw-bold text-success">{{ $stats['active_staff'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Inactive</span>
                        <span class="fw-bold text-secondary">{{ $stats['staff_count'] - $stats['active_staff'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Branch
                        </a>
                        <a href="{{ route('admin.laundries.create') }}?branch={{ $branch->id }}" class="btn btn-outline-success">
                            <i class="bi bi-plus-circle me-2"></i>New Laundry
                        </a>
                        @if($branch->is_active)
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                            <i class="bi bi-power me-2"></i>Deactivate
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#activateModal">
                            <i class="bi bi-power me-2"></i>Activate
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>History</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created</small>
                        <div class="fw-semibold small">{{ $branch->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Last Updated</small>
                        <div class="fw-semibold small">{{ $branch->updated_at->format('M d, Y h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivation Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deactivate Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    This will prevent new laundries and restrict branch access.
                </div>
                <p>Are you sure you want to deactivate <strong>{{ $branch->name }}</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.deactivate', $branch) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activation Modal -->
<div class="modal fade" id="activateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activate Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to activate <strong>{{ $branch->name }}</strong>?</p>
                <p class="text-muted small">This will allow new laundries and restore branch access.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.activate', $branch) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        padding: 1rem 1.25rem;
    }
    .progress {
        border-radius: 10px;
        background-color: #e9ecef;
    }
    .progress-bar {
        border-radius: 10px;
    }
    #branch-location-map {
        cursor: default;
        z-index: 1;
    }
    code {
        background: #f3f4f6;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #3D3B6B;
    }
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($branch->latitude && $branch->longitude)
    // Initialize map
    const map = L.map('branch-location-map').setView([
        {{ $branch->latitude }},
        {{ $branch->longitude }}
    ], 17);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Add marker
    const marker = L.marker([
        {{ $branch->latitude }},
        {{ $branch->longitude }}
    ]).addTo(map);

    // Build address for popup
    const addressParts = [
        '{{ $branch->address }}',
        @if($branch->barangay) '{{ $branch->barangay }}', @endif
        '{{ $branch->city }}',
        @if($branch->zip_code) '{{ $branch->zip_code }}', @endif
        '{{ $branch->province }}'
    ].filter(Boolean);

    // Add popup with branch info
    marker.bindPopup(`
        <div style="min-width: 200px;">
            <strong style="font-size: 1.1rem; display: block; margin-bottom: 5px;">
                🧺 {{ $branch->name }}
            </strong>
            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">
                <i class="bi bi-geo-alt"></i> ${addressParts.join(', ')}
            </div>
            <div style="color: #666; font-size: 0.9rem;">
                <i class="bi bi-telephone"></i> {{ $branch->phone }}
            </div>
            @if($branch->operating_hours)
            <div style="color: #666; font-size: 0.85rem; margin-top: 5px; padding-top: 5px; border-top: 1px solid #eee;">
                <i class="bi bi-clock"></i> {{ $branch->operating_hours }}
            </div>
            @endif
        </div>
    `).openPopup();

    // Fix map rendering
    setTimeout(() => map.invalidateSize(), 300);
    @endif
});
</script>
@endpush
