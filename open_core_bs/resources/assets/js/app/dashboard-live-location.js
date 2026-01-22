/**
 * Live Location View - Employee Tracking Dashboard
 * Manages real-time employee location tracking with Google Maps integration
 */

'use strict';

// Global variables
let map, markers = [], bounds;
let employees = [];
let currentFilter = 'all';
let selectedEmployeeId = null;
let currentInfoWindow = null;
let autoRefreshInterval = null;
let isAutoRefreshEnabled = false;
let markerIcons = null;

// Icon configuration
const iconBase = window.location.origin + '/assets/img/map/';

/**
 * Initialize the map
 */
function initializeMap() {
  // Initialize marker icons (must be done after Google Maps is loaded)
  markerIcons = {
    online: { url: iconBase + 'green_circle.png', scaledSize: new google.maps.Size(32, 32) },
    offline: { url: iconBase + 'red_circle.png', scaledSize: new google.maps.Size(32, 32) }
  };

  bounds = new google.maps.LatLngBounds();

  map = new google.maps.Map(document.getElementById('map'), {
    center: {
      lat: pageData.settings.centerLatitude,
      lng: pageData.settings.centerLongitude
    },
    zoom: pageData.settings.mapZoomLevel,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    gestureHandling: 'greedy',
    streetViewControl: false,
    zoomControl: true,
    fullscreenControl: true
  });

  // Initial data fetch
  fetchLiveLocations();

  // Setup event listeners
  setupEventListeners();
}

/**
 * Start the application once Google Maps is ready
 */
function startApplication() {
  if (typeof google !== 'undefined' && google.maps && document.getElementById('map')) {
    console.log('Initializing live location map...');
    initializeMap();
  } else {
    console.log('Waiting for Google Maps or DOM...');
    setTimeout(startApplication, 100);
  }
}

// Listen for Google Maps ready event
window.addEventListener('googleMapsReady', function() {
  console.log('Google Maps ready event received');
  startApplication();
});

// Also check if Google Maps is already loaded (in case event was missed)
$(document).ready(function() {
  console.log('DOM ready, checking Google Maps status...');
  if (window.googleMapsLoaded) {
    console.log('Google Maps already loaded');
    startApplication();
  }
});

/**
 * Setup all event listeners
 */
function setupEventListeners() {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Manual refresh button
  $('#refreshBtn').on('click', function() {
    $(this).find('i').addClass('bx-spin');
    fetchLiveLocations();
  });

  // Auto-refresh toggle
  $('#autoRefreshToggle').on('click', toggleAutoRefresh);

  // Filter buttons
  $('.filter-btn').on('click', function() {
    const filter = $(this).data('filter');
    setActiveFilter(filter);
    filterEmployees(filter);
  });

  // Employee search with debouncing
  $('#employeeSearch').on('input', debounce(function() {
    const query = $(this).val().toLowerCase();
    searchEmployees(query);
  }, 300));

  // Focus on map button (delegated event)
  $(document).on('click', '.focus-map-btn', function() {
    const lat = parseFloat($(this).data('lat'));
    const lng = parseFloat($(this).data('lng'));
    const name = $(this).data('name');
    const employeeId = $(this).data('employee-id');

    focusOnMap(lat, lng, name, employeeId);
  });
}

/**
 * Fetch live location data from server
 */
function fetchLiveLocations() {
  showMapLoading(true);

  $.ajax({
    url: pageData.urls.liveLocationAjax,
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      employees = response;
      processEmployeeData(response);
      hideLoadingSkeleton();
      showMapLoading(false);

      // Remove spin animation from refresh button
      $('#refreshBtn').find('i').removeClass('bx-spin');
    },
    error: function() {
      console.error('Error fetching locations');
      hideLoadingSkeleton();
      showMapLoading(false);
      $('#refreshBtn').find('i').removeClass('bx-spin');

      // Show error toast
      showToast(pageData.labels.loadingError + '. ' + pageData.labels.tryAgain, 'error');
    }
  });
}

/**
 * Process and display employee data
 */
function processEmployeeData(data) {
  if (!data || data.length === 0) {
    showEmptyState();
    return;
  }

  hideEmptyState();
  clearMarkers();

  let onlineCount = 0;
  let offlineCount = 0;

  data.forEach(user => {
    // Add marker to map
    addMarkerToMap(user);

    // Count status
    if (user.status === 'online') {
      onlineCount++;
    } else {
      offlineCount++;
    }
  });

  // Update statistics
  $('#totalCount').text(data.length);
  $('#onlineCount').text(onlineCount);
  $('#offlineCount').text(offlineCount);

  // Render employee list
  renderEmployeeList(data);

  // Fit map bounds
  if (markers.length > 0) {
    map.fitBounds(bounds);
  }
}

