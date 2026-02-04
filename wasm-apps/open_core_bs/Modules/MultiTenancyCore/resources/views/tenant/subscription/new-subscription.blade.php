@extends('layouts.layoutMaster')

@section('title', __('Subscribe to :plan', ['plan' => $newPlan->name]))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Subscribe to :plan Plan', ['plan' => $newPlan->name]) }}</h2>
                    <p class="text-muted">{{ __('Complete your subscription to get started') }}</p>
                </div>

                {{-- Plan Details --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">{{ __('Plan Details') }}</h5>
                        
                        <div class="text-center mb-4">
                            <h4>{{ $newPlan->name }}</h4>
                            <div class="d-flex justify-content-center align-items-baseline">
                                <h2 class="price mb-0">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($newPlan->price) }}</h2>
                                <sub class="text-muted ms-2">/{{ $newPlan->billing_period }}</sub>
                            </div>
                            @if($newPlan->description)
                                <p class="text-muted mt-2">{{ $newPlan->description }}</p>
                            @endif
                        </div>
                        
                        <hr class="my-4">
                        
                        {{-- Features --}}
                        <div class="mb-4">
                            <h6 class="mb-3">{{ __('Plan Features') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        @foreach(array_slice($newPlan->display_features, 0, ceil(count($newPlan->display_features) / 2)) as $feature)
                                            <li class="mb-2"><i class="bx bx-check text-success me-2"></i>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        @foreach(array_slice($newPlan->display_features, ceil(count($newPlan->display_features) / 2)) as $feature)
                                            <li class="mb-2"><i class="bx bx-check text-success me-2"></i>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Trial Information --}}
                        @if($trialDays > 0)
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading mb-1">{{ __(':days Day Free Trial', ['days' => $trialDays]) }}</h6>
                                <p class="mb-0">{{ __('Your subscription includes a :days day free trial. You will not be charged until the trial ends.', ['days' => $trialDays]) }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment Method Selection --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">{{ __('Select Payment Method') }}</h5>
                        
                        <form action="{{ route('multitenancycore.tenant.subscription.process-change-plan') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">
                            
                            <div class="mb-4">
                                <div class="form-check custom-option custom-option-basic mb-3">
                                    <label class="form-check-label custom-option-content" for="bank_transfer">
                                        <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer" checked>
                                        <span class="custom-option-header">
                                            <span class="h6 mb-0">{{ __('Bank Transfer') }}</span>
                                        </span>
                                        <span class="custom-option-body">
                                            <small>{{ __('Pay via bank transfer and upload proof of payment') }}</small>
                                        </span>
                                    </label>
                                </div>
                                
                                @if(app(\App\Services\AddonService\IAddonService::class)->isAddonEnabled('StripeGateway') && \Modules\MultiTenancyCore\App\Models\SaasSetting::get('payment_gateway_stripe_enabled', false))
                                    <div class="form-check custom-option custom-option-basic mb-3">
                                        <label class="form-check-label custom-option-content" for="stripe">
                                            <input class="form-check-input" type="radio" name="payment_method" id="stripe" value="stripe">
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">{{ __('Credit/Debit Card') }}</span>
                                                <span class="badge bg-label-primary ms-2">Stripe</span>
                                            </span>
                                            <span class="custom-option-body">
                                                <small>{{ __('Pay securely with your credit or debit card') }}</small>
                                            </span>
                                        </label>
                                    </div>
                                @endif
                                
                                @if(app(\App\Services\AddonService\IAddonService::class)->isAddonEnabled('PayPalGateway') && \Modules\MultiTenancyCore\App\Models\SaasSetting::get('payment_gateway_paypal_enabled', false))
                                    <div class="form-check custom-option custom-option-basic mb-3">
                                        <label class="form-check-label custom-option-content" for="paypal">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">{{ __('PayPal') }}</span>
                                            </span>
                                            <span class="custom-option-body">
                                                <small>{{ __('Pay with your PayPal account') }}</small>
                                            </span>
                                        </label>
                                    </div>
                                @endif
                                
                                @if(app(\App\Services\AddonService\IAddonService::class)->isAddonEnabled('RazorpayGateway') && \Modules\MultiTenancyCore\App\Models\SaasSetting::get('payment_gateway_razorpay_enabled', false))
                                    <div class="form-check custom-option custom-option-basic mb-3">
                                        <label class="form-check-label custom-option-content" for="razorpay">
                                            <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay">
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">{{ __('Razorpay') }}</span>
                                            </span>
                                            <span class="custom-option-body">
                                                <small>{{ __('Pay with UPI, cards, wallets and more') }}</small>
                                            </span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-label-secondary">
                                    {{ __('Cancel') }}
                                </a>
                                @if($trialDays > 0)
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        {{ __('Start Free Trial') }}
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        {{ __('Subscribe Now') }}
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection