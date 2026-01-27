@php use App\Enums\ExpenseRequestStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', __('Expense Requests'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/select2/select2.scss',])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
 'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
   'resources/assets/vendor/libs/@form-validation/auto-focus.js',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      currencySymbol: @json($settings->currency_symbol),
      isSelfService: @json($isSelfService ?? false),
      urls: {
        employeeSearch: @json(route('employees.search')),
        @if($isSelfService ?? false)
          datatableAjax: @json(route('hrcore.my.expenses.datatable'))
        @else
          datatableAjax: @json(route('expenseRequests.indexAjax'))
        @endif
      }
    };
  </script>
  @vite(['resources/assets/js/app/expense-requests-index.js'])
@endsection


@section('content')
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('All Expense Requests')"
    :breadcrumbs="[
      ['name' => __('Expense Management'), 'url' => '#'],
      ['name' => __('All Expense Requests'), 'url' => '#']
    ]"
    :homeUrl="url('/')"
  />

  <!-- Filters Section -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <!-- Employee Filter (Only show for admin, not self-service) -->
        @if(!($isSelfService ?? false))
          <div class="col-md-3 mb-3 mb-md-0">
            <label for="employeeFilter" class="form-label">{{ __('Filter by employee') }}</label>
            <select id="employeeFilter" name="employeeFilter" class="form-select select2-ajax">
              <option value="">{{ __('All Employees') }}</option>
            </select>
          </div>
        @endif

        <!--Date Filter -->
        <div class="col-md-3 mb-3 mb-md-0">
          <label for="dateFilter" class="form-label">{{ __('Filter by date') }}</label>
          <input type="date" id="dateFilter" name="dateFilter" class="form-control">
        </div>

        <!-- Expense Type filter -->
        <div class="col-md-3 mb-3 mb-md-0">
          <label for="expenseTypeFilter" class="form-label">{{ __('Filter by expense type') }}</label>
          <select id="expenseTypeFilter" name="expenseTypeFilter" class="form-select select2">
            <option value="" selected>{{ __('All Expense Types') }}</option>
            @foreach($expenseTypes as $expenseType)
              <option value="{{ $expenseType->id }}">{{ $expenseType->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-3 mb-3 mb-md-0">
          <label for="statusFilter" class="form-label">{{ __('Filter by status') }}</label>
          <select id="statusFilter" name="statusFilter" class="form-select select2">
            <option value="" selected>{{ __('All Statuses') }}</option>
            @foreach(ExpenseRequestStatus::cases() as $status)
              <option value="{{ $status->value }}">{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Expense Requests table card -->
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-expenseRequests table border-top">
        <thead>
        <tr>
          <th>@lang('')</th>
          <th>@lang('Id')</th>
          @if(!($isSelfService ?? false))
            <th>@lang('User')</th>
          @endif
          <th>@lang('Expense Type')</th>
          <th>@lang('Expense Date')</th>
          <th>@lang('Amount')</th>
          <th>@lang('Status')</th>
          <th>@lang('Image')</th>
          <th>@lang('Actions')</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>
  @include('_partials._modals.expense.expense_request_details')
@endsection


