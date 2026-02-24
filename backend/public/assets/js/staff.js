const DASHBOARD_CONFIG = {
    autoRefresh: true,
    refreshInterval: 30000,
    charts: { revenueTrend: null },
    cacheKey: 'staff_dashboard_active_tab'
};

// ── Global map variables ──
let logisticsMapInstance = null;
let modalMapInstance = null;
let routeLayer = null;
let startMarker = null;
let endMarker = null;
let pickupMarkers = [];
let pickupCluster = null;
let modalPickupCluster = null;
let selectedPickups = new Set();
let searchResultMarker = null;
const branchMarkerRefs = [];

// ── Data from controller (will be set when script loads) ──
let BRANCHES = [];
let PICKUP_LOCATIONS = [];
let STAFF_BRANCH_ID = null;
const BRANCH_COLORS = ['#007BFF','#10B981','#8B5CF6','#F59E0B','#EF4444','#EC4899','#0EA5E9','#F97316'];

// Dynamic map center (will be calculated)
let MAP_CENTER = [9.3068, 123.3054];
let MAP_ZOOM = 14;

/**
 * Initialize dashboard with data from server
 * This function should be called from the template with JSON data
 */
function initializeDashboardData(branches, pickups, staffBranchId) {
    BRANCHES = branches || [];
    PICKUP_LOCATIONS = pickups || [];
    STAFF_BRANCH_ID = staffBranchId;

    // Calculate map center
    if (BRANCHES.length > 0) {
        MAP_CENTER = [
            BRANCHES.reduce((sum, b) => sum + (parseFloat(b.latitude) || 0), 0) / BRANCHES.length,
            BRANCHES.reduce((sum, b) => sum + (parseFloat(b.longitude) || 0), 0) / BRANCHES.length
        ];
    }
    MAP_ZOOM = BRANCHES.length > 1 ? 12 : 14;

    console.log(`📍 Loaded ${BRANCHES.length} branch(es), ${PICKUP_LOCATIONS.length} pickup(s)`);
}

// ================================================================
// HELPERS
// ================================================================
function getNearestBranch(lat, lon) {
    if (!BRANCHES.length) return { name:'WashBox Branch', latitude:9.3068, longitude:123.3054, address:'', phone:'' };
    let nearest = BRANCHES[0], minD = Infinity;
    BRANCHES.forEach(b => {
        const d = Math.pow((parseFloat(b.latitude) - lat), 2) + Math.pow((parseFloat(b.longitude) - lon), 2);
        if (d < minD) { minD = d; nearest = b; }
    });
    return nearest;
}

function getStaffBranch() {
    return BRANCHES.find(b => parseInt(b.id) === parseInt(STAFF_BRANCH_ID)) || BRANCHES[0] || getNearestBranch(MAP_CENTER[0], MAP_CENTER[1]);
}

function getActiveMap() {
    return (document.getElementById('mapModal')?.classList.contains('show') && modalMapInstance) ? modalMapInstance : logisticsMapInstance;
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'en_route': 'primary',
        'picked_up': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function decodePolyline(encoded) {
    if (!encoded || typeof encoded !== 'string') return [];
    if (encoded.startsWith('[')) return JSON.parse(encoded);
    let pts=[], idx=0, len=encoded.length, lat=0, lng=0;
    while (idx < len) {
        let b, sh=0, res=0;
        do { b=encoded.charCodeAt(idx++)-63; res|=(b&0x1f)<<sh; sh+=5; } while(b>=0x20);
        lat += (res&1) ? ~(res>>1) : (res>>1);
        sh=0; res=0;
        do { b=encoded.charCodeAt(idx++)-63; res|=(b&0x1f)<<sh; sh+=5; } while(b>=0x20);
        lng += (res&1) ? ~(res>>1) : (res>>1);
        pts.push([lat/1e5, lng/1e5]);
    }
    return pts;
}

// ================================================================
// SERVICES CAROUSEL FUNCTIONALITY WITH AUTO-SLIDE
// ================================================================

function initServicesCarousel() {
    const carousel = document.querySelector('.services-carousel');
    if (!carousel) return;

    let autoSlideInterval;
    const slideSpeed = 3000; // Change slide every 3 seconds
    const slideDistance = 250; // Pixels to scroll each time

    // Start auto-slide
    function startAutoSlide() {
        if (autoSlideInterval) clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(() => {
            // Check if carousel is not being hovered
            if (!carousel.matches(':hover')) {
                // If at the end, scroll back to start smoothly
                if (carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 10) {
                    carousel.scrollTo({
                        left: 0,
                        behavior: 'smooth'
                    });
                } else {
                    // Scroll to next slide
                    carousel.scrollBy({
                        left: slideDistance,
                        behavior: 'smooth'
                    });
                }
            }
        }, slideSpeed);
    }

    // Stop auto-slide
    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }
    }

    // Mouse drag scrolling
    let isDown = false;
    let startX;
    let scrollLeft;

    carousel.addEventListener('mousedown', (e) => {
        isDown = true;
        carousel.classList.add('active');
        startX = e.pageX - carousel.offsetLeft;
        scrollLeft = carousel.scrollLeft;
        e.preventDefault();
        stopAutoSlide(); // Stop auto-slide when user interacts
    });

    carousel.addEventListener('mouseleave', () => {
        isDown = false;
        carousel.classList.remove('active');
        startAutoSlide(); // Restart auto-slide when mouse leaves
    });

    carousel.addEventListener('mouseup', () => {
        isDown = false;
        carousel.classList.remove('active');
        startAutoSlide(); // Restart auto-slide after mouse up
    });

    carousel.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - carousel.offsetLeft;
        const walk = (x - startX) * 2;
        carousel.scrollLeft = scrollLeft - walk;
    });

    // Touch support for mobile
    carousel.addEventListener('touchstart', (e) => {
        isDown = true;
        startX = e.touches[0].pageX - carousel.offsetLeft;
        scrollLeft = carousel.scrollLeft;
        stopAutoSlide(); // Stop auto-slide on touch
    });

    carousel.addEventListener('touchend', () => {
        isDown = false;
        startAutoSlide(); // Restart auto-slide after touch ends
    });

    carousel.addEventListener('touchmove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.touches[0].pageX - carousel.offsetLeft;
        const walk = (x - startX) * 2;
        carousel.scrollLeft = scrollLeft - walk;
    });

    // Pause auto-slide when hovering over carousel
    carousel.addEventListener('mouseenter', stopAutoSlide);
    carousel.addEventListener('mouseleave', startAutoSlide);

    // Start auto-slide initially
    startAutoSlide();

    // Add progress indicator
    createProgressIndicator(carousel);
}

