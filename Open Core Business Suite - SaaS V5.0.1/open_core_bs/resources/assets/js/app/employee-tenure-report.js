$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  let tenureDistChart, departmentChart, designationChart;

  // Initialize Select2
  $('.select2').select2({
    placeholder: function () {
      return $(this).data('placeholder');
    },
    allowClear: true
  });

  // Load statistics and update charts
  function loadStatistics() {
    const filters = {
      department_id: $('#departmentFilter').val(),
      designation_id: $('#designationFilter').val()
    };

    $.ajax({
      url: pageData.urls.statistics,
      type: 'GET',
      data: filters,
      success: function (response) {
        if (response.status === 'success') {
          updateStatistics(response.data);
          updateCharts(response.data);
          updateTables(response.data);
        }
      },
      error: function (xhr) {
        console.error('Error loading statistics:', xhr);
      }
    });
  }

  // Update statistics cards
  function updateStatistics(data) {
    $('#averageTenure').text(data.average_tenure_months.toFixed(1));
    $('#totalEmployees').text(data.total_employees);

    // Find longest tenure
    if (data.longest_serving && data.longest_serving.length > 0) {
      const longest = data.longest_serving[0].tenure_months;
      $('#longestTenure').text(longest);
    } else {
      $('#longestTenure').text('0');
    }
  }

  // Update all charts
  function updateCharts(data) {
    updateTenureDistributionChart(data.tenure_distribution);
    updateDepartmentTenureChart(data.tenure_by_department);
    updateDesignationTenureChart(data.tenure_by_designation);
  }

  // Tenure Distribution Chart
  function updateTenureDistributionChart(distributionData) {
    const ranges = distributionData.map(item => item.range);
    const counts = distributionData.map(item => item.count);

    const options = {
      series: [{
        name: pageData.labels.employees,
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
          borderRadius: 4,
          horizontal: false,
          columnWidth: '55%'
        }
      },
      dataLabels: {
        enabled: true
      },
      xaxis: {
        categories: ranges,
        title: {
          text: 'Tenure Range'
        }
      },
      yaxis: {
        title: {
          text: pageData.labels.employees
        }
      },
      colors: ['#00d4bd']
    };

    if (tenureDistChart) {
      tenureDistChart.destroy();
    }

    tenureDistChart = new ApexCharts(document.querySelector('#tenureDistributionChart'), options);
    tenureDistChart.render();
  }

  // Department Tenure Chart
  function updateDepartmentTenureChart(departmentData) {
    const departments = departmentData.map(item => item.department_name);
    const avgTenure = departmentData.map(item => parseFloat(item.avg_tenure_months));

    const options = {
      series: [{
        name: pageData.labels.averageTenure,
        data: avgTenure
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
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + ' ' + pageData.labels.months;
        }
      },
      xaxis: {
        categories: departments,
        title: {
          text: pageData.labels.months
        }
      },
      colors: ['#826bf8']
    };

    if (departmentChart) {
      departmentChart.destroy();
    }

    departmentChart = new ApexCharts(document.querySelector('#departmentTenureChart'), options);
    departmentChart.render();
  }

  // Designation Tenure Chart
  function updateDesignationTenureChart(designationData) {
    const designations = designationData.map(item => item.designation_name);
    const avgTenure = designationData.map(item => parseFloat(item.avg_tenure_months));

    const options = {
      series: [{
        name: pageData.labels.averageTenure,
        data: avgTenure
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
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + ' ' + pageData.labels.months;
        }
      },
      xaxis: {
        categories: designations,
        title: {
          text: pageData.labels.months
        }
      },
      colors: ['#ff9f43']
    };

    if (designationChart) {
      designationChart.destroy();
    }

    designationChart = new ApexCharts(document.querySelector('#designationTenureChart'), options);
    designationChart.render();
  }

  // Update tables
  function updateTables(data) {
    updateLongestServingTable(data.longest_serving);
    updateNewestEmployeesTable(data.newest_employees);
  }

  // Longest Serving Table
  function updateLongestServingTable(longestServing) {
    const tbody = $('#longestServingTable tbody');
    tbody.empty();

    if (longestServing && longestServing.length > 0) {
      longestServing.forEach(function (employee) {
        const years = Math.floor(employee.tenure_months / 12);
        const months = Math.floor(employee.tenure_months % 12);

        let tenureText = '';
        if (years > 0) {
          tenureText = years + ' ' + (years > 1 ? pageData.labels.years : pageData.labels.year);
          if (months > 0) {
            tenureText += ', ' + months + ' ' + pageData.labels.months;
          }
        } else {
          tenureText = months + ' ' + pageData.labels.months;
        }

        const row = `
          <tr>
            <td>${employee.employee_html}</td>
            <td>${employee.department}</td>
            <td>${employee.date_of_joining}</td>
            <td><span class="badge bg-label-success">${tenureText}</span></td>
          </tr>
        `;
        tbody.append(row);
      });
    } else {
      tbody.append('<tr><td colspan="4" class="text-center text-muted">No data available</td></tr>');
    }
  }

  // Newest Employees Table
  function updateNewestEmployeesTable(newestEmployees) {
    const tbody = $('#newestEmployeesTable tbody');
    tbody.empty();

    if (newestEmployees && newestEmployees.length > 0) {
      newestEmployees.forEach(function (employee) {
        const row = `
          <tr>
            <td>${employee.employee_html}</td>
            <td>${employee.department}</td>
            <td>${employee.date_of_joining}</td>
            <td><span class="badge bg-label-info">${employee.days_since_joining} days</span></td>
          </tr>
        `;
        tbody.append(row);
      });
    } else {
      tbody.append('<tr><td colspan="4" class="text-center text-muted">No new employees in last 30 days</td></tr>');
    }
  }

  // Apply filters button
  $('#applyFilterBtn').on('click', function () {
    loadStatistics();
  });

  // Reset filters button
  $('#resetFilterBtn').on('click', function () {
    $('#departmentFilter').val('').trigger('change');
    $('#designationFilter').val('').trigger('change');
    loadStatistics();
  });

  // Initialize on page load
  loadStatistics();
});
