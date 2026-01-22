'use strict';

$(function () {
  var dtTable = $('.datatables-ipgroups');

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // IP groups datatable
  if (dtTable.length) {
    var dtIpGroup = dtTable.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        error: function (xhr, error, code) {
          console.log('Error: ' + error);
          console.log('Code: ' + code);
          console.log('Response: ' + xhr.responseText);
        }
      },
      columns: [
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'description' },
        { data: 'ip_addresses_count' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // id
          targets: 1,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return '<span class="id">' + full['id'] + '</span>';
          }
        },
        {
          // name
          targets: 2,
          className: 'text-start',
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var showUrl = pageData.urls.show.replace(':id', full['id']);
            return '<a href="' + showUrl + '" class="text-body fw-medium">' + full['name'] + '</a>';
          }
        },
        {
          // code
          targets: 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return '<span class="text-body">' + full['code'] + '</span>';
          }
        },
        {
          // description
          targets: 4,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var description = full['description'] ?? 'N/A';
            return '<span class="text-body">' + description + '</span>';
          }
        },
        {
          // ip addresses count
          targets: 5,
          className: 'text-center',
          render: function (data, type, full, meta) {
            var count = full['ip_addresses_count'] ?? 0;
            return '<span class="badge bg-label-primary">' + count + '</span>';
          }
        },
        {
          // status
          targets: 6,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var checked = full['status'] === 'active' ? 'checked' : '';

            return `
              <div class="d-flex justify-content-left">
                <label class="switch mb-0">
                  <input
                    type="checkbox"
                    class="switch-input status-toggle"
                    id="statusToggle${full['id']}"
                    data-id="${full['id']}"
                    ${checked} />
                  <span class="switch-toggle-slider">
                    <span class="switch-on"><i class="bx bx-check"></i></span>
                    <span class="switch-off"><i class="bx bx-x"></i></span>
                  </span>
                </label>
              </div>
            `;
          }
        },
        {
          // created at
          targets: 7,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return '<span class="text-body">' + full['created_at'] + '</span>';
          }
        },
        {
          // Actions
          targets: 8,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var showUrl = pageData.urls.show.replace(':id', full['id']);
            return (
              '<div class="d-flex align-items-left gap-50">' +
              `<a href="${showUrl}" class="btn btn-sm btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="${pageData.labels.actions}"><i class="bx bx-show"></i></a>` +
              `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddOrUpdateIpGroup"><i class="bx bx-pencil"></i></button>` +
              `<button class="btn btn-sm btn-icon text-danger delete-record" data-id="${full['id']}"><i class="bx bx-trash"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [7, 10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: pageData.labels.searchPlaceholder,
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      buttons: [],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
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

  var offCanvasForm = $('#offcanvasAddOrUpdateIpGroup');

  // Add new button click
  $(document).on('click', '.add-new', function () {
    $('#id').val('');
    $('#name').val('');
    $('#code').val('');
    $('#description').val('');
    $('#offcanvasIpGroupLabel').html(pageData.labels.create);
    fv.resetForm(true);
  });

  const addIpGroupForm = document.getElementById('ipGroupForm');

  // Edit record button click
  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id');
    var dtrModal = $('.dtr-bs-modal.show');

    // Hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // Change the title of offcanvas
    $('#offcanvasIpGroupLabel').html(pageData.labels.edit);

    // Get data
    var url = pageData.urls.getById.replace(':id', id);
    $.get(url, function (response) {
      var data = response.data;
      $('#id').val(data.id);
      $('#name').val(data.name);
      $('#code').val(data.code);
      $('#description').val(data.description);
    });
  });

  // Form validation
  const fv = FormValidation.formValidation(addIpGroupForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: pageData.labels.required
          }
        }
      },
      code: {
        validators: {
          notEmpty: {
            message: pageData.labels.required
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: function (field, ele) {
          return '.mb-6';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function () {
    // Adding or updating IP group when form successfully validates
    $.ajax({
      data: $('#ipGroupForm').serialize(),
      url: pageData.urls.addOrUpdate,
      type: 'POST',
      success: function (response) {
        if (response.status === 'success') {
          offCanvasForm.offcanvas('hide');

          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          dtIpGroup.draw();
        }
      },
      error: function (err) {
        var responseTemp = err.responseJSON || JSON.parse(err.responseText);

        Swal.fire({
          title: pageData.labels.error,
          text: responseTemp.message || 'Please try again',
          icon: 'error',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  });

  // Clear form data when offcanvas hidden
  offCanvasForm.on('hidden.bs.offcanvas', function () {
    fv.resetForm(true);
  });

  // Delete record
  $(document).on('click', '.delete-record', function () {
    var id = $(this).data('id');
    var dtrModal = $('.dtr-bs-modal.show');

    // Hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // SweetAlert for confirmation of delete
    Swal.fire({
      title: pageData.labels.deleteConfirm,
      text: pageData.labels.deleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Delete the data
        var url = pageData.urls.delete.replace(':id', id);
        $.ajax({
          type: 'DELETE',
          url: url,
          success: function (response) {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: pageData.labels.deletedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });

            dtIpGroup.draw();
          },
          error: function (error) {
            console.log(error);
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: 'Failed to delete IP group',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        });
      }
    });
  });

  // Status toggle
  $(document).on('change', '.status-toggle', function () {
    var id = $(this).data('id');
    var status = $(this).is(':checked') ? 'active' : 'inactive';

    var url = pageData.urls.changeStatus.replace(':id', id);
    $.ajax({
      url: url,
      type: 'POST',
      data: {
        status: status,
        _token: pageData.csrfToken
      },
      success: function (response) {
        dtIpGroup.draw();
      },
      error: function (response) {
        console.log(response);
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: 'Failed to update status',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  });
});
