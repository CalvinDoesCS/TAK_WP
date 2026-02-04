/**
 * Dashboard Card View - Employee Tracking Dashboard
 * Manages real-time employee card view with status tracking
 */

'use strict';

// Global variables
let employees = [];
let currentFilter = 'all';
let autoRefreshInterval = null;
let isAutoRefreshEnabled = true; // Start enabled by default

/**
 * Format time string to a readable format (e.g., 08:30 AM)
 * @param {string} dateString
 * @returns {string}
 */
function formatTime(dateString) {
  if (!dateString) {
    return 'N/A';
  }
  try {
    const date = new Date(dateString);
    // Check if date is valid
    if (isNaN(date.getTime())) {
      return 'N/A';
    }
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
  } catch (e) {
    console.error('Error formatting time:', e);
    return 'N/A';
  }
}

/**
 * Initialize the card view
 */
function initializeCardView() {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initial data fetch
  fetchCardViewData();

  // Start auto-refresh by default
  startAutoRefresh();

  // Setup event listeners
  setupEventListeners();
}

/**
 * Setup all event listeners
 */
function setupEventListeners() {
  // Manual refresh button
  $('#refreshBtn').on('click', function() {
    $(this).find('i').addClass('bx-spin');
    fetchCardViewData();
  });

  // Auto-refresh toggle
  $('#autoRefreshSwitch').on('change', function() {
    isAutoRefreshEnabled = $(this).is(':checked');

    if (isAutoRefreshEnabled) {
      startAutoRefresh();
      showToast(pageData.labels.autoRefreshOn, 'success');
    } else {
      stopAutoRefresh();
      showToast(pageData.labels.autoRefreshOff, 'success');
    }
  });

  // Filter buttons
  $('.filter-btn').on('click', function() {
    const filter = $(this).data('filter');
    setActiveFilter(filter);
    filterEmployees(filter);
  });
}

/**
 * Fetch card view data from server
 */
function fetchCardViewData() {
  $.ajax({
    url: pageData.urls.cardViewAjax,
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      employees = response.employees || [];
      processCardViewData(response);
      hideLoadingSkeleton();

      // Remove spin animation from refresh button
      $('#refreshBtn').find('i').removeClass('bx-spin');
    },
    error: function(xhr) {
      console.error('Error fetching card view data', xhr);
      hideLoadingSkeleton();
      $('#refreshBtn').find('i').removeClass('bx-spin');

      // Show error toast
      showToast(pageData.labels.loadingError + '. ' + pageData.labels.tryAgain, 'error');
    }
  });
}

/**
 * Process and display card view data
 */
function processCardViewData(data) {
  if (!data.employees || data.employees.length === 0) {
    $('#employeeGrid').addClass('d-none');
    return;
  }

  $('#employeeGrid').removeClass('d-none');

  // Update all cards with fresh data
  data.employees.forEach(employee => {
    updateEmployeeCard(employee);
  });

  // Update filter counters from server statistics
  if (data.statistics) {
    $('#allCount').text(data.statistics.totalCheckedIn);
    $('#onlineCount').text(data.statistics.totalOnline);
    $('#offlineCount').text(data.statistics.totalOffline);
  } else {
    // Fallback to counting from employees data
    updateFilterCounters(data.employees);
  }

  // Apply current filter
  filterEmployees(currentFilter);
}

/**
 * Update individual employee card with new data
 */
