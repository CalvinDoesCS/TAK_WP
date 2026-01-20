$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Load statistics
    function loadStatistics() {
        $.get(pageData.urls.statistics, function(data) {
            $('#pending_count').text(data.pending_count || 0);
            $('#pending_amount').text(data.pending_amount_formatted || '0');
            $('#approved_today').text(data.approved_today || 0);
            $('#approved_month').text(data.approved_this_month || 0);
        });
    }

    // Initialize DataTable
    const dt = $('.dt-payment-approvals').DataTable({
        processing: true,
        serverSide: true,
        ajax: pageData.urls.datatable,
        columns: [
            {data: 'tenant_info', name: 'tenant_id'},
            {data: 'amount_display', name: 'amount'},
            {data: 'payment_info', name: 'payment_method'},
            {data: 'status_display', name: 'status'},
            {data: 'proof_document', name: 'proof_document_path'},
            {data: 'submitted_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[5, 'desc']],
        displayLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end mt-n6 mt-md-0"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            paginate: {
                next: '<i class="bx bx-chevron-right bx-18px"></i>',
                previous: '<i class="bx bx-chevron-left bx-18px"></i>'
            }
        }
    });

    // Load statistics on page load
    loadStatistics();

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
                $.post(pageData.urls.approve.replace(':id', id), {
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
                        });
                        dt.ajax.reload();
                        loadStatistics();
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
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage(pageData.labels.provideReason);
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(pageData.urls.reject.replace(':id', id), {
                    reason: result.value
                }, function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: pageData.labels.rejected,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        dt.ajax.reload();
                        loadStatistics();
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