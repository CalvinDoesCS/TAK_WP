/**
 * My Attendance Regularization
 */

'use strict';

$(function() {
  // Get CSRF token
  const csrfToken = $('meta[name="csrf-token"]').attr('content');

  // Initialize DataTable
  const table = $('#myRegularizationTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: pageData.urls.datatable,
    columns: [
      { data: 'date', name: 'date' },
      { data: 'type', name: 'type' },
      { data: 'requested_times', name: 'requested_times' },
      { data: 'reason', name: 'reason' },
      { data: 'status', name: 'status' },
      { data: 'approved_by', name: 'approved_by' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[0, 'desc']],
    dom: '<"card-header d-flex flex-wrap"<"d-flex justify-content-start justify-content-md-end align-items-baseline gap-2"<"dt-action-buttons"B>l>>t<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    displayLength: 10,
    lengthMenu: [10, 25, 50, 100],
    buttons: [],
    searching: false
  });

  // Handle form submission (create or update)
  $('#regularizationForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type="submit"]');
    const updateId = $(this).attr('data-update-id');
    
    // Disable submit button
    submitBtn.prop('disabled', true);
    
    let url = pageData.urls.store;
    
    // If updating, change URL and add PUT method
    if (updateId) {
      url = pageData.urls.update.replace(':id', updateId);
      formData.append('_method', 'PUT');
    }
    
    $.ajax({
      url: url,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data.message
          });
          
          // Reset form and remove update ID
          $('#regularizationForm')[0].reset();
          $('#regularizationForm').removeAttr('data-update-id');
          
          // Update offcanvas title back to "New"
          $('#addRegularizationOffcanvas .offcanvas-title').text('New Regularization Request');
          
          const offcanvasEl = document.getElementById('addRegularizationOffcanvas');
          const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
          if (offcanvas) {
            offcanvas.hide();
          }
          
          // Reload table
          table.ajax.reload();
        }
      },
      error: function(xhr) {
        let message = pageData.labels.errorOccurred;
        if (xhr.responseJSON) {
          if (xhr.responseJSON.data) {
            message = xhr.responseJSON.data;
          } else if (xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            // Handle validation errors
            const errors = xhr.responseJSON.errors;
            message = Object.values(errors).flat().join('\n');
          }
        }
        
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: message
        });
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false);
      }
    });
  });

  // View regularization details
  window.viewMyRegularization = function(id) {
    $.ajax({
      url: pageData.urls.show.replace(':id', id),
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      success: function(response) {
        if (response.status === 'success' && response.data) {
          // Pass both regularization and approved_by data
          showRegularizationDetails(response.data.regularization, response.data.approved_by);
        }
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.failedToLoadDetails
        });
      }
    });
  };

  // Edit regularization (only for pending)
  window.editMyRegularization = function(id) {
    $.ajax({
      url: pageData.urls.edit.replace(':id', id),
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      success: function(response) {
        if (response.status === 'success' && response.data) {
          console.log('Edit data received:', response.data); // Debug log
          // Populate form with data
          populateEditForm(response.data);
          
          // Open offcanvas
          const offcanvasEl = document.getElementById('addRegularizationOffcanvas');
          const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
          offcanvas.show();
        }
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.failedToLoadData
        });
      }
    });
  };

  // Delete regularization (only for pending)
  window.deleteMyRegularization = function(id) {
    Swal.fire({
      title: pageData.labels.areYouSure,
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
          url: pageData.urls.delete.replace(':id', id),
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken
          },
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleted,
                text: response.data.message,
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              });
              table.ajax.reload();
            }
          },
          error: function(xhr) {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: pageData.labels.failedToDelete,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  };

  // Helper function to show regularization details
  function showRegularizationDetails(regularization, approvedBy) {
    // Format the details
    let statusBadge = '';
    switch(regularization.status) {
      case 'pending':
        statusBadge = '<span class="badge bg-label-warning">Pending</span>';
        break;
      case 'approved':
        statusBadge = '<span class="badge bg-label-success">Approved</span>';
        break;
      case 'rejected':
        statusBadge = '<span class="badge bg-label-danger">Rejected</span>';
        break;
      default:
        statusBadge = '<span class="badge bg-label-secondary">' + regularization.status + '</span>';
    }

    // Format type display
    let typeDisplay = regularization.type || '-';
    if (typeDisplay === 'missing_checkin') typeDisplay = 'Missing Check-in';
    else if (typeDisplay === 'missing_checkout') typeDisplay = 'Missing Check-out';
    else if (typeDisplay === 'wrong_time') typeDisplay = 'Wrong Time';
    else if (typeDisplay === 'forgot_punch') typeDisplay = 'Forgot to Punch';
    else if (typeDisplay === 'other') typeDisplay = 'Other';

    // Format date properly (remove timezone and time)
    let formattedDate = regularization.date || '-';
    if (formattedDate !== '-') {
      if (formattedDate.includes('T')) {
        formattedDate = formattedDate.split('T')[0];
      } else if (formattedDate.includes(' ')) {
        formattedDate = formattedDate.split(' ')[0];
      }
      // Convert to readable format
      const dateParts = formattedDate.split('-');
      if (dateParts.length === 3) {
        const dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
        formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
      }
    }

    let detailsHtml = `
      <div class="mb-3">
        <label class="text-muted small">Date</label>
        <p class="mb-2">${formattedDate}</p>
      </div>

      <div class="mb-3">
        <label class="text-muted small">Type</label>
        <p class="mb-2">${typeDisplay}</p>
      </div>

      <div class="mb-3">
        <label class="text-muted small">Requested Check-in Time</label>
        <p class="mb-2">${regularization.requested_check_in_time || '-'}</p>
      </div>

      <div class="mb-3">
        <label class="text-muted small">Requested Check-out Time</label>
        <p class="mb-2">${regularization.requested_check_out_time || '-'}</p>
      </div>

      <div class="mb-3">
        <label class="text-muted small">Reason</label>
        <p class="mb-2">${regularization.reason || '-'}</p>
      </div>

      <div class="mb-3">
        <label class="text-muted small">Status</label>
        <p class="mb-2">${statusBadge}</p>
      </div>
    `;

    // Add attachments if available
    if (regularization.attachments && regularization.attachments.length > 0) {
      detailsHtml += `
      <div class="mb-3">
        <label class="text-muted small">Attachments</label>
        <div class="mt-2">`;

      regularization.attachments.forEach(function(attachment) {
        const url = attachment.url || `/storage/${attachment.path}`;
        const name = attachment.name || 'Download';
        detailsHtml += `
          <a href="${url}" target="_blank" class="badge bg-label-primary me-1 mb-1">
            <i class='bx bx-link-external me-1'></i>${name}
          </a>`;
      });

      detailsHtml += `
        </div>
      </div>`;
    }

    // Add approved by information with avatar
    if (approvedBy && (regularization.status === 'approved' || regularization.status === 'rejected')) {
      const fullName = `${approvedBy.first_name || ''} ${approvedBy.last_name || ''}`.trim();
      const approvedDate = regularization.approved_at ? new Date(regularization.approved_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) : '';

      // Generate initials from name
      const getInitials = (firstName, lastName) => {
        const first = (firstName || '').trim();
        const last = (lastName || '').trim();
        return ((first.charAt(0) || '') + (last.charAt(0) || '')).toUpperCase() || 'U';
      };
      const initials = getInitials(approvedBy.first_name, approvedBy.last_name);

      // Build avatar HTML - use image if available, otherwise show initials
      let avatarHtml = '';
      if (approvedBy.profile_picture || approvedBy.avatar_url) {
        const avatarUrl = approvedBy.profile_picture || approvedBy.avatar_url;
        avatarHtml = `<img src="${avatarUrl}" alt="${fullName}" class="rounded-circle">`;
      } else {
        avatarHtml = `<span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>`;
      }

      detailsHtml += `
      <div class="mb-3">
        <label class="text-muted small">Approved/Rejected By</label>
        <div class="d-flex align-items-center mt-2">
          <div class="avatar avatar-sm me-2">
            ${avatarHtml}
          </div>
          <div>
            <div class="fw-medium">${fullName}</div>
            ${approvedDate ? `<small class="text-muted">${approvedDate}</small>` : ''}
          </div>
        </div>
      </div>
      `;
    } else {
      detailsHtml += `
      <div class="mb-3">
        <label class="text-muted small">Approved/Rejected By</label>
        <p class="mb-2">-</p>
      </div>
      `;
    }

    // Add manager comments if available
    if (regularization.manager_comments) {
      detailsHtml += `
      <div class="mb-3">
        <label class="text-muted small">Manager Comments</label>
        <p class="mb-2">${regularization.manager_comments}</p>
      </div>`;
    }

    // Populate the offcanvas content
    $('#regularizationDetailsContent').html(detailsHtml);

    // Show the offcanvas
    const offcanvasEl = document.getElementById('viewRegularizationOffcanvas');
    const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
    offcanvas.show();
  }

  // Helper function to populate edit form
  function populateEditForm(data) {
    // Format date to YYYY-MM-DD from ISO 8601 format
    let formattedDate = data.date;
    if (data.date) {
      // Handle ISO 8601 format (e.g., "2025-09-01T18:30:00.000000Z")
      if (data.date.includes('T')) {
        formattedDate = data.date.split('T')[0];
      }
      // Handle datetime format (e.g., "2025-09-01 18:30:00")
      else if (data.date.includes(' ')) {
        formattedDate = data.date.split(' ')[0];
      }
    }

    // Format time to HH:MM if it includes seconds
    let formattedCheckIn = data.requested_check_in_time;
    let formattedCheckOut = data.requested_check_out_time;

    if (formattedCheckIn && formattedCheckIn.length > 5) {
      // Remove seconds if present (HH:MM:SS -> HH:MM)
      formattedCheckIn = formattedCheckIn.substring(0, 5);
    }

    if (formattedCheckOut && formattedCheckOut.length > 5) {
      // Remove seconds if present (HH:MM:SS -> HH:MM)
      formattedCheckOut = formattedCheckOut.substring(0, 5);
    }

    // Populate form fields
    $('#regularizationForm').find('[name="date"]').val(formattedDate);
    $('#regularizationForm').find('[name="type"]').val(data.type);
    $('#regularizationForm').find('[name="requested_check_in_time"]').val(formattedCheckIn);
    $('#regularizationForm').find('[name="requested_check_out_time"]').val(formattedCheckOut);
    $('#regularizationForm').find('[name="reason"]').val(data.reason);

    // Handle existing attachments
    if (data.attachments && data.attachments.length > 0) {
      let attachmentsHtml = '';
      data.attachments.forEach(function(attachment) {
        // Handle both object format {name, path, url} and string format
        let url, fileName;

        if (typeof attachment === 'object') {
          url = attachment.url || `/storage/${attachment.path}`;
          fileName = attachment.name || attachment.path.split('/').pop();
        } else {
          // Legacy string format
          url = attachment;
          fileName = attachment.split('/').pop();
        }

        const fileExt = fileName.split('.').pop().toLowerCase();
        let icon = 'bx-file';

        if (fileExt === 'pdf') {
          icon = 'bx-file-pdf';
        } else if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
          icon = 'bx-image';
        }

        attachmentsHtml += `
          <div class="d-flex align-items-center mb-1">
            <i class="bx ${icon} me-2"></i>
            <a href="${url}" target="_blank" class="text-truncate" style="max-width: 200px;">
              ${fileName}
            </a>
          </div>
        `;
      });

      $('#attachmentsList').html(attachmentsHtml);
      $('#existingAttachments').show();
      $('#uploadNote').show();
    } else {
      $('#existingAttachments').hide();
      $('#uploadNote').hide();
    }

    // Update form for editing mode
    $('#regularizationForm').attr('data-update-id', data.id);

    // Update offcanvas title
    $('#regularizationOffcanvasTitle').text('Edit Regularization Request');

    console.log('Form populated with:', {
      date: formattedDate,
      type: data.type,
      checkIn: formattedCheckIn,
      checkOut: formattedCheckOut,
      attachments: data.attachments
    });
  }

  // Reset form when offcanvas is closed
  document.getElementById('addRegularizationOffcanvas').addEventListener('hidden.bs.offcanvas', function () {
    // Reset form
    $('#regularizationForm')[0].reset();
    $('#regularizationForm').removeAttr('data-update-id');

    // Reset title
    $('#regularizationOffcanvasTitle').text('New Regularization Request');

    // Hide existing attachments section
    $('#existingAttachments').hide();
    $('#uploadNote').hide();
    $('#attachmentsList').html('');
  });
});