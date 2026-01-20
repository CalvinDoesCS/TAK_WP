@php
    use App\Enums\UserAccountStatus;
    use Carbon\Carbon;

    $role = $user->roles()->first()->name ?? '';

    // Status badge styling
    $statusBadgeClass = match($user->status) {
        UserAccountStatus::ACTIVE => $user->isUnderProbation() ? 'bg-warning' : 'bg-success',
        UserAccountStatus::ONBOARDING => 'bg-info',
        UserAccountStatus::SUSPENDED => 'bg-danger',
        UserAccountStatus::TERMINATED => 'bg-dark',
        UserAccountStatus::RELIEVED, UserAccountStatus::RETIRED => 'bg-secondary',
        UserAccountStatus::INACTIVE => 'bg-label-warning',
        default => 'bg-label-secondary'
    };

    $statusLabel = match($user->status) {
        UserAccountStatus::ACTIVE => $user->isUnderProbation() ? __('On Probation') : __('Active'),
        UserAccountStatus::ONBOARDING => __('Onboarding'),
        UserAccountStatus::SUSPENDED => __('Suspended'),
        UserAccountStatus::TERMINATED => __('Terminated'),
        UserAccountStatus::RELIEVED => __('Relieved'),
        UserAccountStatus::RETIRED => __('Retired'),
        UserAccountStatus::INACTIVE => __('Inactive'),
        default => ucfirst($user->status->value)
    };

    // Calculate tenure
    $joiningDate = Carbon::parse($user->date_of_joining);
    $tenureYears = (int) $joiningDate->diffInYears(now());
    $tenureMonths = (int) ($joiningDate->copy()->addYears($tenureYears)->diffInMonths(now()));

    // Default tab based on status
    $defaultTab = $isExitedEmployee ? 'timeline' : 'overview';
@endphp

@extends('layouts.layoutMaster')

@section('title', __('Employee Details') . ' - ' . $user->getFullName())

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
    ])
@endsection

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/page-user-view.scss',
        'resources/assets/css/employee-view.css',
        'resources/assets/scss/employee-timeline.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js'
    ])
@endsection

@section('content')

{{-- Breadcrumb --}}
<x-breadcrumb
    :title="$user->getFullName()"
    :breadcrumbs="[
        ['name' => __('Employees'), 'url' => route('employees.index')],
        ['name' => $user->getFullName(), 'url' => '']
    ]"
    :home-url="url('/')"
/>

