$(function () {
  // CSRF Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2
  $('#expense_type_id').select2({
    placeholder: $('#expense_type_id').data('placeholder') || 'Select Expense Type',
    allowClear: true
  });

  // Initialize Flatpickr for expense date
  flatpickr('#for_date', {
    dateFormat: 'Y-m-d',
    maxDate: 'today',
    defaultDate: 'today'
  });

  // Preview image when file is selected
  $('#document').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      // Check file size (2MB = 2097152 bytes)
      if (file.size > 2097152) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.fileSizeError,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
        $(this).val('');
        $('#document_preview').hide();
        return;
      }

      // Show preview for images
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          $('#preview_image').attr('src', e.target.result);
          $('#document_preview').show();
        }
        reader.readAsDataURL(file);
      } else {
        // For PDF, show file name
        $('#document_preview').html(`
          <div class="alert alert-info mb-0">
            <i class="bx bx-file-pdf me-2"></i>${file.name}
          </div>
        `).show();
      }
    } else {
      $('#document_preview').hide();
    }
  });

  // Form submission with AJAX
  $('#expenseForm').on('submit', function(e) {
    e.preventDefault();

    const expenseType = $('#expense_type_id').val();
    const expenseDate = $('#for_date').val();
    const amount = parseFloat($('#amount').val()) || 0;

    // Client-side validation
    if (!expenseType) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: pageData.labels.selectExpenseType,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    if (!expenseDate) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: pageData.labels.selectExpenseDate,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    if (amount <= 0) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: pageData.labels.amountGreaterThanZero,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    // Prepare form data
    const formData = new FormData(this);
    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.html();

    // Disable submit button and show loading
    submitButton.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>' + pageData.labels.submitting);

    // Submit form via AJAX
    $.ajax({
      url: $(this).attr('action'),
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.message || pageData.labels.expenseSubmitted,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          }).then(() => {
            // Redirect to expenses list
            window.location.href = pageData.urls.expensesList;
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.message || pageData.labels.somethingWentWrong,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
          submitButton.prop('disabled', false).html(originalButtonText);
        }
      },
      error: function(xhr) {
        let errorMessage = pageData.labels.somethingWentWrong;

        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          if (errors) {
            errorMessage = Object.values(errors).flat().join('<br>');
          }
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          html: errorMessage,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });

        submitButton.prop('disabled', false).html(originalButtonText);
      }
    });
  });
});
