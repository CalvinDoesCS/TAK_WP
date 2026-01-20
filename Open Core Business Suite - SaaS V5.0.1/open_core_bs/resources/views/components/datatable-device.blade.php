<div class="d-flex align-items-center">
    <div class="avatar avatar-sm me-3">
        <div class="avatar-initial bg-label-primary rounded">
            <i class="{{ $icon ?? 'bx bx-devices' }}"></i>
        </div>
    </div>
    <div>
        <h6 class="mb-0">{{ $device->name }}</h6>
        <small class="text-muted">{{ $device->unique_id ?? $device->id }}</small>
    </div>
</div>