@php
  $title = __('Live Location');
@endphp

@extends('layouts/layoutMaster')

@section('title', $title)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
<style>
  /* Status indicator dot */
  .status-indicator .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
  }

  .bg-success {
    background-color: #28a745 !important;
  }

  .bg-danger {
    background-color: #dc3545 !important;
  }

  /* Employee card active state */
  .employee-card.active {
    border: 2px solid #696cff;
    box-shadow: 0 4px 15px rgba(105, 108, 255, 0.2);
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
    height: 150px;
    border-radius: 8px;
    margin-bottom: 12px;
  }

  /* Map loading overlay */
  .map-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  /* Filter button active state */
  .filter-btn.active {
    background-color: #696cff !important;
    color: white !important;
  }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #697a8d;
  }

  .empty-state i {
    font-size: 4rem;
    opacity: 0.5;
  }
</style>
@endsection

@section('content')

{{-- Breadcrumb --}}
<x-breadcrumb
  :title="__('Live Location')"
  :breadcrumbs="[
    ['name' => __('Live Location'), 'url' => '']
  ]"
  :home-url="route('dashboard')"
/>

<div class="row g-3">
  {{-- Employee List Column (4-col) --}}
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bx bx-user me-2"></i>{{ __('Employee List') }}
        </h5>
        <input
          type="text"
          id="employeeSearch"
          class="form-control mt-2"
          placeholder="{{ __('Search employees...') }}"
          aria-label="{{ __('Search employees') }}"
        >
      </div>
      <div class="card-body overflow-auto" style="max-height: 80vh;" id="employeeList">
        {{-- Loading skeleton --}}
        <div id="loadingSkeleton">
          <div class="skeleton skeleton-card"></div>
          <div class="skeleton skeleton-card"></div>
          <div class="skeleton skeleton-card"></div>
        </div>

        {{-- Empty state (hidden by default) --}}
        <div id="emptyState" class="empty-state d-none">
          <i class="bx bx-map-pin"></i>
          <h5 class="mt-3">{{ __('No Location Data') }}</h5>
          <p class="text-muted">{{ __('No employees have shared their location yet.') }}</p>
        </div>

        {{-- Employee cards will be populated here --}}
        <div id="employeeCards"></div>
      </div>
    </div>
  </div>

  {{-- Map Column (8-col) --}}
  <div class="col-md-8">
    <div class="row mb-3 align-items-center justify-content-between">
      {{-- Online and Offline Stats with Filters --}}
      <div class="col-auto">
        <div class="btn-group" role="group" aria-label="{{ __('Filter by status') }}">
          <button
            type="button"
            class="btn btn-outline-primary filter-btn active"
            data-filter="all"
            aria-pressed="true"
          >
            {{ __('All') }} <span class="badge bg-label-primary" id="totalCount">0</span>
          </button>
          <button
            type="button"
            class="btn btn-outline-primary filter-btn"
            data-filter="online"
            aria-pressed="false"
          >
            {{ __('Online') }} <span class="badge bg-success" id="onlineCount">0</span>
          </button>
          <button
            type="button"
            class="btn btn-outline-primary filter-btn"
            data-filter="offline"
            aria-pressed="false"
          >
            {{ __('Offline') }} <span class="badge bg-danger" id="offlineCount">0</span>
          </button>
        </div>
      </div>

      {{-- Refresh and Auto-refresh Toggle --}}
      <div class="col-auto">
        <div class="btn-group" role="group">
          <button
            type="button"
            class="btn btn-outline-primary"
            id="refreshBtn"
            aria-label="{{ __('Refresh location data') }}"
          >
            <i class="bx bx-refresh me-2"></i>{{ __('Refresh') }}
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            id="autoRefreshToggle"
            aria-label="{{ __('Toggle auto-refresh') }}"
          >
            <i class="bx bx-time me-2"></i>
            <span id="autoRefreshText">{{ __('Auto: Off') }}</span>
          </button>
        </div>
      </div>
    </div>

    <div class="card shadow-sm position-relative">
      {{-- Map loading overlay --}}
      <div id="mapLoading" class="map-loading d-none">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading...') }}</span>
          </div>
          <p class="mt-3 text-muted">{{ __('Loading map data...') }}</p>
        </div>
      </div>

      <div id="map" style="height:80vh;" role="application" aria-label="{{ __('Employee location map') }}"></div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
  {{-- Page Data Configuration --}}
  <script>
    const pageData = {
      urls: {
        liveLocationAjax: @json(route('liveLocationAjax'))
      },
      labels: {
        online: @json(__('Online')),
        offline: @json(__('Offline')),
        all: @json(__('All')),
        status: @json(__('Status')),
        lastUpdated: @json(__('Last Updated')),
        designation: @json(__('Designation')),
        code: @json(__('Code')),
        focusOnMap: @json(__('Focus on Map')),
        noLocationData: @json(__('No Location Data')),
        noEmployeesFound: @json(__('No employees found')),
        loadingError: @json(__('Failed to load location data')),
        tryAgain: @json(__('Please try again')),
        dataRefreshed: @json(__('Location data refreshed')),
        autoRefreshOn: @json(__('Auto: On')),
        autoRefreshOff: @json(__('Auto: Off')),
        focusedOnMap: @json(__('Focused on map'))
      },
      settings: {
        centerLatitude: {{ $settings->center_latitude ?? 0 }},
        centerLongitude: {{ $settings->center_longitude ?? 0 }},
        mapZoomLevel: {{ $settings->map_zoom_level ?? 10 }},
        autoRefreshInterval: 30000 // 30 seconds
      }
    };

    // Track if Google Maps is ready
    window.googleMapsLoaded = false;

    // Define initMap callback for Google Maps
    window.initMap = function() {
      window.googleMapsLoaded = true;
      console.log('Google Maps API loaded');

      // Trigger custom event that our app JS will listen for
      window.dispatchEvent(new Event('googleMapsReady'));
    };
  </script>

  {{-- Application JavaScript --}}
  @vite(['resources/assets/js/app/dashboard-live-location.js'])

  {{-- Google Maps API --}}
  @if($settings && $settings->map_api_key)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $settings->map_api_key }}&callback=initMap&v=weekly" async defer></script>
  @else
    <script>
      console.error('Google Maps API key is not configured in settings.');
      document.addEventListener('DOMContentLoaded', function() {
        const mapElement = document.getElementById('map');
        if (mapElement) {
          mapElement.innerHTML = '<div class="empty-state"><i class="bx bx-error"></i><h5 class="mt-3">{{ __("Google Maps Not Configured") }}</h5><p class="text-muted">{{ __("Please configure the Google Maps API key in system settings.") }}</p></div>';
        }
        const mapLoading = document.getElementById('mapLoading');
        if (mapLoading) {
          mapLoading.classList.add('d-none');
        }
      });
    </script>
  @endif
@endsection
