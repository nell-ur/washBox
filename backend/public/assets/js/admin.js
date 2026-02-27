// admin.js - Admin Dashboard JavaScript

// Dashboard Configuration
const DASHBOARD_CONFIG = {
    autoRefresh: true,
    refreshInterval: 30000,
    charts: {
        revenue: null,
        customerSource: null,
        customerBranch: null,
        fullService: null,
        selfService: null,
        addonService: null,
    },
    cacheKey: "dashboard_active_tab",
};

// Global Variables
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
let currentDraggableMarker = null;

// These will be set by the initializeDashboardData function
let BRANCHES = [];
const branchMarkers = [];
let MAP_CENTER = [9.3068, 123.3054];
let MAP_ZOOM = 13;

/**
 * Initialize dashboard with data from server
 */
function initializeDashboardData(branches, stats) {
    BRANCHES = branches || [];

    // Calculate map center
    if (BRANCHES.length > 0) {
        MAP_CENTER = [
            BRANCHES.reduce(
                (sum, b) => sum + (parseFloat(b.latitude) || 0),
                0,
            ) / BRANCHES.length,
            BRANCHES.reduce(
                (sum, b) => sum + (parseFloat(b.longitude) || 0),
                0,
            ) / BRANCHES.length,
        ];
        MAP_ZOOM = BRANCHES.length > 1 ? 12 : 13;
    }

    console.log(`📍 Loaded ${BRANCHES.length} branch location(s) for map`);
}

/**
 * Find the nearest branch to a given lat/lon (for routing)
 */
function getNearestBranch(lat, lon) {
    if (BRANCHES.length === 0)
        return {
            name: "WashBox Branch",
            latitude: 9.3068,
            longitude: 123.3054,
            address: "",
            phone: "",
        };

    let nearest = BRANCHES[0];
    let minDist = Infinity;

    BRANCHES.forEach((b) => {
        const d =
            Math.pow(parseFloat(b.latitude) - lat, 2) +
            Math.pow(parseFloat(b.longitude) - lon, 2);
        if (d < minDist) {
            minDist = d;
            nearest = b;
        }
    });

    return nearest;
}

/**
 * Get branch by database ID
 */
function getBranchById(id) {
    return (
        BRANCHES.find((b) => parseInt(b.id) === parseInt(id)) ||
        BRANCHES[0] ||
        null
    );
}

// Initialize everything when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    initializeCharts();
    initializeTabs();
    initializeAutoRefresh();
    initializeDateUpdater();
    initMapAddressSearch();

    // Initialize map if on operations tab
    if (
        document.getElementById("operations-tab")?.classList.contains("active")
    ) {
        setTimeout(() => {
            initLogisticsMap();
        }, 500);
    }

    // Setup modal map
    setupModalMap();

    console.log("✅ Dashboard initialized with sticky tabs!");
});

/**
 * TAB MANAGEMENT
 */
function initializeTabs() {
    const activeTab =
        localStorage.getItem(DASHBOARD_CONFIG.cacheKey) || "overview";
    const tabButton = document.getElementById(`${activeTab}-tab`);

    if (tabButton && typeof bootstrap !== "undefined") {
        const tab = new bootstrap.Tab(tabButton);
        tab.show();
    }

    document.querySelectorAll('[data-bs-toggle="pill"]').forEach((tab) => {
        tab.addEventListener("shown.bs.tab", function (event) {
            const activeTabName = event.target
                .getAttribute("id")
                .replace("-tab", "");
            localStorage.setItem(DASHBOARD_CONFIG.cacheKey, activeTabName);

            // Initialize map if operations tab is shown
            if (activeTabName === "operations") {
                setTimeout(() => {
                    if (!logisticsMapInstance) {
                        initLogisticsMap();
                    } else {
                        logisticsMapInstance.invalidateSize();
                    }
                }, 200);
            }

            // Animate rating bars when customers tab is shown
            if (activeTabName === "customers") {
                setTimeout(animateRatingBars, 150);
            }
        });
    });

    console.log("✅ Tab management initialized");
}

/**
 * MAP ADDRESS SEARCH & GEOCODING
 */
function initMapAddressSearch() {
    const searchInput = document.getElementById("map-address-search");
    if (!searchInput) return;

    // Add enter key support
    searchInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            searchMapAddress();
        }
    });

    console.log("✅ Map address search initialized");
}

/**
 * Search and geocode address using Nominatim (OpenStreetMap)
 */
async function searchMapAddress() {
    const input = document.getElementById("map-address-search");
    const resultsDiv = document.getElementById("search-result-display");
    const address = input.value.trim();

    if (!address || address.length < 3) {
        showToast("Please enter at least 3 characters", "warning");
        return;
    }

    try {
        // Show loading
        resultsDiv.style.display = "block";
        document.getElementById("result-address-text").textContent =
            "Searching...";
        document.getElementById("result-coords-text").textContent =
            "🔍 Looking up location";

        // Call Nominatim API (OpenStreetMap Free Geocoding)
        const searchQuery = encodeURIComponent(address + ", Philippines");
        const apiUrl = `https://nominatim.openstreetmap.org/search?q=${searchQuery}&format=json&limit=1&countrycodes=ph&addressdetails=1`;

        const response = await fetch(apiUrl, {
            headers: {
                "User-Agent": "WashBox Laundry Management System",
            },
        });

        if (!response.ok) {
            throw new Error("Geocoding service unavailable");
        }

        const data = await response.json();

        if (!data || data.length === 0) {
            throw new Error(
                "Address not found. Please try a different search.",
            );
        }

        const result = data[0];
        const latitude = parseFloat(result.lat);
        const longitude = parseFloat(result.lon);

        // Display results
        document.getElementById("result-address-text").textContent =
            result.display_name;
        document.getElementById("result-coords-text").textContent =
            `📍 ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;

        // Add marker to map
        addDraggableSearchMarker(latitude, longitude, result.display_name);

        // Pan map to location with animation
        if (logisticsMapInstance) {
            logisticsMapInstance.flyTo([latitude, longitude], 17, {
                duration: 1.5,
                easeLinearity: 0.25,
            });
        }

        showToast("📍 Location found!", "success");
    } catch (error) {
        console.error("Search error:", error);
        resultsDiv.style.display = "none";
        showToast("Search failed: " + error.message, "danger");
    }
}

/**
 * Add draggable marker for searched location
 */
function addDraggableSearchMarker(lat, lon, address) {
    // Remove previous search marker if exists
    if (searchResultMarker && logisticsMapInstance) {
        logisticsMapInstance.removeLayer(searchResultMarker);
    }

    // Create draggable marker
    searchResultMarker = L.marker([lat, lon], {
        icon: L.divIcon({
            className: "search-result-marker",
            html: `<div class="search-marker-pulse" style="background:#10B981;width:44px;height:44px;border-radius:50%;border:4px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-geo-alt-fill" style="color:white;font-size:22px;"></i>
                   </div>`,
            iconSize: [44, 44],
            iconAnchor: [22, 44],
        }),
        draggable: true,
        autoPan: true,
    }).addTo(logisticsMapInstance);

    // Update popup when marker is dragged
    searchResultMarker.on("dragend", function (event) {
        const newPos = event.target.getLatLng();
        updateMarkerLocation(newPos.lat, newPos.lng);
    });

    // Add popup with actions
    updateSearchMarkerPopup(lat, lon, address);

    searchResultMarker.openPopup();
}

/**
 * Update marker popup with new coordinates
 */
function updateSearchMarkerPopup(lat, lon, address) {
    if (!searchResultMarker) return;

    const popupContent = `
        <div style="min-width: 280px;">
            <h6 class="mb-2"><b>📍 Selected Location</b></h6>
            <p class="mb-2 small text-muted">${address}</p>
            <div class="alert alert-info py-2 px-2 mb-2 small">
                <strong>Coordinates:</strong><br>
                Lat: ${lat.toFixed(6)}<br>
                Lon: ${lon.toFixed(6)}
            </div>
            <p class="small text-muted mb-2">
                <i class="bi bi-info-circle me-1"></i>Drag marker to adjust location
            </p>
            <hr class="my-2">
            <div class="d-grid gap-2">
                <button class="btn btn-sm btn-success" onclick="createPickupAtLocation(${lat}, ${lon}, '${address.replace(/'/g, "\\'")}')">
                    <i class="bi bi-plus-circle me-1"></i> Create Pickup Here
                </button>
                <button class="btn btn-sm btn-primary" onclick="getRouteToSearchLocation(${lat}, ${lon})">
                    <i class="bi bi-arrow-right-circle me-1"></i> Calculate Route
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyCoordinates(${lat}, ${lon})">
                    <i class="bi bi-clipboard me-1"></i> Copy Coordinates
                </button>
            </div>
        </div>
    `;

    searchResultMarker.bindPopup(popupContent);
}

/**
 * Update location when marker is dragged
 */
async function updateMarkerLocation(newLat, newLng) {
    try {
        // Reverse geocode to get address
        const apiUrl = `https://nominatim.openstreetmap.org/reverse?lat=${newLat}&lon=${newLng}&format=json`;

        const response = await fetch(apiUrl, {
            headers: {
                "User-Agent": "WashBox Laundry Management System",
            },
        });

        const data = await response.json();
        const newAddress = data.display_name || "Updated Location";

        // Update display
        document.getElementById("result-coords-text").textContent =
            `📍 ${newLat.toFixed(6)}, ${newLng.toFixed(6)}`;

        // Update popup
        updateSearchMarkerPopup(newLat, newLng, newAddress);

        showToast("📍 Location updated", "info");
    } catch (error) {
        console.error("Reverse geocoding error:", error);
        updateSearchMarkerPopup(newLat, newLng, "Adjusted Location");
    }
}