function updateEmployeeCard(employee) {
  const cardCol = $(`.employee-card-col[data-employee-id="${employee.id}"]`);

  if (cardCol.length === 0) {
    return; // Card doesn't exist in DOM
  }

  // Update status data attribute
  cardCol.attr('data-status', employee.isOnline ? 'online' : 'offline');

  // Update battery level
  $(`#battery-${employee.id} span`).text(`${employee.batteryLevel}%`);

  // Update battery icon based on level
  const batteryIcon = $(`#battery-${employee.id} i`);
  if (employee.batteryLevel >= 75) {
    batteryIcon.attr('class', 'bx bx-battery text-success');
  } else if (employee.batteryLevel >= 50) {
    batteryIcon.attr('class', 'bx bx-battery text-primary');
  } else if (employee.batteryLevel >= 25) {
    batteryIcon.attr('class', 'bx bx-battery text-warning');
  } else {
    batteryIcon.attr('class', 'bx bx-battery text-danger');
  }

  // Update WiFi status
  const wifiIcon = $(`#wifi-${employee.id} i`);
  if (employee.isWifiOn) {
    wifiIcon.attr('class', 'bx bx-wifi text-success');
  } else {
    wifiIcon.attr('class', 'bx bx-wifi-off text-danger');
  }

  // Update GPS status
  const gpsIcon = $(`#gps-${employee.id} i`);
  if (employee.isGpsOn) {
    gpsIcon.attr('class', 'bx bx-current-location text-success');
  } else {
    gpsIcon.attr('class', 'bx bx-current-location text-danger');
  }

  // Update attendance times
  $(`#in-time-${employee.id}`).text(formatTime(employee.attendanceInAt));
  $(`#out-time-${employee.id}`).text(formatTime(employee.attendanceOutAt));

  // Update metrics
  $(`#visits-${employee.id}`).html(
    `<i class="bx bx-map text-primary"></i> ${employee.visitsCount} ${pageData.labels.visits}`
  );
  $(`#orders-${employee.id}`).html(
    `<i class="bx bx-cart text-success"></i> ${employee.ordersCount} ${pageData.labels.orders}`
  );
  $(`#forms-${employee.id}`).html(
    `<i class="bx bx-file text-info"></i> ${employee.formsFilled} ${pageData.labels.forms}`
  );

  // Update last updated time
  $(`#updated-${employee.id}`).text(`${pageData.labels.lastUpdated}: ${employee.updatedAt}`);
}

/**
 * Update filter counters based on employee data
 */
function updateFilterCounters(employees) {
  let allCount = 0;
  let onlineCount = 0;
  let offlineCount = 0;

  employees.forEach(employee => {
    allCount++;

    if (employee.isOnline) {
      onlineCount++;
    } else {
      offlineCount++;
    }
  });

  $('#allCount').text(allCount);
  $('#onlineCount').text(onlineCount);
  $('#offlineCount').text(offlineCount);
}

/**
 * Filter employees by status
 */
function filterEmployees(filter) {
  currentFilter = filter;

  $('.employee-card-col').each(function() {
    const $card = $(this);
    const status = $card.attr('data-status');

    let shouldShow = false;

    switch (filter) {
      case 'all':
        shouldShow = true;
        break;
      case 'online':
        shouldShow = (status === 'online');
        break;
      case 'offline':
        shouldShow = (status === 'offline');
        break;
    }

    if (shouldShow) {
      $card.removeClass('d-none');
    } else {
      $card.addClass('d-none');
    }
  });
}

/**
 * Set active filter button
 */
function setActiveFilter(filter) {
  $('.filter-btn').removeClass('active');
  $(`.filter-btn[data-filter="${filter}"]`).addClass('active');
}

/**
 * Start auto-refresh interval
 */
function startAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
  }

  autoRefreshInterval = setInterval(fetchCardViewData, pageData.settings.autoRefreshInterval);
}

/**
 * Stop auto-refresh interval
 */
function stopAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
    autoRefreshInterval = null;
  }
}

/**
 * Hide loading skeleton
 */
function hideLoadingSkeleton() {
  $('#loadingSkeleton').addClass('d-none');
  $('#employeeGrid').removeClass('d-none');
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
        notyf.success(message);
    }
  } else {
    console.log(type + ': ' + message);
  }
}

/**
 * Cleanup on page unload
 */
$(window).on('beforeunload', function() {
  stopAutoRefresh();
});

// Initialize when document is ready
$(document).ready(function() {
  initializeCardView();
});