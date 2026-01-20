@php use App\Enums\ExpenseRequestStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', __('Employee Expense Report'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/reports-employee-expense.js'])
@endsection

@section('content')
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('Employee Expense Report')"
    :breadcrumbs="[
      ['name' => __('Home'), 'url' => route('dashboard')],
      ['name' => __('Expense Management'), 'url' => route('expenseRequests.index')],
      ['name' => __('Reports'), 'url' => '#'],
      ['name' => __('Employee Expense'), 'url' => '#']
    ]"
    :homeUrl="route('dashboard')"
  />

  {{-- Statistics Cards --}}
  <div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text text-muted">{{ __('Total Employees') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0 me-2" id="totalEmployees">0</h4>
              </div>
              <small class="text-muted">{{ __('with expenses') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-primary rounded p-2">
                <i class='bx bx-user bx-sm'></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text text-muted">{{ __('Average per Employee') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0 me-2" id="avgPerEmployee">{{ $settings->currency_symbol }} 0</h4>
              </div>
              <small class="text-muted">{{ __('average expense') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-info rounded p-2">
                <i class='bx bx-wallet bx-sm'></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text text-muted">{{ __('Pending Approvals') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0 me-2" id="pendingApprovals">0</h4>
              </div>
              <small class="text-muted">{{ __('awaiting action') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-warning rounded p-2">
                <i class='bx bx-time bx-sm'></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text text-muted">{{ __('Compliance Rate') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0 me-2" id="complianceRate">0%</h4>
              </div>
              <small class="text-muted">{{ __('with documents') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-success rounded p-2">
                <i class='bx bx-check-shield bx-sm'></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row mb-4">
    {{-- Top Employees Bar Chart --}}
    <div class="col-lg-7 col-md-12 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bx bx-bar-chart me-2"></i>{{ __('Top 10 Employees by Expense') }}
          </h5>
        </div>
        <div class="card-body">
          <div id="topEmployeesChart" style="min-height: 350px;"></div>
        </div>
      </div>
    </div>

    {{-- Monthly Trend Line Chart --}}
    <div class="col-lg-5 col-md-12 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bx bx-line-chart me-2"></i>{{ __('Monthly Expense Trend') }}
          </h5>
        </div>
        <div class="card-body">
          <div id="monthlyTrendChart" style="min-height: 350px;"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filters Section --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <!-- Date From Filter -->
        <div class="col-md-2 mb-3 mb-md-0">
          <label for="dateFromFilter" class="form-label">{{ __('Date From') }}</label>
          <input type="text" id="dateFromFilter" name="dateFromFilter" class="form-control" placeholder="{{ __('Select date') }}">
        </div>

        <!-- Date To Filter -->
        <div class="col-md-2 mb-3 mb-md-0">
          <label for="dateToFilter" class="form-label">{{ __('Date To') }}</label>
          <input type="text" id="dateToFilter" name="dateToFilter" class="form-control" placeholder="{{ __('Select date') }}">
        </div>

        <!-- Status Filter -->
        <div class="col-md-2 mb-3 mb-md-0">
          <label for="statusFilter" class="form-label">{{ __('Status') }}</label>
          <select id="statusFilter" name="statusFilter" class="form-select select2">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach(ExpenseRequestStatus::cases() as $status)
              <option value="{{ $status->value }}">{{ $status->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Employee Filter -->
        <div class="col-md-3 mb-3 mb-md-0">
          <label for="employeeFilter" class="form-label">{{ __('Employee') }}</label>
          <select id="employeeFilter" name="employeeFilter" class="form-select select2-ajax">
            <option value="">{{ __('All Employees') }}</option>
          </select>
        </div>

        <!-- Department Filter -->
        <div class="col-md-2 mb-3 mb-md-0">
          <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
          <select id="departmentFilter" name="departmentFilter" class="form-select select2">
            <option value="">{{ __('All Departments') }}</option>
            @foreach($departments as $department)
              <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Filter Actions -->
        <div class="col-md-1 d-flex align-items-end mb-3 mb-md-0">
          <button type="button" id="clearFilters" class="btn btn-label-secondary w-100" title="{{ __('Clear Filters') }}">
            <i class="bx bx-x"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Employee Expense DataTable --}}
  <div class="card">
    <div class="card-datatable table-responsive">
      <table id="employeeExpenseTable" class="table">
        <thead>
          <tr>
            <th>{{ __('#') }}</th>
            <th>{{ __('Employee') }}</th>
            <th>{{ __('Total Submitted') }}</th>
            <th>{{ __('Total Approved') }}</th>
            <th>{{ __('Total Requests') }}</th>
            <th>{{ __('Approval Rate') }}</th>
            <th>{{ __('Pending') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      currencySymbol: @json($settings->currency_symbol),
      urls: {
        datatable: @json(route('expenses.employee-report')),
        statistics: @json(route('expenses.employee-report.statistics')),
        employeeSearch: @json(route('employees.search')),
        expensesList: @json(route('expenseRequests.index')),
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
          previous: @json(__('Previous')),
        },
        selectEmployee: @json(__('Select Employee')),
        selectDepartment: @json(__('Select Department')),
        selectStatus: @json(__('All Statuses')),
        topEmployees: @json(__('Top Employees by Expense')),
        monthlyTrend: @json(__('Monthly Expense Trend')),
        amount: @json(__('Amount')),
        month: @json(__('Month')),
        loading: @json(__('Loading...')),
      }
    };
  </script>
@endsection
