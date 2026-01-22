/**
 * Employee List Page
 */

'use strict';

$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Variables
  const employeeView = baseUrl + 'employees/view/';
  const dt_user_table = $('.datatables-users');

  const statusObj = {
    inactive: { title: 'Inactive', class: 'bg-label-secondary' },
    active: { title: 'Active', class: 'bg-label-success' },
    retired: { title: 'Retired', class: 'bg-label-secondary' },
    onboarding: { title: 'Onboarding', class: 'bg-label-info' },
    relieved: { title: 'Relieved', class: 'bg-label-danger' },
    terminated: { title: 'Terminated', class: 'bg-label-danger' },
    probation: { title: 'Probation', class: 'bg-label-warning' },
    resigned: { title: 'Resigned', class: 'bg-label-danger' },
    suspended: { title: 'Suspended', class: 'bg-label-danger' },
    default: { title: 'Unknown', class: 'bg-label-secondary' }
  };

  // Initialize Select2
  $('#roleFilter, #teamFilter, #designationFilter').select2({
    placeholder: function() {
      return $(this).data('placeholder');
    },
    allowClear: true
  });

  // Get status from URL query parameter
  const urlParams = new URLSearchParams(window.location.search);
  const statusFromUrl = urlParams.get('status');

  // Initialize DataTable
  let dt_user;
  if (dt_user_table.length) {
    dt_user = dt_user_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'employees/indexAjax',
        type: 'POST',
        data: function (d) {
          // Use status from URL if available, otherwise from filter
          d.statusFilter = statusFromUrl || $('#statusFilter').val();
          d.roleFilter = $('#roleFilter').val();
          d.teamFilter = $('#teamFilter').val();
          d.designationFilter = $('#designationFilter').val();
          d.attendanceTypeFilter = $('#attendanceTypeFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'phone' },
        { data: 'role' },
        { data: 'attendance_type' },
        { data: 'team' },
        { data: 'status' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          // Responsive control column
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function () {
            return '';
          }
        },
        {
          // ID
          targets: 1,
          searchable: false,
          orderable: true,
          render: function (data, type, full) {
            return `<span class="text-muted">${full.id}</span>`;
          }
        },
        {
          // Employee (with avatar)
          targets: 2,
          responsivePriority: 1,
          render: function (data, type, full) {
            const name = full['name'];
            const code = full['code'];

            // Avatar
            let avatar;
            if (full['profile_picture']) {
              avatar = `<img src="${full['profile_picture']}" alt="Avatar" class="rounded-circle" />`;
            } else {
              const stateNum = Math.floor(Math.random() * 6);
              const states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
              const state = states[stateNum];
              const initials = name.match(/\b\w/g) || [];
              const initialsStr = ((initials.shift() || '') + (initials.pop() || '')).toUpperCase();
              avatar = `<span class="avatar-initial rounded-circle bg-label-${state}">${initialsStr}</span>`;
            }

            return `
              <div class="d-flex justify-content-start align-items-center user-name">
                <div class="avatar-wrapper">
                  <div class="avatar avatar-sm me-3">
                    ${avatar}
                  </div>
                </div>
                <div class="d-flex flex-column">
                  <a href="${employeeView}${full['id']}" class="text-heading text-truncate">
                    <span class="fw-medium">${name}</span>
                  </a>
                  <small class="text-muted">${code}</small>
                </div>
              </div>
            `;
          }
        },
        {
          // Phone
          targets: 3,
          render: function (data, type, full) {
            return `<span>${full['phone']}</span>`;
          }
        },
        {
          // Role
          targets: 4,
          render: function (data, type, full) {
            return `<span>${full['role']}</span>`;
          }
        },
        {
          // Attendance Type
          targets: 5,
          render: function (data, type, full) {
            const attendanceType = full['attendance_type'];
            return `<span class="badge bg-label-primary text-capitalize">${attendanceType}</span>`;
          }
        },
        {
          // Team
          targets: 6,
          render: function (data, type, full) {
            const team = full['team'];
            return team ? `<span>${team}</span>` : '<span class="text-muted">N/A</span>';
          }
        },
        {
          // Status
          targets: 7,
          render: function (data, type, full) {
            const status = full['status'];
            const statusInfo = statusObj[status] || statusObj.default;
            return `<span class="badge ${statusInfo.class} text-capitalize">${statusInfo.title}</span>`;
          }
        },
        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full) {
            return `
              <div class="d-flex align-items-center gap-2">
                <a href="${employeeView}${full['id']}" class="btn btn-sm btn-icon" title="View Details">
                  <i class="bx bx-show"></i>
                </a>
              </div>
            `;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"f>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search employees...',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Details of ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  // Filter change handlers
  $('#statusFilter, #roleFilter, #teamFilter, #designationFilter, #attendanceTypeFilter').on('change', function () {
    dt_user.draw();
  });

  // Reset filters
  $('#resetFilters').on('click', function () {
    $('#statusFilter').val('').trigger('change');
    $('#roleFilter').val('').trigger('change');
    $('#teamFilter').val('').trigger('change');
    $('#designationFilter').val('').trigger('change');
    $('#attendanceTypeFilter').val('').trigger('change');
    dt_user.draw();
  });

  // Filter form control sizing
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
