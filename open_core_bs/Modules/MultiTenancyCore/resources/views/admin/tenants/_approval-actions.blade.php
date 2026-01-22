<div class="d-flex gap-1">
    <button type="button" class="btn btn-sm btn-icon btn-success" onclick="approveTenant({{ $tenant->id }})" title="{{ __('Approve') }}">
        <i class="bx bx-check"></i>
    </button>
    <button type="button" class="btn btn-sm btn-icon btn-danger" onclick="rejectTenant({{ $tenant->id }})" title="{{ __('Reject') }}">
        <i class="bx bx-x"></i>
    </button>
</div>