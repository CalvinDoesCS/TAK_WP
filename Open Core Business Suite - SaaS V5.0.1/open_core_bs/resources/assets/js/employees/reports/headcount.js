$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const urls = pageData.urls;
  const labels = pageData.labels;
  const locationEnabled = pageData.locationManagementEnabled;

  // Chart instances
  let headcountTrendChart;
  let employmentStatusChart;
  let departmentChart;
  let designationChart;
  let locationChart;

  // Load data on page load
  loadHeadcountData();

  /**
   * Load headcount data from server
   */
  function loadHeadcountData() {
    $.ajax({
      url: urls.headcountData,
      method: 'GET',
      success: function (response) {
        if (response.status === 'success') {
          updateStatistics(response.data);
          initializeCharts(response.data);
        } else {
          showError(response.message || labels.error);
        }
      },
      error: function (xhr) {
        showError(labels.error);
        console.error('Error loading headcount data:', xhr);
      }
    });
  }

  /**
   * Update statistics cards
   */
  function updateStatistics(data) {
    // Total active employees
    $('#totalActiveEmployees').text(formatNumber(data.total_active_employees));

    // Growth indicator
    const growthCount = data.growth_count || 0;
    const growthPercentage = data.growth_percentage || 0;
    const growthClass = growthCount >= 0 ? 'text-success' : 'text-danger';
    const growthIcon = growthCount >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';

    $('#growthIndicator').html(
      `<i class="bx ${growthIcon}"></i> ` +
        `<span class="${growthClass}">${growthCount >= 0 ? '+' : ''}${growthCount} (${growthPercentage}%)</span> ` +
        labels.growthThisMonth
    );

    // Departments count
    $('#totalDepartments').text(formatNumber(data.by_department.length));

    // Designations count
    $('#totalDesignations').text(formatNumber(data.by_designation.length));

    // Locations count or average per department
    if (locationEnabled) {
      $('#totalLocations').text(formatNumber(data.by_location.length));
    } else {
      const avgPerDept =
        data.by_department.length > 0
          ? Math.round(data.total_active_employees / data.by_department.length)
          : 0;
      $('#avgPerDepartment').text(formatNumber(avgPerDept));
    }
  }

  /**
   * Initialize all charts
   */
  function initializeCharts(data) {
    initHeadcountTrendChart(data.headcount_trend);
    initEmploymentStatusChart(data.by_employment_status);
    initDepartmentChart(data.by_department);
    initDesignationChart(data.by_designation);

    if (locationEnabled && data.by_location.length > 0) {
      initLocationChart(data.by_location);
    }
  }

  /**
   * Initialize headcount trend line chart
   */
  function initHeadcountTrendChart(trendData) {
    const chartElement = document.querySelector('#headcountTrendChart');
    if (!chartElement) return;

    const months = trendData.map(item => item.month_name);
    const counts = trendData.map(item => item.count);

    const options = {
      series: [
        {
          name: labels.headcount,
          data: counts
        }
      ],
      chart: {
        height: 300,
        type: 'line',
        toolbar: {
          show: false
        },
        zoom: {
          enabled: false
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      colors: ['#696cff'],
      dataLabels: {
        enabled: false
      },
      markers: {
        size: 5,
        colors: ['#fff'],
        strokeColors: '#696cff',
        strokeWidth: 2,
        hover: {
          size: 7
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 0,
          right: 10,
          bottom: 0,
          left: 10
        }
      },
      xaxis: {
        categories: months,
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          },
          formatter: function (val) {
            return Math.round(val);
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return formatNumber(val) + ' ' + labels.employees;
          }
        }
      }
    };

    if (headcountTrendChart) {
      headcountTrendChart.destroy();
    }

    headcountTrendChart = new ApexCharts(chartElement, options);
    headcountTrendChart.render();
  }

  /**
   * Initialize employment status pie chart
   */
  function initEmploymentStatusChart(statusData) {
    const chartElement = document.querySelector('#employmentStatusChart');
    if (!chartElement) return;

    const labels = statusData.map(item => item.employment_status);
    const series = statusData.map(item => item.count);

    const options = {
      series: series,
      labels: labels,
      chart: {
        height: 300,
        type: 'donut'
      },
      colors: ['#71dd37', '#696cff', '#03c3ec'],
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              value: {
                fontSize: '1.5rem',
                fontWeight: 500,
                color: '#697a8d',
                formatter: function (val) {
                  return formatNumber(val);
                }
              },
              name: {
                fontSize: '1rem',
                color: '#a1acb8'
              },
              total: {
                show: true,
                fontSize: '1rem',
                label: pageData.labels.employees,
                formatter: function (w) {
                  return formatNumber(
                    w.globals.seriesTotals.reduce((a, b) => {
                      return a + b;
                    }, 0)
                  );
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
          colors: '#697a8d'
        }
      },
      dataLabels: {
        enabled: false
      }
    };

    if (employmentStatusChart) {
      employmentStatusChart.destroy();
    }

    employmentStatusChart = new ApexCharts(chartElement, options);
    employmentStatusChart.render();
  }

  /**
   * Initialize department bar chart
   */
  function initDepartmentChart(departmentData) {
    const chartElement = document.querySelector('#departmentChart');
    if (!chartElement) return;

    const departments = departmentData.map(item => item.department_name);
    const counts = departmentData.map(item => item.count);

    const options = {
      series: [
        {
          name: labels.employees,
          data: counts
        }
      ],
      chart: {
        height: 350,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          borderRadius: 8,
          horizontal: true,
          barHeight: '60%',
          distributed: false
        }
      },
      colors: ['#ffab00'],
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px',
          colors: ['#fff']
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 0,
          right: 0,
          bottom: 0,
          left: 10
        }
      },
      xaxis: {
        categories: departments,
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return formatNumber(val) + ' ' + labels.employees;
          }
        }
      }
    };

    if (departmentChart) {
      departmentChart.destroy();
    }

    departmentChart = new ApexCharts(chartElement, options);
    departmentChart.render();
  }

  /**
   * Initialize designation bar chart (top 10)
   */
  function initDesignationChart(designationData) {
    const chartElement = document.querySelector('#designationChart');
    if (!chartElement) return;

    // Take top 10 designations
    const top10 = designationData.slice(0, 10);
    const designations = top10.map(item => item.designation_name);
    const counts = top10.map(item => item.count);

    const options = {
      series: [
        {
          name: labels.employees,
          data: counts
        }
      ],
      chart: {
        height: 350,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          borderRadius: 8,
          horizontal: true,
          barHeight: '60%'
        }
      },
      colors: ['#03c3ec'],
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px',
          colors: ['#fff']
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 0,
          right: 0,
          bottom: 0,
          left: 10
        }
      },
      xaxis: {
        categories: designations,
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return formatNumber(val) + ' ' + labels.employees;
          }
        }
      }
    };

    if (designationChart) {
      designationChart.destroy();
    }

    designationChart = new ApexCharts(chartElement, options);
    designationChart.render();
  }

  /**
   * Initialize location area chart
   */
  function initLocationChart(locationData) {
    const chartElement = document.querySelector('#locationChart');
    if (!chartElement) return;

    const locations = locationData.map(item => item.location_name);
    const counts = locationData.map(item => item.count);

    const options = {
      series: [
        {
          name: labels.employees,
          data: counts
        }
      ],
      chart: {
        height: 300,
        type: 'area',
        toolbar: {
          show: false
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      colors: ['#ff3e1d'],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.7,
          opacityTo: 0.3
        }
      },
      dataLabels: {
        enabled: false
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 0,
          right: 10,
          bottom: 0,
          left: 10
        }
      },
      xaxis: {
        categories: locations,
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#a1acb8',
            fontSize: '13px'
          },
          formatter: function (val) {
            return Math.round(val);
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return formatNumber(val) + ' ' + labels.employees;
          }
        }
      }
    };

    if (locationChart) {
      locationChart.destroy();
    }

    locationChart = new ApexCharts(chartElement, options);
    locationChart.render();
  }

  /**
   * Format number with commas
   */
  function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  /**
   * Show error message
   */
  function showError(message) {
    Swal.fire({
      icon: 'error',
      title: labels.error,
      text: message,
      customClass: {
        confirmButton: 'btn btn-primary'
      }
    });
  }
});
