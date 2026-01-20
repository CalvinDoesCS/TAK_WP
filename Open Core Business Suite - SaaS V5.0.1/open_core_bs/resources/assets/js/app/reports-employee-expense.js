$(function () {
  'use strict';

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
  initializeCharts();

  // Event listeners
  $('#dateFromFilter, #dateToFilter, #statusFilter, #departmentFilter').on('change', function () {
    reloadData();
  });

  $('#clearFilters').on('click', function () {
    resetFilters();
  });
});

// Chart instances
let topEmployeesChart;
let monthlyTrendChart;
let employeeExpenseDataTable;

/**
 * Initialize DataTable
 */
function initializeDataTable() {
  employeeExpenseDataTable = $('#employeeExpenseTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.date_from = $('#dateFromFilter').val();
        d.date_to = $('#dateToFilter').val();
        d.employee_id = $('#employeeFilter').val();
        d.department_id = $('#departmentFilter').val();
        d.status = $('#statusFilter').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'employee', name: 'employee', orderable: false, searchable: true },
      { data: 'total_submitted', name: 'total_submitted' },
      { data: 'total_approved', name: 'total_approved' },
      { data: 'total_requests', name: 'total_requests' },
      { data: 'approval_rate', name: 'approval_rate', orderable: false },
      { data: 'pending_count', name: 'pending_count' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[2, 'desc']], // Order by total_submitted DESC
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    dom:
      '<"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
      't' +
      '<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
  });
}

/**
 * Initialize Flatpickr date pickers
 */
function initializeDatePickers() {
  // Set default dates to current month
  const startOfMonth = moment().startOf('month').format('YYYY-MM-DD');
  const endOfMonth = moment().endOf('month').format('YYYY-MM-DD');

  if ($('#dateFromFilter').length) {
    const dateFromPicker = flatpickr('#dateFromFilter', {
      dateFormat: 'Y-m-d',
      allowInput: true,
      defaultDate: startOfMonth,
      maxDate: 'today'
    });
    // Ensure the value is set in the input
    $('#dateFromFilter').val(startOfMonth);
  }

  if ($('#dateToFilter').length) {
    const dateToPicker = flatpickr('#dateToFilter', {
      dateFormat: 'Y-m-d',
      allowInput: true,
      defaultDate: endOfMonth,
      maxDate: 'today'
    });
    // Ensure the value is set in the input
    $('#dateToFilter').val(endOfMonth);
  }
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
  // Employee Filter with AJAX
  if ($('#employeeFilter').length) {
    $('#employeeFilter').select2({
      placeholder: pageData.labels.selectEmployee,
      allowClear: true,
      width: '100%',
      ajax: {
        url: pageData.urls.employeeSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term,
            page: params.page || 1
          };
        },
        processResults: function (data) {
          return {
            results: data.data.map(function (employee) {
              return {
                id: employee.id,
                text: employee.first_name + ' ' + employee.last_name + ' (' + employee.employee_code + ')'
              };
            }),
            pagination: {
              more: data.current_page < data.last_page
            }
          };
        },
        cache: true
      }
    });

    // Reload data when employee is selected
    $('#employeeFilter').on('change', function () {
      reloadData();
    });
  }

  // Department Filter
  if ($('#departmentFilter').length) {
    $('#departmentFilter').select2({
      placeholder: pageData.labels.selectDepartment,
      allowClear: true,
      width: '100%'
    });
  }

  // Status Filter
  if ($('#statusFilter').length) {
    $('#statusFilter').select2({
      placeholder: pageData.labels.selectStatus,
      allowClear: true,
      width: '100%'
    });
  }
}

/**
 * Initialize ApexCharts
 */
