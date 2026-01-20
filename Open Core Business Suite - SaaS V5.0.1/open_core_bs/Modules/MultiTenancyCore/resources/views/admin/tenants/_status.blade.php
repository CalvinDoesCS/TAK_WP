@php
    $statusClasses = [
        'pending' => 'bg-label-warning',
        'approved' => 'bg-label-info',
        'active' => 'bg-label-success',
        'suspended' => 'bg-label-danger',
        'cancelled' => 'bg-label-secondary'
    ];
    
    $statusLabels = [
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'active' => __('Active'),
        'suspended' => __('Suspended'),
        'cancelled' => __('Cancelled')
    ];
@endphp

<span class="badge {{ $statusClasses[$tenant->status] ?? 'bg-label-secondary' }}">
    {{ $statusLabels[$tenant->status] ?? $tenant->status }}
</span>