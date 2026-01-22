$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize Flatpickr date pickers
  const startDatePicker = flatpickr('#start_date', {
    dateFormat: 'Y-m-d',
    onChange: function (selectedDates, dateStr, instance) {
      // Update end date minimum
      endDatePicker.set('minDate', dateStr);
    }
  });

  const endDatePicker = flatpickr('#end_date', {
    dateFormat: 'Y-m-d',
    minDate: $('#start_date').val()
  });

  // Initialize Select2
  $('#department_ids').select2({
    placeholder: pageData.labels.department,
    allowClear: true,
    width: '100%'
  });

  // Initialize DataTable
  let table = $('#departmentComparisonTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.start_date = $('#start_date').val();
        d.end_date = $('#end_date').val();
        d.department_ids = $('#department_ids').val();
      }
    },
    columns: [
      { data: 'ranking', name: 'ranking', orderable: false, searchable: false },
      { data: 'department', name: 'department' },
      { data: 'total_employees', name: 'total_employees' },
      { data: 'attendance_rate', name: 'attendance_rate' },
      { data: 'total_present_days', name: 'total_present_days' },
      { data: 'total_working_hours', name: 'total_working_hours' },
      { data: 'late_metrics', name: 'late_metrics', orderable: false },
      { data: 'overtime_metrics', name: 'overtime_metrics', orderable: false },
      { data: 'punctuality_score', name: 'punctuality_score' }
    ],
    order: [[3, 'desc']], // Default sort by attendance rate
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    responsive: true,
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
  });

  // Chart variables
  let attendanceRateChart = null;
  let workingHoursChart = null;

  // Load statistics and charts
  function loadStatistics() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const departmentIds = $('#department_ids').val();

    $.ajax({
      url: pageData.urls.stats,
      method: 'GET',
      data: {
        start_date: startDate,
        end_date: endDate,
        department_ids: departmentIds
      },
      success: function (response) {
        if (response.status === 'success') {
          updateSummaryCards(response.data);
          updateCharts(response.data);
          updateDateRangeDisplay(response.data.date_range);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error loading statistics:', error);
      }
    });
  }

  // Update summary cards
  function updateSummaryCards(data) {
    const overall = data.overall;

    if (overall.best_department) {
      $('#bestDepartmentRate').text(overall.best_department.attendance_rate + '%');
      $('#bestDepartmentName').text(overall.best_department.department_name);
    } else {
      $('#bestDepartmentRate').text('N/A');
      $('#bestDepartmentName').text(pageData.labels.noData);
    }

    if (overall.worst_department) {
      $('#worstDepartmentRate').text(overall.worst_department.attendance_rate + '%');
      $('#worstDepartmentName').text(overall.worst_department.department_name);
    } else {
      $('#worstDepartmentRate').text('N/A');
      $('#worstDepartmentName').text(pageData.labels.noData);
    }

    $('#avgAttendanceRate').text((overall.average_attendance_rate || 0) + '%');
    $('#totalWorkingHours').text(formatHours(overall.total_working_hours || 0));
  }

  // Update charts
  function updateCharts(data) {
    const departments = data.departments;

    if (departments.length === 0) {
      if (attendanceRateChart) {
        attendanceRateChart.destroy();
        attendanceRateChart = null;
      }
      if (workingHoursChart) {
        workingHoursChart.destroy();
        workingHoursChart = null;
      }
      return;
    }

    // Sort departments by attendance rate for better visualization
    const sortedDepts = [...departments].sort((a, b) => b.attendance_rate - a.attendance_rate);

    // Attendance Rate Bar Chart
    const attendanceRateOptions = {
      series: [
        {
          name: pageData.labels.attendanceRate,
          data: sortedDepts.map(d => d.attendance_rate)
        }
      ],
      chart: {
        type: 'bar',
        height: 350,
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          borderRadius: 4,
          dataLabels: {
            position: 'top'
          }
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + '%';
        },
        offsetY: -20,
        style: {
          fontSize: '12px',
          colors: ['#304758']
        }
      },
      xaxis: {
        categories: sortedDepts.map(d => d.department_name),
        labels: {
          style: {
            fontSize: '12px'
          }
        }
      },
      yaxis: {
        title: {
          text: pageData.labels.attendanceRate
        },
        min: 0,
        max: 100
      },
      colors: ['#696cff'],
      tooltip: {
        y: {
          formatter: function (val) {
            return val.toFixed(2) + '%';
          }
        }
      }
    };

    if (attendanceRateChart) {
      attendanceRateChart.destroy();
    }
    attendanceRateChart = new ApexCharts(
      document.querySelector('#attendanceRateChart'),
      attendanceRateOptions
    );
    attendanceRateChart.render();

    // Working Hours Pie Chart
    const workingHoursData = departments.map(d => d.total_working_hours);
    const totalWorkingHours = workingHoursData.reduce((sum, hours) => sum + hours, 0);

    // Clear any existing chart
    if (workingHoursChart) {
      workingHoursChart.destroy();
      workingHoursChart = null;
    }

    // Check if there's any working hours data to display
    if (totalWorkingHours === 0) {
      // Display a message when no working hours data is available
      const chartContainer = document.querySelector('#workingHoursChart');
      chartContainer.innerHTML = `
        <div class="text-center py-5">
          <i class="bx bx-time-five bx-lg text-muted mb-3"></i>
          <p class="text-muted">${pageData.labels.noData}</p>
        </div>
      `;
    } else {
      // Render the chart with data
      const workingHoursOptions = {
        series: workingHoursData,
        chart: {
          type: 'donut',
          height: 350
        },
        labels: departments.map(d => d.department_name),
        colors: ['#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff3e1d', '#03c3ec', '#233446', '#e7e7ff'],
        legend: {
          position: 'bottom',
          fontSize: '13px'
        },
        dataLabels: {
          enabled: true,
          formatter: function (val, opts) {
            return val.toFixed(1) + '%';
          }
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return formatHours(val);
            }
          }
        }
      };

      workingHoursChart = new ApexCharts(
        document.querySelector('#workingHoursChart'),
        workingHoursOptions
      );
      workingHoursChart.render();
    }
  }

  // Update date range display
  function updateDateRangeDisplay(dateRange) {
    $('#dateRangeDisplay').text(dateRange.start + ' - ' + dateRange.end);
  }

  // Format hours helper function
  function formatHours(hours) {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return h + 'h ' + m + 'm';
  }

  // Filter button click
  $('#filterBtn').on('click', function () {
    table.ajax.reload();
    loadStatistics();
  });

  // Load initial data
  loadStatistics();
});