/**
 * Add marker to map for a user
 */
function addMarkerToMap(user) {
  const position = new google.maps.LatLng(user.latitude, user.longitude);
  const markerIcon = markerIcons[user.status] || markerIcons.offline;

  const marker = new google.maps.Marker({
    position,
    map,
    title: user.name,
    icon: markerIcon,
    userData: user // Store user data in marker
  });

  // Create info window
  const infoWindow = new google.maps.InfoWindow({
    content: createInfoWindowContent(user)
  });

  // Add click listener
  marker.addListener('click', function() {
    if (currentInfoWindow) {
      currentInfoWindow.close();
    }
    infoWindow.open(map, marker);
    currentInfoWindow = infoWindow;

    // Highlight employee card
    highlightEmployeeCard(user.id);
  });

  markers.push(marker);
  bounds.extend(position);
}

/**
 * Create info window content HTML
 */
function createInfoWindowContent(user) {
  const statusText = user.status === 'online' ? pageData.labels.online : pageData.labels.offline;
  const statusClass = user.status === 'online' ? 'success' : 'danger';

  return `
    <div style="min-width: 200px;">
      <h6 class="mb-2">${user.name}</h6>
      <p class="mb-1">
        <span class="badge bg-${statusClass}">${statusText}</span>
      </p>
      <p class="mb-1"><small>${pageData.labels.designation}: ${user.designation}</small></p>
      <p class="mb-0"><small>${pageData.labels.lastUpdated}: ${user.updatedAt}</small></p>
    </div>
  `;
}

/**
 * Render employee list in sidebar
 */
function renderEmployeeList(data) {
  const container = $('#employeeCards');
  container.empty();

  if (!data || data.length === 0) {
    container.append(`
      <div class="text-center text-muted py-4">
        <i class="bx bx-search" style="font-size: 3rem;"></i>
        <p class="mt-2">${pageData.labels.noEmployeesFound}</p>
      </div>
    `);
    return;
  }

  data.forEach(user => {
    const card = createEmployeeCard(user);
    container.append(card);
  });
}

/**
 * Create employee card HTML (DRY - single source of truth)
 */
function createEmployeeCard(user) {
  const profileHtml = user.profilePicture
    ? `<img src="${user.profilePicture}" alt="${user.name}" class="avatar rounded-circle" />`
    : `<span class="avatar-initial rounded-circle bg-label-primary">${user.initials}</span>`;

  const statusClass = user.status === 'online' ? 'bg-success' : 'bg-danger';

  return `
    <div class="card mb-2 p-2 shadow-sm position-relative employee-card" data-employee-id="${user.id}" data-status="${user.status}">
      <div class="status-indicator position-absolute" style="top: 8px; right: 8px;">
        <span class="dot ${statusClass}"></span>
      </div>

      <div>
        <div class="d-flex justify-content-start align-items-center user-name">
          <div class="avatar-wrapper">
            <div class="avatar avatar-sm me-3">
              ${profileHtml}
            </div>
          </div>
          <div class="d-flex flex-column">
            <span class="text-heading text-truncate fw-medium">${user.name}</span>
            <small class="text-muted">${pageData.labels.code}: ${user.code}</small>
          </div>
        </div>
        <p class="mb-1 mt-3"><small>${pageData.labels.designation}: ${user.designation}</small></p>
        <p class="mb-0"><small class="text-muted">${pageData.labels.lastUpdated}: ${user.updatedAt}</small></p>
      </div>

      <button class="btn btn-sm btn-outline-primary mt-2 w-100 focus-map-btn"
              data-lat="${user.latitude}"
              data-lng="${user.longitude}"
              data-name="${user.name}"
              data-employee-id="${user.id}">
        <i class="bx bx-crosshair me-1"></i>${pageData.labels.focusOnMap}
      </button>
    </div>
  `;
}

/**
 * Focus on specific location on map with animation
 */
function focusOnMap(lat, lng, name, employeeId) {
  const position = new google.maps.LatLng(lat, lng);

  // Pan and zoom to location
  map.panTo(position);
  map.setZoom(18);

  // Close previous info window
  if (currentInfoWindow) {
    currentInfoWindow.close();
  }

  // Find and animate marker
  markers.forEach(marker => {
    if (marker.getPosition().lat() === lat && marker.getPosition().lng() === lng) {
      // Bounce animation
      marker.setAnimation(google.maps.Animation.BOUNCE);
      setTimeout(() => marker.setAnimation(null), 1400);

      // Open info window
      currentInfoWindow = new google.maps.InfoWindow({
        content: createInfoWindowContent(marker.userData)
      });
      currentInfoWindow.open(map, marker);
    }
  });

  // Highlight employee card
  highlightEmployeeCard(employeeId);
}

