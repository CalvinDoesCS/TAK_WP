@php
  $title = __('Timeline');
@endphp

@extends('layouts/layoutMaster')

@section('title', $title)

<!-- Vendor Styles -->
@section('vendor-style')
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"/>
  <style>
    .accordion.map-controls {
      max-width: 300px;
      max-height: 80vh;
      overflow-y: auto;
      border-radius: 8px;
    }

    .accordion-button {
      font-size: 0.9rem;
    }

    .accordion-body .form-check {
      margin-bottom: 5px;
    }

    /* Session color coding */
    .session-1 { color: #0d6efd; } /* Blue */
    .session-2 { color: #198754; } /* Green */
    .session-3 { color: #fd7e14; } /* Orange */
    .session-4 { color: #dc3545; } /* Red */
    .session-5 { color: #6f42c1; } /* Purple */
    .session-6 { color: #20c997; } /* Teal */
    .session-7 { color: #ffc107; } /* Yellow */
    .session-8 { color: #d63384; } /* Pink */

    /* Timeline session badges */
    .timeline-session-badge {
      font-size: 0.75rem;
      padding: 0.15rem 0.4rem;
      border-radius: 0.25rem;
      font-weight: bold;
      display: inline-block;
      margin-left: 0.5rem;
    }

    .timeline-session-badge.session-1 { background-color: #0d6efd; color: white; }
    .timeline-session-badge.session-2 { background-color: #198754; color: white; }
    .timeline-session-badge.session-3 { background-color: #fd7e14; color: white; }
    .timeline-session-badge.session-4 { background-color: #dc3545; color: white; }
    .timeline-session-badge.session-5 { background-color: #6f42c1; color: white; }
    .timeline-session-badge.session-6 { background-color: #20c997; color: white; }
    .timeline-session-badge.session-7 { background-color: #ffc107; color: #000; }
    .timeline-session-badge.session-8 { background-color: #d63384; color: white; }

    /* Highlight selected session timeline items */
    .timeline-item-highlighted {
      border-left: 4px solid;
      transition: all 0.3s ease;
    }

    .timeline-item-highlighted.session-1 { border-left-color: #0d6efd; }
    .timeline-item-highlighted.session-2 { border-left-color: #198754; }
    .timeline-item-highlighted.session-3 { border-left-color: #fd7e14; }
    .timeline-item-highlighted.session-4 { border-left-color: #dc3545; }
    .timeline-item-highlighted.session-5 { border-left-color: #6f42c1; }
    .timeline-item-highlighted.session-6 { border-left-color: #20c997; }
    .timeline-item-highlighted.session-7 { border-left-color: #ffc107; }
    .timeline-item-highlighted.session-8 { border-left-color: #d63384; }

    /* Mobile responsive */
    @media (max-width: 768px) {
      .accordion.map-controls {
        max-width: 250px;
        font-size: 0.85rem;
      }

      .accordion-button {
        font-size: 0.8rem;
        padding: 0.5rem;
      }

      .accordion-body {
        padding: 0.75rem;
      }

      #map {
        height: 500px !important;
      }
    }

    /* Loading skeleton styles */
    .skeleton-card {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s ease-in-out infinite;
      border-radius: 0.375rem;
      height: 80px;
    }

    @keyframes loading {
      0% {
        background-position: 200% 0;
      }
      100% {
        background-position: -200% 0;
      }
    }

    /* Map loading overlay */
    #mapLoadingOverlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
  </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])

  <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endsection

@section('content')

  <!-- Breadcrumbs -->
  <x-breadcrumb
    :title="__('Timeline')"
    :breadcrumbs="[
      ['name' => __('Timeline'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <!-- 🗓️ Filters Section -->
  <div class="row mb-4 g-3">
    <div class="col-md-3">
      <label for="date" class="form-label">Filter by date</label>
      <input type="date" id="date" class="form-control" value="{{ now()->format('Y-m-d') }}">
    </div>
    <div class="col-md-4">
      <label for="emp" class="form-label">Filter by employee</label>
      <select class="form-select select2" id="emp">
        <option selected disabled>Please select an employee</option>
        @foreach($employees as $employee)
          <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3 d-none mb-3" id="attendanceLogFilterDiv">
      <label for="attendanceLogFilter">Filter by Session</label>
      <select id="attendanceLogFilter" class="form-select">
        <option value="">All Sessions</option>
      </select>
    </div>
    <div class="col-auto justify-content-end float-end">
      <button type="button" class=" mt-6 btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#helpModal">
        <i class="bi bi-question-circle"></i>
      </button>
    </div>
  </div>

  <!-- 📊 Stats Section -->
  <div class="row mb-4 g-3" id="statsSection">
    <div class="col-md-12 text-center text-muted">
      <p class="mt-3">Please select an employee and date to view their daily activity.</p>
    </div>
  </div>

  <!-- Loading skeletons (hidden by default) -->
  <div class="row mb-4 g-3 d-none" id="statsSkeletonSection">
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
    <div class="col-md-2 col-sm-6 col-12"><div class="skeleton-card"></div></div>
  </div>

  <div class="d-flex justify-content-end">

  </div>

  <!-- 📊 Main Content Layout -->
  <div class="row g-4">
    <!-- 📋 Left Column: Tabs Section -->
    <div class="col-md-4">
      <div class="card shadow-sm p-3" style="height: 700px; overflow-y: auto;">
        <h5 id="employeeName" class="text-center">Employee Details</h5>
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#timeline">Timeline</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#visits">Visits</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#breaks">Breaks</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#orders">Orders</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tasks">Tasks</a></li>
        </ul>
        <div class="tab-content" style="max-height: 700px; overflow-y: auto;">
          <div class="tab-pane fade show active" id="timeline">
            <p class="text-muted text-center">No timeline data available.</p>
          </div>
          <div class="tab-pane fade" id="visits">
            <p class="text-muted text-center">No visits data available.</p>
          </div>
          <div class="tab-pane fade" id="breaks">
            <p class="text-muted text-center">No breaks data available.</p>
          </div>
          <div class="tab-pane fade" id="orders">
            <p class="text-muted text-center">No orders data available.</p>
          </div>
          <div class="tab-pane fade" id="tasks">
            <p class="text-muted text-center">No tasks data available.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 🗺️ Right Column: Map Section -->
    <div class="col-md-8">
      <div class="card shadow-sm position-relative">
        <!-- Map Controls Accordion - Floating Top-Right -->
        <div
          class="accordion map-controls position-absolute top-0 end-0 m-3 m-md-6 p-2 rounded shadow-lg bg-white"
          id="mapControlsAccordion" style="z-index: 999;">
          <!-- Markers Section -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingMarkers">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                      data-bs-target="#collapseMarkers">
                Markers
              </button>
            </h2>
            <div id="collapseMarkers" class="accordion-collapse collapse show">
              <div class="accordion-body">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleDeviceMarkers" checked>
                  <label class="form-check-label" for="toggleDeviceMarkers">Device Markers</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleVisitsMarkers" checked>
                  <label class="form-check-label" for="toggleVisitsMarkers">Visit Markers</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleActivityMarkers" checked>
                  <label class="form-check-label" for="toggleActivityMarkers">Activity Markers</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleBreaksMarkers" checked>
                  <label class="form-check-label" for="toggleBreaksMarkers">Break Markers</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleTasksMarkers" checked>
                  <label class="form-check-label" for="toggleTasksMarkers">Task Markers</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Heatmap Section -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingHeatmap">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                      data-bs-target="#collapseHeatmap">
                Heatmap
              </button>
            </h2>
            <div id="collapseHeatmap" class="accordion-collapse collapse show">
              <div class="accordion-body">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="toggleDeviceHeatmap" checked>
                  <label class="form-check-label" for="toggleDeviceHeatmap">Device Heatmap</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Polyline Section -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingPolyline">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                      data-bs-target="#collapsePolyline">
                Polyline
              </button>
            </h2>
            <div id="collapsePolyline" class="accordion-collapse collapse show">
              <div class="accordion-body">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="togglePolyline" checked>
                  <label class="form-check-label" for="togglePolyline">Show Polyline</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Map Loading Overlay -->
        <div id="mapLoadingOverlay" class="d-none">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading map data...</p>
          </div>
        </div>

        <!-- Map Container -->
        <div id="map" style="height: 700px;"
             class="text-muted text-center d-flex align-items-center justify-content-center">
          <p>Please select an employee to load the map.</p>
        </div>
      </div>
    </div>
  </div>



  <div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Timeline View - Map Controls Help') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <h6 class="fw-semibold mb-3">{{ __('Map Markers') }}</h6>

          <div class="mb-3">
            <p class="mb-1">
              <span class="badge bg-danger me-2">●</span>
              <strong>{{ __('Device Location Markers') }}:</strong>
              {{ __('Show continuous GPS tracking points with distance and speed information. Red numbered markers indicate the tracking sequence.') }}
            </p>
          </div>

          <div class="mb-3">
            <p class="mb-1">
              <span class="badge bg-success me-2">●</span>
              <strong>{{ __('Visit Markers') }}:</strong>
              {{ __('Display client visit locations with visit details, photos, and timestamps. Green numbered markers show visit sequence.') }}
            </p>
          </div>

          <div class="mb-3">
            <p class="mb-1">
              <span class="badge bg-info me-2">●</span>
              <strong>{{ __('Activity Markers') }}:</strong>
              {{ __('Show employee activity changes (STILL, WALKING, IN_VEHICLE) with GPS and device status. Blue markers indicate activity points.') }}
            </p>
          </div>

          <div class="mb-3">
            <p class="mb-1">
              <span class="badge bg-warning me-2">●</span>
              <strong>{{ __('Break Markers') }}:</strong>
              {{ __('Display break locations with start time, end time, and duration. Yellow markers show where breaks were taken.') }}
            </p>
          </div>

          <div class="mb-3">
            <p class="mb-1">
              <span class="badge bg-purple me-2">●</span>
              <strong>{{ __('Task Markers') }}:</strong>
              {{ __('Show assigned task locations with status and priority information. Purple markers indicate task locations.') }}
            </p>
          </div>

          <hr>

          <h6 class="fw-semibold mb-3">{{ __('Map Visualization') }}</h6>

          <div class="mb-3">
            <p class="mb-1">
              <strong>{{ __('Device Heatmap') }}:</strong>
              {{ __('Displays a heat intensity map showing areas where the employee spent more time. Darker colors indicate longer stay duration.') }}
            </p>
          </div>

          <div class="mb-3">
            <p class="mb-1">
              <strong>{{ __('Polyline') }}:</strong>
              {{ __('Shows the travel path with directional arrows. The line connects device location points in chronological order.') }}
            </p>
          </div>

          <hr>

          <h6 class="fw-semibold mb-3">{{ __('Session Filtering') }}</h6>
          <p>{{ __('When an employee checks in/out multiple times in a day, use the "Filter by Session" dropdown to view data for specific work sessions. Each session shows check-in time, check-out time, and duration.') }}</p>

          <hr>

          <h6 class="fw-semibold mb-3">{{ __('Tabs Information') }}</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><strong>{{ __('Timeline') }}:</strong> {{ __('Chronological list of all activities and movements') }}</li>
            <li class="mb-2"><strong>{{ __('Visits') }}:</strong> {{ __('Client visits with photos and details') }}</li>
            <li class="mb-2"><strong>{{ __('Breaks') }}:</strong> {{ __('Break records with time and duration') }}</li>
            <li class="mb-2"><strong>{{ __('Orders') }}:</strong> {{ __('Product orders placed during the day') }}</li>
            <li class="mb-2"><strong>{{ __('Tasks') }}:</strong> {{ __('Assigned tasks for the selected date') }}</li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  {{-- Page Data Configuration --}}
  <script>
    const pageData = {
      settings: {
        centerLatitude: {{ $settings->center_latitude ?? 0 }},
        centerLongitude: {{ $settings->center_longitude ?? 0 }},
        mapZoomLevel: {{ $settings->map_zoom_level ?? 10 }}
      }
    };

    // Track if Google Maps is ready
    window.googleMapsLoaded = false;
    window.timelineMapReady = false;

    // Define initMap callback for Google Maps
    window.initMap = function() {
      window.googleMapsLoaded = true;
      console.log('Google Maps API loaded');

      // Wait for timeline map initialization function to be available
      if (typeof initializeTimelineMap === 'function') {
        initializeTimelineMap();
      } else {
        // Wait for timeline-view.js to load
        const checkInterval = setInterval(function() {
          if (typeof initializeTimelineMap === 'function') {
            clearInterval(checkInterval);
            initializeTimelineMap();
          }
        }, 50);

        // Timeout after 5 seconds
        setTimeout(function() {
          clearInterval(checkInterval);
          if (!window.timelineMapReady) {
            console.error('Timeline map initialization timed out');
          }
        }, 5000);
      }
    };
  </script>

  {{-- Load Timeline View JavaScript BEFORE Google Maps --}}
  @vite(['resources/assets/js/dashboard/timeline-view.js'])

  {{-- Google Maps API --}}
  @if($settings && $settings->map_api_key)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $settings->map_api_key }}&libraries=geometry,visualization&callback=initMap&v=weekly" async defer></script>
  @else
    <script>
      console.error('Google Maps API key is not configured in settings.');
      $(document).ready(function() {
        $('#map').html('<div class="empty-state"><i class="bi bi-exclamation-triangle"></i><h5 class="mt-3">{{ __("Google Maps Not Configured") }}</h5><p class="text-muted">{{ __("Please configure the Google Maps API key in system settings.") }}</p></div>');
      });
    </script>
  @endif
@endsection
