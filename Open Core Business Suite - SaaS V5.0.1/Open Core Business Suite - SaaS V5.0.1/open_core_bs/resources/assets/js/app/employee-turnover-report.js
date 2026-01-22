$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  let turnoverTable;
  let monthlyTurnoverChart, departmentChart, typeChart, voluntaryChart;

  // Initialize Flatpickr for date inputs
  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d',
    allowInput: true
  });

  // Initialize Select2
  $('.select2').select2({
    placeholder: function () {
      return $(this).data('placeholder');
    },
    allowClear: true
  });

  // Initialize DataTable
  function initDataTable() {
    if (turnoverTable) {
      turnoverTable.destroy();
    }

    turnoverTable = $('#terminationsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        data: function (d) {
          d.start_date = $('#startDate').val();
          d.end_date = $('#endDate').val();
          d.department_id = $('#departmentFilter').val();
        }
      },
      columns: [
        { data: 'employee', name: 'employee', orderable: false, searchable: false },
        { data: 'department', name: 'department', orderable: false },
        { data: 'designation', name: 'designation', orderable: false },
        { data: 'event_date', name: 'event_date' },
        { data: 'termination_type', name: 'termination_type', orderable: false },
        { data: 'tenure', name: 'tenure', orderable: false },
        { data: 'exit_reason', name: 'exit_reason', orderable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[3, 'desc']],
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

  // Load statistics and update charts
  function loadStatistics() {
    const filters = {
      start_date: $('#startDate').val(),
      end_date: $('#endDate').val(),
      department_id: $('#departmentFilter').val()
    };

    $.ajax({
      url: pageData.urls.statistics,
      type: 'GET',
      data: filters,
      success: function (response) {
        if (response.status === 'success') {
          updateStatistics(response.data);
          updateCharts(response.data);
        }
      },
      error: function (xhr) {
        console.error('Error loading statistics:', xhr);
      }
    });
  }

  // Update statistics cards
  function updateStatistics(data) {
    $('#overallTurnoverRate').text(data.overall_turnover_rate.toFixed(2) + '%');
    $('#totalTerminations').text(data.total_terminations);
    $('#averageTenure').text(data.average_tenure_months.toFixed(1));
    $('#averageHeadcount').text(data.average_headcount);
  }

  // Update all charts
  function updateCharts(data) {
    updateMonthlyTurnoverChart(data.monthly_trend);
    updateDepartmentChart(data.turnover_by_department);
    updateTypeChart(data.turnover_by_type);
    updateVoluntaryChart(data.voluntary_count, data.involuntary_count);
  }

  // Monthly Turnover Trend Chart
  function updateMonthlyTurnoverChart(monthlyData) {
    const months = monthlyData.map(item => item.month);
    const turnoverRates = monthlyData.map(item => item.turnover_rate);
    const terminations = monthlyData.map(item => item.terminations);

    const options = {
      series: [{
        name: pageData.labels.turnoverRate,
        type: 'line',
        data: turnoverRates
      }, {
        name: pageData.labels.terminations,
        type: 'column',
        data: terminations
      }],
      chart: {
        height: 350,
        type: 'line',
        toolbar: {
          show: true
        }
      },
      stroke: {
        width: [3, 0]
      },
      dataLabels: {
        enabled: true,
        enabledOnSeries: [0]
      },
      labels: months,
      xaxis: {
        type: 'category'
      },
      yaxis: [{
        title: {
          text: pageData.labels.turnoverRate + ' (%)'
        }
      }, {
        opposite: true,
        title: {
          text: pageData.labels.terminations
        }
      }],
      colors: ['#ff4560', '#775dd0']
    };

    if (monthlyTurnoverChart) {
      monthlyTurnoverChart.destroy();
    }

    monthlyTurnoverChart = new ApexCharts(document.querySelector('#monthlyTurnoverChart'), options);
    monthlyTurnoverChart.render();
  }

  // Department Turnover Chart
  function updateDepartmentChart(departmentData) {
    const departments = departmentData.map(item => item.department_name);
    const counts = departmentData.map(item => item.termination_count);

    const options = {
      series: [{
        name: pageData.labels.terminations,
        data: counts
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
          borderRadius: 4
        }
      },
      dataLabels: {
        enabled: true
      },
      xaxis: {
        categories: departments
      },
      colors: ['#ff9f43']
    };

    if (departmentChart) {
      departmentChart.destroy();
    }

    departmentChart = new ApexCharts(document.querySelector('#departmentTurnoverChart'), options);
    departmentChart.render();
  }

  // Termination Type Chart
  function updateTypeChart(typeData) {
    const types = typeData.map(item => item.type);
    const counts = typeData.map(item => item.count);

    const options = {
      series: counts,
      chart: {
        type: 'donut',
        height: 350
      },
      labels: types,
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }],
      colors: ['#00d4bd', '#826bf8', '#2b9bf4', '#fdb528', '#fe6a49']
    };

    if (typeChart) {
      typeChart.destroy();
    }

    typeChart = new ApexCharts(document.querySelector('#terminationTypeChart'), options);
    typeChart.render();
  }

  // Voluntary vs Involuntary Chart
  function updateVoluntaryChart(voluntary, involuntary) {
    const options = {
      series: [voluntary, involuntary],
      chart: {
        type: 'pie',
        height: 300
      },
      labels: [pageData.labels.voluntary, pageData.labels.involuntary],
      colors: ['#28c76f', '#ea5455'],
      legend: {
        position: 'bottom'
      }
    };

    if (voluntaryChart) {
      voluntaryChart.destroy();
    }

    voluntaryChart = new ApexCharts(document.querySelector('#voluntaryInvoluntaryChart'), options);
    voluntaryChart.render();
  }

  // Apply filters button
  $('#applyFilterBtn').on('click', function () {
    loadStatistics();
    if (turnoverTable) {
      turnoverTable.ajax.reload();
    }
  });

  // Reset filters button
  $('#resetFilterBtn').on('click', function () {
    const today = new Date();
    const twelveMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 12, 1);
    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    const startDate = twelveMonthsAgo.toISOString().split('T')[0];
    const endDate = endOfMonth.toISOString().split('T')[0];

    $('#startDate').val(startDate);
    $('#endDate').val(endDate);
    $('#departmentFilter').val('').trigger('change');

    loadStatistics();
    if (turnoverTable) {
      turnoverTable.ajax.reload();
    }
  });

  // Initialize on page load
  initDataTable();
  loadStatistics();
});
