$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const urls = pageData.urls;
  const labels = pageData.labels;

  // Chart instances
  let ageDistributionChart;
  let genderChart;
  let tenureChart;
  let probationStatusChart;

  // Load data on page load
  loadDemographicsData();

  /**
   * Load demographics data from server
   */
  function loadDemographicsData() {
    $.ajax({
      url: urls.demographicsData,
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
        console.error('Error loading demographics data:', xhr);
      }
    });
  }

  /**
   * Update statistics cards
   */
  function updateStatistics(data) {
    // Total employees
    $('#totalEmployees').text(formatNumber(data.total_active_employees));

    // Average age
    $('#averageAge').text(data.average_age);

    // Average tenure
    $('#averageTenure').text(data.average_tenure);

    // Profile completion rate
    $('#profileCompletionRate').text(data.profile_completion_rate);
  }

  /**
   * Initialize all charts
   */
  function initializeCharts(data) {
    initAgeDistributionChart(data.age_groups);
    initGenderChart(data.gender_distribution);
    initTenureChart(data.tenure_distribution);
    initProbationStatusChart(data.probation_status);
  }

  /**
   * Initialize age distribution bar chart
   */
  function initAgeDistributionChart(ageData) {
    const chartElement = document.querySelector('#ageDistributionChart');
    if (!chartElement) return;

    const ageGroups = ageData.map(item => item.age_group);
    const counts = ageData.map(item => item.count);

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
          columnWidth: '60%',
          distributed: false
        }
      },
      colors: ['#696cff'],
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
        categories: ageGroups,
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

    if (ageDistributionChart) {
      ageDistributionChart.destroy();
    }

    ageDistributionChart = new ApexCharts(chartElement, options);
    ageDistributionChart.render();
  }

  /**
   * Initialize gender donut chart
   */
  function initGenderChart(genderData) {
    const chartElement = document.querySelector('#genderChart');
    if (!chartElement) return;

    const genders = genderData.map(item => item.gender);
    const series = genderData.map(item => item.count);

    // Define colors for gender
    const colors = genders.map(gender => {
      if (gender.toLowerCase() === 'male') return '#696cff';
      if (gender.toLowerCase() === 'female') return '#ff3e1d';
      return '#03c3ec';
    });

    const options = {
      series: series,
      labels: genders,
      chart: {
        height: 300,
        type: 'donut'
      },
      colors: colors,
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
                label: labels.employees,
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
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return formatNumber(val) + ' ' + labels.employees;
          }
        }
      }
    };

    if (genderChart) {
      genderChart.destroy();
    }

    genderChart = new ApexCharts(chartElement, options);
    genderChart.render();
  }

  /**
   * Initialize tenure distribution column chart
   */
  function initTenureChart(tenureData) {
    const chartElement = document.querySelector('#tenureChart');
    if (!chartElement) return;

    const tenureGroups = tenureData.map(item => item.tenure_group);
    const counts = tenureData.map(item => item.count);

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
          columnWidth: '60%'
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
        categories: tenureGroups,
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

    if (tenureChart) {
      tenureChart.destroy();
    }

    tenureChart = new ApexCharts(chartElement, options);
    tenureChart.render();
  }

  /**
   * Initialize probation status pie chart
   */
  function initProbationStatusChart(statusData) {
    const chartElement = document.querySelector('#probationStatusChart');
    if (!chartElement) return;

    const statuses = statusData.map(item => item.probation_status);
    const series = statusData.map(item => item.count);

    // Define colors for probation status
    const colors = statuses.map(status => {
      if (status === 'Under Probation') return '#ffab00';
      if (status === 'Confirmed') return '#71dd37';
      if (status === 'Pending Confirmation') return '#ff3e1d';
      return '#a1acb8';
    });

    const options = {
      series: series,
      labels: statuses,
      chart: {
        height: 350,
        type: 'pie'
      },
      colors: colors,
      legend: {
        show: true,
        position: 'bottom',
        fontSize: '13px',
        labels: {
          colors: '#697a8d'
        }
      },
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px'
        },
        formatter: function (val, opts) {
          return Math.round(val) + '%';
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

    if (probationStatusChart) {
      probationStatusChart.destroy();
    }

    probationStatusChart = new ApexCharts(chartElement, options);
    probationStatusChart.render();
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
