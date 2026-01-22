@php
    $statusClasses = [
        'trial' => 'bg-label-warning',
        'active' => 'bg-label-success',
        'cancelled' => 'bg-label-danger',
        'expired' => 'bg-label-secondary'
    ];
    
    $statusLabels = [
        'trial' => __('Trial'),
        'active' => __('Active'),
        'cancelled' => __('Cancelled'),
        'expired' => __('Expired')
    ];
    
    $isExpiringSoon = $subscription->isExpiringSoon();
@endphp

<span class="badge {{ $statusClasses[$subscription->status] ?? 'bg-label-secondary' }}">
    {{ $statusLabels[$subscription->status] ?? $subscription->status }}
</span>

@if($isExpiringSoon)
    <br>
    <small class="text-danger">
        <i class="bx bx-error-circle"></i> {{ __('Expiring soon') }}
    </small>
@endif