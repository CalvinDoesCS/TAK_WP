$(function () {
  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize components
  initializeDataTable();
  loadChartData();

  // Event listeners
  $('#applyFilters').on('click', function () {
    departmentTable.ajax.reload();
    loadChartData();
  });

  $('#resetFilters').on('click', function () {
    resetFilters();
  });
});

// DataTable and Chart instances
let departmentTable;
let departmentChart = null;

/**
 * Initialize DataTable
 */
function initializeDataTable() {
  departmentTable = $('#departmentTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.year = $('#yearFilter').val();
        d.department_id = $('#departmentFilter').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'department', name: 'department' },
      { data: 'total_employees', name: 'total_employees' },
      { data: 'total_leaves_taken', name: 'total_leaves_taken' },
      { data: 'average_per_employee', name: 'average_per_employee' },
      { data: 'utilization_rate', name: 'utilization_rate', orderable: false },
      { data: 'pending_requests', name: 'pending_requests' }
    ],
    order: [[3, 'desc']], // Sort by total leaves taken descending
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
 * Load chart data and render chart
 */
function loadChartData() {
  const data = {
    year: $('#yearFilter').val(),
    department_id: $('#departmentFilter').val()
  };

  $.ajax({
    url: pageData.urls.chartData,
    type: 'GET',
    data: data,
    success: function (response) {
      if (response.success) {
        renderChart(response.data);
      }
    },
    error: function () {
      console.error('Failed to load chart data');
    }
  });
}

/**
 * Render ApexCharts chart
 */
function renderChart(data) {
  const chartConfig = {
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
        endingShape: 'rounded'
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
        name: 'Total Leaves Taken',
        data: data.leaves_taken || []
      },
      {
        name: 'Average per Employee',
        data: data.average_per_employee || []
      }
    ],
    xaxis: {
      categories: data.departments || []
    },
    yaxis: {
      title: {
        text: 'Days'
      }
    },
    fill: {
      opacity: 1
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val.toFixed(1) + ' days';
        }
      }
    },
    colors: ['#696cff', '#71dd37']
  };

  if (departmentChart) {
    departmentChart.destroy();
  }

  departmentChart = new ApexCharts(
    document.querySelector('#departmentComparisonChart'),
    chartConfig
  );

  departmentChart.render();
}

/**
 * Reset filters to default values
 */
function resetFilters() {
  const currentYear = new Date().getFullYear();

  $('#yearFilter').val(currentYear);
  $('#departmentFilter').val('');

  departmentTable.ajax.reload();
  loadChartData();
}
