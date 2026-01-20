$(function () {
    'use strict';

    let quill;
    let form = $('#policyForm');

    // Initialize components
    function initializeComponents() {
        // Initialize Select2
        $('.select2').select2({
            dropdownParent: $('body')
        });

        // Initialize Flatpickr
        $('.flatpickr').flatpickr({
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'F j, Y'
        });

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
      const fullToolbar = [
        [

          {
            size: []
          }
        ],
        ['bold', 'italic', 'underline', 'strike'],
        [
          {
            color: []
          },
          {
            background: []
          }
        ],
        [
          {
            script: 'super'
          },
          {
            script: 'sub'
          }
        ],
        [
          {
            header: '1'
          },
          {
            header: '2'
          },
          'blockquote',
          'code-block'
        ],
        [
          {
            list: 'ordered'
          },
          {
            list: 'bullet'
          },
          {
            indent: '-1'
          },
          {
            indent: '+1'
          }
        ],
        [{ direction: 'rtl' }],
        ['link'],
        ['clean']
      ];
      if (typeof Quill !== 'undefined') {

        quill = new Quill('#content-editor', {
          theme: 'snow',
          formula: true,
          modules: {
            toolbar: fullToolbar
          },
        });

        // If editing existing content, import HTML properly
        if (pageData.mode === 'edit' && pageData.policyContent) {
          quill.clipboard.dangerouslyPasteHTML(pageData.policyContent);
        }

        // Keep hidden textarea in sync
        quill.on('text-change', function () {
          document.getElementById('content').value = quill.root.innerHTML;
        });
      }

        // Bind events
        bindEvents();
    }

    // Bind events
    function bindEvents() {
        // Toggle acknowledgment deadline field
        $('#requires_acknowledgment').on('change', function() {
            if ($(this).is(':checked')) {
                $('#acknowledgment_deadline_container').show();
            } else {
                $('#acknowledgment_deadline_container').hide();
                $('#acknowledgment_deadline_days').val('');
            }
        });

        // Form submission
        form.on('submit', function(e) {
            e.preventDefault();
            handleFormSubmission();
        });
    }

    // Handle form submission
    function handleFormSubmission() {
        const formData = new FormData(form[0]);

        // Fix checkbox values
        const checkboxes = ['is_mandatory', 'requires_acknowledgment', 'auto_assign_new_employees'];
        checkboxes.forEach(function(checkbox) {
            const isChecked = $('#' + checkbox).is(':checked');
            formData.delete(checkbox);
            formData.append(checkbox, isChecked ? '1' : '0');
        });

        // Update content from Quill
        if (quill) {
            formData.set('content', quill.root.innerHTML);
        }

        // Disable submit button
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text(pageData.labels.processing);

        $.ajax({
            url: pageData.urls.submit,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            if (response.status === 'success') {
                const message = pageData.mode === 'edit' ?
                    pageData.labels.updateSuccess :
                    pageData.labels.createSuccess;

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = pageData.urls.index;
                });
            } else {
                showNotification('error', response.message || pageData.labels.error);
                if (response.errors) {
                    handleValidationErrors(response.errors);
                }
            }
        })
        .fail(function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.data) {
                handleValidationErrors(xhr.responseJSON.data);
                showNotification('error', pageData.labels.validationError);
            } else {
                showNotification('error', pageData.labels.error);
            }
        })
        .always(function() {
            submitBtn.prop('disabled', false).text(originalText);
        });
    }

    // Handle validation errors
    function handleValidationErrors(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Display new errors
        Object.keys(errors).forEach(function(field) {
            const input = $(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(errors[field][0]);

            // Special handling for select2
            if (input.hasClass('select2')) {
                input.next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });
    }

    // Show notification
    function showNotification(type, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success!' : 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
    }

    // Initialize when document is ready
    initializeComponents();
});
