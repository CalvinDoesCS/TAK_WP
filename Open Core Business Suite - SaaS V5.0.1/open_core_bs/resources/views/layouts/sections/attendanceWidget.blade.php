@php
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Auth;
use App\Services\Settings\ModuleSettingsService;
use App\Services\AddonService\IAddonService;

$todayAttendance = null;
$isCheckedIn = false;
$checkInTime = null;
$checkOutTime = null;
$workingHours = null;
$isBreakSystemEnabled = false;
$isOnBreak = false;

if (Auth::check()) {
    $todayAttendance = Attendance::where('user_id', Auth::id())
        ->whereDate('date', today())
        ->with('attendanceLogs')
        ->first();

    // Check if BreakSystem addon is enabled
    $addonService = app(IAddonService::class);
    $isBreakSystemEnabled = $addonService->isAddonEnabled('BreakSystem');

    if ($todayAttendance) {
        // Check if multiple check-in is enabled
        $settingsService = app(ModuleSettingsService::class);
        $isMultipleCheckInEnabled = $settingsService->get('HRCore', 'is_multiple_check_in_enabled', true);

        if ($isMultipleCheckInEnabled && auth()->user()->can('hrcore.multiple-check-in')) {
            // For multiple check-in, check the last log
            $lastLog = $todayAttendance->attendanceLogs->sortByDesc('created_at')->first();
            $isCheckedIn = $lastLog && $lastLog->type === 'check_in';
        } else {
            // For single check-in, use attendance table
            $isCheckedIn = $todayAttendance->check_in_time && !$todayAttendance->check_out_time;
        }

        // Get times - fallback to logs if not in attendance table
        $checkInTime = $todayAttendance->check_in_time;
        if (!$checkInTime) {
            $checkInLog = $todayAttendance->attendanceLogs->where('type', 'check_in')->first();
            $checkInTime = $checkInLog ? $checkInLog->created_at : null;
        }

        $checkOutTime = $todayAttendance->check_out_time;
        if (!$checkOutTime) {
            $checkOutLog = $todayAttendance->attendanceLogs->where('type', 'check_out')->last();
            $checkOutTime = $checkOutLog ? $checkOutLog->created_at : null;
        }

        // Calculate working hours in "Xh Ym" format
        $workingHours = '0h 0m';
        if ($checkInTime) {
            $checkInCarbon = \Carbon\Carbon::parse($checkInTime);
            $totalMinutes = 0;

            // Match web attendance page: always calculate to NOW for live time
            $totalMinutes = now()->diffInMinutes($checkInCarbon);

            if ($totalMinutes > 0) {
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $workingHours = $hours . 'h ' . $minutes . 'm';
            }
        }

        // Check if user is currently on break (only if break system is enabled and checked in)
        if ($isBreakSystemEnabled && $isCheckedIn) {
            // Get the latest check-in log for break tracking
            $checkInLog = $todayAttendance->attendanceLogs->where('type', 'check_in')->sortByDesc('created_at')->first();
            if ($checkInLog && class_exists(\Modules\BreakSystem\App\Models\AttendanceBreak::class)) {
                $runningBreak = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_log_id', $checkInLog->id)
                    ->whereNull('end_time')
                    ->first();
                $isOnBreak = (bool) $runningBreak;
            }
        }
    }
}
@endphp

<!-- Floating Attendance Widget -->
<div class="attendance-widget-toggle">
    <button type="button" class="btn btn-{{ $isCheckedIn ? 'danger' : 'success' }} btn-icon rounded-pill"
            id="attendanceWidgetToggle"
            data-bs-toggle="offcanvas"
            data-bs-target="#attendanceWidgetOffcanvas">
        <i class="bx {{ $isCheckedIn ? 'bx-log-out' : 'bx-log-in' }} bx-md"></i>
    </button>
</div>

