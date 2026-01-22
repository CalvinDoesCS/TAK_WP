@extends('layouts.layoutMaster')

@section('title', __('Provision Tenant Database'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Provision Tenant Database')"
    :breadcrumbs="[
        ['name' => __('Multi-Tenancy'), 'url' => route('multitenancycore.admin.dashboard')],
        ['name' => __('Database Provisioning'), 'url' => route('multitenancycore.admin.provisioning.index')],
        ['name' => $tenant->name, 'url' => '']
    ]" 
/>

<div class="row">
    {{-- Tenant Information --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Tenant Information') }}</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Name') }}:</dt>
                    <dd class="col-sm-8">{{ $tenant->name }}</dd>
                    
                    <dt class="col-sm-4">{{ __('Email') }}:</dt>
                    <dd class="col-sm-8">{{ $tenant->email }}</dd>
                    
                    <dt class="col-sm-4">{{ __('Subdomain') }}:</dt>
                    <dd class="col-sm-8">{{ $tenant->subdomain }}</dd>
                    
                    <dt class="col-sm-4">{{ __('Plan') }}:</dt>
                    <dd class="col-sm-8">{{ $tenant->subscription ? $tenant->subscription->plan->name : '-' }}</dd>
                    
                    <dt class="col-sm-4">{{ __('Status') }}:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-label-{{ $tenant->status === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">{{ __('Created') }}:</dt>
                    <dd class="col-sm-8">{{ $tenant->created_at->format('M d, Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    
    {{-- Provisioning Options --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Database Provisioning') }}</h5>
            </div>
            <div class="card-body">
                @if($tenant->database_provisioning_status === 'provisioned')
                    <div class="alert alert-success">
                        <i class="bx bx-check-circle me-2"></i>
                        {{ __('Database already provisioned') }}
                    </div>
                    
                    @if($tenant->database)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Database') }}:</dt>
                            <dd class="col-sm-8"><code>{{ $tenant->database->database_name }}</code></dd>

                            <dt class="col-sm-4">{{ __('Host') }}:</dt>
                            <dd class="col-sm-8">{{ $tenant->database->host }}:{{ $tenant->database->port }}</dd>

                            <dt class="col-sm-4">{{ __('Username') }}:</dt>
                            <dd class="col-sm-8"><code>{{ $tenant->database->username }}</code></dd>

                            <dt class="col-sm-4">{{ __('Provisioned') }}:</dt>
                            <dd class="col-sm-8">{{ $tenant->database->provisioned_at->format('M d, Y H:i') }}</dd>
                        </dl>
                    @endif
                @else
                    @if($autoProvisioningEnabled)
                        <div class="mb-4">
                            <h6>{{ __('Automatic Provisioning (VPS Mode)') }}</h6>
                            <p class="text-muted">{{ __('Automatically create database and run migrations.') }}</p>
                            <button type="button" class="btn btn-primary" onclick="autoProvision()">
                                <i class="bx bx-magic-wand me-2"></i>{{ __('Auto Provision Database') }}
                            </button>
                        </div>
                        
                        <hr>
                    @endif
                    
                    <div>
                        <h6>{{ __('Manual Provisioning (Shared Hosting)') }}</h6>
                        <p class="text-muted">{{ __('Manually configure database credentials.') }}</p>
                        
                        <form id="manualProvisionForm" onsubmit="manualProvision(event)">
                            <div class="mb-3">
                                <label class="form-label" for="host">{{ __('Database Host') }}</label>
                                <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="port">{{ __('Port') }}</label>
                                <input type="text" class="form-control" id="port" name="port" value="3306" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="database_name">{{ __('Database Name') }}</label>
                                <input type="text" class="form-control" id="database_name" name="database_name" required>
                                <small class="form-text text-muted">{{ __('Must be created beforehand in your hosting panel') }}</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="username">{{ __('Database Username') }}</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="password">{{ __('Database Password') }}</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-save me-2"></i>{{ __('Save & Provision') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($tenant->database_provisioning_status === 'failed')
<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <h6 class="alert-heading">{{ __('Previous Provisioning Failed') }}</h6>
            @if($tenant->database && $tenant->database->provisioning_error)
                <p class="mb-0">{{ $tenant->database->provisioning_error }}</p>
            @endif
        </div>
    </div>
</div>
@endif
@endsection

@section('page-script')
    @vite(['Modules/MultiTenancyCore/resources/assets/js/admin/provisioning-show.js'])
    <script>
        // Pass data from server to JavaScript
        window.pageData = {
            urls: {
                autoProvision: @json(route('multitenancycore.admin.provisioning.auto-provision', $tenant->id)),
                manualProvision: @json(route('multitenancycore.admin.provisioning.manual-provision', $tenant->id)),
                index: @json(route('multitenancycore.admin.provisioning.index'))
            },
            labels: {
                autoProvisionTitle: @json(__('Auto Provision Database?')),
                autoProvisionText: @json(__('This will create a new database and run migrations automatically.')),
                configureTitle: @json(__('Configure Database?')),
                configureText: @json(__('This will test the connection and run migrations.')),
                yesProvision: @json(__('Yes, provision')),
                yesConfigure: @json(__('Yes, configure')),
                cancel: @json(__('Cancel')),
                success: @json(__('Success!')),
                provisioningSuccess: @json(__('Database provisioned successfully'))
            }
        };
    </script>
@endsection