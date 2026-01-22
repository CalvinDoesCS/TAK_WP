@php use App\Enums\ExpenseRequestStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', __('My Expenses'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
   'resources/assets/vendor/libs/select2/select2.scss',])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      currencySymbol: @json($settings->currency_symbol),
      urls: {
        datatableAjax: @json(route('hrcore.my.expenses.datatable')),
        createExpense: @json(route('hrcore.my.expenses.create')),
        showExpense: @json(route('hrcore.my.expenses.show', ':id'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('You will not be able to recover this expense request!')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleted: @json(__('Deleted!')),
        deleteSuccess: @json(__('Your expense request has been deleted.')),
        error: @json(__('Error')),
        deleteError: @json(__('Failed to delete expense request'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/my-expenses-index.js'])
@endsection


@section('content')
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('My Expenses')"
    :breadcrumbs="[
      ['name' => __('My Expenses'), 'url' => '#']
    ]"
    :homeUrl="url('/')"
  >
    <x-slot name="actions">
      <a href="{{ route('hrcore.my.expenses.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>
        <span class="d-none d-sm-inline-block">{{ __('Submit Expense') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filters Section -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <!--Date Filter -->
        <div class="col-md-4 mb-3 mb-md-0">
          <label for="dateFilter" class="form-label">{{ __('Filter by date') }}</label>
          <input type="date" id="dateFilter" name="dateFilter" class="form-control">
        </div>

        <!-- Expense Type filter -->
        <div class="col-md-4 mb-3 mb-md-0">
          <label for="expenseTypeFilter" class="form-label">{{ __('Filter by expense type') }}</label>
          <select id="expenseTypeFilter" name="expenseTypeFilter" class="form-select select2">
            <option value="" selected>{{ __('All Expense Types') }}</option>
            @foreach($expenseTypes as $expenseType)
              <option value="{{ $expenseType->id }}">{{ $expenseType->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-4 mb-3 mb-md-0">
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

  <!-- My Expenses table card -->
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-myExpenses table border-top">
        <thead>
        <tr>
          <th>@lang('')</th>
          <th>@lang('Id')</th>
          <th>@lang('Expense Type')</th>
          <th>@lang('Expense Date')</th>
          <th>@lang('Amount')</th>
          <th>@lang('Status')</th>
          <th>@lang('Receipt')</th>
          <th>@lang('Actions')</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- Expense Details Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExpenseDetails" aria-labelledby="offcanvasExpenseDetailsLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasExpenseDetailsLabel" class="offcanvas-title">{{ __('Expense Details') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="mb-3">
        <label class="form-label text-muted">{{ __('Expense Type') }}</label>
        <p class="fw-medium" id="detail_expense_type">-</p>
      </div>

      <div class="mb-3">
        <label class="form-label text-muted">{{ __('Expense Date') }}</label>
        <p class="fw-medium" id="detail_expense_date">-</p>
      </div>

      <div class="mb-3">
        <label class="form-label text-muted">{{ __('Amount Requested') }}</label>
        <p class="fw-medium" id="detail_amount">-</p>
      </div>

      <div class="mb-3" id="approved_amount_section" style="display: none;">
        <label class="form-label text-muted">{{ __('Approved Amount') }}</label>
        <p class="fw-medium text-success" id="detail_approved_amount">-</p>
      </div>

      <div class="mb-3">
        <label class="form-label text-muted">{{ __('Status') }}</label>
        <div id="detail_status"></div>
      </div>

      <div class="mb-3">
        <label class="form-label text-muted">{{ __('My Remarks') }}</label>
        <p id="detail_remarks">-</p>
      </div>

      <div class="mb-3" id="admin_remarks_section" style="display: none;">
        <label class="form-label text-muted">{{ __('Admin Remarks') }}</label>
        <p id="detail_admin_remarks">-</p>
      </div>

      <div class="mb-3" id="receipt_section" style="display: none;">
        <!-- Content will be dynamically populated by JavaScript -->
      </div>

      <div class="mb-3">
        <label class="form-label text-muted">{{ __('Submitted On') }}</label>
        <p class="text-muted" id="detail_created_at">-</p>
      </div>

      <div class="d-flex gap-2" id="expense_actions">
        <button type="button" class="btn btn-label-danger" id="deleteExpenseBtn" style="display: none;">
          <i class="bx bx-trash me-1"></i>{{ __('Delete') }}
        </button>
      </div>
    </div>
  </div>
@endsection
