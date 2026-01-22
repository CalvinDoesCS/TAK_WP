/**
 * My Attendance Page
 */

'use strict';

$(document).ready(function() {
  // DataTable initialization
  let dt;
  const attendanceTable = $('#attendanceTable');
  if (attendanceTable.length) {
    dt = attendanceTable.DataTable({
      order: [[0, 'desc']],
      pageLength: 15,
      responsive: true,
      language: {
        paginate: {
          previous: '<i class="bx bx-chevron-left"></i>',
          next: '<i class="bx bx-chevron-right"></i>'
        }
      },
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    });

    // Date range filter
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
      const fp = flatpickr(dateFilter, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        onChange: function(selectedDates, dateStr) {
          if (selectedDates.length === 2) {
            filterByDateRange(selectedDates[0], selectedDates[1]);
          }
        }
      });
    }
  }

  // Filter by date range
  function filterByDateRange(startDate, endDate) {
    // Custom filtering logic for DataTable
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      const dateStr = data[0]; // Date column
      const date = new Date(dateStr);
      
      if (date >= startDate && date <= endDate) {
        return true;
      }
      return false;
    });
    
    // Redraw table
    dt.draw();
    
    // Remove custom filter after drawing
    $.fn.dataTable.ext.search.pop();
  }

  // Export attendance
  $('#exportAttendance').on('click', function() {
    if (!dt) return;
    
    // Get current filtered data
    const filteredData = dt.rows({ search: 'applied' }).data().toArray();
    
    // Convert to CSV
    let csv = 'Date,Day,Check In,Check Out,Total Hours,Overtime,Status\n';
    filteredData.forEach(row => {
      // Clean HTML from cells
      const cleanRow = row.map(cell => {
        const div = document.createElement('div');
        div.innerHTML = cell;
        return div.textContent || div.innerText || '';
      });
      csv += cleanRow.slice(0, 7).join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `attendance_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    // Show success message
    Swal.fire({
      icon: 'success',
      title: 'Export Successful',
      text: 'Your attendance data has been exported.',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  });

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // View attendance details function (make it global)
  window.viewAttendanceDetails = function(attendanceId) {
    const offcanvasElement = document.getElementById('viewDetailsOffcanvas');
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    const contentDiv = document.getElementById('attendanceDetailsContent');

    // Show loading spinner
    contentDiv.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    `;

    offcanvas.show();

    // Fetch attendance details
    fetch(`/hrcore/my/attendance/${attendanceId}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        displayAttendanceDetails(data.data);
      } else {
        contentDiv.innerHTML = `
          <div class="alert alert-danger">
            <i class="bx bx-error-circle me-2"></i>
            Failed to load attendance details.
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching attendance details:', error);
      contentDiv.innerHTML = `
        <div class="alert alert-danger">
          <i class="bx bx-error-circle me-2"></i>
          An error occurred while loading attendance details.
        </div>
      `;
    });
  };

  // Display attendance details
  function displayAttendanceDetails(attendance) {
    const contentDiv = document.getElementById('attendanceDetailsContent');

    // Format date
    const date = new Date(attendance.created_at);
    const dateStr = date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

    // Get check-in and check-out times from logs
    const checkInLog = attendance.attendance_logs?.find(log => log.type === 'check_in');
    const checkOutLog = attendance.attendance_logs?.filter(log => log.type === 'check_out').pop();

    const checkInTime = checkInLog ? new Date(checkInLog.created_at).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    }) : '--:--';

    const checkOutTime = checkOutLog ? new Date(checkOutLog.created_at).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    }) : '--:--';

    // Status badge color
    const statusColors = {
      'present': 'success',
      'absent': 'danger',
      'late': 'warning',
      'half_day': 'info',
      'holiday': 'secondary',
      'weekend': 'secondary'
    };

    const statusColor = statusColors[attendance.status] || 'primary';

    // Build logs table if there are multiple check-ins/outs
    let logsHtml = '';
    if (attendance.attendance_logs && attendance.attendance_logs.length > 0) {
      logsHtml = `
        <div class="mt-3">
          <h6 class="mb-2">Check-in/out Logs:</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Time</th>
                  <th>Location</th>
                </tr>
              </thead>
              <tbody>
      `;

      attendance.attendance_logs.forEach(log => {
        const logTime = new Date(log.created_at).toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: true
        });

        const logType = log.type === 'check_in' ? 'Check In' : 'Check Out';
        const logTypeColor = log.type === 'check_in' ? 'success' : 'danger';
        const location = log.location ? `${log.location.latitude}, ${log.location.longitude}` : 'N/A';

        logsHtml += `
          <tr>
            <td><span class="badge bg-label-${logTypeColor}">${logType}</span></td>
            <td>${logTime}</td>
            <td><small>${location}</small></td>
          </tr>
        `;
      });

      logsHtml += `
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    contentDiv.innerHTML = `
      <div class="mb-3">
        <h6 class="text-muted mb-1">Date</h6>
        <p class="mb-0"><strong>${dateStr}</strong></p>
      </div>

      <div class="row mb-3">
        <div class="col-6">
          <h6 class="text-muted mb-1">Check In</h6>
          <p class="mb-0">
            <span class="badge bg-label-success">${checkInTime}</span>
          </p>
        </div>
        <div class="col-6">
          <h6 class="text-muted mb-1">Check Out</h6>
          <p class="mb-0">
            <span class="badge bg-label-danger">${checkOutTime}</span>
          </p>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-6">
          <h6 class="text-muted mb-1">Total Hours</h6>
          <p class="mb-0"><strong>${attendance.total_hours || '0'} hrs</strong></p>
        </div>
        <div class="col-6">
          <h6 class="text-muted mb-1">Overtime</h6>
          <p class="mb-0">
            ${attendance.overtime_hours > 0 ? `<span class="badge bg-label-info">+${attendance.overtime_hours} hrs</span>` : '<span class="text-muted">--</span>'}
          </p>
        </div>
      </div>

      <div class="mb-3">
        <h6 class="text-muted mb-1">Status</h6>
        <p class="mb-0">
          <span class="badge bg-label-${statusColor}">${attendance.status.replace('_', ' ').toUpperCase()}</span>
        </p>
      </div>

      ${attendance.notes ? `
        <div class="mb-3">
          <h6 class="text-muted mb-1">Notes</h6>
          <p class="mb-0">${attendance.notes}</p>
        </div>
      ` : ''}

      ${logsHtml}
    `;
  }

  // Real-time clock for today's status
  function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit', 
      second: '2-digit' 
    });
    
    const clockElement = document.getElementById('currentTime');
    if (clockElement) {
      clockElement.textContent = timeString;
    }
  }

  // Update clock every second
  setInterval(updateClock, 1000);
  updateClock(); // Initial call

  // Check-in/out status checker
  function checkAttendanceStatus() {
    fetch('/hrcore/attendance/today-status', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        updateTodayStatus(data.data);
      }
    })
    .catch(error => {
      console.error('Error checking attendance status:', error);
    });
  }

  // Update today's status UI
  function updateTodayStatus(statusData) {
    // Update check-in time
    const checkInElement = document.querySelector('.check-in-time');
    if (checkInElement) {
      checkInElement.textContent = statusData.checkInTime || '--:--';
    }
    
    // Update check-out time
    const checkOutElement = document.querySelector('.check-out-time');
    if (checkOutElement) {
      checkOutElement.textContent = statusData.checkOutTime || '--:--';
    }
    
    // Update total hours
    const totalHoursElement = document.querySelector('.total-hours');
    if (totalHoursElement) {
      totalHoursElement.textContent = statusData.totalHours ? `${statusData.totalHours} hrs` : '--';
    }
  }

  // Check status every 5 minutes
  setInterval(checkAttendanceStatus, 5 * 60 * 1000);

  // Monthly statistics chart (if needed)
  const statsChartEl = document.getElementById('monthlyStatsChart');
  if (statsChartEl) {
    const statsChartOptions = {
      series: [{
        name: 'Present',
        data: [/* monthly data */]
      }, {
        name: 'Absent',
        data: [/* monthly data */]
      }, {
        name: 'Late',
        data: [/* monthly data */]
      }],
      chart: {
        type: 'bar',
        height: 200,
        stacked: true,
        toolbar: {
          show: false
        }
      },
      colors: ['#71dd37', '#ff3e1d', '#ffab00'],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '50%'
        }
      },
      xaxis: {
        categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4']
      },
      legend: {
        position: 'top'
      },
      fill: {
        opacity: 1
      }
    };

    const statsChart = new ApexCharts(statsChartEl, statsChartOptions);
    statsChart.render();
  }
});  // End of document ready