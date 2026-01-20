$(function () {
    // Initialize Select2
    $('.select2').select2({
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true
    });

    // Initialize Flatpickr
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        minDate: 'today'
    });

    // Get global trial days from pageData
    const globalTrialDays = pageData.trialDays || 0;

    // Plan change handler
    $('#plan_id').on('change', function() {
        const selected = $(this).find(':selected');
        
        if (selected.val()) {
            const price = selected.data('price');
            const period = selected.data('period');
            
            let periodText = period === 'monthly' ? pageData.labels.perMonth : 
                           period === 'yearly' ? pageData.labels.perYear : pageData.labels.oneTime;
            
            let details = `
                <p class="mb-1"><strong>${pageData.labels.price}</strong> ${price} ${periodText}</p>
                <p class="mb-0"><strong>${pageData.labels.trialDays}</strong> ${globalTrialDays}</p>
            `;
            
            $('#plan_details').html(details);
            $('#plan_info').show();
            
            // Set status based on trial days
            if (globalTrialDays > 0) {
                $('#status').val('trial');
            }
            
            // Calculate end date for trial
            if ($('#status').val() === 'trial' && globalTrialDays > 0) {
                const startDate = $('#starts_at').val() || new Date().toISOString().split('T')[0];
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + globalTrialDays);
                $('#ends_at').val(endDate.toISOString().split('T')[0]);
            }
        } else {
            $('#plan_info').hide();
        }
    });

    // Status change handler
    $('#status').on('change', function() {
        if ($(this).val() === 'trial' && globalTrialDays > 0) {
            const startDate = $('#starts_at').val() || new Date().toISOString().split('T')[0];
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + globalTrialDays);
            $('#ends_at').val(endDate.toISOString().split('T')[0]);
        }
    });

    // Form submission
    $('#createSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
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
                        text: pageData.labels.subscriptionCreated,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = pageData.urls.subscriptionsIndex;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response.data
                    });
                }
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'An error occurred';
                if (xhr.responseJSON?.data?.errors) {
                    const errors = xhr.responseJSON.data.errors;
                    message = Object.values(errors).flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: message
                });
            }
        });
    });
});