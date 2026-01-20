@extends('layouts.layoutMaster')

@section('title', __('Department Attendance Comparison'))

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
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    ])
@endsection

@section('page-script')
    @vite([
      'resources/assets/js/app/hrcore-department-comparison.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Department Attendance Comparison')"
            :breadcrumbs="[
                ['name' => __('Attendance Management'), 'url' => route('hrcore.attendance.index')],
                ['name' => __('Department Comparison'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-building bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="bestDepartmentRate">--%</h4>
                                <small class="text-muted">{{ __('Best Performance') }}</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted fw-semibold" id="bestDepartmentName">{{ __('Loading...') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded">
                                    <i class="bx bx-error-circle bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="worstDepartmentRate">--%</h4>
                                <small class="text-muted">{{ __('Needs Improvement') }}</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted fw-semibold" id="worstDepartmentName">{{ __('Loading...') }}</small>
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
                                    <i class="bx bx-bar-chart bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="avgAttendanceRate">--%</h4>
                                <small class="text-muted">{{ __('Average Attendance') }}</small>
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
                                    <i class="bx bx-time-five bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalWorkingHours">--</h4>
                                <small class="text-muted">{{ __('Total Working Hours') }}</small>
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
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                        <input type="text" id="start_date" name="start_date" class="form-control flatpickr-date"
                               value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                        <input type="text" id="end_date" name="end_date" class="form-control flatpickr-date"
                               value="{{ now()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department_ids" class="form-label">{{ __('Select Departments') }}</label>
                        <select id="department_ids" name="department_ids" class="form-select select2" multiple>
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">
                                    {{ $department->name }} ({{ $department->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" id="filterBtn">
                            <i class="bx bx-filter-alt me-1"></i> {{ __('Apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Attendance Rate by Department') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="attendanceRateChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Working Hours Distribution') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="workingHoursChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Comparison Table --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Department Comparison Table') }}</h5>
                    <small class="text-muted" id="dateRangeDisplay">{{ __('Select date range') }}</small>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentComparisonTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Rank') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Employees') }}</th>
                                <th>{{ __('Attendance Rate') }}</th>
                                <th>{{ __('Present Days') }}</th>
                                <th>{{ __('Working Hours') }}</th>
                                <th>{{ __('Late Metrics') }}</th>
                                <th>{{ __('Overtime') }}</th>
                                <th>{{ __('Punctuality Score') }}</th>
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
                stats: @json(route('hrcore.attendance.department-comparison-stats')),
                datatable: @json(route('hrcore.attendance.department-comparison-datatable'))
            },
            labels: {
                search: @json(__('Search')),
                processing: @json(__('Processing...')),
                lengthMenu: @json(__('Show _MENU_ entries')),
                info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
                infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
                emptyTable: @json(__('No data available')),
                paginate: {
                    first: @json(__('First')),
                    last: @json(__('Last')),
                    next: @json(__('Next')),
                    previous: @json(__('Previous'))
                },
                department: @json(__('Department')),
                attendanceRate: @json(__('Attendance Rate (%)')),
                workingHours: @json(__('Working Hours')),
                loading: @json(__('Loading...')),
                noData: @json(__('No data available for selected period'))
            }
        };
    </script>
@endsection