<!-- Attendance Widget Offcanvas -->
<div class="offcanvas offcanvas-end"
     tabindex="-1"
     id="attendanceWidgetOffcanvas"
     aria-labelledby="attendanceWidgetLabel"
     style="width: 360px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="attendanceWidgetLabel">
            <i class="bx bx-time-five me-2"></i>{{ __('Attendance') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- Status Card -->
        <div class="p-4 bg-label-{{ $isCheckedIn ? 'success' : 'secondary' }}">
            <div class="text-center">
                <div class="mb-3">
                    <i class="bx {{ $isCheckedIn ? 'bx-check-circle' : 'bx-time' }} bx-lg text-{{ $isCheckedIn ? 'success' : 'secondary' }}"></i>
                </div>
                <h4 class="mb-1">
                    @if($isCheckedIn)
                        {{ __('You are Checked In') }}
                    @else
                        {{ __('Not Checked In') }}
                    @endif
                </h4>
                <p class="mb-0 text-muted">
                    {{ now()->format('l, F j, Y') }}
                </p>
            </div>
        </div>

        <!-- Today's Summary -->
        <div class="p-4">
            <h6 class="mb-3">{{ __("Today's Summary") }}</h6>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <div class="avatar-initial bg-label-success rounded">
                            <i class="bx bx-log-in"></i>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block">{{ __('Check In') }}</small>
                        <strong id="widgetCheckInTime">{{ $checkInTime ? \Carbon\Carbon::parse($checkInTime)->format('h:i A') : '--:--' }}</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <div class="avatar-initial bg-label-danger rounded">
                            <i class="bx bx-log-out"></i>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block">{{ __('Check Out') }}</small>
                        <strong id="widgetCheckOutTime">{{ $checkOutTime ? \Carbon\Carbon::parse($checkOutTime)->format('h:i A') : '--:--' }}</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <div class="avatar-initial bg-label-primary rounded">
                            <i class="bx bx-time"></i>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block">{{ __('Working Hours') }}</small>
                        <strong id="widgetWorkingHours">{{ $workingHours ?? '0h 0m' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Check In/Out Button -->
            @if($isCheckedIn)
                <button type="button" class="btn btn-danger w-100" id="widgetCheckOutBtn" onclick="widgetCheckOut()">
                    <i class="bx bx-log-out me-2"></i>{{ __('Check Out Now') }}
                </button>
            @else
                <button type="button" class="btn btn-success w-100" id="widgetCheckInBtn" onclick="widgetCheckIn()">
                    <i class="bx bx-log-in me-2"></i>{{ __('Check In Now') }}
                </button>
            @endif

            <!-- Break Button (only shown when checked in and BreakSystem is enabled) -->
            @if($isBreakSystemEnabled && $isCheckedIn)
                @if($isOnBreak)
                    <button type="button" class="btn btn-warning w-100 mt-2" id="widgetBreakBtn" onclick="widgetToggleBreak()">
                        <i class="bx bx-stop-circle me-2"></i>{{ __('Stop Break') }}
                    </button>
                @else
                    <button type="button" class="btn btn-info w-100 mt-2" id="widgetBreakBtn" onclick="widgetToggleBreak()">
                        <i class="bx bx-coffee me-2"></i>{{ __('Start Break') }}
                    </button>
                @endif
            @endif

            <!-- View Full Attendance -->
            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-label-secondary w-100 mt-2">
                <i class="bx bx-calendar me-2"></i>{{ __('View Full Attendance') }}
            </a>
        </div>
    </div>
</div>

<style>
.attendance-widget-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1049;
}

.attendance-widget-toggle .btn {
    width: 56px;
    height: 56px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.attendance-widget-toggle .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.attendance-widget-toggle .btn i {
    font-size: 1.5rem;
}

#attendanceWidgetOffcanvas {
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
}
</style>

<script>
let updateInterval = null;

// Update widget data when offcanvas is opened
document.getElementById('attendanceWidgetToggle')?.addEventListener('click', function() {
    updateWidgetData();
});

// Auto-refresh working hours every minute when offcanvas is open
const offcanvasElement = document.getElementById('attendanceWidgetOffcanvas');
if (offcanvasElement) {
    offcanvasElement.addEventListener('shown.bs.offcanvas', function() {
        updateWidgetData();
        // Update every 30 seconds while open
        updateInterval = setInterval(updateWidgetData, 30000);
    });

    offcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
        // Stop updating when closed
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    });
}

// Function to update widget data from API
function updateWidgetData() {
    $.ajax({
        url: '{{ route("hrcore.attendance.today-status") }}',
        type: 'GET',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const data = response.data;

                // Update check-in time
                if (data.checkInTime) {
                    const checkInDate = new Date(data.checkInTime);
                    $('#widgetCheckInTime').text(checkInDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
                }

                // Update check-out time
                if (data.checkOutTime) {
                    const checkOutDate = new Date(data.checkOutTime);
                    $('#widgetCheckOutTime').text(checkOutDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
                } else {
                    $('#widgetCheckOutTime').text('--:--');
                }

                // Calculate and update working hours in "Xh Ym" format
                // Match web attendance page: always calculate to NOW for live elapsed time
                if (data.checkInTime) {
                    const checkInDate = new Date(data.checkInTime);
                    const now = new Date();
                    const workingMinutes = Math.abs(now - checkInDate) / (1000 * 60);

                    const hours = Math.floor(workingMinutes / 60);
                    const minutes = Math.floor(workingMinutes % 60);
                    $('#widgetWorkingHours').text(hours + 'h ' + minutes + 'm');
                }
            }
        },
        error: function() {
            console.log('Failed to update widget data');
        }
    });
}

// Widget Check-In function
function widgetCheckIn() {
    const btn = document.getElementById('widgetCheckInBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>{{ __("Processing...") }}';

    // Get current time
    const now = new Date();
    const time = now.toTimeString().split(' ')[0]; // HH:MM:SS format

    $.ajax({
        url: '{{ route("hrcore.attendance.web-check-in") }}',
        type: 'POST',
        data: {
            time: time,
            latitude: null,
            longitude: null
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success!") }}',
                    text: response.data?.message || '{{ __("Checked in successfully!") }}',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error!") }}',
                    text: response.data || '{{ __("Failed to check in. Please try again.") }}'
                });
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        },
        error: function(xhr) {
            let errorMessage = '{{ __("An error occurred. Please try again.") }}';
            if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage = xhr.responseJSON.data;
            }
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error!") }}',
                text: errorMessage
            });
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}

