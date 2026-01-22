<div class="d-flex gap-1">
    @if($payment->status === 'pending')
        <button type="button" class="btn btn-sm btn-icon btn-success" onclick="approvePayment({{ $payment->id }})" title="{{ __('Approve') }}">
            <i class="bx bx-check"></i>
        </button>
        <button type="button" class="btn btn-sm btn-icon btn-danger" onclick="rejectPayment({{ $payment->id }})" title="{{ __('Reject') }}">
            <i class="bx bx-x"></i>
        </button>
    @endif
    <a href="{{ route('multitenancycore.admin.payments.show', $payment->id) }}" class="btn btn-sm btn-icon btn-info" title="{{ __('View Details') }}">
        <i class="bx bx-show"></i>
    </a>
</div>