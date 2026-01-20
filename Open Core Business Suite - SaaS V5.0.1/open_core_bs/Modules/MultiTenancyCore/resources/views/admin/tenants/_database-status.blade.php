@php
    $statusClasses = [
        'pending' => 'bg-label-warning',
        'provisioning' => 'bg-label-info',
        'provisioned' => 'bg-label-success',
        'failed' => 'bg-label-danger'
    ];
    
    $statusLabels = [
        'pending' => __('Pending'),
        'provisioning' => __('Provisioning'),
        'provisioned' => __('Ready'),
        'failed' => __('Failed')
    ];
    
    $statusIcons = [
        'pending' => 'bx-time',
        'provisioning' => 'bx-loader-alt',
        'provisioned' => 'bx-check-circle',
        'failed' => 'bx-x-circle'
    ];
@endphp

@if($tenant->database_provisioning_status === 'provisioned')
    <span class="badge bg-label-success">
        <i class="bx bx-check-circle me-1"></i>
        {{ __('Ready') }}
    </span>
@elseif($tenant->database_provisioning_status === 'provisioning')
    <span class="badge bg-label-info">
        <i class="bx bx-loader-alt me-1"></i>
        {{ __('Provisioning') }}
    </span>
@elseif($tenant->database_provisioning_status === 'failed')
    <span class="badge bg-label-danger">
        <i class="bx bx-x-circle me-1"></i>
        {{ __('Failed') }}
    </span>
@elseif($tenant->database_provisioning_status === 'pending')
    <span class="badge bg-label-warning">
        <i class="bx bx-time me-1"></i>
        {{ __('Pending') }}
    </span>
@else
    <span class="badge bg-label-secondary">
        <i class="bx bx-database me-1"></i>
        {{ __('Not Created') }}
    </span>
@endif