// Create progress indicator for the auto-slide
function createProgressIndicator(carousel) {
    const container = document.querySelector('.services-carousel-container');
    if (!container) return;

    // Remove existing progress bar if any
    const existingProgress = document.querySelector('.carousel-progress');
    if (existingProgress) existingProgress.remove();

    // Create progress bar container
    const progressContainer = document.createElement('div');
    progressContainer.className = 'carousel-progress';

    const progressBar = document.createElement('div');
    progressBar.className = 'carousel-progress-bar';

    progressContainer.appendChild(progressBar);
    container.appendChild(progressContainer);

    // Animate progress bar
    let progressInterval;
    const slideSpeed = 3000; // Match with auto-slide speed
    const updateInterval = 100; // Update every 100ms

    function startProgressAnimation() {
        let width = 0;
        if (progressInterval) clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            width += (updateInterval / slideSpeed) * 100;
            if (width >= 100) {
                width = 0;
            }
            progressBar.style.width = width + '%';
        }, updateInterval);
    }

    // Control progress based on carousel interaction
    carousel.addEventListener('mouseenter', () => {
        if (progressInterval) clearInterval(progressInterval);
        progressBar.style.width = '0%';
    });

    carousel.addEventListener('mouseleave', () => {
        startProgressAnimation();
    });

    // Start progress animation
    startProgressAnimation();
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeTabs();
    initializeAutoRefresh();
    initializeDateUpdater();
    initMapAddressSearch();
    // Init map immediately (overview tab is default)
    setTimeout(() => initLogisticsMap(), 400);
    setupModalMap();
    initServicesCarousel(); // Initialize the services carousel
    console.log('✅ Staff Dashboard initialized');
});

// ================================================================
// TAB MANAGEMENT
// ================================================================
function initializeTabs() {
    const saved = localStorage.getItem(DASHBOARD_CONFIG.cacheKey) || 'overview';
    const btn = document.getElementById(`${saved}-tab`);
    if (btn && typeof bootstrap !== 'undefined') {
        new bootstrap.Tab(btn).show();
    }

    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', e => {
            const name = e.target.id.replace('-tab','');
            localStorage.setItem(DASHBOARD_CONFIG.cacheKey, name);
            if (name === 'overview') {
                setTimeout(() => {
                    if (!logisticsMapInstance) initLogisticsMap();
                    else logisticsMapInstance.invalidateSize();
                    // Resize chart if it exists
                    if (DASHBOARD_CONFIG.charts.revenueTrend) {
                        DASHBOARD_CONFIG.charts.revenueTrend.resize();
                    }
                }, 200);
            }
        });
    });
}

// ================================================================
// MAP INIT (branchMap container)
// ================================================================
function initLogisticsMap() {
    const container = document.getElementById('branchMap');
    if (!container) { console.error('Map container #branchMap not found'); return; }
    if (logisticsMapInstance) { logisticsMapInstance.remove(); logisticsMapInstance = null; }

    logisticsMapInstance = L.map('branchMap').setView(MAP_CENTER, MAP_ZOOM);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(logisticsMapInstance);

    pickupCluster = L.markerClusterGroup({ chunkedLoading: true });
    logisticsMapInstance.addLayer(pickupCluster);
    createClusterToggleControl(logisticsMapInstance, 'clusterToggleStaff', true);

    addBranchMarkers();
    loadPickupsAndRender();
    setTimeout(() => { if (logisticsMapInstance) logisticsMapInstance.invalidateSize(); }, 300);
    console.log('✅ Logistics map initialized');
}

// ================================================================
// BRANCH MARKERS (color palette + staff glow)
// ================================================================
function addBranchMarkers() {
    BRANCHES.forEach((branch, i) => {
        if (!branch.latitude || !branch.longitude) return;
        const isStaff = parseInt(branch.id) === parseInt(STAFF_BRANCH_ID);
        const color = BRANCH_COLORS[i % BRANCH_COLORS.length];
        const sz = isStaff ? 48 : 40;
        const glow = isStaff ? `box-shadow:0 0 16px ${color}88;` : '';

        const marker = L.marker([parseFloat(branch.latitude), parseFloat(branch.longitude)], {
            icon: L.divIcon({
                className: 'branch-marker',
                html: `<div style="background:${color};width:${sz}px;height:${sz}px;border-radius:50%;border:${isStaff?4:3}px solid white;${glow}display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="bi bi-shop" style="color:white;font-size:${isStaff?22:18}px;"></i></div>`,
                iconSize: [sz,sz], iconAnchor: [sz/2, sz]
            }),
            zIndexOffset: isStaff ? 1000 : 0
        }).addTo(logisticsMapInstance);

        const badge = isStaff ? ' <span class="badge bg-success" style="font-size:0.6rem;">YOUR BRANCH</span>' : '';
        marker.bindPopup(`<div style="min-width:220px"><h6 class="mb-1"><b><span style="color:${color}">●</span> ${branch.name}${badge}</b></h6><p class="mb-1 small text-muted">${branch.address||'Negros Oriental'}</p>${branch.phone?`<p class="mb-1 small"><i class="bi bi-telephone me-1"></i>${branch.phone}</p>`:''}<hr class="my-2"><button class="btn btn-sm btn-primary w-100" onclick="window.showBranchInfo(${branch.id})"><i class="bi bi-info-circle"></i> Branch Info</button></div>`);
        branchMarkerRefs.push(marker);
        pickupMarkers.push(marker);
    });

    if (!BRANCHES.length) {
        const fb = L.marker([9.3068,123.3054], {
            icon: L.divIcon({ className:'branch-marker', html:'<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="bi bi-shop" style="color:white;font-size:18px;"></i></div>', iconSize:[40,40], iconAnchor:[20,40] })
        }).addTo(logisticsMapInstance).bindPopup('<b>WashBox Laundry</b>');
        pickupMarkers.push(fb);
    }
}

