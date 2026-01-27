@extends('layouts.layoutMaster')

@section('title', __('Late Arrivals Report'))

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
      'resources/assets/js/app/late-arrivals-report.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Late Arrivals Report')"
            :breadcrumbs="[
                ['name' => __('Attendance'), 'url' => route('hrcore.attendance.index')],
                ['name' => __('Late Arrivals Report'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-filter-alt"></i> {{ __('Filters') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="dateRange" class="form-label">{{ __('Date Range') }} <span class="text-danger">*</span></label>
                        <input type="text" id="dateRange" class="form-control" placeholder="{{ __('Select date range') }}">
                        <input type="hidden" id="startDate" name="start_date">
                        <input type="hidden" id="endDate" name="end_date">
                    </div>
                    <div class="col-md-3">
                        <label for="departmentId" class="form-label">{{ __('Department') }}</label>
                        <select id="departmentId" name="department_id" class="form-select select2">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="userId" class="form-label">{{ __('Employee') }}</label>
                        <select id="userId" name="user_id" class="form-select select2">
                            <option value="">{{ __('All Employees') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->code }} - {{ $user->getFullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="minLateMinutes" class="form-label">{{ __('Minimum Late Minutes') }}</label>
                        <input type="number" id="minLateMinutes" name="min_late_minutes" class="form-control"
                               placeholder="{{ __('e.g., 15') }}" min="0" value="0">
                    </div>
                    <div class="col-md-12">
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

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-time-five bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="totalLateInstances">0</h3>
                                <small class="text-muted">{{ __('Total Late Instances') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-timer bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="avgLateMinutes">0</h3>
                                <small class="text-muted">{{ __('Avg Late Minutes') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded">
                                    <i class="bx bx-user-x bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0" id="mostLateEmployee">{{ __('N/A') }}</h6>
                                <small class="text-muted">{{ __('Most Late Employee') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Late Arrivals by Day of Week') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="lateByDayChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Late Arrival Trend') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="lateTrendChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Charts Row --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Top 10 Late Employees') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="topLateEmployeesChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Late Arrivals by Department') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="lateByDepartmentChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Late Arrivals Table --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Late Arrival Instances') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="lateArrivalsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Day') }}</th>
                                <th>{{ __('Shift') }}</th>
                                <th>{{ __('Scheduled Time') }}</th>
                                <th>{{ __('Actual Check-In') }}</th>
                                <th>{{ __('Late Duration') }}</th>
                                <th>{{ __('Reason') }}</th>
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
                datatable: @json(route('hrcore.attendance.late-arrivals.datatable')),
                statistics: @json(route('hrcore.attendance.late-arrivals.statistics'))
            },
            labels: {
                search: @json(__('Search')),
                processing: @json(__('Processing...')),
                lengthMenu: @json(__('Show _MENU_ entries')),
                info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
                infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
                emptyTable: @json(__('No late arrivals found')),
                paginate: {
                    first: @json(__('First')),
                    last: @json(__('Last')),
                    next: @json(__('Next')),
                    previous: @json(__('Previous'))
                },
                // Chart labels
                lateArrivals: @json(__('Late Arrivals')),
                count: @json(__('Count')),
                minutes: @json(__('Minutes')),
                days: {
                    sunday: @json(__('Sunday')),
                    monday: @json(__('Monday')),
                    tuesday: @json(__('Tuesday')),
                    wednesday: @json(__('Wednesday')),
                    thursday: @json(__('Thursday')),
                    friday: @json(__('Friday')),
                    saturday: @json(__('Saturday'))
                },
                noData: @json(__('No data available'))
            },
            // Default date range (last 30 days)
            defaultStartDate: @json(now()->subMonth()->format('Y-m-d')),
            defaultEndDate: @json(now()->format('Y-m-d'))
        };
    </script>
@endsection
