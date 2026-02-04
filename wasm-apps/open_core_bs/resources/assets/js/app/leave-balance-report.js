$(function () {
  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize components
  initializeSelect2();
  initializeDataTable();
  loadStatistics();

  // Event listeners - using event delegation for better reliability
  $(document).on('click', '#applyFilters', function (e) {
    e.preventDefault();

    if (typeof balanceTable !== 'undefined' && balanceTable !== null) {
      balanceTable.ajax.reload(null, false); // false = don't reset pagination
      loadStatistics();
    } else {
      console.error('DataTable not initialized');
    }
  });

  $(document).on('click', '#resetFilters', function (e) {
    e.preventDefault();
    resetFilters();
  });
});

// DataTable instance
let balanceTable;

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
  // Employee Filter with AJAX
  if ($('#employeeFilter').length) {
    $('#employeeFilter').select2({
      placeholder: pageData.labels.employee || 'Select Employee',
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
            results: data.data || [],  // Use data as-is, it already has id and text
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
      placeholder: 'Select Department',
      allowClear: true,
      width: '100%'
    });
  }

  // Leave Type Filter
  if ($('#leaveTypeFilter').length) {
    $('#leaveTypeFilter').select2({
      placeholder: 'Select Leave Type',
      allowClear: true,
      width: '100%'
    });
  }
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
  if (!$('#leaveBalanceTable').length) {
    console.error('Table element not found');
    return;
  }

  try {
    balanceTable = $('#leaveBalanceTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        type: 'GET',
        data: function (d) {
          // Add filter values to the request
          d.year = $('#yearFilter').val();
          d.employee_id = $('#employeeFilter').val();
          d.department_id = $('#departmentFilter').val();
          d.leave_type_id = $('#leaveTypeFilter').val();
          d.expiring_soon = $('#expiringSoonFilter').is(':checked') ? '1' : '0';
        },
        error: function (xhr, error, code) {
          console.error('DataTable AJAX error:', error, code);
          console.error('Response:', xhr.responseText);
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'employee', name: 'employee', orderable: false, searchable: false },
        { data: 'leave_type', name: 'leaveType.name', orderable: false },
        { data: 'entitled', name: 'entitled_leaves', orderable: true },
        { data: 'used', name: 'used_leaves', orderable: true },
        { data: 'available', name: 'available_leaves', orderable: true },
        { data: 'carried_forward', name: 'carried_forward_leaves', orderable: true },
        { data: 'expiry_date', name: 'carry_forward_expiry_date', orderable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[3, 'desc']], // Sort by entitled
      pageLength: 25,
      responsive: false,
      language: {
        search: pageData.labels.search || 'Search',
        processing: pageData.labels.processing || 'Processing...',
        lengthMenu: pageData.labels.lengthMenu || 'Show _MENU_ entries',
        info: pageData.labels.info || 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: pageData.labels.infoEmpty || 'Showing 0 to 0 of 0 entries',
        emptyTable: pageData.labels.emptyTable || 'No balance records found',
        paginate: pageData.labels.paginate || {
          first: 'First',
          last: 'Last',
          next: 'Next',
          previous: 'Previous'
        }
      }
    });
  } catch (error) {
    console.error('Error initializing DataTable:', error);
  }
}

/**
 * Load and display statistics
 */
function loadStatistics() {
  const data = {
    year: $('#yearFilter').val(),
    employee_id: $('#employeeFilter').val(),
    department_id: $('#departmentFilter').val(),
    leave_type_id: $('#leaveTypeFilter').val(),
    expiring_soon: $('#expiringSoonFilter').is(':checked') ? '1' : '0'
  };

  $.ajax({
    url: pageData.urls.statistics,
    type: 'GET',
    data: data,
    success: function (response) {
      if (response.success) {
        $('#totalEmployees').text(response.data.total_employees || 0);
        $('#totalEntitled').text(parseFloat(response.data.total_entitled || 0).toFixed(2));
        $('#totalUsed').text(parseFloat(response.data.total_used || 0).toFixed(2));
        $('#totalAvailable').text(parseFloat(response.data.total_available || 0).toFixed(2));
      }
    },
    error: function (xhr, status, error) {
      console.error('Failed to load statistics:', error);
    }
  });
}

/**
 * Reset filters to default values
 */
function resetFilters() {
  const currentYear = new Date().getFullYear();

  $('#yearFilter').val(currentYear);
  $('#employeeFilter').val(null).trigger('change');
  $('#departmentFilter').val('').trigger('change');
  $('#leaveTypeFilter').val('').trigger('change');
  $('#expiringSoonFilter').prop('checked', false);

  if (typeof balanceTable !== 'undefined' && balanceTable !== null) {
    balanceTable.ajax.reload(null, false);
    loadStatistics();
  }
}

/**
 * View balance details function
 */
window.viewBalanceDetails = function (userId) {
  const modal = new bootstrap.Modal(document.getElementById('balanceDetailsModal'));
  const modalBody = $('#balanceDetailsContent');

  // Show loading spinner
  modalBody.html(`
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">${pageData.labels.processing || 'Loading...'}</span>
      </div>
    </div>
  `);

  // Show modal
  modal.show();

  // Fetch balance details
  const url = pageData.urls.balanceDetails.replace(':userId', userId);

  $.ajax({
    url: url,
    type: 'GET',
    data: {
      year: $('#yearFilter').val(),
      leave_type_id: $('#leaveTypeFilter').val()
    },
    success: function (response) {
      if (response.success && response.data.balances.length > 0) {
        let html = `
          <div class="mb-3">
            <h6 class="mb-2">${response.data.employee_name}</h6>
            <small class="text-muted">${response.data.employee_code || ''}</small>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>${pageData.labels.leaveType}</th>
                  <th class="text-end">${pageData.labels.entitled}</th>
                  <th class="text-end">${pageData.labels.used}</th>
                  <th class="text-end">${pageData.labels.available}</th>
                  <th class="text-end">${pageData.labels.carriedForward}</th>
                  <th>${pageData.labels.expiryDate}</th>
                </tr>
              </thead>
              <tbody>
        `;

        response.data.balances.forEach(function (balance) {
          html += `
            <tr>
              <td>${balance.leave_type_name}</td>
              <td class="text-end">${parseFloat(balance.entitled).toFixed(2)}</td>
              <td class="text-end">${parseFloat(balance.used).toFixed(2)}</td>
              <td class="text-end">${parseFloat(balance.available).toFixed(2)}</td>
              <td class="text-end">${parseFloat(balance.carried_forward).toFixed(2)}</td>
              <td>${balance.carry_forward_expiry_date || '-'}</td>
            </tr>
          `;
        });

        html += `
              </tbody>
            </table>
          </div>
        `;

        modalBody.html(html);
      } else {
        modalBody.html(`
          <div class="text-center py-5">
            <i class="bx bx-info-circle bx-lg text-muted mb-3"></i>
            <p class="text-muted">${pageData.labels.noData || 'No data available'}</p>
          </div>
        `);
      }
    },
    error: function () {
      modalBody.html(`
        <div class="text-center py-5">
          <i class="bx bx-error-circle bx-lg text-danger mb-3"></i>
          <p class="text-danger">Failed to load balance details</p>
        </div>
      `);
    }
  });
};
