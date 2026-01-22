@php
    use Carbon\Carbon;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Employment Information') }}</h5>
        @if (!$isExitedEmployee)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasEditWorkInfo" onclick="loadSelectList()">
                <i class="bx bx-edit me-1"></i>{{ __('Edit') }}
            </button>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-4">
            {{-- Work Information --}}
            <div class="col-12">
                <h6 class="text-muted mb-3">{{ __('Work Information') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Employee Code') }}</label>
                        <p class="mb-0 fw-medium font-monospace">{{ $user->code }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Designation') }}</label>
                        <p class="mb-0">{{ $user->designation ? $user->designation->name : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Team') }}</label>
                        <p class="mb-0">{{ $user->team ? $user->team->name : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Shift') }}</label>
                        <p class="mb-0">{{ $user->shift ? $user->shift->name : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Reporting Manager') }}</label>
                        <p class="mb-0">{{ $user->reporting_to_id ? $user->getReportingToUserName() : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Role') }}</label>
                        <p class="mb-0">
                            <span class="badge bg-label-primary">{{ $role }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12"><hr></div>

            {{-- Joining Details --}}
            <div class="col-12">
                <h6 class="text-muted mb-3">{{ __('Joining Details') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Date of Joining') }}</label>
                        <p class="mb-0">{{ Carbon::parse($user->date_of_joining)->format('d M Y') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Tenure') }}</label>
                        <p class="mb-0">{{ $tenureYears }} {{ __('years') }}, {{ $tenureMonths }} {{ __('months') }}</p>
                    </div>
                    @if ($user->probation_end_date)
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Probation End Date') }}</label>
                            <p class="mb-0">{{ Carbon::parse($user->probation_end_date)->format('d M Y') }}</p>
                        </div>
                    @endif
                    @if ($user->probation_confirmed_at)
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Probation Confirmed') }}</label>
                            <p class="mb-0">{{ Carbon::parse($user->probation_confirmed_at)->format('d M Y') }}</p>
                        </div>
                    @endif
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Employment Type') }}</label>
                        <p class="mb-0">{{ __('Full Time') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Attendance Type') }}</label>
                        <p class="mb-0">
                            <span class="badge bg-label-info">{{ ucfirst(str_replace('_', ' ', $user->attendance_type)) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            @if (($enabledModules['LocationManagement'] ?? false) && $user->location_id)
                <div class="col-12"><hr></div>

                {{-- Location Information --}}
                <div class="col-12">
                    <h6 class="text-muted mb-3">{{ __('Location Assignment') }}</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Location') }}</label>
                            <p class="mb-0">{{ $user->location ? $user->location->name : __('N/A') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Compensation is now managed through Payroll module's EmployeeSalaryStructure --}}
        </div>
    </div>
</div>
