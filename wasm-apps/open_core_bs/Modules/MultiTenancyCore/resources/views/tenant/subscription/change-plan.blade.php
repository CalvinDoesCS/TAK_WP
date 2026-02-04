@extends('layouts.layoutMaster')

@section('title', __('Change Subscription Plan'))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Change Subscription Plan') }}</h2>
                    <p class="text-muted">{{ __('Review the changes and select your payment method') }}</p>
                </div>

                {{-- Plan Comparison --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">{{ __('Plan Comparison') }}</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h6 class="text-muted mb-2">{{ __('Current Plan') }}</h6>
                                    <h4>{{ $currentSubscription->plan->name }}</h4>
                                    <p class="h5 mb-0">{{ $currentSubscription->plan->formatted_price }}/{{ $currentSubscription->plan->billing_period }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h6 class="text-muted mb-2">{{ __('New Plan') }}</h6>
                                    <h4>{{ $newPlan->name }}</h4>
                                    <p class="h5 mb-0">{{ $newPlan->formatted_price }}/{{ $newPlan->billing_period }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        {{-- Pricing Details --}}
                        <div class="mb-4">
                            <h6 class="mb-3">{{ __('Billing Details') }}</h6>
                            @if($currentSubscription->plan->price < $newPlan->price)
                                {{-- Upgrade --}}
                                @php
                                    $daysRemaining = $currentSubscription->ends_at ? now()->diffInDays($currentSubscription->ends_at) : 0;
                                    $dailyRate = $currentSubscription->plan->price / 30;
                                    $credit = $dailyRate * $daysRemaining;
                                    $amountDue = max($newPlan->price - $credit, 0);
                                @endphp
                                
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td>{{ __('New plan price') }}</td>
                                                <td class="text-end">{{ $newPlan->formatted_price }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('Credit from current plan') }} <small class="text-muted">({{ $daysRemaining }} {{ __('days remaining') }})</small></td>
                                                <td class="text-end">-{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($credit) }}</td>
                                            </tr>
                                            <tr class="border-top">
                                                <td><strong>{{ __('Amount due today') }}</strong></td>
                                                <td class="text-end"><strong>{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($amountDue) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="alert alert-info" role="alert">
                                    <i class="bx bx-info-circle me-2"></i>
                                    {{ __('You will be upgraded immediately after payment. Your next billing date will remain the same.') }}
                                </div>
                            @else
                                {{-- Downgrade --}}
                                <div class="alert alert-warning" role="alert">
                                    <h6 class="alert-heading mb-1">{{ __('Downgrade Notice') }}</h6>
                                    <p class="mb-0">
                                        @if($currentSubscription->ends_at)
                                            {{ __('Your plan will be downgraded at the end of your current billing period on :date. No payment is required now.', ['date' => $currentSubscription->ends_at->format('M d, Y')]) }}
                                        @else
                                            {{ __('Your plan will be downgraded. No payment is required now.') }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Feature Changes --}}
                        <div class="mb-4">
                            <h6 class="mb-3">{{ __('Feature Changes') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>{{ __('Current Plan Features') }}:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($currentSubscription->plan->display_features as $feature)
                                            <li><i class="bx bx-check text-muted me-2"></i>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>{{ __('New Plan Features') }}:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($newPlan->display_features as $feature)
                                            <li><i class="bx bx-check text-success me-2"></i>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Method Selection --}}
                @if($currentSubscription->plan->price < $newPlan->price)
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
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        {{ __('Proceed to Payment') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- Downgrade Confirmation --}}
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('multitenancycore.tenant.subscription.process-change-plan') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">
                                <input type="hidden" name="payment_method" value="bank_transfer">

                                <div class="d-flex gap-2">
                                    <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-label-secondary">
                                        {{ __('Cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        {{ __('Confirm Downgrade') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection