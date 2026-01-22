@php
    use App\Enums\AttendanceStatus;
    use Carbon\Carbon;
@endphp

<div class="row g-4">
    {{-- Attendance Statistics Cards --}}
    <div class="col-12">
        <div class="row g-3">
            @php
                // Calculate present count (checked_in, checked_out, or half_day)
                $presentStatuses = [
                    AttendanceStatus::CHECKED_IN->value,
                    AttendanceStatus::CHECKED_OUT->value,
                    AttendanceStatus::HALF_DAY->value
                ];
                $presentCount = $attendanceLogs->whereIn('status', $presentStatuses)->count();
                $absentCount = $attendanceLogs->where('status', AttendanceStatus::ABSENT->value)->count();
                $lateCount = $attendanceLogs->filter(function($log) {
                    return isset($log->late_hours) && $log->late_hours > 0;
                })->count();
                $halfDayCount = $attendanceLogs->where('status', AttendanceStatus::HALF_DAY->value)->count();
                $totalDays = $attendanceLogs->count();
                $attendancePercentage = $totalDays > 0 ? (($presentCount + ($halfDayCount * 0.5)) / $totalDays) * 100 : 0;
            @endphp

            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">{{ __('Attendance Rate') }}</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ number_format($attendancePercentage, 1) }}%</h4>
                                </div>
                                <small class="mb-0">{{ __('Last 30 days') }}</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-calendar-check bx-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">{{ __('Present Days') }}</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $presentCount }}</h4>
                                </div>
                                <small class="mb-0">{{ __('Out of') }} {{ $totalDays }}</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-user-check bx-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">{{ __('Late Arrivals') }}</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $lateCount }}</h4>
                                </div>
                                <small class="mb-0">{{ __('Last 30 days') }}</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="bx bx-time bx-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">{{ __('Absent Days') }}</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $absentCount }}</h4>
                                </div>
                                <small class="mb-0">{{ __('Last 30 days') }}</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="bx bx-x-circle bx-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance Log Table --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Attendance Log') }}</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-attendance table border-top" id="attendanceLogTable">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Day') }}</th>
                            <th>{{ __('Shift') }}</th>
                            <th>{{ __('Check In') }}</th>
                            <th>{{ __('Check Out') }}</th>
                            <th>{{ __('Work Hours') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Remarks') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendanceLogs as $log)
                            <tr>
                                <td>{{ $log->date ? Carbon::parse($log->date)->format('d M Y') : __('N/A') }}</td>
                                <td>{{ $log->date ? Carbon::parse($log->date)->format('l') : __('N/A') }}</td>
                                <td>{{ $log->shift->name ?? __('N/A') }}</td>
                                <td>
                                    @if ($log->check_in_time)
                                        {{ Carbon::parse($log->check_in_time)->format('h:i A') }}
                                    @else
                                        <span class="text-muted">{{ __('N/A') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->check_out_time)
                                        {{ Carbon::parse($log->check_out_time)->format('h:i A') }}
                                    @else
                                        <span class="text-muted">{{ __('N/A') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->working_hours)
                                        @php
                                            $hours = floor($log->working_hours);
                                            $minutes = round(($log->working_hours - $hours) * 60);
                                        @endphp
                                        @if($hours > 0)
                                            {{ $hours }}h {{ $minutes }}m
                                        @else
                                            {{ $minutes }}m
                                        @endif
                                    @else
                                        <span class="text-muted">{{ __('N/A') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusEnum = is_string($log->status) ? AttendanceStatus::tryFrom($log->status) : $log->status;
                                    @endphp
                                    @if($statusEnum)
                                        <span class="badge {{ $statusEnum->badgeClass() }}">
                                            {{ __($statusEnum->label()) }}
                                            @if(isset($log->late_hours) && $log->late_hours > 0)
                                                <i class="bx bx-time-five ms-1" title="{{ __('Late by') }} {{ number_format($log->late_hours * 60) }} {{ __('minutes') }}"></i>
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">{{ ucfirst($log->status ?? 'N/A') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $log->remarks }}">
                                        {{ $log->remarks ?? __('N/A') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">{{ __('No attendance records found') }}</td>
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
    // Initialize DataTable for attendance log
    if ($('#attendanceLogTable').length && $.fn.DataTable) {
        $('#attendanceLogTable').DataTable({
            order: [[0, 'desc']], // Order by date
            pageLength: 15,
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
