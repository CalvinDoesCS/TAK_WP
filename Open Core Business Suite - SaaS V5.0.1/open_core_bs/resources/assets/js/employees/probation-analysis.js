$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize Select2
  $('.select2').select2();

  // ApexCharts instances
  let monthlyProbationChart, outcomeChart, departmentChart;

  // Load initial data
  loadProbationAnalysisData();

  // Apply Filters
  $('#applyFilters').on('click', function () {
    loadProbationAnalysisData();
  });

  // Reset Filters
  $('#resetFilters').on('click', function () {
    $('#departmentFilter').val('').trigger('change');
    $('#yearFilter').val(pageData.currentYear);
    loadProbationAnalysisData();
  });

  // Load Probation Analysis Data
  function loadProbationAnalysisData() {
    const filters = {
      department_id: $('#departmentFilter').val(),
      year: $('#yearFilter').val() || pageData.currentYear
    };

    $.ajax({
      url: pageData.urls.analysisData,
      method: 'GET',
      data: filters,
      success: function (response) {
        if (response.success) {
          updateStatistics(response.data.statistics);
          renderMonthlyChart(response.data.monthly_data);
          renderOutcomeChart(response.data.outcome_distribution);
          renderDepartmentChart(response.data.department_data);
          initializeDataTables();
        }
      },
      error: function () {
        showToast('error', pageData.labels.error);
      }
    });
  }

  // Update Statistics Cards
  function updateStatistics(stats) {
    $('#currentOnProbation').text(stats.current_on_probation);
    $('#successRate').text(stats.success_rate.toFixed(1));
    $('#failureRate').text(stats.failure_rate.toFixed(1));
    $('#avgDuration').text(stats.average_duration_days);
  }

  // Render Monthly Probation Chart
  function renderMonthlyChart(data) {
    const months = data.map(item => item.month);
    const confirmed = data.map(item => item.confirmed);
    const failed = data.map(item => item.failed);
    const extended = data.map(item => item.extended);

    const options = {
      series: [
        { name: pageData.labels.confirmed, data: confirmed },
        { name: pageData.labels.failed, data: failed },
        { name: pageData.labels.extended, data: extended }
      ],
      chart: {
        type: 'bar',
        height: 350,
        stacked: false,
        toolbar: { show: true }
      },
      colors: ['#28c76f', '#ea5455', '#ff9f43'],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          borderRadius: 5
        }
      },
      dataLabels: { enabled: false },
      stroke: { show: true, width: 2, colors: ['transparent'] },
      xaxis: {
        categories: months,
        labels: { style: { fontSize: '13px' } }
      },
      yaxis: { title: { text: pageData.labels.employees } },
      fill: { opacity: 1 },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.employees;
          }
        }
      },
      legend: { position: 'top', horizontalAlign: 'left' }
    };

    if (monthlyProbationChart) {
      monthlyProbationChart.destroy();
    }

    monthlyProbationChart = new ApexCharts(
      document.querySelector('#monthlyProbationChart'),
      options
    );
    monthlyProbationChart.render();
  }

  // Render Outcome Distribution Chart
  function renderOutcomeChart(data) {
    const labels = data.map(item => item.label);
    const values = data.map(item => item.value);

    const options = {
      series: values,
      chart: {
        type: 'donut',
        height: 300
      },
      labels: labels,
      colors: ['#28c76f', '#ea5455', '#ff9f43', '#00cfe8'],
      legend: { position: 'bottom' },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              name: { show: true },
              value: { show: true },
              total: {
                show: true,
                label: 'Total',
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                }
              }
            }
          }
        }
      },
      dataLabels: { enabled: false },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.employees;
          }
        }
      }
    };

    if (outcomeChart) {
      outcomeChart.destroy();
    }

    outcomeChart = new ApexCharts(document.querySelector('#outcomeChart'), options);
    outcomeChart.render();
  }

  // Render Department Chart
  function renderDepartmentChart(data) {
    const departments = data.map(item => item.department);
    const confirmed = data.map(item => item.confirmed);
    const failed = data.map(item => item.failed);
    const extended = data.map(item => item.extended);

    const options = {
      series: [
        { name: pageData.labels.confirmed, data: confirmed },
        { name: pageData.labels.failed, data: failed },
        { name: pageData.labels.extended, data: extended }
      ],
      chart: {
        type: 'bar',
        height: 350,
        stacked: true,
        toolbar: { show: true }
      },
      colors: ['#28c76f', '#ea5455', '#ff9f43'],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 5
        }
      },
      dataLabels: { enabled: false },
      stroke: { show: true, width: 2, colors: ['transparent'] },
      xaxis: {
        categories: departments,
        labels: { style: { fontSize: '13px' } }
      },
      yaxis: { title: { text: '' } },
      fill: { opacity: 1 },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + ' ' + pageData.labels.employees;
          }
        }
      },
      legend: { position: 'top', horizontalAlign: 'left' }
    };

    if (departmentChart) {
      departmentChart.destroy();
    }

    departmentChart = new ApexCharts(
      document.querySelector('#departmentChart'),
      options
    );
    departmentChart.render();
  }

  // Initialize DataTables
  function initializeDataTables() {
    // Current Probation Table
    if ($.fn.DataTable.isDataTable('#currentProbationTable')) {
      $('#currentProbationTable').DataTable().destroy();
    }

    $('#currentProbationTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.currentProbationData,
      columns: [
        { data: 'employee', name: 'employee', orderable: false, searchable: true },
        { data: 'department', name: 'department' },
        { data: 'probation_start', name: 'probation_start' },
        { data: 'probation_end', name: 'probation_end' },
        { data: 'days_remaining', name: 'days_remaining' },
        { data: 'is_extended', name: 'is_extended' }
      ],
      order: [[3, 'asc']],
      language: {
        processing: pageData.labels.loading,
        emptyTable: pageData.labels.noData
      }
    });

    // Upcoming Probation Table
    if ($.fn.DataTable.isDataTable('#upcomingProbationTable')) {
      $('#upcomingProbationTable').DataTable().destroy();
    }

    $('#upcomingProbationTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.upcomingProbationData,
      columns: [
        { data: 'employee', name: 'employee', orderable: false, searchable: true },
        { data: 'department', name: 'department' },
        { data: 'end_date', name: 'end_date' },
        { data: 'days_left', name: 'days_left' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[2, 'asc']],
      language: {
        processing: pageData.labels.loading,
        emptyTable: pageData.labels.noData
      }
    });
  }

  // Helper function for toast notifications
  function showToast(type, message) {
    // Implement toast notification based on your project's toast library
    console.log(type + ': ' + message);
  }
});

// Global functions for onclick handlers
window.viewEmployee = function(userId) {
  const url = pageData.urls.employeeShow.replace(':id', userId);
  window.location.href = url;
};
