$(function () {
  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize components
  initializeDatePickers();
  initializeSelect2();
  initializeDataTable();
  loadStatistics();

  // Event listeners
  $('#applyFilters').on('click', function () {
    historyTable.ajax.reload();
    loadStatistics();
  });

  $('#resetFilters').on('click', function () {
    resetFilters();
  });
});

// DataTable instance
let historyTable;

/**
 * Initialize Flatpickr date pickers
 */
function initializeDatePickers() {
  if ($('#dateFromFilter').length) {
    flatpickr('#dateFromFilter', {
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }

  if ($('#dateToFilter').length) {
    flatpickr('#dateToFilter', {
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
  // Employee Filter with AJAX
  if ($('#employeeFilter').length) {
    $('#employeeFilter').select2({
      placeholder: pageData.labels.employee,
      allowClear: true,
      width: '100%',
      ajax: {
        url: pageData.urls.employeeSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term,
            page: params.page || 1
          };
        },
        processResults: function (data) {
          return {
            results: data.data,  // Use data as-is, it already has id and text
            pagination: {
              more: data.pagination?.more || false
            }
          };
        },
        cache: true
      }
    });
  }

  // Department Filter
  if ($('#departmentFilter').length) {
    $('#departmentFilter').select2({
      placeholder: function () {
        return $(this).data('placeholder');
      },
      allowClear: true,
      width: '100%'
    });
  }

  // Leave Type Filter
  if ($('#leaveTypeFilter').length) {
    $('#leaveTypeFilter').select2({
      placeholder: function () {
        return $(this).data('placeholder');
      },
      allowClear: true,
      width: '100%'
    });
  }

  // Status Filter
  if ($('#statusFilter').length) {
    $('#statusFilter').select2({
      placeholder: function () {
        return $(this).data('placeholder');
      },
      allowClear: true,
      width: '100%'
    });
  }
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
  historyTable = $('#leaveHistoryTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.date_from = $('#dateFromFilter').val();
        d.date_to = $('#dateToFilter').val();
        d.employee_id = $('#employeeFilter').val();
        d.department_id = $('#departmentFilter').val();
        d.leave_type_id = $('#leaveTypeFilter').val();
        d.status = $('#statusFilter').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'employee', name: 'user.first_name', orderable: false },
      { data: 'leave_type', name: 'leave_type.name' },
      { data: 'date_range', name: 'start_date', orderable: false },
      { data: 'total_days', name: 'total_days' },
      { data: 'status', name: 'status', orderable: false },
      { data: 'requested_on', name: 'created_at' },
      { data: 'action_by', name: 'actioned_by', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[6, 'desc']], // Sort by requested date descending
    pageLength: 25,
    responsive: true,
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    }
  });
}

/**
 * Load and display statistics
 */
function loadStatistics() {
  const data = {
    date_from: $('#dateFromFilter').val(),
    date_to: $('#dateToFilter').val(),
    employee_id: $('#employeeFilter').val(),
    department_id: $('#departmentFilter').val(),
    leave_type_id: $('#leaveTypeFilter').val(),
    status: $('#statusFilter').val()
  };

  $.ajax({
    url: pageData.urls.statistics,
    type: 'GET',
    data: data,
    success: function (response) {
      if (response.success) {
        $('#totalRequests').text(response.data.total_requests || 0);
        $('#approvedCount').text(response.data.approved_count || 0);
        $('#pendingCount').text(response.data.pending_count || 0);
        $('#rejectedCount').text(response.data.rejected_count || 0);
      }
    },
    error: function () {
      console.error('Failed to load statistics');
    }
  });
}

/**
 * Reset filters to default values
 */
function resetFilters() {
  $('#dateFromFilter').val('');
  $('#dateToFilter').val('');
  $('#employeeFilter').val(null).trigger('change');
  $('#departmentFilter').val('').trigger('change');
  $('#leaveTypeFilter').val('').trigger('change');
  $('#statusFilter').val('').trigger('change');

  historyTable.ajax.reload();
  loadStatistics();
}

/**
 * View leave details function - redirects to leave show page
 */
window.viewLeaveDetails = function (id) {
  const url = pageData.urls.leaveShow.replace(':id', id);
  window.location.href = url;
};
