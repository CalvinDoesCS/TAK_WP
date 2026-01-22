@if($tenant->database_provisioning_status === 'pending')
    <span class="badge bg-label-warning">
        <i class="bx bx-time-five me-1"></i>{{ __('Pending') }}
    </span>
@elseif($tenant->database_provisioning_status === 'provisioning')
    <span class="badge bg-label-info">
        <i class="bx bx-loader-alt bx-spin me-1"></i>{{ __('Provisioning') }}
    </span>
@elseif($tenant->database_provisioning_status === 'failed')
    <span class="badge bg-label-danger">
        <i class="bx bx-error-circle me-1"></i>{{ __('Failed') }}
    </span>
@elseif($tenant->database_provisioning_status === 'provisioned')
    <span class="badge bg-label-success">
        <i class="bx bx-check-circle me-1"></i>{{ __('Provisioned') }}
    </span>
@else
    <span class="badge bg-label-secondary">{{ ucfirst($tenant->database_provisioning_status) }}</span>
@endif