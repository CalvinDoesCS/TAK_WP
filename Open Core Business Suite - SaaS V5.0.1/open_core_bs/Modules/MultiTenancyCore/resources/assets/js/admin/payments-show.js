$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Approve payment
    window.approvePayment = function(id) {
        Swal.fire({
            title: pageData.labels.confirmApprove,
            html: `
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="activate_subscription" checked>
                    <label class="form-check-label" for="activate_subscription">
                        ${pageData.labels.activateSubscription}
                    </label>
                </div>
                <div>
                    <label class="form-label">${pageData.labels.approveNotes}</label>
                    <textarea class="form-control" id="approval_notes" rows="3"></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesApprove,
            cancelButtonText: pageData.labels.cancel,
            preConfirm: () => {
                return {
                    activate_subscription: document.getElementById('activate_subscription').checked,
                    notes: document.getElementById('approval_notes').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(pageData.urls.approve, {
                    activate_subscription: result.value.activate_subscription ? 1 : 0,
                    notes: result.value.notes
                }, function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: pageData.labels.approved,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data
                        });
                    }
                });
            }
        });
    };

    // Reject payment
    window.rejectPayment = function(id) {
        Swal.fire({
            title: pageData.labels.confirmReject,
            text: pageData.labels.rejectReason,
            icon: 'warning',
            input: 'textarea',
            inputAttributes: {
                autocapitalize: 'off',
                required: true
            },
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesReject,
            cancelButtonText: pageData.labels.cancel,
            inputValidator: (value) => {
                if (!value) {
                    return pageData.labels.provideReason;
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(pageData.urls.reject, {
                    reason: result.value
                }, function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: pageData.labels.rejected,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data
                        });
                    }
                });
            }
        });
    };
});