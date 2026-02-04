$(function () {
    // Set up AJAX defaults
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Make functions available globally
    window.autoProvision = function() {
        Swal.fire({
            title: pageData.labels.autoProvisionTitle,
            text: pageData.labels.autoProvisionText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesProvision,
            cancelButtonText: pageData.labels.cancel,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: pageData.urls.autoProvision,
                    type: 'POST'
                }).then(response => response).catch(error => {
                    Swal.showValidationMessage(
                        error.responseJSON?.message || 'Provisioning failed'
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.success,
                    text: result.value.message || pageData.labels.provisioningSuccess,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = pageData.urls.index;
                });
            }
        });
    };

    window.manualProvision = function(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        Swal.fire({
            title: pageData.labels.configureTitle,
            text: pageData.labels.configureText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesConfigure,
            cancelButtonText: pageData.labels.cancel,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: pageData.urls.manualProvision,
                    type: 'POST',
                    data: data
                }).then(response => response).catch(error => {
                    Swal.showValidationMessage(
                        error.responseJSON?.message || error.responseJSON?.data || 'Configuration failed'
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.success,
                    text: result.value.message || pageData.labels.provisioningSuccess,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = pageData.urls.index;
                });
            }
        });
    };
});