// Widget Check-Out function
function widgetCheckOut() {
    const btn = document.getElementById('widgetCheckOutBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>{{ __("Processing...") }}';

    // Get current time
    const now = new Date();
    const time = now.toTimeString().split(' ')[0]; // HH:MM:SS format

    $.ajax({
        url: '{{ route("hrcore.attendance.web-check-in") }}',
        type: 'POST',
        data: {
            time: time,
            latitude: null,
            longitude: null
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success!") }}',
                    text: response.data?.message || '{{ __("Checked out successfully!") }}',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error!") }}',
                    text: response.data || '{{ __("Failed to check out. Please try again.") }}'
                });
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        },
        error: function(xhr) {
            let errorMessage = '{{ __("An error occurred. Please try again.") }}';
            if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage = xhr.responseJSON.data;
            }
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error!") }}',
                text: errorMessage
            });
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}

// Widget Break Toggle function
function widgetToggleBreak() {
    const btn = document.getElementById('widgetBreakBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>{{ __("Processing...") }}';

    $.ajax({
        url: '{{ route("hrcore.attendance.start-stop-break") }}',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                const message = response.data.isOnBreak 
                    ? '{{ __("Break started successfully!") }}' 
                    : '{{ __("Break stopped successfully!") }}';
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success!") }}',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error!") }}',
                    text: response.data || '{{ __("Failed to toggle break. Please try again.") }}'
                });
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        },
        error: function(xhr) {
            let errorMessage = '{{ __("An error occurred. Please try again.") }}';
            if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage = xhr.responseJSON.data;
            }
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error!") }}',
                text: errorMessage
            });
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}
</script>
