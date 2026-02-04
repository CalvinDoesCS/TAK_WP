@extends('layouts.layoutMaster')

@section('title', __('Processing Payment'))

@section('content')
    <section class="section-py bg-body first-section-pt">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
                <h4>{{ __('Redirecting to payment gateway...') }}</h4>
                <p class="text-muted">{{ __('Please wait while we redirect you to complete your payment.') }}</p>
            </div>
        </div>
    </div>
</section>

@if($paymentMethod === 'stripe')
    <form id="payment-form" action="{{ route('stripegateway.payment.create', $payment->id) }}" method="POST">
        @csrf
    </form>
@elseif($paymentMethod === 'paypal')
    <form id="payment-form" action="{{ route('paypalgateway.payment.create', $payment->id) }}" method="POST">
        @csrf
    </form>
@elseif($paymentMethod === 'razorpay')
    <form id="payment-form" action="{{ route('razorpaygateway.payment.create', $payment->id) }}" method="POST">
        @csrf
    </form>
@endif
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit the form
        var form = document.getElementById('payment-form');
        if (form) {
            form.submit();
        }
    });
</script>
@endsection