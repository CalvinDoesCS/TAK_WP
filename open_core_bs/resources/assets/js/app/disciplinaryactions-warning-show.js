$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Acknowledge warning
  $('#acknowledgeBtn').on('click', function () {
    Swal.fire({
      title: 'Acknowledge Warning',
      text: 'Are you sure you want to acknowledge this warning?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Acknowledge',
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        acknowledgeWarning();
      }
    });
  });

  // Appeal warning - Open offcanvas
  $('#appealBtn').on('click', function () {
    const warningId = $(this).data('warning-id');
    openAppealOffcanvas(warningId);
  });

  // Handle appeal form submission
  $('#appealForm').on('submit', function (e) {
    e.preventDefault();
    submitAppeal();
  });

  // Open appeal offcanvas
  function openAppealOffcanvas(warningId) {
    $('#appeal_warning_id').val(warningId);
    $('#appeal_reason').val('');
    $('#employee_statement').val('');

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('appealOffcanvas'));
    offcanvas.show();
  }

  // Submit appeal
  function submitAppeal() {
    const $btn = $('#submitAppealBtn');
    const originalText = $btn.html();

    // Validate
    const appealReason = $('#appeal_reason').val().trim();
    if (!appealReason) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: 'Please enter a reason for your appeal',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + pageData.labels.submitting);

    $.ajax({
      url: pageData.urls.appealStore,
      method: 'POST',
      data: {
        warning_id: $('#appeal_warning_id').val(),
        appeal_reason: appealReason,
        employee_statement: $('#employee_statement').val()
      },
      success: function (response) {
        if (response.status === 'success') {
          // Close offcanvas
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('appealOffcanvas'));
          offcanvas.hide();

          Swal.fire({
            icon: 'success',
            title: pageData.labels.appealSuccess,
            text: pageData.labels.appealSuccessMessage,
            timer: 3000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || pageData.labels.errorOccurred,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          $btn.prop('disabled', false).html(originalText);
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.errorOccurred;

        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });

        $btn.prop('disabled', false).html(originalText);
      }
    });
  }

  // Acknowledge warning function
  function acknowledgeWarning() {
    const $btn = $('#acknowledgeBtn');
    const originalText = $btn.text();
    $btn.prop('disabled', true).text('Processing...');

    $.ajax({
      url: pageData.urls.acknowledge,
      method: 'POST',
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.data.message,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.data,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          $btn.prop('disabled', false).text(originalText);
        }
      },
      error: function (xhr) {
        let errorMessage = 'An error occurred. Please try again.';

        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });

        $btn.prop('disabled', false).text(originalText);
      }
    });
  }
});
