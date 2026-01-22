@extends('layouts.layoutMaster')

@section('title', __('Usage Overview'))

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Usage Overview') }}</h2>
                    <p class="text-muted">{{ __('Monitor your resource usage and limits') }}</p>
                </div>
            </div>
        </div>

        {{-- Plan Information --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('Current Plan') }}</h5>
                        @if($subscription && $plan)
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1">{{ $plan->name }}</h4>
                                    <p class="text-muted mb-0">{{ $plan->description }}</p>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-1">{{ $plan->formatted_price }}</h4>
                                    <p class="text-muted mb-0">{{ __('per :period', ['period' => $plan->billing_period]) }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('No active subscription found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Usage Cards --}}
        <div class="row">
            {{-- Users Usage --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">{{ __('Users') }}</h6>
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-user"></i>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('Used') }}</span>
                                <span class="fw-medium">
                                    {{ $usage['users']['current'] }} 
                                    @if(is_numeric($usage['users']['limit']))
                                        / {{ $usage['users']['limit'] }}
                                    @endif
                                </span>
                            </div>
                            @if(is_numeric($usage['users']['limit']))
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ $usage['users']['percentage'] }}%"
                                         aria-valuenow="{{ $usage['users']['percentage'] }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-label-success">{{ __('Unlimited') }}</span>
                            @endif
                        </div>
                        
                        @if(is_numeric($usage['users']['limit']) && $usage['users']['percentage'] >= 90)
                            <div class="alert alert-warning py-2 px-3 mb-0" role="alert">
                                <small>{{ __('You are approaching your user limit') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Storage Usage --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">{{ __('Storage') }}</h6>
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-hdd"></i>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('Used') }}</span>
                                <span class="fw-medium">
                                    {{ number_format($usage['storage']['current'] / 1024 / 1024 / 1024, 2) }} GB
                                    @if(is_numeric($usage['storage']['limit']))
                                        / {{ $usage['storage']['limit'] }} GB
                                    @endif
                                </span>
                            </div>
                            @if(is_numeric($usage['storage']['limit']))
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: {{ $usage['storage']['percentage'] }}%"
                                         aria-valuenow="{{ $usage['storage']['percentage'] }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-label-success">{{ __('Unlimited') }}</span>
                            @endif
                        </div>
                        
                        @if(is_numeric($usage['storage']['limit']) && $usage['storage']['percentage'] >= 90)
                            <div class="alert alert-warning py-2 px-3 mb-0" role="alert">
                                <small>{{ __('You are approaching your storage limit') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Plan Features --}}
        @if($plan && $plan->features)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Plan Features') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($plan->display_features as $feature)
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-check-circle text-success me-2" style="font-size: 1.25rem;"></i>
                                            <span>{{ $feature }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Upgrade CTA --}}
        @if($plan && ($usage['users']['percentage'] >= 80 || $usage['storage']['percentage'] >= 80))
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center py-4">
                            <h5 class="text-white mb-3">{{ __('Need more resources?') }}</h5>
                            <p class="text-white mb-4">{{ __('Upgrade your plan to get more users, storage, and API calls.') }}</p>
                            <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-light">
                                {{ __('Upgrade Plan') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection