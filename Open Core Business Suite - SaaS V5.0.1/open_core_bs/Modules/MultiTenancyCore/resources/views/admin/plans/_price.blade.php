<div>
    <span class="fw-bold text-primary">{{ $plan->formatted_price ?? '$' . number_format($plan->price, 2) }}</span>
    <small class="text-muted d-block">{{ $plan->billing_period_label ?? $plan->billing_period }}</small>
</div>
