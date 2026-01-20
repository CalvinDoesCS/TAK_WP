@php
  $title = __('Employee Card View');
@endphp

@extends('layouts/layoutMaster')

@section('title', $title)

@section('page-style')
  <style>
    /* General Styles */
    .employee-card {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      background: #fff;
    }

    .employee-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    }

    /* Profile Section */
    .profile-section {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 10px 15px;
      border-bottom: 1px solid #f1f1f1;
    }

    .avatar-wrapper {
      width: 60px;
      height: 60px;
      overflow: hidden;
      border-radius: 50%;
      border: 2px solid #ddd;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f5f5f5;
    }

    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-initial {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      font-size: 1rem;
      font-weight: bold;
      color: #555;
      text-transform: uppercase;
      background: #e0e0e0;
    }

    .details h6 {
      margin: 0;
      font-size: 16px;
      font-weight: bold;
    }

    .details small {
      font-size: 12px;
      color: #6c757d;
    }

    /* Status Icons */
    .status-icons {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 15px;
      border-bottom: 1px solid #f1f1f1;
    }

    .status-icons div {
      text-align: center;
    }

    .status-icons i {
      font-size: 18px;
      margin-bottom: 5px;
    }

    .status-icons span {
      font-size: 12px;
    }

    /* Attendance Info */
    .attendance-info {
      padding: 10px 15px;
      border-bottom: 1px solid #f1f1f1;
      font-size: 14px;
    }

    .attendance-info span {
      display: block;
    }

    /* Metrics */
    .metrics {
      padding: 10px 15px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      border-bottom: 1px solid #f1f1f1;
    }

    .metrics span {
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* Footer */
    .card-footer {
      padding: 10px 15px;
      background: #f9fafb;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      color: #6c757d;
    }

    .card-footer a {
      font-size: 12px;
      color: #007bff;
      text-decoration: none;
    }

    /* Loading skeleton */
    .skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
    }

    @keyframes loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .skeleton-card {
      height: 350px;
      border-radius: 12px;
      margin-bottom: 12px;
    }
  </style>
@endsection

@section('content')

{{-- Breadcrumb --}}
<x-breadcrumb
  :title="__('Employee Card View')"
  :breadcrumbs="[
    ['name' => __('Card View'), 'url' => '']
  ]"
  :home-url="route('dashboard')"
/>

<!-- Filters and Controls -->
<div class="filter-tabs d-flex align-items-center justify-content-between mb-4">
  <div>
    <div class="btn-group" role="group" aria-label="{{ __('Filter employees') }}">
      <button class="btn btn-outline-primary filter-btn active" data-filter="all">
        {{ __('All Checked In') }} <span class="badge bg-label-primary" id="allCount">{{ $totalCheckedIn ?? 0 }}</span>
      </button>
      <button class="btn btn-outline-success filter-btn" data-filter="online">
        {{ __('Online') }} <span class="badge bg-success" id="onlineCount">{{ $totalOnline ?? 0 }}</span>
      </button>
      <button class="btn btn-outline-danger filter-btn" data-filter="offline">
        {{ __('Offline') }} <span class="badge bg-danger" id="offlineCount">{{ $totalOffline ?? 0 }}</span>
      </button>
    </div>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <button class="btn btn-outline-primary" id="refreshBtn">
      <i class="bx bx-refresh me-2"></i>{{ __('Refresh') }}
    </button>
    <div class="form-check form-switch mb-0">
      <input class="form-check-input" type="checkbox" id="autoRefreshSwitch" checked>
      <label class="form-check-label" for="autoRefreshSwitch">{{ __('Auto Refresh') }}</label>
    </div>
  </div>
</div>

<!-- Loading skeleton (shown on initial load) -->
<div id="loadingSkeleton">
  <div class="row g-3">
    @for($i = 0; $i < 8; $i++)
      <div class="col-md-3">
        <div class="skeleton skeleton-card"></div>
      </div>
    @endfor
  </div>
</div>

<!-- Employee Grid -->
<div id="employeeGrid" class="d-none">
  @foreach($teams as $team)
    <div class="team-section mb-4" data-team-id="{{ $team['id'] }}">
      <h5 class="mb-3">
        {{ $team['name'] }}
        <span class="text-muted">({{ $team['totalEmployees'] }} {{ __('Employees') }})</span>
      </h5>
      <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3" id="team-{{ $team['id'] }}-cards">
        @foreach($team['cardItems'] as $cardItem)
          <div class="col employee-card-col" data-employee-id="{{ $cardItem['id'] }}" data-status="{{ $cardItem['isOnline'] ? 'online' : 'offline' }}">
            <div class="card employee-card">

              <!-- Profile Section -->
              <div class="profile-section">
                <div class="avatar-wrapper">
                  @if($cardItem['profilePicture'])
                    <img src="{{ $cardItem['profilePicture'] }}" alt="{{ $cardItem['name'] }}">
                  @else
                    <span class="avatar-initial rounded-circle bg-label-primary">{{ $cardItem['initials'] }}</span>
                  @endif
                </div>
                <div class="details">
                  <h6>{{ $cardItem['name'] }}</h6>
                  <small>{{ __('Code') }}: {{ $cardItem['employeeCode'] }}</small>
                </div>
              </div>

              <!-- Status Icons -->
              <div class="status-icons">
                <div id="battery-{{ $cardItem['id'] }}">
                  <i class="bx bx-battery text-primary"></i>
                  <span>{{ $cardItem['batteryLevel'] }}%</span>
                </div>
                <div id="wifi-{{ $cardItem['id'] }}">
                  <i class="bx {{ $cardItem['isWifiOn'] ? 'bx-wifi text-success' : 'bx-wifi-off text-danger' }}"></i>
                  <span>{{ __('WiFi') }}</span>
                </div>
                <div id="gps-{{ $cardItem['id'] }}">
                  <i class="bx {{ $cardItem['isGpsOn'] ? 'bx-current-location text-success' : 'bx-current-location text-danger' }}"></i>
                  <span>{{ __('GPS') }}</span>
                </div>
              </div>

              <!-- Attendance Info -->
              <div class="attendance-info">
                <span class="d-flex justify-content-between align-items-center">
                  <strong>{{ __('In Time') }}:</strong>
                  <span id="in-time-{{ $cardItem['id'] }}">{{ $cardItem['attendanceInAt'] ? \Carbon\Carbon::parse($cardItem['attendanceInAt'])->format('h:i A') : 'N/A' }}</span>
                </span>
                <span class="d-flex justify-content-between align-items-center mt-1">
                  <strong>{{ __('Out Time') }}:</strong>
                  <span id="out-time-{{ $cardItem['id'] }}">{{ $cardItem['attendanceOutAt'] ? \Carbon\Carbon::parse($cardItem['attendanceOutAt'])->format('h:i A') : 'N/A' }}</span>
                </span>
              </div>

              <!-- Metrics -->
              <div class="metrics">
                <span id="visits-{{ $cardItem['id'] }}">
                  <i class="bx bx-map text-primary"></i> {{ $cardItem['visitsCount'] }} {{ __('Visits') }}
                </span>
                <span id="orders-{{ $cardItem['id'] }}">
                  <i class="bx bx-cart text-success"></i> {{ $cardItem['ordersCount'] }} {{ __('Orders') }}
                </span>
                <span id="forms-{{ $cardItem['id'] }}">
                  <i class="bx bx-file text-info"></i> {{ $cardItem['formsFilled'] }} {{ __('Forms') }}
                </span>
              </div>

              <!-- Footer -->
              <div class="card-footer">
                <a href="{{ route('liveLocationView') }}" id="location-{{ $cardItem['id'] }}">
                  <i class="bx bx-map"></i> {{ __('Open in Map') }}
                </a>
                <span id="updated-{{ $cardItem['id'] }}">{{ __('Last Updated') }}: {{ $cardItem['updatedAt'] }}</span>
              </div>

            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endforeach
</div>

@endsection

@section('page-script')
  {{-- Page Data Configuration --}}
  <script>
    const pageData = {
      urls: {
        cardViewAjax: @json(route('cardViewAjax'))
      },
      labels: {
        all: @json(__('All Checked In')),
        online: @json(__('Online')),
        offline: @json(__('Offline')),
        employees: @json(__('Employees')),
        code: @json(__('Code')),
        wifi: @json(__('WiFi')),
        gps: @json(__('GPS')),
        inTime: @json(__('In Time')),
        outTime: @json(__('Out Time')),
        visits: @json(__('Visits')),
        orders: @json(__('Orders')),
        forms: @json(__('Forms')),
        openInMap: @json(__('Open in Map')),
        lastUpdated: @json(__('Last Updated')),
        loadingError: @json(__('Failed to load data')),
        tryAgain: @json(__('Please try again')),
        autoRefreshOn: @json(__('Auto-refresh enabled')),
        autoRefreshOff: @json(__('Auto-refresh disabled'))
      },
      settings: {
        autoRefreshInterval: 30000 // 30 seconds
      }
    };
  </script>

  {{-- Application JavaScript --}}
  @vite(['resources/assets/js/app/dashboard-card-view.js'])
@endsection
