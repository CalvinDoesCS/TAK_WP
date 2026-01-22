/* Monthly Attendance Calendar */

'use strict';

$(function () {
  console.log('Monthly Attendance Calendar Initialized');

  let currentMonth = new Date().getMonth() + 1;
  let currentYear = new Date().getFullYear();
  let calendarData = null;

  // CSRF Token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Populate year dropdown (from 5 years ago to 2 years in future)
  const currentYearValue = new Date().getFullYear();
  for (let year = currentYearValue - 5; year <= currentYearValue + 2; year++) {
    const option = $('<option></option>').val(year).text(year);
    if (year === currentYear) {
      option.prop('selected', true);
    }
    $('#yearSelect').append(option);
  }

  // Set current month in dropdown
  $('#monthSelect').val(currentMonth);

  // Month/Year change handlers
  $('#monthSelect, #yearSelect').on('change', function () {
    currentMonth = parseInt($('#monthSelect').val());
    currentYear = parseInt($('#yearSelect').val());
    loadCalendarData();
  });

  // Initialize Select2 for department filter
  $('#departmentFilter').select2({
    placeholder: 'All Departments',
    allowClear: true
  });

  // Event Listeners
  $('#departmentFilter').on('change', function () {
    loadCalendarData();
  });

  let searchTimeout;
  $('#searchEmployee').on('keyup', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      loadCalendarData();
    }, 500);
  });

  $('#refreshBtn').on('click', function () {
    loadCalendarData();
  });

  // Load initial data
  loadCalendarData();

  /**
   * Load calendar data from server
   */
  function loadCalendarData() {
    const departmentId = $('#departmentFilter').val();
    const search = $('#searchEmployee').val();

    // Set loading message
    const monthName = new Date(currentYear, currentMonth - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    showLoading('Loading attendance data...', `Fetching records for ${monthName}`);

    $.ajax({
      url: '/hrcore/attendance/monthly-calendar/data',
      type: 'GET',
      data: {
        month: currentMonth,
        year: currentYear,
        department_id: departmentId,
        search: search
      },
      success: function (response) {
        if (response.status === 'success') {
          calendarData = response.data;
          showLoading('Rendering calendar...', 'Building employee grid');

          // Small delay to show rendering message
          setTimeout(function() {
            renderCalendar();
            updateMonthDisplay();
            hideLoading();
          }, 300);
        } else {
          hideLoading();
          showError('Failed to load calendar data');
        }
      },
      error: function (xhr) {
        console.error('Error loading calendar data:', xhr);
        hideLoading();
        showError('An error occurred while loading calendar data');
      }
    });
  }

  /**
   * Render the calendar table
   */
  function renderCalendar() {
    if (!calendarData || !calendarData.employees || calendarData.employees.length === 0) {
      $('#calendarTable').hide();
      $('#emptyState').show();
      return;
    }

    $('#calendarTable').show();
    $('#emptyState').hide();

    // Populate departments in filter dropdown
    if (calendarData.departments && $('#departmentFilter option').length === 1) {
      calendarData.departments.forEach(function (dept) {
        $('#departmentFilter').append(
          $('<option></option>')
            .val(dept.id)
            .text(dept.name + ' (' + dept.code + ')')
        );
      });
    }

    // Render header row (dates)
    renderCalendarHeader();

    // Render employee rows
    renderCalendarBody();

    // Scroll to today's date
    scrollToToday();
  }

  /**
   * Render calendar header (dates)
   */
  function renderCalendarHeader() {
    const headerRow = $('#calendarTable thead tr');

    // Clear existing date headers (keep employee header)
    headerRow.find('th:not(.employee-cell)').remove();

    // Add date headers
    if (calendarData.employees.length > 0 && calendarData.employees[0].days) {
      calendarData.employees[0].days.forEach(function (dayData) {
        const date = new Date(dayData.date);
        const dayNum = date.getDate();
        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });

        let headerClass = '';
        if (dayData.isWeekend) {
          headerClass = 'weekend-header';
        }
        if (dayData.isToday) {
          headerClass = 'today-header';
        }

        headerRow.append(
          `<th class="text-center ${headerClass}">
            <div style="font-size: 12px; font-weight: 600;">${dayNum}</div>
            <div style="font-size: 10px; opacity: 0.7;">${dayName}</div>
          </th>`
        );
      });
    }
  }

  /**
   * Render calendar body (employee rows)
   */
  function renderCalendarBody() {
    const tbody = $('#calendarBody');
    tbody.empty();

    calendarData.employees.forEach(function (employee) {
      const row = $('<tr></tr>');

      // Get initials from name
      const nameParts = employee.name.split(' ');
      const initials = nameParts.length >= 2
        ? (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase()
        : nameParts[0].substring(0, 2).toUpperCase();

      // Check if using default avatar
      const isDefaultAvatar = employee.photo_url.includes('avatars/1.png');

      // Build avatar HTML
      let avatarHtml;
      if (isDefaultAvatar) {
        avatarHtml = `<div class="employee-avatar-initials">${initials}</div>`;
      } else {
        avatarHtml = `<img src="${employee.photo_url}" alt="${employee.name}" class="employee-avatar" />`;
      }

      // Employee cell
      const employeeCell = $(`
        <td class="employee-cell">
          <div class="employee-info">
            ${avatarHtml}
            <div class="employee-details">
              <div class="employee-name">${employee.name}</div>
              <div class="employee-meta">
                ${employee.code} • ${employee.designation}
              </div>
              <div class="employee-meta" style="font-size: 10px; margin-top: 2px;">
                ${employee.department}
              </div>
            </div>
          </div>
        </td>
      `);
      row.append(employeeCell);

      // Day cells
      employee.days.forEach(function (dayData) {
        // Add real-time indicator class if applicable
        let badgeClass = dayData.statusClass;
        if (dayData.isRealtime) {
          badgeClass += ' badge-realtime';
        }

        // Add today-cell class for styling
        let cellClass = 'day-cell';
        if (dayData.isToday) {
          cellClass += ' today-cell';
        }

        const dayCell = $(`
          <td class="${cellClass}" data-employee-id="${employee.id}" data-date="${dayData.date}" data-attendance-id="${dayData.attendance_id || ''}"
              ${dayData.isRealtime ? 'title="Live data - will be finalized at 23:30"' : ''}>
            <span class="status-badge ${badgeClass}">${dayData.statusLabel}</span>
          </td>
        `);

        // Add click handler
        dayCell.on('click', function () {
          showAttendanceDetail(employee, dayData);
        });

        row.append(dayCell);
      });

      tbody.append(row);
    });
  }

  /**
   * Scroll calendar to show today's date
   */
  function scrollToToday() {
    // Use setTimeout to ensure DOM is fully rendered
    setTimeout(function() {
      const calendarContainer = $('.calendar-container');
      const todayHeader = $('#calendarTable thead th.today-header');

      if (todayHeader.length > 0) {
        // Get the position of today's column
        const todayPosition = todayHeader.position().left;
        const todayWidth = todayHeader.outerWidth();
        const containerWidth = calendarContainer.width();
        const employeeCellWidth = $('.employee-cell').outerWidth();

        // Calculate scroll position to center today's column
        // Account for the sticky employee column
        const scrollPosition = todayPosition - (containerWidth / 2) + (todayWidth / 2) + employeeCellWidth;

        // Scroll to today's date with smooth animation
        calendarContainer.animate({
          scrollLeft: Math.max(0, scrollPosition)
        }, 600, 'swing');
      }
    }, 100); // Small delay to ensure rendering is complete
  }

  /**
   * Show attendance detail offcanvas
   */
  function showAttendanceDetail(employee, dayData) {
    const date = new Date(dayData.date);
    const dateStr = date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

    // Add real-time badge class if applicable
    let statusBadgeClass = dayData.statusClass;
    if (dayData.isRealtime) {
      statusBadgeClass += ' badge-realtime';
    }

    let statusText = '';

    switch (dayData.status) {
      case 'present':
        statusText = 'Present';
        break;
      case 'absent':
        statusText = 'Absent';
        break;
      case 'late':
        statusText = 'Late';
        break;
      case 'early':
        statusText = 'Early Checkout';
        break;
      case 'half-day':
        statusText = 'Half Day';
        break;
      case 'weekend':
        statusText = 'Weekend';
        break;
      default:
        statusText = 'Not Marked';
    }

    // Show/hide real-time notification
    if (dayData.isRealtime) {
      $('#realtimeNotification').removeClass('d-none');
    } else {
      $('#realtimeNotification').addClass('d-none');
    }

    // Get initials for avatar
    const nameParts = employee.name.split(' ');
    const initials = nameParts.length >= 2
      ? (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase()
      : nameParts[0].substring(0, 2).toUpperCase();

    // Check if using default avatar
    const isDefaultAvatar = employee.photo_url.includes('avatars/1.png');

    // Build avatar HTML
    let avatarHtml;
    if (isDefaultAvatar) {
      avatarHtml = `<div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px;">${initials}</div>`;
    } else {
      avatarHtml = `<img src="${employee.photo_url}" alt="${employee.name}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;" />`;
    }

    let detailHtml = `
      <div class="mb-3">
        <div class="d-flex align-items-center gap-3 mb-3">
          ${avatarHtml}
          <div>
            <h6 class="mb-0">${employee.name}</h6>
            <small class="text-muted d-block">${employee.code} • ${employee.designation}</small>
            <small class="text-muted" style="font-size: 11px;">${employee.department}</small>
          </div>
        </div>
        <hr />
        <div class="mb-3">
          <strong>Date:</strong> ${dateStr}
        </div>
        <div class="mb-3">
          <strong>Status:</strong> <span class="badge ${statusBadgeClass}">${statusText}</span>
        </div>
    `;

    if (dayData.checkInTime || dayData.checkOutTime || dayData.workingHours) {
      detailHtml += `
        <hr />
        <div class="row">
          ${dayData.checkInTime ? `
            <div class="col-md-6 mb-2">
              <small class="text-muted d-block">Check In</small>
              <strong>${dayData.checkInTime}</strong>
            </div>
          ` : ''}
          ${dayData.checkOutTime ? `
            <div class="col-md-6 mb-2">
              <small class="text-muted d-block">Check Out</small>
              <strong>${dayData.checkOutTime}</strong>
            </div>
          ` : ''}
          ${dayData.workingHours ? `
            <div class="col-md-12 mb-2">
              <small class="text-muted d-block">Working Hours</small>
              <strong>${dayData.workingHours}</strong>
            </div>
          ` : ''}
        </div>
      `;
    }

    if (dayData.attendance_id) {
      detailHtml += `
        <hr />
        <div class="text-center">
          <a href="/hrcore/attendance/${dayData.attendance_id}/details" class="btn btn-sm btn-primary" target="_blank">
            <i class="bx bx-show me-1"></i> View Full Details
          </a>
        </div>
      `;
    }

    detailHtml += '</div>';

    $('#attendanceDetailContent').html(detailHtml);

    // Show offcanvas instead of modal
    const offcanvasElement = document.getElementById('attendanceDetailOffcanvas');
    const bsOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
    bsOffcanvas.show();
  }

  /**
   * Update month display
   */
  function updateMonthDisplay() {
    if (calendarData) {
      $('#currentMonthDisplay').text(calendarData.monthName);
    }
  }

  /**
   * Show loading overlay
   */
  function showLoading(message, subMessage) {
    if (message) {
      $('#loadingMessage').text(message);
    }
    if (subMessage) {
      $('#loadingSubMessage').text(subMessage);
    }
    $('#loadingOverlay').fadeIn(200);
  }

  /**
   * Hide loading overlay
   */
  function hideLoading() {
    $('#loadingOverlay').fadeOut(200);
  }

  /**
   * Show error message
   */
  function showError(message) {
    Swal.fire({
      title: 'Error',
      text: message,
      icon: 'error',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  /**
   * Recalculate button handler
   */
  $('#recalculateBtn').on('click', function() {
    const startDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    const lastDay = new Date(currentYear, currentMonth, 0).getDate();
    const endDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    const monthName = calendarData && calendarData.monthName ? calendarData.monthName : '';

    Swal.fire({
      title: 'Recalculate Attendance?',
      html: `This will recalculate attendance data for <strong>${monthName}</strong>.<br>This may take a few moments.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Recalculate',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-warning',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        recalculateAttendance(startDate, endDate);
      }
    });
  });

  /**
   * Recalculate attendance for date range
   */
  function recalculateAttendance(startDate, endDate) {
    showLoading('Recalculating attendance...', 'Processing data, please wait');

    $.ajax({
      url: '/hrcore/attendance/recalculate',
      type: 'POST',
      data: {
        start_date: startDate,
        end_date: endDate
      },
      success: function(response) {
        hideLoading();

        if (response.status === 'success') {
          Swal.fire({
            title: 'Success!',
            html: `
              ${response.message}<br><br>
              <strong>Processed:</strong> ${response.data.processed}<br>
              <strong>Absences Created:</strong> ${response.data.absents_created}
            `,
            icon: 'success',
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          }).then(() => {
            // Reload calendar to show updated data
            loadCalendarData();
          });
        } else {
          showError(response.message || 'Recalculation failed');
        }
      },
      error: function(xhr) {
        hideLoading();
        console.error('Recalculation error:', xhr);

        let errorMessage = 'Failed to recalculate attendance';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        showError(errorMessage);
      }
    });
  }
});
