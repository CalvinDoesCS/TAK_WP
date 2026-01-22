/**
 * Leave Analytics Dashboard
 * Handles all charts, filters, and data loading for leave analytics
 */

$(function () {
  'use strict';

  // ========================================
  // CSRF Token Setup
  // ========================================
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // ========================================
  // Chart Instances (Global)
  // ========================================
  let monthlyTrendChart = null;
  let leaveTypeChart = null;
  let departmentChart = null;
  let statusChart = null;

  // ========================================
  // Initialize Plugins
  // ========================================

  /**
   * Initialize Date Range Picker
   */
  function initDateRangePicker() {
    const currentYear = pageData.currentYear;
    const startDate = `${currentYear}-01-01`;
    const endDate = `${currentYear}-12-31`;

    $('#dateRange').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      defaultDate: [startDate, endDate],
      maxDate: 'today',
      locale: {
        rangeSeparator: ' to '
      }
    });
  }

  /**
   * Initialize Select2 Dropdowns
   */
  function initSelect2() {
    // Department Filter
    if ($('#departmentFilter').length) {
      $('#departmentFilter').select2({
        placeholder: 'All Departments',
        allowClear: true,
        width: '100%'
      });
    }

    // Leave Type Filter
    if ($('#leaveTypeFilter').length) {
      $('#leaveTypeFilter').select2({
        placeholder: 'All Leave Types',
        allowClear: true,
        width: '100%'
      });
    }
  }

  // ========================================
  // Chart Initialization
  // ========================================

  /**
   * Initialize Monthly Trend Area Chart
   */
  function initMonthlyTrendChart() {
    const chartEl = document.querySelector('#monthlyTrendChart');
    if (!chartEl) return;

    const options = {
      series: [{
        name: pageData.labels.leaveRequests,
        data: Array(12).fill(0)
      }],
      chart: {
        type: 'area',
        height: 350,
        toolbar: {
          show: false
        },
        zoom: {
          enabled: false
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      colors: ['#696cff'],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.5,
          opacityTo: 0.1,
          stops: [0, 90, 100]
        }
      },
      xaxis: {
        categories: pageData.labels.months,
        labels: {
          style: {
            fontSize: '13px',
            colors: '#a1acb8'
          }
        }
      },
      yaxis: {
        title: {
          text: pageData.labels.requests,
          style: {
            fontSize: '13px',
            color: '#a1acb8'
          }
        },
        labels: {
          style: {
            fontSize: '13px',
            colors: '#a1acb8'
          }
        }
      },
      grid: {
        borderColor: '#eceef1',
        padding: {
          bottom: 10
        }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.requests;
          }
        }
      },
      noData: {
        text: pageData.labels.noData,
        style: {
          fontSize: '14px'
        }
      }
    };

    monthlyTrendChart = new ApexCharts(chartEl, options);
    monthlyTrendChart.render();
  }

  /**
   * Initialize Leave Type Donut Chart
   */
  function initLeaveTypeChart() {
    const chartEl = document.querySelector('#leaveTypeChart');
    if (!chartEl) return;

    const options = {
      series: [],
      chart: {
        type: 'donut',
        height: 300
      },
      labels: [],
      colors: ['#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff3e1d', '#03c3ec', '#e7e7e7'],
      plotOptions: {
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: true,
              name: {
                fontSize: '14px',
                offsetY: -5
              },
              value: {
                fontSize: '20px',
                fontWeight: 600,
                offsetY: 5,
                formatter: function (val) {
                  return val;
                }
              },
              total: {
                show: true,
                label: pageData.labels.total,
                fontSize: '14px',
                color: '#a1acb8',
                formatter: function (w) {
                  const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  return total;
                }
              }
            }
          }
        }
      },
      legend: {
        show: true,
        position: 'bottom',
        fontSize: '13px',
        labels: {
          colors: '#a1acb8'
        },
        markers: {
          width: 8,
          height: 8
        },
        itemMargin: {
          horizontal: 10,
          vertical: 5
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return Math.round(val) + '%';
        },
        style: {
          fontSize: '12px'
        }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.requests;
          }
        }
      },
      noData: {
        text: pageData.labels.noData,
        style: {
          fontSize: '14px'
        }
      }
    };

    leaveTypeChart = new ApexCharts(chartEl, options);
    leaveTypeChart.render();
  }

  /**
   * Initialize Department Horizontal Bar Chart
   */
  function initDepartmentChart() {
    const chartEl = document.querySelector('#departmentChart');
    if (!chartEl) return;

    const options = {
      series: [{
        name: pageData.labels.days,
        data: []
      }],
      chart: {
        type: 'bar',
        height: 350,
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
        offsetX: 30,
        style: {
          fontSize: '12px',
          colors: ['#304758']
        },
        formatter: function (val) {
          return val.toFixed(1) + ' ' + pageData.labels.days;
        }
      },
      colors: ['#ffab00'],
      xaxis: {
        categories: [],
        labels: {
          style: {
            fontSize: '13px',
            colors: '#a1acb8'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            fontSize: '13px',
            colors: '#a1acb8'
          }
        }
      },
      grid: {
        borderColor: '#eceef1'
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val.toFixed(2) + ' ' + pageData.labels.days;
          }
        }
      },
      noData: {
        text: pageData.labels.noData,
        style: {
          fontSize: '14px'
        }
      }
    };

    departmentChart = new ApexCharts(chartEl, options);
    departmentChart.render();
  }

  /**
   * Initialize Status Distribution Pie Chart
   */
  function initStatusChart() {
    const chartEl = document.querySelector('#statusChart');
    if (!chartEl) return;

    const options = {
      series: [],
      chart: {
        type: 'pie',
        height: 300
      },
      labels: [
        pageData.labels.approved,
        pageData.labels.pending,
        pageData.labels.rejected,
        pageData.labels.cancelled
      ],
      colors: ['#71dd37', '#ffab00', '#ff3e1d', '#8592a3'],
      legend: {
        show: true,
        position: 'bottom',
        fontSize: '13px',
        labels: {
          colors: '#a1acb8'
        },
        markers: {
          width: 8,
          height: 8
        },
        itemMargin: {
          horizontal: 10,
          vertical: 5
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val, opts) {
          const name = opts.w.globals.labels[opts.seriesIndex];
          return name + ': ' + Math.round(val) + '%';
        },
        style: {
          fontSize: '12px',
          colors: ['#fff']
        },
        dropShadow: {
          enabled: false
        }
      },
      plotOptions: {
        pie: {
          customScale: 1,
          expandOnClick: false
        }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.requests;
          }
        }
      },
      noData: {
        text: pageData.labels.noData,
        style: {
          fontSize: '14px'
        }
      }
    };

    statusChart = new ApexCharts(chartEl, options);
    statusChart.render();
  }

  // ========================================
  // Data Loading & Chart Updates
  // ========================================

  /**
   * Get Filter Values
   */
  function getFilters() {
    const dateRange = $('#dateRange').val();
    const dates = dateRange ? dateRange.split(' to ') : [];

    return {
      start_date: dates[0] || '',
      end_date: dates[1] || dates[0] || '',
      department_id: $('#departmentFilter').val() || '',
      leave_type_id: $('#leaveTypeFilter').val() || ''
    };
  }

  /**
   * Load Dashboard Data via AJAX
   */
  function loadDashboardData() {
    const filters = getFilters();

    // Show loading state
    showLoadingState();

    $.ajax({
      url: pageData.urls.dashboardData,
      type: 'GET',
      data: filters,
      success: function (response) {
        if (response.success) {
          updateStatistics(response.data.statistics);
          updateMonthlyTrendChart(response.data.monthly_trend);
          updateLeaveTypeChart(response.data.leave_type_distribution);
          updateDepartmentChart(response.data.department_utilization);
          updateStatusChart(response.data.status_distribution);
        } else {
          showError(response.message || pageData.labels.error);
        }
      },
      error: function (xhr) {
        console.error('Error loading dashboard data:', xhr);
        showError(pageData.labels.error);
      }
    });
  }

  /**
   * Show Loading State for Statistics
   */
  function showLoadingState() {
    $('#totalLeavesTaken').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#pendingApprovals').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#avgBalance').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#onLeaveToday').html('<span class="spinner-border spinner-border-sm"></span>');
  }

  /**
   * Update Statistics Cards
   */
  function updateStatistics(stats) {
    // Total Leaves Taken
    $('#totalLeavesTaken').text(stats.total_leaves_taken || 0);
    if (stats.total_leaves_change) {
      $('#totalLeavesChange').text(stats.total_leaves_change);
    }

    // Pending Approvals
    $('#pendingApprovals').text(stats.pending_approvals || 0);

    // Average Balance
    $('#avgBalance').text((stats.avg_balance || 0).toFixed(1));

    // On Leave Today
    $('#onLeaveToday').text(stats.on_leave_today || 0);
    if (stats.on_leave_today_percent) {
      $('#onLeaveTodayPercent').text(stats.on_leave_today_percent + '% ' + pageData.labels.employees);
    }
  }

  /**
   * Update Monthly Trend Chart
   */
  function updateMonthlyTrendChart(data) {
    if (!monthlyTrendChart || !data) return;

    const seriesData = Array(12).fill(0);

    if (data.counts && Array.isArray(data.counts)) {
      data.counts.forEach((count, index) => {
        if (index < 12) {
          seriesData[index] = count || 0;
        }
      });
    }

    monthlyTrendChart.updateSeries([{
      name: pageData.labels.leaveRequests,
      data: seriesData
    }]);
  }

  /**
   * Update Leave Type Donut Chart
   */
  function updateLeaveTypeChart(data) {
    if (!leaveTypeChart || !data) return;

    const labels = data.labels || [];
    const values = data.values || [];

    if (labels.length === 0 || values.length === 0) {
      leaveTypeChart.updateOptions({
        series: [],
        labels: []
      });
      return;
    }

    leaveTypeChart.updateOptions({
      labels: labels
    });
    leaveTypeChart.updateSeries(values);
  }

  /**
   * Update Department Bar Chart
   */
  function updateDepartmentChart(data) {
    if (!departmentChart || !data) return;

    const categories = data.labels || [];
    const values = data.values || [];

    if (categories.length === 0 || values.length === 0) {
      departmentChart.updateOptions({
        xaxis: { categories: [] }
      });
      departmentChart.updateSeries([{
        name: pageData.labels.days,
        data: []
      }]);
      return;
    }

    departmentChart.updateOptions({
      xaxis: {
        categories: categories
      }
    });

    departmentChart.updateSeries([{
      name: pageData.labels.days,
      data: values
    }]);
  }

  /**
   * Update Status Pie Chart
   */
  function updateStatusChart(data) {
    if (!statusChart || !data) return;

    const values = [
      data.approved || 0,
      data.pending || 0,
      data.rejected || 0,
      data.cancelled || 0
    ];

    statusChart.updateSeries(values);
  }

  /**
   * Show Error Message
   */
  function showError(message) {
    Swal.fire({
      icon: 'error',
      title: pageData.labels.error,
      text: message,
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  // ========================================
  // Event Handlers
  // ========================================

  /**
   * Apply Filters Button
   */
  $('#applyFilters').on('click', function () {
    loadDashboardData();
  });

  /**
   * Reset Filters Button
   */
  $('#resetFilters').on('click', function () {
    // Reset date range to current year
    const currentYear = pageData.currentYear;
    const startDate = `${currentYear}-01-01`;
    const endDate = `${currentYear}-12-31`;

    $('#dateRange')[0]._flatpickr.setDate([startDate, endDate]);

    // Reset dropdowns
    $('#departmentFilter').val('').trigger('change');
    $('#leaveTypeFilter').val('').trigger('change');

    // Reload data
    loadDashboardData();
  });

  // ========================================
  // Initialization
  // ========================================

  // Initialize plugins
  initDateRangePicker();
  initSelect2();

  // Initialize all charts
  initMonthlyTrendChart();
  initLeaveTypeChart();
  initDepartmentChart();
  initStatusChart();

  // Load initial data
  loadDashboardData();
});