// ================================================================
// PICKUP MARKERS
// ================================================================
function loadPickupsAndRender() {
    if (!PICKUP_LOCATIONS.length) { fitMapToMarkers(); return; }
    clearPickupMarkers();
    PICKUP_LOCATIONS.forEach(p => { if (p.latitude && p.longitude) addPickupMarker(p); });
    fitMapToMarkers();
}

function addPickupMarker(pickup) {
    const colors = {
        pending:'#FFC107',
        accepted:'#17A2B8',
        en_route:'#007BFF',
        picked_up:'#28A745',
        cancelled:'#DC3545'
    };
    const c = colors[pickup.status] || '#6C757D';
    const marker = L.marker([parseFloat(pickup.latitude), parseFloat(pickup.longitude)], {
        icon: L.divIcon({
            className: 'pickup-marker',
            html: `<div style="background:${c};width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;" id="marker-${pickup.id}"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`,
            iconSize:[32,32], iconAnchor:[16,32]
        })
    }).bindPopup(createPickupPopup(pickup));
    if (pickupCluster) pickupCluster.addLayer(marker);
    else marker.addTo(logisticsMapInstance);
    pickupMarkers.push(marker);
}

function createPickupPopup(pickup) {
    const name = pickup.customer?.name || 'Customer';
    const isSel = selectedPickups.has(parseInt(pickup.id));
    const lat = parseFloat(pickup.latitude), lng = parseFloat(pickup.longitude);
    return `<div style="min-width:250px" class="pickup-${pickup.id}">
        <h6><b>${name}</b></h6>
        <p class="mb-1 small">${pickup.pickup_address || 'No address'}</p>
        <span class="badge bg-${getStatusColor(pickup.status)}">${pickup.status}</span>
        <hr class="my-2">
        <div class="d-grid gap-1">
            <button class="btn btn-sm ${isSel?'btn-purple':'btn-outline-purple'}" onclick="window.togglePickupSelection(${pickup.id}); this.blur();">
                <i class="bi ${isSel?'bi-check-square-fill':'bi-check-square'} me-1"></i> ${isSel?'Selected':'Select for Multi-Route'}
            </button>
            <button class="btn btn-sm btn-primary" onclick="window.getRouteToPickup(${lat},${lng},'${name.replace(/'/g,"\\'")}')">
                <i class="bi bi-signpost me-1"></i> Direct Route
            </button>
            <button class="btn btn-sm btn-success" onclick="window.startNavigation(${pickup.id})">
                <i class="bi bi-play-circle me-1"></i> Start Navigation
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.open('/staff/pickups/${pickup.id}','_blank')">
                <i class="bi bi-eye me-1"></i> View Details
            </button>
        </div></div>`;
}

function clearPickupMarkers() {
    if (pickupCluster) pickupCluster.clearLayers();
    pickupMarkers.forEach(m => { try { if (m && logisticsMapInstance) logisticsMapInstance.removeLayer(m); } catch(e){} });
    pickupMarkers = [];
}

function fitMapToMarkers() {
    const pts = [];
    BRANCHES.forEach(b => { if (b.latitude && b.longitude) pts.push([parseFloat(b.latitude), parseFloat(b.longitude)]); });
    PICKUP_LOCATIONS.forEach(p => { if (p.latitude && p.longitude) pts.push([parseFloat(p.latitude), parseFloat(p.longitude)]); });
    if (pts.length > 1) logisticsMapInstance.fitBounds(L.latLngBounds(pts).pad(0.15));
    else if (pts.length === 1) logisticsMapInstance.setView(pts[0], 15);
}

function createClusterToggleControl(map, id, on) {
    const C = L.Control.extend({
        onAdd: () => {
            const d = L.DomUtil.create('div','leaflet-bar p-2 bg-white rounded shadow-sm');
            d.innerHTML = `<div class="form-check m-1"><input class="form-check-input" type="checkbox" id="${id}" ${on?'checked':''}><label class="form-check-label small ms-1" for="${id}">Cluster pickups</label></div>`;
            L.DomEvent.disableClickPropagation(d);
            return d;
        }
    });
    map.addControl(new C({ position:'topright' }));
    setTimeout(() => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', e => { if (e.target.checked) map.addLayer(pickupCluster); else map.removeLayer(pickupCluster); });
    }, 200);
}

function refreshMapMarkers() {
    if (!logisticsMapInstance) return;
    // Re-add tile layer that may have been removed
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19 }).addTo(logisticsMapInstance);
    clearPickupMarkers();
    addBranchMarkers();
    loadPickupsAndRender();
    showToast('Map refreshed', 'info');
}

// ================================================================
// ADDRESS SEARCH & GEOCODING
// ================================================================
function initMapAddressSearch() {
    const input = document.getElementById('map-address-search');
    if (!input) return;
    input.addEventListener('keypress', e => { if (e.key==='Enter') { e.preventDefault(); searchMapAddress(); } });
}

