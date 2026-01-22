@extends('layouts/layoutMaster')

@section('title', __('Audit Logs'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  ])
@endsection

@section('page-script')
  @vite(['resources/js/main-datatable.js'])
@endsection


@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Audit Logs')"
      :breadcrumbs="[
        ['name' => __('Audit Logs'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    {{-- Audit Log Table Card --}}
    <div class="card">
      <div class="card-datatable table-responsive">
        <table id="datatable" class="datatables-users table border-top">
          <thead>
          <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Event') }}</th>
            <th>{{ __('Ip') }}</th>
            <th>{{ __('Model') }}</th>
            <th>{{ __('Created At') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($auditLogs as $auditLog)
            <tr>
              <td>{{ $auditLog->id }}</td>
              <td>
                @if($auditLog->user == null)
                  <span class="text-muted">{{ __('N/A') }}</span>
                @else
                  <x-datatable-user :user="$auditLog->user" />
                @endif
              </td>
              <td>{{ $auditLog->event }}</td>
              <td>{{ $auditLog->ip_address }}</td>
              <td>{{ $auditLog->auditable_type }}</td>
              <td>{{ $auditLog->created_at }}</td>
              <td>
                <a href="{{ route('auditLogs.show', $auditLog->id) }}"
                   class="btn btn-icon">
                  <i class="bx bx-show"></i>
                </a>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
