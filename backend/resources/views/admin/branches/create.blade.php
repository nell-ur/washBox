purok 3, Diversion Road, Magatas, Sibulan, 6200 Negros Oriental

@extends('admin.layouts.app')

@section('title', 'Create New Branch')
@section('page-title', 'Create New Branch')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.branches.index') }}" class="me-2 text-dark"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-bold mb-0">Add New Branch</h5>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.branches.store') }}" class="card shadow-sm p-4">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-3"><i class="bi bi-building me-2"></i>Branch Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}" required placeholder="e.g., WashBox Sibulan">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                               name="code" value="{{ old('code') }}" required maxlength="10" placeholder="e.g., SBL, DGT, BAI">
                        <small class="text-muted">Unique 3-10 letter code</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('address') is-invalid @enderror"
                              name="address" rows="2" required placeholder="Full street address (e.g., Purok 3, Diversion Road, Magatas)">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Barangay/District</label>
                        <input type="text" class="form-control @error('barangay') is-invalid @enderror"
                               name="barangay" value="{{ old('barangay') }}" placeholder="e.g., Magatas">
                        @error('barangay')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">City/Municipality <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror"
                               name="city" value="{{ old('city') }}" required placeholder="e.g., Sibulan">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('province') is-invalid @enderror"
                               name="province" value="{{ old('province', $defaultProvince ?? 'Negros Oriental') }}" required placeholder="e.g., Negros Oriental">
                        @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Zip Code</label>
                        <input type="text" class="form-control @error('zip_code') is-invalid @enderror"
                               name="zip_code" value="{{ old('zip_code') }}" placeholder="e.g., 6200" maxlength="4">
                        @error('zip_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                               name="phone" value="{{ old('phone') }}" required placeholder="e.g., 0917 123 4567">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" placeholder="e.g., branch@washbox.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Manager</label>
                        <input type="text" class="form-control @error('manager') is-invalid @enderror"
                               name="manager" value="{{ old('manager') }}" placeholder="Name of branch manager">
                        @error('manager')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                            <option value="1" {{ old('is_active', 1) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active', 1) ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <small class="text-muted">Inactive branches won't appear in mobile app</small>
                        @error('is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ═══ MAP CARD ═══ --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Branch Location</h6>
                        <small class="text-muted">
                            Search by address, Plus Code (e.g. <code>972F+JH4, Sibulan</code>), or click the map to pin
                        </small>
                    </div>
                    <div class="card-body p-0">

                        {{-- Search bar --}}
                        <div class="p-3 border-bottom bg-light">
                            <div class="input-group">
                                <input type="text" id="map-search-input" class="form-control"
                                       placeholder="e.g., Purok 3, Diversion Road, Magatas, Sibulan, 6200 Negros Oriental">
                                <button type="button" class="btn btn-primary" id="btn-search-map">
                                    <i class="bi bi-search me-1"></i> Search
                                </button>
                                <button type="button" class="btn btn-success" id="btn-locate-address">
                                    <i class="bi bi-crosshair me-1"></i> Locate from Form
                                </button>
                            </div>
                            <div id="geocode-status" class="small mt-2" style="display:none;"></div>

                            {{-- Plus Code hint --}}
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Philippine addresses supported:</strong> Purok, Street, Barangay, City/Municipality, Province, Zip Code<br>
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Supports: regular addresses, Google Plus Codes (<code>972F+JH4, Sibulan</code>), and landmark names
                                </small>
                            </div>
                        </div>

                        {{-- Map --}}
                        <div id="branch-map" style="height: 350px; width: 100%;"></div>

                        {{-- Coordinate display --}}
                        <div class="p-3 bg-white border-top">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="badge bg-primary-subtle text-primary px-3 py-2" id="coord-display">
                                        <i class="bi bi-pin-map me-1"></i> No location set
                                    </span>
                                </div>
                                <div class="col-auto ms-auto">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            id="btn-clear-location" style="display:none;">
                                        <i class="bi bi-x-circle me-1"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden inputs --}}
                        <input type="hidden" name="latitude"  id="latitude"  value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2"></i>Operating Hours (Optional)</h6>
                        <small class="text-muted">Simple text format for branch hours</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Operating Hours</label>
                            <input type="text" class="form-control @error('operating_hours') is-invalid @enderror"
                                   name="operating_hours" value="{{ old('operating_hours') }}"
                                   placeholder="e.g., Monday-Friday: 8:00 AM - 6:00 PM, Saturday-Sunday: 9:00 AM - 5:00 PM">
                            <small class="text-muted">
                                Examples:<br>
                                • "Monday-Friday: 8:00 AM - 6:00 PM, Saturday-Sunday: 9:00 AM - 5:00 PM"<br>
                                • "Daily: 8:00 AM - 8:00 PM"
                            </small>
                            @error('operating_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2"></i>Quick Tips</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-lightbulb me-2"></i>Best Practices:</h6>
                            <ul class="mb-0">
                                <li>Use unique branch codes (SBL, DGT, BAI)</li>
                                <li>Ensure phone numbers are correct for customer contact</li>
                                <li>Use the map to pin your branch location</li>
                                <li>Use clear, readable text for operating hours</li>
                            </ul>
                        </div>

                        <div class="alert alert-success">
                            <h6><i class="bi bi-geo-alt me-2"></i>Philippine Address Format:</h6>
                            <ul class="mb-0">
                                <li><strong>Example:</strong> Purok 3, Diversion Road, Magatas, Sibulan, 6200 Negros Oriental</li>
                                <li>Purok / Sitio / Subdivision</li>
                                <li>Street / Road name</li>
                                <li>Barangay</li>
                                <li>City / Municipality</li>
                                <li>Zip Code + Province</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important:</h6>
                            <ul class="mb-0">
                                <li>Inactive branches won't appear in mobile app</li>
                                <li>Branch code cannot be changed after creation</li>
                                <li>Click the map to set exact location if search is not precise</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Create Branch
                    </button>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-light border">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Preview</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">This branch will appear in mobile app as:</small>
                        <div class="mt-2 p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary rounded-circle p-2 me-2">
                                    <i class="bi bi-building text-white"></i>
                                </div>
                                <div>
                                    <strong id="preview-name">{{ old('name', 'WashBox Branch') }}</strong>
                                    <div class="text-muted small" id="preview-code">{{ old('code', 'CODE') }}</div>
                                </div>
                            </div>
                            <div class="small">
                                <div><i class="bi bi-geo-alt me-1"></i> <span id="preview-address">{{ old('address', 'Address not set') }}</span></div>
                                <div><i class="bi bi-telephone me-1"></i> <span id="preview-phone">{{ old('phone', 'Phone not set') }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
{{-- Leaflet --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
{{-- Open Location Code (Plus Code decoder) --}}
<script src="https://cdn.jsdelivr.net/npm/open-location-code@1.0.3/openlocationcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ═══════════════════════════════════════
    // LIVE PREVIEW
    // ═══════════════════════════════════════
    const nameInput     = document.querySelector('input[name="name"]');
    const codeInput     = document.querySelector('input[name="code"]');
    const addressInput  = document.querySelector('textarea[name="address"]');
    const phoneInput    = document.querySelector('input[name="phone"]');
    const barangayInput = document.querySelector('input[name="barangay"]');
    const cityInput     = document.querySelector('input[name="city"]');
    const provinceInput = document.querySelector('input[name="province"]');
    const zipInput      = document.querySelector('input[name="zip_code"]');

    const previewName    = document.getElementById('preview-name');
    const previewCode    = document.getElementById('preview-code');
    const previewAddress = document.getElementById('preview-address');
    const previewPhone   = document.getElementById('preview-phone');

    function updatePreview() {
        previewName.textContent = nameInput.value || 'WashBox Branch';
        previewCode.textContent = codeInput.value || 'CODE';

        // Build full address for preview
        const addressParts = [
            addressInput.value,
            barangayInput.value,
            cityInput.value,
            zipInput.value,
            provinceInput.value
        ].filter(Boolean);

        previewAddress.textContent = addressParts.join(', ') || 'Address not set';
        previewPhone.textContent = phoneInput.value || 'Phone not set';
    }

    [nameInput, codeInput, addressInput, phoneInput, barangayInput, cityInput, provinceInput, zipInput].forEach(el => {
        if (el) el.addEventListener('input', updatePreview);
    });
    updatePreview();

    // Format phone
    phoneInput.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 7)      v = v.slice(0, 4) + ' ' + v.slice(4, 7) + ' ' + v.slice(7);
        else if (v.length > 4) v = v.slice(0, 4) + ' ' + v.slice(4);
        e.target.value = v;
    });

    // ═══════════════════════════════════════
    // MAP SETUP
    // ═══════════════════════════════════════
    const latInput     = document.getElementById('latitude');
    const lngInput     = document.getElementById('longitude');
    const coordDisplay = document.getElementById('coord-display');
    const statusDiv    = document.getElementById('geocode-status');
    const clearBtn     = document.getElementById('btn-clear-location');
    const searchInput  = document.getElementById('map-search-input');

    const DEFAULT_LAT = 9.3068;
    const DEFAULT_LNG = 123.3054;

    const map = L.map('branch-map').setView([DEFAULT_LAT, DEFAULT_LNG], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    let marker = null;

    function setMarker(lat, lng, flyTo = true) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
        if (isNaN(lat) || isNaN(lng)) return;

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                updateCoordinates(pos.lat, pos.lng);
            });
        }

        if (flyTo) map.flyTo([lat, lng], 17, { duration: 1.2 });
        updateCoordinates(lat, lng);
    }

    function updateCoordinates(lat, lng) {
        latInput.value  = parseFloat(lat).toFixed(6);
        lngInput.value  = parseFloat(lng).toFixed(6);
        coordDisplay.innerHTML = `<i class="bi bi-pin-map-fill me-1"></i> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
        coordDisplay.className = 'badge bg-success-subtle text-success px-3 py-2';
        clearBtn.style.display = 'inline-block';
    }

    function showStatus(message, type) {
        statusDiv.style.display = 'block';
        const icon = type === 'loading' ? 'arrow-repeat' : type === 'success' ? 'check-circle' : 'exclamation-circle';
        statusDiv.className = `small mt-2 geocode-${type}`;
        statusDiv.innerHTML = `<i class="bi bi-${icon} me-1${type === 'loading' ? ' spinner-rotate' : ''}"></i> ${message}`;
        if (type !== 'loading') setTimeout(() => { statusDiv.style.display = 'none'; }, 6000);
    }

    map.on('click', function (e) {
        setMarker(e.latlng.lat, e.latlng.lng, false);
        showStatus('Location set by clicking the map. Drag the marker to fine-tune.', 'success');
    });

    // ═══════════════════════════════════════
    // IMPROVED NOMINATIM GEOCODER for PH Addresses
    // ═══════════════════════════════════════

    async function nominatimGeocode(query) {
        try {
            // Add Philippines if not already in query
            let searchQuery = query;
            if (!/philippines|pilipinas/i.test(searchQuery)) {
                searchQuery += ', Philippines';
            }

            // Clean up the query - remove extra spaces, standardize
            searchQuery = searchQuery.replace(/\s+/g, ' ').trim();

            console.log('Geocoding:', searchQuery); // For debugging

            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(searchQuery)}&format=json&limit=1&countrycodes=ph&addressdetails=1`;
            const resp = await fetch(url, {
                headers: {
                    'User-Agent': 'WashBox Laundry Management System',
                    'Accept-Language': 'en-US,en;q=0.9' // Prefer English results
                }
            });

            if (!resp.ok) {
                throw new Error(`HTTP error! status: ${resp.status}`);
            }

            const data = await resp.json();

            if (!data || data.length === 0) {
                // Try with simplified address (remove barangay/purok if no results)
                return await trySimplifiedGeocode(query);
            }

            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon),
                display: data[0].display_name
            };
        } catch (error) {
            console.error('Geocoding error:', error);
            return null;
        }
    }

    // Try with progressively simpler address formats
    async function trySimplifiedGeocode(originalQuery) {
        // Remove zip code first (e.g., 6200)
        let simplified = originalQuery.replace(/\b\d{4}\b/g, '').trim();

        // Try without purok/barangay if it exists
        const parts = simplified.split(',').map(p => p.trim());

        // Try different combinations
        const attempts = [
            // Full address without zip
            simplified,
            // City/Municipality and Province only
            parts.slice(-2).join(', '),
            // Province only
            parts[parts.length - 1],
            // Just the city/municipality
            parts[parts.length - 2]
        ];

        for (const attempt of attempts) {
            if (!attempt) continue;

            console.log('Trying simplified:', attempt);

            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(attempt + ', Philippines')}&format=json&limit=1&countrycodes=ph`;
            const resp = await fetch(url, {
                headers: { 'User-Agent': 'WashBox Laundry Management System' }
            });
            const data = await resp.json();

            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    display: data[0].display_name,
                    note: 'Approximate location (based on city/province)'
                };
            }
        }

        return null;
    }

    // ═══════════════════════════════════════
    // PLUS CODE DETECTION & DECODING
    // ═══════════════════════════════════════

    /**
     * Returns the Plus Code part and the reference locality separately.
     *
     * Examples:
     *   "972F+JH4, Diversion Road, Sibulan, Negros Oriental"
     *     → { code: "972F+JH4", locality: "Sibulan, Negros Oriental, Philippines" }
     *
     *   "7GX2972F+JH4"
     *     → { code: "7GX2972F+JH4", locality: null }   (full code, no reference needed)
     *
     *   "Robinsons Place Dumaguete"
     *     → null  (not a Plus Code)
     */
    function parsePlusCode(input) {
        // Plus Code alphabet (excludes vowels + some ambiguous chars)
        const OLC_CHARS = '[23456789CFGHJMPQRVWX]';

        // Full 8-char code:  e.g. 7GX2972F+JH4
        const fullPattern  = new RegExp(`(${OLC_CHARS}{4,8}\\+${OLC_CHARS}{2,})`, 'i');
        // Short code:        e.g. 972F+JH4
        const shortPattern = new RegExp(`^\\s*(${OLC_CHARS}{4}\\+${OLC_CHARS}{2,})`, 'i');

        // Try full code first
        let match = input.match(fullPattern);
        if (match && match[1].length >= 8) {
            // Full code — locality = everything AFTER the plus code + comma
            const afterCode = input.replace(match[1], '').replace(/^[,\s]+/, '').trim();
            return { code: match[1].toUpperCase(), locality: afterCode || null, isShort: false };
        }

        // Try short code
        match = input.match(shortPattern);
        if (match) {
            // Everything after "CODE, ..." = reference locality
            const rest = input.slice(match[0].length).replace(/^[,\s]+/, '').trim();
            return { code: match[1].toUpperCase(), locality: rest || null, isShort: true };
        }

        return null; // not a Plus Code
    }

    /**
     * Decode a Plus Code to {lat, lng} using the open-location-code library.
     * Short codes require a reference {lat, lng} to recover their global position.
     */
    async function decodePlusCode(parsed) {
        const olc = window.OpenLocationCode || window.openlocationcode || window.openLocationCode;

        if (!olc) {
            showStatus('Plus Code library not loaded. Falling back to address search.', 'error');
            return null;
        }

        try {
            if (!parsed.isShort) {
                // Full code — decode directly
                if (!olc.isValid(parsed.code)) {
                    showStatus('Invalid Plus Code format.', 'error');
                    return null;
                }
                const ca = olc.decode(parsed.code);
                return { lat: ca.latitudeCenter, lng: ca.longitudeCenter };
            }

            // Short code — need reference lat/lng from locality string
            if (!parsed.locality) {
                showStatus('Short Plus Code needs a reference location. Try: "972F+JH4, Sibulan"', 'error');
                return null;
            }

            // Build a list of progressively simpler locality candidates.
            // Input like "Diversion Road, Sibulan, Negros Oriental" becomes:
            //   ["Diversion Road, Sibulan, Negros Oriental", "Sibulan, Negros Oriental", "Negros Oriental"]
            // We try each until Nominatim resolves one — street names often fail
            // but city/province names succeed.
            const localityParts = parsed.locality.split(',').map(s => s.trim()).filter(Boolean);
            let refCoords = null;
            let resolvedWith = null;

            for (let i = 0; i < localityParts.length; i++) {
                const candidate = localityParts.slice(i).join(', ') + ', Philippines';
                showStatus(`Resolving reference: ${candidate}…`, 'loading');
                refCoords = await nominatimGeocode(candidate);
                if (refCoords) { resolvedWith = candidate; break; }
            }

            if (!refCoords) {
                showStatus(`Could not resolve any part of "${parsed.locality}" as a location. ` +
                           `Try: "<strong>${parsed.code}, Sibulan</strong>" or "<strong>${parsed.code}, Negros Oriental</strong>".`, 'error');
                return null;
            }

            // Recover the full code using the reference point
            const fullCode = olc.recoverNearest(parsed.code, refCoords.lat, refCoords.lng);
            if (!olc.isValid(fullCode)) {
                showStatus('Could not decode Plus Code with given reference location.', 'error');
                return null;
            }

            const ca = olc.decode(fullCode);
            // Store resolved reference so caller can display it
            parsed._resolvedWith = resolvedWith;
            return { lat: ca.latitudeCenter, lng: ca.longitudeCenter };

        } catch (err) {
            console.error('Plus Code decode error:', err);
            showStatus('Plus Code decoding failed. Try adding the city name after the code.', 'error');
            return null;
        }
    }

    // ═══════════════════════════════════════
    // IMPROVED MAIN GEOCODE FUNCTION
    // ═══════════════════════════════════════

    async function geocodeQuery(rawQuery) {
        showStatus('Searching…', 'loading');

        // Clean up the input
        rawQuery = rawQuery.replace(/\s+/g, ' ').trim();

        // Check if it's a Plus Code first
        const parsed = parsePlusCode(rawQuery);

        // ── Branch A: Plus Code ───────────────
        if (parsed) {
            showStatus(
                parsed.isShort
                    ? `Decoding short Plus Code <strong>${parsed.code}</strong> with reference: ${parsed.locality || '?'}…`
                    : `Decoding Plus Code <strong>${parsed.code}</strong>…`,
                'loading'
            );

            const coords = await decodePlusCode(parsed);
            if (coords) {
                const refNote = parsed._resolvedWith
                    ? ` (resolved via <em>${parsed._resolvedWith.replace(', Philippines','')}</em>)`
                    : '';
                setMarker(coords.lat, coords.lng);
                showStatus(
                    `📍 Plus Code <strong>${parsed.code}</strong>${refNote} → ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)}`,
                    'success'
                );
                return;
            }
            // If decoding failed, fall through to Nominatim
        }

        // ── Branch B: Regular address ─────
        let result = await nominatimGeocode(rawQuery);

        if (!result) {
            // Try to extract just the city and province
            const parts = rawQuery.split(',').map(p => p.trim());
            const cityProvince = parts.slice(-2).join(', ');

            if (cityProvince && cityProvince !== rawQuery) {
                showStatus(`Trying city/province: ${cityProvince}…`, 'loading');
                result = await nominatimGeocode(cityProvince);
            }
        }

        if (!result) {
            showStatus(
                `No results found for "<strong>${rawQuery}</strong>". ` +
                `Try clicking directly on the map to pin the location.`,
                'error'
            );
            return;
        }

        setMarker(result.lat, result.lng);

        // Show result with note if it's approximate
        const displayMessage = result.note
            ? `📍 ${result.note}: ${result.display.substring(0, 60)}…`
            : `📍 Found: ${result.display.substring(0, 80)}${result.display.length > 80 ? '…' : ''}`;

        showStatus(displayMessage, 'success');

        // Auto-fill city/province if they're empty
        if (!cityInput.value.trim() && result.display.includes(',')) {
            const parts = result.display.split(',');
            if (parts.length >= 2) {
                // Try to extract city and province from result
                const lastTwo = parts.slice(-2).map(p => p.trim().replace('Philippines', '').trim());
                if (lastTwo.length === 2) {
                    if (!cityInput.value.trim()) cityInput.value = lastTwo[0];
                    if (!provinceInput.value.trim()) provinceInput.value = lastTwo[1];
                }
            }
        }
    }

    // ═══════════════════════════════════════
    // BUTTON HANDLERS
    // ═══════════════════════════════════════

    // Manual search bar
    document.getElementById('btn-search-map').addEventListener('click', function () {
        const q = searchInput.value.trim();
        if (!q) { showStatus('Please enter an address or Plus Code to search.', 'error'); return; }
        geocodeQuery(q);
    });

    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-search-map').click(); }
    });

    // "Locate from Form" — builds from all form fields
    document.getElementById('btn-locate-address').addEventListener('click', function () {
        const parts = [
            addressInput.value.trim(),
            barangayInput.value.trim(),
            cityInput.value.trim(),
            zipInput.value.trim(),
            provinceInput.value.trim()
        ].filter(Boolean);

        if (parts.length < 2) {
            showStatus('Please fill in at least Address and City fields first.', 'error');
            return;
        }

        // Build the full address with proper formatting
        const fullAddress = parts.join(', ');
        geocodeQuery(fullAddress);
    });

    // Clear location
    clearBtn.addEventListener('click', function () {
        if (marker) { map.removeLayer(marker); marker = null; }
        latInput.value  = '';
        lngInput.value  = '';
        coordDisplay.innerHTML = '<i class="bi bi-pin-map me-1"></i> No location set';
        coordDisplay.className = 'badge bg-primary-subtle text-primary px-3 py-2';
        clearBtn.style.display = 'none';
        map.setView([DEFAULT_LAT, DEFAULT_LNG], 13);
    });

    // Restore coordinates on validation error reload
    const oldLat = latInput.value;
    const oldLng = lngInput.value;
    if (oldLat && oldLng) setMarker(oldLat, oldLng);

    // Fix Leaflet rendering inside cards
    setTimeout(() => map.invalidateSize(), 300);
});
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .form-label       { font-size: 0.9rem; font-weight: 600; }
    .text-muted       { font-size: 0.85rem; }
    .card-header      { padding: 0.75rem 1.25rem; }
    #preview-name     { font-size: 1rem; line-height: 1.2; }
    #preview-code     { font-size: 0.75rem; opacity: 0.8; }
    #branch-map       { cursor: crosshair; z-index: 1; }
    .geocode-success  { color: #10B981; }
    .geocode-error    { color: #EF4444; }
    .geocode-loading  { color: #6366F1; }

    /* Spinner animation for loading icon */
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .spinner-rotate { display: inline-block; animation: spin 1s linear infinite; }

    code {
        background: #f3f4f6;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #3D3B6B;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .input-group {
            flex-direction: column;
        }
        .input-group > * {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>
@endpush
