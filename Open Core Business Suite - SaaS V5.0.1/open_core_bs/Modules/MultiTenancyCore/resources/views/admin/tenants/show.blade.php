@extends('layouts.layoutMaster')

@section('title', __('Tenant Details'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('content')
    <x-breadcrumb 
        :title="__('Tenant Details')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Tenants'), 'url' => route('multitenancycore.admin.tenants.index')],
            ['name' => $tenant->name, 'url' => '']
        ]" 
    />

    <div class="row">
        <!-- Tenant Information -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Tenant Information') }}</h5>
                    
                    <div class="info-container">
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Company:') }}</span>
                                <span>{{ $tenant->name }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Email:') }}</span>
                                <span>{{ $tenant->email }}</span>
                            </li>
                            @if($tenant->phone)
                                <li class="mb-3">
                                    <span class="h6 me-1">{{ __('Phone:') }}</span>
                                    <span>{{ $tenant->phone }}</span>
                                </li>
                            @endif
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Subdomain:') }}</span>
                                <span><code>{{ $tenant->subdomain }}</code></span>
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('UUID:') }}</span>
                                <span><code>{{ $tenant->uuid }}</code></span>
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Status:') }}</span>
                                @include('multitenancycore::admin.tenants._status', ['tenant' => $tenant])
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Created At:') }}</span>
                                <span>{{ $tenant->created_at->format('Y-m-d H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Database Information -->
            @if($tenant->database)
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Database Information') }}</h5>
                        
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Status:') }}</span>
                                @include('multitenancycore::admin.tenants._database-status', ['tenant' => $tenant])
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Type:') }}</span>
                                <span class="badge bg-label-primary">{{ ucfirst($tenant->database->provisioning_type) }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Name:') }}</span>
                                <span><code>{{ $tenant->database->database_name }}</code></span>
                            </li>
                            @if($tenant->database->isProvisioned())
                                <li class="mb-3">
                                    <span class="h6 me-1">{{ __('Provisioned At:') }}</span>
                                    <span>{{ $tenant->database->provisioned_at?->format('Y-m-d H:i') }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- Subscription History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Subscription History') }}</h5>
                </div>
                <div class="card-body">
                    @if($tenant->subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Plan') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Period') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenant->subscriptions as $subscription)
                                        <tr>
                                            <td>{{ $subscription->plan->name }}</td>
                                            <td>
                                                @php
                                                    $statusClasses = [
                                                        'trial' => 'bg-label-warning',
                                                        'active' => 'bg-label-success',
                                                        'cancelled' => 'bg-label-danger',
                                                        'expired' => 'bg-label-secondary'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $statusClasses[$subscription->status] ?? 'bg-label-secondary' }}">
                                                    {{ ucfirst($subscription->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $subscription->starts_at->format('Y-m-d') }} - 
                                                {{ $subscription->ends_at ? $subscription->ends_at->format('Y-m-d') : __('Lifetime') }}
                                            </td>
                                            <td>{{ $subscription->formatted_amount }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ __('No subscriptions found') }}</p>
                    @endif
                </div>
            </div>

            <!-- Payment History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Payment History') }}</h5>
                </div>
                <div class="card-body">
                    @if($tenant->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenant->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                                            <td>{{ $payment->formatted_amount }}</td>
                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                            <td>
                                                @php
                                                    $statusClasses = [
                                                        'pending' => 'bg-label-warning',
                                                        'approved' => 'bg-label-success',
                                                        'rejected' => 'bg-label-danger'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $statusClasses[$payment->status] ?? 'bg-label-secondary' }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ __('No payments found') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection