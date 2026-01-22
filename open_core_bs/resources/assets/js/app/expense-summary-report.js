$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Global variables
  let donutChart, barChart;
  let dataTable;

  // Initialize Flatpickr date pickers
  const dateFrom = flatpickr('#dateFrom', {
    dateFormat: pageData.dateFormat,
    defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    onChange: function () {
      loadData();
    }
  });

  const dateTo = flatpickr('#dateTo', {
    dateFormat: pageData.dateFormat,
    defaultDate: new Date(),
    onChange: function () {
      loadData();
    }
  });

  // Initialize DataTable
  function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#expenseSummaryTable')) {
      $('#expenseSummaryTable').DataTable().destroy();
    }

    dataTable = $('#expenseSummaryTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.tableData,
        type: 'GET',
        data: function (d) {
          return $.extend({}, d, getFilterValues());
        }
      },
      columns: [
        { data: 'expense_type', name: 'expense_type' },
        { data: 'total_submitted', name: 'total_submitted' },
        { data: 'total_approved', name: 'total_approved' },
        { data: 'request_count', name: 'request_count' },
        { data: 'approval_rate', name: 'approval_rate' },
        { data: 'avg_amount', name: 'avg_amount' }
      ],
      order: [[0, 'asc']],
      language: {
        emptyTable: pageData.labels.noData,
        loadingRecords: pageData.labels.loading,
        processing: pageData.labels.loading
      },
      columnDefs: [
        {
          targets: [4], // Approval rate column
          orderable: true,
          render: function (data) {
            return data; // HTML already rendered from server
          }
        }
      ],
      drawCallback: function () {
        // Any post-draw operations
      }
    });
  }

  // Initialize Donut Chart
  function initializeDonutChart(labels, series) {
    const donutChartEl = document.querySelector('#donutChart');

    if (!donutChartEl) return;

    // Clear loading spinner
    donutChartEl.innerHTML = '';

    // Check if no data
    if (!series || series.length === 0) {
      donutChartEl.innerHTML = `<div class="text-center p-5"><p class="text-muted">${pageData.labels.noData}</p></div>`;
      return;
    }

    const donutChartConfig = {
      chart: {
        height: 350,
        type: 'donut'
      },
      labels: labels,
      series: series,
      colors: ['#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff3e1d', '#03c3ec', '#7367f0', '#ea5455'],
      stroke: {
        width: 0
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + '%';
        }
      },
      legend: {
        show: true,
        position: 'bottom',
        fontSize: '13px',
        markers: {
          width: 12,
          height: 12,
          radius: 12
        },
        itemMargin: {
          horizontal: 10,
          vertical: 5
        }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              value: {
                fontSize: '18px',
                fontWeight: 500,
                formatter: function (val) {
                  return pageData.currencySymbol + ' ' + parseFloat(val).toFixed(2);
                }
              },
              total: {
                show: true,
                fontSize: '13px',
                label: pageData.labels.submitted,
                formatter: function (w) {
                  const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  return pageData.currencySymbol + ' ' + total.toFixed(2);
                }
              }
            }
          }
        }
      },
      responsive: [
        {
          breakpoint: 992,
          options: {
            chart: {
              height: 380
            },
            legend: {
              position: 'bottom'
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            chart: {
              height: 320
            },
            legend: {
              show: false
            }
          }
        }
      ]
    };

    if (donutChart) {
      donutChart.destroy();
    }

    donutChart = new ApexCharts(donutChartEl, donutChartConfig);
    donutChart.render();
  }

  // Initialize Bar Chart
  function initializeBarChart(categories, submittedData, approvedData) {
    const barChartEl = document.querySelector('#barChart');

    if (!barChartEl) return;

    // Clear loading spinner
    barChartEl.innerHTML = '';

    // Check if no data
    if (!categories || categories.length === 0) {
      barChartEl.innerHTML = `<div class="text-center p-5"><p class="text-muted">${pageData.labels.noData}</p></div>`;
      return;
    }

    const barChartConfig = {
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
          endingShape: 'rounded',
          borderRadius: 8
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
      series: [
        {
          name: pageData.labels.submitted,
          data: submittedData
        },
        {
          name: pageData.labels.approved,
          data: approvedData
        }
      ],
      colors: ['#696cff', '#71dd37'],
      xaxis: {
        categories: categories,
        labels: {
          style: {
            fontSize: '12px'
          }
        }
      },
      yaxis: {
        title: {
          text: pageData.currencySymbol
        },
        labels: {
          formatter: function (val) {
            return pageData.currencySymbol + ' ' + val.toFixed(0);
          }
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 5
      },
      fill: {
        opacity: 1
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return pageData.currencySymbol + ' ' + val.toFixed(2);
          }
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'left',
        fontSize: '13px',
        markers: {
          width: 10,
          height: 10,
          radius: 10
        }
      },
      responsive: [
        {
          breakpoint: 600,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '70%'
              }
            },
            xaxis: {
              labels: {
                rotate: -45
              }
            }
          }
        }
      ]
    };

    if (barChart) {
      barChart.destroy();
    }

    barChart = new ApexCharts(barChartEl, barChartConfig);
    barChart.render();
  }

  // Load statistics
  function loadStatistics(data) {
    $('#totalSubmitted').text(data.total_submitted);
    $('#totalApproved').text(data.total_approved);
    $('#approvalRate').text(data.approval_rate);
    $('#totalRequests').text(data.total_requests);
  }

  // Load all data (statistics, charts, and table)
  function loadData() {
    const filters = getFilterValues();

    // Load statistics and charts
    $.ajax({
      url: pageData.urls.reportData,
      type: 'GET',
      data: filters,
      success: function (response) {
        if (response.success) {
          // Update statistics
          loadStatistics(response.statistics);

          // Update donut chart
          initializeDonutChart(response.chartData.donut.labels, response.chartData.donut.series);

          // Update bar chart
          initializeBarChart(
            response.chartData.bar.categories,
            response.chartData.bar.submitted,
            response.chartData.bar.approved
          );
        }
      },
      error: function (xhr) {
        console.error('Error loading report data:', xhr);
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: xhr.responseJSON?.message || pageData.labels.error,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      }
    });

    // Reload DataTable
    if (dataTable) {
      dataTable.ajax.reload();
    }
  }

  // Get current filter values
  function getFilterValues() {
    return {
      date_from: $('#dateFrom').val(),
      date_to: $('#dateTo').val(),
      status: $('#statusFilter').val(),
      expense_type_id: $('#expenseTypeFilter').val()
    };
  }

  // Event Handlers
  $('#statusFilter, #expenseTypeFilter').on('change', function () {
    loadData();
  });

  $('#applyFilters').on('click', function () {
    loadData();
  });

  $('#clearFilters').on('click', function () {
    // Reset to current month
    dateFrom.setDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
    dateTo.setDate(new Date());
    $('#statusFilter').val('all');
    $('#expenseTypeFilter').val('');

    // Reload data
    loadData();
  });

  // Initialize on page load
  $(document).ready(function () {
    initializeDataTable();
    loadData();
  });
});
