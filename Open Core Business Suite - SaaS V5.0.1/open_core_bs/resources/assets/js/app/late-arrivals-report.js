/**
 * Late Arrivals Report
 */

'use strict';

$(function () {
  // Setup CSRF token for AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize variables
  const urls = pageData.urls;
  const labels = pageData.labels;
  let lateArrivalsTable;
  let lateByDayChart, lateTrendChart, topLateEmployeesChart, lateByDepartmentChart;

  // Initialize date range picker
  const dateRangePicker = $('#dateRange').flatpickr({
    mode: 'range',
    dateFormat: 'Y-m-d',
    defaultDate: [pageData.defaultStartDate, pageData.defaultEndDate],
    onChange: function (selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        $('#startDate').val(moment(selectedDates[0]).format('YYYY-MM-DD'));
        $('#endDate').val(moment(selectedDates[1]).format('YYYY-MM-DD'));
      }
    }
  });

  // Set initial hidden date values
  $('#startDate').val(pageData.defaultStartDate);
  $('#endDate').val(pageData.defaultEndDate);

  // Initialize Select2
  $('.select2').select2({
    placeholder: labels.selectEmployee,
    allowClear: true
  });

  // Initialize DataTable
  function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#lateArrivalsTable')) {
      lateArrivalsTable.destroy();
    }

    lateArrivalsTable = $('#lateArrivalsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: urls.datatable,
        type: 'GET',
        data: function (d) {
          d.start_date = $('#startDate').val();
          d.end_date = $('#endDate').val();
          d.department_id = $('#departmentId').val();
          d.user_id = $('#userId').val();
          d.min_late_minutes = $('#minLateMinutes').val() || 0;
        }
      },
      columns: [
        { data: 'user', name: 'user_id', orderable: false },
        { data: 'department', name: 'department', orderable: false },
        { data: 'date', name: 'date' },
        { data: 'day_of_week', name: 'day_of_week', orderable: false },
        { data: 'shift', name: 'shift', orderable: false },
        { data: 'scheduled_time', name: 'scheduled_time', orderable: false },
        { data: 'check_in_time', name: 'check_in_time', orderable: false },
        { data: 'late_duration', name: 'late_hours' },
        { data: 'late_reason', name: 'late_reason', orderable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[2, 'desc']],
      language: {
        search: labels.search,
        processing: labels.processing,
        lengthMenu: labels.lengthMenu,
        info: labels.info,
        infoEmpty: labels.infoEmpty,
        emptyTable: labels.emptyTable,
        paginate: labels.paginate
      },
      drawCallback: function () {
        // Initialize tooltips after table draw
        $('[data-bs-toggle="tooltip"]').tooltip();
      }
    });
  }

  // Load statistics
  function loadStatistics() {
    $.ajax({
      url: urls.statistics,
      type: 'GET',
      data: {
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        department_id: $('#departmentId').val(),
        user_id: $('#userId').val(),
        min_late_minutes: $('#minLateMinutes').val() || 0
      },
      success: function (response) {
        if (response.status === 'success') {
          updateStatisticsCards(response.data);
          updateCharts(response.data);
        }
      },
      error: function (xhr) {
        console.error('Error loading statistics:', xhr);
      }
    });
  }

  // Update statistics cards
  function updateStatisticsCards(data) {
    $('#totalLateInstances').text(data.total_late_instances || 0);
    $('#avgLateMinutes').text(data.avg_late_minutes || 0);

    if (data.most_late_employee && data.most_late_employee.name) {
      $('#mostLateEmployee').html(
        data.most_late_employee.name +
          '<br><small class="text-muted">(' +
          data.most_late_employee.count +
          ' times)</small>'
      );
    } else {
      $('#mostLateEmployee').text(labels.noData);
    }
  }

  // Update charts
  function updateCharts(data) {
    updateLateByDayChart(data.late_by_day_of_week);
    updateLateTrendChart(data.trend_data);
    updateTopLateEmployeesChart(data.top_late_employees);
    updateLateByDepartmentChart(data.late_by_department);
  }

  // Late by Day of Week Chart
  function updateLateByDayChart(data) {
    const days = data.map(item => item.day);
    const counts = data.map(item => item.count);

    const options = {
      chart: {
        type: 'bar',
        height: 300,
        toolbar: {
          show: false
        }
      },
      series: [
        {
          name: labels.lateArrivals,
          data: counts
        }
      ],
      xaxis: {
        categories: days
      },
      colors: ['#ff9f43'],
      plotOptions: {
        bar: {
          borderRadius: 8,
          columnWidth: '60%'
        }
      },
      dataLabels: {
        enabled: false
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 0,
          right: 0,
          bottom: 0,
          left: 0
        }
      }
    };

    if (lateByDayChart) {
      lateByDayChart.destroy();
    }

    lateByDayChart = new ApexCharts(document.querySelector('#lateByDayChart'), options);
    lateByDayChart.render();
  }

  // Late Trend Chart
  function updateLateTrendChart(data) {
    const dates = data.map(item => item.date);
    const counts = data.map(item => item.count);

    const options = {
      chart: {
        type: 'area',
        height: 300,
        toolbar: {
          show: false
        },
        zoom: {
          enabled: false
        }
      },
      series: [
        {
          name: labels.lateArrivals,
          data: counts
        }
      ],
      xaxis: {
        categories: dates,
        type: 'datetime',
        labels: {
          format: 'dd MMM'
        }
      },
      colors: ['#ff6384'],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.7,
          opacityTo: 0.3,
          stops: [0, 90, 100]
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 2
      },
      grid: {
        borderColor: '#f1f1f1'
      }
    };

    if (lateTrendChart) {
      lateTrendChart.destroy();
    }

    lateTrendChart = new ApexCharts(document.querySelector('#lateTrendChart'), options);
    lateTrendChart.render();
  }

  // Top Late Employees Chart
  function updateTopLateEmployeesChart(data) {
    const names = data.map(item => item.user_name);
    const counts = data.map(item => item.late_count);

    const options = {
      chart: {
        type: 'bar',
        height: 350,
        toolbar: {
          show: false
        }
      },
      series: [
        {
          name: labels.count,
          data: counts
        }
      ],
      xaxis: {
        categories: names,
        labels: {
          trim: true,
          style: {
            fontSize: '11px'
          }
        }
      },
      colors: ['#ea5455'],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 4
        }
      },
      dataLabels: {
        enabled: false
      },
      grid: {
        borderColor: '#f1f1f1'
      }
    };

    if (topLateEmployeesChart) {
      topLateEmployeesChart.destroy();
    }

    topLateEmployeesChart = new ApexCharts(document.querySelector('#topLateEmployeesChart'), options);
    topLateEmployeesChart.render();
  }

  // Late by Department Chart
  function updateLateByDepartmentChart(data) {
    const departments = data.map(item => item.department_name);
    const counts = data.map(item => item.count);

    const options = {
      chart: {
        type: 'pie',
        height: 350,
        toolbar: {
          show: false
        }
      },
      series: counts,
      labels: departments,
      colors: ['#28c76f', '#00cfe8', '#ea5455', '#ff9f43', '#7367f0', '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff'],
      legend: {
        position: 'bottom',
        fontSize: '12px'
      },
      dataLabels: {
        enabled: true,
        formatter: function (val, opts) {
          return opts.w.config.series[opts.seriesIndex];
        }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '45%'
          }
        }
      }
    };

    if (lateByDepartmentChart) {
      lateByDepartmentChart.destroy();
    }

    lateByDepartmentChart = new ApexCharts(document.querySelector('#lateByDepartmentChart'), options);
    lateByDepartmentChart.render();
  }

  // Filter button click
  $('#filterBtn').on('click', function () {
    lateArrivalsTable.ajax.reload();
    loadStatistics();
  });

  // Reset button click
  $('#resetBtn').on('click', function () {
    // Reset date range
    dateRangePicker.setDate([pageData.defaultStartDate, pageData.defaultEndDate]);
    $('#startDate').val(pageData.defaultStartDate);
    $('#endDate').val(pageData.defaultEndDate);

    // Reset other filters
    $('#departmentId').val('').trigger('change');
    $('#userId').val('').trigger('change');
    $('#minLateMinutes').val(0);

    // Reload data
    lateArrivalsTable.ajax.reload();
    loadStatistics();
  });

  // Initialize on page load
  initializeDataTable();
  loadStatistics();
});
