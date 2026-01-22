@extends('layouts.layoutMaster')

@section('title', __('Subscription Details'))

@section('content')
    <x-breadcrumb 
        :title="__('Subscription Details')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Subscriptions'), 'url' => route('multitenancycore.admin.subscriptions.index')],
            ['name' => __('Details'), 'url' => '']
        ]" 
    />

    <div class="row">
        <!-- Subscription Information -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Subscription Information') }}</h5>
                    
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Tenant:') }}</span>
                            <a href="{{ route('multitenancycore.admin.tenants.show', $subscription->tenant_id) }}">
                                {{ $subscription->tenant->name }}
                            </a>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Plan:') }}</span>
                            <span class="badge bg-label-primary">{{ $subscription->plan->name }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Status:') }}</span>
                            @include('multitenancycore::admin.subscriptions._status', ['subscription' => $subscription])
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Amount:') }}</span>
                            <span>{{ $subscription->formatted_amount }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Billing Period:') }}</span>
                            <span>{{ ucfirst($subscription->plan->billing_period) }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Start Date:') }}</span>
                            <span>{{ $subscription->starts_at->format('Y-m-d H:i') }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('End Date:') }}</span>
                            <span>
                                @if($subscription->ends_at)
                                    {{ $subscription->ends_at->format('Y-m-d H:i') }}
                                @else
                                    {{ __('Lifetime') }}
                                @endif
                            </span>
                        </li>
                        @if($subscription->cancelled_at)
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Cancelled At:') }}</span>
                                <span class="text-danger">{{ $subscription->cancelled_at->format('Y-m-d H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Plan Features -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Plan Features') }}</h5>

                    @if($subscription->plan && $subscription->plan->display_features)
                        <ul class="list-unstyled mb-0">
                            @foreach($subscription->plan->display_features as $feature)
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>{{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">{{ __('No features configured') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Related Payments') }}</h5>
                </div>
                <div class="card-body">
                    @if($subscription->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Reference') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscription->payments as $payment)
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
                                            <td>
                                                @if($payment->reference_number)
                                                    <code>{{ $payment->reference_number }}</code>
                                                @else
                                                    -
                                                @endif
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