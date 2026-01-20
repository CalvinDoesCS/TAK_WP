@php
  use Illuminate\Support\Str;
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Employee Dashboard'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/card-analytics.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/dashboards-analytics.js'
  ])
@endsection

@section('content')
  <div class="row gy-6">
    <!-- Welcome Card -->
    <div class="col-12">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-sm-8">
              <div class="card-title text-white mb-3">
                <h4 class="text-white mb-2">{{ __('Welcome back,') }} {{ $user->getFullName() }}! 👋</h4>
                <p class="text-white mb-0">{{ __('Here\'s what\'s happening with your work today.') }}</p>
              </div>
            </div>
            <div class="col-sm-4 text-center text-sm-end">
              <div class="card-text">
                <img src="{{asset('assets/img/illustrations/boy-with-rocket-light.png')}}" alt="welcome" width="120">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <!-- Check In/Out -->
            <div class="col-md-3 col-sm-6">
              @if(!$todayAttendance)
                <form action="{{ route('hrcore.attendance.web-check-in') }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-success w-100">
                    <i class="bx bx-log-in me-2"></i>{{ __('Check In') }}
                  </button>
                </form>
              @elseif($todayAttendance && !$todayAttendance->check_out_time)
                <form action="{{ route('hrcore.attendance.web-check-out') }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-warning w-100">
                    <i class="bx bx-log-out me-2"></i>{{ __('Check Out') }}
                  </button>
                </form>
              @else
                <button type="button" class="btn btn-secondary w-100" disabled>
                  <i class="bx bx-check me-2"></i>{{ __('Completed') }}
                </button>
              @endif
            </div>

            <!-- Apply Leave -->
            <div class="col-md-3 col-sm-6">
              <a href="{{ route('hrcore.my.leaves.apply') }}" class="btn btn-info w-100">
                <i class="bx bx-calendar-minus me-2"></i>{{ __('Apply Leave') }}
              </a>
            </div>

            <!-- Submit Expense -->
            <div class="col-md-3 col-sm-6">
              <a href="{{ route('hrcore.my.expenses.create') }}" class="btn btn-warning w-100">
                <i class="bx bx-receipt me-2"></i>{{ __('Submit Expense') }}
              </a>
            </div>

            <!-- View Profile -->
            <div class="col-md-3 col-sm-6">
              <a href="{{ route('hrcore.my.profile') }}" class="btn btn-primary w-100">
                <i class="bx bx-user me-2"></i>{{ __('My Profile') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="flex-grow-1">
              <span class="fw-medium d-block mb-1">{{ __('This Week Hours') }}</span>
              <h3 class="card-title mb-1">
                @php
                  $weeklyHours = floor($weeklyWorkingHours);
                  $weeklyMinutes = round(($weeklyWorkingHours - $weeklyHours) * 60);
                @endphp
                @if($weeklyHours > 0)
                  {{ $weeklyHours }}h {{ $weeklyMinutes }}m
                @else
                  {{ $weeklyMinutes }}m
                @endif
              </h3>
              <small class="text-success fw-medium">
                <i class="bx bx-up-arrow-alt"></i>{{ __('This week') }}
              </small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-time-five"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="flex-grow-1">
              <span class="fw-medium d-block mb-1">{{ __('This Month Hours') }}</span>
              <h3 class="card-title mb-1">
                @php
                  $monthlyHours = floor($monthlyWorkingHours);
                  $monthlyMinutes = round(($monthlyWorkingHours - $monthlyHours) * 60);
                @endphp
                @if($monthlyHours > 0)
                  {{ $monthlyHours }}h {{ $monthlyMinutes }}m
                @else
                  {{ $monthlyMinutes }}m
                @endif
              </h3>
              <small class="text-info fw-medium">
                <i class="bx bx-calendar"></i>{{ __('This month') }}
              </small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-calendar-check"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="flex-grow-1">
              <span class="fw-medium d-block mb-1">{{ __('Total Leaves') }}</span>
              <h3 class="card-title mb-1">{{ $totalLeaves }}</h3>
              <small class="text-warning fw-medium">
                {{ $approvedLeaves }} {{ __('approved') }}, {{ $pendingLeaves }} {{ __('pending') }}
              </small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-calendar-minus"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if($addonService->isAddonEnabled('FieldTask'))
      <div class="col-xl-3 col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="flex-grow-1">
                <span class="fw-medium d-block mb-1">{{ __('My Tasks') }}</span>
                <h3 class="card-title mb-1">{{ $totalTasks }}</h3>
                <small class="text-success fw-medium">
                  {{ $completedTasks }} {{ __('completed') }}, {{ $pendingTasks }} {{ __('pending') }}
                </small>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="bx bx-task"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- Recent Activities -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Recent Activities') }}</h5>
            <div class="dropdown">
              <button class="btn p-0" type="button" id="recentActivities" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bx bx-dots-vertical-rounded"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="recentActivities">
                <a class="dropdown-item" href="{{ route('hrcore.my.leaves') }}">{{ __('View All Leaves') }}</a>
                <a class="dropdown-item" href="{{ route('hrcore.my.expenses') }}">{{ __('View All Expenses') }}</a>
                @if($addonService->isAddonEnabled('FieldTask'))
                  <a class="dropdown-item" href="{{ route('fieldtask.tasks.index') }}">{{ __('View All Tasks') }}</a>
                @endif
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <button class="nav-link active" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" role="tab">
                  {{ __('Leaves') }}
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" role="tab">
                  {{ __('Expenses') }}
                </button>
              </li>
              @if($addonService->isAddonEnabled('FieldTask'))
                <li class="nav-item">
                  <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" role="tab">
                    {{ __('Tasks') }}
                  </button>
                </li>
              @endif
            </ul>
            <div class="tab-content">
              <!-- Recent Leave Requests -->
              <div class="tab-pane fade show active" id="leaves" role="tabpanel">
                @if($recentLeaveRequests->count() > 0)
                  <div class="timeline">
                    @foreach($recentLeaveRequests as $leave)
                      <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'danger') }}">
                          <i class="bx bx-calendar-minus"></i>
                        </span>
                        <div class="timeline-event">
                          <div class="timeline-header">
                            <h6 class="mb-0">{{ __('Leave Request') }}</h6>
                            <small class="text-muted">{{ $leave->created_at->diffForHumans() }}</small>
                          </div>
                          <p class="mb-2">{{ $leave->from_date->format('M d') }} - {{ $leave->to_date->format('M d, Y') }}</p>
                          <div class="d-flex align-items-center">
                            <span class="badge bg-label-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'danger') }}">
                              {{ ucfirst($leave->status->value) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @else
                  <div class="text-center py-4">
                    <i class="bx bx-calendar-minus bx-lg text-muted"></i>
                    <p class="text-muted mt-2">{{ __('No leave requests yet') }}</p>
                  </div>
                @endif
              </div>

              <!-- Recent Expense Requests -->
              <div class="tab-pane fade" id="expenses" role="tabpanel">
                @if($recentExpenseRequests->count() > 0)
                  <div class="timeline">
                    @foreach($recentExpenseRequests as $expense)
                      <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-{{ $expense->status === 'approved' ? 'success' : ($expense->status === 'pending' ? 'warning' : 'danger') }}">
                          <i class="bx bx-receipt"></i>
                        </span>
                        <div class="timeline-event">
                          <div class="timeline-header">
                            <h6 class="mb-0">{{ __('Expense Request') }}</h6>
                            <small class="text-muted">{{ $expense->created_at->diffForHumans() }}</small>
                          </div>
                          <p class="mb-2">{{ $expense->description ?? __('Expense submission') }}</p>
                          <div class="d-flex align-items-center justify-content-between">
                            <span class="badge bg-label-{{ $expense->status === 'approved' ? 'success' : ($expense->status === 'pending' ? 'warning' : 'danger') }}">
                              {{ ucfirst($expense->status) }}
                            </span>
                            <strong class="text-primary">${{ number_format($expense->amount, 2) }}</strong>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @else
                  <div class="text-center py-4">
                    <i class="bx bx-receipt bx-lg text-muted"></i>
                    <p class="text-muted mt-2">{{ __('No expense requests yet') }}</p>
                  </div>
                @endif
              </div>

              <!-- Recent Tasks -->
              @if($addonService->isAddonEnabled('FieldTask'))
                <div class="tab-pane fade" id="tasks" role="tabpanel">
                  @if($recentTasks->count() > 0)
                    <div class="timeline">
                      @foreach($recentTasks as $task)
                        <div class="timeline-item">
                          <span class="timeline-indicator-advanced timeline-indicator-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in_progress' ? 'warning' : 'primary') }}">
                            <i class="bx bx-task"></i>
                          </span>
                          <div class="timeline-event">
                            <div class="timeline-header">
                              <h6 class="mb-0">{{ $task->title ?? __('Task') }}</h6>
                              <small class="text-muted">{{ $task->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-2">{{ Str::limit($task->description ?? __('Task assignment'), 100) }}</p>
                            <div class="d-flex align-items-center">
                              <span class="badge bg-label-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in_progress' ? 'warning' : 'primary') }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                              </span>
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <div class="text-center py-4">
                      <i class="bx bx-task bx-lg text-muted"></i>
                      <p class="text-muted mt-2">{{ __('No tasks assigned yet') }}</p>
                    </div>
                  @endif
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Attendance -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __("Today's Attendance") }}</h5>
        </div>
        <div class="card-body">
          @if($todayAttendance)
            <div class="text-center">
              <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-{{ $todayAttendance->check_out_time ? 'success' : 'warning' }}">
                  <i class="bx bx-{{ $todayAttendance->check_out_time ? 'check' : 'time' }} bx-lg"></i>
                </span>
              </div>

              <div class="mb-3">
                <h6 class="mb-1">{{ __('Check In Time') }}</h6>
                <p class="text-primary fw-medium">{{ $todayAttendance->check_in_time->format('h:i A') }}</p>
              </div>

              @if($todayAttendance->check_out_time)
                <div class="mb-3">
                  <h6 class="mb-1">{{ __('Check Out Time') }}</h6>
                  <p class="text-success fw-medium">{{ $todayAttendance->check_out_time->format('h:i A') }}</p>
                </div>

                <div class="mb-3">
                  <h6 class="mb-1">{{ __('Total Hours') }}</h6>
                  <p class="text-info fw-medium">
                    @php
                      $totalHours = floor($todayAttendance->check_in_time->diffInHours($todayAttendance->check_out_time));
                      $totalMinutes = $todayAttendance->check_in_time->diffInMinutes($todayAttendance->check_out_time) % 60;
                    @endphp
                    @if($totalHours > 0)
                      {{ $totalHours }}h {{ $totalMinutes }}m
                    @else
                      {{ $totalMinutes }}m
                    @endif
                  </p>
                </div>

                <span class="badge bg-success">{{ __('Completed') }}</span>
              @else
                <div class="mb-3">
                  <h6 class="mb-1">{{ __('Currently Working') }}</h6>
                  <p class="text-warning fw-medium" id="working-duration">
                    @php
                      $currentHours = floor($todayAttendance->check_in_time->diffInHours(now()));
                      $currentMinutes = $todayAttendance->check_in_time->diffInMinutes(now()) % 60;
                    @endphp
                    @if($currentHours > 0)
                      {{ $currentHours }}h {{ $currentMinutes }}m
                    @else
                      {{ $currentMinutes }}m
                    @endif
                  </p>
                </div>

                <span class="badge bg-warning">{{ __('In Progress') }}</span>
              @endif
            </div>
          @else
            <div class="text-center">
              <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                  <i class="bx bx-clock bx-lg"></i>
                </span>
              </div>
              <h6 class="mb-2">{{ __('Not Checked In') }}</h6>
              <p class="text-muted mb-3">{{ __('You haven\'t checked in today yet.') }}</p>
              <form action="{{ route('hrcore.attendance.web-check-in') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">
                  <i class="bx bx-log-in me-2"></i>{{ __('Check In Now') }}
                </button>
              </form>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Live working duration update -->
  @if($todayAttendance && !$todayAttendance->check_out_time)
    <script>
      setInterval(function() {
        const checkInTime = new Date('{{ $todayAttendance->check_in_time->toISOString() }}');
        const now = new Date();
        const diffMs = now - checkInTime;
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        document.getElementById('working-duration').textContent = hours + 'h ' + minutes + 'm';
      }, 60000); // Update every minute
    </script>
  @endif
@endsection
