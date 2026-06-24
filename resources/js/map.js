import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Inject alert pulse animation CSS
if (typeof document !== 'undefined' && !document.getElementById('drone-marker-styles')) {
    const style = document.createElement('style');
    style.id = 'drone-marker-styles';
    style.textContent = `
        @keyframes drone-alert-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }
    `;
    document.head.appendChild(style);
}

// Drone status color mapping
const STATUS_COLORS = {
    active: '#22c55e',
    inactive: '#6b7280',
    maintenance: '#eab308',
    emergency: '#ef4444',
};

// Map instance
let map = null;
let markers = {};
let trailPolyline = null;
let followMode = false;

/**
 * Initialize the map with Leaflet
 * @param {string} containerId - The DOM element ID for the map
 * @param {Array} initialCenter - Initial map center [lat, lng]
 * @param {number} initialZoom - Initial zoom level
 */
export function initMap(containerId = 'map-container', initialCenter = [0, 0], initialZoom = 13) {
    map = L.map(containerId).setView(initialCenter, initialZoom);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    return map;
}

/**
 * Get the current map instance
 */
export function getMap() {
    return map;
}

/**
 * Alert type visual configuration: icon SVG path + color + label.
 * Each alert type renders a distinct icon on the drone marker badge.
 */
const ALERT_TYPE_CONFIG = {
    geofence_violation: {
        color: '#ef4444',
        icon: '<path d="M12 2L3 7v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V7l-9-5z"/><path d="M12 7v5"/><path d="M12 16h.01" stroke-width="2.5"/>',
        label: 'Geofence Violation',
    },
    critical_battery: {
        color: '#ef4444',
        icon: '<rect x="2" y="7" width="16" height="10" rx="2"/><line x1="22" y1="11" x2="22" y2="13" stroke-width="3"/><path d="M10 10v3" stroke-width="2.5"/><path d="M10 15h.01" stroke-width="2.5"/>',
        label: 'Critical Battery',
    },
    low_battery: {
        color: '#eab308',
        icon: '<rect x="2" y="7" width="16" height="10" rx="2"/><line x1="22" y1="11" x2="22" y2="13" stroke-width="3"/><path d="M7 12h3" stroke-width="2.5"/>',
        label: 'Low Battery',
    },
    signal_loss: {
        color: '#ef4444',
        icon: '<path d="M2 8a14 14 0 0 1 20 0" stroke-width="2"/><path d="M5 11a10 10 0 0 1 14 0" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="16" stroke-width="3" stroke-linecap="round"/><line x1="3" y1="3" x2="21" y2="21" stroke-width="2.5"/>',
        label: 'Signal Loss',
    },
    emergency: {
        color: '#ef4444',
        icon: '<path d="M12 2v6m0 8v6M2 12h6m8 0h6" stroke-width="2.5"/><circle cx="12" cy="12" r="3"/>',
        label: 'Emergency',
    },
};

/**
 * Build a single alert badge SVG for a given alert type.
 * @param {Object} alert - {type, severity, message}
 * @param {number} offset - vertical offset (px) for stacking multiple badges
 * @returns {string} HTML string for the badge
 */
function buildAlertBadge(alert, offset) {
    const cfg = ALERT_TYPE_CONFIG[alert.type] || {
        color: alert.severity === 'critical' ? '#ef4444' : '#eab308',
        icon: '<path d="M12 9v4"/><path d="M12 17h.01"/>',
        label: alert.type,
    };
    const isCritical = alert.severity === 'critical';
    const pulse = isCritical ? 'animation: drone-alert-pulse 1s ease-in-out infinite;' : '';

    return `
        <div title="${cfg.label}: ${alert.message || ''}"
             style="
            position: absolute;
            top: ${offset}px;
            right: -8px;
            width: 18px;
            height: 18px;
            background: ${cfg.color};
            border: 2px solid #10131a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            ${pulse}
            z-index: 10;
        ">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                ${cfg.icon}
            </svg>
        </div>`;
}

