@extends('layouts.layoutMaster')

@section('title', __('Employee Tenure Analysis'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    ])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/app/employee-tenure-report.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Employee Tenure Analysis')"
            :breadcrumbs="[
                ['name' => __('Employees'), 'url' => route('employees.index')],
                ['name' => __('Tenure Analysis'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-time bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="averageTenure">0.0</h4>
                                <small class="text-muted">{{ __('Average Tenure (Months)') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-user-check bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalEmployees">0</h4>
                                <small class="text-muted">{{ __('Total Active Employees') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-star bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="longestTenure">0</h4>
                                <small class="text-muted">{{ __('Longest Tenure (Months)') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
                        <select id="departmentFilter" name="department_id" class="form-select select2">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="designationFilter" class="form-label">{{ __('Designation') }}</label>
                        <select id="designationFilter" name="designation_id" class="form-select select2">
                            <option value="">{{ __('All Designations') }}</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="applyFilterBtn">
                            <i class="bx bx-filter-alt me-1"></i> {{ __('Apply Filters') }}
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="resetFilterBtn">
                            <i class="bx bx-refresh me-1"></i> {{ __('Reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            {{-- Tenure Distribution --}}
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Tenure Distribution') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="tenureDistributionChart"></div>
                    </div>
                </div>
            </div>

            {{-- Average Tenure by Department --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Average Tenure by Department') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="departmentTenureChart"></div>
                    </div>
                </div>
            </div>

            {{-- Average Tenure by Designation --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Average Tenure by Designation') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="designationTenureChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables Row --}}
        <div class="row">
            {{-- Longest Serving Employees --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Longest Serving Employees') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="longestServingTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee') }}</th>
                                        <th>{{ __('Department') }}</th>
                                        <th>{{ __('Joined') }}</th>
                                        <th>{{ __('Tenure') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Newest Employees --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Newest Employees (Last 30 Days)') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="newestEmployeesTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee') }}</th>
                                        <th>{{ __('Department') }}</th>
                                        <th>{{ __('Joined') }}</th>
                                        <th>{{ __('Days') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Data for JavaScript --}}
    <script>
        const pageData = {
            urls: {
                statistics: @json(route('employees.reports.tenure.data')),
            },
            labels: {
                averageTenure: @json(__('Average Tenure')),
                months: @json(__('Months')),
                years: @json(__('Years')),
                year: @json(__('Year')),
                employees: @json(__('Employees')),
                count: @json(__('Count')),
                department: @json(__('Department')),
                designation: @json(__('Designation')),
            }
        };
    </script>
@endsection
