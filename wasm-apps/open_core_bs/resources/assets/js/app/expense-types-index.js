$(function () {
  var dtTable = $('.datatables-expenseTypes');

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  if (dtTable.length) {
    var dtExpenseTypes = dtTable.DataTable({
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
      columnDefs: [
        {
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
          targets: 1,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + full['name'] + '</span>';
          }
        },
        {
          targets: 2,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['description'] ? full['description'] : '<span class="text-muted">-</span>';
          }
        },
        {
          targets: 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['status'] === pageData.status.active
              ? '<span class="badge bg-label-success">' + pageData.labels.active + '</span>'
              : '<span class="badge bg-label-warning">' + pageData.labels.inactive + '</span>';
          }
        },
        {
          // Actions
          targets: 4,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full['actions'];
          }
        }
      ],
      order: [[1, 'asc']],
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
        searchPlaceholder: 'Search Expense Types',
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
              return 'Expense Type Details';
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

  $('#addExpenseTypeBtn').on('click', function () {
    $('#expenseTypeForm')[0].reset();
    $('#expense_type_id').val('');
    $('#offcanvasExpenseTypeLabel').text('Add Expense Type');

    // Reset submit button state
    const submitButton = $('#expenseTypeForm').find('button[type="submit"]');
    submitButton.prop('disabled', false).text('Save');
  });

  $('#expenseTypeForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#expense_type_id').val();
    const isEdit = id !== '';
    const url = isEdit ? pageData.urls.update.replace(':id', id) : pageData.urls.store;
    const method = isEdit ? 'PUT' : 'POST';

    const formData = {
      name: $('#name').val(),
      description: $('#description').val(),
      status: $('#status').val()
    };

    if (!formData.name) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: pageData.labels.validationError,
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.html();
    submitButton.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Saving...');

    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function (response) {
        if (response.status === 'success') {
          // Reset button state
          submitButton.prop('disabled', false).html(originalButtonText);

          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: isEdit ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });

          // Close offcanvas
          var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasExpenseType'));
          if (offcanvas) {
            offcanvas.hide();
          }

          // Reload table
          dtExpenseTypes.draw();
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          submitButton.prop('disabled', false).html(originalButtonText);
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.error;
        if (xhr.status === 422 && xhr.responseJSON.errors) {
          errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          html: errorMessage,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });

        submitButton.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  $(document).on('click', '.edit-expense-type', function () {
    const id = $(this).data('id');

    const rowData = dtExpenseTypes.rows().data().toArray().find(row => row.id === id);

    if (rowData) {
      $('#expense_type_id').val(rowData.id);
      $('#name').val(rowData.name);
      $('#description').val(rowData.description);
      $('#status').val(rowData.status);
      $('#offcanvasExpenseTypeLabel').text('Edit Expense Type');

      var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasExpenseType'));
      offcanvas.show();
    }
  });

  $(document).on('click', '.delete-expense-type', function () {
    const id = $(this).data('id');
    const url = pageData.urls.delete.replace(':id', id);

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yesDelete,
      cancelButtonText: pageData.labels.cancel,
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          method: 'DELETE',
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleted,
                text: pageData.labels.deleteSuccess,
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              });

              dtExpenseTypes.draw();
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response.message || pageData.labels.deleteError,
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
            }
          },
          error: function (xhr) {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: xhr.responseJSON?.message || pageData.labels.deleteError,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });
});