/**
 * Create a custom drone marker icon (quadcopter shape) with status color,
 * battery indicator, and type-specific alert badges if the drone has active alerts.
 * @param {string} status - Drone status
 * @param {number} battery - Battery level percentage
 * @param {Array}  alerts - Active alerts array [{type, severity, message}, ...]
 */
function createDroneIcon(status, battery, alerts = []) {
    const color = STATUS_COLORS[status] || STATUS_COLORS.inactive;
    const batteryWidth = Math.max(0, Math.min(100, battery));
    const batteryColor = batteryWidth < 25 ? '#ef4444' : batteryWidth < 50 ? '#eab308' : '#22c55e';

    // Deduplicate by type so we show one badge per alert type (not per alert)
    const seenTypes = new Set();
    const uniqueAlerts = (alerts || []).filter(a => {
        if (seenTypes.has(a.type)) return false;
        seenTypes.add(a.type);
        return true;
    });

    // Stack badges vertically above the marker (each badge is 18px + 4px gap)
    const alertBadges = uniqueAlerts.map((a, i) => buildAlertBadge(a, -8 - i * 22)).join('');

    return L.divIcon({
        className: 'drone-marker',
        html: `
            <div style="position: relative; text-align: center; width: 40px;">
                ${alertBadges}
                <svg width="40" height="40" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Quadcopter arms (X-frame) -->
                    <line x1="8" y1="8" x2="40" y2="40" stroke="${color}" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="40" y1="8" x2="8" y2="40" stroke="${color}" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Rotors (4 propeller circles) -->
                    <circle cx="8" cy="8" r="5" fill="${color}" fill-opacity="0.15" stroke="${color}" stroke-width="1.5"/>
                    <circle cx="40" cy="8" r="5" fill="${color}" fill-opacity="0.15" stroke="${color}" stroke-width="1.5"/>
                    <circle cx="8" cy="40" r="5" fill="${color}" fill-opacity="0.15" stroke="${color}" stroke-width="1.5"/>
                    <circle cx="40" cy="40" r="5" fill="${color}" fill-opacity="0.15" stroke="${color}" stroke-width="1.5"/>
                    <!-- Propeller blades -->
                    <line x1="5" y1="8" x2="11" y2="8" stroke="${color}" stroke-width="1" stroke-opacity="0.6"/>
                    <line x1="37" y1="8" x2="43" y2="8" stroke="${color}" stroke-width="1" stroke-opacity="0.6"/>
                    <line x1="5" y1="40" x2="11" y2="40" stroke="${color}" stroke-width="1" stroke-opacity="0.6"/>
                    <line x1="37" y1="40" x2="43" y2="40" stroke="${color}" stroke-width="1" stroke-opacity="0.6"/>
                    <!-- Central body -->
                    <rect x="18" y="18" width="12" height="12" rx="3" fill="${color}" stroke="#10131a" stroke-width="1"/>
                    <!-- Camera lens -->
                    <circle cx="24" cy="24" r="2.5" fill="#10131a" stroke="#fff" stroke-width="0.5"/>
                </svg>
                <div style="
                    width: 32px;
                    height: 4px;
                    background: #e5e7eb;
                    border-radius: 2px;
                    margin: -2px auto 0;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="
                        width: ${batteryWidth}%;
                        height: 100%;
                        background: ${batteryColor};
                        border-radius: 1px;
                    "></div>
                </div>
            </div>
        `,
        iconSize: [40, 48],
        iconAnchor: [20, 24],
        popupAnchor: [0, -24],
    });
}

/**
 * Add a drone marker to the map
 * @param {Object} drone - Drone data object
 * @param {number} drone.id - Drone ID
 * @param {string} drone.name - Drone name
 * @param {string} drone.status - Drone status
 * @param {number} drone.lat - Latitude
 * @param {number} drone.lng - Longitude
 * @param {number} drone.battery - Battery level
 * @param {Object|null} drone.geofence - Associated geofence data
 */
