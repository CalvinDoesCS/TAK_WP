$(function () {
  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize components
  initializeDatePickers();
  initializeSelect2();
  initializeDataTable();
  loadStatistics();

  // Event listeners
  $('#applyFilterBtn').on('click', function () {
    overtimeTable.ajax.reload();
    loadStatistics();
  });

  $('#resetFilterBtn').on('click', function () {
    resetFilters();
  });
});

// DataTable instance
let overtimeTable;
let departmentChart, breakdownChart, trendChart;

/**
 * Initialize Flatpickr date pickers
 */
function initializeDatePickers() {
  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d',
    allowInput: true,
    onChange: function () {
      // Auto reload when date changes
      if (overtimeTable) {
        overtimeTable.ajax.reload();
        loadStatistics();
      }
    }
  });
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
  $('.select2').select2({
    placeholder: function () {
      return $(this).data('placeholder') || pageData.labels.selectEmployee;
    },
    allowClear: true,
    width: '100%'
  });
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
  overtimeTable = $('#overtimeTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.start_date = $('#startDate').val();
        d.end_date = $('#endDate').val();
        d.department_id = $('#departmentFilter').val();
        d.user_id = $('#userFilter').val();
        d.min_overtime_hours = $('#minOvertimeHours').val();
        d.day_type = $('#dayTypeFilter').val();
        d.approval_status = $('#approvalStatusFilter').val();
      }
    },
    columns: [
      { data: 'user', name: 'user.first_name', orderable: false },
      { data: 'department', name: 'user.designation.department.name' },
      { data: 'date', name: 'date' },
      { data: 'day_type', name: 'is_weekend', orderable: false },
      { data: 'shift_details', name: 'shift.name', orderable: false },
      { data: 'check_times', name: 'check_in_time', orderable: false },
      { data: 'working_hours', name: 'working_hours' },
      { data: 'overtime_hours', name: 'overtime_hours' },
      { data: 'approval_status', name: 'approved_at', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[2, 'desc']], // Sort by date descending
    pageLength: 25,
    responsive: true,
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
}

/**
 * Load and display statistics
 */
function loadStatistics() {
  const data = {
    start_date: $('#startDate').val(),
    end_date: $('#endDate').val(),
    department_id: $('#departmentFilter').val()
  };

  $.ajax({
    url: pageData.urls.statistics,
    method: 'GET',
    data: data,
    success: function (response) {
      if (response.status === 'success') {
        updateStatisticsCards(response.data);
        updateCharts(response.data);
      }
    },
    error: function () {
      Swal.fire({
        title: pageData.labels.error,
        icon: 'error',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
    }
  });
}

/**
 * Update statistics cards
 */
function updateStatisticsCards(data) {
  $('#totalOvertimeHours').text(data.total_overtime_hours.toFixed(2));
  $('#employeesWithOvertime').text(data.employees_with_overtime);
  $('#averageOvertime').text(data.average_overtime_per_employee.toFixed(2));
  $('#weekendOvertimeHours').text(data.weekend_overtime_hours.toFixed(2));
}

/**
 * Update all charts
 */
function updateCharts(data) {
  updateDepartmentChart(data.overtime_by_department);
  updateBreakdownChart(data);
  updateTrendChart(data.monthly_trend);
}

/**
 * Update overtime by department chart
 */
function updateDepartmentChart(departmentData) {
  const departments = departmentData.map(d => d.department_name);
  const overtimeHours = departmentData.map(d => parseFloat(d.total_overtime));

  const options = {
    series: [{
      name: pageData.labels.overtimeHours,
      data: overtimeHours
    }],
    chart: {
      type: 'bar',
      height: 300,
      toolbar: {
        show: false
      }
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 4,
        dataLabels: {
          position: 'top'
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(2) + ' hrs';
      },
      offsetX: -6,
      style: {
        fontSize: '12px',
        colors: ['#fff']
      }
    },
    xaxis: {
      categories: departments,
      title: {
        text: pageData.labels.hours
      }
    },
    yaxis: {
      title: {
        text: ''
      }
    },
    colors: ['#696cff'],
    grid: {
      borderColor: '#f1f1f1'
    }
  };

  if (departmentChart) {
    departmentChart.destroy();
  }

  departmentChart = new ApexCharts(
    document.querySelector('#overtimeByDepartmentChart'),
    options
  );
  departmentChart.render();
}

/**
 * Update overtime breakdown chart (pie/donut)
 */
function updateBreakdownChart(data) {
  const series = [
    data.weekday_overtime_hours,
    data.weekend_overtime_hours,
    data.holiday_overtime_hours
  ];

  const options = {
    series: series,
    chart: {
      type: 'donut',
      height: 300
    },
    labels: [pageData.labels.weekday, pageData.labels.weekend, pageData.labels.holiday],
    colors: ['#696cff', '#03c3ec', '#ffab00'],
    legend: {
      position: 'bottom'
    },
    dataLabels: {
      enabled: true,
      formatter: function (val, opts) {
        const value = opts.w.globals.series[opts.seriesIndex];
        return value.toFixed(2) + ' hrs';
      }
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: true,
            total: {
              show: true,
              label: pageData.labels.overtimeHours,
              formatter: function (w) {
                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                return total.toFixed(2) + ' hrs';
              }
            }
          }
        }
      }
    }
  };

  if (breakdownChart) {
    breakdownChart.destroy();
  }

  breakdownChart = new ApexCharts(
    document.querySelector('#overtimeBreakdownChart'),
    options
  );
  breakdownChart.render();
}

