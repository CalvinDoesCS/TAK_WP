<div>
    <span class="badge bg-label-primary">{{ ucfirst($payment->payment_method) }}</span>
    @if($payment->reference_number)
        <br><small class="text-muted">{{ __('Ref:') }} <code>{{ $payment->reference_number }}</code></small>
    @endif
    @if($payment->metadata && isset($payment->metadata['bank_name']))
        <br><small class="text-muted">{{ $payment->metadata['bank_name'] }}</small>
    @endif
</div>