async function searchMapAddress() {
    const input = document.getElementById('map-address-search');
    const resultsDiv = document.getElementById('search-result-display');
    const address = input?.value.trim();
    if (!address || address.length < 3) { showToast('Enter at least 3 characters','warning'); return; }

    try {
        resultsDiv.style.display = 'block';
        document.getElementById('result-address-text').textContent = 'Searching...';
        document.getElementById('result-coords-text').textContent = '🔍 Looking up location';

        const resp = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address+', Philippines')}&format=json&limit=1&countrycodes=ph&addressdetails=1`, {
            headers: { 'User-Agent':'WashBox Laundry Management System' }
        });
        if (!resp.ok) throw new Error('Geocoding service unavailable');
        const data = await resp.json();
        if (!data?.length) throw new Error('Address not found');

        const lat = parseFloat(data[0].lat), lon = parseFloat(data[0].lon);
        document.getElementById('result-address-text').textContent = data[0].display_name;
        document.getElementById('result-coords-text').textContent = `📍 ${lat.toFixed(6)}, ${lon.toFixed(6)}`;
        addDraggableSearchMarker(lat, lon, data[0].display_name);
        if (logisticsMapInstance) logisticsMapInstance.flyTo([lat,lon], 17, { duration:1.5 });
        showToast('📍 Location found!','success');
    } catch(err) {
        resultsDiv.style.display = 'none';
        showToast('Search failed: '+err.message,'danger');
    }
}

function addDraggableSearchMarker(lat, lon, address) {
    if (searchResultMarker && logisticsMapInstance) logisticsMapInstance.removeLayer(searchResultMarker);
    searchResultMarker = L.marker([lat,lon], {
        icon: L.divIcon({
            className:'search-result-marker',
            html:'<div class="search-marker-pulse" style="background:#10B981;width:44px;height:44px;border-radius:50%;border:4px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;"><i class="bi bi-geo-alt-fill" style="color:white;font-size:22px;"></i></div>',
            iconSize:[44,44], iconAnchor:[22,44]
        }),
        draggable:true, autoPan:true
    }).addTo(logisticsMapInstance);
    searchResultMarker.on('dragend', e => { const p=e.target.getLatLng(); updateMarkerLocation(p.lat,p.lng); });
    updateSearchMarkerPopup(lat, lon, address);
    searchResultMarker.openPopup();
}

function updateSearchMarkerPopup(lat, lon, addr) {
    if (!searchResultMarker) return;
    searchResultMarker.bindPopup(`<div style="min-width:280px;">
        <h6 class="mb-2"><b>📍 Selected Location</b></h6>
        <p class="mb-2 small text-muted">${addr}</p>
        <div class="alert alert-info py-2 px-2 mb-2 small"><strong>Coordinates:</strong><br>Lat: ${lat.toFixed(6)}<br>Lon: ${lon.toFixed(6)}</div>
        <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Drag marker to adjust</p><hr class="my-2">
        <div class="d-grid gap-2">
            <button class="btn btn-sm btn-primary" onclick="window.getRouteToSearchLocation(${lat},${lon})"><i class="bi bi-arrow-right-circle me-1"></i> Calculate Route</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.copyCoordinates(${lat},${lon})"><i class="bi bi-clipboard me-1"></i> Copy Coordinates</button>
        </div></div>`);
}

async function updateMarkerLocation(lat, lng) {
    try {
        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, { headers:{'User-Agent':'WashBox'} });
        const d = await r.json();
        document.getElementById('result-coords-text').textContent = `📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        updateSearchMarkerPopup(lat, lng, d.display_name || 'Updated Location');
        showToast('📍 Location updated','info');
    } catch(e) { updateSearchMarkerPopup(lat, lng, 'Adjusted Location'); }
}

async function getRouteToSearchLocation(lat, lon) {
    try {
        showToast('🗺️ Calculating route...','info');
        const branch = getNearestBranch(lat, lon);
        const resp = await fetch(`https://router.project-osrm.org/route/v1/driving/${branch.longitude},${branch.latitude};${lon},${lat}?overview=full&geometries=geojson&steps=true`);
        const data = await resp.json();
        if (data.code !== 'Ok' || !data.routes?.length) throw new Error('Could not calculate route');
        const route = data.routes[0];
        const coords = route.geometry.coordinates.map(c => [c[1],c[0]]);
        if (window._searchRouteLine) logisticsMapInstance.removeLayer(window._searchRouteLine);
        window._searchRouteLine = L.polyline(coords, { color:'#0EA5E9', weight:5, opacity:0.8 }).addTo(logisticsMapInstance);
        logisticsMapInstance.fitBounds(window._searchRouteLine.getBounds().pad(0.1));
        showToast(`📍 Route: ${(route.distance/1000).toFixed(2)} km, ~${Math.round(route.duration/60)} min`,'success');
    } catch(e) { showToast('Route failed: '+e.message,'danger'); }
}

function copyCoordinates(lat, lon) {
    const t = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(()=>showToast('📋 Copied!','success')).catch(()=>_fallbackCopy(t));
    else _fallbackCopy(t);
}

function _fallbackCopy(t) {
    const ta=document.createElement('textarea');
    ta.value=t;
    ta.style.cssText='position:fixed;opacity:0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); showToast('📋 Copied!','success'); } catch(e) {}
    document.body.removeChild(ta);
}

// ================================================================
// PICKUP SELECTION (Multi-Route)
// ================================================================
function togglePickupSelection(pickupId) {
    const id = parseInt(pickupId);
    if (selectedPickups.has(id)) selectedPickups.delete(id);
    else selectedPickups.add(id);
    updateSelectedPickupCount();
    const item = document.getElementById(`pickup-item-${id}`);
    const chk = document.getElementById(`chk-${id}`);
    if (item) item.classList.toggle('selected', selectedPickups.has(id));
    if (chk) chk.checked = selectedPickups.has(id);
}