/**
 * Highlight selected employee card and scroll to it
 */
function highlightEmployeeCard(employeeId) {
  $('.employee-card').removeClass('active');
  $(`.employee-card[data-employee-id="${employeeId}"]`).addClass('active');

  // Scroll to card
  const card = $(`.employee-card[data-employee-id="${employeeId}"]`);
  if (card.length) {
    card[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  selectedEmployeeId = employeeId;
}

/**
 * Filter employees by status
 */
function filterEmployees(filter) {
  currentFilter = filter;

  if (filter === 'all') {
    renderEmployeeList(employees);
    showAllMarkers();
  } else {
    const filtered = employees.filter(emp => emp.status === filter);
    renderEmployeeList(filtered);
    filterMarkers(filter);
  }
}

/**
 * Show all markers on map
 */
function showAllMarkers() {
  markers.forEach(marker => marker.setVisible(true));
}

/**
 * Filter markers by status
 */
function filterMarkers(status) {
  markers.forEach(marker => {
    const shouldShow = marker.userData.status === status;
    marker.setVisible(shouldShow);
  });
}

/**
 * Set active filter button
 */
function setActiveFilter(filter) {
  $('.filter-btn').removeClass('active').attr('aria-pressed', 'false');
  $(`.filter-btn[data-filter="${filter}"]`).addClass('active').attr('aria-pressed', 'true');
}

/**
 * Search employees by name, code, or designation
 */
function searchEmployees(query) {
  if (!query) {
    filterEmployees(currentFilter);
    return;
  }

  let filtered = employees.filter(emp =>
    emp.name.toLowerCase().includes(query) ||
    emp.code.toLowerCase().includes(query) ||
    emp.designation.toLowerCase().includes(query)
  );

  // Apply current filter on top of search
  if (currentFilter !== 'all') {
    filtered = filtered.filter(emp => emp.status === currentFilter);
  }

  renderEmployeeList(filtered);
}

/**
 * Toggle auto-refresh functionality
 */
function toggleAutoRefresh() {
  isAutoRefreshEnabled = !isAutoRefreshEnabled;

  if (isAutoRefreshEnabled) {
    // Start auto-refresh
    autoRefreshInterval = setInterval(fetchLiveLocations, pageData.settings.autoRefreshInterval);
    $('#autoRefreshText').text(pageData.labels.autoRefreshOn);
    $('#autoRefreshToggle').removeClass('btn-outline-secondary').addClass('btn-success');
    showToast(pageData.labels.autoRefreshOn, 'success');
  } else {
    // Stop auto-refresh
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
      autoRefreshInterval = null;
    }
    $('#autoRefreshText').text(pageData.labels.autoRefreshOff);
    $('#autoRefreshToggle').removeClass('btn-success').addClass('btn-outline-secondary');
    showToast(pageData.labels.autoRefreshOff, 'success');
  }
}

/**
 * Clear all markers from map
 */
function clearMarkers() {
  markers.forEach(marker => marker.setMap(null));
  markers = [];
  bounds = new google.maps.LatLngBounds();
}

/**
 * Show/hide map loading overlay
 */
function showMapLoading(show) {
  if (show) {
    $('#mapLoading').removeClass('d-none');
  } else {
    $('#mapLoading').addClass('d-none');
  }
}

/**
 * Hide loading skeleton
 */
function hideLoadingSkeleton() {
  $('#loadingSkeleton').addClass('d-none');
}

/**
 * Show empty state message
 */
function showEmptyState() {
  $('#emptyState').removeClass('d-none');
  $('#employeeCards').empty();
}

/**
 * Hide empty state message
 */
function hideEmptyState() {
  $('#emptyState').addClass('d-none');
}

/**
 * Show toast notification using Notyf
 */
function showToast(message, type = 'info') {
  if (typeof notyf !== 'undefined') {
    switch(type) {
      case 'success':
        notyf.success(message);
        break;
      case 'error':
      case 'danger':
        notyf.error(message);
        break;
      default:
        // Notyf doesn't have info/warning by default, use success for positive messages
        notyf.success(message);
    }
  } else {
    console.log(type + ': ' + message);
  }
}

/**
 * Debounce utility function to limit function calls
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Cleanup on page unload
 */
$(window).on('beforeunload', function() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
  }
});
