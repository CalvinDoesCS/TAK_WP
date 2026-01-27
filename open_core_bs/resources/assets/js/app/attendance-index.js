/* Attendance Index */

'use strict';

$(function () {
  console.log('Attendance Index');

  // Initialize Flatpickr for date input
  flatpickr('#date', {
    dateFormat: 'Y-m-d',
    defaultDate: $('#date').val() || new Date()
  });

  var dataTable = $('#attendanceTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '/hrcore/attendance/datatable',
      data: function data(d) {
        d.userId = $('#userId').val();
        d.date = $('#date').val();
      }
    },
    columns: [
      {data: 'id', name: 'id'},
      {data: 'user', name: 'user', orderable: false, searchable: true},
      {data: 'shift', name: 'shift'},
      {data: 'check_in_time', name: 'check_in_time'},
      {data: 'check_out_time', name: 'check_out_time'},
      {data: 'late_indicator', name: 'late_indicator', orderable: false, searchable: false},
      {data: 'early_indicator', name: 'early_indicator', orderable: false, searchable: false},
      {data: 'overtime_indicator', name: 'overtime_indicator', orderable: false, searchable: false},
      {data: 'actions', name: 'actions', orderable: false, searchable: false}
    ],
    order: [[0, 'desc']]
  });

  $('#userId').select2();

  $('#userId').on('change', function () {
    dataTable.draw();
  });

  $('#date').on('change', function () {
    dataTable.draw();
  });
});
