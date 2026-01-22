$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize Select2
  $('.select2').select2();

  // Initialize Flatpickr for date range
  const dateRangePicker = flatpickr('#dateRange', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    maxDate: 'today'
  });

  // Load initial statistics
  loadStatistics();

  // Initialize DataTable
  const lifecycleEventsTable = $('#lifecycleEventsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.lifecycleEventsData,
      data: function (d) {
        const dateRange = $('#dateRange').val();
        const dates = dateRange ? dateRange.split(' to ') : [];

        d.date_from = dates[0] || '';
        d.date_to = dates[1] || dates[0] || '';
        d.event_type = $('#eventTypeFilter').val();
        d.event_category = $('#categoryFilter').val();
        d.department_id = $('#departmentFilter').val();
      }
    },
    columns: [
      { data: 'event_date', name: 'event_date' },
      { data: 'employee', name: 'employee', orderable: false, searchable: true },
      { data: 'event_type', name: 'event_type' },
      { data: 'description', name: 'description', orderable: false },
      { data: 'triggered_by', name: 'triggered_by', orderable: false, searchable: true },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[0, 'desc']],
    language: {
      processing: pageData.labels.loading,
      emptyTable: pageData.labels.noData
    }
  });

  // Apply Filters
  $('#applyFilters').on('click', function () {
    lifecycleEventsTable.ajax.reload();
    loadStatistics();
  });

  // Reset Filters
  $('#resetFilters').on('click', function () {
    dateRangePicker.clear();
    $('#categoryFilter').val('').trigger('change');
    $('#eventTypeFilter').val('').trigger('change');
    $('#departmentFilter').val('').trigger('change');
    lifecycleEventsTable.ajax.reload();
    loadStatistics();
  });

  // Category filter change - filter event types
  $('#categoryFilter').on('change', function () {
    const category = $(this).val();
    const $eventTypeFilter = $('#eventTypeFilter');

    if (category) {
      $eventTypeFilter.find('option').each(function () {
        const optionCategory = $(this).data('category');
        if ($(this).val() === '' || optionCategory === category) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    } else {
      $eventTypeFilter.find('option').show();
    }

    $eventTypeFilter.val('').trigger('change');
  });

  // Load Statistics
  function loadStatistics() {
    const dateRange = $('#dateRange').val();
    const dates = dateRange ? dateRange.split(' to ') : [];

    const filters = {
      date_from: dates[0] || '',
      date_to: dates[1] || dates[0] || ''
    };

    $.ajax({
      url: pageData.urls.lifecycleEventStatistics,
      method: 'GET',
      data: filters,
      success: function (response) {
        if (response.success) {
          updateStatistics(response.data);
        }
      },
      error: function () {
        showToast('error', pageData.labels.error);
      }
    });
  }

  // Update Statistics Cards
  function updateStatistics(data) {
    $('#totalEvents').text(data.total_events);
    $('#recentEvents').text(data.recent_events);

    // Most common event
    if (data.events_by_type && data.events_by_type.length > 0) {
      const mostCommon = data.events_by_type.reduce((prev, current) =>
        prev.count > current.count ? prev : current
      );
      $('#mostCommonEvent').text(mostCommon.type);
      $('#mostCommonCount').text(mostCommon.count + ' ' + pageData.labels.events);
    } else {
      $('#mostCommonEvent').text('-');
      $('#mostCommonCount').text('0 ' + pageData.labels.events);
    }

    // Top category
    if (data.events_by_category && data.events_by_category.length > 0) {
      const topCategory = data.events_by_category.reduce((prev, current) =>
        prev.count > current.count ? prev : current
      );
      $('#topCategory').text(topCategory.category);
      $('#topCategoryCount').text(topCategory.count + ' ' + pageData.labels.events);
    } else {
      $('#topCategory').text('-');
      $('#topCategoryCount').text('0 ' + pageData.labels.events);
    }
  }

  // Helper function for toast notifications
  function showToast(type, message) {
    // Implement toast notification based on your project's toast library
    console.log(type + ': ' + message);
  }
});

// Global functions for onclick handlers (must be outside jQuery ready function)
window.viewEventDetails = function(element) {
  try {
    // Get the event data from the data attribute
    const eventDataJson = element.getAttribute('data-event');
    if (!eventDataJson) {
      throw new Error('No event data found');
    }

    const eventData = JSON.parse(eventDataJson);

    // Build metadata HTML
    let metadataHtml = '';
    if (eventData.metadata && Object.keys(eventData.metadata).length > 0) {
      metadataHtml = '<div class="mt-3"><h6>Additional Details:</h6><ul class="list-unstyled">';
      for (const [key, value] of Object.entries(eventData.metadata)) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        metadataHtml += `<li><strong>${label}:</strong> ${value}</li>`;
      }
      metadataHtml += '</ul></div>';
    }

    const htmlContent = `
      <div class="text-start">
        <div class="row mb-2">
          <div class="col-5"><strong>Employee:</strong></div>
          <div class="col-7">${eventData.employee} (${eventData.employee_code})</div>
        </div>
        <div class="row mb-2">
          <div class="col-5"><strong>Event Type:</strong></div>
          <div class="col-7">${eventData.event_type}</div>
        </div>
        <div class="row mb-2">
          <div class="col-5"><strong>Event Date:</strong></div>
          <div class="col-7">${eventData.event_date}</div>
        </div>
        <div class="row mb-2">
          <div class="col-5"><strong>Triggered By:</strong></div>
          <div class="col-7">${eventData.triggered_by}</div>
        </div>
        <div class="row mb-2">
          <div class="col-5"><strong>Notes:</strong></div>
          <div class="col-7">${eventData.notes}</div>
        </div>
        ${metadataHtml}
      </div>
    `;

    Swal.fire({
      title: 'Event Details',
      html: htmlContent,
      icon: 'info',
      width: '600px',
      confirmButtonText: 'Close',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  } catch (error) {
    console.error('Error loading event details:', error);
    Swal.fire({
      title: 'Error',
      text: 'Failed to load event details',
      icon: 'error',
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }
};
