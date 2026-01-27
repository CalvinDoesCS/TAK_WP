$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // No Quill editors needed - using plain textareas

  // Initialize Flatpickr for hearing date
  if ($('#hearing_date').length) {
    flatpickr('#hearing_date', {
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      minDate: 'today',
      time_24hr: false
    });
  }

  // Withdraw appeal
  $('#withdrawAppeal').on('click', function () {
    Swal.fire({
      title: 'Withdraw Appeal',
      text: pageData.labels.confirmWithdraw,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, Withdraw'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.withdraw,
          method: 'POST',
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.labels.error;
            
            if (xhr.responseJSON && xhr.responseJSON.data) {
              errorMessage = xhr.responseJSON.data;
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: errorMessage
            });
          }
        });
      }
    });
  });

  // Edit appeal (redirect to edit page - not implemented in this version)
  $('#editAppeal').on('click', function () {
    // For now, just show a message
    Swal.fire({
      icon: 'info',
      title: 'Edit Appeal',
      text: 'Appeal editing functionality will be available soon.'
    });
  });

  // Review appeal - show offcanvas
  $('#reviewAppeal').on('click', function () {
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('reviewOffcanvas'));
    offcanvas.show();
  });

  // Schedule hearing - show offcanvas
  $('#scheduleHearing').on('click', function () {
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('scheduleHearingOffcanvas'));
    offcanvas.show();
  });

  // Review form submission
  $('#reviewForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Validate required fields
    if (!$('#review_status').val()) {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please select a decision'
      });
      return;
    }

    if (!$('#review_comments').val().trim()) {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please provide review comments'
      });
      return;
    }

    if (!$('#review_decision').val().trim()) {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please provide a review decision'
      });
      return;
    }
    
    // Submit button handling
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).text('Processing...');
    
    $.ajax({
      url: pageData.urls.review,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.data.message,
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            window.location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.data
          });
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.error;
        
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          errorMessage = Object.values(errors).flat().join('\n');
        } else if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage
        });
      },
      complete: function () {
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });

  // Schedule hearing form submission
  $('#scheduleHearingForm').on('submit', function (e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Submit button handling
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).text('Scheduling...');
    
    $.ajax({
      url: pageData.urls.scheduleHearing,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.data.message,
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('scheduleHearingOffcanvas'));
            offcanvas.hide();
            window.location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.data
          });
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.error;
        
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          errorMessage = Object.values(errors).flat().join('\n');
        } else if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage
        });
      },
      complete: function () {
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });

  // Reset offcanvas on close
  $('#reviewOffcanvas').on('hidden.bs.offcanvas', function () {
    $('#reviewForm')[0].reset();
  });

  $('#scheduleHearingOffcanvas').on('hidden.bs.offcanvas', function () {
    $('#scheduleHearingForm')[0].reset();
  });
});