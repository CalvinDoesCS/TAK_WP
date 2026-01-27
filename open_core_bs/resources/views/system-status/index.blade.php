@extends('layouts/layoutMaster')

@section('title', __('System Status'))

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('Dashboard'), 'url' => route('superAdmin.dashboard')],
      ['name' => __('System Status'), 'url' => '']
    ];
  @endphp
  <x-breadcrumb :title="__('System Status')" :breadcrumbs="$breadcrumbs" />

  <div class="row">
    <!-- Application Info -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="bx bx-info-circle"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Application Info') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Application Name') }}</span>
              <span class="fw-medium">{{ $systemInfo['application']['name'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Version') }}</span>
              <span class="badge bg-primary">v{{ $systemInfo['application']['version'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Laravel Version') }}</span>
              <span class="fw-medium">{{ $systemInfo['application']['laravel_version'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Environment') }}</span>
              <span class="badge bg-label-{{ $systemInfo['application']['environment'] === 'production' ? 'success' : 'warning' }}">
                {{ ucfirst($systemInfo['application']['environment']) }}
              </span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Debug Mode') }}</span>
              @if($systemInfo['application']['debug_mode'])
                <span class="badge bg-label-danger">{{ __('Enabled') }}</span>
              @else
                <span class="badge bg-label-success">{{ __('Disabled') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Timezone') }}</span>
              <span class="fw-medium">{{ $systemInfo['application']['timezone'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2">
              <span class="text-muted">{{ __('Locale') }}</span>
              <span class="fw-medium">{{ strtoupper($systemInfo['application']['locale']) }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Server Info -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-info">
              <i class="bx bx-server"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Server Info') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('PHP Version') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['php_version'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Server Software') }}</span>
              <span class="fw-medium text-truncate ms-2" style="max-width: 200px;" title="{{ $systemInfo['server']['server_software'] }}">
                {{ $systemInfo['server']['server_software'] }}
              </span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Operating System') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['server_os'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Server Time') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['server_time'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Max Execution Time') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['max_execution_time'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Memory Limit') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['memory_limit'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Upload Max Filesize') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['upload_max_filesize'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2">
              <span class="text-muted">{{ __('Post Max Size') }}</span>
              <span class="fw-medium">{{ $systemInfo['server']['post_max_size'] }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Database Info -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-data"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Database Info') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Driver') }}</span>
              <span class="fw-medium">{{ $systemInfo['database']['driver'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Version') }}</span>
              <span class="fw-medium text-truncate ms-2" style="max-width: 200px;" title="{{ $systemInfo['database']['version'] }}">
                {{ $systemInfo['database']['version'] }}
              </span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Database Name') }}</span>
              <span class="fw-medium">{{ $systemInfo['database']['database'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Host') }}</span>
              <span class="fw-medium">{{ $systemInfo['database']['host'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2">
              <span class="text-muted">{{ __('Port') }}</span>
              <span class="fw-medium">{{ $systemInfo['database']['port'] }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Drivers Configuration -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-cog"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Drivers Configuration') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Cache Driver') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['cache'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Queue Driver') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['queue'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Session Driver') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['session'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Broadcasting Driver') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['broadcasting'] ?? 'null' }}</span>
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Mail Driver') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['mail'] }}</span>
            </li>
            <li class="d-flex justify-content-between py-2">
              <span class="text-muted">{{ __('Filesystem') }}</span>
              <span class="badge bg-label-secondary">{{ $systemInfo['drivers']['filesystem'] }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Services Status -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-pulse"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Services Status') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            @foreach($systemInfo['services'] as $service => $status)
              <li class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span class="text-muted">{{ ucfirst($service) }}</span>
                <div class="d-flex align-items-center">
                  @if($status['status'])
                    <span class="badge bg-success me-2">
                      <i class="bx bx-check me-1"></i>{{ __('OK') }}
                    </span>
                  @else
                    <span class="badge bg-label-secondary me-2">
                      <i class="bx bx-x me-1"></i>{{ __('N/A') }}
                    </span>
                  @endif
                  <small class="text-muted text-truncate" style="max-width: 120px;" title="{{ $status['message'] }}">{{ $status['message'] }}</small>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>

    <!-- Storage Permissions -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-secondary">
              <i class="bx bx-folder"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('Storage Permissions') }}</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Storage Directory') }}</span>
              @if($systemInfo['storage']['storage_writable'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Writable') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Not Writable') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Storage Link') }}</span>
              @if($systemInfo['storage']['storage_link_exists'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Exists') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Missing') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Logs Directory') }}</span>
              @if($systemInfo['storage']['logs_writable'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Writable') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Not Writable') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Cache Directory') }}</span>
              @if($systemInfo['storage']['cache_writable'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Writable') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Not Writable') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">{{ __('Sessions Directory') }}</span>
              @if($systemInfo['storage']['sessions_writable'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Writable') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Not Writable') }}</span>
              @endif
            </li>
            <li class="d-flex justify-content-between py-2">
              <span class="text-muted">{{ __('Views Directory') }}</span>
              @if($systemInfo['storage']['views_writable'])
                <span class="badge bg-success"><i class="bx bx-check me-1"></i>{{ __('Writable') }}</span>
              @else
                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>{{ __('Not Writable') }}</span>
              @endif
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- PHP Extensions -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header d-flex align-items-center">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="bx bx-extension"></i>
            </span>
          </div>
          <h5 class="mb-0">{{ __('PHP Extensions') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            @foreach($systemInfo['php_extensions'] as $extension => $loaded)
              <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                <div class="d-flex align-items-center">
                  @if($loaded)
                    <i class="bx bx-check-circle text-success me-2"></i>
                  @else
                    <i class="bx bx-x-circle text-danger me-2"></i>
                  @endif
                  <span class="{{ $loaded ? '' : 'text-muted' }}">{{ $extension }}</span>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Modules -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-package"></i>
              </span>
            </div>
            <h5 class="mb-0">{{ __('Modules') }}</h5>
          </div>
          <div>
            <span class="badge bg-success me-2">{{ $systemInfo['modules']['enabled'] }} {{ __('Enabled') }}</span>
            <span class="badge bg-secondary">{{ $systemInfo['modules']['disabled'] }} {{ __('Disabled') }}</span>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            @foreach($systemInfo['modules']['list'] as $module)
              <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                <div class="d-flex align-items-center">
                  @if($module['enabled'])
                    <i class="bx bx-check-circle text-success me-2"></i>
                  @else
                    <i class="bx bx-x-circle text-secondary me-2"></i>
                  @endif
                  <span class="{{ $module['enabled'] ? '' : 'text-muted' }}">{{ $module['name'] }}</span>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