export function addDroneMarker(drone) {
    if (!map) {
        console.error('Map not initialized. Call initMap() first.');
        return;
    }

    // Skip rendering if the drone has no position (FR-014, edge case)
    if (drone.lat === null || drone.lng === null ||
        drone.lat === undefined || drone.lng === undefined) {
        return;
    }

    const lat = drone.lat;
    const lng = drone.lng;
    const hasPosition = true;

    // Remove existing marker if present
    if (markers[drone.id]) {
        map.removeLayer(markers[drone.id]);
    }

    const icon = createDroneIcon(drone.status, drone.battery ?? 0, drone.alerts || []);
    const marker = L.marker([lat, lng], { icon }).addTo(map);

    // Build popup content
    const popupContent = `
        <div class="drone-popup">
            <h3 class="font-semibold text-gray-900">${drone.name}</h3>
            <p class="text-sm text-gray-600">Status: <span class="capitalize" style="color: ${STATUS_COLORS[drone.status] || STATUS_COLORS.inactive}">${drone.status}</span></p>
            <p class="text-sm text-gray-600">Battery: ${drone.battery ?? 'N/A'}%</p>
            ${hasPosition ? `
                <p class="text-sm text-gray-600">Lat: ${lat.toFixed(6)}</p>
                <p class="text-sm text-gray-600">Lng: ${lng.toFixed(6)}</p>
            ` : '<p class="text-sm text-orange-600">No position data</p>'}
            <a href="/map/${drone.id}" class="text-sm text-blue-600 hover:underline">View Details &rarr;</a>
        </div>
    `;

    marker.bindPopup(popupContent);

    // Store marker reference with drone metadata for popup rebuilding
    marker._droneId = drone.id;
    marker._droneName = drone.name;
    markers[drone.id] = marker;

    return marker;
}

/**
 * Update a drone marker position
 * @param {number} droneId - Drone ID
 * @param {number} lat - New latitude
 * @param {number} lng - New longitude
 * @param {string|null} status - Optional new status
 * @param {number|null} battery - Optional new battery level
 * @param {Array|null} alerts - Optional active alerts array
 */
export function updateDronePosition(droneId, lat, lng, status = null, battery = null, alerts = null) {
    if (!markers[droneId]) {
        console.warn(`Marker for drone ${droneId} not found`);
        return false;
    }

    const marker = markers[droneId];
    marker.setLatLng([lat, lng]);

    // Update icon if status, battery, or alerts changed
    if (status !== null || battery !== null || alerts !== null) {
        const currentStatus = status || 'inactive';
        const currentBattery = battery || 0;
        const currentAlerts = alerts || [];
        marker.setIcon(createDroneIcon(currentStatus, currentBattery, currentAlerts));
    }

    // Rebuild popup with updated data
    const droneName = marker._droneName || '';
    const displayStatus = status || 'inactive';
    const displayBattery = battery ?? 'N/A';
    const updatedPopup = `
        <div class="drone-popup">
            <h3 class="font-semibold text-gray-900">${droneName}</h3>
            <p class="text-sm text-gray-600">Status: <span class="capitalize" style="color: ${STATUS_COLORS[displayStatus] || STATUS_COLORS.inactive}">${displayStatus}</span></p>
            <p class="text-sm text-gray-600">Battery: ${displayBattery}%</p>
            <p class="text-sm text-gray-600">Lat: ${lat.toFixed(6)}</p>
            <p class="text-sm text-gray-600">Lng: ${lng.toFixed(6)}</p>
            <a href="/map/${droneId}" class="text-sm text-blue-600 hover:underline">View Details &rarr;</a>
        </div>
    `;
    marker.setPopupContent(updatedPopup);

    // If follow mode is enabled, pan map to this marker
    if (followMode && window.focusedDroneId === droneId) {
        map.panTo([lat, lng]);
    }

    return true;
}

/**
 * Remove a drone marker from the map
 * @param {number} droneId - Drone ID
 */
