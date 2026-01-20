@extends('layouts.layoutMaster')

@section('title', __('Tenant Database Provisioning'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Database Provisioning')"
    :breadcrumbs="[
        ['name' => __('Multi-Tenancy'), 'url' => route('multitenancycore.admin.dashboard')],
        ['name' => __('Database Provisioning'), 'url' => '']
    ]" 
/>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('Pending Provisioning') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h3 class="mb-0 me-2" id="stat-pending">0</h3>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="bx bx-time"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('Failed Provisioning') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h3 class="mb-0 me-2" id="stat-failed">0</h3>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="bx bx-error-circle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('Provisioned Today') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h3 class="mb-0 me-2" id="stat-today">0</h3>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="bx bx-check-circle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('Total Active') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h3 class="mb-0 me-2" id="stat-active">0</h3>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-server"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-pending" aria-selected="true">
                    <i class="bx bx-time me-1"></i>{{ __('Pending') }}
                    <span class="badge bg-label-warning ms-1" id="badge-pending">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-history" aria-selected="false">
                    <i class="bx bx-history me-1"></i>{{ __('History') }}
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            {{-- Pending Tab --}}
            <div class="tab-pane fade show active" id="tab-pending" role="tabpanel">
                <div class="table-responsive">
                    <table class="datatables-provisioning table border-top">
                        <thead>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Plan') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            {{-- History Tab --}}
            <div class="tab-pane fade" id="tab-history" role="tabpanel">
                <div class="table-responsive">
                    <table class="datatables-history table border-top">
                        <thead>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Plan') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Provisioned') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
    @vite(['Modules/MultiTenancyCore/resources/assets/js/admin/provisioning-index.js'])
    <script>
        // Pass data from server to JavaScript
        window.pageData = {
            urls: {
                datatable: @json(route('multitenancycore.admin.provisioning.datatable')),
                history: @json(route('multitenancycore.admin.provisioning.history')),
                statistics: @json(route('multitenancycore.admin.provisioning.statistics')),
                show: @json(route('multitenancycore.admin.provisioning.show', ':id')),
                tenantDashboard: @json(route('multitenancycore.admin.tenants.show', ':id'))
            },
            labels: {
                confirmProvision: @json(__('Are you sure you want to provision this database?')),
                provisioning: @json(__('Provisioning...')),
                success: @json(__('Success!')),
                error: @json(__('Error')),
                refresh: @json(__('Refresh')),
                provisioningRequests: @json(__('Provisioning Requests')),
                noRecords: @json(__('No records found'))
            }
        };
    </script>
@endsection