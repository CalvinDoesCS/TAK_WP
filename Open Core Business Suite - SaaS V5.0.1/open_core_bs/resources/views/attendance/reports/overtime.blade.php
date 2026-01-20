@extends('layouts.layoutMaster')

@section('title', __('Overtime Hours Report'))

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
        'resources/assets/js/app/hrcore-overtime-report.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Overtime Hours Report')"
            :breadcrumbs="[
                ['name' => __('Attendance'), 'url' => route('hrcore.attendance.index')],
                ['name' => __('Overtime Report'), 'url' => '']
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
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-time bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalOvertimeHours">0.00</h4>
                                <small class="text-muted">{{ __('Total Overtime Hours') }}</small>
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
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-user-check bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="employeesWithOvertime">0</h4>
                                <small class="text-muted">{{ __('Employees with Overtime') }}</small>
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
                                    <i class="bx bx-trending-up bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="averageOvertime">0.00</h4>
                                <small class="text-muted">{{ __('Avg Overtime/Employee') }}</small>
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
                                    <i class="bx bx-calendar-week bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="weekendOvertimeHours">0.00</h4>
                                <small class="text-muted">{{ __('Weekend Overtime') }}</small>
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
                        <label for="startDate" class="form-label">{{ __('Start Date') }}</label>
                        <input type="text" id="startDate" name="start_date" class="form-control flatpickr-date"
                               value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="endDate" class="form-label">{{ __('End Date') }}</label>
                        <input type="text" id="endDate" name="end_date" class="form-control flatpickr-date"
                               value="{{ now()->endOfMonth()->format('Y-m-d') }}"
                               placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
                        <select id="departmentFilter" name="department_id" class="form-select select2">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="userFilter" class="form-label">{{ __('Employee') }}</label>
                        <select id="userFilter" name="user_id" class="form-select select2">
                            <option value="">{{ __('All Employees') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->code }} - {{ $user->getFullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="minOvertimeHours" class="form-label">{{ __('Minimum Overtime Hours') }}</label>
                        <input type="number" id="minOvertimeHours" name="min_overtime_hours" class="form-control"
                               placeholder="0.00" step="0.5" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="dayTypeFilter" class="form-label">{{ __('Day Type') }}</label>
                        <select id="dayTypeFilter" name="day_type" class="form-select">
                            <option value="">{{ __('All Days') }}</option>
                            <option value="weekday">{{ __('Weekdays') }}</option>
                            <option value="weekend">{{ __('Weekends') }}</option>
                            <option value="holiday">{{ __('Holidays') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="approvalStatusFilter" class="form-label">{{ __('Approval Status') }}</label>
                        <select id="approvalStatusFilter" name="approval_status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label d-block">&nbsp;</label>
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
            {{-- Overtime by Department Chart --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Overtime by Department') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="overtimeByDepartmentChart"></div>
                    </div>
                </div>
            </div>

            {{-- Overtime Breakdown Chart --}}
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Overtime Breakdown') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="overtimeBreakdownChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Trend Chart --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Monthly Overtime Trend') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="monthlyTrendChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Overtime Table --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Overtime Records') }}</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="overtimeTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Day Type') }}</th>
                                <th>{{ __('Shift Details') }}</th>
                                <th>{{ __('Check Times') }}</th>
                                <th>{{ __('Working Hours') }}</th>
                                <th>{{ __('Overtime Hours') }}</th>
                                <th>{{ __('Approval Status') }}</th>
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
                datatable: @json(route('hrcore.attendance.overtime-report.datatable')),
                statistics: @json(route('hrcore.attendance.overtime-report.statistics')),
                approveOvertime: @json(route('hrcore.attendance.overtime-report.approve', ':id')),
            },
            labels: {
                search: @json(__('Search')),
                processing: @json(__('Processing...')),
                lengthMenu: @json(__('Show _MENU_ entries')),
                info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
                infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
                emptyTable: @json(__('No overtime records found')),
                paginate: {
                    first: @json(__('First')),
                    last: @json(__('Last')),
                    next: @json(__('Next')),
                    previous: @json(__('Previous'))
                },
                confirmApprove: @json(__('Are you sure you want to approve this overtime?')),
                approveSuccess: @json(__('Overtime approved successfully')),
                error: @json(__('An error occurred. Please try again.')),
                overtimeHours: @json(__('Overtime Hours')),
                employees: @json(__('Employees')),
                weekday: @json(__('Weekday')),
                weekend: @json(__('Weekend')),
                holiday: @json(__('Holiday')),
                approved: @json(__('Approved')),
                pending: @json(__('Pending')),
                month: @json(__('Month')),
                hours: @json(__('Hours'))
            }
        };
    </script>
@endsection
