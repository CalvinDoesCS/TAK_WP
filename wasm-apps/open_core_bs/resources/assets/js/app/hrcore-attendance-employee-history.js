$(function () {
    'use strict';

    // Variables
    let currentData = null;
    let workingHoursChart = null;

    // CSRF Token Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Flatpickr for date range
    const dateRangePicker = $('.flatpickr-range').flatpickr({
        mode: 'range',
        dateFormat: 'Y-m-d',
        defaultDate: [moment().startOfMonth().format('YYYY-MM-DD'), moment().endOfMonth().format('YYYY-MM-DD')],
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                loadAttendanceData();
            }
        }
    });

    // Quick range selector
    $('#quickRange').on('change', function() {
        const range = $(this).val();
        let start, end;

        switch(range) {
            case 'current_month':
                start = moment().startOfMonth();
                end = moment().endOfMonth();
                break;
            case 'last_month':
                start = moment().subtract(1, 'month').startOfMonth();
                end = moment().subtract(1, 'month').endOfMonth();
                break;
            case 'last_3_months':
                start = moment().subtract(3, 'months').startOfMonth();
                end = moment().endOfMonth();
                break;
            case 'last_6_months':
                start = moment().subtract(6, 'months').startOfMonth();
                end = moment().endOfMonth();
                break;
            case 'current_year':
                start = moment().startOfYear();
                end = moment().endOfMonth();
                break;
        }

        dateRangePicker.setDate([start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD')]);
    });

    // Load data button
    $('#loadDataBtn').on('click', function() {
        loadAttendanceData();
    });

    // Initial load
    loadAttendanceData();

    // Load attendance data
    function loadAttendanceData() {
        const dates = dateRangePicker.selectedDates;
        if (dates.length !== 2) {
            Swal.fire({
                icon: 'warning',
                title: pageData.labels.warning || 'Warning!',
                text: 'Please select a valid date range'
            });
            return;
        }

        const startDate = moment(dates[0]).format('YYYY-MM-DD');
        const endDate = moment(dates[1]).format('YYYY-MM-DD');

        $('#loadingIndicator').removeClass('d-none');
        $('#mainContent').addClass('d-none');

        $.ajax({
            url: pageData.urls.employeeHistory,
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.status === 'success') {
                    currentData = response.data;
                    renderDashboard(currentData);
                    $('#loadingIndicator').addClass('d-none');
                    $('#mainContent').removeClass('d-none');
                } else {
                    showError('Failed to load attendance data');
                }
            },
            error: function(xhr) {
                showError('Failed to load attendance data: ' + (xhr.responseJSON?.message || 'Unknown error'));
                $('#loadingIndicator').addClass('d-none');
            }
        });
    }

    // Render dashboard
    function renderDashboard(data) {
        renderStatistics(data.statistics);
        renderCalendar(data.calendar);
        renderChart(data.attendances);
        renderTimeline(data.attendances);
    }

    // Render statistics cards
    function renderStatistics(stats) {
        $('#statPresent').text(stats.present_days);
        $('#statAbsent').text(stats.absent_days);
        $('#statLate').text(stats.late_days);
        $('#statAvgHours').text(stats.avg_working_hours.toFixed(1));
        $('#statOvertime').text(formatHours(stats.total_overtime_hours));
        $('#statAttendancePercentage').text(stats.attendance_percentage + '%');
    }

    // Render calendar view
    function renderCalendar(calendarData) {
        let calendarHtml = '<div class="calendar-grid">';

        // Days of week header
        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        calendarHtml += '<div class="calendar-header row g-1 mb-2">';
        daysOfWeek.forEach(day => {
            calendarHtml += `<div class="col text-center"><small class="text-muted fw-bold">${day}</small></div>`;
        });
        calendarHtml += '</div>';

        // Calendar days
        calendarHtml += '<div class="calendar-body">';
        let currentWeek = [];

        calendarData.forEach((day, index) => {
            if (index === 0) {
                // Add empty cells for days before the first day
                const firstDayOfWeek = moment(day.date).day();
                for (let i = 0; i < firstDayOfWeek; i++) {
                    currentWeek.push('<div class="col"></div>');
                }
            }

            const isToday = day.isToday ? 'border-primary' : '';
            const statusBadge = getStatusBadge(day.status);

            currentWeek.push(`
                <div class="col">
                    <div class="calendar-day text-center p-2 border rounded ${isToday}"
                         data-date="${day.date}"
                         style="cursor: pointer; min-height: 50px;">
                        <div class="fw-bold">${day.day}</div>
                        <div class="mt-1">${statusBadge}</div>
                    </div>
                </div>
            `);

            // Create new row after Saturday
            if (moment(day.date).day() === 6 || index === calendarData.length - 1) {
                calendarHtml += `<div class="row g-1 mb-1">${currentWeek.join('')}</div>`;
                currentWeek = [];
            }
        });

        calendarHtml += '</div></div>';
        $('#attendanceCalendar').html(calendarHtml);

        // Add click handlers for calendar days
        $('.calendar-day').on('click', function() {
            const date = $(this).data('date');
            scrollToDate(date);
        });
    }

    // Get status badge
    function getStatusBadge(status) {
        const badges = {
            'present': '<span class="badge bg-success" style="font-size: 0.6rem;">P</span>',
            'late': '<span class="badge bg-warning" style="font-size: 0.6rem;">L</span>',
            'absent': '<span class="badge bg-danger" style="font-size: 0.6rem;">A</span>',
            'half-day': '<span class="badge bg-info" style="font-size: 0.6rem;">H</span>',
            'weekend': '<span class="badge bg-secondary" style="font-size: 0.6rem;">W</span>',
            'not-marked': '<span class="text-muted" style="font-size: 0.6rem;">-</span>'
        };
        return badges[status] || '';
    }

    // Render working hours chart
    function renderChart(attendances) {
        const chartElement = document.getElementById('workingHoursChart');
        if (!chartElement) return;

        // Prepare data (limit to last 30 records for readability)
        const recentAttendances = attendances.slice(0, 30).reverse();
        const dates = recentAttendances.map(a => moment(a.date).format('MMM DD'));
        const workingHours = recentAttendances.map(a => a.working_hours);
        const lateHours = recentAttendances.map(a => a.late_hours);
        const overtimeHours = recentAttendances.map(a => a.overtime_hours);

        const chartOptions = {
            series: [
                {
                    name: pageData.labels.workingHours,
                    data: workingHours
                },
                {
                    name: pageData.labels.lateHours,
                    data: lateHours
                },
                {
                    name: pageData.labels.overtimeHours,
                    data: overtimeHours
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                stacked: false,
                toolbar: {
                    show: true
                }
            },
            colors: ['#28a745', '#ffc107', '#17a2b8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: dates
            },
            yaxis: {
                title: {
                    text: 'Hours'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toFixed(2) + " hours";
                    }
                }
            },
            legend: {
                position: 'top'
            }
        };

        if (workingHoursChart) {
            workingHoursChart.destroy();
        }

        workingHoursChart = new ApexCharts(chartElement, chartOptions);
        workingHoursChart.render();
    }

    // Render timeline
    function renderTimeline(attendances) {
        if (attendances.length === 0) {
            $('#timelineContainer').addClass('d-none');
            $('#noDataMessage').removeClass('d-none');
            return;
        }

        $('#timelineContainer').removeClass('d-none');
        $('#noDataMessage').addClass('d-none');

        let timelineHtml = '<ul class="timeline">';

        attendances.forEach((attendance, index) => {
            const statusClass = getStatusClass(attendance.status);
            const statusBadge = getDetailedStatusBadge(attendance);

            timelineHtml += `
                <li class="timeline-item pb-4 timeline-item-${statusClass} border-left-dashed" id="attendance-${attendance.date}">
                    <span class="timeline-indicator-advanced timeline-indicator-${statusClass}">
                        <i class="bx bx-calendar"></i>
                    </span>
                    <div class="timeline-event">
                        <div class="timeline-header border-bottom mb-3 pb-2">
                            <h6 class="mb-1">${attendance.date_formatted}</h6>
                            ${statusBadge}
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-log-in-circle me-2 text-success"></i>
                                    <div>
                                        <small class="text-muted">${pageData.labels.checkIn}:</small>
                                        <div class="fw-semibold">${attendance.check_in_time || 'N/A'}</div>
                                        ${attendance.check_in_address !== 'N/A' ? `<small class="text-muted"><i class="bx bx-map-pin"></i> ${attendance.check_in_address}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-log-out-circle me-2 text-danger"></i>
                                    <div>
                                        <small class="text-muted">${pageData.labels.checkOut}:</small>
                                        <div class="fw-semibold">${attendance.check_out_time || 'N/A'}</div>
                                        ${attendance.check_out_address !== 'N/A' ? `<small class="text-muted"><i class="bx bx-map-pin"></i> ${attendance.check_out_address}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-3 col-6">
                                <small class="text-muted">${pageData.labels.workingHours}:</small>
                                <div class="badge bg-label-info">${formatHours(attendance.working_hours)}</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted">${pageData.labels.lateHours}:</small>
                                <div class="badge bg-label-warning">${formatHours(attendance.late_hours)}</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted">${pageData.labels.earlyHours}:</small>
                                <div class="badge bg-label-danger">${formatHours(attendance.early_hours)}</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted">${pageData.labels.overtimeHours}:</small>
                                <div class="badge bg-label-success">${formatHours(attendance.overtime_hours)}</div>
                            </div>
                        </div>
                        ${attendance.logs_count > 0 ? `
                            <div class="mt-3">
                                <button class="btn btn-sm btn-label-secondary" onclick="toggleLogs('${attendance.date}')">
                                    <i class="bx bx-list-ul me-1"></i> ${pageData.labels.logs} (${attendance.logs_count})
                                </button>
                                <div id="logs-${attendance.date}" class="mt-2 d-none">
                                    ${renderLogs(attendance.logs)}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </li>
            `;
        });

        timelineHtml += '</ul>';
        $('#timelineContainer').html(timelineHtml);
    }

    // Render logs
    function renderLogs(logs) {
        let logsHtml = '<ul class="list-unstyled mb-0">';
        logs.forEach(log => {
            const icon = log.type === 'check_in' ? 'bx-log-in-circle text-success' : 'bx-log-out-circle text-danger';
            logsHtml += `
                <li class="mb-2">
                    <i class="bx ${icon} me-2"></i>
                    <strong>${log.time}</strong> - ${log.type.replace('_', ' ').toUpperCase()}
                    ${log.address !== 'N/A' ? `<br><small class="ms-4 text-muted"><i class="bx bx-map-pin"></i> ${log.address}</small>` : ''}
                </li>
            `;
        });
        logsHtml += '</ul>';
        return logsHtml;
    }

    // Toggle logs visibility
    window.toggleLogs = function(date) {
        $(`#logs-${date}`).toggleClass('d-none');
    };

    // Scroll to specific date
    function scrollToDate(date) {
        const element = document.getElementById(`attendance-${date}`);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            $(element).addClass('highlight-animation');
            setTimeout(() => {
                $(element).removeClass('highlight-animation');
            }, 2000);
        }
    }

    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'present': 'success',
            'absent': 'danger',
            'late': 'warning',
            'half-day': 'info'
        };
        return classes[status] || 'secondary';
    }

    function getDetailedStatusBadge(attendance) {
        if (attendance.late_hours > 0) {
            return `<span class="badge bg-label-warning">Late (${formatHours(attendance.late_hours)})</span>`;
        } else if (attendance.status === 'present') {
            return '<span class="badge bg-label-success">Present</span>';
        } else if (attendance.status === 'absent') {
            return '<span class="badge bg-label-danger">Absent</span>';
        } else if (attendance.status === 'half-day') {
            return '<span class="badge bg-label-info">Half Day</span>';
        }
        return '<span class="badge bg-label-secondary">N/A</span>';
    }

    function formatHours(hours) {
        if (!hours || hours === 0) return '0h 0m';
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h ${m}m`;
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message
        });
    }

    // Add CSS for highlight animation
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .highlight-animation {
                animation: highlight 2s ease-in-out;
            }
            @keyframes highlight {
                0%, 100% { background-color: transparent; }
                50% { background-color: rgba(255, 193, 7, 0.2); }
            }
            .calendar-day:hover {
                background-color: rgba(0, 123, 255, 0.1);
                transform: scale(1.05);
                transition: all 0.2s;
            }
        `)
        .appendTo('head');
});