/**
 * Create pickup at selected location
 */
function createPickupAtLocation(lat, lon, address) {
    try {
        // Store in sessionStorage
        sessionStorage.setItem(
            "pickup_location",
            JSON.stringify({
                latitude: lat,
                longitude: lon,
                address: address,
            }),
        );

        // Redirect to pickup creation page with parameters
        const url = `/admin/pickups/create?lat=${lat}&lon=${lon}&address=${encodeURIComponent(address)}`;
        window.location.href = url;
    } catch (error) {
        console.error("Error:", error);
        showToast("Failed to prepare pickup creation", "danger");
    }
}

/**
 * Calculate route to searched location
 */
async function getRouteToSearchLocation(lat, lon) {
    try {
        showToast("🗺️ Calculating route...", "info");

        // Find the nearest branch to the destination
        const branch = getNearestBranch(lat, lon);
        const branchCoords = [branch.latitude, branch.longitude];

        // Use OSRM for routing
        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${branchCoords[1]},${branchCoords[0]};${lon},${lat}?overview=full&geometries=geojson&steps=true`;

        const response = await fetch(osrmUrl);
        const data = await response.json();

        if (data.code !== "Ok" || !data.routes || data.routes.length === 0) {
            throw new Error("Could not calculate route");
        }

        const route = data.routes[0];
        const coordinates = route.geometry.coordinates.map((coord) => [
            coord[1],
            coord[0],
        ]);

        // Clear previous route
        if (window.currentRouteLine) {
            logisticsMapInstance.removeLayer(window.currentRouteLine);
        }

        // Draw route on map
        window.currentRouteLine = L.polyline(coordinates, {
            color: "#0EA5E9",
            weight: 5,
            opacity: 0.8,
            smoothFactor: 1,
        }).addTo(logisticsMapInstance);

        // Fit map to show entire route
        logisticsMapInstance.fitBounds(
            window.currentRouteLine.getBounds().pad(0.1),
        );

        // Show route info
        const distance = (route.distance / 1000).toFixed(2);
        const duration = Math.round(route.duration / 60);

        showToast(`📍 Route: ${distance} km, ~${duration} minutes`, "success");
    } catch (error) {
        console.error("Routing error:", error);
        showToast("Failed to calculate route", "danger");
    }
}

/**
 * Copy coordinates to clipboard
 */
function copyCoordinates(lat, lon) {
    const text = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;

    if (navigator.clipboard) {
        navigator.clipboard
            .writeText(text)
            .then(() => {
                showToast("📋 Coordinates copied to clipboard!", "success");
            })
            .catch((err) => {
                fallbackCopy(text);
            });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand("copy");
        showToast("📋 Coordinates copied!", "success");
    } catch (err) {
        showToast("Failed to copy coordinates", "danger");
    }
    document.body.removeChild(textarea);
}

/**
 * MULTI-STOP ROUTE OPTIMIZATION FUNCTIONS
 */
function togglePickupSelection(pickupId) {
    const id = parseInt(pickupId);
    if (selectedPickups.has(id)) {
        selectedPickups.delete(id);
    } else {
        selectedPickups.add(id);
    }

    updateSelectedPickupCount();

    // Highlight marker on map
    const marker = findMarkerByPickupId(id);
    if (marker && marker.getElement()) {
        if (selectedPickups.has(id)) {
            marker.getElement().classList.add("selected-pickup");
        } else {
            marker.getElement().classList.remove("selected-pickup");
        }
    }
}

function findMarkerByPickupId(pickupId) {
    return pickupMarkers.find((marker) => {
        const popupContent = marker.getPopup()?.getContent();
        return popupContent && popupContent.includes(`pickup-${pickupId}`);
    });
}

function updateSelectedPickupCount() {
    const count = selectedPickups.size;

    // Update all count displays
    document
        .querySelectorAll("#selectedCount, #selectedCountTop")
        .forEach((el) => {
            if (el) el.textContent = count;
        });

    const countBadge = document.getElementById("selectedPickupCount");
    if (countBadge) {
        countBadge.textContent = count;
        countBadge.style.display = count > 0 ? "inline-block" : "none";
    }

    // Show/hide multi-route buttons
    const multiRouteBtn = document.getElementById("multiRouteBtn");
    const multiRouteTopBtn = document.getElementById("multiRouteTopBtn");

    if (multiRouteBtn)
        multiRouteBtn.style.display = count > 1 ? "block" : "none";
    if (multiRouteTopBtn)
        multiRouteTopBtn.style.display = count > 1 ? "block" : "none";
}

async function getOptimizedMultiRoute() {
    if (selectedPickups.size < 2) {
        showToast(
            "Please select at least 2 pickups for route optimization",
            "warning",
        );
        return;
    }

    const pickupIds = Array.from(selectedPickups);

    try {
        showToast(
            "Optimizing route for " + pickupIds.length + " stops...",
            "info",
        );

        const response = await fetch("/admin/logistics/optimize-route", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({ pickup_ids: pickupIds }),
        });

        if (!response.ok) {
            console.error(
                "Route optimization failed with status:",
                response.status,
            );
            showToast("Server error: " + response.statusText, "danger");
            return;
        }

        const data = await response.json();
        console.log("Route optimization response:", data);

        if (data.success) {
            const routeData = data.optimization || data;
            drawMultiStopRoute(routeData);
            showMultiRouteSummary(routeData);
            showToast("Route optimized successfully!", "success");
        } else {
            console.error(
                "Route optimization failed:",
                data.error || data.message,
            );
            showToast(
                data.error || data.message || "Failed to optimize route",
                "danger",
            );
        }
    } catch (error) {
        console.error("Route optimization network error:", error);
        showToast(
            "Network error: " + (error.message || "Could not reach server"),
            "danger",
        );
    }
}

function drawMultiStopRoute(data) {
    clearRoute();

    // Safety: unwrap if caller passed the full response instead of optimization
    if (data.optimization && !data.coordinates) {
        data = data.optimization;
    }

    console.log("🗺️ Drawing route with data:", data);

    const isModalOpen = document
        .getElementById("mapModal")
        ?.classList.contains("show");
    const targetMap = isModalOpen ? modalMapInstance : logisticsMapInstance;

    if (!targetMap) {
        console.error("❌ No map instance found");
        showToast("Map not initialized", "danger");
        return;
    }

    // Handle different coordinate formats
    let coordinates = [];

    if (data.coordinates && Array.isArray(data.coordinates)) {
        // Direct coordinates array [[lat, lng], [lat, lng], ...]
        coordinates = data.coordinates;
        console.log("✅ Using direct coordinates array");
    } else if (data.geometry && typeof data.geometry === "string") {
        // Encoded polyline string (from OSRM)
        coordinates = decodePolyline(data.geometry);
        console.log("✅ Decoded polyline geometry");
    } else if (data.route && data.route.geometry) {
        // Nested route object
        if (typeof data.route.geometry === "string") {
            coordinates = decodePolyline(data.route.geometry);
        } else {
            coordinates = data.route.geometry;
        }
        console.log("✅ Using nested route geometry");
    } else {
        console.error("❌ No valid geometry data found:", data);
        showToast("Invalid route data - missing coordinates", "danger");
        return;
    }

    // Validate coordinates
    if (!coordinates || !Array.isArray(coordinates) || coordinates.length < 2) {
        console.error("❌ Invalid coordinates array:", coordinates);
        showToast(
            "Not enough coordinates to draw route (need at least 2 points)",
            "danger",
        );
        return;
    }

    console.log(
        "✅ Drawing route with " + coordinates.length + " coordinate points",
    );

    // Draw the route line
    try {
        routeLayer = L.polyline(coordinates, {
            color: "#8B5CF6",
            weight: 6,
            opacity: 0.8,
            lineJoin: "round",
            lineCap: "round",
        }).addTo(targetMap);

        console.log("✅ Route line drawn successfully");
    } catch (error) {
        console.error("❌ Error drawing polyline:", error);
        showToast("Error drawing route line", "danger");
        return;
    }

    // Add markers for each stop
    if (data.stops && Array.isArray(data.stops) && data.stops.length > 0) {
        console.log("Adding " + data.stops.length + " stop markers");

        data.stops.forEach((stop, index) => {
            try {
                const isFirst = index === 0;
                const isLast = index === data.stops.length - 1;

                // Determine marker style
                let markerColor, iconHtml, iconClass;

                if (isFirst) {
                    // Branch (start point)
                    markerColor = "#0d6efd"; // Bootstrap primary blue
                    iconHtml = '<i class="bi bi-shop"></i>';
                    iconClass = "branch-marker";
                } else if (isLast) {
                    // Last pickup
                    markerColor = "#198754"; // Bootstrap success green
                    iconHtml = '<i class="bi bi-flag-fill"></i>';
                    iconClass = "last-marker";
                } else {
                    // Intermediate pickups
                    markerColor = "#ffc107"; // Bootstrap warning yellow
                    iconHtml = `<span style="font-weight:bold;font-size:14px">${index}</span>`;
                    iconClass = "pickup-marker";
                }

                // Extract coordinates from stop
                let lat, lng;

                if (
                    Array.isArray(stop.location) &&
                    stop.location.length === 2
                ) {
                    // [lng, lat] or [lat, lng] format
                    // Assume [lng, lat] from backend, Leaflet needs [lat, lng]
                    lng = stop.location[0];
                    lat = stop.location[1];
                } else if (stop.latitude && stop.longitude) {
                    // {latitude, longitude} object format
                    lat = parseFloat(stop.latitude);
                    lng = parseFloat(stop.longitude);
                } else if (stop.lat && stop.lng) {
                    // {lat, lng} object format
                    lat = parseFloat(stop.lat);
                    lng = parseFloat(stop.lng);
                } else {
                    console.warn("⚠️ Invalid stop location format:", stop);
                    return; // Skip this marker
                }

                // Create marker
                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: `stop-marker ${iconClass}`,
                        html: `<div style="
                            background: ${markerColor};
                            width: 36px;
                            height: 36px;
                            border-radius: 50%;
                            border: 3px solid white;
                            color: white;
                            font-size: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        ">${iconHtml}</div>`,
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36],
                    }),
                    title: stop.name || `Stop ${index + 1}`,
                }).addTo(targetMap);

                // Add popup with stop details
                const popupContent = `
                    <div style="min-width: 150px;">
                        <strong style="color: ${markerColor}; font-size: 14px;">
                            ${stop.name || `Stop ${index + 1}`}
                        </strong>
                        ${stop.address ? `<br><small class="text-muted">${stop.address}</small>` : ""}
                        ${stop.type ? `<br><span class="badge bg-secondary">${stop.type}</span>` : ""}
                    </div>
                `;

                marker.bindPopup(popupContent);

                // Store reference
                pickupMarkers.push(marker);
            } catch (error) {
                console.error(
                    "❌ Error adding marker for stop",
                    index,
                    ":",
                    error,
                );
            }
        });

        console.log("✅ Added " + pickupMarkers.length + " markers");
    } else {
        console.warn("⚠️ No stops data to add markers");
    }

    // Fit map bounds to show entire route
    try {
        const bounds = routeLayer.getBounds();

        if (bounds && bounds.isValid && bounds.isValid()) {
            targetMap.fitBounds(bounds, {
                padding: [50, 50],
                maxZoom: 15, // Don't zoom in too much
            });
            console.log("✅ Map fitted to route bounds");
        } else {
            console.warn("⚠️ Invalid bounds from route layer, using fallback");

            // Fallback: center on first coordinate
            if (coordinates.length > 0) {
                targetMap.setView(coordinates[0], 13);
            }
        }
    } catch (error) {
        console.error("❌ Error fitting bounds:", error);
        // Fallback: try to center on first coordinate
        if (coordinates.length > 0) {
            try {
                targetMap.setView(coordinates[0], 13);
            } catch (e) {
                console.error("❌ Even fallback centering failed:", e);
            }
        }
    }

    // Update ETA display
    const duration = data.duration || data.estimated_time || "Unknown";
    const distance = data.distance || "Unknown";

    updateETADisplay(`${duration} • ${distance}`);

    console.log("✅ Route drawing complete!");
    console.log("📊 Route summary:", {
        coordinates: coordinates.length,
        stops: data.stops?.length || 0,
        duration: duration,
        distance: distance,
    });
}

