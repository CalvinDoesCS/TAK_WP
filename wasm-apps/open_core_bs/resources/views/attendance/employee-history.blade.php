@extends('layouts.layoutMaster')

@section('title', __('Employee Attendance History'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/moment/moment.js',
    ])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/app/hrcore-attendance-employee-history.js',
    ])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb --}}
    <x-breadcrumb
        :title="__('Employee Attendance History')"
        :breadcrumbs="[
            ['name' => __('Attendance'), 'url' => route('hrcore.attendance.index')],
            ['name' => __('Employee History'), 'url' => '']
        ]"
        :home-url="url('/')"
    />

    {{-- Employee Header Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    @if($employee->profile_photo_path)
                        <img class="rounded" src="{{ asset('storage/'.$employee->profile_photo_path) }}" height="80" width="80" alt="{{ $employee->getFullName() }}">
                    @else
                        <div class="avatar avatar-xl">
                            <span class="avatar-initial rounded bg-label-primary fs-4">
                                {{ $employee->getInitials() }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $employee->getFullName() }}</h4>
                    <div class="d-flex flex-wrap gap-3">
                        <div>
                            <small class="text-muted">{{ __('Employee ID') }}:</small>
                            <span class="badge bg-label-secondary">{{ $employee->code }}</span>
                        </div>
                        <div>
                            <small class="text-muted">{{ __('Department') }}:</small>
                            <span>{{ $employee->designation?->department?->name ?? __('N/A') }}</span>
                        </div>
                        <div>
                            <small class="text-muted">{{ __('Designation') }}:</small>
                            <span>{{ $employee->designation?->name ?? __('N/A') }}</span>
                        </div>
                        <div>
                            <small class="text-muted">{{ __('Shift') }}:</small>
                            <span>{{ $employee->shift?->name ?? __('N/A') }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-label-secondary" onclick="window.history.back()">
                        <i class="bx bx-arrow-back me-1"></i> {{ __('Back') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Range Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="dateRange" class="form-label">{{ __('Select Date Range') }}</label>
                    <input type="text" id="dateRange" class="form-control flatpickr-range" placeholder="{{ __('Select Date Range') }}" />
                </div>
                <div class="col-md-4 mb-3">
                    <label for="quickRange" class="form-label">{{ __('Quick Select') }}</label>
                    <select id="quickRange" class="form-select">
                        <option value="current_month" selected>{{ __('Current Month') }}</option>
                        <option value="last_month">{{ __('Last Month') }}</option>
                        <option value="last_3_months">{{ __('Last 3 Months') }}</option>
                        <option value="last_6_months">{{ __('Last 6 Months') }}</option>
                        <option value="current_year">{{ __('Current Year') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="loadDataBtn">
                        <i class="bx bx-refresh me-1"></i> {{ __('Load Data') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div id="loadingIndicator" class="text-center py-5 d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading...') }}</span>
        </div>
        <p class="mt-2">{{ __('Loading attendance data...') }}</p>
    </div>

    {{-- Main Content (Hidden until data loads) --}}
    <div id="mainContent" class="d-none">
        {{-- Summary Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-check-circle bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statPresent">0</h4>
                                <small class="text-muted">{{ __('Present Days') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded">
                                    <i class="bx bx-x-circle bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statAbsent">0</h4>
                                <small class="text-muted">{{ __('Absent Days') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-time-five bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statLate">0</h4>
                                <small class="text-muted">{{ __('Late Days') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-time bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statAvgHours">0</h4>
                                <small class="text-muted">{{ __('Avg Hours') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-trending-up bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statOvertime">0</h4>
                                <small class="text-muted">{{ __('Overtime') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-check-shield bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="statAttendancePercentage">0%</h4>
                                <small class="text-muted">{{ __('Attendance') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Calendar View --}}
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Calendar View') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="attendanceCalendar"></div>
                        <div class="mt-3">
                            <small class="text-muted d-block mb-2">{{ __('Legend') }}:</small>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-success">{{ __('Present') }}</span>
                                <span class="badge bg-warning">{{ __('Late') }}</span>
                                <span class="badge bg-danger">{{ __('Absent') }}</span>
                                <span class="badge bg-info">{{ __('Half Day') }}</span>
                                <span class="badge bg-secondary">{{ __('Weekend') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Working Hours Trend') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="workingHoursChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Records Timeline --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Daily Attendance Records') }}</h5>
            </div>
            <div class="card-body">
                <div id="timelineContainer"></div>
                <div id="noDataMessage" class="text-center py-5 d-none">
                    <i class="bx bx-calendar-x display-1 text-muted"></i>
                    <p class="mt-2 text-muted">{{ __('No attendance records found for the selected date range.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
    const pageData = {
        urls: {
            employeeHistory: @json(route('hrcore.attendance.employee-history-data', $employee->id)),
        },
        employee: {
            id: @json($employee->id),
            name: @json($employee->getFullName()),
            code: @json($employee->code),
        },
        labels: {
            present: @json(__('Present')),
            absent: @json(__('Absent')),
            late: @json(__('Late')),
            halfDay: @json(__('Half Day')),
            weekend: @json(__('Weekend')),
            checkIn: @json(__('Check In')),
            checkOut: @json(__('Check Out')),
            workingHours: @json(__('Working Hours')),
            lateHours: @json(__('Late Hours')),
            earlyHours: @json(__('Early Hours')),
            overtimeHours: @json(__('Overtime Hours')),
            breakHours: @json(__('Break Hours')),
            shift: @json(__('Shift')),
            site: @json(__('Site')),
            location: @json(__('Location')),
            logs: @json(__('Logs')),
            viewDetails: @json(__('View Details')),
            noData: @json(__('No data available')),
            loading: @json(__('Loading...')),
        }
    };
</script>
@endsection
