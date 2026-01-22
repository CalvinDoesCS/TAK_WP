'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Selectors
    const offcanvasElement = document.getElementById('offcanvasAddOrUpdateShift');
    const offcanvas = offcanvasElement ? new bootstrap.Offcanvas(offcanvasElement) : null;
    const shiftForm = $('#shiftForm');
    const offcanvasLabel = $('#offcanvasShiftLabel');
    const submitBtn = $('#submitShiftBtn');
    const shiftIdInput = $('#shift_id');
    const generalErrorDiv = $('#generalErrorMessage');
    const workingDaysError = $('#workingDaysError');

    // Form Fields
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');

    // Initialize Flatpickr
    let fpStartTime, fpEndTime;
    if (startTimeInput) {
        fpStartTime = flatpickr(startTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true
        });
    }
    if (endTimeInput) {
        fpEndTime = flatpickr(endTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true
        });
    }

    // Initialize DataTable
    let shiftsTable = $('#shiftsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            type: 'GET',
            error: function (xhr, error, code) {
                console.error('DataTable AJAX error:', error);
            }
        },
        columns: [
            { data: 'id', name: 'id', orderable: true, searchable: true },
            { data: 'name', name: 'name', orderable: true, searchable: true },
            { data: 'code', name: 'code', orderable: true, searchable: true },
            { data: 'timing', name: 'start_time', orderable: false, searchable: false },
            { data: 'working_days', name: 'working_days', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            {
                targets: 0,
                visible: true,
                searchable: true
            }
        ],
        order: [[0, 'desc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        }
    });

    // Helper Functions
    function resetFormValidation() {
        shiftForm.find('.is-invalid').removeClass('is-invalid');
        shiftForm.find('.invalid-feedback').text('').hide();
        generalErrorDiv.text('').addClass('d-none');
        workingDaysError.text('').hide();
    }

    function resetForm() {
        resetFormValidation();
        shiftForm[0].reset();
        shiftIdInput.val('');
        fpStartTime?.clear();
        fpEndTime?.clear();

        // Set default checked days (Monday-Friday)
        shiftForm.find('.working-day-check').prop('checked', false);
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
            $(`#${day}Toggle`).prop('checked', true);
        });

        offcanvasLabel.text('Add Shift');
        submitBtn.text('Save').prop('disabled', false);
    }

    function setButtonLoading(isLoading) {
        const buttonText = shiftIdInput.val() ? 'Update' : 'Save';
        submitBtn.prop('disabled', isLoading);
        submitBtn.html(isLoading
            ? '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
            : buttonText
        );
    }

    function displayValidationErrors(errors) {
        resetFormValidation();
        let firstErrorField = null;

        $.each(errors, function (field, messages) {
            const inputElement = shiftForm.find(`[name="${field}"]`);
            if (inputElement.length) {
                inputElement.addClass('is-invalid');
                const feedbackElement = inputElement.siblings('.invalid-feedback');
                if (feedbackElement.length) {
                    feedbackElement.text(messages[0]).show();
                } else {
                    inputElement.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                }

                if (!firstErrorField) {
                    firstErrorField = inputElement;
                }
            }
        });

        if (firstErrorField) {
            firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }
    }

    function showToast(message, type = 'success') {
        if (type === 'success') {
            showSuccessToast(message);
        } else {
            showErrorToast(message);
        }
    }

    // Add Shift Button
    $('#addNewShift, [data-bs-target="#offcanvasAddOrUpdateShift"]').on('click', function() {
        if (!$(this).data('edit')) {
            resetForm();
        }
    });

    // Reset form when offcanvas is hidden
    if (offcanvasElement) {
        offcanvasElement.addEventListener('hidden.bs.offcanvas', function () {
            resetForm();
        });
    }

    // Form Submit
    shiftForm.on('submit', function (e) {
        e.preventDefault();

        // Validate at least one working day is selected
        const checkedDays = shiftForm.find('.working-day-check:checked').length;
        if (checkedDays === 0) {
            workingDaysError.text('Please select at least one working day.').show();
            return;
        } else {
            workingDaysError.hide();
        }

        setButtonLoading(true);
        resetFormValidation();

        const formData = new FormData(this);
        const shiftId = shiftIdInput.val();
        let url = pageData.urls.store;
        let method = 'POST';

        if (shiftId) {
            url = `${pageData.urls.edit}/${shiftId}`;
            method = 'POST'; // jQuery AJAX will use POST
            formData.append('_method', 'PUT');
        }

        // Convert checkboxes to 1 or 0
        const checkboxFields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        checkboxFields.forEach(field => {
            const isChecked = $(`#${field}Toggle`).is(':checked');
            formData.delete(field);
            formData.append(field, isChecked ? '1' : '0');
        });

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                setButtonLoading(false);
                if (response.success) {
                    offcanvas?.hide();
                    // Show success toast
                    showSuccessToast(response.message);
                    shiftsTable.ajax.reload(null, false);
                } else {
                    showErrorToast(response.message || 'Operation failed.');
                }
            },
            error: function (jqXHR) {
                setButtonLoading(false);
                if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
                    displayValidationErrors(jqXHR.responseJSON.errors);
                    showErrorToast(pageData.labels.validationError);
                } else {
                    const errorMessage = jqXHR.responseJSON?.message || 'Something went wrong. Please try again.';
                    showErrorToast(errorMessage);
                }
            }
        });
    });

    // Global Functions (called from DataTable onclick attributes)
    window.editShift = function(id) {
        $.ajax({
            url: `${pageData.urls.edit}/${id}/edit`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data) {
                    const shift = response.data;

                    // Populate form with correct IDs
                    shiftIdInput.val(shift.id);
                    $('#shiftName').val(shift.name);
                    $('#shiftCode').val(shift.code);
                    $('#shiftNotes').val(shift.notes || '');

                    // Set times using Flatpickr - use the correct format
                    if (shift.start_time && fpStartTime) {
                        fpStartTime.setDate(shift.start_time, true);
                    }
                    if (shift.end_time && fpEndTime) {
                        fpEndTime.setDate(shift.end_time, true);
                    }

                    // Set working days
                    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    days.forEach(day => {
                        $(`#${day}Toggle`).prop('checked', shift[day] == 1);
                    });

                    offcanvasLabel.text('Edit Shift');
                    submitBtn.text('Update');
                    offcanvas?.show();
                } else {
                    showErrorToast(response.message || 'Failed to load shift data.');
                }
            },
            error: function (jqXHR) {
                showErrorToast(jqXHR.responseJSON?.message || 'Failed to load shift data.');
            }
        });
    };

    window.deleteShift = function(id) {
        Swal.fire({
            title: pageData.labels.confirmDelete,
            text: pageData.labels.confirmDeleteText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.confirmDeleteButton,
            cancelButtonText: pageData.labels.cancelButton,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${pageData.urls.destroy}/${id}`,
                    method: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message || pageData.labels.deletedText);
                            shiftsTable.ajax.reload(null, false);
                        } else {
                            showErrorToast(response.message || 'Delete failed.');
                        }
                    },
                    error: function (jqXHR) {
                        showErrorToast(jqXHR.responseJSON?.message || 'Deletion failed.');
                    }
                });
            }
        });
    };

    window.toggleStatus = function(id) {
        $.ajax({
            url: `${pageData.urls.toggleStatus}/${id}/toggle-status`,
            method: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showSuccessToast(response.message);
                    shiftsTable.ajax.reload(null, false);
                } else {
                    showErrorToast(response.message || 'Status toggle failed.');
                }
            },
            error: function (jqXHR) {
                showErrorToast(jqXHR.responseJSON?.message || 'Status toggle failed.');
            }
        });
    };
});
