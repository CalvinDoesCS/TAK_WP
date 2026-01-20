@extends('layouts/horizontalLayout')

@section('title', __('My Attendance'))

@section('content')
<x-breadcrumb
    :title="__('My Attendance')"
    :breadcrumbs="[
        ['name' => __('Dashboard'), 'url' => route('employee.dashboard')],
        ['name' => __('My Attendance'), 'url' => '']
    ]"
    :homeUrl="route('employee.dashboard')"/>

<div class="row">
    <!-- Today's Attendance Card -->
    <div class="col-md-4 mb-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __("Today's Attendance") }}</h5>
            </div>
            <div class="card-body">
                @if($todayAttendance)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">{{ __('Check In') }}</span>
                            <strong>{{ $todayAttendance->check_in_time ? $todayAttendance->check_in_time->format('h:i A') : '-' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">{{ __('Check Out') }}</span>
                            <strong>{{ $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('h:i A') : '-' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Total Hours') }}</span>
                            <strong>
                                @if($todayAttendance->check_in_time && $todayAttendance->check_out_time)
                                    {{ $todayAttendance->check_in_time->diff($todayAttendance->check_out_time)->format('%hh %im') }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                    </div>

                    @if(!$todayAttendance->check_out_time)
                        <form action="{{ route('employee.attendance.check-out') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bx bx-log-out me-2"></i>{{ __('Check Out') }}
                            </button>
                        </form>
                    @else
                        <div class="alert alert-success mb-0">
                            <i class="bx bx-check-circle me-2"></i>{{ __('Attendance Completed') }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-time-five bx-lg text-muted mb-3"></i>
                        <p class="text-muted mb-4">{{ __('Not checked in yet') }}</p>
                        <form action="{{ route('employee.attendance.check-in') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-log-in me-2"></i>{{ __('Check In') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Attendance History -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Attendance History') }}</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Check In') }}</th>
                            <th>{{ __('Check Out') }}</th>
                            <th>{{ __('Total Hours') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->created_at->format('d M, Y') }}</td>
                                <td>
                                    {{ $attendance->check_in_time ? $attendance->check_in_time->format('h:i A') : '-' }}
                                </td>
                                <td>
                                    {{ $attendance->check_out_time ? $attendance->check_out_time->format('h:i A') : '-' }}
                                </td>
                                <td>
                                    @if($attendance->check_in_time && $attendance->check_out_time)
                                        {{ $attendance->check_in_time->diff($attendance->check_out_time)->format('%hh %im') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($attendance->check_out_time)
                                        <span class="badge bg-success">{{ __('Completed') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('In Progress') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bx bx-calendar-x bx-lg text-muted"></i>
                                    <p class="text-muted mt-2">{{ __('No attendance records found') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attendances->hasPages())
                <div class="card-footer">
                    {{ $attendances->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection