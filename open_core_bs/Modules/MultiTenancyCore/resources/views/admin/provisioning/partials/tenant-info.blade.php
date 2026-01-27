<div>
    <div class="fw-semibold">{{ $tenant->name }}</div>
    <small class="text-muted">{{ $tenant->email }}</small>
    <br>
    <small class="text-muted">{{ $tenant->subdomain }}.{{ request()->getHost() }}</small>
</div>