@extends('layouts.layoutMaster')

@section('title', __('Monthly Attendance Calendar'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
  ])
@endsection

@section('page-script')
  @vite([
    'resources/js/main-select2.js',
    'resources/assets/js/app/attendance-monthly-calendar.js',
  ])
@endsection

@section('page-style')
  <style>
    .calendar-container {
      overflow-x: auto;
      max-width: 100%;
      position: relative;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar styling */
    .calendar-container::-webkit-scrollbar {
      height: 10px;
    }

    .calendar-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    [data-style="dark"] .calendar-container::-webkit-scrollbar-track {
      background: #2b2c40;
    }

    .calendar-container::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .calendar-container::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    [data-style="dark"] .calendar-container::-webkit-scrollbar-thumb {
      background: #4b4d69;
    }

    [data-style="dark"] .calendar-container::-webkit-scrollbar-thumb:hover {
      background: #6c6d84;
    }

    .calendar-table {
      min-width: 1200px;
      border-collapse: separate;
      border-spacing: 0;
      position: relative;
      width: auto !important;
    }

    .calendar-table th,
    .calendar-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
      vertical-align: middle;
    }

    .calendar-table tbody {
      position: relative;
    }

    [data-style="dark"] .calendar-table th,
    [data-style="dark"] .calendar-table td {
      border-color: #4b4d69;
    }

    .calendar-table thead th {
      background-color: #f8f9fa;
      font-weight: 600;
      position: sticky;
      top: 0;
      z-index: 20;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    [data-style="dark"] .calendar-table thead th {
      background-color: #2b2c40;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .employee-cell {
      position: sticky !important;
      left: 0 !important;
      background-color: white !important;
      z-index: 10;
      min-width: 220px;
      max-width: 220px;
      width: 220px;
      text-align: left !important;
      padding: 12px !important;
      box-shadow: 2px 0 4px rgba(0, 0, 0, 0.08);
      border-right: 2px solid #ddd !important;
    }

    [data-style="dark"] .employee-cell {
      background-color: #2b2c40 !important;
      box-shadow: 2px 0 4px rgba(0, 0, 0, 0.3);
      border-right-color: #4b4d69 !important;
    }

    .calendar-table thead th.employee-cell {
      z-index: 30 !important;
    }

    .calendar-table tbody td.employee-cell {
      z-index: 10 !important;
    }

    .day-cell {
      min-width: 45px;
      width: 45px;
      height: 45px;
      padding: 4px;
      cursor: pointer;
      transition: background-color 0.2s, opacity 0.2s;
    }

    .day-cell:hover {
      opacity: 0.85;
      background-color: rgba(0, 0, 0, 0.02);
    }

    [data-style="dark"] .day-cell:hover {
      background-color: rgba(255, 255, 255, 0.05);
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 13px;
      transition: opacity 0.2s;
      flex-shrink: 0;
    }

    .status-badge:hover {
      opacity: 0.9;
    }

    .weekend-header {
      background-color: #e9ecef !important;
    }

    [data-style="dark"] .weekend-header {
      background-color: #3a3c52 !important;
    }

    .today-header {
      background-color: #e3f2fd !important;
      font-weight: 700;
      position: relative;
      border-left: 3px solid #2196F3 !important;
      border-right: 3px solid #2196F3 !important;
    }

    .today-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #2196F3, #42A5F5);
    }

    [data-style="dark"] .today-header {
      background-color: #1e4976 !important;
      border-left-color: #42A5F5 !important;
      border-right-color: #42A5F5 !important;
    }

    /* Today's column cells styling */
    .day-cell.today-cell {
      border-left: 2px solid #2196F3 !important;
      border-right: 2px solid #2196F3 !important;
      background-color: #f5f9ff;
    }

    [data-style="dark"] .day-cell.today-cell {
      background-color: #1a2942;
      border-left-color: #42A5F5 !important;
      border-right-color: #42A5F5 !important;
    }

    .employee-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .employee-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
    }

    .employee-avatar-initials {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 14px;
      flex-shrink: 0;
    }

    .employee-details {
      flex: 1;
    }

    .employee-name {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 2px;
    }

    .employee-meta {
      font-size: 11px;
      color: #6c757d;
    }

    .legend-item {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-right: 20px;
      margin-bottom: 8px;
    }

    .legend-badge {
      width: 28px;
      height: 28px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      font-weight: 600;
      font-size: 12px;
    }

    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    [data-style="dark"] .loading-overlay {
      background: rgba(43, 44, 64, 0.9);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 64px;
      color: #ccc;
      margin-bottom: 20px;
    }

    [data-style="dark"] .empty-state i {
      color: #6c6d84;
    }

    /* Real-time badge indicator - dashed border style */
    .badge-realtime {
      border: 2px dashed currentColor !important;
      opacity: 0.9;
      position: relative;
    }

    .badge-realtime::after {
      content: '●';
      position: absolute;
      top: -3px;
      right: -3px;
      font-size: 8px;
      color: #ff9f43;
      animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.5;
      }
    }

    /* Tooltip styling */
    [title] {
      cursor: help;
    }

    /* Offcanvas styling */
    #attendanceDetailOffcanvas {
      width: 400px;
      max-width: 90vw;
    }

    #attendanceDetailOffcanvas .offcanvas-header {
      border-bottom: 1px solid #ddd;
      padding: 1.25rem 1.5rem;
    }

    [data-style="dark"] #attendanceDetailOffcanvas .offcanvas-header {
      border-bottom-color: #4b4d69;
    }

    #attendanceDetailOffcanvas .offcanvas-body {
      padding: 1.5rem;
    }

    /* Real-time notification styling in offcanvas */
    #realtimeNotification {
      border-left: 4px solid #ff9f43;
      background-color: #fff5e6;
    }

    [data-style="dark"] #realtimeNotification {
      background-color: #3d3416;
      border-left-color: #ff9f43;
    }
  </style>
