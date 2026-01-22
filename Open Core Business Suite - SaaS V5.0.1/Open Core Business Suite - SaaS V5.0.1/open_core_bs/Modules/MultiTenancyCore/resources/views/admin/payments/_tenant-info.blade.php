<div class="d-flex align-items-center">
    <div>
        <h6 class="mb-0">{{ $payment->tenant->name }}</h6>
        <small class="text-muted">{{ $payment->tenant->email }}</small>
        @if($payment->subscription)
            <br><small class="text-info">{{ __('Plan:') }} {{ $payment->subscription->plan->name }}</small>
        @endif
    </div>
</div>