/**
 * Helper function to update ETA display
 */
function updateETADisplay(text) {
    const etaElement = document.getElementById("routeETA");
    if (etaElement) {
        etaElement.textContent = text;
    }
}

function showMultiRouteSummary(data) {
    // Safety: unwrap if caller passed the full response
    if (data.optimization && !data.distance) {
        data = data.optimization;
    }

    const summary = `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-route me-2"></i>Optimized Route Summary</h6>
                <button class="btn btn-sm btn-light" onclick="closeRouteDetails()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">Total Distance</small>
                        <h5>${data.distance}</h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Total Time</small>
                        <h5>${data.duration}</h5>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">Pickup Laundry:</small>
                    <ol class="mt-2 ps-3">
                        <li><strong>Start:</strong> ${BRANCHES.length > 0 ? BRANCHES[0].name : "WashBox Branch"}</li>
                        ${
                            data.stops
                                ? data.stops
                                      .slice(1)
                                      .map(
                                          (stop, idx) =>
                                              `<li><strong>Stop ${idx + 1}:</strong> ${stop.name || "Pickup Location " + (idx + 1)}</li>`,
                                      )
                                      .join("")
                                : ""
                        }
                    </ol>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="startMultiPickupNavigation()">
                        <i class="bi bi-play-circle me-2"></i>Start Multi-Pickup Run
                    </button>
                    <button class="btn btn-outline-primary" onclick="printRouteSchedule()">
                        <i class="bi bi-printer me-2"></i>Print Schedule
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearRoute()">
                        <i class="bi bi-x-circle me-2"></i>Clear Route
                    </button>
                </div>
            </div>
        </div>
    `;

    const routeDetails = document.getElementById("routeDetailsPanel");
    if (routeDetails) {
        routeDetails.innerHTML = summary;
        routeDetails.style.display = "block";
    }
}

async function startMultiPickupNavigation() {
    const pickupIds = Array.from(selectedPickups);

    if (pickupIds.length === 0) {
        showToast("No pickups selected", "warning");
        return;
    }

    try {
        showToast("Starting multi-pickup navigation...", "info");

        const response = await fetch("/admin/logistics/start-multi-pickup", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({ pickup_ids: pickupIds }),
        });

        const data = await response.json();

        if (data.success) {
            showToast(
                'Multi-pickup navigation started! All selected pickups are now marked as "En Route".',
                "success",
            );
            // Refresh the pickups list
            refreshMapMarkers();
            // Clear selection
            clearSelections();
        } else {
            showToast(data.error || "Failed to start navigation", "danger");
        }
    } catch (error) {
        console.error("Navigation error:", error);
        showToast("Failed to start navigation", "danger");
    }
}

