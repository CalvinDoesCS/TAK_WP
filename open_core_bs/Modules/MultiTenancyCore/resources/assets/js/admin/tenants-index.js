$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize DataTable
    const dt = $('.dt-tenants').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.status = $('.tenant_status select').val();
                d.plan_id = $('.tenant_plan select').val();
            }
        },
        columns: [
            {data: 'company_info', name: 'name'},
            {data: 'plan', name: 'plan', orderable: false},
            {data: 'status_display', name: 'status'},
            {data: 'database_status', name: 'database_status', orderable: false},
            {data: 'created_at_formatted', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[4, 'desc']],
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

    // Status filter
    $('.tenant_status').html(`
        <select class="form-select">
            <option value="">${pageData.labels.allStatus}</option>
            <option value="pending">${pageData.labels.pending}</option>
            <option value="approved">${pageData.labels.approved}</option>
            <option value="active">${pageData.labels.active}</option>
            <option value="suspended">${pageData.labels.suspended}</option>
            <option value="cancelled">${pageData.labels.cancelled}</option>
        </select>
    `);

    // Plan filter
    $('.tenant_plan').html(`
        <select class="form-select">
            <option value="">${pageData.labels.allPlans}</option>
            ${pageData.plans.map(plan => `<option value="${plan.id}">${plan.name}</option>`).join('')}
        </select>
    `);

    // Filter change events
    $('.tenant_status select, .tenant_plan select').on('change', function () {
        dt.ajax.reload();
    });

    // Edit tenant
    window.editTenant = function(id) {
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('editTenantOffcanvas'));
        
        // Load tenant data
        $.get(pageData.urls.edit.replace(':id', id), function(tenant) {
            $('#edit_tenant_id').val(tenant.id);
            $('#edit_name').val(tenant.name);
            $('#edit_email').val(tenant.email);
            $('#edit_phone').val(tenant.phone);
            $('#edit_status').val(tenant.status);
            $('#edit_notes').val(tenant.notes);
            
            offcanvas.show();
        });
    };

    // Update tenant
    $('#editTenantForm').on('submit', function(e) {
        e.preventDefault();
        
        const tenantId = $('#edit_tenant_id').val();
        const formData = new FormData(this);
        
        $.ajax({
            url: pageData.urls.update.replace(':id', tenantId),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: pageData.labels.updated,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('editTenantOffcanvas'));
                    offcanvas.hide();
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
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: xhr.responseJSON?.message || 'An error occurred'
                });
            }
        });
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

    // Suspend tenant
    window.suspendTenant = function(id) {
        Swal.fire({
            title: pageData.labels.confirmSuspend,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesSuspend,
            cancelButtonText: pageData.labels.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.urls.suspend.replace(':id', id),
                    method: 'POST',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: pageData.labels.suspended,
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

    // Activate tenant
    window.activateTenant = function(id) {
        Swal.fire({
            title: pageData.labels.confirmActivate,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesActivate,
            cancelButtonText: pageData.labels.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.urls.activate.replace(':id', id),
                    method: 'POST',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: pageData.labels.activated,
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