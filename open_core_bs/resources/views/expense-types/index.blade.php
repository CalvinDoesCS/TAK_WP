@extends('layouts/layoutMaster')

@section('title', __('Expense Types'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
   'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
     'resources/assets/vendor/libs/@form-validation/auto-focus.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('hrcore.expense-types.datatable')),
        store: @json(route('hrcore.expense-types.store')),
        update: @json(route('hrcore.expense-types.update', ':id')),
        delete: @json(route('hrcore.expense-types.destroy', ':id'))
      },
      labels: {
        error: @json(__('Error')),
        success: @json(__('Success!')),
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('This expense type will be permanently deleted!')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleted: @json(__('Deleted!')),
        deleteSuccess: @json(__('Expense type has been deleted.')),
        deleteError: @json(__('Failed to delete expense type')),
        createSuccess: @json(__('Expense type created successfully')),
        updateSuccess: @json(__('Expense type updated successfully')),
        validationError: @json(__('Please fill in all required fields')),
        active: @json(__('Active')),
        inactive: @json(__('Inactive'))
      },
      status: {
        active: 'active',
        inactive: 'inactive'
      }
    };
  </script>
  @vite(['resources/assets/js/app/expense-types-index.js'])
@endsection

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('HR Core'), 'url' => '#']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Expense Types')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Expense Types') }}</h5>
        <button type="button" class="btn btn-primary" id="addExpenseTypeBtn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExpenseType">
          <i class="bx bx-plus me-1"></i>
          {{ __('Add Expense Type') }}
        </button>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-expenseTypes table border-top">
        <thead>
        <tr>
          <th></th>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Description') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExpenseType" aria-labelledby="offcanvasExpenseTypeLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasExpenseTypeLabel" class="offcanvas-title">{{ __('Add Expense Type') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form id="expenseTypeForm">
        <input type="hidden" id="expense_type_id" name="expense_type_id">

        <div class="mb-3">
          <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="description" class="form-label">{{ __('Description') }}</label>
          <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
          <label for="status" class="form-label">{{ __('Status') }}</label>
          <select id="status" name="status" class="form-select">
            <option value="active" selected>{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Save') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>
@endsection
