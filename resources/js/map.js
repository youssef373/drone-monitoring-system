import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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
 * Create a custom drone marker icon with status color and battery indicator
 * @param {string} status - Drone status
 * @param {number} battery - Battery level percentage
 */
function createDroneIcon(status, battery) {
    const color = STATUS_COLORS[status] || STATUS_COLORS.inactive;
    const batteryWidth = Math.max(0, Math.min(100, battery));
    const batteryColor = batteryWidth < 25 ? '#ef4444' : batteryWidth < 50 ? '#eab308' : '#22c55e';

    return L.divIcon({
        className: 'drone-marker',
        html: `
            <div style="position: relative; text-align: center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                    <line x1="12" y1="12" x2="12" y2="2"/>
                </svg>
                <div style="
                    width: 28px;
                    height: 4px;
                    background: #e5e7eb;
                    border-radius: 2px;
                    margin-top: 2px;
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
        iconSize: [32, 40],
        iconAnchor: [16, 20],
        popupAnchor: [0, -20],
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

    const lat = drone.lat ?? 0;
    const lng = drone.lng ?? 0;
    const hasPosition = drone.lat !== null && drone.lng !== null;

    // Remove existing marker if present
    if (markers[drone.id]) {
        map.removeLayer(markers[drone.id]);
    }

    const icon = createDroneIcon(drone.status, drone.battery ?? 0);
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

    // Store marker reference
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
 */
export function updateDronePosition(droneId, lat, lng, status = null, battery = null) {
    if (!markers[droneId]) {
        console.warn(`Marker for drone ${droneId} not found`);
        return false;
    }

    const marker = markers[droneId];
    marker.setLatLng([lat, lng]);

    // Update icon if status or battery changed
    if (status !== null || battery !== null) {
        const currentStatus = status || 'inactive';
        const currentBattery = battery || 0;
        marker.setIcon(createDroneIcon(currentStatus, currentBattery));
    }

    // Update popup content if open
    if (marker.isPopupOpen()) {
        marker.setPopupContent(marker.getPopup().getContent());
    }

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

    window.Echo.private(`drone.${droneId}`)
        .listen('.telemetry.updated', (event) => {
            console.log('Telemetry update received:', event);

            // Update marker position
            updateDronePosition(
                event.drone_id,
                event.latitude,
                event.longitude,
                event.status,
                event.battery_level
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

    // Initialize map
    const initialCenter = window.initialMapCenter || [0, 0];
    const initialZoom = window.initialMapZoom || 13;
    initMap('map-container', initialCenter, initialZoom);

    // Load drones data if available
    if (window.dronesData && Array.isArray(window.dronesData)) {
        window.dronesData.forEach(drone => {
            addDroneMarker(drone);
        });

        // Center on drones if multiple, or set view for single
        if (window.dronesData.length > 1) {
            centerOnDrones();
        } else if (window.dronesData.length === 1) {
            const drone = window.dronesData[0];
            if (drone.lat && drone.lng) {
                map.setView([drone.lat, drone.lng], 15);
            }
        }
    }

    // Load geofences if available
    if (window.geofencesData && Array.isArray(window.geofencesData)) {
        window.geofencesData.forEach(geofence => {
            addGeofence(geofence);
        });
    }

    // Initialize trail if in single drone view
    if (window.trailData && Array.isArray(window.trailData)) {
        initTrail(window.trailData);
    }

    // Set focused drone if in single view
    if (window.focusedDroneId) {
        setFocusedDrone(window.focusedDroneId);
    }

    // Subscribe to WebSocket updates
    if (window.dronesData && Array.isArray(window.dronesData)) {
        const droneIds = window.dronesData.map(d => d.id);
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
    setFocusedDrone,
    setFollowMode,
    initTrail,
    addTrailPoint,
    subscribeToDroneUpdates,
    subscribeToAllDrones,
    unsubscribeAll,
};