function updateSelectedPickupCount() {
    const n = selectedPickups.size;
    document.querySelectorAll('#selectedCount, #selectedCountTop').forEach(el => el.textContent = n);
    const badge = document.getElementById('selectedPickupCount');
    if (badge) { badge.textContent = n; badge.style.display = n > 0 ? 'inline-block' : 'none'; }
    const btn = document.getElementById('multiRouteBtn');
    const top = document.getElementById('multiRouteTopBtn');
    if (btn) btn.style.display = n > 1 ? 'block' : 'none';
    if (top) top.style.display = n > 1 ? 'block' : 'none';
}

function selectAllPending() {
    PICKUP_LOCATIONS.forEach(p => {
        if (p.status==='pending'||p.status==='accepted') {
            const id = parseInt(p.id);
            selectedPickups.add(id);
            const item=document.getElementById(`pickup-item-${id}`), chk=document.getElementById(`chk-${id}`);
            if (item) item.classList.add('selected');
            if (chk) chk.checked = true;
        }
    });
    updateSelectedPickupCount();
    showToast(`Selected ${selectedPickups.size} pending pickups`,'success');
}

function clearSelections() {
    selectedPickups.clear();
    document.querySelectorAll('.pickup-list-item').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.pickup-checkbox').forEach(el => el.checked = false);
    updateSelectedPickupCount();
}

// ================================================================
// SINGLE ROUTE — Direct OSRM (road-following)
// ================================================================
async function getRouteToPickup(pickupLat, pickupLng, customerName) {
    const branch = getStaffBranch();
    if (!branch) { showToast('No branch coordinates','danger'); return; }
    clearRoute();
    showToast('Loading route...','info');

    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${branch.longitude},${branch.latitude};${pickupLng},${pickupLat}?overview=full&geometries=geojson&steps=true`;
        const resp = await fetch(url);
        const data = await resp.json();
        if (data.code !== 'Ok' || !data.routes?.length) throw new Error('Route not found');

        const route = data.routes[0];
        const coords = route.geometry.coordinates.map(c => [c[1],c[0]]);
        const distKm = (route.distance/1000).toFixed(2);
        const durMin = Math.ceil(route.duration/60);

        const tMap = getActiveMap();
        routeLayer = L.layerGroup().addTo(tMap);

        // Shadow + colored line
        L.polyline(coords, { color:'#1e293b', weight:7, opacity:0.3, lineCap:'round' }).addTo(routeLayer);
        L.polyline(coords, { color:'#3D3B6B', weight:5, opacity:0.9, lineCap:'round' }).addTo(routeLayer);

        startMarker = L.circleMarker([parseFloat(branch.latitude), parseFloat(branch.longitude)], {
            radius:8, fillColor:'#007BFF', color:'#fff', weight:2, fillOpacity:1
        }).addTo(routeLayer).bindPopup(`<b>${branch.name}</b>`);

        endMarker = L.marker([pickupLat, pickupLng], {
            icon: L.divIcon({ className:'end-marker',
                html:'<div style="background:#28A745;width:30px;height:30px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.2);"><i class="bi bi-geo-alt-fill" style="color:white;"></i></div>',
                iconSize:[30,30], iconAnchor:[15,30]
            })
        }).addTo(routeLayer).bindPopup(`<b>${customerName}</b>`);

        tMap.fitBounds(L.latLngBounds(coords).pad(0.15));
        toggleRouteControls(true);

        // Route details panel
        const panel = document.getElementById('routeDetailsPanel');
        const eta = new Date(Date.now()+route.duration*1000).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
        panel.innerHTML = `<div class="card border-0 shadow-sm"><div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"><h6 class="mb-0">Route Details</h6><button class="btn btn-sm btn-light" onclick="window.closeRouteDetails()"><i class="bi bi-x-lg"></i></button></div><div class="card-body"><div class="mb-3"><h5 class="text-success"><i class="bi bi-signpost"></i> ${distKm} km</h5><p class="text-muted"><i class="bi bi-clock"></i> ${durMin} min</p></div><hr><div class="mb-3"><small class="text-muted">From:</small><p class="mb-0"><b>${branch.name}</b></p></div><div class="mb-3"><small class="text-muted">To:</small><p class="mb-0"><b>${customerName}</b></p><small class="text-muted">${eta} ETA</small></div><hr><div class="d-grid gap-2"><button class="btn btn-outline-primary" onclick="window.printRoute()"><i class="bi bi-printer me-2"></i>Print</button><button class="btn btn-outline-danger" onclick="window.clearRoute()"><i class="bi bi-x-circle me-2"></i>Clear</button></div></div></div>`;
        panel.style.display = 'block';

        showToast(`Route: ${distKm} km, ~${durMin} min`,'success');
    } catch(err) {
        console.error('OSRM error:', err);
        showToast('Route calculation failed: '+err.message,'danger');
    }
}

