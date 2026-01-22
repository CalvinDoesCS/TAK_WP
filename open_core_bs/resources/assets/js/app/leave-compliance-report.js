$(function () {
  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize components
  initializeDataTables();
  loadStatistics();
});

// DataTable instances
let expiringTable, encashmentTable, policyAlertsTable;

/**
 * Initialize all DataTables
 */
function initializeDataTables() {
  // Expiring Carry Forward Leaves Table
  expiringTable = $('#expiringBalanceTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.expiringDatatable
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'employee', name: 'user.first_name', orderable: false },
      { data: 'leave_type', name: 'leave_type.name' },
      { data: 'cf_leaves', name: 'carried_forward' },
      { data: 'expiry_date', name: 'cf_expiry_date' },
      { data: 'urgency', name: 'cf_expiry_date', orderable: false }
    ],
    order: [[4, 'asc']], // Sort by expiry date ascending
    pageLength: 10,
    responsive: true,
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    drawCallback: function () {
      updateExpiringBadge();
    }
  });

  // Encashment Eligible Employees Table
  encashmentTable = $('#encashmentEligibleTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.encashmentDatatable
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'employee', name: 'user.first_name', orderable: false },
      { data: 'leave_type', name: 'leave_type.name' },
      { data: 'available_leaves', name: 'available' },
      { data: 'max_encashment', name: 'max_encashment', orderable: false },
      { data: 'eligible_for_encashment', name: 'eligible_for_encashment', orderable: false },
      { data: 'status', name: 'status', orderable: false }
    ],
    order: [[3, 'desc']], // Sort by available leaves descending
    pageLength: 10,
    responsive: true,
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    drawCallback: function () {
      updateEncashmentBadge();
    }
  });

  // Policy Alerts & Violations Table
  policyAlertsTable = $('#policyAlertsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.alertsDatatable
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'employee', name: 'user.first_name', orderable: false },
      { data: 'leave_type', name: 'leave_type.name' },
      { data: 'date_range', name: 'start_date', orderable: false },
      { data: 'days', name: 'total_days' },
      { data: 'alerts', name: 'alerts', orderable: false },
      { data: 'status', name: 'status', orderable: false }
    ],
    order: [[4, 'desc']], // Sort by days descending
    pageLength: 10,
    responsive: true,
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    drawCallback: function () {
      updatePolicyBadge();
    }
  });
}

/**
 * Load and display compliance statistics
 */
function loadStatistics() {
  $.ajax({
    url: pageData.urls.statistics,
    type: 'GET',
    success: function (response) {
      if (response.success) {
        $('#expiringCount').text(response.data.expiring_count || 0);
        $('#expiringBadge').text(response.data.expiring_count || 0);

        $('#encashmentCount').text(response.data.encashment_eligible || 0);
        $('#encashmentBadge').text(response.data.encashment_eligible || 0);

        $('#alertsCount').text(response.data.policy_alerts || 0);
        $('#policyBadge').text(response.data.policy_alerts || 0);
      }
    },
    error: function () {
      console.error('Failed to load compliance statistics');
    }
  });
}

/**
 * Update expiring badge from DataTable
 */
function updateExpiringBadge() {
  if (expiringTable) {
    const info = expiringTable.page.info();
    $('#expiringBadge').text(info.recordsTotal || 0);
  }
}

/**
 * Update encashment badge from DataTable
 */
function updateEncashmentBadge() {
  if (encashmentTable) {
    const info = encashmentTable.page.info();
    $('#encashmentBadge').text(info.recordsTotal || 0);
  }
}

/**
 * Update policy alerts badge from DataTable
 */
function updatePolicyBadge() {
  if (policyAlertsTable) {
    const info = policyAlertsTable.page.info();
    $('#policyBadge').text(info.recordsTotal || 0);
  }
}
