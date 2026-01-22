@extends('layouts.layoutMaster')

@section('title', __('Tenant Dashboard'))

@section('content')
    @include('multitenancycore::partials.tenant-breadcrumb')
    
    <div class="container">
        {{-- Email Verification Alert --}}
        @if(!auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning alert-dismissible" role="alert">
                <h5 class="alert-heading mb-2">
                    <i class="bx bx-envelope me-2"></i>{{ __('Verify your email address') }}
                </h5>
                <p class="mb-0">{{ __('Please verify your email address to access all features. We have sent a verification link to :email', ['email' => auth()->user()->email]) }}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Welcome Section --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h2 class="mb-3">{{ __('Welcome to :app, :name!', ['app' => config('app.name'), 'name' => auth()->user()->first_name]) }}</h2>
                        <p class="text-muted mb-4">{{ __('Manage your subscription, billing, and company settings from this dashboard.') }}</p>

                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-primary">
                                <i class="bx bx-credit-card me-2"></i>{{ __('Manage Subscription') }}
                            </a>
                            <a href="{{ route('multitenancycore.tenant.profile') }}" class="btn btn-label-primary">
                                <i class="bx bx-building me-2"></i>{{ __('Company Profile') }}
                            </a>
                            <a href="{{ route('multitenancycore.tenant.support') }}" class="btn btn-label-secondary">
                                <i class="bx bx-help-circle me-2"></i>{{ __('Get Support') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mt-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1">
                                <span class="fw-medium d-block mb-1">{{ __('Current Plan') }}</span>
                                <h3 class="card-title mb-2">{{ ($subscription && $subscription->plan) ? $subscription->plan->name : __('No Plan') }}</h3>
                                <small class="text-success fw-medium">
                                    @if($subscription && $subscription->status === 'trial' && $subscription->trial_ends_at)
                                        @php
                                            $daysRemaining = now()->diffInDays($subscription->trial_ends_at, false);
                                            $daysRemaining = max(0, ceil($daysRemaining));
                                        @endphp
                                        <i class="bx bx-time"></i> {{ __('Trial ends in :days days', ['days' => $daysRemaining]) }}
                                    @elseif($subscription && $subscription->status === 'active')
                                        <i class="bx bx-check-circle"></i> {{ __('Active') }}
                                    @else
                                        <i class="bx bx-x-circle"></i> {{ __('Inactive') }}
                                    @endif
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-credit-card"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1">
                                <span class="fw-medium d-block mb-1">{{ __('Next Payment') }}</span>
                                <h3 class="card-title mb-2">
                                    @if($subscription && $subscription->next_payment_date && $subscription->plan)
                                        {{ $subscription->plan->formatted_price }}
                                    @else
                                        {{ __('N/A') }}
                                    @endif
                                </h3>
                                <small class="text-muted fw-medium">
                                    @if($subscription && $subscription->next_payment_date)
                                        {{ $subscription->next_payment_date->format('M d, Y') }}
                                    @else
                                        {{ __('No upcoming payment') }}
                                    @endif
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1">
                                <span class="fw-medium d-block mb-1">{{ __('Users') }}</span>
                                <h3 class="card-title mb-2">{{ $userCount ?? 1 }}</h3>
                                <small class="text-muted fw-medium">
                                    @if($subscription && $subscription->plan && !$subscription->plan->hasUnlimitedUsers())
                                        {{ __('of :max allowed', ['max' => $subscription->plan->getMaxUsers()]) }}
                                    @else
                                        {{ __('Unlimited') }}
                                    @endif
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-user"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions & Recent Activity --}}
        <div class="row mt-4">
            {{-- Quick Actions --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('multitenancycore.tenant.billing') }}" class="btn btn-outline-primary text-start">
                                <i class="bx bx-receipt me-2"></i>{{ __('View Billing History') }}
                            </a>
                            <a href="{{ route('multitenancycore.tenant.invoices') }}" class="btn btn-outline-primary text-start">
                                <i class="bx bx-file me-2"></i>{{ __('Download Invoices') }}
                            </a>
                            <a href="{{ route('multitenancycore.tenant.usage') }}" class="btn btn-outline-primary text-start">
                                <i class="bx bx-chart me-2"></i>{{ __('Check Usage') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subscription Details --}}
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Subscription Details') }}</h5>
                        @if($subscription)
                            <span class="badge bg-label-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'trial' ? 'info' : 'warning') }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($subscription)
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="text-nowrap">{{ __('Plan') }}</td>
                                            <td class="fw-medium">{{ $subscription->plan ? $subscription->plan->name : __('N/A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-nowrap">{{ __('Price') }}</td>
                                            <td class="fw-medium">
                                                @if($subscription->plan)
                                                    {{ $subscription->plan->formatted_price }}/{{ $subscription->plan->billing_period }}
                                                @else
                                                    {{ __('N/A') }}
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-nowrap">{{ __('Started') }}</td>
                                            <td class="fw-medium">{{ $subscription->created_at->format('M d, Y') }}</td>
                                        </tr>
                                        @if($subscription->trial_ends_at && $subscription->status === 'trial')
                                            <tr>
                                                <td class="text-nowrap">{{ __('Trial Ends') }}</td>
                                                <td class="fw-medium">{{ $subscription->trial_ends_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endif
                                        @if($subscription->next_payment_date)
                                            <tr>
                                                <td class="text-nowrap">{{ __('Next Billing') }}</td>
                                                <td class="fw-medium">{{ $subscription->next_payment_date->format('M d, Y') }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-primary">
                                    {{ __('Manage Subscription') }}
                                </a>
                            </div>
                        @else
                            <p class="text-center text-muted py-5">
                                {{ __('No active subscription found.') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
