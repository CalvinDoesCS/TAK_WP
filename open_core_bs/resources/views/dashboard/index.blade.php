@php
    $quotes = [
        ["text" => "Success is the result of hard work, determination, and the courage to pursue greatness."],
        ["text" => "Teamwork divides the task and multiplies the success."],
        ["text" => "You don't have to be perfect to make an impact; every small effort counts."],
        ["text" => "Productivity is not about doing more; it's about focusing on what truly matters."],
        ["text" => "Your attitude, not your aptitude, will determine your altitude."],
        ["text" => "The secret to great teamwork is trust, communication, and a shared goal."],
        ["text" => "Every accomplishment starts with the decision to try."],
        ["text" => "When we work together, the impossible becomes possible."],
        ["text" => "Productivity is never an accident. It is always the result of commitment to excellence."],
        ["text" => "Believe in your ability to shape the future with the work you do today."],
    ];

    $quote = $quotes[array_rand($quotes)];
    $quoteText = $quote["text"];
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Dashboard'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/app/dashboard-index.js'])
@endsection

@section('content')
    {{-- Hero Section --}}
    <div class="card bg-transparent shadow-none my-6 border-0">
        <div class="card-body row p-0 pb-6 g-6">
            <div class="col-12 col-lg-8 card-separator">
                <h5 class="mb-2">{{ __('Welcome back') }}, <span class="h4">{{ auth()->user()->getFullName() }} 👋🏻</span></h5>
                <div class="col-12 col-lg-8">
                    <p class="text-muted">{{ $quoteText }}</p>
                </div>
                <div class="d-flex justify-content-between flex-wrap gap-4 me-12 mt-4">
                    <div class="d-flex align-items-center gap-4 me-6 me-sm-0">
                        <div class="avatar avatar-lg">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="bx bx-time-five"></i>
                            </div>
                        </div>
                        <div class="content-right">
                            <p class="mb-0 fw-medium">{{ __('Hours Today') }}</p>
                            <h4 class="text-primary mb-0">{{ $core['todayHours'] ?? 0 }}h</h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4 me-6 me-sm-0">
                        <div class="avatar avatar-lg">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="bx bx-user-check"></i>
                            </div>
                        </div>
                        <div class="content-right">
                            <p class="mb-0 fw-medium">{{ __('Present Today') }}</p>
                            <h4 class="text-success mb-0">{{ $core['todayPresent'] ?? 0 }}</h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="avatar avatar-lg">
                            <div class="avatar-initial bg-label-warning rounded">
                                <i class="bx bx-briefcase"></i>
                            </div>
                        </div>
                        <div class="content-right">
                            <p class="mb-0 fw-medium">{{ __('On Leave') }}</p>
                            <h4 class="text-warning mb-0">{{ $core['todayOnLeave'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4 ps-md-4 ps-lg-6">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ __('Time Spending') }}</h5>
                        <p class="mb-9">{{ __('Weekly Overview') }}</p>
                        <div class="time-spending-chart">
                            <h4 class="mb-2">{{ $core['weeklyHours'] ?? 0 }}<span class="text-body">h</span></h4>
                            <span class="badge bg-label-success">{{ __('Weekly working hours') }}</span>
                        </div>
                    </div>
                    <div id="weeklyReportChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Core HR & Attendance Overview --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <i class="bx bx-user"></i> {{ __('Attendance & HR Overview') }}
            </h5>
        </div>
        <x-dashboard.stat-card
            :title="__('Total Employees')"
            :value="$core['totalEmployees'] ?? 0"
            icon="bx-group"
            iconColor="primary"
        />
        <x-dashboard.stat-card
            :title="__('Present Today')"
            :value="$core['todayPresent'] ?? 0"
            icon="bx-user-check"
            iconColor="success"
            :change="$core['todayPresent'] . ' ' . __('present')"
            changeType="up"
        />
        <x-dashboard.stat-card
            :title="__('Absent Today')"
            :value="$core['todayAbsent'] ?? 0"
            icon="bx-user-x"
            iconColor="danger"
        />
        <x-dashboard.stat-card
            :title="__('On Leave Today')"
            :value="$core['todayOnLeave'] ?? 0"
            icon="bx-calendar"
            iconColor="warning"
        />
    </div>

    {{-- HR & Workforce Section --}}
    @if(!empty($hrWorkforce))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-briefcase-alt-2"></i> {{ __('HR & Workforce Management') }}
                </h5>
            </div>

            {{-- Payroll --}}
            @if(isset($hrWorkforce['payroll']) && $hrWorkforce['payroll']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Last Payroll')"
                    :value="$hrWorkforce['payroll']['lastProcessed'] ?? __('N/A')"
                    icon="bx-money"
                    iconColor="success"
                    :url="\Illuminate\Support\Facades\Route::has('payroll.index') ? route('payroll.index') : null"
                />
            @endif

            {{-- Recruitment --}}
            @if(isset($hrWorkforce['recruitment']) && $hrWorkforce['recruitment']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Open Positions')"
                    :value="$hrWorkforce['recruitment']['openPositions'] ?? 0"
                    icon="bx-briefcase"
                    iconColor="info"
                    :url="\Illuminate\Support\Facades\Route::has('recruitment.index') ? route('recruitment.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Applications')"
                    :value="$hrWorkforce['recruitment']['totalApplications'] ?? 0"
                    icon="bx-file"
                    iconColor="primary"
                />
            @endif

            {{-- LMS --}}
            @if(isset($hrWorkforce['lms']) && $hrWorkforce['lms']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Active Courses')"
                    :value="$hrWorkforce['lms']['activeCourses'] ?? 0"
                    icon="bx-book"
                    iconColor="info"
                    :url="\Illuminate\Support\Facades\Route::has('lms.index') ? route('lms.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Completion Rate')"
                    :value="($hrWorkforce['lms']['completionRate'] ?? 0) . '%'"
                    icon="bx-trophy"
                    iconColor="success"
                />
            @endif

            {{-- Assets --}}
            @if(isset($hrWorkforce['assets']) && $hrWorkforce['assets']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Total Assets')"
                    :value="$hrWorkforce['assets']['total'] ?? 0"
                    icon="bx-package"
                    iconColor="primary"
                    :url="\Illuminate\Support\Facades\Route::has('assets.index') ? route('assets.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Available Assets')"
                    :value="$hrWorkforce['assets']['available'] ?? 0"
                    icon="bx-check-circle"
                    iconColor="success"
                />
            @endif
        </div>
    @endif

    {{-- Finance & Accounting Section --}}
    @if(!empty($finance))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-wallet"></i> {{ __('Finance & Accounting') }}
                </h5>
            </div>

            {{-- Accounting Core --}}
            @if(isset($finance['accounting']) && $finance['accounting']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Monthly Income')"
                    :value="'$' . number_format($finance['accounting']['monthlyIncome'] ?? 0, 2)"
                    icon="bx-trending-up"
                    iconColor="success"
                    :url="\Illuminate\Support\Facades\Route::has('accountingcore.index') ? route('accountingcore.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Monthly Expense')"
                    :value="'$' . number_format($finance['accounting']['monthlyExpense'] ?? 0, 2)"
                    icon="bx-trending-down"
                    iconColor="danger"
                />
                <x-dashboard.stat-card
                    :title="__('Net Profit')"
                    :value="'$' . number_format($finance['accounting']['netProfit'] ?? 0, 2)"
                    icon="bx-dollar-circle"
                    :iconColor="($finance['accounting']['netProfit'] ?? 0) >= 0 ? 'success' : 'danger'"
                />
            @endif

            {{-- Loans --}}
            @if(isset($finance['loans']) && $finance['loans']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Pending Loans')"
                    :value="$finance['loans']['pending'] ?? 0"
                    icon="bx-dollar"
                    iconColor="warning"
                    :url="\Illuminate\Support\Facades\Route::has('loan.admin.index') ? route('loan.admin.index') : null"
                />
            @endif

            {{-- Expenses --}}
            @if(isset($finance['expenses']) && $finance['expenses']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Pending Expenses')"
                    :value="$finance['expenses']['pending'] ?? 0"
                    icon="bx-receipt"
                    iconColor="info"
                    :url="route('expenseRequests.index')"
                />
            @endif

            {{-- Payment Collection --}}
            @if(isset($finance['paymentCollection']) && $finance['paymentCollection']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Collected Today')"
                    :value="'$' . number_format($finance['paymentCollection']['collectedToday'] ?? 0, 2)"
                    icon="bx-credit-card"
                    iconColor="success"
                />
            @endif
        </div>
    @endif

    {{-- Business Operations Section --}}
    @if(!empty($businessOperations))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-cog"></i> {{ __('Business Operations') }}
                </h5>
            </div>

            @if(isset($businessOperations['systemCore']) && $businessOperations['systemCore']['enabled'])
                {{-- Customers --}}
                <x-dashboard.stat-card
                    :title="__('Customers')"
                    :value="$businessOperations['systemCore']['totalCustomers'] ?? 0"
                    icon="bx-user"
                    iconColor="primary"
                    :change="($businessOperations['systemCore']['activeCustomers'] ?? 0) . ' ' . __('active')"
                    changeType="up"
                    :url="\Illuminate\Support\Facades\Route::has('systemcore.customers.index') ? route('systemcore.customers.index') : null"
                />

                {{-- Suppliers --}}
                <x-dashboard.stat-card
                    :title="__('Suppliers')"
                    :value="$businessOperations['systemCore']['totalSuppliers'] ?? 0"
                    icon="bx-store"
                    iconColor="info"
                    :change="($businessOperations['systemCore']['activeSuppliers'] ?? 0) . ' ' . __('active')"
                    changeType="up"
                    :url="\Illuminate\Support\Facades\Route::has('systemcore.suppliers.index') ? route('systemcore.suppliers.index') : null"
                />

                {{-- Products --}}
                <x-dashboard.stat-card
                    :title="__('Products')"
                    :value="$businessOperations['systemCore']['totalProducts'] ?? 0"
                    icon="bx-package"
                    iconColor="success"
                    :change="($businessOperations['systemCore']['activeProducts'] ?? 0) . ' ' . __('active')"
                    changeType="up"
                    :url="\Illuminate\Support\Facades\Route::has('systemcore.products.index') ? route('systemcore.products.index') : null"
                />

                {{-- Sales This Month --}}
                <x-dashboard.stat-card
                    :title="__('Sales This Month')"
                    :value="'$' . number_format($businessOperations['systemCore']['salesThisMonth'] ?? 0, 2)"
                    icon="bx-trending-up"
                    iconColor="warning"
                    :change="($businessOperations['systemCore']['salesOrdersToday'] ?? 0) . ' ' . __('orders today')"
                    changeType="neutral"
                    :url="\Illuminate\Support\Facades\Route::has('systemcore.sales-orders.index') ? route('systemcore.sales-orders.index') : null"
                />
            @endif
        </div>
    @endif

    {{-- Sales & Field Operations Section --}}
    @if(!empty($sales))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-shopping-bag"></i> {{ __('Sales & Field Operations') }}
                </h5>
            </div>

            {{-- Field Manager --}}
            @if(isset($sales['fieldManager']) && $sales['fieldManager']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Visits Today')"
                    :value="$sales['fieldManager']['visitsToday'] ?? 0"
                    icon="bx-map"
                    iconColor="primary"
                    :url="\Illuminate\Support\Facades\Route::has('fieldmanager.index') ? route('fieldmanager.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Total Clients')"
                    :value="$sales['fieldManager']['totalClients'] ?? 0"
                    icon="bx-user-circle"
                    iconColor="info"
                />
            @endif

            {{-- Field Task --}}
            @if(isset($sales['fieldTask']) && $sales['fieldTask']['enabled'])
                <x-dashboard.stat-card
                    :title="__('New Tasks')"
                    :value="$sales['fieldTask']['newTasks'] ?? 0"
                    icon="bx-task"
                    iconColor="warning"
                    :url="\Illuminate\Support\Facades\Route::has('fieldtask.index') ? route('fieldtask.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('In Progress')"
                    :value="$sales['fieldTask']['inProgress'] ?? 0"
                    icon="bx-loader"
                    iconColor="info"
                />
                <x-dashboard.stat-card
                    :title="__('Overdue Tasks')"
                    :value="$sales['fieldTask']['overdue'] ?? 0"
                    icon="bx-error"
                    iconColor="danger"
                />
            @endif

            {{-- Product Order --}}
            @if(isset($sales['productOrder']) && $sales['productOrder']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Orders Today')"
                    :value="$sales['productOrder']['ordersToday'] ?? 0"
                    icon="bx-cart"
                    iconColor="success"
                    :url="\Illuminate\Support\Facades\Route::has('productorder.index') ? route('productorder.index') : null"
                />
            @endif

            {{-- Sales Target --}}
            @if(isset($sales['salesTarget']) && $sales['salesTarget']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Target Achievement')"
                    :value="($sales['salesTarget']['achievementPercentage'] ?? 0) . '%'"
                    icon="bx-target-lock"
                    :iconColor="($sales['salesTarget']['achievementPercentage'] ?? 0) >= 100 ? 'success' : 'warning'"
                    :url="\Illuminate\Support\Facades\Route::has('salestarget.index') ? route('salestarget.index') : null"
                />
            @endif
        </div>
    @endif

    {{-- Communications Section --}}
    @if(!empty($communications))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-message-dots"></i> {{ __('Communications') }}
                </h5>
            </div>

            {{-- Agora Call --}}
            @if(isset($communications['agoraCall']) && $communications['agoraCall']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Calls Today')"
                    :value="$communications['agoraCall']['callsToday'] ?? 0"
                    icon="bx-phone"
                    iconColor="info"
                />
                <x-dashboard.stat-card
                    :title="__('Missed Calls')"
                    :value="$communications['agoraCall']['missedCallsToday'] ?? 0"
                    icon="bx-phone-off"
                    iconColor="danger"
                />
            @endif

            {{-- Notice Board --}}
            @if(isset($communications['noticeBoard']) && $communications['noticeBoard']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Active Notices')"
                    :value="$communications['noticeBoard']['activeNotices'] ?? 0"
                    icon="bx-notification"
                    iconColor="warning"
                    :url="\Illuminate\Support\Facades\Route::has('noticeboard.index') ? route('noticeboard.index') : null"
                />
            @endif
        </div>
    @endif

    {{-- Documents & Operations Section --}}
    @if(!empty($documents) || !empty($operations))
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                    <i class="bx bx-folder"></i> {{ __('Documents & Operations') }}
                </h5>
            </div>

            {{-- Document Management --}}
            @if(isset($documents['documentManagement']) && $documents['documentManagement']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Total Documents')"
                    :value="$documents['documentManagement']['totalDocuments'] ?? 0"
                    icon="bx-file"
                    iconColor="primary"
                    :url="\Illuminate\Support\Facades\Route::has('documentmanagement.index') ? route('documentmanagement.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Pending Requests')"
                    :value="$documents['documentManagement']['pendingRequests'] ?? 0"
                    icon="bx-hourglass"
                    iconColor="warning"
                />
            @endif

            {{-- Form Builder --}}
            @if(isset($documents['formBuilder']) && $documents['formBuilder']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Active Forms')"
                    :value="$documents['formBuilder']['activeForms'] ?? 0"
                    icon="bx-edit"
                    iconColor="info"
                    :url="\Illuminate\Support\Facades\Route::has('forms.index') ? route('forms.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Submissions Today')"
                    :value="$documents['formBuilder']['submissionsToday'] ?? 0"
                    icon="bx-paper-plane"
                    iconColor="success"
                />
            @endif

            {{-- Calendar --}}
            @if(isset($operations['calendar']) && $operations['calendar']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Events Today')"
                    :value="$operations['calendar']['eventsToday'] ?? 0"
                    icon="bx-calendar"
                    iconColor="primary"
                    :url="\Illuminate\Support\Facades\Route::has('calendar.index') ? route('calendar.index') : null"
                />
                <x-dashboard.stat-card
                    :title="__('Upcoming Events')"
                    :value="$operations['calendar']['upcomingEvents'] ?? 0"
                    icon="bx-calendar-event"
                    iconColor="info"
                />
            @endif

            {{-- Notes --}}
            @if(isset($operations['notes']) && $operations['notes']['enabled'])
                <x-dashboard.stat-card
                    :title="__('Total Notes')"
                    :value="$operations['notes']['totalNotes'] ?? 0"
                    icon="bx-notepad"
                    iconColor="warning"
                    :url="\Illuminate\Support\Facades\Route::has('notes.index') ? route('notes.index') : null"
                />
            @endif
        </div>
    @endif

    {{-- Charts and Detailed Views --}}
    <div class="row mb-4">
        {{-- Department Performance Chart --}}
        <x-dashboard.chart-widget
            :title="__('Department Performance')"
            :subtitle="__('Present vs Absent by Department')"
            icon="bx-bar-chart"
            chartId="topDepartmentsChart"
            height="350"
            colClass="col-lg-8 col-md-12"
        />

        {{-- Pending Requests --}}
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bx bx-time"></i>
                        {{ __('Pending Requests') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @if(\Illuminate\Support\Facades\Route::has('leaveRequests.index'))
                            <a href="{{ route('leaveRequests.index') }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bx bx-calendar me-2"></i>{{ __('Leave Requests') }}</span>
                                <span class="badge bg-primary rounded-pill">{{ $core['pendingLeaveRequests'] ?? 0 }}</span>
                            </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('expenseRequests.index'))
                            <a href="{{ route('expenseRequests.index') }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bx bx-receipt me-2"></i>{{ __('Expense Requests') }}</span>
                                <span class="badge bg-success rounded-pill">{{ $core['pendingExpenseRequests'] ?? 0 }}</span>
                            </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('loan.index'))
                            <a href="{{ route('loan.index') }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bx bx-dollar me-2"></i>{{ __('Loan Requests') }}</span>
                                <span class="badge bg-danger rounded-pill">{{ $core['pendingLoanRequests'] ?? 0 }}</span>
                            </a>
                        @endif
                        @if(isset($documents['documentManagement']) && $documents['documentManagement']['enabled'])
                            <a href="{{ route('documentmanagement.document-requests.index') }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bx bx-file me-2"></i>{{ __('Document Requests') }}</span>
                                <span class="badge bg-warning rounded-pill">{{ $documents['documentManagement']['pendingRequests'] ?? 0 }}</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-footer text-center text-muted">
                    <small>{{ __('Last updated') }} {{ now()->diffForHumans() }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activities --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-history"></i>
                            {{ __('Recent Activities') }}
                        </h5>
                        <p class="card-subtitle my-0 text-muted">{{ __('Latest activities of users') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <ul id="activityList" class="list-group list-group-flush overflow-auto" style="max-height: 400px;">
                        {{-- Activities will be dynamically loaded via JavaScript --}}
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Pass data to JavaScript --}}
    <script>
        const pageData = {
            urls: {
                recentActivities: '{{ route('getRecentActivities') }}',
                departmentPerformance: '{{ route('getDepartmentPerformanceAjax') }}'
            },
            enabledModules: @json($enabledModules ?? []),
            labels: {
                loading: @json(__('Loading...')),
                noActivities: @json(__('No recent activities found')),
                error: @json(__('Error loading data'))
            },
            weeklyHours: {{ $core['weeklyHours'] ?? 0 }}
        };
    </script>
@endsection