async function autoRouteAllVisible() {
    try {
        showToast("Finding optimal route for all pending pickups...", "info");

        // Get all pending pickup IDs from the current view
        const pendingPickups = window.PENDING_PICKUPS || [];

        if (!pendingPickups || pendingPickups.length < 2) {
            showToast(
                "Need at least 2 pending pickups to optimize route",
                "warning",
            );
            return;
        }

        const pickupIds = pendingPickups.map((p) => p.id);

        // Select all pending pickups
        selectedPickups.clear();
        pickupIds.forEach((id) => selectedPickups.add(id));
        updateSelectedPickupCount();

        const response = await fetch("/admin/logistics/optimize-route", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
                Accept: "application/json",
            },
            body: JSON.stringify({
                pickup_ids: pickupIds,
            }),
        });

        if (!response.ok) {
            throw new Error(`Server returned ${response.status}`);
        }

        const data = await response.json();

        console.log("Route optimization response:", data); // Debug log

        if (data.success) {
            drawMultiStopRoute(data.optimization);
            showMultiRouteSummary(data.optimization);
            showToast(
                "Route optimized for " + pickupIds.length + " pickups!",
                "success",
            );
        } else {
            showToast(data.error || "Failed to optimize route", "danger");
        }
    } catch (error) {
        console.error("Auto-route error:", error);
        showToast("Failed to optimize route: " + error.message, "danger");
    }
}

async function selectAllPending() {
    try {
        const response = await fetch("/admin/logistics/pending-pickups");
        const data = await response.json();

        if (data.success && data.pickups) {
            selectedPickups.clear();
            data.pickups.forEach((pickup) => {
                selectedPickups.add(parseInt(pickup.id));
            });
            updateSelectedPickupCount();
            showToast(
                `Selected ${data.pickups.length} pending pickups`,
                "success",
            );
        }
    } catch (error) {
        console.error("Error selecting pending pickups:", error);
        showToast("Failed to load pending pickups", "danger");
    }
}

function clearSelections() {
    selectedPickups.clear();
    updateSelectedPickupCount();
    // Remove selected class from all markers
    pickupMarkers.forEach((marker) => {
        if (marker.getElement()) {
            marker.getElement().classList.remove("selected-pickup");
        }
    });
}

