/**
 * Attendance Monthly Summary Report
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    const summaryTable = initializeDataTable();

    // Initialize Select2
    initializeSelect2();

    // Initialize Flatpickr
    initializeFlatpickr();

    // Bind Events
    bindFilterEvents();

    // Load initial statistics
    loadStatistics();
});

/**
 * Initialize DataTable with server-side processing
 */
function initializeDataTable() {
    return $('#monthlySummaryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            type: 'GET',
            data: function (d) {
                // Get month/year from flatpickr
                const monthValue = $('#month').val();
                let month = new Date().getMonth() + 1;
                let year = new Date().getFullYear();

                if (monthValue) {
                    const parts = monthValue.split('-');
                    year = parts[0];
                    month = parts[1];
                }

                d.month = month;
                d.year = year;
                d.department_id = $('#departmentId').val() || '';
                d.user_id = $('#userId').val() || '';
            }
        },
        columns: [
            { data: 'user', name: 'user', orderable: false },
            { data: 'present_days', name: 'present_days' },
            { data: 'absent_days', name: 'absent_days' },
            { data: 'late_days', name: 'late_days' },
            { data: 'half_days', name: 'half_days' },
            { data: 'total_working_hours', name: 'total_working_hours' },
            { data: 'total_late_hours', name: 'total_late_hours' },
            { data: 'total_early_hours', name: 'total_early_hours' },
            { data: 'total_overtime_hours', name: 'total_overtime_hours' },
            { data: 'attendance_percentage', name: 'attendance_percentage' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[9, 'desc']], // Sort by attendance percentage descending
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        },
        drawCallback: function() {
            // Update statistics after table draw
            loadStatistics();
        }
    });
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('#departmentId').select2({
        placeholder: pageData.labels.selectDepartment || 'Select Department',
        allowClear: true,
        width: '100%'
    });

    $('#userId').select2({
        placeholder: pageData.labels.selectEmployee || 'Select Employee',
        allowClear: true,
        width: '100%'
    });
}

/**
 * Initialize Flatpickr for month picker
 */
function initializeFlatpickr() {
    const currentDate = new Date();
    const currentMonthYear = currentDate.getFullYear() + '-' +
                            String(currentDate.getMonth() + 1).padStart(2, '0');

    $('#month').flatpickr({
        dateFormat: 'Y-m',
        defaultDate: currentMonthYear,
        maxDate: 'today'
    });
}

/**
 * Bind filter events
 */
function bindFilterEvents() {
    // Apply filters button
    $('#filterBtn').on('click', function() {
        $('#monthlySummaryTable').DataTable().ajax.reload();
        loadStatistics();
    });

    // Reset filters button
    $('#resetBtn').on('click', function() {
        // Reset month to current month
        const currentDate = new Date();
        const currentMonthYear = currentDate.getFullYear() + '-' +
                                String(currentDate.getMonth() + 1).padStart(2, '0');

        const flatpickrInstance = document.querySelector('#month')._flatpickr;
        flatpickrInstance.setDate(currentMonthYear);

        $('#departmentId').val('').trigger('change');
        $('#userId').val('').trigger('change');

        $('#monthlySummaryTable').DataTable().ajax.reload();
        loadStatistics();
    });

    // Auto-reload on filter change
    $('#departmentId, #userId').on('change', function() {
        $('#monthlySummaryTable').DataTable().ajax.reload();
        loadStatistics();
    });

    // Month change event
    $('#month').on('change', function() {
        $('#monthlySummaryTable').DataTable().ajax.reload();
        loadStatistics();
    });
}

/**
 * Load aggregate statistics
 */
function loadStatistics() {
    // Get month/year from flatpickr
    const monthValue = $('#month').val();
    let month = new Date().getMonth() + 1;
    let year = new Date().getFullYear();

    if (monthValue) {
        const parts = monthValue.split('-');
        year = parts[0];
        month = parts[1];
    }

    const departmentId = $('#departmentId').val();
    const userId = $('#userId').val();

    $.ajax({
        url: pageData.urls.statistics,
        method: 'GET',
        data: {
            month: month,
            year: year,
            department_id: departmentId,
            user_id: userId
        },
        success: function(response) {
            if (response.status === 'success') {
                const data = response.data;

                $('#totalEmployees').text(data.total_employees || 0);
                $('#averageAttendanceRate').text((data.average_attendance_rate || 0) + '%');
                $('#totalWorkingHours').text(formatHours(data.total_working_hours || 0));
                $('#totalOvertimeHours').text(formatHours(data.total_overtime_hours || 0));
            }
        },
        error: function(xhr) {
            console.error('Failed to load statistics:', xhr);
        }
    });
}

/**
 * Format hours to human-readable format
 */
function formatHours(hours) {
    if (!hours || hours === 0) {
        return '0h 0m';
    }

    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);

    if (h === 0) {
        return m + 'm';
    }

    if (m === 0) {
        return h + 'h';
    }

    return h + 'h ' + m + 'm';
}