/**
 * Update monthly trend chart
 */
function updateTrendChart(trendData) {
  const months = trendData.map(d => d.month);
  const overtimeHours = trendData.map(d => parseFloat(d.total_overtime));
  const employeeCounts = trendData.map(d => parseInt(d.employee_count));

  const options = {
    series: [
      {
        name: pageData.labels.overtimeHours,
        type: 'column',
        data: overtimeHours
      },
      {
        name: pageData.labels.employees,
        type: 'line',
        data: employeeCounts
      }
    ],
    chart: {
      height: 350,
      type: 'line',
      toolbar: {
        show: false
      }
    },
    stroke: {
      width: [0, 4]
    },
    dataLabels: {
      enabled: true,
      enabledOnSeries: [1]
    },
    labels: months,
    xaxis: {
      type: 'category',
      title: {
        text: pageData.labels.month
      }
    },
    yaxis: [
      {
        title: {
          text: pageData.labels.overtimeHours
        }
      },
      {
        opposite: true,
        title: {
          text: pageData.labels.employees
        }
      }
    ],
    colors: ['#696cff', '#71dd37'],
    grid: {
      borderColor: '#f1f1f1'
    },
    legend: {
      position: 'top'
    }
  };

  if (trendChart) {
    trendChart.destroy();
  }

  trendChart = new ApexCharts(
    document.querySelector('#monthlyTrendChart'),
    options
  );
  trendChart.render();
}

/**
 * Reset filters to default values
 */
function resetFilters() {
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

  $('#startDate').val(formatDate(startOfMonth));
  $('#endDate').val(formatDate(endOfMonth));
  $('#departmentFilter').val('').trigger('change');
  $('#userFilter').val('').trigger('change');
  $('#minOvertimeHours').val('');
  $('#dayTypeFilter').val('');
  $('#approvalStatusFilter').val('');

  overtimeTable.ajax.reload();
  loadStatistics();
}

/**
 * Format date to YYYY-MM-DD
 */
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * Approve overtime for an attendance record
 */
window.approveOvertime = function (attendanceId) {
  Swal.fire({
    title: pageData.labels.confirmApprove,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.approved,
    cancelButtonText: pageData.labels.cancel,
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(result => {
    if (result.isConfirmed) {
      const url = pageData.urls.approveOvertime.replace(':id', attendanceId);

      $.ajax({
        url: url,
        method: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              title: pageData.labels.approveSuccess,
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });

            overtimeTable.ajax.reload();
            loadStatistics();
          }
        },
        error: function (xhr) {
          let errorMessage = pageData.labels.error;
          if (xhr.responseJSON && xhr.responseJSON.data) {
            errorMessage = xhr.responseJSON.data;
          }

          Swal.fire({
            title: errorMessage,
            icon: 'error',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      });
    }
  });
};
