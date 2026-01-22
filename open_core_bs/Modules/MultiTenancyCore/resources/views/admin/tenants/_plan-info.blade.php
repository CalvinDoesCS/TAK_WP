<div>
    <span class="badge bg-label-primary">{{ $subscription->plan->name }}</span>
    <br>
    <small class="text-muted">
        @if($subscription->status === 'trial' && $subscription->ends_at)
            <i class="bx bx-time"></i> {{ __('Trial until') }} {{ $subscription->ends_at->format('Y-m-d') }}
        @elseif($subscription->status === 'active')
            @if($subscription->ends_at)
                <i class="bx bx-check-circle"></i> {{ __('Active until') }} {{ $subscription->ends_at->format('Y-m-d') }}
            @else
                <i class="bx bx-check-circle"></i> {{ __('Active') }}
            @endif
        @endif
    </small>
</div>