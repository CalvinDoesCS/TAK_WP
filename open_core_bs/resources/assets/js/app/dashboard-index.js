'use strict';

$(function () {
    // CSRF Token Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Color configuration
    let cardColor, headingColor, labelColor, borderColor, legendColor;

    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        headingColor = config.colors_dark.headingColor;
        labelColor = config.colors_dark.textMuted;
        legendColor = config.colors_dark.bodyColor;
        borderColor = config.colors_dark.borderColor;
    } else {
        cardColor = config.colors.cardColor;
        headingColor = config.colors.headingColor;
        labelColor = config.colors.textMuted;
        legendColor = config.colors.bodyColor;
        borderColor = config.colors.borderColor;
    }

    // Chart color constants
    const chartColors = {
        primary: '#826af9',
        success: '#2ECC71',
        danger: '#E74C3C',
        warning: '#FFC107',
        info: '#1D9FF2',
        secondary: '#d2b0ff',
        column: {
            series1: '#826af9',
            series2: '#d2b0ff',
            bg: '#f8d3ff'
        },
        donut: {
            series1: '#fee802',
            series2: '#F1F0F2',
            series3: '#826bf8',
            series4: '#3fd0bd'
        }
    };

    /**
     * Initialize Weekly Report Gauge Chart
     */
    function initWeeklyReportChart() {
        const weeklyChartEl = document.querySelector('#weeklyReportChart');
        if (!weeklyChartEl) return;

        const weeklyHours = pageData.weeklyHours || 0;
        const targetHours = 40; // Standard work week
        const percentage = Math.min((weeklyHours / targetHours) * 100, 100);

        const weeklyChartConfig = {
            chart: {
                height: 120,
                width: 120,
                type: 'radialBar'
            },
            series: [percentage],
            colors: [chartColors.primary],
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '60%'
                    },
                    dataLabels: {
                        show: true,
                        name: {
                            show: false
                        },
                        value: {
                            show: true,
                            fontSize: '18px',
                            fontWeight: 600,
                            offsetY: 8,
                            formatter: function (val) {
                                return Math.round(val) + '%';
                            }
                        }
                    }
                }
            },
            stroke: {
                lineCap: 'round'
            }
        };

        const weeklyChart = new ApexCharts(weeklyChartEl, weeklyChartConfig);
        weeklyChart.render();
    }

    /**
     * Load Department Performance Chart
     */
    function loadDepartmentPerformance() {
        const chartEl = document.querySelector('#topDepartmentsChart');
        if (!chartEl) return;

        // Show loading state
        chartEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">' + pageData.labels.loading + '</span></div></div>';

        $.ajax({
            url: pageData.urls.departmentPerformance,
            type: 'GET',
            success: function (response) {
                const data = response.data;

                if (!data || data.length === 0) {
                    chartEl.innerHTML = '<div class="text-center py-5 text-muted">' + pageData.labels.error + '</div>';
                    return;
                }

                // Extract department data
                const departmentNames = data.map(dept => dept.code);
                const presentEmployees = data.map(dept => dept.totalPresentEmployees);
                const absentEmployees = data.map(dept => dept.totalAbsentEmployees);

                // Clear loading state
                chartEl.innerHTML = '';

                // Initialize ApexCharts with vertical column chart
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
                            name: 'Present Employees',
                            data: presentEmployees
                        },
                        {
                            name: 'Absent Employees',
                            data: absentEmployees
                        }
                    ],
                    xaxis: {
                        categories: departmentNames,
                        labels: {
                            style: {
                                fontSize: '12px',
                                fontWeight: '500',
                                colors: labelColor
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Number of Employees',
                            style: {
                                color: labelColor,
                                fontSize: '12px',
                                fontWeight: '500'
                            }
                        },
                        labels: {
                            style: {
                                colors: labelColor
                            }
                        }
                    },
                    colors: [chartColors.success, chartColors.danger],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '50%',
                            borderRadius: 8
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '10px',
                            fontWeight: 'bold',
                            colors: ['#fff']
                        }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        theme: isDarkStyle ? 'dark' : 'light'
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        fontSize: '13px',
                        markers: {
                            width: 10,
                            height: 10,
                            radius: 4
                        },
                        labels: {
                            colors: legendColor
                        }
                    },
                    grid: {
                        borderColor: borderColor,
                        strokeDashArray: 4,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        }
                    }
                };

                const chart = new ApexCharts(chartEl, options);
                chart.render();
            },
            error: function (xhr, status, error) {
                console.error('Department Performance Error:', error);
                chartEl.innerHTML = '<div class="text-center py-5"><p class="text-danger">' + pageData.labels.error + '</p></div>';
            }
        });
    }

    /**
     * Load Recent Activities
     */
    function loadRecentActivities() {
        const activityList = document.querySelector('#activityList');
        if (!activityList) return;

        // Show loading state
        activityList.innerHTML = '<li class="list-group-item text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">' + pageData.labels.loading + '</span></div></li>';

        $.ajax({
            url: pageData.urls.recentActivities,
            type: 'GET',
            success: function (response) {
                activityList.innerHTML = '';

                if (!response.data || response.data.length === 0) {
                    activityList.innerHTML = `
                        <li class="list-group-item text-center py-5">
                            <i class="bx bx-info-circle bx-lg text-muted mb-2"></i>
                            <p class="text-muted mb-0">${pageData.labels.noActivities}</p>
                        </li>
                    `;
                    return;
                }

                response.data.forEach(activity => {
                    const user = activity.user || {};
                    const iconClass = getActivityIcon(activity.type);
                    const colorClass = getActivityColor(activity.type);

                    const activityHtml = `
                        <li class="list-group-item border-0 d-flex align-items-start py-3">
                            <div class="d-flex align-items-start w-100">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-${colorClass}">
                                        <i class="bx ${iconClass}"></i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0">${activity.type}</h6>
                                        <small class="text-muted">${activity.created_at_human}</small>
                                    </div>
                                    <p class="mb-0">${activity.title}</p>
                                    ${user.name ? `<small class="text-muted">by ${user.name}</small>` : ''}
                                </div>
                            </div>
                        </li>
                    `;

                    activityList.innerHTML += activityHtml;
                });
            },
            error: function (xhr, status, error) {
                console.error('Recent Activities Error:', error);
                activityList.innerHTML = `
                    <li class="list-group-item text-center py-5">
                        <p class="text-danger mb-0">${pageData.labels.error}</p>
                    </li>
                `;
            }
        });
    }

    /**
     * Get activity icon based on type
     */
    function getActivityIcon(type) {
        const icons = {
            'Order': 'bx-cart',
            'Visit': 'bx-map',
            'Form Submission': 'bx-file',
            'Task': 'bx-task'
        };
        return icons[type] || 'bx-history';
    }

    /**
     * Get activity color based on type
     */
    function getActivityColor(type) {
        const colors = {
            'Order': 'success',
            'Visit': 'primary',
            'Form Submission': 'info',
            'Task': 'warning'
        };
        return colors[type] || 'secondary';
    }

    /**
     * Initialize all charts and load data
     */
    function init() {
        // Initialize charts
        initWeeklyReportChart();

        // Load dynamic data
        loadDepartmentPerformance();
        loadRecentActivities();
    }

    // Initialize on document ready
    init();
});
