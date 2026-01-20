@extends('layouts.layoutMaster')

@section('title', __('Manage Subscription'))

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
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Manage Your Subscription') }}</h2>
                    <p class="text-muted">{{ __('View your current plan, upgrade or downgrade, and manage billing') }}</p>
                </div>
            </div>
        </div>

        {{-- Current Subscription --}}
        @if($currentSubscription)
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Current Subscription') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">{{ $currentSubscription->plan->name }}</h6>
                                    <div class="mb-2">
                                        <strong>{{ __('Status') }}:</strong>
                                        <span class="badge bg-label-{{ $currentSubscription->status === 'active' ? 'success' : ($currentSubscription->status === 'trial' ? 'info' : 'warning') }}">
                                            {{ ucfirst($currentSubscription->status) }}
                                        </span>
                                        @if($currentSubscription->cancel_at_period_end)
                                            <span class="badge bg-label-danger ms-2">{{ __('Cancelling') }}</span>
                                        @endif
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ __('Price') }}:</strong> {{ $currentSubscription->plan->formatted_price }}/{{ $currentSubscription->plan->billing_period }}
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ __('Started') }}:</strong> {{ $currentSubscription->created_at->format('M d, Y') }}
                                    </div>
                                    @if($tenant && $tenant->database_provisioning_status === 'provisioned')
                                        <div class="mb-2">
                                            <strong>{{ __('Your Application URL') }}:</strong> 
                                            <a href="{{ $tenant->getSubdomainUrl() }}" target="_blank" rel="noopener noreferrer" class="text-primary">
                                                <i class="bx bx-link-external"></i> {{ $tenant->getSubdomainUrl() }}
                                            </a>
                                        </div>
                                    @elseif($tenant && $tenant->database_provisioning_status === 'pending')
                                        <div class="mb-2">
                                            <strong>{{ __('Application Status') }}:</strong> 
                                            <span class="badge bg-label-warning">{{ __('Database Provisioning Pending') }}</span>
                                        </div>
                                    @endif
                                    @if($currentSubscription->status === 'trial' && $currentSubscription->trial_ends_at)
                                        <div class="mb-2">
                                            <strong>{{ __('Trial Ends') }}:</strong> {{ $currentSubscription->trial_ends_at->format('M d, Y') }}
                                            @php
                                                $daysRemaining = now()->diffInDays($currentSubscription->trial_ends_at, false);
                                                $daysRemaining = max(0, ceil($daysRemaining));
                                            @endphp
                                            <small class="text-muted">({{ $daysRemaining }} {{ __('days remaining') }})</small>
                                        </div>
                                    @endif
                                    @if($currentSubscription->ends_at)
                                        <div class="mb-2">
                                            <strong>{{ __('Next Billing') }}:</strong> {{ $currentSubscription->ends_at->format('M d, Y') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">{{ __('Plan Features') }}</h6>
                                    @if($currentSubscription->plan)
                                        <ul class="list-unstyled">
                                            @foreach($currentSubscription->plan->display_features as $feature)
                                                <li><i class="bx bx-check text-success me-2"></i>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted">{{ __('Plan details not available') }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            @if(!$currentSubscription->cancel_at_period_end)
                                <div class="mt-4">
                                    <button type="button" class="btn btn-label-danger" onclick="showCancelModal()">
                                        <i class="bx bx-x-circle me-2"></i>{{ __('Cancel Subscription') }}
                                    </button>
                                </div>
                            @else
                                <div class="alert alert-warning mt-4" role="alert">
                                    <h6 class="alert-heading mb-1">{{ __('Subscription Scheduled for Cancellation') }}</h6>
                                    <p class="mb-2">
                                        @if($currentSubscription->ends_at)
                                            {{ __('Your subscription will end on :date', ['date' => $currentSubscription->ends_at->format('M d, Y')]) }}
                                        @else
                                            {{ __('Your subscription has been scheduled for cancellation.') }}
                                        @endif
                                    </p>
                                    <form action="{{ route('multitenancycore.tenant.subscription.resume') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Resume Subscription') }}</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row mb-5">
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading mb-1">{{ __('No Active Subscription') }}</h6>
                        <p class="mb-0">{{ __('You do not have an active subscription. Choose a plan below to get started.') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Available Plans --}}
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="mb-4">{{ __('Available Plans') }}</h4>
            </div>
            @foreach($plans as $plan)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 {{ $currentSubscription && $currentSubscription->plan_id == $plan->id ? 'border-primary' : '' }}">
                        @if($plan->is_featured)
                            <div class="card-header text-center bg-primary text-white">
                                <span class="badge bg-white text-primary">{{ __('Most Popular') }}</span>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h5 class="mb-2">{{ $plan->name }}</h5>
                                <div class="d-flex justify-content-center align-items-baseline">
                                    <h2 class="price mb-0">{{ $plan->formatted_price }}</h2>
                                    <sub class="text-muted ms-2">/{{ $plan->billing_period }}</sub>
                                </div>
                                @if($plan->description)
                                    <p class="text-muted mt-2">{{ $plan->description }}</p>
                                @endif
                            </div>
                            
                            <ul class="list-unstyled mb-4">
                                @foreach($plan->display_features as $feature)
                                    <li class="mb-2"><i class="bx bx-check text-success me-2"></i>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            
                            <div class="d-grid">
                                @if($currentSubscription && $currentSubscription->plan_id == $plan->id)
                                    <button class="btn btn-label-primary" disabled>{{ __('Current Plan') }}</button>
                                @else
                                    <a href="{{ route('multitenancycore.tenant.subscription.select-plan', $plan->id) }}" class="btn btn-primary">
                                        @if(!$currentSubscription)
                                            {{ __('Get Started') }}
                                        @elseif($currentSubscription->plan->price < $plan->price)
                                            {{ __('Upgrade') }}
                                        @else
                                            {{ __('Downgrade') }}
                                        @endif
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Subscription History --}}
        @if($subscriptionHistory->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Subscription History') }}</h5>
                        </div>
                        <div class="card-body">
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
                                        @foreach($subscriptionHistory as $subscription)
                                            <tr>
                                                <td>{{ $subscription->plan->name }}</td>
                                                <td>
                                                    <span class="badge bg-label-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'cancelled' ? 'danger' : 'secondary') }}">
                                                        {{ ucfirst($subscription->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ $subscription->created_at->format('M d, Y') }} -
                                                    {{ $subscription->ends_at ? $subscription->ends_at->format('M d, Y') : __('Ongoing') }}
                                                </td>
                                                <td>{{ $subscription->plan->formatted_price }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

{{-- Cancel Subscription Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Cancel Subscription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('multitenancycore.tenant.subscription.cancel') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <h6 class="alert-heading mb-1">{{ __('Are you sure?') }}</h6>
                        <p class="mb-0">{{ __('Your subscription will remain active until the end of the current billing period.') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" for="reason">{{ __('Please tell us why you are cancelling') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Keep Subscription') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Cancel Subscription') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function showCancelModal() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>
@endsection