function initializeCharts() {
  // Top Employees Bar Chart
  const topEmployeesOptions = {
    series: [{
      name: pageData.labels.amount,
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
      formatter: function (val) {
        return pageData.currencySymbol + ' ' + val.toFixed(2);
      },
      offsetX: -6,
      style: {
        fontSize: '12px',
        colors: ['#304758']
      }
    },
    xaxis: {
      categories: [],
      labels: {
        formatter: function (val) {
          return pageData.currencySymbol + ' ' + val.toFixed(0);
        }
      }
    },
    colors: ['#696cff'],
    tooltip: {
      y: {
        formatter: function (val) {
          return pageData.currencySymbol + ' ' + val.toFixed(2);
        }
      }
    }
  };

  topEmployeesChart = new ApexCharts(document.querySelector('#topEmployeesChart'), topEmployeesOptions);
  topEmployeesChart.render();

  // Monthly Trend Line Chart
  const monthlyTrendOptions = {
    series: [{
      name: pageData.labels.amount,
      data: []
    }],
    chart: {
      type: 'line',
      height: 350,
      toolbar: {
        show: false
      }
    },
    stroke: {
      curve: 'smooth',
      width: 3
    },
    xaxis: {
      categories: []
    },
    yaxis: {
      labels: {
        formatter: function (val) {
          return pageData.currencySymbol + ' ' + val.toFixed(0);
        }
      }
    },
    colors: ['#71dd37'],
    markers: {
      size: 5,
      hover: {
        size: 7
      }
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return pageData.currencySymbol + ' ' + val.toFixed(2);
        }
      }
    },
    grid: {
      borderColor: '#f1f1f1'
    }
  };

  monthlyTrendChart = new ApexCharts(document.querySelector('#monthlyTrendChart'), monthlyTrendOptions);
  monthlyTrendChart.render();
}

/**
 * Load and display statistics
 */
function loadStatistics() {
  const data = {
    date_from: $('#dateFromFilter').val(),
    date_to: $('#dateToFilter').val(),
    employee_id: $('#employeeFilter').val(),
    department_id: $('#departmentFilter').val(),
    status: $('#statusFilter').val()
  };

  $.ajax({
    url: pageData.urls.statistics,
    type: 'GET',
    data: data,
    success: function (response) {
      if (response.success) {
        // Update statistics cards
        $('#totalEmployees').text(response.data.total_employees || 0);
        $('#avgPerEmployee').text(pageData.currencySymbol + ' ' + (response.data.avg_per_employee || 0).toFixed(2));
        $('#pendingApprovals').text(response.data.total_pending_approvals || 0);
        $('#complianceRate').text((response.data.compliance_rate || 0).toFixed(1) + '%');

        // Update Top Employees Chart
        if (response.data.top_employees && response.data.top_employees.length > 0) {
          const employeeNames = response.data.top_employees.map(emp => emp.name);
          const employeeAmounts = response.data.top_employees.map(emp => emp.amount);

          topEmployeesChart.updateOptions({
            xaxis: {
              categories: employeeNames
            }
          });

          topEmployeesChart.updateSeries([{
            name: pageData.labels.amount,
            data: employeeAmounts
          }]);
        } else {
          topEmployeesChart.updateOptions({
            xaxis: {
              categories: []
            }
          });
          topEmployeesChart.updateSeries([{
            name: pageData.labels.amount,
            data: []
          }]);
        }

        // Update Monthly Trend Chart
        if (response.data.monthly_trend && response.data.monthly_trend.length > 0) {
          const months = response.data.monthly_trend.map(item => item.month);
          const amounts = response.data.monthly_trend.map(item => item.amount);

          monthlyTrendChart.updateOptions({
            xaxis: {
              categories: months
            }
          });

          monthlyTrendChart.updateSeries([{
            name: pageData.labels.amount,
            data: amounts
          }]);
        } else {
          monthlyTrendChart.updateOptions({
            xaxis: {
              categories: []
            }
          });
          monthlyTrendChart.updateSeries([{
            name: pageData.labels.amount,
            data: []
          }]);
        }
      }
    },
    error: function (xhr, status, error) {
      console.error('Failed to load statistics:', error);
      // Show error notification
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load statistics. Please try again.',
          confirmButtonText: 'OK'
        });
      }
    }
  });
}

/**
 * Reload data (DataTable and statistics)
 */
function reloadData() {
  // Reload DataTable
  if (employeeExpenseDataTable) {
    employeeExpenseDataTable.draw();
  }

  // Reload statistics
  loadStatistics();
}

/**
 * Reset filters to default values
 */
function resetFilters() {
  // Reset date pickers to current month
  const startOfMonth = moment().startOf('month').format('YYYY-MM-DD');
  const endOfMonth = moment().endOf('month').format('YYYY-MM-DD');

  $('#dateFromFilter').val(startOfMonth);
  $('#dateToFilter').val(endOfMonth);

  // Clear Select2 dropdowns
  $('#employeeFilter').val(null).trigger('change');
  $('#departmentFilter').val('').trigger('change');
  $('#statusFilter').val('').trigger('change');

  // Reload data
  reloadData();
}

/**
 * View employee details - redirects to expense list filtered by employee
 */
window.viewEmployeeDetails = function (employeeId) {
  const url = pageData.urls.expensesList + '?employeeFilter=' + employeeId;
  window.location.href = url;
};
