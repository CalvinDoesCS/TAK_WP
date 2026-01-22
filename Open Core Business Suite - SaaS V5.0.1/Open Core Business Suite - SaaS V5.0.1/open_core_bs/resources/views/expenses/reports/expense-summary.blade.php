@php
    $title = __('Expense Summary by Category');
@endphp

@section('title', $title)

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/app/expense-summary-report.js'])
@endsection

@extends('layouts/layoutMaster')

@section('content')
    {{-- Breadcrumbs --}}
    <x-breadcrumb
        :title="$title"
        :breadcrumbs="[
            ['name' => __('Expense Management'), 'url' => route('expenses.index')],
            ['name' => __('Reports'), 'url' => '#'],
            ['name' => __('Expense Summary')]
        ]"
        :homeUrl="route('dashboard')"
    >
    </x-breadcrumb>

    {{-- Filter Panel --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bx bx-filter-alt me-2"></i>{{ __('Filters') }}
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- Date From --}}
                <div class="col-md-3">
                    <label for="dateFrom" class="form-label">{{ __('Date From') }}</label>
                    <input type="text" id="dateFrom" class="form-control flatpickr-date" placeholder="{{ __('Select date') }}">
                </div>

                {{-- Date To --}}
                <div class="col-md-3">
                    <label for="dateTo" class="form-label">{{ __('Date To') }}</label>
                    <input type="text" id="dateTo" class="form-control flatpickr-date" placeholder="{{ __('Select date') }}">
                </div>

                {{-- Status Filter --}}
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">{{ __('Status') }}</label>
                    <select id="statusFilter" class="form-select">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                    </select>
                </div>

                {{-- Expense Type Filter --}}
                <div class="col-md-3">
                    <label for="expenseTypeFilter" class="form-label">{{ __('Expense Type') }}</label>
                    <select id="expenseTypeFilter" class="form-select">
                        <option value="">{{ __('All Types') }}</option>
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Buttons --}}
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="button" id="applyFilters" class="btn btn-primary">
                            <i class="bx bx-search me-1"></i>{{ __('Apply Filters') }}
                        </button>
                        <button type="button" id="clearFilters" class="btn btn-label-secondary">
                            <i class="bx bx-reset me-1"></i>{{ __('Clear Filters') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        {{-- Total Submitted Amount --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1">{{ __('Total Submitted') }}</p>
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="mb-0 me-2" id="totalSubmitted">{{ $settings->currency_symbol ?? '$' }} 0.00</h4>
                            </div>
                            <p class="mb-0">
                                <small class="text-muted">{{ __('All submitted expenses') }}</small>
                            </p>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="bx bx-money bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Approved Amount --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1">{{ __('Total Approved') }}</p>
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="mb-0 me-2" id="totalApproved">{{ $settings->currency_symbol ?? '$' }} 0.00</h4>
                            </div>
                            <p class="mb-0">
                                <small class="text-muted">{{ __('All approved expenses') }}</small>
                            </p>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded p-2">
                                <i class="bx bx-check-circle bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Approval Rate --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1">{{ __('Approval Rate') }}</p>
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="mb-0 me-2"><span id="approvalRate">0.0</span>%</h4>
                            </div>
                            <p class="mb-0">
                                <small class="text-muted">{{ __('Percentage approved') }}</small>
                            </p>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded p-2">
                                <i class="bx bx-trending-up bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Requests --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1">{{ __('Total Requests') }}</p>
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="mb-0 me-2" id="totalRequests">0</h4>
                            </div>
                            <p class="mb-0">
                                <small class="text-muted">{{ __('Number of requests') }}</small>
                            </p>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="bx bx-list-ul bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="row mb-4">
        {{-- Donut Chart --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-pie-chart-alt me-2"></i>{{ __('Expense Distribution by Type') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div id="donutChart" style="min-height: 350px;">
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                            <p class="text-muted mt-2">{{ __('Loading chart data...') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar Chart --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-bar-chart me-2"></i>{{ __('Submitted vs Approved by Category') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div id="barChart" style="min-height: 350px;">
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                            <p class="text-muted mt-2">{{ __('Loading chart data...') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Section --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bx bx-table me-2"></i>{{ __('Detailed Summary by Category') }}
            </h5>
        </div>
        <div class="card-body">
            <table id="expenseSummaryTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Expense Type') }}</th>
                        <th>{{ __('Total Submitted') }}</th>
                        <th>{{ __('Total Approved') }}</th>
                        <th>{{ __('Number of Requests') }}</th>
                        <th>{{ __('Approval Rate') }}</th>
                        <th>{{ __('Average Amount') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <script>
        // Pass data to JavaScript
        const pageData = {
            urls: {
                reportData: @json(route('expenses.reports.summary.data')),
                tableData: @json(route('expenses.reports.summary.table')),
            },
            labels: {
                submitted: @json(__('Submitted')),
                approved: @json(__('Approved')),
                noData: @json(__('No data available')),
                loading: @json(__('Loading...')),
                error: @json(__('Error loading data')),
            },
            currencySymbol: @json($settings->currency_symbol ?? '$'),
            dateFormat: 'Y-m-d'
        };
    </script>
@endsection
