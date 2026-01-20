@extends('layouts.layoutMaster')

@section('title', __('Monthly Attendance Summary'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/moment/moment.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    ])
@endsection

@section('page-script')
    @vite([
      'resources/assets/js/app/attendance-monthly-summary.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Monthly Attendance Summary')"
            :breadcrumbs="[
                ['name' => __('Attendance'), 'url' => route('hrcore.attendance.index')],
                ['name' => __('Monthly Summary'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Summary Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-group bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalEmployees">0</h4>
                                <small class="text-muted">{{ __('Total Employees') }}</small>
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
                                    <i class="bx bx-trending-up bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="averageAttendanceRate">0%</h4>
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
                                <h4 class="mb-0" id="totalWorkingHours">0h</h4>
                                <small class="text-muted">{{ __('Total Working Hours') }}</small>
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
                                    <i class="bx bx-plus-circle bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0" id="totalOvertimeHours">0h</h4>
                                <small class="text-muted">{{ __('Total Overtime') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="month" class="form-label">{{ __('Month') }}</label>
                        <input type="text" id="month" name="month" class="form-control flatpickr-month"
                               value="{{ now()->format('Y-m') }}"
                               placeholder="{{ __('Select Month') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="departmentId" class="form-label">{{ __('Department') }}</label>
                        <select id="departmentId" name="department_id" class="form-select select2">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="userId" class="form-label">{{ __('Employee') }}</label>
                        <select id="userId" name="user_id" class="form-select select2">
                            <option value="">{{ __('All Employees') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->code }} - {{ $user->getFullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-primary" id="filterBtn">
                            <i class="bx bx-filter-alt me-1"></i> {{ __('Apply Filters') }}
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="resetBtn">
                            <i class="bx bx-refresh me-1"></i> {{ __('Reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Summary Table --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Employee-wise Monthly Summary') }}</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="monthlySummaryTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Present Days') }}</th>
                                <th>{{ __('Absent Days') }}</th>
                                <th>{{ __('Late Days') }}</th>
                                <th>{{ __('Half Days') }}</th>
                                <th>{{ __('Working Hours') }}</th>
                                <th>{{ __('Late Hours') }}</th>
                                <th>{{ __('Early Hours') }}</th>
                                <th>{{ __('Overtime Hours') }}</th>
                                <th>{{ __('Attendance %') }}</th>
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
                datatable: @json(route('hrcore.attendance.monthly-summary.datatable')),
                statistics: @json(route('hrcore.attendance.monthly-summary.statistics')),
                attendanceIndex: @json(route('hrcore.attendance.index'))
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
                selectMonth: @json(__('Select Month')),
                selectDepartment: @json(__('Select Department')),
                selectEmployee: @json(__('Select Employee'))
            }
        };
    </script>
@endsection
