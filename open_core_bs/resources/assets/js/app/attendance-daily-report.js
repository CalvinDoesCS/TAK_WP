/**
 * Daily Attendance Report
 */
'use strict';

$(function () {
  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      dropdownParent: $('#filtersOffcanvas')
    });
  }

  // Initialize Flatpickr for date input
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      defaultDate: pageData.currentDate,
      maxDate: 'today'
    });
  }

  // Initialize DataTable
  let table = $('#dailyReportTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.date = $('#filterDate').val();
        d.user_id = $('#filterUser').val();
        d.department_id = $('#filterDepartment').val();
        d.shift_id = $('#filterShift').val();
        d.status = $('#filterStatus').val();
      }
    },
    columns: [
      { data: 'user', name: 'user.first_name', orderable: true },
      { data: 'date', name: 'date', orderable: true },
      { data: 'check_in', name: 'check_in_time', orderable: true },
      { data: 'check_out', name: 'check_out_time', orderable: true },
      { data: 'shift', name: 'shift.name', orderable: true },
      { data: 'working_hours', name: 'working_hours', orderable: true },
      { data: 'late_hours', name: 'late_hours', orderable: true },
      { data: 'early_hours', name: 'early_hours', orderable: true },
      { data: 'overtime_hours', name: 'overtime_hours', orderable: true },
      { data: 'status', name: 'status', orderable: true },
      { data: 'location', name: 'location', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[1, 'desc'], [0, 'asc']],
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    responsive: true,
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
  });

  // Show filters offcanvas
  $('#filterBtn').on('click', function () {
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('filtersOffcanvas'));
    offcanvas.show();
  });

  // Apply filters
  $('#filtersForm').on('submit', function (e) {
    e.preventDefault();
    table.ajax.reload();
    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('filtersOffcanvas'));
    if (offcanvas) {
      offcanvas.hide();
    }
  });

  // Reset filters
  $('#resetFiltersBtn').on('click', function () {
    $('#filterDate').val(pageData.currentDate);
    $('#filterUser').val('').trigger('change');
    $('#filterDepartment').val('').trigger('change');
    $('#filterShift').val('').trigger('change');
    $('#filterStatus').val('');

    // Trigger flatpickr to update
    if ($('.flatpickr-date')[0]._flatpickr) {
      $('.flatpickr-date')[0]._flatpickr.setDate(pageData.currentDate);
    }

    table.ajax.reload();
    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('filtersOffcanvas'));
    if (offcanvas) {
      offcanvas.hide();
    }
  });
});
