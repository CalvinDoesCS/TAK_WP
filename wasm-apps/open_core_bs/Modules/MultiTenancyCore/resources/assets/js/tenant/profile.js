$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Handle profile form submission
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $saveBtn = $('#saveBtn');
        const originalBtnText = $saveBtn.html();

        // Disable button and show loading
        $saveBtn.prop('disabled', true).html(`<i class="bx bx-loader bx-spin me-2"></i>${pageData.translations.saving}`);

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        $.ajax({
            url: pageData.updateUrl,
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: pageData.translations.success,
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.data.errors;

                    $.each(errors, function(field, messages) {
                        const $field = $(`#${field}`);
                        $field.addClass('is-invalid');
                        $field.after(`<div class="invalid-feedback">${messages[0]}</div>`);
                    });

                    Swal.fire({
                        icon: 'error',
                        title: pageData.translations.validationError,
                        text: pageData.translations.validationMessage,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                } else {
                    // Other errors
                    Swal.fire({
                        icon: 'error',
                        title: pageData.translations.error,
                        text: xhr.responseJSON?.data || pageData.translations.errorOccurred,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            complete: function() {
                // Re-enable button
                $saveBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});