@extends('layouts.layoutMaster')

@section('title', __('Daily Attendance Report'))

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
      'resources/assets/js/app/attendance-daily-report.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Daily Attendance Report')"
            :breadcrumbs="[
                ['name' => __('Attendance'), 'url' => route('hrcore.attendance.index')],
                ['name' => __('Daily Report'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Report Card --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-file-blank me-2"></i>{{ __('Daily Attendance Report') }}
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" id="filterBtn">
                        <i class="bx bx-filter-alt me-1"></i> {{ __('Filters') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dailyReportTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Check In') }}</th>
                                <th>{{ __('Check Out') }}</th>
                                <th>{{ __('Shift') }}</th>
                                <th>{{ __('Working Hrs') }}</th>
                                <th>{{ __('Late') }}</th>
                                <th>{{ __('Early Out') }}</th>
                                <th>{{ __('Overtime') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Location') }}</th>
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

    {{-- Filters Offcanvas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filtersOffcanvas" aria-labelledby="filtersOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 id="filtersOffcanvasLabel" class="offcanvas-title">
                <i class="bx bx-filter-alt me-2"></i>{{ __('Report Filters') }}
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filtersForm">
                <div class="mb-3">
                    <label for="filterDate" class="form-label">{{ __('Date') }}</label>
                    <input type="text" id="filterDate" name="date" class="form-control flatpickr-date"
                           value="{{ now()->format('Y-m-d') }}"
                           placeholder="YYYY-MM-DD">
                    <small class="text-muted">{{ __('Select the date for the report') }}</small>
                </div>

                <div class="mb-3">
                    <label for="filterUser" class="form-label">{{ __('Employee') }}</label>
                    <select id="filterUser" name="user_id" class="form-select select2">
                        <option value="">{{ __('All Employees') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->code }} - {{ $user->getFullName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="filterDepartment" class="form-label">{{ __('Department') }}</label>
                    <select id="filterDepartment" name="department_id" class="form-select select2">
                        <option value="">{{ __('All Departments') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="filterShift" class="form-label">{{ __('Shift') }}</label>
                    <select id="filterShift" name="shift_id" class="form-select select2">
                        <option value="">{{ __('All Shifts') }}</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="filterStatus" class="form-label">{{ __('Status') }}</label>
                    <select id="filterStatus" name="status" class="form-select">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="present">{{ __('Present') }}</option>
                        <option value="late">{{ __('Late') }}</option>
                        <option value="early">{{ __('Early Checkout') }}</option>
                        <option value="overtime">{{ __('Overtime') }}</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bx bx-search me-1"></i>{{ __('Apply Filters') }}
                    </button>
                    <button type="button" class="btn btn-label-secondary flex-fill" id="resetFiltersBtn">
                        <i class="bx bx-refresh me-1"></i>{{ __('Reset') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Page Data for JavaScript --}}
    <script>
        const pageData = {
            urls: {
                datatable: @json(route('hrcore.attendance.daily-report.ajax')),
            },
            labels: {
                search: @json(__('Search')),
                processing: @json(__('Processing...')),
                lengthMenu: @json(__('Show _MENU_ entries')),
                info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
                infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
                emptyTable: @json(__('No attendance records found')),
                paginate: {
                    first: @json(__('First')),
                    last: @json(__('Last')),
                    next: @json(__('Next')),
                    previous: @json(__('Previous'))
                }
            },
            currentDate: @json(now()->format('Y-m-d'))
        };
    </script>
@endsection
