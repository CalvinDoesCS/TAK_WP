$(function () {
  var dtTable = $('.datatables-myExpenses');

  // CSRF Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Date filter
  $('#dateFilter').on('change', function () {
    if (typeof dtMyExpenses !== 'undefined') {
      dtMyExpenses.draw();
    }
  });

  // Expense Type filter
  $('#expenseTypeFilter').select2();
  $('#expenseTypeFilter').on('change', function () {
    if (typeof dtMyExpenses !== 'undefined') {
      dtMyExpenses.draw();
    }
  });

  // Status filter
  $('#statusFilter').select2();
  $('#statusFilter').on('change', function () {
    if (typeof dtMyExpenses !== 'undefined') {
      dtMyExpenses.draw();
    }
  });

  if (dtTable.length) {
    var dtMyExpenses = dtTable.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatableAjax,
        data: function (d) {
          d.dateFilter = $('#dateFilter').val();
          d.statusFilter = $('#statusFilter').val();
          d.expenseTypeFilter = $('#expenseTypeFilter').val();
        },
        error: function (xhr, error, code) {
          console.log('Error: ' + error);
          console.log('Code: ' + code);
          console.log('Response: ' + xhr.responseText);
        }
      },
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
          // ID
          targets: 1,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + full['id'] + '</span>';
          }
        },
        {
          // Expense Type
          targets: 2,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['expense_type'];
          }
        },
        {
          // Expense Date
          targets: 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['for_date'];
          }
        },
        {
          // Amount
          targets: 4,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $amount = full['amount'];
            var $status = full['status'];
            var $approvedAmount = full['approved_amount'];

            if ($status == 'approved' && $approvedAmount) {
              return '<span class="fw-medium">' + pageData.currencySymbol + $amount + '</span>' +
                '<br>' +
                '<span class="text-success small"><i class="bx bx-check-circle me-1"></i>Approved: ' + pageData.currencySymbol + $approvedAmount + '</span>';
            } else {
              return '<span class="fw-medium">' + pageData.currencySymbol + $amount + '</span>';
            }
          }
        },
        {
          // Status
          targets: 5,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $status = full['status'];
            if ($status == 'approved') {
              return '<span class="badge bg-label-success">Approved</span>';
            } else if ($status == 'rejected') {
              return '<span class="badge bg-label-danger">Rejected</span>';
            } else if ($status == 'cancelled') {
              return '<span class="badge bg-label-secondary">Cancelled</span>';
            } else {
              return '<span class="badge bg-label-warning">Pending</span>';
            }
          }
        },
        {
          // Receipt
          targets: 6,
          className: 'text-start',
          render: function (data, type, full, meta) {
            if (!full['document_url']) {
              return '<span class="text-muted">-</span>';
            }

            // Check if document is PDF
            const isPdf = full['document_url'].toLowerCase().endsWith('.pdf');

            if (isPdf) {
              // Display PDF icon with download link
              return '<a href="' + full['document_url'] + '" target="_blank" class="btn btn-sm btn-icon btn-label-primary">' +
                '<i class="bx bxs-file-pdf"></i>' +
                '</a>';
            } else {
              // Display image thumbnail
              return '<img src="' + full['document_url'] + '" alt="Receipt" class="rounded img-thumbnail" height="40" />';
            }
          }
        },
        {
          // Actions
          targets: 7,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-2">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill view-expense" data-id="' + full['id'] + '" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExpenseDetails" title="View Details">' +
              '<i class="bx bx-show"></i>' +
              '</button>' +
              '</div>'
            );
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
      lengthMenu: [7, 10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search My Expenses',
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
              var data = row.data();
              return 'Expense Details';
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

  // View expense details
  $(document).on('click', '.view-expense', function () {
    var id = $(this).data('id');
    var url = pageData.urls.showExpense.replace(':id', id);

    $.get(url, function (response) {
      if (response.status === 'success') {
        var data = response.data;

        $('#detail_expense_type').text(data.expenseType);
        $('#detail_expense_date').text(data.forDate);
        $('#detail_amount').text(pageData.currencySymbol + data.amount);
        $('#detail_remarks').text(data.remarks || '-');
        $('#detail_created_at').text(data.createdAt);

        // Status badge
        var statusBadge = '';
        if (data.status === 'approved') {
          statusBadge = '<span class="badge bg-label-success">Approved</span>';
          $('#approved_amount_section').show();
          $('#detail_approved_amount').text(pageData.currencySymbol + data.approvedAmount);
        } else if (data.status === 'rejected') {
          statusBadge = '<span class="badge bg-label-danger">Rejected</span>';
          $('#approved_amount_section').hide();
        } else if (data.status === 'cancelled') {
          statusBadge = '<span class="badge bg-label-secondary">Cancelled</span>';
          $('#approved_amount_section').hide();
        } else {
          statusBadge = '<span class="badge bg-label-warning">Pending</span>';
          $('#approved_amount_section').hide();
        }
        $('#detail_status').html(statusBadge);

        // Admin remarks
        if (data.adminRemarks) {
          $('#admin_remarks_section').show();
          $('#detail_admin_remarks').text(data.adminRemarks);
        } else {
          $('#admin_remarks_section').hide();
        }

        // Receipt
        if (data.document) {
          const isPdf = data.document.toLowerCase().endsWith('.pdf');

          if (isPdf) {
            // Show PDF icon with link
            $('#receipt_section').html(
              '<label class="form-label text-muted">Receipt/Proof</label>' +
              '<div class="mt-2">' +
              '<a href="' + data.document + '" target="_blank" class="btn btn-primary btn-sm">' +
              '<i class="bx bxs-file-pdf me-1"></i>View PDF' +
              '</a>' +
              '</div>'
            );
          } else {
            // Show image
            $('#receipt_section').html(
              '<label class="form-label text-muted">Receipt/Proof</label>' +
              '<div class="mt-2">' +
              '<img src="' + data.document + '" alt="Receipt" class="img-fluid rounded border" style="max-width: 100%;">' +
              '</div>'
            );
          }
          $('#receipt_section').show();
        } else {
          $('#receipt_section').hide();
        }

        // Show delete button only for pending expenses
        if (data.status === 'pending') {
          $('#deleteExpenseBtn').show().data('id', id);
        } else {
          $('#deleteExpenseBtn').hide();
        }
      }
    });
  });

  // Delete expense
  $(document).on('click', '#deleteExpenseBtn', function () {
    var id = $(this).data('id');
    var url = pageData.urls.showExpense.replace(':id', id);

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
                }
              });

              // Close offcanvas
              var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasExpenseDetails'));
              if (offcanvas) {
                offcanvas.hide();
              }

              // Reload table
              dtMyExpenses.draw();
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response.message || pageData.labels.deleteError,
                customClass: {
                  confirmButton: 'btn btn-primary'
                }
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
              }
            });
          }
        });
      }
    });
  });
});
