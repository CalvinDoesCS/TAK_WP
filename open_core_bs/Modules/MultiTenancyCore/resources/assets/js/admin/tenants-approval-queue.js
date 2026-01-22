$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize DataTable
    const dt = $('.dt-approval-queue').DataTable({
        processing: true,
        serverSide: true,
        ajax: pageData.urls.datatable,
        columns: [
            {data: 'company_info', name: 'name'},
            {data: 'requested_plan', name: 'requested_plan'},
            {data: 'submitted_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
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

    // Approve tenant
    window.approveTenant = function(id) {
        Swal.fire({
            title: pageData.labels.confirmApprove,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesApprove,
            cancelButtonText: pageData.labels.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.urls.approve.replace(':id', id),
                    method: 'POST',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: pageData.labels.approved,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            dt.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.error,
                                text: response.data
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.data || xhr.responseJSON?.message || 'An error occurred';
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: message
                        });
                    }
                });
            }
        });
    };

    // Reject tenant
    window.rejectTenant = function(id) {
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
                $.ajax({
                    url: pageData.urls.reject.replace(':id', id),
                    method: 'POST',
                    data: { reason: result.value },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: pageData.labels.rejected,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            dt.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.error,
                                text: response.data
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.data || xhr.responseJSON?.message || 'An error occurred';
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: message
                        });
                    }
                });
            }
        });
    };
});