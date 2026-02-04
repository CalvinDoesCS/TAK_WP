{{-- Quick Stats Cards --}}
<div class="row g-6 mb-6">
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">{{ __('Total Leave') }}</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['totalLeave'] }}</h4>
                        </div>
                        <small class="mb-0">{{ __('Days') }}</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-calendar-x bx-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">{{ __('Attendance') }}</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['attendancePercentage'] }}%</h4>
                        </div>
                        <small class="mb-0">{{ __('This Month') }}</small>
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

    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">{{ __('Pending Approvals') }}</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['pendingApprovals'] }}</h4>
                        </div>
                        <small class="mb-0">{{ __('Requests') }}</small>
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

    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">{{ __('Active Warnings') }}</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['activeWarnings'] }}</h4>
                        </div>
                        <small class="mb-0">{{ __('Disciplinary') }}</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="bx bx-error bx-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-6">
    {{-- Employment Status Card --}}
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0">{{ __('Employment Status') }}</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-shield text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Status') }}:</span>
                            <span class="badge bg-label-{{ $user->status->value === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($employmentInfo['status']) }}
                            </span>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-briefcase text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Designation') }}:</span>
                            <span>{{ $employmentInfo['designation'] }}</span>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-group text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Team') }}:</span>
                            <span>{{ $employmentInfo['team'] }}</span>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-user text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Reporting To') }}:</span>
                            <span>{{ $employmentInfo['reportingTo'] }}</span>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-calendar text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Joining Date') }}:</span>
                            <span>{{ $employmentInfo['joiningDate'] }}</span>
                        </div>
                    </li>
                    <li class="mb-0">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-time-five text-muted me-2"></i>
                            <span class="fw-medium me-2">{{ __('Tenure') }}:</span>
                            <span>{{ $employmentInfo['tenure'] }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="col-xl-8 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0">{{ __('Recent Activity') }}</h5>
                <a href="javascript:void(0);" onclick="loadTab('timeline')" class="text-muted small">
                    {{ __('View All') }} <i class="bx bx-chevron-right"></i>
                </a>
            </div>
            <div class="card-body">
                @if($recentActivity && $recentActivity->count() > 0)
                    <ul class="timeline mb-0">
                        @foreach($recentActivity as $event)
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-{{ $event->getEventColor() }}"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-1">
                                        <h6 class="mb-0">{{ $event->event_type_display }}</h6>
                                        <small class="text-muted">{{ $event->event_date->diffForHumans() }}</small>
                                    </div>
                                    @if($event->notes)
                                        <p class="mb-0 text-muted small">{{ $event->notes }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-time-five display-4 text-muted"></i>
                        <p class="text-muted mt-2">{{ __('No recent activity') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
