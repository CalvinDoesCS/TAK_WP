<div class="d-flex align-items-center">
    <div>
        <h6 class="mb-0 fw-medium">{{ $plan->name }}</h6>
        @if($plan->description)
            <small class="text-muted">{{ \Illuminate\Support\Str::limit($plan->description, 50) }}</small>
        @endif
    </div>
</div>
