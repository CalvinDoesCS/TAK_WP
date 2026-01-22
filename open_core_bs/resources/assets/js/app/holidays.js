'use strict';

$(function () {
    // CSRF setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    // Initialize Select2
    $('.select2').select2({
        dropdownParent: $('#holidayFormOffcanvas'),
        placeholder: pageData.labels.selectPlaceholder || 'Select...',
        allowClear: true,
    });

    // Initialize Flatpickr for date input
    const datePicker = flatpickr('#date', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    // Initialize DataTable
    const dtTable = $('.datatables-holidays');
    let dataTable;

    if (dtTable.length) {
        dataTable = dtTable.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pageData.urls.datatable,
                error: function (xhr, error, code) {
                    console.error('DataTable error:', error, code, xhr.responseText);
                },
            },
            columns: [
                { data: 'id', orderable: false, searchable: false }, // Responsive control
                { data: 'name' },
                { data: 'date_formatted' },
                { data: 'type_badge' },
                { data: 'applicability' },
                { data: 'properties' },
                { data: 'status_badge' },
                { data: 'actions', orderable: false, searchable: false },
            ],
            columnDefs: [
                {
                    // Responsive control
                    className: 'control',
                    targets: 0,
                    render: function () {
                        return '';
                    },
                },
            ],
            order: [[2, 'asc']], // Order by date
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: '',
                searchPlaceholder: pageData.labels.searchPlaceholder || 'Search holidays...',
                lengthMenu: '_MENU_',
                info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries to show',
                infoFiltered: '(filtered from _TOTAL_ total entries)',
                paginate: {
                    next: '<i class="bx bx-chevron-right"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>',
                },
            },
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            const data = row.data();
                            return 'Details of ' + data.name;
                        },
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        const data = $.map(columns, function (col, i) {
                            return col.title !== ''
                                ? '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                  '<td>' + col.title + ':</td> ' +
                                  '<td>' + col.data + '</td>' +
                                  '</tr>'
                                : '';
                        }).join('');

                        return data ? $('<table class="table"/>').append(data) : false;
                    },
                },
            },
        });
    }

    // Add Holiday button
    $('#btnAddHoliday').on('click', function () {
        resetForm();
        $('#holidayFormOffcanvasLabel').text(pageData.labels.addHoliday);
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('holidayFormOffcanvas'));
        offcanvas.show();
    });

    // Edit Holiday function (called from DataTable actions)
    window.editHoliday = function (id) {
        $.ajax({
            url: `${pageData.urls.show}/${id}`,
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const data = response.data;

                    // Populate form fields
                    $('#holiday_id').val(data.id);
                    $('#name').val(data.name);
                    $('#code').val(data.code);
                    $('#date').val(data.date);
                    $('#type').val(data.type);
                    $('#category').val(data.category || '');
                    $('#description').val(data.description || '');
                    $('#color').val(data.color || '#4CAF50');
                    $('#applicable_for').val(data.applicable_for).trigger('change');

                    // Set checkboxes
                    $('#is_recurring').prop('checked', data.is_recurring);
                    $('#is_optional').prop('checked', data.is_optional);
                    $('#is_restricted').prop('checked', data.is_restricted);
                    $('#is_half_day').prop('checked', data.is_half_day).trigger('change');
                    $('#half_day_type').val(data.half_day_type || 'morning');
                    $('#is_visible_to_employees').prop('checked', data.is_visible_to_employees);
                    $('#send_notification').prop('checked', data.send_notification).trigger('change');
                    $('#notification_days_before').val(data.notification_days_before || 7);

                    // Set multi-select fields
                    if (data.departments && data.departments.length > 0) {
                        $('#departments').val(data.departments).trigger('change');
                    }
                    if (data.specific_employees && data.specific_employees.length > 0) {
                        $('#specific_employees').val(data.specific_employees).trigger('change');
                    }

                    // Update date picker
                    datePicker.setDate(data.date);

                    $('#holidayFormOffcanvasLabel').text(pageData.labels.editHoliday);
                    const offcanvas = new bootstrap.Offcanvas(document.getElementById('holidayFormOffcanvas'));
                    offcanvas.show();
                } else {
                    showAlert('error', response.message || 'Failed to load holiday data');
                }
            },
            error: function () {
                showAlert('error', 'Failed to load holiday data');
            },
        });
    };

    // Toggle Status function
    window.toggleStatus = function (id) {
        $.ajax({
            url: `${pageData.urls.toggleStatus}/${id}/toggle-status`,
            type: 'POST',
            success: function (response) {
                if (response.status === 'success') {
                    showAlert('success', response.message || pageData.labels.statusUpdated);
                    dataTable.ajax.reload(null, false);
                } else {
                    showAlert('error', response.message || 'Failed to update status');
                }
            },
            error: function () {
                showAlert('error', 'Failed to update status');
            },
        });
    };

    // Delete Holiday function
    window.deleteHoliday = function (id) {
        Swal.fire({
            title: pageData.labels.confirmDelete,
            text: pageData.labels.confirmDeleteText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yes,
            cancelButtonText: pageData.labels.no,
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${pageData.urls.destroy}/${id}`,
                    type: 'DELETE',
                    success: function (response) {
                        if (response.status === 'success') {
                            showAlert('success', response.message || pageData.labels.deleted);
                            dataTable.ajax.reload(null, false);
                        } else {
                            showAlert('error', response.message || 'Failed to delete holiday');
                        }
                    },
                    error: function () {
                        showAlert('error', 'Failed to delete holiday');
                    },
                });
            }
        });
    };

    // Form submission
    $('#holidayForm').on('submit', function (e) {
        e.preventDefault();

        const holidayId = $('#holiday_id').val();
        const isUpdate = holidayId !== '';
        const url = isUpdate ? `${pageData.urls.update}/${holidayId}` : pageData.urls.store;
        const method = isUpdate ? 'PUT' : 'POST';

        const formData = new FormData(this);

        // Convert checkbox values
        formData.set('is_recurring', $('#is_recurring').is(':checked') ? '1' : '0');
        formData.set('is_optional', $('#is_optional').is(':checked') ? '1' : '0');
        formData.set('is_restricted', $('#is_restricted').is(':checked') ? '1' : '0');
        formData.set('is_half_day', $('#is_half_day').is(':checked') ? '1' : '0');
        formData.set('is_visible_to_employees', $('#is_visible_to_employees').is(':checked') ? '1' : '0');
        formData.set('send_notification', $('#send_notification').is(':checked') ? '1' : '0');

        // Add _method for PUT request
        if (isUpdate) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    showAlert('success', response.message || (isUpdate ? 'Holiday updated successfully' : 'Holiday created successfully'));

                    // Close offcanvas
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('holidayFormOffcanvas'));
                    if (offcanvas) {
                        offcanvas.hide();
                    }

                    // Reload DataTable
                    dataTable.ajax.reload(null, false);

                    // Reset form
                    resetForm();
                } else {
                    showAlert('error', response.message || 'Failed to save holiday');
                }
            },
            error: function (xhr) {
                let errorMessage = 'Failed to save holiday';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                showAlert('error', errorMessage);
            },
        });
    });

    // Applicable For change handler
    $('#applicable_for').on('change', function () {
        const value = $(this).val();

        // Hide all containers
        $('#departments_container').addClass('d-none');
        $('#employees_container').addClass('d-none');

        // Show relevant container
        if (value === 'department') {
            $('#departments_container').removeClass('d-none');
        } else if (value === 'custom') {
            $('#employees_container').removeClass('d-none');
        }
    });

    // Half Day checkbox handler
    $('#is_half_day').on('change', function () {
        if ($(this).is(':checked')) {
            $('#half_day_container').removeClass('d-none');
        } else {
            $('#half_day_container').addClass('d-none');
        }
    });

    // Send Notification checkbox handler
    $('#send_notification').on('change', function () {
        if ($(this).is(':checked')) {
            $('#notification_container').removeClass('d-none');
        } else {
            $('#notification_container').addClass('d-none');
        }
    });

    // Reset form
    function resetForm() {
        $('#holidayForm')[0].reset();
        $('#holiday_id').val('');
        $('#departments').val(null).trigger('change');
        $('#specific_employees').val(null).trigger('change');
        $('#departments_container').addClass('d-none');
        $('#employees_container').addClass('d-none');
        $('#half_day_container').addClass('d-none');
        $('#notification_container').addClass('d-none');
        $('#is_visible_to_employees').prop('checked', true);
        datePicker.clear();
    }

    // Show alert helper
    function showAlert(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? pageData.labels.success : pageData.labels.error,
            html: message,
            customClass: {
                confirmButton: 'btn btn-' + (type === 'success' ? 'success' : 'danger'),
            },
            buttonsStyling: false,
        });
    }

    // Clear form when offcanvas is hidden
    $('#holidayFormOffcanvas').on('hidden.bs.offcanvas', function () {
        resetForm();
    });
});