function printRouteSchedule() {
    // Create a printable version of the route
    const routeDetails = document.getElementById("routeDetailsPanel");
    if (routeDetails) {
        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
            <html>
                <head>
                    <title>Route Schedule - ${new Date().toLocaleDateString()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #333; }
                        .route-info { margin: 20px 0; }
                        .stop-list { margin: 15px 0; }
                        .stop-item { padding: 5px 0; border-bottom: 1px solid #eee; }
                        .footer { margin-top: 30px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    ${routeDetails.innerHTML}
                    <div class="footer">
                        Printed on ${new Date().toLocaleString()}
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

/**
 * Print single route directions
 */
function printRoute() {
    const routeDetails = document.getElementById("routeDetailsPanel");
    if (!routeDetails || routeDetails.style.display === "none") {
        showToast(
            "No route to print. Please calculate a route first.",
            "warning",
        );
        return;
    }

    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
        <html>
            <head>
                <title>Route Directions - ${new Date().toLocaleDateString()}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        max-width: 800px;
                        margin: 0 auto;
                    }
                    h1 {
                        color: #0EA5E9;
                        border-bottom: 3px solid #0EA5E9;
                        padding-bottom: 10px;
                    }
                    .route-header {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        margin: 20px 0;
                    }
                    .route-info {
                        margin: 15px 0;
                        display: flex;
                        justify-content: space-between;
                    }
                    .info-item {
                        padding: 10px;
                        background: white;
                        border-radius: 5px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    .info-label {
                        font-size: 12px;
                        color: #666;
                        text-transform: uppercase;
                    }
                    .info-value {
                        font-size: 18px;
                        font-weight: bold;
                        color: #333;
                    }
                    .directions-list {
                        margin: 20px 0;
                        list-style: none;
                        padding: 0;
                    }
                    .direction-item {
                        padding: 15px;
                        border-left: 3px solid #0EA5E9;
                        margin-bottom: 10px;
                        background: #f8f9fa;
                        border-radius: 0 5px 5px 0;
                    }
                    .direction-item:hover {
                        background: #e9ecef;
                    }
                    .step-number {
                        display: inline-block;
                        background: #0EA5E9;
                        color: white;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        text-align: center;
                        line-height: 30px;
                        margin-right: 10px;
                        font-weight: bold;
                    }
                    .footer {
                        margin-top: 40px;
                        padding-top: 20px;
                        border-top: 1px solid #ddd;
                        font-size: 12px;
                        color: #666;
                        text-align: center;
                    }
                    @media print {
                        body { padding: 0; }
                        .direction-item { break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <h1>🗺️ Route Directions</h1>
                ${routeDetails.innerHTML}
                <div class="footer">
                    <strong>WashBox Laundry Management System</strong><br>
                    Printed on ${new Date().toLocaleString()}<br>
                    <em>For internal use only</em>
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();

    // Wait for content to load then print
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

/**
 * LOGISTICS MAP FUNCTIONS
 */
function initLogisticsMap() {
    const container = document.getElementById("logisticsMap");
    if (!container) {
        console.error("Map container not found");
        return;
    }

    // Clear existing map
    if (logisticsMapInstance) {
        logisticsMapInstance.remove();
        logisticsMapInstance = null;
    }

    // Initialize map centered on all branches
    logisticsMapInstance = L.map("logisticsMap").setView(MAP_CENTER, MAP_ZOOM);

    // Add OpenStreetMap tiles
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
        maxZoom: 19,
    }).addTo(logisticsMapInstance);

    // Initialize marker cluster for pickups
    pickupCluster = L.markerClusterGroup({ chunkedLoading: true });
    logisticsMapInstance.addLayer(pickupCluster);

    // Add cluster toggle control
    createClusterToggleControl(
        logisticsMapInstance,
        "clusterToggleAdmin",
        true,
    );

    // Always add ALL branch markers first
    addBranchMarkers();

    // Load pickup data
    loadPickupsAndRender();
}

function loadPickupsAndRender() {
    console.log("Loading pickup data...");

    // Get pickup data from global variable
    const pickupData = window.PENDING_PICKUPS || [];

    console.log("Pickup data:", pickupData);

    if (!pickupData || pickupData.length === 0) {
        console.log("No pickup data available");
        addSamplePickups();
        return;
    }

    // Clear existing pickup markers (branch markers persist)
    clearPickupMarkers();

    // Add pickup markers
    pickupData.forEach((pickup) => {
        if (pickup.latitude && pickup.longitude) {
            addPickupMarker(pickup);
        } else {
            console.warn("Pickup missing coordinates:", pickup);
        }
    });

    // Fit bounds to show all markers
    fitMapToMarkers();
}

function fitMapToMarkers() {
    // Prefer cluster bounds if cluster has markers
    if (pickupCluster && pickupCluster.getLayers().length > 0) {
        const bounds = pickupCluster.getBounds();
        if (bounds.isValid && bounds.isValid()) {
            logisticsMapInstance.fitBounds(bounds.pad(0.1));
            return;
        }
    }

    if (pickupMarkers.length === 0) return;

    const group = new L.featureGroup(pickupMarkers);
    logisticsMapInstance.fitBounds(group.getBounds().pad(0.1));
}

function clearPickupMarkers() {
    // Clear cluster layers first
    if (pickupCluster && logisticsMapInstance) {
        pickupCluster.clearLayers();
    }

    // Fallback for any individual markers
    pickupMarkers.forEach((marker) => {
        if (marker && logisticsMapInstance) {
            try {
                logisticsMapInstance.removeLayer(marker);
            } catch (e) {
                // ignore
            }
        }
    });
    pickupMarkers = [];
}

function addBranchMarkers() {
    // Color palette so each branch is visually distinct
    const branchColors = [
        "#007BFF",
        "#10B981",
        "#8B5CF6",
        "#F59E0B",
        "#EF4444",
    ];

    BRANCHES.forEach((branch, index) => {
        const color = branchColors[index % branchColors.length];

        const marker = L.marker(
            [parseFloat(branch.latitude), parseFloat(branch.longitude)],
            {
                icon: L.divIcon({
                    className: "branch-marker",
                    html: `<div style="background:${color};width:40px;height:40px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;font-size:18px;"></i></div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                }),
            },
        ).addTo(logisticsMapInstance).bindPopup(`
            <div style="min-width:220px">
                <h6 class="mb-1"><b>${branch.name}</b></h6>
                <p class="mb-1 small text-muted">${branch.address || "Negros Oriental, Philippines"}</p>
                ${branch.phone ? `<p class="mb-1 small"><i class="bi bi-telephone me-1"></i>${branch.phone}</p>` : ""}
                <hr class="my-2">
                <div class="d-grid gap-1">
                    <button class="btn btn-sm btn-primary" onclick="showBranchInfo(${branch.id})">
                        <i class="bi bi-info-circle"></i> Branch Info
                    </button>
                </div>
            </div>
        `);

        branchMarkers.push(marker);
        pickupMarkers.push(marker);
    });

    // Fallback if no branches in database yet
    if (BRANCHES.length === 0) {
        const fallback = L.marker([9.3068, 123.3054], {
            icon: L.divIcon({
                className: "branch-marker",
                html: '<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;font-size:18px;"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
            }),
        })
            .addTo(logisticsMapInstance)
            .bindPopup("<b>WashBox Laundry</b>");
        pickupMarkers.push(fallback);
    }

    // Fit map to show all branches
    if (branchMarkers.length > 1) {
        const group = new L.featureGroup(branchMarkers);
        logisticsMapInstance.fitBounds(group.getBounds().pad(0.2));
    }
}

function addPickupMarker(pickup) {
    const statusColors = {
        pending: "#FFC107",
        accepted: "#17A2B8",
        en_route: "#007BFF",
        picked_up: "#28A745",
        cancelled: "#DC3545",
    };

    const color = statusColors[pickup.status] || "#6C757D";

    const marker = L.marker(
        [parseFloat(pickup.latitude), parseFloat(pickup.longitude)],
        {
            icon: L.divIcon({
                className: "pickup-marker",
                html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;" id="marker-${pickup.id}"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
            }),
        },
    ).bindPopup(createPickupPopup(pickup));

    // Add to cluster if available, otherwise add to the map
    if (pickupCluster) {
        pickupCluster.addLayer(marker);
    } else {
        marker.addTo(logisticsMapInstance);
    }

    pickupMarkers.push(marker);
}

function createPickupPopup(pickup) {
    const isSelected = selectedPickups.has(parseInt(pickup.id));
    const selectBtnClass = isSelected ? "btn-purple" : "btn-outline-purple";
    const selectBtnIcon = isSelected
        ? "bi-check-square-fill"
        : "bi-check-square";
    const selectBtnText = isSelected ? "Selected" : "Select for Multi-Route";

    return `
        <div style="min-width:250px" class="pickup-${pickup.id}">
            <h6><b>${pickup.customer?.name || "Customer"}</b></h6>
            <p class="mb-1 small">${pickup.pickup_address || "No address"}</p>
            <span class="badge bg-${getStatusColor(pickup.status)}">${pickup.status}</span>
            <hr class="my-2">
            <div class="d-grid gap-1">
                <button class="btn btn-sm ${selectBtnClass}" onclick="togglePickupSelection(${pickup.id}); this.blur();">
                    <i class="bi ${selectBtnIcon} me-1"></i> ${selectBtnText}
                </button>
                <button class="btn btn-sm btn-primary" onclick="getRouteToPickup(${pickup.id})">
                    <i class="bi bi-signpost me-1"></i> Direct Route
                </button>
                <button class="btn btn-sm btn-success" onclick="startNavigationForPickup(${pickup.id})">
                    <i class="bi bi-play-circle me-1"></i> Start Navigation
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="viewPickupDetails(${pickup.id})">
                    <i class="bi bi-eye me-1"></i> View Details
                </button>
            </div>
        </div>
    `;
}

function getStatusColor(status) {
    const colors = {
        pending: "warning",
        accepted: "info",
        en_route: "primary",
        picked_up: "success",
        cancelled: "danger",
    };
    return colors[status] || "secondary";
}

function createClusterToggleControl(map, id, defaultOn = true) {
    const ClusterControl = L.Control.extend({
        onAdd: function (map) {
            const container = L.DomUtil.create(
                "div",
                "leaflet-bar cluster-toggle-control p-2 bg-white rounded shadow-sm",
            );
            container.innerHTML = `
                <div class="form-check m-1">
                    <input class="form-check-input" type="checkbox" id="${id}" ${defaultOn ? "checked" : ""}>
                    <label class="form-check-label small ms-1" for="${id}">Cluster pickups</label>
                </div>
            `;
            L.DomEvent.disableClickPropagation(container);
            return container;
        },
    });

    map.addControl(new ClusterControl({ position: "topright" }));

    // Wire up change handler after DOM is available
    setTimeout(() => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener("change", (e) => {
            if (e.target.checked) {
                if (pickupCluster) map.addLayer(pickupCluster);
            } else {
                if (pickupCluster) map.removeLayer(pickupCluster);
            }
        });
    }, 200);
}

/**
 * SINGLE ROUTE FUNCTIONS
 */
async function getRouteToPickup(pickupId) {
    try {
        const url = `/admin/pickups/${pickupId}/route`;
        console.log("Fetching route from:", url);

        // Show loading state
        showToast("Loading route...", "info");

        const response = await fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const data = await response.json();
        console.log("Route data:", data);

        if (data.success && data.route) {
            // Draw the route on the map
            drawSingleRouteOnMap(data.route);

            // Show route details panel
            showSingleRouteDetails(data, pickupId);

            // Update ETA display
            updateETADisplay(data.estimated_arrival);

            // Show route controls
            toggleRouteControls(true);

            showToast("Route loaded successfully!", "success");
            return data;
        } else {
            throw new Error("Invalid route data");
        }
    } catch (error) {
        console.error("Error fetching route:", error);
        showToast(`Failed to load route: ${error.message}`, "danger");
        return null;
    }
}

function drawSingleRouteOnMap(routeData) {
    const isModalOpen = document
        .getElementById("mapModal")
        ?.classList.contains("show");
    const targetMap = isModalOpen ? modalMapInstance : logisticsMapInstance;

    if (!targetMap) {
        console.error("No active map instance found to draw the route.");
        return;
    }

    // Always clear existing routes from BOTH maps to keep them in sync
    clearRoute();

    if (routeData.geometry) {
        const coordinates = decodePolyline(routeData.geometry);

        // Create the polyline
        routeLayer = L.polyline(coordinates, {
            color: "#3D3B6B",
            weight: 6,
            opacity: 0.8,
            lineJoin: "round",
        }).addTo(targetMap);

        // Add Start Marker (use route's actual start coordinate)
        const startCoord = coordinates[0] || MAP_CENTER;
        const nearestBranch = getNearestBranch(startCoord[0], startCoord[1]);
        startMarker = L.circleMarker(startCoord, {
            radius: 8,
            fillColor: "#007BFF",
            color: "#fff",
            weight: 2,
            fillOpacity: 1,
        })
            .addTo(targetMap)
            .bindPopup(`<b>${nearestBranch.name}</b>`);

        // Add End Marker
        const endCoord = coordinates[coordinates.length - 1];
        endMarker = L.marker(endCoord, {
            icon: L.divIcon({
                className: "end-marker",
                html: '<div style="background:#28A745;width:30px;height:30px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.2);"><i class="bi bi-geo-alt-fill" style="color:white;"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 30],
            }),
        })
            .addTo(targetMap)
            .bindPopup("<b>Customer Location</b>");

        // Zoom the correct map
        targetMap.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
    }
}

function decodePolyline(encoded) {
    if (!encoded || typeof encoded !== "string") return [];

    // If backend already sent an array of [lat,lng], just return it
    if (encoded.startsWith("[")) return JSON.parse(encoded);

    var points = [];
    var index = 0,
        len = encoded.length;
    var lat = 0,
        lng = 0;

    while (index < len) {
        var b,
            shift = 0,
            result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        var dlat = result & 1 ? ~(result >> 1) : result >> 1;
        lat += dlat;

        shift = 0;
        result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        var dlng = result & 1 ? ~(result >> 1) : result >> 1;
        lng += dlng;

        points.push([lat / 1e5, lng / 1e5]); // Note: 1e5 for OSRM/Google
    }
    return points;
}

function clearRoute() {
    const maps = [logisticsMapInstance, modalMapInstance];

    maps.forEach((map) => {
        if (map && typeof map.removeLayer === "function") {
            try {
                if (routeLayer && typeof routeLayer.removeFrom === "function") {
                    routeLayer.removeFrom(map);
                } else if (routeLayer) {
                    map.removeLayer(routeLayer);
                }
            } catch (e) {
                console.warn("Could not remove routeLayer:", e);
            }

            try {
                if (
                    startMarker &&
                    typeof startMarker.removeFrom === "function"
                ) {
                    startMarker.removeFrom(map);
                } else if (startMarker) {
                    map.removeLayer(startMarker);
                }
            } catch (e) {
                console.warn("Could not remove startMarker:", e);
            }

            try {
                if (endMarker && typeof endMarker.removeFrom === "function") {
                    endMarker.removeFrom(map);
                } else if (endMarker) {
                    map.removeLayer(endMarker);
                }
            } catch (e) {
                console.warn("Could not remove endMarker:", e);
            }
        }
    });

    routeLayer = null;
    startMarker = null;
    endMarker = null;

    const etaContainer = document.getElementById("eta-display-container");
    if (etaContainer) etaContainer.style.display = "none";

    toggleRouteControls(false);
    closeRouteDetails();
}

function toggleRouteControls(show = true) {
    const routeControls = document.querySelector(".route-controls");
    if (routeControls) {
        routeControls.style.display = show ? "block" : "none";
    }
}

function showSingleRouteDetails(routeData, pickupId) {
    let detailsPanel = document.getElementById("routeDetailsPanel");

    detailsPanel.innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Route Details</h6>
                <button class="btn btn-sm btn-light" onclick="closeRouteDetails()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h5 class="text-success">
                        <i class="bi bi-signpost"></i> ${routeData.route?.distance?.text || "36.89 km"}
                    </h5>
                    <p class="text-muted">
                        <i class="bi bi-clock"></i> ${routeData.route?.duration?.text || "74 min"}
                    </p>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">From (Branch):</small>
                    <p class="mb-0"><b>${(() => {
                        const wp = routeData.route?.waypoints?.start;
                        if (wp)
                            return getNearestBranch(wp.latitude, wp.longitude)
                                .name;
                        return BRANCHES.length > 0
                            ? BRANCHES[0].name
                            : "WashBox Branch";
                    })()}</b></p>
                    <small class="text-muted">Negros Oriental</small>
                </div>
                <div class="mb-3">
                    <small class="text-muted">To (Pickup):</small>
                    <p class="mb-0"><b>Customer Location</b></p>
                    <small class="text-muted">${routeData.estimated_arrival || "06:23 PM"} ETA</small>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="startNavigation(${pickupId})">
                        <i class="bi bi-play-circle me-2"></i> Start Navigation
                    </button>
                    <button class="btn btn-outline-primary" onclick="printRoute()">
                        <i class="bi bi-printer me-2"></i> Print Directions
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearRoute()">
                        <i class="bi bi-x-circle me-2"></i> Clear Route
                    </button>
                </div>
            </div>
        </div>
    `;

    detailsPanel.style.display = "block";
}

function closeRouteDetails() {
    const panel = document.getElementById("routeDetailsPanel");
    if (panel) {
        panel.style.display = "none";
    }
}

function updateETADisplay(etaTime) {
    const etaContainer = document.getElementById("eta-display-container");
    if (etaContainer) {
        etaContainer.innerHTML = `
            <div class="eta-display">
                <div class="eta-label">Estimated Arrival</div>
                <div class="eta-time">${etaTime || "06:23 PM"}</div>
                <small class="text-muted">Based on current traffic</small>
            </div>
        `;
        etaContainer.style.display = "block";
    }
}

// Helper functions
function showBranchInfo(branchId) {
    const branch = branchId ? getBranchById(branchId) : BRANCHES[0] || null;
    if (!branch) {
        alert("Branch information not available.");
        return;
    }
    alert(
        `${branch.name}\n\nAddress: ${branch.address || "N/A"}\nPhone: ${branch.phone || "N/A"}\nCoordinates: ${parseFloat(branch.latitude).toFixed(4)}, ${parseFloat(branch.longitude).toFixed(4)}`,
    );
}

function viewPickupDetails(pickupId) {
    window.open(`/admin/pickups/${pickupId}`, "_blank");
}

async function startNavigation(pickupId) {
    try {
        const response = await fetch(
            `/admin/pickups/${pickupId}/start-navigation`,
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    "Content-Type": "application/json",
                },
            },
        );

        const data = await response.json();

        if (data.success) {
            showToast("Navigation started!", "success");
            // Update pickup status on the map
            refreshMapMarkers();
        } else {
            showToast("Failed to start navigation: " + data.message, "danger");
        }
    } catch (error) {
        console.error("Error starting navigation:", error);
        showToast("Failed to start navigation", "danger");
    }
}

function startNavigationForPickup(pickupId) {
    startNavigation(pickupId);
}

function refreshMapMarkers() {
    if (logisticsMapInstance) {
        loadPickupsAndRender();
        if (branchMarkers.length > 1) {
            const group = new L.featureGroup(branchMarkers);
            logisticsMapInstance.fitBounds(group.getBounds().pad(0.2));
        } else {
            logisticsMapInstance.setView(MAP_CENTER, MAP_ZOOM);
        }
        showToast("Map refreshed", "info");
    }
}

/**
 * MODAL MAP FUNCTIONS
 */
function setupModalMap() {
    const modalEl = document.getElementById("mapModal");
    if (modalEl) {
        modalEl.addEventListener("shown.bs.modal", () => {
            if (!modalMapInstance) {
                modalMapInstance = L.map("modalLogisticsMap").setView(
                    MAP_CENTER,
                    MAP_ZOOM,
                );
                L.tileLayer(
                    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                    {
                        attribution: "© OpenStreetMap",
                    },
                ).addTo(modalMapInstance);
            }

            // Sync: Clear and Re-add markers to the modal map
            setTimeout(() => {
                modalMapInstance.invalidateSize();

                // Clear any old modal markers
                modalMapInstance.eachLayer((layer) => {
                    if (
                        layer instanceof L.Marker ||
                        layer instanceof L.Polyline
                    ) {
                        modalMapInstance.removeLayer(layer);
                    }
                });

                // Re-add tile layer
                L.tileLayer(
                    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                ).addTo(modalMapInstance);

                // Reload all pickups into the modal map
                const pickupData = window.PENDING_PICKUPS || [];

                // Add ALL branch markers
                addBranchesToModalMap();

                // Add Pickups
                pickupData.forEach((pickup) => {
                    if (pickup.latitude && pickup.longitude) {
                        addPickupMarkerToModalMap(pickup);
                    }
                });
            }, 300);
        });
    }
}

function addBranchesToModalMap() {
    const branchColors = [
        "#007BFF",
        "#10B981",
        "#8B5CF6",
        "#F59E0B",
        "#EF4444",
    ];

    BRANCHES.forEach((branch, index) => {
        const color = branchColors[index % branchColors.length];
        L.marker([parseFloat(branch.latitude), parseFloat(branch.longitude)], {
            icon: L.divIcon({
                className: "branch-marker",
                html: `<div style="background:${color};width:40px;height:40px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;"></i></div>`,
                iconSize: [40, 40],
            }),
        })
            .addTo(modalMapInstance)
            .bindPopup(
                `<b>${branch.name}</b><br><small>${branch.address || ""}</small>`,
            );
    });

    if (BRANCHES.length === 0) {
        L.marker([9.3068, 123.3054], {
            icon: L.divIcon({
                className: "branch-marker",
                html: '<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;"></i></div>',
                iconSize: [40, 40],
            }),
        })
            .addTo(modalMapInstance)
            .bindPopup("<b>WashBox Laundry</b>");
    }
}

function addPickupMarkerToModalMap(pickup) {
    const statusColors = {
        pending: "#FFC107",
        accepted: "#17A2B8",
        en_route: "#007BFF",
        picked_up: "#28A745",
        cancelled: "#DC3545",
    };

    const color = statusColors[pickup.status] || "#6C757D";

    const modalMarker = L.marker(
        [parseFloat(pickup.latitude), parseFloat(pickup.longitude)],
        {
            icon: L.divIcon({
                className: "pickup-marker",
                html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`,
                iconSize: [32, 32],
            }),
        },
    ).bindPopup(createPickupPopup(pickup));

    if (modalPickupCluster) {
        modalPickupCluster.addLayer(modalMarker);
    } else {
        modalMarker.addTo(modalMapInstance);
    }
}

/**
 * DASHBOARD CHART FUNCTIONS — Theme-Aware
 */

/**
 * Returns chart color tokens based on the current [data-theme] value.
 * Called on init and every time the user toggles dark mode.
 */
function getChartThemeColors() {
    const dark = document.documentElement.getAttribute("data-theme") === "dark";
    return {
        isDark: dark,
        gridColor: dark ? "rgba(255, 255, 255, 0.06)" : "rgba(0, 0, 0, 0.05)",
        tickColor: dark ? "#9ca3af" : "#6b7280",
        legendColor: dark ? "#d1d5db" : "#374151",
        tooltipBg: dark ? "rgba(17, 24, 39, 0.95)" : "rgba(15, 23, 42, 0.9)",
        // Doughnut slice border should match card background
        sliceBorder: dark ? "#1f2937" : "#ffffff",
    };
}

/**
 * Apply current theme colors to all active charts without destroying them.
 * Called whenever the user switches themes.
 */
function updateChartsForTheme() {
    const t = getChartThemeColors();

    const revenueChart = DASHBOARD_CONFIG.charts.revenue;
    if (revenueChart) {
        // Grid & ticks
        revenueChart.options.scales.y.grid.color = t.gridColor;
        revenueChart.options.scales.y.ticks.color = t.tickColor;
        revenueChart.options.scales.x.ticks.color = t.tickColor;
        // Tooltip
        revenueChart.options.plugins.tooltip.backgroundColor = t.tooltipBg;
        // Dataset fill gradient adapts automatically; update the area fill opacity
        revenueChart.data.datasets[0].backgroundColor = t.isDark
            ? "rgba(37, 99, 235, 0.18)"
            : "rgba(37, 99, 235, 0.10)";
        revenueChart.update("none"); // 'none' = instant, no animation
    }

    const sourceChart = DASHBOARD_CONFIG.charts.customerSource;
    if (sourceChart) {
        sourceChart.options.plugins.legend.labels.color = t.legendColor;
        sourceChart.options.plugins.tooltip.backgroundColor = t.tooltipBg;
        sourceChart.data.datasets[0].borderColor = t.sliceBorder;
        sourceChart.data.datasets[0].borderWidth = t.isDark ? 2 : 0;
        sourceChart.update("none");
    }

    // ── Service pie charts ───────────────────────────────────
    ["fullService", "selfService", "addonService", "customerBranch"].forEach((key) => {
        const chart = DASHBOARD_CONFIG.charts[key];
        if (chart) {
            chart.options.plugins.legend.labels.color = t.legendColor;
            chart.options.plugins.tooltip.backgroundColor = t.tooltipBg;
            chart.data.datasets[0].borderColor = t.sliceBorder;
            chart.data.datasets[0].borderWidth = t.isDark ? 2 : 0;
            chart.update("none");
        }
    });
}

/**
 * Watch for data-theme attribute changes on <html> and update charts.
 * This hooks into whatever toggle mechanism the layout uses.
 */
function initChartThemeObserver() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            if (m.type === "attributes" && m.attributeName === "data-theme") {
                updateChartsForTheme();
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
}

function initializeCharts() {
    if (typeof Chart === "undefined") {
        console.warn("Chart.js is not loaded. Charts will not be displayed.");
        return;
    }

    const t = getChartThemeColors();

    // ── Revenue Chart ────────────────────────────────────────
    const revenueCtx = document.getElementById("revenueChart");
    if (revenueCtx && window.REVENUE_DATA) {
        DASHBOARD_CONFIG.charts.revenue = new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: window.REVENUE_DATA.labels || [],
                datasets: [
                    {
                        label: "Daily Revenue",
                        data: window.REVENUE_DATA.values || [],
                        borderColor: "#2563eb",
                        backgroundColor: t.isDark
                            ? "rgba(37, 99, 235, 0.18)"
                            : "rgba(37, 99, 235, 0.10)",
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: "#2563eb",
                        pointBorderColor: t.isDark ? "#1f2937" : "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBorderColor: t.isDark ? "#111827" : "#ffffff",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: "index",
                        intersect: false,
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => "₱" + ctx.parsed.y.toLocaleString(),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: t.gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: t.tickColor,
                            callback: (value) => "₱" + value.toLocaleString(),
                            font: { size: 11, family: "system-ui" },
                        },
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            color: t.tickColor,
                            font: { size: 11, family: "system-ui" },
                        },
                    },
                },
                interaction: { intersect: false, mode: "index" },
            },
        });
    }

    // ── Customer Source Chart ────────────────────────────────
    const customerSourceCtx = document.getElementById("customerSourceChart");
    if (customerSourceCtx && window.CUSTOMER_SOURCE_DATA) {
        DASHBOARD_CONFIG.charts.customerSource = new Chart(customerSourceCtx, {
            type: "doughnut",
            data: {
                labels: ["Walk-in", "Mobile App"],
                datasets: [
                    {
                        data: [
                            window.CUSTOMER_SOURCE_DATA.walk_in || 0,
                            window.CUSTOMER_SOURCE_DATA.app || 0, // ← correct
                        ],
                        backgroundColor: ["#2563eb", "#10b981"],
                        borderColor: t.sliceBorder,
                        borderWidth: t.isDark ? 2 : 0,
                        hoverOffset: 15,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: t.legendColor,
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 12, family: "system-ui" },
                        },
                    },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce(
                                    (a, b) => a + b,
                                    0,
                                );
                                const pct = Math.round((ctx.raw / total) * 100);
                                return `${ctx.label}: ${ctx.raw} (${pct}%)`;
                            },
                        },
                    },
                },
                cutout: "70%",
                animation: { animateScale: true, animateRotate: true },
            },
        });
    }

    // ── Customer Branch Pie Chart (walk-in vs mobile) ────────
    const customerBranchCtx = document.getElementById("customerBranchChart");
    if (customerBranchCtx && window.CUSTOMER_BRANCH_DATA?.length) {
        const totalWalkIn = window.CUSTOMER_BRANCH_DATA.reduce((s, b) => s + (b.walk_in || 0), 0);
        const totalMobile = window.CUSTOMER_BRANCH_DATA.reduce((s, b) => s + (b.mobile  || 0), 0);

        DASHBOARD_CONFIG.charts.customerBranch = new Chart(customerBranchCtx, {
            type: "doughnut",
            data: {
                labels: ["Walk-In", "Self-Registered"],
                datasets: [{
                    data: [totalWalkIn, totalMobile],
                    backgroundColor: ["#34d399", "#818cf8"],
                    borderColor: t.sliceBorder,
                    borderWidth: t.isDark ? 2 : 0,
                    hoverOffset: 14,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.raw.toLocaleString()} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // ── Full Service Pie Chart (category: drop_off) ──────────
    const fullServiceCtx = document.getElementById("fullServiceChart");
    if (fullServiceCtx && window.SERVICE_CHART_DATA?.drop_off?.labels?.length) {
        const fs = window.SERVICE_CHART_DATA.drop_off;
        const fullColors = ["#1d4ed8","#2563eb","#3b82f6","#60a5fa","#93c5fd","#bfdbfe","#dbeafe"];

        DASHBOARD_CONFIG.charts.fullService = new Chart(fullServiceCtx, {
            type: "doughnut",
            data: {
                labels: fs.labels,
                datasets: [{
                    data: fs.counts,
                    backgroundColor: fullColors.slice(0, fs.labels.length),
                    borderColor: t.sliceBorder,
                    borderWidth: t.isDark ? 2 : 0,
                    hoverOffset: 14,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: t.legendColor,
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 12, family: "system-ui" },
                        },
                    },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.raw.toLocaleString()} orders (${pct}%)`;
                            },
                            afterLabel: (ctx) => {
                                const rev = window.SERVICE_CHART_DATA.drop_off.revenues[ctx.dataIndex] ?? 0;
                                return ` Revenue: ₱${rev.toLocaleString()}`;
                            },
                        },
                    },
                },
            },
        });
    }

    // ── Self Service Pie Chart (category: self_service) ──────
    const selfServiceCtx = document.getElementById("selfServiceChart");
    if (selfServiceCtx && window.SERVICE_CHART_DATA?.self_service?.labels?.length) {
        const ss = window.SERVICE_CHART_DATA.self_service;
        const selfColors = ["#5b21b6","#6d28d9","#7c3aed","#8b5cf6","#a78bfa","#c4b5fd","#ede9fe"];

        DASHBOARD_CONFIG.charts.selfService = new Chart(selfServiceCtx, {
            type: "doughnut",
            data: {
                labels: ss.labels,
                datasets: [{
                    data: ss.counts,
                    backgroundColor: selfColors.slice(0, ss.labels.length),
                    borderColor: t.sliceBorder,
                    borderWidth: t.isDark ? 2 : 0,
                    hoverOffset: 14,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: t.legendColor,
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 12, family: "system-ui" },
                        },
                    },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.raw.toLocaleString()} orders (${pct}%)`;
                            },
                            afterLabel: (ctx) => {
                                const rev = window.SERVICE_CHART_DATA.self_service.revenues[ctx.dataIndex] ?? 0;
                                return ` Revenue: ₱${rev.toLocaleString()}`;
                            },
                        },
                    },
                },
            },
        });
    }

    // ── Add-On Pie Chart (category: addon) ───────────────────
    const addonServiceCtx = document.getElementById("addonServiceChart");
    if (addonServiceCtx && window.SERVICE_CHART_DATA?.addon?.labels?.length) {
        const ad = window.SERVICE_CHART_DATA.addon;
        const addonColors = ["#92400e","#b45309","#d97706","#f59e0b","#fbbf24","#fcd34d","#fde68a"];

        DASHBOARD_CONFIG.charts.addonService = new Chart(addonServiceCtx, {
            type: "doughnut",
            data: {
                labels: ad.labels,
                datasets: [{
                    data: ad.counts,
                    backgroundColor: addonColors.slice(0, ad.labels.length),
                    borderColor: t.sliceBorder,
                    borderWidth: t.isDark ? 2 : 0,
                    hoverOffset: 14,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: t.legendColor,
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 12, family: "system-ui" },
                        },
                    },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e7eb",
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.raw.toLocaleString()} orders (${pct}%)`;
                            },
                            afterLabel: (ctx) => {
                                const rev = window.SERVICE_CHART_DATA.addon.revenues[ctx.dataIndex] ?? 0;
                                return ` Revenue: ₱${rev.toLocaleString()}`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Start watching theme changes after charts are ready
    initChartThemeObserver();
}

/**
 * AUTO REFRESH
 */
function initializeAutoRefresh() {
    if (DASHBOARD_CONFIG.autoRefresh) {
        setInterval(refreshDashboardStats, DASHBOARD_CONFIG.refreshInterval);
    }
}

function refreshDashboardStats() {
    const refreshBtn = document.getElementById("refresh-btn");
    const originalHtml = refreshBtn ? refreshBtn.innerHTML : "";

    if (refreshBtn) {
        refreshBtn.innerHTML =
            '<i class="bi bi-arrow-clockwise me-2"></i><span>Refreshing...</span>';
        refreshBtn.disabled = true;
    }

    fetch("/admin/dashboard/stats")
        .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then((data) => {
            updateDashboardData(data);
            showToast("Dashboard updated successfully", "success");
        })
        .catch((error) => {
            console.error("Error refreshing dashboard:", error);
            showToast("Failed to refresh dashboard", "danger");
        })
        .finally(() => {
            if (refreshBtn) {
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            }
        });
}

function refreshDashboard() {
    refreshDashboardStats();
}

function updateDashboardData(data) {
    if (data.todayLaundries !== undefined)
        updateElementText('[data-kpi="todayLaundries"]', data.todayLaundries);
    if (data.todayRevenue !== undefined)
        updateElementText(
            '[data-kpi="todayRevenue"]',
            "₱" + data.todayRevenue.toLocaleString(),
        );
    if (data.activeCustomers !== undefined)
        updateElementText(
            '[data-kpi="activeCustomers"]',
            data.activeCustomers.toLocaleString(),
        );
    if (data.unclaimedLaundry !== undefined)
        updateElementText(
            '[data-kpi="unclaimedLaundry"]',
            data.unclaimedLaundry,
        );

    const lastSync = document.getElementById("last-sync");
    if (lastSync) lastSync.textContent = "Updated just now";

    if (DASHBOARD_CONFIG.charts.revenue && data.revenueData) {
        DASHBOARD_CONFIG.charts.revenue.data.labels =
            data.revenueData.labels || [];
        DASHBOARD_CONFIG.charts.revenue.data.datasets[0].data =
            data.revenueData.values || [];
        DASHBOARD_CONFIG.charts.revenue.update();
    }
}

function updateElementText(selector, text) {
    const element = document.querySelector(selector);
    if (element) element.textContent = text;
}

function initializeDateUpdater() {
    const updateDate = () => {
        const now = new Date();
        const options = {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        };
        const dateEl = document.getElementById("current-date");
        if (dateEl)
            dateEl.textContent = now.toLocaleDateString("en-US", options);
    };
    updateDate();
    setInterval(updateDate, 60000);
}

function showToast(message, type = "info") {
    let toastContainer = document.querySelector(".toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        document.body.appendChild(toastContainer);
    }

    const toastEl = document.createElement("div");
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.setAttribute("role", "alert");
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toastEl);

    if (typeof bootstrap !== "undefined") {
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
    } else {
        // Fallback if Bootstrap is not available
        setTimeout(() => toastEl.remove(), 3000);
    }
}

// Make functions available globally
window.initializeDashboardData = initializeDashboardData;
window.refreshDashboard = refreshDashboard;
window.refreshMapMarkers = refreshMapMarkers;
window.getRouteToPickup = getRouteToPickup;
window.startNavigation = startNavigation;
window.startNavigationForPickup = startNavigationForPickup;
window.closeRouteDetails = closeRouteDetails;
window.clearRoute = clearRoute;
window.printRoute = printRoute;
window.showBranchInfo = showBranchInfo;
window.viewPickupDetails = viewPickupDetails;
window.togglePickupSelection = togglePickupSelection;
window.getOptimizedMultiRoute = getOptimizedMultiRoute;
window.autoRouteAllVisible = autoRouteAllVisible;
window.selectAllPending = selectAllPending;
window.clearSelections = clearSelections;
window.startMultiPickupNavigation = startMultiPickupNavigation;
window.printRouteSchedule = printRouteSchedule;

// Geocoding and address search functions
window.searchMapAddress = searchMapAddress;
window.createPickupAtLocation = createPickupAtLocation;
window.getRouteToSearchLocation = getRouteToSearchLocation;
window.updateMarkerLocation = updateMarkerLocation;
window.copyCoordinates = copyCoordinates;

// Export function (placeholder)
window.exportData = function (format) {
    alert("Export to " + format + " functionality to be implemented");
};

// Add sample pickups function (for development)
function addSamplePickups() {
    console.log("Adding sample pickups for development");
    // This is just a placeholder - remove in production
}

/**
 * RATING BARS — animate trc-star-fill widths on tab entry
 */
function animateRatingBars() {
    document.querySelectorAll(".trc-star-fill").forEach((bar) => {
        const target = bar.getAttribute("data-width") || "0";
        bar.style.width = "0%";
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                bar.style.width = target + "%";
            });
        });
    });
}

// Run on page load too in case customers tab is default
document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById("customers")?.classList.contains("active")) {
        setTimeout(animateRatingBars, 300);
    }
});

console.log("✅ Admin Dashboard JS loaded");