@endsection

@section('content')
  <x-breadcrumb :title="__('Monthly Attendance Calendar')" :links="[
      ['url' => route('dashboard'), 'label' => __('Home')],
      ['url' => route('hrcore.attendance.index'), 'label' => __('Attendance')],
      ['label' => __('Monthly Calendar')],
  ]" />

  <div class="card">
    <div class="card-header">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h5 class="mb-0">
            <i class="bx bx-calendar me-2"></i>
            {{ __('Monthly Attendance Calendar') }}
          </h5>
        </div>
        <div class="col-md-6 text-end">
          <span id="currentMonthDisplay" class="badge bg-label-primary fs-6"></span>
        </div>
      </div>
    </div>

    <div class="card-body">
      <!-- Filters -->
      <div class="row mb-4">
        <div class="col-md-2 mb-3">
          <label for="monthSelect" class="form-label">{{ __('Month') }}</label>
          <select id="monthSelect" class="form-select">
            <option value="1">{{ __('January') }}</option>
            <option value="2">{{ __('February') }}</option>
            <option value="3">{{ __('March') }}</option>
            <option value="4">{{ __('April') }}</option>
            <option value="5">{{ __('May') }}</option>
            <option value="6">{{ __('June') }}</option>
            <option value="7">{{ __('July') }}</option>
            <option value="8">{{ __('August') }}</option>
            <option value="9">{{ __('September') }}</option>
            <option value="10">{{ __('October') }}</option>
            <option value="11">{{ __('November') }}</option>
            <option value="12">{{ __('December') }}</option>
          </select>
        </div>

        <div class="col-md-2 mb-3">
          <label for="yearSelect" class="form-label">{{ __('Year') }}</label>
          <select id="yearSelect" class="form-select">
            <!-- Years will be populated by JavaScript -->
          </select>
        </div>

        <div class="col-md-3 mb-3">
          <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
          <select id="departmentFilter" class="form-select select2">
            <option value="">{{ __('All Departments') }}</option>
          </select>
        </div>

        <div class="col-md-3 mb-3">
          <label for="searchEmployee" class="form-label">{{ __('Search Employee') }}</label>
          <input type="text" id="searchEmployee" class="form-control"
                 placeholder="{{ __('Search by name, code, email...') }}" />
        </div>

        <div class="col-md-1 mb-3">
          <label class="form-label d-block">&nbsp;</label>
          <button type="button" id="refreshBtn" class="btn btn-primary w-100">
            <i class="bx bx-refresh me-1"></i>
            {{ __('Refresh') }}
          </button>
        </div>

        <div class="col-md-2 mb-3">
          <label class="form-label d-block">&nbsp;</label>
          <button type="button" id="recalculateBtn" class="btn btn-warning w-100">
            <i class="bx bx-calculator me-1"></i>
            {{ __('Recalculate') }}
          </button>
        </div>
      </div>

      <!-- Legend -->
      <div class="alert alert-info mb-4">
        <div class="d-flex flex-wrap align-items-center">
          <strong class="me-3">{{ __('Legend') }}:</strong>
          <div class="legend-item">
            <span class="legend-badge bg-success text-white">P</span>
            <span>{{ __('Present') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-danger text-white">A</span>
            <span>{{ __('Absent') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-warning text-dark">L</span>
            <span>{{ __('Late') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-danger text-white">E</span>
            <span>{{ __('Early Checkout') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-info text-white">H</span>
            <span>{{ __('Half Day') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-primary text-white">LV</span>
            <span>{{ __('Leave') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-info text-white">HD</span>
            <span>{{ __('Holiday') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-secondary text-white">W</span>
            <span>{{ __('Weekend') }}</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge bg-light text-muted">—</span>
            <span>{{ __('Not Marked') }}</span>
          </div>
          <div class="legend-item ms-auto">
            <span class="legend-badge bg-success text-white badge-realtime">P</span>
            <span>{{ __('Live Data (finalized at 23:30)') }}</span>
          </div>
        </div>
      </div>

      <!-- Calendar -->
      <div class="calendar-container">
        <table class="calendar-table table table-bordered table-hover" id="calendarTable">
          <thead>
          <tr>
            <th class="employee-cell">{{ __('Employee') }}</th>
            <!-- Days will be populated by JavaScript -->
          </tr>
          </thead>
          <tbody id="calendarBody">
          <!-- Rows will be populated by JavaScript -->
          </tbody>
        </table>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display: none;">
          <i class="bx bx-calendar-x"></i>
          <h5>{{ __('No Employees Found') }}</h5>
          <p class="text-muted">{{ __('Try adjusting your filters or search criteria') }}</p>
        </div>
      </div>

      <!-- Scroll Hint -->
      <div class="text-center mt-2">
        <small class="text-muted">
          <i class="bx bx-mouse"></i>
          {{ __('Scroll horizontally to view all days') }}
        </small>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="text-center">
      <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">{{ __('Loading...') }}</span>
      </div>
      <h5 id="loadingMessage" class="fw-semibold">{{ __('Loading calendar data...') }}</h5>
      <p class="text-muted" id="loadingSubMessage">{{ __('Please wait') }}</p>
    </div>
  </div>

  <!-- Attendance Detail Offcanvas (Right Side) -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="attendanceDetailOffcanvas" aria-labelledby="attendanceDetailOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="attendanceDetailOffcanvasLabel">{{ __('Attendance Details') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <!-- Real-time Data Notification (will be shown/hidden by JS) -->
      <div id="realtimeNotification" class="alert alert-warning d-none mb-3" role="alert">
        <div class="d-flex align-items-center">
          <i class="bx bx-info-circle me-2"></i>
          <div>
            <strong>{{ __('Live Data') }}</strong>
            <p class="mb-0 small">{{ __('This data is calculated in real-time and will be finalized at 23:30 tonight.') }}</p>
          </div>
        </div>
      </div>

      <div id="attendanceDetailContent">
        <!-- Content will be populated by JavaScript -->
      </div>
    </div>
  </div>
@endsection
