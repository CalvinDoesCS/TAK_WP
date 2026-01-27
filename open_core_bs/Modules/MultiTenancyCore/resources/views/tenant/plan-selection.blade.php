@extends('layouts.layoutMaster')

@php
    $pageConfigs = ['myLayout' => 'horizontal'];
@endphp

@section('title', __('Select Your Plan'))

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/page-pricing.scss'
    ])
@endsection

@section('content')
<section class="section-py bg-body first-section-pt">
    <div class="container">
        {{-- Header --}}
        <div class="text-center mb-5">
            <h2 class="mb-2">{{ __('Choose Your Plan') }}</h2>
            <p class="text-muted">{{ __('Select the plan that best fits your needs') }}</p>
            @if($isTrialEligible && $trialEnabled)
                <span class="badge bg-label-success mt-2">
                    <i class="bx bx-gift me-1"></i>{{ __(':days Day Trial Available', ['days' => $trialDays]) }}
                </span>
            @endif
        </div>

        {{-- Pending Payment Notice --}}
        @if($pendingPayment)
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="card border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-label-warning me-3">
                                    <i class="bx bx-time-five"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ __('Payment Pending Verification') }}</h6>
                                    <p class="mb-0 text-muted">
                                        {{ __('You have a pending payment for the :plan plan.', ['plan' => $pendingPayment->newPlan?->name ?? 'selected']) }}
                                        @if($pendingPayment->proof_document_path)
                                            {{ __('Your payment proof has been submitted and is awaiting admin verification.') }}
                                        @else
                                            {{ __('Please complete your payment or upload proof of payment.') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="ms-3">
                                    @if(!$pendingPayment->proof_document_path)
                                        <a href="{{ route('multitenancycore.tenant.payment.instructions', $pendingPayment->id) }}" class="btn btn-warning btn-sm">
                                            <i class="bx bx-upload me-1"></i>{{ __('Upload Proof') }}
                                        </a>
                                    @else
                                        <a href="{{ route('multitenancycore.tenant.billing') }}" class="btn btn-outline-warning btn-sm">
                                            <i class="bx bx-show me-1"></i>{{ __('View Status') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Plan Cards Grid --}}
        <div class="row justify-content-center">
            @foreach($plans as $plan)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column">
                            <h4 class="mb-3">{{ $plan->name }}</h4>

                            {{-- Pricing --}}
                            <div class="my-4">
                                <div class="d-flex justify-content-center align-items-baseline">
                                    <h2 class="price mb-0">
                                        {{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($plan->price) }}
                                    </h2>
                                </div>
                                <span class="text-muted">/{{ $plan->billing_period }}</span>
                            </div>

                            @if($plan->description)
                                <p class="text-muted mb-4">{{ $plan->description }}</p>
                            @endif

                            {{-- Features List --}}
                            <ul class="list-unstyled mb-4 text-start flex-grow-1">
                                @foreach($plan->display_features as $feature)
                                    <li class="mb-2">
                                        <i class="bx bx-check text-primary me-2"></i>{{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Trial Badge if applicable --}}
                            @if($isTrialEligible && $trialEnabled && !$plan->isFree())
                                <p class="text-success mb-3">
                                    <i class="bx bx-gift me-1"></i>
                                    {{ $trialDays }} {{ __('days free trial') }}
                                </p>
                            @endif

                            {{-- Select Button --}}
                            <div class="mt-auto">
                                <form action="{{ route('multitenancycore.tenant.plan-selection.submit') }}"
                                      method="POST"
                                      class="plan-select-form"
                                      data-plan-id="{{ $plan->id }}"
                                      data-plan-name="{{ $plan->name }}"
                                      data-requires-payment="{{ (!$plan->isFree() && (!$isTrialEligible || !$trialEnabled || $requirePaymentForTrial)) ? 'true' : 'false' }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="payment_method" value="">
                                    <button type="submit" class="btn btn-primary w-100">
                                        @if($plan->isFree())
                                            {{ __('Start Free') }}
                                        @elseif($isTrialEligible && $trialEnabled)
                                            {{ __('Start Free Trial') }}
                                        @else
                                            {{ __('Select Plan') }}
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Additional Information --}}
        <div class="row justify-content-center mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="avatar-initial rounded bg-label-primary mb-2">
                                        <i class="bx bx-credit-card"></i>
                                    </span>
                                    <h6 class="mb-1">{{ __('Flexible Billing') }}</h6>
                                    <small class="text-muted">{{ __('Change plans anytime') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="avatar-initial rounded bg-label-success mb-2">
                                        <i class="bx bx-shield-quarter"></i>
                                    </span>
                                    <h6 class="mb-1">{{ __('Secure Payments') }}</h6>
                                    <small class="text-muted">{{ __('SSL encrypted transactions') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="avatar-initial rounded bg-label-info mb-2">
                                        <i class="bx bx-support"></i>
                                    </span>
                                    <h6 class="mb-1">{{ __('24/7 Support') }}</h6>
                                    <small class="text-muted">{{ __('Always here to help') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FAQ Section --}}
        <div class="row justify-content-center mt-5">
            <div class="col-lg-8">
                <h4 class="text-center mb-4">{{ __('Frequently Asked Questions') }}</h4>
                <div class="accordion" id="planFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                {{ __('Can I change my plan later?') }}
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#planFaq">
                            <div class="accordion-body">
                                {{ __('Yes, you can upgrade or downgrade your plan at any time. When upgrading, you will be charged the prorated difference. When downgrading, the change will take effect at the end of your current billing period.') }}
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                {{ __('What happens after the trial ends?') }}
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#planFaq">
                            <div class="accordion-body">
                                {{ __('After your trial ends, you will need to select a payment method to continue using the service. Your data will be preserved during this transition.') }}
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                {{ __('What payment methods do you accept?') }}
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#planFaq">
                            <div class="accordion-body">
                                {{ __('We accept bank transfers, credit/debit cards via Stripe, PayPal, and other regional payment methods depending on your location.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Payment Method Selection Modal --}}
<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-labelledby="paymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentMethodModalLabel">{{ __('Select Payment Method') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">{{ __('Please select how you would like to pay for your subscription.') }}</p>

                <div id="paymentMethodOptions">
                    {{-- Bank Transfer (Always available if enabled) --}}
                    @if(in_array('bank_transfer', $paymentMethods))
                        <div class="form-check custom-option custom-option-basic mb-3">
                            <label class="form-check-label custom-option-content" for="modal_bank_transfer">
                                <input class="form-check-input" type="radio" name="modal_payment_method" id="modal_bank_transfer" value="bank_transfer" checked>
                                <span class="custom-option-header">
                                    <span class="h6 mb-0"><i class="bx bx-building me-2"></i>{{ __('Bank Transfer') }}</span>
                                </span>
                                <span class="custom-option-body">
                                    <small>{{ __('Pay via bank transfer and upload proof of payment') }}</small>
                                </span>
                            </label>
                        </div>
                    @endif

                    {{-- Stripe --}}
                    @if(in_array('stripe', $paymentMethods))
                        <div class="form-check custom-option custom-option-basic mb-3">
                            <label class="form-check-label custom-option-content" for="modal_stripe">
                                <input class="form-check-input" type="radio" name="modal_payment_method" id="modal_stripe" value="stripe">
                                <span class="custom-option-header">
                                    <span class="h6 mb-0"><i class="bx bx-credit-card me-2"></i>{{ __('Credit/Debit Card') }}</span>
                                    <span class="badge bg-label-primary ms-2">Stripe</span>
                                </span>
                                <span class="custom-option-body">
                                    <small>{{ __('Pay securely with your credit or debit card') }}</small>
                                </span>
                            </label>
                        </div>
                    @endif

                    {{-- PayPal --}}
                    @if(in_array('paypal', $paymentMethods))
                        <div class="form-check custom-option custom-option-basic mb-3">
                            <label class="form-check-label custom-option-content" for="modal_paypal">
                                <input class="form-check-input" type="radio" name="modal_payment_method" id="modal_paypal" value="paypal">
                                <span class="custom-option-header">
                                    <span class="h6 mb-0"><i class="bx bxl-paypal me-2"></i>{{ __('PayPal') }}</span>
                                </span>
                                <span class="custom-option-body">
                                    <small>{{ __('Pay with your PayPal account') }}</small>
                                </span>
                            </label>
                        </div>
                    @endif

                    {{-- Razorpay --}}
                    @if(in_array('razorpay', $paymentMethods))
                        <div class="form-check custom-option custom-option-basic mb-3">
                            <label class="form-check-label custom-option-content" for="modal_razorpay">
                                <input class="form-check-input" type="radio" name="modal_payment_method" id="modal_razorpay" value="razorpay">
                                <span class="custom-option-header">
                                    <span class="h6 mb-0"><i class="bx bx-wallet me-2"></i>{{ __('Razorpay') }}</span>
                                </span>
                                <span class="custom-option-body">
                                    <small>{{ __('Pay with UPI, cards, wallets and more') }}</small>
                                </span>
                            </label>
                        </div>
                    @endif

                    {{-- No payment methods available --}}
                    @if(empty($paymentMethods))
                        <div class="alert alert-warning">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ __('No payment methods are currently available. Please contact support.') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                @if(!empty($paymentMethods))
                    <button type="button" class="btn btn-primary" id="confirmPaymentMethod">
                        <i class="bx bx-check me-1"></i>{{ __('Continue to Payment') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
    let activeForm = null;

    // Intercept form submissions for paid plans
    document.querySelectorAll('.plan-select-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiresPayment = form.dataset.requiresPayment === 'true';

            if (requiresPayment) {
                e.preventDefault();
                activeForm = form;

                // Update modal title with plan name
                const planName = form.dataset.planName;
                document.getElementById('paymentMethodModalLabel').textContent =
                    '{{ __("Select Payment Method for") }} ' + planName;

                // Show the modal
                paymentModal.show();
            }
            // For free plans or trial-eligible plans, let form submit normally
        });
    });

    // Handle payment method confirmation
    const confirmBtn = document.getElementById('confirmPaymentMethod');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const selectedMethod = document.querySelector('input[name="modal_payment_method"]:checked');

            if (selectedMethod && activeForm) {
                // Update the hidden payment_method field in the form
                const paymentMethodInput = activeForm.querySelector('input[name="payment_method"]');
                if (paymentMethodInput) {
                    paymentMethodInput.value = selectedMethod.value;
                }

                // Hide modal and submit form
                paymentModal.hide();
                activeForm.submit();
            }
        });
    }

    // Reset selection when modal is closed
    document.getElementById('paymentMethodModal').addEventListener('hidden.bs.modal', function() {
        activeForm = null;
    });
});
</script>
@endsection
