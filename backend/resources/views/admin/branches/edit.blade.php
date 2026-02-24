@extends('admin.layouts.app')


@section('page-title', 'Edit Branch')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.branches.index') }}" class="me-2 text-dark"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-bold mb-0">Branch Configuration</h5>
        <span class="ms-auto badge bg-light text-dark px-3 py-2 rounded-pill">BRANCH CODE: {{ $branch->code }}</span>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.branches.update', $branch->id) }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-2"><i class="bi bi-building me-2"></i>General Information</h5>
                    <p class="text-muted mb-4">Primary identification and location details.</p>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $branch->code) }}" required maxlength="10">
                            <small class="text-muted">Unique code (e.g., SBL, DGT, BAI)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" required>{{ old('address', $branch->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $branch->city) }}" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province', $branch->province) }}" required>
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $branch->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $branch->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Manager</label>
                            <input type="text" class="form-control @error('manager') is-invalid @enderror" name="manager" value="{{ old('manager', $branch->manager) }}">
                            @error('manager')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Operating Status</label>
                            <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                                <option value="1" {{ old('is_active', $branch->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', $branch->is_active) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Branch Location</h6>
                            <small class="text-muted">Click "Locate Address" or click on the map to update the branch pin</small>
                        </div>
                        <div class="card-body p-0">
                            {{-- Search bar --}}
                            <div class="p-3 border-bottom bg-light">
                                <div class="input-group">
                                    <input type="text" id="map-search-input" class="form-control"
                                           placeholder="Search location... e.g. Robinsons Dumaguete">
                                    <button type="button" class="btn btn-primary" id="btn-search-map">
                                        <i class="bi bi-search me-1"></i> Search
                                    </button>
                                    <button type="button" class="btn btn-success" id="btn-locate-address">
                                        <i class="bi bi-crosshair me-1"></i> Locate Address
                                    </button>
                                </div>
                                <div id="geocode-status" class="small mt-2" style="display:none;"></div>
                            </div>

                            {{-- Map --}}
                            <div id="branch-map" style="height: 350px; width: 100%;"></div>

                            {{-- Coordinate display --}}
                            <div class="p-3 bg-white border-top">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2" id="coord-display">
                                            <i class="bi bi-pin-map me-1"></i>
                                            @if($branch->latitude && $branch->longitude)
                                                {{ $branch->latitude }}, {{ $branch->longitude }}
                                            @else
                                                No location set
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-location"
                                                style="{{ ($branch->latitude && $branch->longitude) ? '' : 'display:none;' }}">
                                            <i class="bi bi-x-circle me-1"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden inputs --}}
                            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $branch->latitude) }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $branch->longitude) }}">
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2"></i>Operating Hours</h6>
                            <small class="text-muted">Simple text format for branch hours (optional)</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Operating Hours</label>
                                <input type="text" class="form-control @error('operating_hours') is-invalid @enderror"
                                       name="operating_hours" value="{{ old('operating_hours', $branch->operating_hours) }}"
                                       placeholder="e.g., Monday-Friday: 8:00 AM - 6:00 PM, Saturday-Sunday: 9:00 AM - 5:00 PM">
                                <small class="text-muted">
                                    Examples:<br>
                                    • "Monday-Friday: 8:00 AM - 6:00 PM, Saturday-Sunday: 9:00 AM - 5:00 PM"<br>
                                    • "Daily: 8:00 AM - 8:00 PM"<br>
                                    • "Weekdays: 7:00 AM - 7:00 PM (Closed on Sundays)"
                                </small>
                                @error('operating_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Branch Insights</h6>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Laundries (MTD)</span>
                            <span class="fw-bold">{{ $branch->laundries_mtd }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Revenue (MTD)</span>
                            <span class="fw-bold text-success">₱{{ number_format($branch->revenue_mtd, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Active Staff</span>
                            <span class="fw-bold">{{ $branch->active_staff }} Members</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Historical Data</h6>
                    <div class="mb-2">
                        <span class="text-muted">Created At</span>
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($branch->created_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Last Updated</span>
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($branch->updated_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="alert alert-warning mt-3 py-2 px-3" style="font-size: 0.95rem;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Changing branch code may affect tracking and reporting. Use caution.
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-light border">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>

                    @if($branch->is_active)
                    <button type="button" class="btn btn-outline-danger mt-3" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                        <i class="bi bi-power me-2"></i>Deactivate Branch
                    </button>
                    @else
                    <button type="button" class="btn btn-outline-success mt-3" data-bs-toggle="modal" data-bs-target="#activateModal">
                        <i class="bi bi-power me-2"></i>Activate Branch
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Deactivation Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> Deactivating this branch will prevent new laundries and restrict access for branch users.
                </div>
                <p>Are you sure you want to deactivate <strong>{{ $branch->name }}</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.deactivate', $branch->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger">Deactivate Branch</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activation Modal -->
<div class="modal fade" id="activateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Activation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to activate <strong>{{ $branch->name }}</strong>? This will allow new orders and restore access for branch users.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.activate', $branch->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate Branch</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
    }
    .text-muted {
        font-size: 0.85rem;
    }
    .card-header {
        padding: 0.75rem 1.25rem;
    }
    #branch-map {
        cursor: crosshair;
        z-index: 1;
    }
    .geocode-success { color: #10B981; }
    .geocode-error   { color: #EF4444; }
    .geocode-loading { color: #6366F1; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ═══════════════════════════════════════
    // INTERACTIVE MAP PICKER
    // ═══════════════════════════════════════
    const latInput     = document.getElementById('latitude');
    const lngInput     = document.getElementById('longitude');
    const coordDisplay = document.getElementById('coord-display');
    const statusDiv    = document.getElementById('geocode-status');
    const clearBtn     = document.getElementById('btn-clear-location');
    const searchInput  = document.getElementById('map-search-input');
    const addressInput = document.querySelector('textarea[name="address"]');
    const cityInput    = document.querySelector('input[name="city"]');
    const provinceInput = document.querySelector('input[name="province"]');

    // Use existing branch coordinates or default to Dumaguete
    const EXISTING_LAT = {{ $branch->latitude ?? 9.3068 }};
    const EXISTING_LNG = {{ $branch->longitude ?? 123.3054 }};
    const HAS_COORDS   = {{ ($branch->latitude && $branch->longitude) ? 'true' : 'false' }};
    const DEFAULT_LAT   = 9.3068;
    const DEFAULT_LNG   = 123.3054;

    // Initialize map
    const map = L.map('branch-map').setView([EXISTING_LAT, EXISTING_LNG], HAS_COORDS ? 16 : 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    let marker = null;

    // Place or move marker
    function setMarker(lat, lng, flyTo = true) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateCoordinates(pos.lat, pos.lng);
            });
        }

        if (flyTo) map.flyTo([lat, lng], 17, { duration: 1.2 });
        updateCoordinates(lat, lng);
    }

    function updateCoordinates(lat, lng) {
        latInput.value = parseFloat(lat).toFixed(6);
        lngInput.value = parseFloat(lng).toFixed(6);
        coordDisplay.innerHTML = `<i class="bi bi-pin-map-fill me-1"></i> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
        coordDisplay.className = 'badge bg-success-subtle text-success px-3 py-2';
        clearBtn.style.display = 'inline-block';
    }

    function showStatus(message, type) {
        statusDiv.style.display = 'block';
        statusDiv.className = `small mt-2 geocode-${type}`;
        statusDiv.innerHTML = type === 'loading'
            ? `<i class="bi bi-arrow-repeat me-1"></i> ${message}`
            : type === 'success'
                ? `<i class="bi bi-check-circle me-1"></i> ${message}`
                : `<i class="bi bi-exclamation-circle me-1"></i> ${message}`;
        if (type !== 'loading') setTimeout(() => { statusDiv.style.display = 'none'; }, 5000);
    }

    // Click map to place marker
    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng, false);
        showStatus('Location set by clicking map. Drag marker to adjust.', 'success');
    });

    // Geocode function (Nominatim)
    async function geocodeQuery(query) {
        showStatus('Searching...', 'loading');
        try {
            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1&countrycodes=ph&addressdetails=1`;
            const resp = await fetch(url, {
                headers: { 'User-Agent': 'WashBox Laundry Management System' }
            });
            const data = await resp.json();

            if (!data || data.length === 0) {
                showStatus(`No results for "${query}". Try a different search or click the map.`, 'error');
                return false;
            }

            const result = data[0];
            setMarker(result.lat, result.lon);

            const name = result.display_name.length > 80
                ? result.display_name.substring(0, 80) + '...' : result.display_name;
            showStatus(`Found: ${name}`, 'success');
            return true;
        } catch (err) {
            showStatus('Geocoding failed. Please click the map to set location manually.', 'error');
            return false;
        }
    }

    // "Locate Address" button — uses address + city + province fields
    document.getElementById('btn-locate-address').addEventListener('click', function() {
        const parts = [
            addressInput.value.trim(),
            cityInput.value.trim(),
            provinceInput.value.trim(),
            'Philippines'
        ].filter(Boolean);

        if (parts.length <= 1) {
            showStatus('Please fill in the Address and City fields first.', 'error');
            return;
        }
        geocodeQuery(parts.join(', '));
    });

    // Search bar
    document.getElementById('btn-search-map').addEventListener('click', function() {
        const query = searchInput.value.trim();
        if (!query) return;
        geocodeQuery(query + ', Negros Oriental, Philippines');
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-search-map').click();
        }
    });

    // Clear location
    clearBtn.addEventListener('click', function() {
        if (marker) { map.removeLayer(marker); marker = null; }
        latInput.value = '';
        lngInput.value = '';
        coordDisplay.innerHTML = '<i class="bi bi-pin-map me-1"></i> No location set';
        coordDisplay.className = 'badge bg-primary-subtle text-primary px-3 py-2';
        clearBtn.style.display = 'none';
        map.setView([DEFAULT_LAT, DEFAULT_LNG], 13);
    });

    // Show existing branch marker on load
    if (HAS_COORDS) {
        setMarker(EXISTING_LAT, EXISTING_LNG, false);
    }

    // Fix map rendering
    setTimeout(() => map.invalidateSize(), 300);
});
</script>
@endpush
