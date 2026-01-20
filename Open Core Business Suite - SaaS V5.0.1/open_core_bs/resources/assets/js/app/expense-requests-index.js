$(function () {
  var dtTable = $('.datatables-expenseRequests');

  // CSRF Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Employee Filter with Select2 AJAX (only for admin view)
  if ($('#employeeFilter').length && !pageData.isSelfService) {
    $('#employeeFilter').select2({
      ajax: {
        url: pageData.urls.employeeSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term,
            page: params.page || 1
          };
        },
        processResults: function (response) {
          return {
            results: response.data || [],
            pagination: {
              more: response.pagination && response.pagination.more
            }
          };
        },
        cache: true
      },
      placeholder: 'Select an employee',
      allowClear: true,
      minimumInputLength: 0
    });

    $('#employeeFilter').on('change', function () {
      if (typeof dtExpenseRequests !== 'undefined') {
        dtExpenseRequests.draw();
      }
    });
  }

  $('#dateFilter').on('change', function () {
    dtExpenseRequests.draw();
  })

  $('#expenseTypeFilter').select2();

  $('#expenseTypeFilter').on('change', function () {
    dtExpenseRequests.draw();
  });

  $('#statusFilter').select2();

  $('#statusFilter').on('change', function () {
    dtExpenseRequests.draw();
  });

  if (dtTable.length) {
    var employeeView = baseUrl + 'employees/view/';

    var dtExpenseRequests = dtTable.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatableAjax,
        data: function (d) {
          d.dateFilter = $('#dateFilter').val();
          d.statusFilter = $('#statusFilter').val();
          d.employeeFilter = $('#employeeFilter').val();
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
          // id
          targets: 1,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $id = full['id'];
            return '<span class="id">' + $id + '</span>';
          }
        },
        // User column (conditionally included based on isSelfService flag)
        ...(pageData.isSelfService ? [] : [{
          // Name with avatar
          targets: 2,
          className: 'text-start',
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['user_name'],
              code = full['user_code'],
              initials = full['user_initial'],
              profileOutput,
              rowOutput;

            if (full['user_profile_image']) {
              profileOutput =
                '<img src="' + full['user_profile_image'] + '" alt="Avatar" class="avatar rounded-circle " />';
            } else {
              initials = full['user_initial'];
              profileOutput = '<span class="avatar-initial rounded-circle bg-label-info">' + initials + '</span>';
            }

            // Creates full output for row
            rowOutput =
              '<div class="d-flex justify-content-start align-items-center user-name">' +
              '<div class="avatar-wrapper">' +
              '<div class="avatar avatar-sm me-4">' +
              profileOutput +
              '</div>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<a href="' +
              employeeView +
              full['user_id'] +
              '" class="text-heading text-truncate"><span class="fw-medium">' +
              $name +
              '</span></a>' +
              '<small>' +
              code +
              '</small>' +
              '</div>' +
              '</div>';

            return rowOutput;
          }
        }]),

        {
          // Expense type
          targets: pageData.isSelfService ? 2 : 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['expense_type'];
          }
        },

        {
          // Expense Date
          targets: pageData.isSelfService ? 3 : 4,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['for_date'];
          }
        },
        {
          // Amount
          targets: pageData.isSelfService ? 4 : 5,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $amount = full['amount'];
            var $status = full['status'];
            var $approvedAmount = full['approved_amount'];

            if ($status == 'approved' && $approvedAmount !== null && $approvedAmount !== undefined) {
              return '<span class="">' + pageData.currencySymbol + $amount + '</span>' +
                '<br>' +
                '<span class="text-success">Approved: ' + pageData.currencySymbol + $approvedAmount + '</span>';
            } else {
              return '<span class="">' + pageData.currencySymbol + $amount + '</span>';
            }

          }
        },

        {
          //status
          targets: pageData.isSelfService ? 5 : 6,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $status = full['status'];
            if ($status == 'approved') {
              return '<span class="badge bg-label-success">Approved</span>';
            } else if ($status == 'rejected') {
              return '<span class="badge bg-label-danger">Rejected</span>';
            } else if ($status == 'cancelled') {
              return '<span class="badge bg-label-danger">Cancelled</span>';
            } else {
              return '<span class="badge bg-label-warning">Pending</span>';
            }
          }
        },
        {
          //Image
          targets: pageData.isSelfService ? 6 : 7,
          className: 'text-start',
          render: function (data, type, full, meta) {
            if (!full['document_url']) {
              return 'N/A';
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
              return '<img src="' + full['document_url'] + '" alt="Proof" class="img-thumbnail" height="50" />';
            }
          }
        },
        {
          // Actions
          targets: pageData.isSelfService ? 7 : 8,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-left gap-50">' +
              `<button class="btn btn-sm btn-icon expense-request-details" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExpenseRequestDetails"><i class="bx bx-show"></i></button>` +
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
      lengthMenu: [7, 10, 20, 50, 70, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Expense Requests',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      // For responsive popup
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
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
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

  $(document).on('click', '.expense-request-details', function () {
    var id = $(this).data('id');
    //get data
    $.get(`${baseUrl}expenseRequests/getByIdAjax/${id}`, function (response) {
      if (response.status === 'success') {
        var data = response.data;

        var statusQS = $('#status');
        var statusDiv = $('#statusDiv');

        $('#id').val(data.id);
        $('#userName').text(data.userName);
        $('#userCode').text(data.userCode);
        $('#expenseType').text(data.expenseType);
        $('#forDate').text(data.forDate);
        $('#amount').text(data.amount);
        $('#documentUrl').attr('src', data.documentUrl);
        statusQS.text(data.status);
        $('#createdAt').text(data.createdAt);
        $('#userNotes').text(data.userNotes || 'N/A');

        $('#expenseRequestForm').hide();
        $('#adminRemarks').val('');
        $('#approvedAmount').val('');

        if (data.status === 'approved' ) {
          statusDiv.html('<span class="badge bg-label-success">Approved</span>');
          $('#statusDDDiv').hide();
          $('#approvedAmountHide').show();
          $('#approvedAmountDisplay').text(data.approvedAmount);
          statusQS.empty();
          $('#actionButton').hide();
          $('#expenseRequestForm').hide();
        } else if (data.status === 'rejected') {
          statusDiv.html('<span class="badge bg-label-danger">Rejected</span>');
          $('#statusDDDiv').hide();
          $('#approvedAmountHide').hide();
          statusQS.empty();
          $('#actionButton').hide();
          $('#expenseRequestForm').hide();
        } else if (data.status === 'cancelled') {
          statusDiv.html('<span class="badge bg-label-danger">Cancelled</span>');
          $('#statusDDDiv').hide();
          $('#approvedAmountHide').hide();
          statusQS.empty();
          $('#actionButton').hide();
          $('#expenseRequestForm').hide();
        } else {
          statusDiv.html('<span class="badge bg-label-warning">Pending</span>');
          $('#approvedAmountHide').hide();

          statusQS.empty();
          $('#statusDDDiv').show();
          statusQS.append(`<option value="approved">Approve</option>`);
          statusQS.append(`<option value="rejected">Reject</option>`);

          $('#actionButton').text('Submit');
          $('#actionButton').show();
          $('#expenseRequestForm').show();
        }

        if (data.document !== null) {
          const isPdf = data.document.toLowerCase().endsWith('.pdf');

          if (isPdf) {
            // Show PDF icon with link
            $('#documentHide').html(
              '<div class="col-4 fw-bold">Document:</div>' +
              '<div class="col-8">' +
              '<a href="' + data.document + '" target="_blank" class="btn btn-primary btn-sm">' +
              '<i class="bx bxs-file-pdf me-1"></i>View PDF' +
              '</a>' +
              '</div>'
            );
          } else {
            // Show image
            $('#documentHide').html(
              '<div class="col-4 fw-bold">Image:</div>' +
              '<div class="col-8">' +
              '<img id="document" class="img-fluid" src="' + data.document + '" width="50" height="50">' +
              '</div>'
            );
          }
          $('#documentHide').show();
        } else {
          $('#documentHide').hide();
        }

        statusQS.on('change', function () {
          var status = $(this).val();
          if (status === 'rejected') {
            $('#approvedAmountDiv').hide();
          } else if (status === 'approved') {
            $('#approvedAmountDiv').show();
          }
        });
      }
    });
  });

  // Handle expense request form submission via AJAX
  $('#expenseRequestForm').on('submit', function (e) {
    e.preventDefault();

    const formData = {
      id: $('#id').val(),
      status: $('#status').val(),
      approvedAmount: $('#approvedAmount').val(),
      adminRemarks: $('#adminRemarks').val()
    };

    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.html();
    submitButton.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Processing...');

    $.ajax({
      url: baseUrl + 'expenseRequests/actionAjax',
      method: 'POST',
      data: formData,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });

          // Close offcanvas
          var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasExpenseRequestDetails'));
          if (offcanvas) {
            offcanvas.hide();
          }

          // Reload DataTable
          if (typeof dtExpenseRequests !== 'undefined') {
            dtExpenseRequests.draw();
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Something went wrong. Please try again.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          submitButton.prop('disabled', false).html(originalButtonText);
        }
      },
      error: function (xhr) {
        let errorMessage = 'Something went wrong. Please try again.';
        if (xhr.status === 422 && xhr.responseJSON.errors) {
          errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
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
});

