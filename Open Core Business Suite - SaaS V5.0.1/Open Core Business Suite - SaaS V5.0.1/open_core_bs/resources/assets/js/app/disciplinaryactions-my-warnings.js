$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  const dt = $('.datatables-my-warnings').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.status = $('#filter-status').val();
      }
    },
    columns: [
      { data: 'warning_info', name: 'warning_info', orderable: false },
      { data: 'dates', name: 'dates', orderable: false },
      { data: 'status_badge', name: 'status', searchable: false },
      { data: 'issued_by', name: 'issued_by', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[2, 'desc']],
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    language: {
      search: '',
      searchPlaceholder: 'Search warnings...'
    }
  });

  // Load statistics
  loadStatistics();

  // Filter change
  $('#filter-status').on('change', function () {
    dt.draw();
    loadStatistics();
  });

  // Reset filters
  $('#reset-filters').on('click', function () {
    $('#filter-status').val('');
    dt.draw();
    loadStatistics();
  });

  // Warning actions
  $(document).on('click', '.acknowledge-warning', function () {
    const warningId = $(this).data('id');
    acknowledgeWarning(warningId);
  });

  $(document).on('click', '.appeal-warning', function () {
    const warningId = $(this).data('id');
    openAppealOffcanvas(warningId);
  });

  $(document).on('click', '.view-warning', function () {
    const warningId = $(this).data('id');
    window.location.href = pageData.urls.show.replace(':id', warningId) + '?source=ess';
  });

  $(document).on('click', '.download-letter', function () {
    const warningId = $(this).data('id');
    window.open(pageData.urls.downloadLetter.replace(':id', warningId), '_blank');
  });

  // Handle appeal form submission
  $('#appealForm').on('submit', function (e) {
    e.preventDefault();
    submitAppeal();
  });

  // Handle file selection for preview and validation
  $('#supporting_documents').on('change', function() {
    updateFilePreview();
  });

  // Load statistics
  function loadStatistics() {
    $.ajax({
      url: pageData.urls.stats,
      method: 'GET',
      data: {
        my_warnings: true
      },
      success: function(response) {
        if (response.status === 'success' && response.data) {
          const stats = response.data;
          $('#active-warnings').text(stats.active_warnings || 0);
          $('#acknowledged-warnings').text(stats.acknowledged_warnings || 0);
          $('#appealed-warnings').text(stats.appealed_warnings || 0);
        }
      },
      error: function() {
        // Keep default values on error
        console.log('Failed to load warning statistics');
      }
    });
  }

  // Acknowledge warning
  function acknowledgeWarning(warningId) {
    Swal.fire({
      title: 'Acknowledge Warning',
      text: 'Do you want to add any comments?',
      input: 'textarea',
      inputPlaceholder: 'Optional comments...',
      showCancelButton: true,
      confirmButtonText: 'Acknowledge',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false,
      showLoaderOnConfirm: true,
      preConfirm: (comments) => {
        return $.ajax({
          url: pageData.urls.acknowledge.replace(':id', warningId),
          method: 'POST',
          data: {
            comments: comments
          }
        });
      },
      allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
      if (result.isConfirmed) {
        if (result.value.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: 'Warning acknowledged successfully',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          dt.draw(false);
          loadStatistics(); // Reload statistics after acknowledging
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: result.value.data || 'Failed to acknowledge warning',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      }
    }).catch((error) => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to acknowledge warning',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
    });
  }

  // Open appeal offcanvas
  function openAppealOffcanvas(warningId) {
    $('#appeal_warning_id').val(warningId);
    $('#appeal_reason').val('');
    $('#employee_statement').val('');
    $('#supporting_documents').val('');
    $('#selectedFilesPreview').hide();
    $('#filesList').empty();

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

    // Validate files
    const filesInput = $('#supporting_documents')[0];
    if (filesInput.files.length > 0) {
      const validation = validateFiles(filesInput.files);
      if (!validation.valid) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: validation.message,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        return;
      }
    }

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + pageData.labels.submitting);

    // Create FormData object for file upload
    const formData = new FormData();
    formData.append('warning_id', $('#appeal_warning_id').val());
    formData.append('appeal_reason', appealReason);
    formData.append('employee_statement', $('#employee_statement').val());

    // Add files to FormData
    if (filesInput.files.length > 0) {
      for (let i = 0; i < filesInput.files.length; i++) {
        formData.append('supporting_documents[]', filesInput.files[i]);
      }
    }

    $.ajax({
      url: pageData.urls.appealStore,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
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
            dt.draw(false);
            loadStatistics(); // Reload statistics after appealing
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

  // Validate files
  function validateFiles(files) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const maxFiles = 5;
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const allowedExtensions = ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'];

    if (files.length > maxFiles) {
      return {
        valid: false,
        message: 'Maximum 5 files allowed'
      };
    }

    for (let i = 0; i < files.length; i++) {
      const file = files[i];

      // Check file size
      if (file.size > maxSize) {
        return {
          valid: false,
          message: `File "${file.name}" exceeds 10MB limit`
        };
      }

      // Check file type
      const fileName = file.name.toLowerCase();
      const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
      const hasValidType = allowedTypes.includes(file.type);

      if (!hasValidExtension && !hasValidType) {
        return {
          valid: false,
          message: `File "${file.name}" is not a supported type. Only PDF, JPG, PNG, DOC, DOCX are allowed`
        };
      }
    }

    return { valid: true };
  }

  // Update file preview
  function updateFilePreview() {
    const filesInput = $('#supporting_documents')[0];
    const filesList = $('#filesList');
    const preview = $('#selectedFilesPreview');

    filesList.empty();

    if (filesInput.files.length === 0) {
      preview.hide();
      return;
    }

    // Validate files
    const validation = validateFiles(filesInput.files);
    if (!validation.valid) {
      Swal.fire({
        icon: 'error',
        title: 'Invalid File(s)',
        text: validation.message,
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      filesInput.value = '';
      preview.hide();
      return;
    }

    // Show selected files
    for (let i = 0; i < filesInput.files.length; i++) {
      const file = filesInput.files[i];
      const fileSize = formatFileSize(file.size);
      const fileIcon = getFileIcon(file.name);

      const listItem = `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="${fileIcon} me-2"></i>
            <span>${file.name}</span>
            <small class="text-muted ms-2">(${fileSize})</small>
          </div>
          <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${i}">
            <i class="bx bx-trash"></i>
          </button>
        </li>
      `;
      filesList.append(listItem);
    }

    preview.show();
  }

  // Remove file from selection
  $(document).on('click', '.remove-file', function() {
    const filesInput = $('#supporting_documents')[0];
    const dataTransfer = new DataTransfer();

    // Get all files except the one to remove
    const indexToRemove = parseInt($(this).data('index'));
    for (let i = 0; i < filesInput.files.length; i++) {
      if (i !== indexToRemove) {
        dataTransfer.items.add(filesInput.files[i]);
      }
    }

    filesInput.files = dataTransfer.files;
    updateFilePreview();
  });

  // Format file size
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  // Get file icon based on extension
  function getFileIcon(filename) {
    const ext = filename.toLowerCase().split('.').pop();
    const icons = {
      'pdf': 'bx bxs-file-pdf text-danger',
      'doc': 'bx bxs-file-doc text-primary',
      'docx': 'bx bxs-file-doc text-primary',
      'jpg': 'bx bxs-file-image text-info',
      'jpeg': 'bx bxs-file-image text-info',
      'png': 'bx bxs-file-image text-info'
    };
    return icons[ext] || 'bx bx-file';
  }
});
