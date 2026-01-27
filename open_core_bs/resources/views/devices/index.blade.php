@extends('layouts.layoutMaster')

@section('title', __('Devices'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
  <x-breadcrumb :title="__('Devices')" :breadcrumbs="$breadcrumbs" />

  {{-- Filters --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <label for="userFilter" class="form-label">{{ __('Filter by User') }}</label>
          <select class="form-select select2" id="userFilter">
            <option value="">{{ __('All Users') }}</option>
            @foreach ($users as $user)
              <option value="{{ $user->id }}">{{ $user->getFullName() }} ({{ $user->code }})</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  {{-- Devices Table --}}
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ __('Registered Devices') }}</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="dt-responsive table" id="devicesTable">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Device Type') }}</th>
            <th>{{ __('Device Info') }}</th>
            <th>{{ __('App Version') }}</th>
            <th>{{ __('Registered At') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- Device Details Offcanvas --}}
  @include('_partials._modals.device.show_device_details')
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/device-index.js'])
  <script>
    // Pass data from PHP to JavaScript
    window.pageData = {
      urls: {
        datatable: "{{ route('devices.datatable') }}",
        show: "{{ route('devices.getByIdAjax', ':id') }}",
        destroy: "{{ route('devices.deleteAjax', ':id') }}"
      },
      labels: {
        allUsers: @json(__('All Users')),
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        cancelButton: @json(__('Cancel')),
        deleted: @json(__('Deleted!')),
        deviceDeleted: @json(__('The device has been deleted!')),
        error: @json(__('Error!')),
        somethingWentWrong: @json(__('Something went wrong!')),
        viewDetails: @json(__('View Details')),
        delete: @json(__('Delete')),
        loading: @json(__('Loading...')),
        user: @json(__('User')),
        appVersion: @json(__('App Version')),
        sdkVersion: @json(__('SDK Version')),
        registeredAt: @json(__('Registered At')),
        location: @json(__('Last Known Location')),
        viewOnMap: @json(__('View on Map')),
        close: @json(__('Close'))
      }
    };

    // Export global functions for inline onclick handlers
    window.viewDeviceDetails = function(id) {
      if (window.DeviceManager) {
        window.DeviceManager.viewDeviceDetails(id);
      }
    };

    window.deleteDevice = function(id) {
      if (window.DeviceManager) {
        window.DeviceManager.deleteDevice(id);
      }
    };
  </script>
@endsection
