@php
    $tenant = \Modules\MultiTenancyCore\App\Models\Tenant::where('email', auth()->user()->email)->first();
@endphp

@if($tenant && $tenant->subdomain)
<div class="container mb-4">
    <div class="row">
        <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ __('Welcome to your Tenant Portal') }}</h5>
                        <p class="text-muted mb-0">{{ __('Manage your subscription, billing, and access your application') }}</p>
                    </div>
                    
                    @if($tenant->database_provisioning_status === 'provisioned')
                        <a href="{{ $tenant->getSubdomainUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <i class="bx bx-link-external me-1"></i>
                            <span class="d-none d-sm-inline">{{ __('Access Your Application') }}</span>
                            <span class="d-inline d-sm-none">{{ __('Access App') }}</span>
                        </a>
                    @elseif($tenant->database_provisioning_status === 'pending')
                        <span class="badge bg-label-warning">
                            <i class="bx bx-time me-1"></i>{{ __('Application Provisioning...') }}
                        </span>
                    @elseif($tenant->database_provisioning_status === 'not_created')
                        <span class="badge bg-label-info">
                            <i class="bx bx-info-circle me-1"></i>{{ __('Awaiting Setup') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
@endif