// ================================================================
// MULTI-STOP ROUTE — OSRM Trip API
// ================================================================
async function getOptimizedMultiRoute() {
    if (selectedPickups.size < 2) { showToast('Select at least 2 pickups','warning'); return; }
    showToast(`Optimizing route for ${selectedPickups.size} stops...`,'info');

    try {
        const branch = getStaffBranch();
        const waypoints = [[branch.longitude, branch.latitude]];
        const stopNames = [branch.name];

        Array.from(selectedPickups).forEach(id => {
            const p = PICKUP_LOCATIONS.find(x => parseInt(x.id) === id);
            if (p?.latitude && p?.longitude) {
                waypoints.push([parseFloat(p.longitude), parseFloat(p.latitude)]);
                stopNames.push(p.customer?.name || `Pickup #${id}`);
            }
        });
        if (waypoints.length < 3) { showToast('Not enough valid coordinates','warning'); return; }

        const coordStr = waypoints.map(w=>`${w[0]},${w[1]}`).join(';');
        const url = `https://router.project-osrm.org/trip/v1/driving/${coordStr}?overview=full&geometries=geojson&steps=true&roundtrip=false&source=first`;

        const resp = await fetch(url);
        const data = await resp.json();
        if (data.code !== 'Ok' || !data.trips?.length) throw new Error('Route optimization failed');

        const trip = data.trips[0];
        const routeData = {
            coordinates: trip.geometry.coordinates.map(c=>[c[1],c[0]]),
            stops: data.waypoints.map((wp,idx) => ({
                name: stopNames[wp.waypoint_index] || `Stop ${idx+1}`,
                type: idx===0 ? 'branch' : 'pickup',
                latitude: wp.location[1],
                longitude: wp.location[0],
                waypoint_index: wp.waypoint_index
            })),
            distance: (trip.distance/1000).toFixed(1)+' km',
            duration: Math.ceil(trip.duration/60)+' mins',
            total_pickups: waypoints.length - 1,
            route_type: 'osrm_trip'
        };

        drawMultiStopRoute(routeData);
        showMultiRouteSummary(routeData);
        showToast('Route optimized successfully!','success');
    } catch(err) {
        console.error('Multi-route error:', err);
        showToast('Route optimization failed: '+err.message,'danger');
    }
}

// ================================================================
// drawMultiStopRoute — IDENTICAL to admin dashboard
// ================================================================
function drawMultiStopRoute(data) {
    clearRoute();
    if (data.optimization && !data.coordinates) data = data.optimization;
    console.log('🗺️ Drawing route with data:', data);

    const tMap = getActiveMap();
    if (!tMap) { showToast('Map not initialized','danger'); return; }

    let coordinates = [];
    if (data.coordinates && Array.isArray(data.coordinates)) coordinates = data.coordinates;
    else if (data.geometry && typeof data.geometry === 'string') coordinates = decodePolyline(data.geometry);
    else if (data.route?.geometry) coordinates = typeof data.route.geometry==='string' ? decodePolyline(data.route.geometry) : data.route.geometry;
    else { showToast('Invalid route data','danger'); return; }

    if (!coordinates?.length || coordinates.length < 2) { showToast('Not enough coordinates','danger'); return; }
    console.log(`✅ Drawing route with ${coordinates.length} points`);

    try {
        routeLayer = L.polyline(coordinates, { color:'#8B5CF6', weight:6, opacity:0.8, lineJoin:'round', lineCap:'round' }).addTo(tMap);
    } catch(e) { showToast('Error drawing route','danger'); return; }

    if (data.stops?.length) {
        data.stops.forEach((stop, idx) => {
            try {
                const isFirst = idx===0, isLast = idx===data.stops.length-1;
                let mc, ih;
                if (isFirst) { mc='#0d6efd'; ih='<i class="bi bi-shop"></i>'; }
                else if (isLast) { mc='#198754'; ih='<i class="bi bi-flag-fill"></i>'; }
                else { mc='#ffc107'; ih=`<span style="font-weight:bold;font-size:14px">${idx}</span>`; }

                let lat, lng;
                if (stop.latitude && stop.longitude) { lat=+stop.latitude; lng=+stop.longitude; }
                else if (Array.isArray(stop.location)) { lng=stop.location[0]; lat=stop.location[1]; }
                else if (stop.lat && stop.lng) { lat=+stop.lat; lng=+stop.lng; }
                else return;

                L.marker([lat,lng], {
                    icon: L.divIcon({
                        className:'stop-marker',
                        html:`<div style="background:${mc};width:36px;height:36px;border-radius:50%;border:3px solid white;color:white;font-size:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);">${ih}</div>`,
                        iconSize:[36,36], iconAnchor:[18,36], popupAnchor:[0,-36]
                    }),
                    title: stop.name || `Stop ${idx+1}`
                }).addTo(tMap).bindPopup(`<div style="min-width:150px;"><strong style="color:${mc};font-size:14px;">${stop.name||`Stop ${idx+1}`}</strong>${stop.type?`<br><span class="badge bg-secondary">${stop.type}</span>`:''}</div>`);
            } catch(e) { console.error('Stop marker error', idx, e); }
        });
    }

    try {
        const bounds = routeLayer.getBounds();
        if (bounds?.isValid?.()) tMap.fitBounds(bounds, { padding:[50,50], maxZoom:15 });
        else if (coordinates.length) tMap.setView(coordinates[0], 13);
    } catch(e) { if (coordinates.length) try { tMap.setView(coordinates[0],13); } catch(ex){} }

    toggleRouteControls(true);
    console.log('✅ Route drawing complete!');
}

// ================================================================
// showMultiRouteSummary — matches admin
// ================================================================
function showMultiRouteSummary(data) {
    if (data.optimization && !data.distance) data = data.optimization;
    const panel = document.getElementById('routeDetailsPanel');
    panel.innerHTML = `<div class="card border-0 shadow-sm"><div class="card-header text-white d-flex justify-content-between align-items-center" style="background:#8B5CF6;"><h6 class="mb-0"><i class="bi bi-route me-2"></i>Optimized Route Summary</h6><button class="btn btn-sm btn-light" onclick="window.closeRouteDetails()"><i class="bi bi-x-lg"></i></button></div><div class="card-body"><div class="row mb-3"><div class="col-6"><small class="text-muted">Total Distance</small><h5>${data.distance}</h5></div><div class="col-6"><small class="text-muted">Total Time</small><h5>${data.duration}</h5></div></div><hr><div class="mb-3"><small class="text-muted">Pickup Laundry:</small><ol class="mt-2 ps-3"><li><strong>Start:</strong> ${getStaffBranch().name}</li>${data.stops?data.stops.slice(1).map((s,i)=>`<li><strong>Stop ${i+1}:</strong> ${s.name||'Pickup '+(i+1)}</li>`).join(''):''}</ol></div><hr><div class="d-grid gap-2"><button class="btn btn-success" onclick="window.startMultiPickupNavigation()"><i class="bi bi-play-circle me-2"></i>Start Multi-Pickup Run</button><button class="btn btn-outline-primary" onclick="window.printRouteSchedule()"><i class="bi bi-printer me-2"></i>Print Schedule</button><button class="btn btn-outline-danger" onclick="window.clearRoute()"><i class="bi bi-x-circle me-2"></i>Clear Route</button></div></div></div>`;
    panel.style.display = 'block';
}

