@extends('layouts.layoutMaster')

@section('title', __('Employee Turnover Analysis'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    ])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/app/employee-turnover-report.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Employee Turnover Analysis')"
            :breadcrumbs="[
                ['name' => __('Employees'), 'url' => route('employees.index')],
                ['name' => __('Turnover Analysis'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded">
                                    <i class="bx bx-trending-up bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="overallTurnoverRate">0.00%</h4>
                                <small class="text-muted">{{ __('Overall Turnover Rate') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-user-x bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalTerminations">0</h4>
                                <small class="text-muted">{{ __('Total Terminations') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-time bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="averageTenure">0.0</h4>
                                <small class="text-muted">{{ __('Avg Tenure (Months)') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-user-check bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="averageHeadcount">0</h4>
                                <small class="text-muted">{{ __('Average Headcount') }}</small>
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
                    <div class="col-md-4 mb-3">
                        <label for="startDate" class="form-label">{{ __('Start Date') }}</label>
                        <input type="text" id="startDate" name="start_date" class="form-control flatpickr-date"
                               value="{{ now()->subMonths(12)->startOfMonth()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="endDate" class="form-label">{{ __('End Date') }}</label>
                        <input type="text" id="endDate" name="end_date" class="form-control flatpickr-date"
                               value="{{ now()->endOfMonth()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
                        <select id="departmentFilter" name="department_id" class="form-select select2">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
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
            {{-- Monthly Turnover Trend --}}
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Monthly Turnover Trend') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="monthlyTurnoverChart"></div>
                    </div>
                </div>
            </div>

            {{-- Turnover by Department --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Turnover by Department') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="departmentTurnoverChart"></div>
                    </div>
                </div>
            </div>

            {{-- Turnover by Type --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Termination Types') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="terminationTypeChart"></div>
                    </div>
                </div>
            </div>

            {{-- Voluntary vs Involuntary --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Voluntary vs Involuntary') }}</h5>
                        <div id="voluntaryInvoluntaryChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Terminations Table --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Recent Terminations') }}</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="terminationsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Designation') }}</th>
                                <th>{{ __('Termination Date') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Tenure') }}</th>
                                <th>{{ __('Exit Reason') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Data for JavaScript --}}
    <script>
        const pageData = {
            urls: {
                datatable: @json(route('employees.reports.turnover.records')),
                statistics: @json(route('employees.reports.turnover.data')),
            },
            labels: {
                search: @json(__('Search')),
                processing: @json(__('Processing...')),
                lengthMenu: @json(__('Show _MENU_ entries')),
                info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
                infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
                emptyTable: @json(__('No termination records found')),
                paginate: {
                    first: @json(__('First')),
                    last: @json(__('Last')),
                    next: @json(__('Next')),
                    previous: @json(__('Previous'))
                },
                turnoverRate: @json(__('Turnover Rate')),
                terminations: @json(__('Terminations')),
                month: @json(__('Month')),
                department: @json(__('Department')),
                type: @json(__('Type')),
                count: @json(__('Count')),
                voluntary: @json(__('Voluntary')),
                involuntary: @json(__('Involuntary')),
            }
        };
    </script>
@endsection
