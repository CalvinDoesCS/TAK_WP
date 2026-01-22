@extends('layouts.layoutMaster')

@php
    $pageConfigs = ['myLayout' => 'horizontal'];
@endphp

@section('title', __('Account Pending Approval'))

@section('content')
<section class="section-py bg-body first-section-pt">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <!-- Icon -->
                        <div class="mb-4">
                            <span class="badge badge-center rounded-pill bg-label-warning" style="width: 80px; height: 80px;">
                                <i class="bx bx-time-five bx-lg"></i>
                            </span>
                        </div>

                        <!-- Title -->
                        <h3 class="mb-3">{{ __('Account Pending Approval') }}</h3>

                        <!-- Message -->
                        <p class="text-muted mb-4">
                            {{ __('Thank you for registering! Your account is currently being reviewed by our team.') }}
                        </p>
                        <p class="text-muted mb-4">
                            {{ __('You will receive an email notification once your account has been approved.') }}
                        </p>

                        <!-- Tenant Info -->
                        @if(isset($tenant))
                        <div class="bg-lighter rounded p-3 mb-4">
                            <div class="row text-start">
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted d-block">{{ __('Company') }}</small>
                                    <span>{{ $tenant->name }}</span>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted d-block">{{ __('Email') }}</small>
                                    <span>{{ $tenant->email }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">{{ __('Registered') }}</small>
                                    <span>{{ $tenant->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">{{ __('Status') }}</small>
                                    <span class="badge bg-label-warning">{{ __('Pending') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('auth.logout') }}" class="btn btn-label-secondary"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bx bx-log-out me-1"></i>{{ __('Logout') }}
                            </a>
                            <a href="mailto:{{ config('mail.from.address') }}" class="btn btn-label-primary">
                                <i class="bx bx-envelope me-1"></i>{{ __('Contact Support') }}
                            </a>
                        </div>

                        <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>

                <!-- Help Text -->
                <p class="text-center text-muted mt-4">
                    {{ __('Need help?') }}
                    <a href="mailto:{{ config('mail.from.address') }}">{{ __('Contact our support team') }}</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