// ================================================================
// AUTO ROUTE / NAVIGATION
// ================================================================
async function autoRouteAllVisible() {
    const pending = PICKUP_LOCATIONS.filter(p=>p.status==='pending'||p.status==='accepted');
    if (pending.length < 2) { showToast('Need at least 2 pending pickups','warning'); return; }
    selectedPickups.clear();
    pending.forEach(p => {
        const id = parseInt(p.id);
        selectedPickups.add(id);
        const it=document.getElementById(`pickup-item-${id}`), chk=document.getElementById(`chk-${id}`);
        if(it)it.classList.add('selected');
        if(chk)chk.checked=true;
    });
    updateSelectedPickupCount();
    await getOptimizedMultiRoute();
}

async function startMultiPickupNavigation() {
    const ids = Array.from(selectedPickups);
    if (!ids.length) { showToast('No pickups selected','warning'); return; }
    try {
        showToast('Starting navigation...','info');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) throw new Error('CSRF token not found');

        const r = await fetch('/staff/pickups/start-multi-navigation', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pickup_ids: ids })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Navigation started! Pickups marked as En Route.','success');
            refreshMapMarkers();
            clearSelections();
        } else showToast(d.error||'Failed','danger');
    } catch(e) { showToast('Navigation failed: ' + e.message,'danger'); }
}

async function startNavigation(pickupId) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) throw new Error('CSRF token not found');

        const r = await fetch(`/staff/pickups/${pickupId}/start-navigation`, {
            method:'POST',
            headers:{
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type':'application/json'
            }
        });
        const d = await r.json();
        if (d.success) {
            showToast('Navigation started!','success');
            refreshMapMarkers();
        } else showToast(d.error||'Failed','danger');
    } catch(e) { showToast('Navigation failed: ' + e.message,'danger'); }
}

// ================================================================
// ROUTE HELPERS
// ================================================================
function clearRoute() {
    [logisticsMapInstance, modalMapInstance].forEach(map => {
        if (!map) return;
        try { if (routeLayer) routeLayer.removeFrom ? routeLayer.removeFrom(map) : map.removeLayer(routeLayer); } catch(e){}
        try { if (startMarker) startMarker.removeFrom ? startMarker.removeFrom(map) : map.removeLayer(startMarker); } catch(e){}
        try { if (endMarker) endMarker.removeFrom ? endMarker.removeFrom(map) : map.removeLayer(endMarker); } catch(e){}
    });
    routeLayer=null; startMarker=null; endMarker=null;
    toggleRouteControls(false); closeRouteDetails();
}

function toggleRouteControls(show) {
    const c = document.querySelector('.route-controls');
    if (c) c.style.display = show ? 'block' : 'none';
}

function closeRouteDetails() {
    const p = document.getElementById('routeDetailsPanel');
    if (p) p.style.display = 'none';
}

function showBranchInfo(id) {
    const b = BRANCHES.find(x=>parseInt(x.id)===parseInt(id))||BRANCHES[0];
    if (b) alert(`${b.name}\n\nAddress: ${b.address||'N/A'}\nPhone: ${b.phone||'N/A'}\nCoords: ${parseFloat(b.latitude).toFixed(4)}, ${parseFloat(b.longitude).toFixed(4)}`);
}

function printRoute() {
    const panel = document.getElementById('routeDetailsPanel');
    if (!panel || panel.style.display==='none') { showToast('No route to print','warning'); return; }
    const w = window.open('','_blank');
    w.document.write(`<html><head><title>Route - ${new Date().toLocaleDateString()}</title><style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto}h1{color:#0EA5E9;border-bottom:3px solid #0EA5E9;padding-bottom:10px}.footer{margin-top:40px;border-top:1px solid #ddd;padding-top:20px;font-size:12px;color:#666;text-align:center}</style></head><body><h1>🗺️ Route Directions</h1>${panel.innerHTML}<div class="footer"><strong>WashBox Laundry Management System</strong><br>Printed ${new Date().toLocaleString()}</div></body></html>`);
    w.document.close(); setTimeout(()=>w.print(), 250);
}

function printRouteSchedule() { printRoute(); }

