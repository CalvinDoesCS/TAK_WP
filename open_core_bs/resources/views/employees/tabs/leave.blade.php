@php
    use App\Enums\LeaveRequestStatus;
@endphp

<div class="row g-4">
    {{-- Leave Balance Card --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Leave Balance') }}</h5>
            </div>
            <div class="card-body">
                @if ($leaveTypes->isEmpty())
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>{{ __('No leave types configured') }}
                    </div>
                @else
                    <div class="row g-3">
                        @foreach ($leaveTypes as $leaveType)
                            @php
                                // Get user's leave balance from users_available_leaves table
                                $availableLeave = \App\Models\UserAvailableLeave::where('user_id', $user->id)
                                    ->where('leave_type_id', $leaveType->id)
                                    ->where('year', now()->year)
                                    ->first();

                                if ($availableLeave) {
                                    // Use actual allocated balance from database
                                    $totalLeaves = $availableLeave->entitled_leaves + $availableLeave->carried_forward_leaves + $availableLeave->additional_leaves;
                                    $usedLeaves = $availableLeave->used_leaves;
                                    $remainingLeaves = $availableLeave->available_leaves;
                                } else {
                                    // Fallback to leave type defaults if no allocation exists
                                    $totalLeaves = $leaveType->default_days ?? 0;
                                    $usedLeaves = 0;
                                    $remainingLeaves = $totalLeaves;
                                }

                                // Calculate usage percentage (how much has been used)
                                $usagePercentage = $totalLeaves > 0 ? ($usedLeaves / $totalLeaves) * 100 : 0;
                            @endphp
                            <div class="col-md-6 col-lg-3">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="avatar flex-shrink-0 me-2">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-calendar-x"></i>
                                                </span>
                                            </div>
                                            <span class="badge bg-label-{{ $remainingLeaves > 0 ? 'success' : 'danger' }}">
                                                {{ number_format($remainingLeaves, 1) }}
                                            </span>
                                        </div>
                                        <h6 class="mb-1">{{ $leaveType->name }}</h6>
                                        <small class="text-muted">{{ __('Used') }}: {{ number_format($usedLeaves, 1) }} / {{ number_format($totalLeaves, 1) }}</small>
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $usagePercentage }}%;"
                                                 aria-valuenow="{{ $usagePercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Leave History Table --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Leave History') }}</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-leave table border-top" id="leaveHistoryTable">
                    <thead>
                        <tr>
                            <th>{{ __('Leave Type') }}</th>
                            <th>{{ __('From Date') }}</th>
                            <th>{{ __('To Date') }}</th>
                            <th>{{ __('Days') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Applied On') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user->leaveRequests()->with('leaveType')->orderBy('created_at', 'desc')->get() as $leave)
                            <tr>
                                <td>{{ $leave->leaveType->name ?? __('N/A') }}</td>
                                <td>{{ $leave->from_date ? \Carbon\Carbon::parse($leave->from_date)->format('d M Y') : __('N/A') }}</td>
                                <td>{{ $leave->to_date ? \Carbon\Carbon::parse($leave->to_date)->format('d M Y') : __('N/A') }}</td>
                                <td>{{ number_format($leave->total_days, 1) }}</td>
                                <td>
                                    @if($leave->status instanceof \App\Enums\LeaveRequestStatus)
                                        {!! $leave->status->badge() !!}
                                    @else
                                        <span class="badge bg-label-secondary">{{ is_string($leave->status) ? ucfirst($leave->status) : __('N/A') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $leave->user_notes }}">
                                        {{ $leave->user_notes ?? __('N/A') }}
                                    </span>
                                </td>
                                <td>{{ $leave->created_at ? $leave->created_at->format('d M Y') : __('N/A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('No leave records found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
<script>
$(function () {
    // Initialize DataTable for leave history
    if ($('#leaveHistoryTable').length && $.fn.DataTable) {
        $('#leaveHistoryTable').DataTable({
            order: [[6, 'desc']], // Order by Applied On date
            pageLength: 10,
            responsive: true,
            language: {
                search: '{{ __("Search") }}:',
                lengthMenu: '{{ __("Show") }} _MENU_',
                info: '{{ __("Showing") }} _START_ {{ __("to") }} _END_ {{ __("of") }} _TOTAL_ {{ __("entries") }}',
                infoEmpty: '{{ __("Showing 0 to 0 of 0 entries") }}',
                infoFiltered: '({{ __("filtered from") }} _MAX_ {{ __("total entries") }})',
                paginate: {
                    first: '{{ __("First") }}',
                    last: '{{ __("Last") }}',
                    next: '{{ __("Next") }}',
                    previous: '{{ __("Previous") }}'
                }
            }
        });
    }
});
</script>
@endpush
