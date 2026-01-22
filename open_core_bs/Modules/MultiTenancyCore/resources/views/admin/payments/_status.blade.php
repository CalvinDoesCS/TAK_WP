@php
    $statusClasses = [
        'pending' => 'bg-label-warning',
        'approved' => 'bg-label-success',
        'completed' => 'bg-label-success',
        'rejected' => 'bg-label-danger',
        'failed' => 'bg-label-danger',
        'cancelled' => 'bg-label-secondary'
    ];

    $statusLabels = [
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'completed' => __('Completed'),
        'rejected' => __('Rejected'),
        'failed' => __('Failed'),
        'cancelled' => __('Cancelled')
    ];

    $statusIcons = [
        'pending' => 'bx-time',
        'approved' => 'bx-check-circle',
        'completed' => 'bx-check-circle',
        'rejected' => 'bx-x-circle',
        'failed' => 'bx-x-circle',
        'cancelled' => 'bx-block'
    ];
@endphp

<span class="badge {{ $statusClasses[$payment->status] ?? 'bg-label-secondary' }}">
    <i class="bx {{ $statusIcons[$payment->status] ?? 'bx-question-mark' }} me-1"></i>
    {{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}
</span>