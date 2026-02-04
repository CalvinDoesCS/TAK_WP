$(function () {
    // Chart options
    const chartColors = {
        primary: '#696cff',
        secondary: '#8592a3',
        success: '#71dd37',
        warning: '#ffab00',
        danger: '#ff3e1d',
        info: '#03c3ec'
    };

    // Helper function to get status badge
    function getStatusBadge(status) {
        const badges = {
            active: '<span class="badge bg-label-success">Active</span>',
            pending: '<span class="badge bg-label-warning">Pending</span>',
            suspended: '<span class="badge bg-label-danger">Suspended</span>',
            trial: '<span class="badge bg-label-info">Trial</span>'
        };
        return badges[status] || `<span class="badge bg-label-secondary">${status}</span>`;
    }

    // Revenue Chart
    const revenueChartOptions = {
        chart: {
            height: 300,
            type: 'area',
            toolbar: {
                show: false
            }
        },
        series: [{
            name: window.pageData.labels.revenue,
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        },
        colors: [chartColors.primary],
        fill: {
            opacity: 0.8
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '$' + val.toFixed(2);
                }
            }
        }
    };
    const revenueChart = new ApexCharts(document.querySelector('#revenueChart'), revenueChartOptions);
    revenueChart.render();

    // Plan Distribution Chart
    const planChartOptions = {
        chart: {
            height: 250,
            type: 'donut'
        },
        series: [0, 0, 0, 0],
        labels: window.pageData.labels.planLabels,
        colors: [chartColors.secondary, chartColors.info, chartColors.primary, chartColors.success],
        legend: {
            position: 'bottom'
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
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: window.pageData.labels.total,
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    };
    const planChart = new ApexCharts(document.querySelector('#planChart'), planChartOptions);
    planChart.render();

    // Load dashboard data
    function loadDashboardData() {
        // Update statistics cards
        $('.card-title').eq(0).text(window.pageData.stats.totalTenants);
        $('.card-title').eq(1).text(window.pageData.stats.activeSubscriptions);
        $('.card-title').eq(2).text('$' + window.pageData.stats.monthlyRevenue.toFixed(2));
        $('.card-title').eq(3).text(window.pageData.stats.pendingApprovals);

        // Update recent tenants
        if (window.pageData.recentTenants.length === 0) {
            $('#recentTenants').html(`
                <tr>
                    <td colspan="4" class="text-center text-muted">${window.pageData.labels.noTenants}</td>
                </tr>
            `);
        } else {
            let tenantsHtml = '';
            window.pageData.recentTenants.forEach(tenant => {
                const plan = tenant.active_subscription ? tenant.active_subscription.plan.name : 'No Plan';
                const statusBadge = getStatusBadge(tenant.status);
                tenantsHtml += `
                    <tr>
                        <td>${tenant.name}</td>
                        <td><span class="badge bg-label-primary">${plan}</span></td>
                        <td>${statusBadge}</td>
                        <td>${new Date(tenant.created_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            $('#recentTenants').html(tenantsHtml);
        }

        // Update expiring subscriptions
        if (window.pageData.expiringSubscriptions.length === 0) {
            $('#expiringSubscriptions').html(`
                <tr>
                    <td colspan="4" class="text-center text-muted">${window.pageData.labels.noExpiring}</td>
                </tr>
            `);
        } else {
            let expiringHtml = '';
            window.pageData.expiringSubscriptions.forEach(subscription => {
                const daysRemaining = Math.ceil((new Date(subscription.ends_at) - new Date()) / (1000 * 60 * 60 * 24));
                const daysClass = daysRemaining <= 3 ? 'text-danger' : (daysRemaining <= 7 ? 'text-warning' : 'text-muted');
                expiringHtml += `
                    <tr>
                        <td>${subscription.tenant.name}</td>
                        <td><span class="badge bg-label-primary">${subscription.plan.name}</span></td>
                        <td class="${daysClass}">${daysRemaining} days</td>
                        <td>
                            <a href="${window.location.origin}/multitenancy/admin/subscriptions/${subscription.id}" 
                               class="btn btn-sm btn-label-primary">
                                View
                            </a>
                        </td>
                    </tr>
                `;
            });
            $('#expiringSubscriptions').html(expiringHtml);
        }

        // Update plan distribution chart
        if (window.pageData.planDistribution) {
            const planData = window.pageData.planDistribution.map(plan => plan.count);
            const planLabels = window.pageData.planDistribution.map(plan => plan.name);
            
            planChart.updateOptions({
                series: planData,
                labels: planLabels
            });
        }
    }

    // Load data on page load
    loadDashboardData();
});