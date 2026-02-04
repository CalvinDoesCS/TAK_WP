@extends('layouts.layoutMaster')

@section('title', __('My Compensatory Offs'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js'
])
@endsection

@section('content')

<x-breadcrumb
  :title="__('My Compensatory Offs')"
  :breadcrumbs="[
    ['name' => __('Self Service'), 'url' => ''],
    ['name' => __('My Compensatory Offs'), 'url' => '']
  ]"
/>

<div class="row mb-4">
  <!-- Statistics Cards -->
  <div class="col-md col-sm-6 mb-4">
    <div class="card" data-stat="total_earned">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text text-muted">{{ __('Total Earned') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2">{{ $statistics['total_earned'] ?? 0 }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-time-five bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md col-sm-6 mb-4">
    <div class="card" data-stat="available">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text text-muted">{{ __('Available') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2 text-success">{{ $statistics['available'] ?? 0 }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-check-circle bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md col-sm-6 mb-4">
    <div class="card" data-stat="used">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text text-muted">{{ __('Used') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2 text-info">{{ $statistics['used'] ?? 0 }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-calendar-minus bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md col-sm-6 mb-4">
    <div class="card" data-stat="expired">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text text-muted">{{ __('Expired') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2 text-danger">{{ $statistics['expired'] ?? 0 }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-danger rounded p-2">
              <i class="bx bx-x-circle bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ __('My Compensatory Offs') }}</h5>
      <button type="button" class="btn btn-primary" onclick="showRequestForm()">
        <i class="bx bx-plus me-1"></i> {{ __('Request Comp Off') }}
      </button>
    </div>
  </div>

  <div class="card-body">
    <!-- Filters Row -->
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">{{ __('Status') }}</label>
        <select id="filterStatus" class="form-select">
          <option value="">{{ __('All Status') }}</option>
          <option value="pending">{{ __('Pending') }}</option>
          <option value="approved">{{ __('Approved') }}</option>
          <option value="rejected">{{ __('Rejected') }}</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Date From') }}</label>
        <input type="date" id="filterDateFrom" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Date To') }}</label>
        <input type="date" id="filterDateTo" class="form-control">
      </div>
    </div>

    <!-- DataTable -->
    <div class="table-responsive">
      <table id="compOffsTable" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('Worked Date') }}</th>
            <th>{{ __('Hours Worked') }}</th>
            <th>{{ __('Comp Off Days') }}</th>
            <th>{{ __('Expiry Date') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Usage Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Offcanvas for Comp Off Request Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="compOffFormOffcanvas">
  <div class="offcanvas-header">
    <h5 id="compOffFormTitle" class="offcanvas-title">{{ __('Request Compensatory Off') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form id="compOffForm">
      <div class="mb-3">
        <label for="worked_date" class="form-label">{{ __('Worked Date') }} <span class="text-danger">*</span></label>
        <input type="text" id="worked_date" name="worked_date" class="form-control" required>
        <small class="form-text text-muted">{{ __('Date when you worked extra hours') }}</small>
      </div>

      <div class="mb-3">
        <label for="hours_worked" class="form-label">{{ __('Hours Worked') }} <span class="text-danger">*</span></label>
        <input type="number" id="hours_worked" name="hours_worked" class="form-control" min="0.5" max="24" step="0.5" required>
        <small class="form-text text-muted">{{ __('Total extra hours worked') }}</small>
      </div>

      <div class="mb-3">
        <label for="comp_off_days" class="form-label">{{ __('Comp Off Days') }} <span class="text-danger">*</span></label>
        <input type="number" id="comp_off_days" name="comp_off_days" class="form-control" min="0.5" max="5" step="0.5" required readonly>
        <small class="form-text text-muted">{{ __('Automatically calculated (8 hours = 1 day)') }}</small>
      </div>

      <div class="mb-3">
        <label for="reason" class="form-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
        <textarea id="reason" name="reason" class="form-control" rows="4" required maxlength="1000"></textarea>
        <small class="form-text text-muted">{{ __('Explain why you worked extra hours') }}</small>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">{{ __('Submit Request') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>

<!-- Offcanvas for Comp Off Details -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="compOffDetailsOffcanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">{{ __('Compensatory Off Details') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body" id="compOffDetailsContent">
    <div class="text-center">
      <div class="spinner-border spinner-border-sm" role="status">
        <span class="visually-hidden">{{ __('Loading...') }}</span>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
window.pageData = {
  urls: {
    datatable: @json(route('hrcore.my.compensatory-offs.datatable')),
    request: @json(route('hrcore.my.compensatory-offs.request')),
    show: @json(route('hrcore.compensatory-offs.show', ['id' => '__ID__'])),
    editData: @json(route('hrcore.my.compensatory-offs.edit-data', ['id' => '__ID__'])),
    update: @json(route('hrcore.my.compensatory-offs.update', ['id' => '__ID__'])),
    statistics: @json(route('hrcore.compensatory-offs.statistics'))
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
    success: @json(__('Success')),
    error: @json(__('Error')),
    requested: @json(__('Compensatory off request submitted successfully')),
    confirmSubmit: @json(__('Submit Request?')),
    submitButton: @json(__('Submit')),
    submitRequest: @json(__('Submit Request')),
    cancel: @json(__('Cancel')),
    comingSoon: @json(__('Coming Soon')),
    editFunctionalitySoon: @json(__('Edit functionality will be available soon')),
    requestCompOff: @json(__('Request Compensatory Off')),
    editCompOff: @json(__('Edit Compensatory Off')),
    updateRequest: @json(__('Update Request'))
  }
};
</script>
@vite('resources/assets/js/app/my-comp-offs.js')
@endsection
