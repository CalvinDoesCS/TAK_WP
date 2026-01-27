@extends('layouts.layoutMaster')

@section('title', __('Support Center'))

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Support Center') }}</h2>
                    <p class="text-muted">{{ __('We\'re here to help you with any questions or issues') }}</p>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="row mb-5">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-lg mx-auto mb-3">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                <i class="bx bx-envelope"></i>
                            </span>
                        </div>
                        <h5 class="mb-2">{{ __('Email Support') }}</h5>
                        <p class="text-muted mb-3">{{ __('Get help via email') }}</p>
                        <a href="mailto:{{ $supportEmail }}" class="text-primary">{{ $supportEmail }}</a>
                        <p class="text-muted mt-2 mb-0">
                            <small>{{ __('Response time: 24-48 hours') }}</small>
                        </p>
                    </div>
                </div>
            </div>

            @if($supportPhone)
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="avatar avatar-lg mx-auto mb-3">
                                <span class="avatar-initial rounded-circle bg-label-success">
                                    <i class="bx bx-phone"></i>
                                </span>
                            </div>
                            <h5 class="mb-2">{{ __('Phone Support') }}</h5>
                            <p class="text-muted mb-3">{{ __('Talk to our team') }}</p>
                            <a href="tel:{{ $supportPhone }}" class="text-primary">{{ $supportPhone }}</a>
                            <p class="text-muted mt-2 mb-0">
                                <small>{{ $supportHours }}</small>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Common Topics --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Common Support Topics') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="supportAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#billing">
                                        <i class="bx bx-credit-card me-2"></i>{{ __('Billing & Payments') }}
                                    </button>
                                </h2>
                                <div id="billing" class="accordion-collapse collapse show" data-bs-parent="#supportAccordion">
                                    <div class="accordion-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <a href="{{ route('multitenancycore.tenant.billing') }}" class="text-primary">
                                                    {{ __('View payment history') }}
                                                </a>
                                            </li>
                                            <li class="mb-2">
                                                <a href="{{ route('multitenancycore.tenant.subscription') }}" class="text-primary">
                                                    {{ __('Manage subscription') }}
                                                </a>
                                            </li>
                                            <li class="mb-2">
                                                <a href="{{ route('multitenancycore.tenant.invoices') }}" class="text-primary">
                                                    {{ __('Download invoices') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account">
                                        <i class="bx bx-user me-2"></i>{{ __('Account Management') }}
                                    </button>
                                </h2>
                                <div id="account" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                    <div class="accordion-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <a href="{{ route('multitenancycore.tenant.profile') }}" class="text-primary">
                                                    {{ __('Update company profile') }}
                                                </a>
                                            </li>
                                            <li class="mb-2">
                                                <span class="text-muted">{{ __('Contact support to change email address or password') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical">
                                        <i class="bx bx-code-alt me-2"></i>{{ __('Technical Support') }}
                                    </button>
                                </h2>
                                <div id="technical" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                    <div class="accordion-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <a href="{{ route('multitenancycore.tenant.usage') }}" class="text-primary">
                                                    {{ __('Check resource usage') }}
                                                </a>
                                            </li>
                                            <li class="mb-2">
                                                <span class="text-muted">{{ __('For technical assistance, please contact support') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq">
                                        <i class="bx bx-help-circle me-2"></i>{{ __('Frequently Asked Questions') }}
                                    </button>
                                </h2>
                                <div id="faq" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <h6>{{ __('How do I upgrade my plan?') }}</h6>
                                            <p class="text-muted mb-0">
                                                {{ __('Go to your subscription page and click on "Change Plan" to select a new plan.') }}
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6>{{ __('How do I cancel my subscription?') }}</h6>
                                            <p class="text-muted mb-0">
                                                {{ __('You can cancel your subscription from the subscription page. Your service will remain active until the end of the billing period.') }}
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6>{{ __('Where can I find my invoices?') }}</h6>
                                            <p class="text-muted mb-0">
                                                {{ __('All your invoices are available in the Invoices section where you can view and download them.') }}
                                            </p>
                                        </div>
                                        <div>
                                            <h6>{{ __('How do I access my application?') }}</h6>
                                            <p class="text-muted mb-0">
                                                {{ __('Your application is accessible at your subdomain URL. You can find this in your company profile.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