// ================================================================
// MODAL MAP
// ================================================================
function setupModalMap() {
    const el = document.getElementById('mapModal');
    if (!el) return;
    el.addEventListener('shown.bs.modal', () => {
        if (!modalMapInstance) {
            modalMapInstance = L.map('modalBranchMap').setView(MAP_CENTER, MAP_ZOOM);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OpenStreetMap' }).addTo(modalMapInstance);
        }
        setTimeout(() => {
            modalMapInstance.invalidateSize();
            modalMapInstance.eachLayer(l => { if (l instanceof L.Marker || l instanceof L.Polyline) modalMapInstance.removeLayer(l); });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMapInstance);
            // Add branches
            BRANCHES.forEach((b,i) => {
                if (!b.latitude||!b.longitude) return;
                const isS = parseInt(b.id) === parseInt(STAFF_BRANCH_ID), c=BRANCH_COLORS[i%BRANCH_COLORS.length], sz=isS?48:40;
                L.marker([parseFloat(b.latitude),parseFloat(b.longitude)], {
                    icon: L.divIcon({ className:'branch-marker', html:`<div style="background:${c};width:${sz}px;height:${sz}px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;"></i></div>`, iconSize:[sz,sz] })
                }).addTo(modalMapInstance).bindPopup(`<b>${b.name}</b><br><small>${b.address||''}</small>`);
            });
            // Add pickups
            PICKUP_LOCATIONS.forEach(p => {
                if (!p.latitude||!p.longitude) return;
                const colors={pending:'#FFC107',accepted:'#17A2B8',en_route:'#007BFF',picked_up:'#28A745'};
                L.marker([parseFloat(p.latitude),parseFloat(p.longitude)], {
                    icon: L.divIcon({ className:'pickup-marker', html:`<div style="background:${colors[p.status]||'#6C757D'};width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`, iconSize:[32,32] })
                }).addTo(modalMapInstance).bindPopup(createPickupPopup(p));
            });
            // Fit
            const pts=[];
            BRANCHES.forEach(b=>{if(b.latitude)pts.push([parseFloat(b.latitude),parseFloat(b.longitude)]);});
            PICKUP_LOCATIONS.forEach(p=>{if(p.latitude)pts.push([parseFloat(p.latitude),parseFloat(p.longitude)]);});
            if (pts.length>1) modalMapInstance.fitBounds(L.latLngBounds(pts).pad(0.1));
        }, 300);
    });
}

function refreshModalMap() {
    if (modalMapInstance) {
        modalMapInstance.invalidateSize();
        showToast('Modal map refreshed', 'info');
    }
}

// ================================================================
// CHARTS
// ================================================================
function initializeCharts() {
    const ctx = document.getElementById('revenueTrendChart');
    if (!ctx) {
        console.warn('⚠️ revenueTrendChart canvas not found');
        return;
    }

    if (typeof Chart === 'undefined') {
        console.warn('⚠️ Chart.js not loaded yet, retrying...');
        setTimeout(initializeCharts, 500);
        return;
    }

    // Get revenue trend data from global variable (set by template)
    if (typeof window.REVENUE_TREND_DATA === 'undefined') {
        console.warn('⚠️ No revenue trend data available');
        showEmptyChart(ctx);
        return;
    }

    const revenueTrendData = window.REVENUE_TREND_DATA;
    console.log('📊 Revenue trend data:', revenueTrendData);

    // Check if we have data
    if (!revenueTrendData || Object.keys(revenueTrendData).length === 0) {
        console.warn('⚠️ No revenue trend data available');
        showEmptyChart(ctx);
        return;
    }

    // Format dates for display
    const labels = Object.keys(revenueTrendData).map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    const values = Object.values(revenueTrendData);

    // Destroy existing chart if it exists
    if (DASHBOARD_CONFIG.charts.revenueTrend) {
        DASHBOARD_CONFIG.charts.revenueTrend.destroy();
    }

    // Create new chart
    DASHBOARD_CONFIG.charts.revenueTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Revenue',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 11 },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    console.log('✅ Revenue Trend chart initialized with', values.length, 'data points');
}

function showEmptyChart(ctx) {
    const emptyData = {
        labels: ['No Data'],
        datasets: [{
            label: 'Daily Revenue',
            data: [0],
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148, 163, 184, 0.1)',
            borderWidth: 2,
            fill: true
        }]
    };

    DASHBOARD_CONFIG.charts.revenueTrend = new Chart(ctx, {
        type: 'line',
        data: emptyData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                }
            }
        }
    });
}

// ================================================================
// UTILITIES
// ================================================================
function initializeAutoRefresh() {
    if (DASHBOARD_CONFIG.autoRefresh) {
        setInterval(() => showToast('Dashboard updated','success'), DASHBOARD_CONFIG.refreshInterval);
    }
}

function initializeDateUpdater() {
    const u=()=>{
        const el=document.getElementById('current-date');
        if(el) el.textContent=new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    };
    u();
    setInterval(u, 60000);
}

function showToast(msg, type='info') {
    let c=document.querySelector('.toast-container');
    if (!c) {
        c=document.createElement('div');
        c.className='toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(c);
    }
    const el=document.createElement('div');
    el.className=`toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role','alert');
    el.innerHTML=`<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    c.appendChild(el);

    if (typeof bootstrap !== 'undefined') {
        const t = new bootstrap.Toast(el, {delay:3000});
        t.show();
        el.addEventListener('hidden.bs.toast',()=>el.remove());
    } else {
        // Fallback if Bootstrap is not available
        setTimeout(() => el.remove(), 3000);
    }
}

// ================================================================
// EXPOSE FUNCTIONS TO WINDOW
// ================================================================
window.initializeDashboardData = initializeDashboardData;
window.refreshMapMarkers = refreshMapMarkers;
window.getRouteToPickup = getRouteToPickup;
window.startNavigation = startNavigation;
window.closeRouteDetails = closeRouteDetails;
window.clearRoute = clearRoute;
window.printRoute = printRoute;
window.printRouteSchedule = printRouteSchedule;
window.showBranchInfo = showBranchInfo;
window.togglePickupSelection = togglePickupSelection;
window.getOptimizedMultiRoute = getOptimizedMultiRoute;
window.autoRouteAllVisible = autoRouteAllVisible;
window.selectAllPending = selectAllPending;
window.clearSelections = clearSelections;
window.startMultiPickupNavigation = startMultiPickupNavigation;
window.searchMapAddress = searchMapAddress;
window.getRouteToSearchLocation = getRouteToSearchLocation;
window.updateMarkerLocation = updateMarkerLocation;
window.copyCoordinates = copyCoordinates;
window.refreshModalMap = refreshModalMap;

console.log('✅ Staff Dashboard JS loaded');