export function removeDroneMarker(droneId) {
    if (markers[droneId]) {
        map.removeLayer(markers[droneId]);
        delete markers[droneId];
    }
}

/**
 * Add a geofence overlay to the map
 * @param {Object} geofence - Geofence data
 * @param {number} geofence.id - Geofence ID
 * @param {string} geofence.name - Geofence name
 * @param {Array|null} geofence.boundary - Polygon boundary as array of [lat, lng] pairs
 * @param {number|null} geofence.center_lat - Center latitude for circle
 * @param {number|null} geofence.center_lng - Center longitude for circle
 * @param {number|null} geofence.radius_meters - Radius in meters for circle
 */
export function addGeofence(geofence) {
    if (!map) {
        console.error('Map not initialized. Call initMap() first.');
        return;
    }

    const style = {
        color: '#3b82f6',
        fillColor: '#3b82f6',
        fillOpacity: 0.1,
        weight: 2,
    };

    let overlay = null;

    // Circle geofence
    if (geofence.radius_meters !== null && geofence.center_lat !== null && geofence.center_lng !== null) {
        overlay = L.circle(
            [geofence.center_lat, geofence.center_lng],
            { radius: geofence.radius_meters, ...style }
        ).addTo(map);
    }
    // Polygon geofence
    else if (geofence.boundary && Array.isArray(geofence.boundary) && geofence.boundary.length > 0) {
        const coords = geofence.boundary.map(point => [point[0], point[1]]);
        overlay = L.polygon(coords, style).addTo(map);
    }

    if (overlay) {
        overlay.bindTooltip(geofence.name, {
            permanent: false,
            direction: 'top',
        });
    }

    return overlay;
}

/**
 * Center the map to fit all drone markers
 */
export function centerOnDrones() {
    if (!map || Object.keys(markers).length === 0) {
        return;
    }

    const markerArray = Object.values(markers);
    const group = L.featureGroup(markerArray);
    map.fitBounds(group.getBounds().pad(0.1));
}

/**
 * Set focused drone for follow mode
 * @param {number|null} droneId - Drone ID to follow, or null to disable
 */
export function setFocusedDrone(droneId) {
    window.focusedDroneId = droneId;
}

/**
 * Center the map on the focused drone marker
 * Reads window.focusedDroneId to determine which marker to center on.
 */
export function centerOnFocused() {
    if (!map || !window.focusedDroneId) {
        return;
    }

    const marker = markers[window.focusedDroneId];
    if (marker) {
        const latLng = marker.getLatLng();
        map.setView(latLng, Math.max(map.getZoom(), 15));
    }
}

/**
 * Toggle follow mode
 * @param {boolean} enabled - Whether to enable follow mode
 */
export function setFollowMode(enabled) {
    followMode = enabled;
}

/**
 * Initialize a trail polyline for a single drone view
 * @param {Array} trailData - Array of [lat, lng] pairs
 */
export function initTrail(trailData = []) {
    if (!map) {
        console.error('Map not initialized. Call initMap() first.');
        return;
    }

    if (trailPolyline) {
        map.removeLayer(trailPolyline);
    }

    if (trailData.length > 0) {
        trailPolyline = L.polyline(trailData, {
            color: '#3b82f6',
            weight: 3,
            opacity: 0.7,
            dashArray: '5, 10',
        }).addTo(map);
    }
}

/**
 * Add a point to the trail polyline
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 */
export function addTrailPoint(lat, lng) {
    if (trailPolyline) {
        const latLngs = trailPolyline.getLatLngs();
        latLngs.push([lat, lng]);

        // Keep only last 50 points
        if (latLngs.length > 50) {
            latLngs.shift();
        }

        trailPolyline.setLatLngs(latLngs);
    }
}

/**
 * Subscribe to WebSocket updates for a drone
 * @param {number} droneId - Drone ID to subscribe to
 * @param {Function} onUpdate - Callback function for telemetry updates
 */
