<div class="d-flex align-items-center">
    <div>
        <h6 class="mb-0">{{ $tenant->name }}</h6>
        <small class="text-muted">{{ $tenant->email }}</small>
        @if($tenant->phone)
            <br><small class="text-muted"><i class="bx bx-phone"></i> {{ $tenant->phone }}</small>
        @endif
    </div>
</div>