{{-- Profile Header Card --}}
<div class="card mb-6">
    <div class="card-body">
        <div class="row">
            {{-- Profile Picture Section --}}
            <div class="col-lg-2 col-md-3 col-12 text-center mb-4 mb-md-0">
                <div class="position-relative d-inline-block" style="width: 120px; height: 120px;">
                    @if ($user->profile_picture)
                        <img class="img-fluid rounded-circle" src="{{ $user->getProfilePicture() }}"
                             style="width: 120px; height: 120px; object-fit: cover;"
                             alt="{{ $user->getFullName() }}" id="userProfilePicture" />
                    @else
                        <div class="avatar" style="width: 120px; height: 120px;">
                            <span class="avatar-initial rounded-circle bg-label-primary"
                                  style="width: 120px; height: 120px; font-size: 3rem; display: flex; align-items: center; justify-content: center;">
                                {{ $user->getInitials() }}
                            </span>
                        </div>
                    @endif

                    @if (!$isExitedEmployee)
                        <button class="btn btn-sm btn-icon btn-outline-primary rounded-circle position-absolute"
                                style="bottom: 0; right: 0;"
                                id="changeProfilePictureButton" title="{{ __('Change Photo') }}">
                            <i class="bx bx-camera"></i>
                        </button>

                        <form id="profilePictureForm" action="{{ route('employees.changeEmployeeProfilePicture') }}"
                              method="POST" enctype="multipart/form-data" style="display: none;">
                            @csrf
                            <input type="hidden" name="userId" value="{{ $user->id }}">
                            <input type="file" id="file" name="file" accept="image/*">
                        </form>
                    @endif
                </div>
            </div>

            {{-- Profile Info Section --}}
            <div class="col-lg-7 col-md-6 col-12">
                <h4 class="mb-1">{{ $user->getFullName() }}</h4>
                <div class="mb-2">
                    <span class="badge bg-label-secondary me-2">{{ $user->code }}</span>
                    <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    @if ($user->isUnderProbation() && $probationDaysRemaining !== null)
                        <span class="badge bg-label-warning ms-2">
                            <i class="bx bx-time-five"></i> {{ $probationDaysRemaining }} {{ __('days left') }}
                        </span>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-3 mb-3">
                    <div>
                        <i class="bx bx-briefcase text-muted"></i>
                        <span class="fw-medium">{{ $user->designation ? $user->designation->name : __('N/A') }}</span>
                    </div>
                    <div>
                        <i class="bx bx-group text-muted"></i>
                        <span>{{ $user->team ? $user->team->name : __('N/A') }}</span>
                    </div>
                    <div>
                        <i class="bx bx-calendar text-muted"></i>
                        <span>{{ $tenureYears }}y {{ $tenureMonths }}m {{ __('tenure') }}</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span><i class="bx bx-envelope text-muted me-1"></i>{{ $user->email }}</span>
                    <span><i class="bx bx-phone text-muted me-1"></i>{{ $user->phone }}</span>
                </div>
            </div>

            {{-- Quick Actions Section --}}
            <div class="col-lg-3 col-md-3 col-12">
                <div class="d-flex flex-column gap-2">
                    @if (!$isExitedEmployee)
                        {{-- Edit Dropdown --}}
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-edit me-1"></i>{{ __('Edit') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditBasicInfo" onclick="loadEditBasicInfo()">
                                    <i class="bx bx-user me-1"></i>{{ __('Basic Information') }}
                                </a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditWorkInfo" onclick="loadSelectList()">
                                    <i class="bx bx-briefcase me-1"></i>{{ __('Work Information') }}
                                </a></li>
                            </ul>
                        </div>

                        {{-- Actions Dropdown for ACTIVE (Not Probation) --}}
                        @if ($user->status === UserAccountStatus::ACTIVE && !$user->isUnderProbation())
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-cog me-1"></i>{{ __('Actions') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="markAsInactive()">
                                        <i class="bx bx-user-x me-1"></i>{{ __('Mark as Inactive') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="openSuspendModal()">
                                        <i class="bx bx-pause-circle me-1"></i>{{ __('Suspend Employee') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#terminateEmployeeModal">
                                        <i class="bx bx-block me-1"></i>{{ __('Initiate Termination') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="retireEmployee()">
                                        <i class="bx bx-home-heart me-1"></i>{{ __('Mark as Retired') }}
                                    </a></li>
                                </ul>
                            </div>
                        @endif

                        {{-- Actions Dropdown for ACTIVE (Under Probation) --}}
                        @if ($user->status === UserAccountStatus::ACTIVE && $user->isUnderProbation())
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-cog me-1"></i>{{ __('Actions') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#confirmProbationOffcanvas">
                                        <i class="bx bx-check me-1"></i>{{ __('Confirm Probation') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#extendProbationOffcanvas">
                                        <i class="bx bx-time-five me-1"></i>{{ __('Extend Probation') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#failProbationOffcanvas">
                                        <i class="bx bx-x me-1"></i>{{ __('Fail Probation') }}
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="openSuspendModal()">
                                        <i class="bx bx-pause-circle me-1"></i>{{ __('Suspend Employee') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#terminateEmployeeModal">
                                        <i class="bx bx-block me-1"></i>{{ __('Initiate Termination') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="retireEmployee()">
                                        <i class="bx bx-home-heart me-1"></i>{{ __('Mark as Retired') }}
                                    </a></li>
                                </ul>
                            </div>
                        @endif

                        {{-- Actions Dropdown for SUSPENDED --}}
                        @if ($user->status === UserAccountStatus::SUSPENDED)
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-cog me-1"></i>{{ __('Actions') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="reactivateEmployee()">
                                        <i class="bx bx-play-circle me-1"></i>{{ __('Reactivate Employee') }}
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#terminateEmployeeModal">
                                        <i class="bx bx-block me-1"></i>{{ __('Initiate Termination') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="retireEmployee()">
                                        <i class="bx bx-home-heart me-1"></i>{{ __('Mark as Retired') }}
                                    </a></li>
                                </ul>
                            </div>
                        @endif

                        {{-- Actions Dropdown for INACTIVE --}}
                        @if ($user->status === UserAccountStatus::INACTIVE)
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-cog me-1"></i>{{ __('Actions') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="reactivateEmployee()">
                                        <i class="bx bx-check-circle me-1"></i>{{ __('Activate Employee') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#terminateEmployeeModal">
                                        <i class="bx bx-block me-1"></i>{{ __('Initiate Termination') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="retireEmployee()">
                                        <i class="bx bx-home-heart me-1"></i>{{ __('Mark as Retired') }}
                                    </a></li>
                                </ul>
                            </div>
                        @endif

                        {{-- Primary Button for TERMINATED --}}
                        @if ($user->status === UserAccountStatus::TERMINATED)
                            <button type="button" class="btn btn-secondary" onclick="markAsRelieved()">
                                <i class="bx bx-check-circle me-1"></i>{{ __('Mark as Relieved') }}
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status-Based Alerts --}}
@if ($user->status === UserAccountStatus::ONBOARDING)
    <div class="alert alert-info mb-6" role="alert">
        <div>
            <h5 class="alert-heading mb-1">
                <i class="bx bx-rocket me-2"></i>{{ __('Onboarding in Progress') }}
            </h5>
            <p class="mb-0">{{ __('This employee is currently going through the onboarding process.') }}</p>
        </div>
    </div>
@endif

@if ($user->isUnderProbation() && !$isExitedEmployee)
    <div class="alert alert-warning mb-6" role="alert">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    <i class="bx bx-time-five me-2"></i>{{ __('Probation Period Active') }}
                </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted">{{ __('Start Date') }}</small>
                        <p class="mb-0 fw-medium">{{ $user->date_of_joining ? Carbon::parse($user->date_of_joining)->format('d M Y') : __('N/A') }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">{{ __('End Date') }}</small>
                        <p class="mb-0 fw-medium">{{ Carbon::parse($user->probation_end_date)->format('d M Y') }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">{{ __('Remaining') }}</small>
                        <p class="mb-0 fw-medium">{{ $probationDaysRemaining }} {{ __('days') }}</p>
                    </div>
                </div>
            </div>
            <div class="btn-group ms-3">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="offcanvas" data-bs-target="#confirmProbationOffcanvas">
                    <i class="bx bx-check me-1"></i>{{ __('Confirm') }}
                </button>
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="offcanvas" data-bs-target="#extendProbationOffcanvas">
                    <i class="bx bx-time me-1"></i>{{ __('Extend') }}
                </button>
                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="offcanvas" data-bs-target="#failProbationOffcanvas">
                    <i class="bx bx-x me-1"></i>{{ __('Fail') }}
                </button>
            </div>
        </div>
    </div>
@endif

@if ($user->status === UserAccountStatus::SUSPENDED)
    <div class="alert alert-danger mb-6" role="alert">
        <h5 class="alert-heading">
            <i class="bx bx-error me-2"></i>{{ __('Employee Suspended') }}
        </h5>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <strong>{{ __('Suspension Date') }}:</strong> {{ $user->suspended_at ? Carbon::parse($user->suspended_at)->format('d M Y') : __('N/A') }}
            </div>
            @if ($user->suspension_reason)
                <div class="col-12 mt-2">
                    <strong>{{ __('Reason') }}:</strong> {{ $user->suspension_reason }}
                </div>
            @endif
        </div>
    </div>
@endif

@if ($isExitedEmployee)
    <div class="alert alert-{{ $user->status == UserAccountStatus::TERMINATED ? 'danger' : 'warning' }} mb-6" role="alert">
        <h5 class="alert-heading">
            <i class="bx {{ $user->status == UserAccountStatus::TERMINATED ? 'bx-block' : 'bx-info-circle' }} me-2"></i>
            @if ($user->status == UserAccountStatus::TERMINATED)
                {{ __('Employment Terminated') }}
            @elseif ($user->status == UserAccountStatus::RELIEVED)
                {{ __('Employee Relieved') }}
            @else
                {{ __('Employee Retired') }}
            @endif
        </h5>
        <hr>
        <div class="row">
            @if ($user->status == UserAccountStatus::RETIRED)
                <div class="col-md-6">
                    <strong>{{ __('Retirement Date') }}:</strong> {{ $user->retired_at ? Carbon::parse($user->retired_at)->format('d M Y') : __('N/A') }}
                </div>
                @if ($user->retired_reason)
                    <div class="col-12 mt-2">
                        <strong>{{ __('Reason') }}:</strong> {{ $user->retired_reason }}
                    </div>
                @endif
            @else
                <div class="col-md-6">
                    <strong>{{ __('Exit Date') }}:</strong> {{ $user->exit_date ? Carbon::parse($user->exit_date)->format('d M Y') : __('N/A') }}
                </div>
                <div class="col-md-6">
                    <strong>{{ __('Last Working Day') }}:</strong> {{ $user->last_working_day ? Carbon::parse($user->last_working_day)->format('d M Y') : __('N/A') }}
                </div>
                @if ($user->exit_reason)
                    <div class="col-12 mt-2">
                        <strong>{{ __('Reason') }}:</strong> {{ $user->exit_reason }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endif

{{-- Main Content Tabs --}}
<div class="nav-align-top mb-6">
    <ul class="nav nav-pills flex-column flex-md-row mb-6" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $defaultTab === 'overview' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                <i class="bx bx-home me-1"></i>{{ __('Overview') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button" role="tab">
                <i class="bx bx-user me-1"></i>{{ __('Personal Info') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employment" type="button" role="tab">
                <i class="bx bx-briefcase me-1"></i>{{ __('Employment') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button" role="tab">
                <i class="bx bx-calendar-check me-1"></i>{{ __('Attendance') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-leave" type="button" role="tab">
                <i class="bx bx-calendar-x me-1"></i>{{ __('Leave') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $defaultTab === 'timeline' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-timeline" type="button" role="tab">
                <i class="bx bx-time-five me-1"></i>{{ __('Timeline') }}
            </button>
        </li>
    </ul>

    <div class="tab-content p-0">
        {{-- Overview Tab --}}
        <div class="tab-pane fade {{ $defaultTab === 'overview' ? 'show active' : '' }}" id="tab-overview" role="tabpanel">
            @include('employees.tabs.overview')
        </div>

        {{-- Personal Info Tab --}}
        <div class="tab-pane fade" id="tab-personal" role="tabpanel">
            @include('employees.tabs.personal')
        </div>

        {{-- Employment Tab --}}
        <div class="tab-pane fade" id="tab-employment" role="tabpanel">
            @include('employees.tabs.employment')
        </div>

        {{-- Attendance Tab --}}
        <div class="tab-pane fade" id="tab-attendance" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div id="attendanceTabContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Leave Tab --}}
        <div class="tab-pane fade" id="tab-leave" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div id="leaveTabContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline Tab --}}
        <div class="tab-pane fade {{ $defaultTab === 'timeline' ? 'show active' : '' }}" id="tab-timeline" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Employee Lifecycle Timeline') }}</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" id="filterAllEvents">{{ __('All') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="filterStatusEvents">{{ __('Status') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="filterProbationEvents">{{ __('Probation') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="filterChangeEvents">{{ __('Changes') }}</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="timelineContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Include Modals and Offcanvas --}}
@include('_partials._modals.employees.edit_basic_info')
@include('_partials._modals.employees.edit_work_info')

@if (!$isExitedEmployee)
    @include('employees.modals.probation')
    @include('employees.modals.terminate')
    @include('employees.modals.suspend')
@endif

@endsection

@section('page-script')
<script>
    const pageData = {
        userId: {{ $user->id }},
        userStatus: '{{ $user->status->value }}',
        isExited: {{ $isExitedEmployee ? 'true' : 'false' }},
        defaultTab: '{{ $defaultTab }}',
        urls: {
            overview: '{{ route('employees.overview', $user->id) }}',
            attendance: '{{ route('employees.attendance', $user->id) }}',
            leave: '{{ route('employees.leave', $user->id) }}',
            timeline: '{{ route('employees.timeline', $user->id) }}',
            terminate: '{{ route('employees.terminate', $user->id) }}',
            suspend: '{{ route('employees.suspend', $user->id) }}',
            reactivate: '{{ route('employees.reactivate', $user->id) }}',
            markRelieved: '{{ route('employees.markRelieved', $user->id) }}',
            markInactive: '{{ route('employees.markInactive', $user->id) }}',
            retire: '{{ route('employees.retire', $user->id) }}',
            getGeofenceGroups: '{{ route('employee.getGeofenceGroups') }}',
            getIpGroups: '{{ route('employee.getIpGroups') }}',
            getQrGroups: '{{ route('employee.getQrGroups') }}',
            getSites: '{{ route('employee.getSites') }}',
            getDynamicQrDevices: '{{ route('employee.getDynamicQrDevices') }}'
        },
        labels: {
            loading: @json(__('Loading...')),
            error: @json(__('Error')),
            success: @json(__('Success')),
            confirmDelete: @json(__('Are you sure?')),
            deleted: @json(__('Deleted successfully')),
            errorLoading: @json(__('Failed to load data')),
            errorLoadingTimeline: @json(__('Failed to load timeline data')),
            noTimelineEvents: @json(__('No timeline events found'))
        }
    };

    // Legacy compatibility
    var user = @json($user);
    var role = @json($role);
    var attendanceType = @json($user->attendance_type);
</script>

@vite(['resources/js/main-helper.js', 'resources/assets/js/app/employee-view.js', 'resources/assets/js/app/employee-timeline.js'])
@endsection
