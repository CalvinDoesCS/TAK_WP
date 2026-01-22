@extends('layouts.layoutMaster')

@section('title', __('Multitenancy Dashboard'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js'
    ])
@endsection

@section('content')
    <x-breadcrumb 
        :title="__('Multitenancy Dashboard')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => ''],
            ['name' => __('Dashboard'), 'url' => '']
        ]" 
    />

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">{{ __('Total Tenants') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['total_tenants'] }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="bx bx-buildings bx-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">{{ __('Active Subscriptions') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['active_subscriptions'] }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded p-2">
                                <i class="bx bx-credit-card bx-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">{{ __('Monthly Revenue') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">${{ number_format($stats['monthly_revenue'], 2) }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded p-2">
                                <i class="bx bx-dollar bx-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">{{ __('Pending Approvals') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['pending_approvals'] }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="bx bx-time-five bx-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">{{ __('Revenue Overview') }}</h5>
                </div>
                <div class="card-body">
                    <div id="revenueChart"></div>
                </div>
            </div>
        </div>

        <!-- Plan Distribution -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Plan Distribution') }}</h5>
                </div>
                <div class="card-body">
                    <div id="planChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Tenants -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Recent Tenants') }}</h5>
                    <a href="{{ route('multitenancycore.admin.tenants.index') }}" class="btn btn-sm btn-label-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Company') }}</th>
                                    <th>{{ __('Plan') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody id="recentTenants">
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('Loading...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Subscriptions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Expiring Subscriptions') }}</h5>
                    <a href="{{ route('multitenancycore.admin.subscriptions.index') }}?expiring_soon=1" class="btn btn-sm btn-label-warning">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Tenant') }}</th>
                                    <th>{{ __('Plan') }}</th>
                                    <th>{{ __('Expires') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="expiringSubscriptions">
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('Loading...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
    // Page data
    window.pageData = {
        labels: {
            revenue: @json(__('Revenue')),
            total: @json(__('Total')),
            planLabels: [@json(__('Free')), @json(__('Basic')), @json(__('Professional')), @json(__('Enterprise'))],
            noTenants: @json(__('No tenants found')),
            noExpiring: @json(__('No expiring subscriptions'))
        },
        stats: {
            totalTenants: {{ $stats['total_tenants'] }},
            activeSubscriptions: {{ $stats['active_subscriptions'] }},
            monthlyRevenue: {{ $stats['monthly_revenue'] }},
            pendingApprovals: {{ $stats['pending_approvals'] }}
        },
        recentTenants: @json($recentTenants),
        expiringSubscriptions: @json($expiringSubscriptions),
        planDistribution: @json($planDistribution)
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/dashboard.js'])
@endsection

@section('page-script-old')
<script>
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
            name: '{{ __('Revenue') }}',
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
        labels: ['{{ __('Free') }}', '{{ __('Basic') }}', '{{ __('Professional') }}', '{{ __('Enterprise') }}'],
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
                            label: '{{ __('Total') }}',
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
        // This would typically make an AJAX call to get real data
        // For now, we'll use placeholder data
        
        // Update statistics cards
        $('.card-title').eq(0).text('{{ \Modules\MultiTenancyCore\App\Models\Tenant::count() }}');
        $('.card-title').eq(1).text('{{ \Modules\MultiTenancyCore\App\Models\Subscription::whereIn('status', ['trial', 'active'])->count() }}');
        $('.card-title').eq(2).text('$0'); // Would calculate monthly revenue
        $('.card-title').eq(3).text('{{ \Modules\MultiTenancyCore\App\Models\Tenant::where('status', 'pending')->count() }}');

        // Update recent tenants
        const recentTenantsHtml = `
            <tr>
                <td colspan="4" class="text-center text-muted">{{ __('No tenants found') }}</td>
            </tr>
        `;
        $('#recentTenants').html(recentTenantsHtml);

        // Update expiring subscriptions
        const expiringHtml = `
            <tr>
                <td colspan="4" class="text-center text-muted">{{ __('No expiring subscriptions') }}</td>
            </tr>
        `;
        $('#expiringSubscriptions').html(expiringHtml);
    }

    // Load data on page load
    loadDashboardData();

    // Refresh data every 30 seconds
    setInterval(loadDashboardData, 30000);
});
</script>
@endsection-old