export function subscribeToDroneUpdates(droneId, onUpdate = null) {
    if (!window.Echo) {
        console.error('Laravel Echo not initialized. Check bootstrap.js');
        return;
    }

    window.Echo.channel(`drone.${droneId}`)
        .listen('.telemetry.updated', (event) => {
            console.log('Telemetry update received:', event);

            // Update marker position
            updateDronePosition(
                event.drone_id,
                event.latitude,
                event.longitude,
                event.status,
                event.battery_level,
                event.active_alerts || []
            );

            // Update trail if in single drone view
            if (window.focusedDroneId === event.drone_id) {
                addTrailPoint(event.latitude, event.longitude);
            }

            // Call custom callback if provided
            if (onUpdate && typeof onUpdate === 'function') {
                onUpdate(event);
            }

            // Dispatch custom event for Alpine.js
            window.dispatchEvent(new CustomEvent('drone-telemetry-updated', {
                detail: event,
            }));
        });
}

/**
 * Subscribe to all drone updates
 * @param {Array} droneIds - Array of drone IDs to subscribe to
 * @param {Function} onUpdate - Callback function for telemetry updates
 */
export function subscribeToAllDrones(droneIds, onUpdate = null) {
    droneIds.forEach(id => {
        subscribeToDroneUpdates(id, onUpdate);
    });
}

/**
 * Unsubscribe from all drone channels
 */
export function unsubscribeAll() {
    if (!window.Echo) {
        return;
    }

    // Echo doesn't have a direct method to get all channels,
    // so we need to manually disconnect or leave channels
    window.Echo.disconnect();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if map container exists
    const mapContainer = document.getElementById('map-container');
    if (!mapContainer) {
        return;
    }

    // Read bootstrap data from window.mapConfig (set by Blade templates)
    const config = window.mapConfig || {};
    const dronesData = config.dronesData || [];
    const geofencesData = config.geofencesData || [];
    const trailData = config.trailData || [];
    const focusedDroneId = config.focusedDroneId;
    const singleDrone = config.drone;

    // In single-drone mode, build a dronesData array from the single drone object
    // so addDroneMarker and subscribeToAllDrones work uniformly
    const allDrones = config.mode === 'single'
        ? (singleDrone ? [singleDrone] : [])
        : dronesData;

    // Initialize map
    const initialCenter = window.initialMapCenter || [0, 0];
    const initialZoom = window.initialMapZoom || 13;
    initMap('map-container', initialCenter, initialZoom);

    // Expose map instance and action functions to window for page-level scripts
    // (map-index.js and map-show.js reference these via window.*)
    window.mapInstance = map;
    window.fitAllDrones = centerOnDrones;
    window.centerOnFocused = centerOnFocused;
    window.setFollowMode = setFollowMode;

    // Load drones data
    allDrones.forEach(drone => {
        addDroneMarker(drone);
    });

    // Center on drones if multiple, or set view for single
    if (allDrones.length > 1) {
        centerOnDrones();
    } else if (allDrones.length === 1) {
        const drone = allDrones[0];
        if (drone.lat && drone.lng) {
            map.setView([drone.lat, drone.lng], 15);
        }
    }

    // Load geofences if available
    geofencesData.forEach(geofence => {
        addGeofence(geofence);
    });

    // Initialize trail if in single drone view
    if (trailData.length > 0) {
        initTrail(trailData);
    }

    // Set focused drone if in single view
    if (focusedDroneId) {
        setFocusedDrone(focusedDroneId);
    }

    // Subscribe to WebSocket updates
    if (allDrones.length > 0) {
        const droneIds = allDrones.map(d => d.id);
        subscribeToAllDrones(droneIds);
    }
});

// Export for use in other modules
export default {
    initMap,
    getMap,
    addDroneMarker,
    updateDronePosition,
    removeDroneMarker,
    addGeofence,
    centerOnDrones,
    centerOnFocused,
    setFocusedDrone,
    setFollowMode,
    initTrail,
    addTrailPoint,
    subscribeToDroneUpdates,
    subscribeToAllDrones,
    unsubscribeAll,
};
