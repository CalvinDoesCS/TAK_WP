<div>
    <small class="text-muted">
        {{ __('Start:') }} {{ $subscription->starts_at ? $subscription->starts_at->format('Y-m-d') : '-' }}
    </small>
    <br>
    <small class="text-muted">
        {{ __('End:') }} 
        @if($subscription->ends_at)
            {{ $subscription->ends_at->format('Y-m-d') }}
            @if($subscription->cancelled_at)
                <span class="text-danger">({{ __('Cancelled') }})</span>
            @endif
        @else
            {{ __('Lifetime') }}
        @endif
    </small>
</div>