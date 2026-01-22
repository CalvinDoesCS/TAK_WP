{{-- Compensatory Off Details Partial for Offcanvas --}}
<div class="comp-off-details">
  {{-- Header --}}
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">{{ __('Request #:id', ['id' => $compOff->id]) }}</h6>
      @php
        $statusColors = [
          'pending' => 'warning',
          'approved' => 'success',
          'rejected' => 'danger'
        ];
        $color = $statusColors[$compOff->status] ?? 'secondary';
      @endphp
      <span class="badge bg-{{ $color }}">{{ ucfirst($compOff->status) }}</span>
    </div>
    <small class="text-muted">{{ __('Submitted on') }} {{ \Carbon\Carbon::parse($compOff->created_at)->format('M d, Y H:i') }}</small>
  </div>

  {{-- Work Information --}}
  <div class="mb-4">
    <h6 class="text-primary mb-3">{{ __('Work Information') }}</h6>
    <table class="table table-sm">
      <tr>
        <td class="text-muted">{{ __('Worked Date') }}:</td>
        <td><strong>{{ \Carbon\Carbon::parse($compOff->worked_date)->format('M d, Y') }}</strong></td>
      </tr>
      <tr>
        <td class="text-muted">{{ __('Hours Worked') }}:</td>
        <td><span class="badge bg-label-info">{{ $compOff->hours_worked }} {{ __('hours') }}</span></td>
      </tr>
      <tr>
        <td class="text-muted">{{ __('Comp Off Days') }}:</td>
        <td><span class="badge bg-label-primary">{{ $compOff->comp_off_days }} {{ __('days') }}</span></td>
      </tr>
      <tr>
        <td class="text-muted">{{ __('Expiry Date') }}:</td>
        <td>
          @php
            $expiryDate = \Carbon\Carbon::parse($compOff->expiry_date);
            $isExpired = $expiryDate->isPast() && !$compOff->is_used;
            $isExpiringSoon = $expiryDate->diffInDays(now()) <= 7 && !$compOff->is_used && !$isExpired;
            $badgeColor = $isExpired ? 'danger' : ($isExpiringSoon ? 'warning' : 'secondary');
          @endphp
          <span class="badge bg-label-{{ $badgeColor }}">
            {{ $expiryDate->format('M d, Y') }}
            @if($isExpired)
              ({{ __('Expired') }})
            @elseif($isExpiringSoon)
              ({{ __('Expiring Soon') }})
            @endif
          </span>
        </td>
      </tr>
    </table>
  </div>

  {{-- Reason --}}
  <div class="mb-4">
    <h6 class="text-primary mb-2">{{ __('Reason for Extra Hours') }}</h6>
    <div class="border rounded p-3 bg-light">
      <p class="mb-0">{{ $compOff->reason }}</p>
    </div>
  </div>

  {{-- Usage Information --}}
  <div class="mb-4">
    <h6 class="text-primary mb-3">{{ __('Usage Information') }}</h6>
    <div class="row">
      <div class="col-12 mb-2">
        <small class="text-muted d-block">{{ __('Current Status') }}</small>
        @if($compOff->is_used)
          <span class="badge bg-success">{{ __('Used') }}</span>
        @elseif($compOff->status === 'approved' && $expiryDate->isPast())
          <span class="badge bg-danger">{{ __('Expired') }}</span>
        @elseif($compOff->status === 'approved')
          <span class="badge bg-primary">{{ __('Available') }}</span>
        @else
          <span class="badge bg-secondary">{{ __('Not Available') }}</span>
        @endif
      </div>
      @if($compOff->is_used)
      <div class="col-12 mb-2">
        <small class="text-muted d-block">{{ __('Used Date') }}</small>
        <strong>{{ \Carbon\Carbon::parse($compOff->used_date)->format('M d, Y') }}</strong>
      </div>
      @endif
      @if($compOff->leaveRequest)
      <div class="col-12 mb-2">
        <small class="text-muted d-block">{{ __('Used For Leave') }}</small>
        <span class="text-primary">{{ __('Leave Request #:id', ['id' => $compOff->leaveRequest->id]) }}</span>
      </div>
      @endif
    </div>
  </div>

  {{-- Approval Information --}}
  @if($compOff->approved_by_id || $compOff->rejected_by_id)
  <div class="mb-4">
    <h6 class="text-primary mb-3">{{ __('Approval Information') }}</h6>
    @if($compOff->approved_by_id)
      <div class="d-flex align-items-center mb-3">
        <div class="avatar avatar-sm me-3">
          <span class="avatar-initial rounded-circle bg-success">
            <i class="bx bx-check"></i>
          </span>
        </div>
        <div>
          <h6 class="mb-0">{{ __('Approved by') }} {{ $compOff->approvedBy->first_name }} {{ $compOff->approvedBy->last_name }}</h6>
          <small class="text-muted">{{ \Carbon\Carbon::parse($compOff->approved_at)->format('M d, Y H:i') }}</small>
        </div>
      </div>
    @endif

    @if($compOff->rejected_by_id)
      <div class="d-flex align-items-center mb-3">
        <div class="avatar avatar-sm me-3">
          <span class="avatar-initial rounded-circle bg-danger">
            <i class="bx bx-x"></i>
          </span>
        </div>
        <div>
          <h6 class="mb-0">{{ __('Rejected by') }} {{ $compOff->rejectedBy ? ($compOff->rejectedBy->first_name . ' ' . $compOff->rejectedBy->last_name) : __('Unknown') }}</h6>
          <small class="text-muted">{{ \Carbon\Carbon::parse($compOff->rejected_at)->format('M d, Y H:i') }}</small>
        </div>
      </div>
    @endif

    @if($compOff->approval_notes)
      <div class="mt-3">
        <h6 class="mb-2">{{ __('Notes') }}</h6>
        <div class="border rounded p-3 bg-light">
          <p class="mb-0">{{ $compOff->approval_notes }}</p>
        </div>
      </div>
    @endif
  </div>
  @endif

  {{-- Action Buttons for Pending Requests --}}
  @if($compOff->status === 'pending' && $compOff->user_id === auth()->id())
  <div class="d-grid gap-2">
    <button type="button" class="btn btn-primary" onclick="editCompensatoryOffForm({{ $compOff->id }})">
      <i class="bx bx-edit me-1"></i>{{ __('Edit Request') }}
    </button>
  </div>
  @endif
</div>
