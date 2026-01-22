$(function () {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Form submission handler
    $('#accountingCoreSettingsForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {};

        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        // Handle checkboxes - convert to 0/1 for backend
        $('#accountingCoreSettingsForm input[type="checkbox"]').each(function() {
            const name = $(this).attr('name');
            data[name] = $(this).is(':checked') ? 1 : 0;
        });

        // Submit via AJAX
        $.ajax({
            url: pageData.urls.update,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: response.message || pageData.labels.settingsUpdated,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let message = pageData.labels.errorOccurred;

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }

                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: message
                });
            }
        });
    });

    // Reset form handler
    $('#accountingCoreSettingsForm button[type="reset"]').on('click', function(e) {
        e.preventDefault();
        location.reload();
    });
});