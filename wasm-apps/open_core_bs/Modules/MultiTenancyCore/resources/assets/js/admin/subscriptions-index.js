$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize DataTable
    const dt = $('.dt-subscriptions').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.status = $('.subscription_status select').val();
                d.plan_id = $('.subscription_plan select').val();
                d.expiring_soon = $('#expiring_soon').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            {data: 'tenant_info', name: 'tenant_id'},
            {data: 'plan_info', name: 'plan_id'},
            {data: 'status_display', name: 'status'},
            {data: 'period', name: 'starts_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[0, 'desc']],
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
    $('.subscription_status').html(`
        <select class="form-select">
            <option value="">${pageData.labels.allStatus}</option>
            <option value="trial">${pageData.labels.trial}</option>
            <option value="active">${pageData.labels.active}</option>
            <option value="cancelled">${pageData.labels.cancelled}</option>
            <option value="expired">${pageData.labels.expired}</option>
        </select>
    `);

    // Plan filter
    let planOptions = '<select class="form-select">';
    planOptions += `<option value="">${pageData.labels.allPlans}</option>`;
    pageData.plans.forEach(function(plan) {
        planOptions += `<option value="${plan.id}">${plan.name}</option>`;
    });
    planOptions += '</select>';
    $('.subscription_plan').html(planOptions);

    // Filter change events
    $('.subscription_status select, .subscription_plan select').on('change', function () {
        dt.ajax.reload();
    });

    $('#expiring_soon').on('change', function () {
        dt.ajax.reload();
    });

    // Renew subscription
    window.renewSubscription = function(id) {
        Swal.fire({
            title: pageData.labels.confirmRenew,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesRenew,
            cancelButtonText: pageData.labels.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(pageData.urls.renew.replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: pageData.labels.renewed,
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
                });
            }
        });
    };

    // Cancel subscription
    window.cancelSubscription = function(id) {
        Swal.fire({
            title: pageData.labels.confirmCancel,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.cancelNow,
            confirmButtonColor: '#d33',
            showDenyButton: true,
            denyButtonText: pageData.labels.cancelEnd,
            cancelButtonText: pageData.labels.noKeepActive
        }).then((result) => {
            if (result.isConfirmed || result.isDenied) {
                const immediately = result.isConfirmed;
                
                $.post(pageData.urls.cancel.replace(':id', id), {
                    immediately: immediately ? 1 : 0
                }, function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: pageData.labels.cancelled,
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
                });
            }
        });
    };

    // Change plan
    window.changePlan = function(id) {
        $('#change_subscription_id').val(id);
        
        // Load current subscription info
        $.get(pageData.urls.show.replace(':id', id), function(subscription) {
            $('#current_plan_info').html(`
                <strong>${subscription.plan.name}</strong><br>
                ${subscription.plan.formatted_price}
            `);
            
            // Remove current plan from options
            $('#new_plan_id option').show();
            $('#new_plan_id option[value="' + subscription.plan_id + '"]').hide();
        });
        
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('changePlanOffcanvas'));
        offcanvas.show();
    };

    // Submit plan change
    $('#changePlanForm').on('submit', function(e) {
        e.preventDefault();

        const subscriptionId = $('#change_subscription_id').val();
        const planId = $('#new_plan_id').val();
        const immediate = $('#immediate').is(':checked') ? 1 : 0;

        if (!planId) {
            Swal.fire({
                icon: 'warning',
                title: pageData.labels.error,
                text: pageData.labels.selectPlan || 'Please select a plan'
            });
            return;
        }

        $.ajax({
            url: pageData.urls.changePlan.replace(':id', subscriptionId),
            method: 'POST',
            data: {
                plan_id: planId,
                immediate: immediate
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: pageData.labels.planChanged,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('changePlanOffcanvas'));
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
});