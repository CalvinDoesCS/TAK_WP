/**
 * Approval Pipeline Report
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
  let approvalPipelineTable;
  let statusDistributionChart, pendingByApproverChart;

  // Initialize date pickers
  const dateFromPicker = $('#dateFrom').flatpickr({
    dateFormat: 'Y-m-d',
    defaultDate: pageData.defaultDateFrom
  });

  const dateToPicker = $('#dateTo').flatpickr({
    dateFormat: 'Y-m-d',
    defaultDate: pageData.defaultDateTo
  });

  // Initialize Select2
  $('.select2').select2({
    placeholder: labels.selectApprover,
    allowClear: true
  });

  // Initialize DataTable
  function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#approvalPipelineTable')) {
      approvalPipelineTable.destroy();
    }

    approvalPipelineTable = $('#approvalPipelineTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: urls.datatable,
        type: 'GET',
        data: function (d) {
          d.date_from = $('#dateFrom').val();
          d.date_to = $('#dateTo').val();
          d.status = $('#statusFilter').val();
          d.aging = $('#agingFilter').val();
          d.approver_id = $('#approverFilter').val();
        }
      },
      columns: [
        { data: 'request_id', name: 'id' },
        { data: 'user', name: 'user_id', orderable: false },
        { data: 'expense_type', name: 'expense_type_id', orderable: false },
        { data: 'submitted_date', name: 'created_at' },
        { data: 'amount', name: 'amount' },
        { data: 'days_pending', name: 'days_pending' },
        { data: 'status', name: 'status' },
        { data: 'approver', name: 'approved_by_id', orderable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[3, 'desc']],
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
        date_from: $('#dateFrom').val(),
        date_to: $('#dateTo').val(),
        status: $('#statusFilter').val(),
        aging: $('#agingFilter').val(),
        approver_id: $('#approverFilter').val()
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
    $('#totalPending').text(data.total_pending || 0);
    $('#avgDaysPending').text(data.avg_days_pending || 0);
    $('#over7Days').text(data.over_7_days || 0);
    $('#approvalRate').text((data.approval_rate || 0) + '%');
  }

  // Update charts
  function updateCharts(data) {
    updateStatusDistributionChart(data.status_distribution);
    updatePendingByApproverChart(data.pending_by_approver);
  }

  // Status Distribution Chart (Donut)
  function updateStatusDistributionChart(data) {
    const statuses = data.map(item => item.status);
    const counts = data.map(item => item.count);

    const options = {
      chart: {
        type: 'donut',
        height: 300,
        toolbar: {
          show: false
        }
      },
      series: counts,
      labels: statuses,
      colors: ['#ff9f43', '#28c76f', '#ea5455', '#6c757d'],
      legend: {
        position: 'bottom',
        fontSize: '13px'
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
            size: '65%',
            labels: {
              show: true,
              name: {
                fontSize: '15px',
                fontWeight: 600
              },
              value: {
                fontSize: '22px',
                fontWeight: 600,
                formatter: function (val) {
                  return val;
                }
              },
              total: {
                show: true,
                label: labels.count,
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                }
              }
            }
          }
        }
      }
    };

    if (statusDistributionChart) {
      statusDistributionChart.destroy();
    }

    statusDistributionChart = new ApexCharts(
      document.querySelector('#statusDistributionChart'),
      options
    );
    statusDistributionChart.render();
  }

  // Pending by Approver Chart (Bar)
  function updatePendingByApproverChart(data) {
    const approvers = data.map(item => item.approver_name);
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
          name: labels.count,
          data: counts
        }
      ],
      xaxis: {
        categories: approvers,
        labels: {
          trim: true,
          style: {
            fontSize: '11px'
          }
        }
      },
      colors: ['#ff9f43'],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 4,
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
      },
      yaxis: {
        labels: {
          style: {
            fontSize: '11px'
          }
        }
      }
    };

    if (pendingByApproverChart) {
      pendingByApproverChart.destroy();
    }

    pendingByApproverChart = new ApexCharts(
      document.querySelector('#pendingByApproverChart'),
      options
    );
    pendingByApproverChart.render();
  }

  // View record function
  window.viewRecord = function (id) {
    window.location.href = urls.view + '?id=' + id;
  };

  // Filter button click
  $('#filterBtn').on('click', function () {
    approvalPipelineTable.ajax.reload();
    loadStatistics();
  });

  // Reset button click
  $('#resetBtn').on('click', function () {
    // Reset date range
    dateFromPicker.setDate(pageData.defaultDateFrom);
    dateToPicker.setDate(pageData.defaultDateTo);

    // Reset other filters
    $('#statusFilter').val('pending');
    $('#agingFilter').val('');
    $('#approverFilter').val('').trigger('change');

    // Reload data
    approvalPipelineTable.ajax.reload();
    loadStatistics();
  });

  // Initialize on page load
  initializeDataTable();
  loadStatistics();
});
