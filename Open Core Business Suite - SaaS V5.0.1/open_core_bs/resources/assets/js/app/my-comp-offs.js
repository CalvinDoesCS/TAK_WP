/**
 * My Compensatory Offs - Self Service
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Ensure jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is required for my-comp-offs.js');
        return;
    }

    // Use jQuery's document ready
    jQuery(function ($) {
        // CSRF token setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize DataTable
        const dt = $('#compOffsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pageData.urls.datatable,
                data: function (d) {
                    d.status = $('#filterStatus').val();
                    d.from_date = $('#filterDateFrom').val();
                    d.to_date = $('#filterDateTo').val();
                }
            },
            columns: [
                { data: 'worked_date_display', orderable: true, searchable: true },
                { data: 'hours_worked_display', orderable: false, searchable: true },
                { data: 'comp_off_days_display', orderable: false, searchable: true },
                { data: 'expiry_date_display', orderable: true, searchable: false },
                { data: 'status_display', orderable: false, searchable: true },
                { data: 'usage_status', orderable: false, searchable: false },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            language: {
                search: pageData.labels.search,
                processing: pageData.labels.processing,
                lengthMenu: pageData.labels.lengthMenu,
                info: pageData.labels.info,
                infoEmpty: pageData.labels.infoEmpty,
                emptyTable: pageData.labels.emptyTable,
                paginate: pageData.labels.paginate
            },
            dom:
                '<"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                't' +
                '<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
        });

        // Initialize Flatpickr for worked date
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#worked_date', {
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                allowInput: true
            });
        }

        // Filter handlers
        $('#filterStatus, #filterDateFrom, #filterDateTo').on('change', function () {
            dt.ajax.reload();
        });

        // Calculate comp off days based on hours worked
        $('#hours_worked').on('input', function () {
            const hoursWorked = parseFloat($(this).val()) || 0;
            let compOffDays = 0;

            // Standard calculation: 8 hours = 1 day
            if (hoursWorked >= 8) {
                compOffDays = Math.floor(hoursWorked / 8);
            } else if (hoursWorked >= 4) {
                compOffDays = 0.5;
            }

            $('#comp_off_days').val(compOffDays);
        });

        // Show request form
        window.showRequestForm = function () {
            $('#compOffForm')[0].reset();
            $('#compOffForm').removeAttr('data-id');
            $('#comp_off_days').val(0);

            // Reset form title and button
            $('#compOffFormTitle').text(pageData.labels.requestCompOff || 'Request Compensatory Off');
            $('#compOffForm button[type="submit"]').html(
                '<i class="bx bx-send me-1"></i> ' + (pageData.labels.submitRequest || 'Submit Request')
            );

            const offcanvas = new bootstrap.Offcanvas(document.getElementById('compOffFormOffcanvas'));
            offcanvas.show();
        };

        // Submit comp off request
        $('#compOffForm').on('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const compOffId = $(this).attr('data-id');
            const isEdit = !!compOffId;

            let url = isEdit
                ? pageData.urls.update.replace('__ID__', compOffId)
                : pageData.urls.request;
            let method = isEdit ? 'PUT' : 'POST';

            // For PUT requests, convert FormData to regular object
            let requestData;
            if (isEdit) {
                requestData = {};
                formData.forEach((value, key) => {
                    requestData[key] = value;
                });
                requestData._method = 'PUT';
            } else {
                requestData = formData;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: requestData,
                processData: isEdit,
                contentType: isEdit ? 'application/x-www-form-urlencoded; charset=UTF-8' : false,
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.data?.message || pageData.labels.requested,
                            confirmButtonClass: 'btn btn-success',
                            buttonsStyling: false
                        });

                        // Hide offcanvas
                        const offcanvas = bootstrap.Offcanvas.getInstance(
                            document.getElementById('compOffFormOffcanvas')
                        );
                        offcanvas.hide();

                        // Reload table and refresh statistics
                        $('#compOffsTable').DataTable().ajax.reload();
                        refreshStatistics();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data?.message || response.data || pageData.labels.error,
                            confirmButtonClass: 'btn btn-danger',
                            buttonsStyling: false
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: xhr.responseJSON?.data?.message || xhr.responseJSON?.data || pageData.labels.error,
                        confirmButtonClass: 'btn btn-danger',
                        buttonsStyling: false
                    });
                }
            });
        });

        // View compensatory off details
        window.viewCompensatoryOff = function (id) {
            const url = pageData.urls.show.replace('__ID__', id);

            $.ajax({
                url: url,
                type: 'GET',
                success: function (html) {
                    $('#compOffDetailsContent').html(html);
                    const offcanvas = new bootstrap.Offcanvas(
                        document.getElementById('compOffDetailsOffcanvas')
                    );
                    offcanvas.show();
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: pageData.labels.error,
                        confirmButtonClass: 'btn btn-danger',
                        buttonsStyling: false
                    });
                }
            });
        };

        // Edit compensatory off (only for pending requests)
        window.editCompensatoryOff = function (id) {
            const url = pageData.urls.editData.replace('__ID__', id);

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    if (response.status === 'success' && response.data.compOff) {
                        const compOff = response.data.compOff;

                        // Populate form
                        $('#compOffForm').attr('data-id', compOff.id);

                        // Format worked_date to YYYY-MM-DD (handle both date string and ISO format)
                        let workedDate = compOff.worked_date;
                        if (workedDate.includes('T')) {
                            workedDate = workedDate.split('T')[0];
                        }
                        $('#worked_date').val(workedDate);

                        $('#hours_worked').val(compOff.hours_worked);
                        $('#comp_off_days').val(compOff.comp_off_days);
                        $('#reason').val(compOff.reason);

                        // Change form title and submit button
                        $('#compOffFormTitle').text(pageData.labels.editCompOff);
                        $('#compOffForm button[type="submit"]').html(
                            '<i class="bx bx-save me-1"></i> ' + pageData.labels.updateRequest
                        );

                        // Show offcanvas
                        const offcanvas = new bootstrap.Offcanvas(
                            document.getElementById('compOffFormOffcanvas')
                        );
                        offcanvas.show();
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: pageData.labels.error,
                        confirmButtonClass: 'btn btn-danger',
                        buttonsStyling: false
                    });
                }
            });
        };

        // Helper function to be called from details offcanvas
        window.editCompensatoryOffForm = function (id) {
            // Close details offcanvas
            const detailsOffcanvas = bootstrap.Offcanvas.getInstance(
                document.getElementById('compOffDetailsOffcanvas')
            );
            if (detailsOffcanvas) {
                detailsOffcanvas.hide();
            }

            // Open edit form
            setTimeout(() => {
                editCompensatoryOff(id);
            }, 300);
        };

        // Function to refresh statistics
        function refreshStatistics() {
            $.ajax({
                url: pageData.urls.statistics,
                type: 'GET',
                success: function (response) {
                    if (response.status === 'success' && response.data.statistics) {
                        const stats = response.data.statistics;

                        // Update all statistics displays
                        updateStatisticDisplay('total_earned', stats.total_earned || 0);
                        updateStatisticDisplay('available', stats.available || 0);
                        updateStatisticDisplay('used', stats.used || 0);
                        updateStatisticDisplay('expired', stats.expired || 0);
                    }
                },
                error: function (xhr) {
                    console.error('Failed to refresh statistics:', xhr);
                }
            });
        }

        // Helper function to update statistic display
        function updateStatisticDisplay(statKey, value) {
            // Update display in card body
            $(`[data-stat="${statKey}"] h4`).text(value);
        }
    }); // End jQuery ready
}); // End DOMContentLoaded
