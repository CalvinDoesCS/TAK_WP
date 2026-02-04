@extends('layouts.layoutMaster')

@section('title', 'Live Demo Preview')

@section('content')
  <div class="demo-preview-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="container">
        <div class="row align-items-center hero-content">
          <div class="col-lg-8">
            <div class="d-flex align-items-center mb-3">
              <div class="logo-wrapper me-3">
                <img src="{{asset('assets/img/logo.png')}}" alt="App Logo" class="hero-logo">
              </div>
              <div>
                <h1 class="hero-title mb-1">{{config('variables.templateName')}}</h1>
                <p class="hero-subtitle mb-0">{{config('variables.templateDescription')}}</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="{{config('variables.superAdminPanelDemoLink')}}"
               target="_blank"
               class="btn btn-hero-demo">
              <i class="bx bx-desktop me-2"></i>Launch Live Demo
              <i class="bx bx-right-arrow-alt ms-2"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container content-section">
      <div class="section-header text-center mb-4">
        <h2 class="section-title">Platform Features & Modules</h2>
        <p class="section-subtitle">Comprehensive business management with 30+ integrated modules</p>
      </div>

      <!-- Features Showcase -->
      <div class="features-grid mb-5">
        <div class="row g-3">
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon hr-icon">
                <i class="bx bx-user-circle"></i>
              </div>
              <h4 class="feature-title">HR Management</h4>
              <p class="feature-description">Payroll, Attendance, Leave, Recruitment, Performance, Loans, Assets</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon finance-icon">
                <i class="bx bx-line-chart"></i>
              </div>
              <h4 class="feature-title">Finance & Accounting</h4>
              <p class="feature-description">Accounting Core, Expense Management, Payment Collections, Budgeting</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card coming-soon-feature">
              <div class="feature-icon operations-icon">
                <i class="bx bx-briefcase"></i>
              </div>
              <h4 class="feature-title">
                Project Management
                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; vertical-align: middle;">Coming Soon</span>
              </h4>
              <p class="feature-description">Task Management, Milestones, Gantt Charts, Resource Allocation, Team Collaboration</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon field-icon">
                <i class="bx bx-map-pin"></i>
              </div>
              <h4 class="feature-title">Field Services</h4>
              <p class="feature-description">GPS Tracking, Geofencing, Live Location, Site Management, Route Planning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon collab-icon">
                <i class="bx bx-message-dots"></i>
              </div>
              <h4 class="feature-title">Collaboration</h4>
              <p class="feature-description">Team Chat, Video Calls, Calendar, Notifications, Approvals Workflow</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card coming-soon-feature">
              <div class="feature-icon inventory-icon">
                <i class="bx bx-package"></i>
              </div>
              <h4 class="feature-title">
                Inventory & WMS
                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; vertical-align: middle;">Coming Soon</span>
              </h4>
              <p class="feature-description">Warehouse Management, Product Inventory, Stock Control, Transfers, Barcode Scanning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card coming-soon-feature">
              <div class="feature-icon crm-icon">
                <i class="bx bx-store"></i>
              </div>
              <h4 class="feature-title">
                CRM & Sales
                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; vertical-align: middle;">Coming Soon</span>
              </h4>
              <p class="feature-description">Customer Management, Sales Pipeline, Lead Tracking, Opportunities, Contact Management</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon task-icon">
                <i class="bx bx-task"></i>
              </div>
              <h4 class="feature-title">Task System</h4>
              <p class="feature-description">Task Assignment, Progress Tracking, Deadlines, Priorities, Team Collaboration</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon analytics-icon">
                <i class="bx bx-bar-chart-alt-2"></i>
              </div>
              <h4 class="feature-title">Analytics & Reports</h4>
              <p class="feature-description">Real-time Dashboards, Custom Reports, Data Visualization, Insights</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="feature-card">
              <div class="feature-icon settings-icon">
                <i class="bx bx-cog"></i>
              </div>
              <h4 class="feature-title">Admin & Settings</h4>
              <p class="feature-description">User Management, Roles & Permissions, Audit Logs, Backup & Restore</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Apps Demo Section -->
      <div class="section-header text-center mb-4">
        <h2 class="section-title">Mobile Applications</h2>
        <p class="section-subtitle">Access your business on the go with our mobile apps</p>
      </div>

      <!-- Mobile Apps Grid -->
      <div class="row g-4">
        <!-- Employee App -->
        <div class="col-lg-4">
          <div class="mobile-app-card available">
            <div class="app-card-header">
              <div class="app-icon employee-icon">
                <i class="bx bx-user-circle"></i>
              </div>
              <span class="status-badge available">Available Now</span>
            </div>
            <div class="app-card-body">
              <h4 class="app-card-title">Employee App</h4>
              <p class="app-card-description">
                Complete mobile solution for employees with GPS attendance tracking, leave management, expense claims, document access, payroll information, and real-time communication with team members.
              </p>
              <div class="app-features-list">
                <div class="feature-item">
                  <i class="bx bx-map-pin"></i>
                  <span>Multi-Mode Attendance (GPS, Face, QR, Site)</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-calendar"></i>
                  <span>Leave & Break Management</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-dollar-circle"></i>
                  <span>Payroll & Expense Claims</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-message-dots"></i>
                  <span>Chat & Video Calls</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-file"></i>
                  <span>Documents & Policies Access</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-bell"></i>
                  <span>Real-time Notifications</span>
                </div>
              </div>
            </div>
            <div class="app-card-footer">
              <a href="{{config('variables.appDemoLink')}}"
                 target="_blank"
                 class="btn btn-success btn-block">
                <i class="bx bxl-android me-2"></i>Download Android Demo
              </a>
              <div class="platform-badges mt-3">
                <span class="platform-badge"><i class="bx bxl-android"></i> Android</span>
                <span class="platform-badge"><i class="bx bxl-apple"></i> iOS</span>
              </div>
              <p class="text-muted text-center mt-2 mb-0" style="font-size: 0.8rem;">
                <i class="bx bx-info-circle"></i> Demo APK available for Android only
              </p>
            </div>
          </div>
        </div>

        <!-- Field Manager App -->
        <div class="col-lg-4">
          <div class="mobile-app-card coming-soon">
            <div class="app-card-header">
              <div class="app-icon manager-icon">
                <i class="bx bx-briefcase"></i>
              </div>
              <span class="status-badge coming-soon">Coming Soon</span>
            </div>
            <div class="app-card-body">
              <h4 class="app-card-title">Field Manager App</h4>
              <p class="app-card-description">
                Powerful field operations management with live team tracking, task assignments, geofencing, site monitoring, route optimization, and comprehensive field workforce analytics dashboard.
              </p>
              <div class="app-features-list">
                <div class="feature-item">
                  <i class="bx bx-target-lock"></i>
                  <span>Field Task Assignment & Tracking</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-current-location"></i>
                  <span>Real-time Team Location Monitoring</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-map"></i>
                  <span>Geofencing & Site Management</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-user-check"></i>
                  <span>Attendance & Activity Verification</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-line-chart"></i>
                  <span>Performance Analytics Dashboard</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-map-alt"></i>
                  <span>Route Optimization & Timeline View</span>
                </div>
              </div>
            </div>
            <div class="app-card-footer">
              <button class="btn btn-outline-secondary btn-block" disabled>
                <i class="bx bx-time me-2"></i>Coming Soon
              </button>
              <div class="platform-badges mt-3">
                <span class="platform-badge coming-soon-badge"><i class="bx bxl-android"></i> Android</span>
                <span class="platform-badge coming-soon-badge"><i class="bx bxl-apple"></i> iOS</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat App -->
        <div class="col-lg-4">
          <div class="mobile-app-card coming-soon">
            <div class="app-card-header">
              <div class="app-icon chat-icon">
                <i class="bx bx-message-rounded-dots"></i>
              </div>
              <span class="status-badge coming-soon">Coming Soon</span>
            </div>
            <div class="app-card-body">
              <h4 class="app-card-title">Chat App</h4>
              <p class="app-card-description">
                Standalone enterprise communication hub with secure instant messaging, HD video/audio calls, media sharing, group conversations, and seamless integration with all business suite features.
              </p>
              <div class="app-features-list">
                <div class="feature-item">
                  <i class="bx bx-message-dots"></i>
                  <span>Instant Messaging & Reactions</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-video"></i>
                  <span>HD Video & Audio Calls</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-file"></i>
                  <span>Files, Images & Media Sharing</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-group"></i>
                  <span>Group Chats & Channels</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-shield-alt"></i>
                  <span>End-to-End Encryption</span>
                </div>
                <div class="feature-item">
                  <i class="bx bx-bell"></i>
                  <span>Smart Push Notifications</span>
                </div>
              </div>
            </div>
            <div class="app-card-footer">
              <button class="btn btn-outline-secondary btn-block" disabled>
                <i class="bx bx-time me-2"></i>Coming Soon
              </button>
              <div class="platform-badges mt-3">
                <span class="platform-badge coming-soon-badge"><i class="bx bxl-android"></i> Android</span>
                <span class="platform-badge coming-soon-badge"><i class="bx bxl-apple"></i> iOS</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Call to Action Section -->
      <div class="cta-section mt-5">
        <div class="cta-card">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <h3 class="cta-title">Ready to Transform Your Business Operations?</h3>
              <p class="cta-description">Experience the power of 30+ integrated modules managing HR, Finance, Field Operations, and more. Start your journey with Open Core Business Suite today.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="{{config('variables.superAdminPanelDemoLink')}}" target="_blank" class="btn btn-light btn-lg">
                <i class="bx bx-rocket me-2"></i>Explore Live Demo
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* ============================================
       GLOBAL STYLES
    ============================================ */
    .demo-preview-page {
      background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
      min-height: 100vh;
      padding-bottom: 4rem;
    }

    /* ============================================
       HERO SECTION
    ============================================ */
    .hero-section {
      background: linear-gradient(135deg, #696cff 0%, #5a5fc7 100%);
      padding: 1.5rem 0;
      margin-bottom: 3rem;
      position: relative;
      overflow: hidden;
    }

    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
      background-size: 50px 50px;
      opacity: 0.3;
    }

    .hero-content {
      position: relative;
      z-index: 1;
    }

    .logo-wrapper {
      background: white;
      padding: 0.75rem;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .hero-logo {
      max-width: 50px;
      height: auto;
      display: block;
    }

    .hero-title {
      color: white;
      font-size: 2rem;
      font-weight: 700;
      margin: 0;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .hero-subtitle {
      color: rgba(255, 255, 255, 0.9);
      font-size: 1rem;
      font-weight: 300;
      margin: 0;
    }

    .btn-hero-demo {
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 12px;
      background: white;
      color: #696cff;
      border: none;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
      display: inline-flex;
      align-items: center;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn-hero-demo:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
      color: #696cff;
    }

    /* ============================================
       CONTENT SECTION
    ============================================ */
    .content-section {
      max-width: 1400px;
    }

    .section-header {
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 0.75rem;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #6c757d;
      max-width: 600px;
      margin: 0 auto;
    }

    /* ============================================
       FEATURES GRID
    ============================================ */
    .features-grid {
      margin-top: 2rem;
    }

    .feature-card {
      background: white;
      border-radius: 16px;
      padding: 1.75rem;
      height: 100%;
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
      border: 1px solid #e9ecef;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
      border-color: #696cff;
    }

    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      color: white;
      margin-bottom: 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .hr-icon {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .finance-icon {
      background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    }

    .operations-icon {
      background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
    }

    .field-icon {
      background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    }

    .collab-icon {
      background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
    }

    .inventory-icon {
      background: linear-gradient(135deg, #198754 0%, #0dcaf0 100%);
    }

    .analytics-icon {
      background: linear-gradient(135deg, #696cff 0%, #5a5fc7 100%);
    }

    .settings-icon {
      background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }

    .crm-icon {
      background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    }

    .task-icon {
      background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
    }

    .feature-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
    }

    .feature-description {
      font-size: 0.9rem;
      color: #6c757d;
      line-height: 1.6;
      margin-bottom: 0;
    }

    .coming-soon-feature {
      position: relative;
    }

    .coming-soon-feature > * {
      position: relative;
      z-index: 1;
    }

    .coming-soon-feature::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.4);
      backdrop-filter: blur(0.5px);
      pointer-events: none;
      border-radius: 16px;
      z-index: 0;
    }

    /* ============================================
       MOBILE APP CARDS
    ============================================ */
    .mobile-app-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    .mobile-app-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 35px rgba(0, 0, 0, 0.12);
    }

    .mobile-app-card.coming-soon {
      opacity: 0.95;
    }

    .mobile-app-card.coming-soon::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.5);
      backdrop-filter: blur(1px);
      pointer-events: none;
      z-index: 0;
    }

    .app-card-header {
      padding: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      position: relative;
      z-index: 1;
    }

    .app-icon {
      width: 70px;
      height: 70px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }

    .employee-icon {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .manager-icon {
      background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
    }

    .chat-icon {
      background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
    }

    .status-badge {
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-badge.available {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
    }

    .status-badge.coming-soon {
      background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
      color: white;
    }

    .app-card-body {
      padding: 0 2rem 2rem;
      flex: 1;
      position: relative;
      z-index: 1;
    }

    .app-card-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 1rem;
    }

    .app-card-description {
      color: #6c757d;
      line-height: 1.7;
      margin-bottom: 1.5rem;
      min-height: 80px;
    }

    .app-features-list {
      display: grid;
      gap: 0.75rem;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      background: #f8f9fa;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .feature-item:hover {
      background: #e9ecef;
      transform: translateX(5px);
    }

    .feature-item i {
      font-size: 1.25rem;
      color: #696cff;
    }

    .feature-item span {
      font-size: 0.9rem;
      color: #495057;
      font-weight: 500;
    }

    .app-card-footer {
      padding: 2rem;
      border-top: 1px solid #e9ecef;
      position: relative;
      z-index: 1;
    }

    .btn-block {
      width: 100%;
      padding: 1rem;
      font-weight: 600;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .btn-success.btn-block:hover {
      box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
    }

    .platform-badges {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .platform-badge {
      padding: 0.4rem 0.75rem;
      background: #e9ecef;
      border-radius: 50px;
      font-size: 0.8rem;
      color: #495057;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }

    .coming-soon-badge {
      background: #f8f9fa;
      color: #adb5bd;
    }

    /* ============================================
       CTA SECTION
    ============================================ */
    .cta-section {
      margin-top: 5rem;
    }

    .cta-card {
      background: linear-gradient(135deg, #696cff 0%, #5a5fc7 100%);
      border-radius: 24px;
      padding: 3rem;
      box-shadow: 0 10px 40px rgba(105, 108, 255, 0.3);
      position: relative;
      overflow: hidden;
    }

    .cta-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
      background-size: 50px 50px;
      opacity: 0.3;
    }

    .cta-card > .row {
      position: relative;
      z-index: 1;
    }

    .cta-title {
      color: white;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .cta-description {
      color: rgba(255, 255, 255, 0.9);
      font-size: 1.1rem;
      margin-bottom: 0;
    }

    .cta-card .btn-light {
      padding: 1rem 2rem;
      font-weight: 600;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .cta-card .btn-light:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* ============================================
       RESPONSIVE DESIGN
    ============================================ */
    @media (max-width: 991px) {
      .hero-section {
        padding: 1.25rem 0;
      }

      .hero-title {
        font-size: 1.75rem;
      }

      .hero-subtitle {
        font-size: 0.95rem;
      }

      .btn-hero-demo {
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
      }

      .section-title {
        font-size: 1.75rem;
      }

      .feature-card {
        padding: 1.5rem;
      }

      .cta-title {
        font-size: 1.75rem;
        margin-bottom: 1rem;
      }

      .cta-card .btn-light {
        margin-top: 1rem;
      }
    }

    @media (max-width: 767px) {
      .hero-section {
        padding: 1rem 0;
      }

      .hero-content .col-lg-8,
      .hero-content .col-lg-4 {
        text-align: center !important;
      }

      .hero-content .d-flex {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
      }

      .logo-wrapper {
        margin-right: 0 !important;
        margin-bottom: 0.75rem;
      }

      .hero-logo {
        max-width: 45px;
      }

      .hero-title {
        font-size: 1.5rem;
      }

      .hero-subtitle {
        font-size: 0.875rem;
      }

      .btn-hero-demo {
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        margin-top: 1rem;
      }

      .section-header {
        margin-bottom: 1.5rem;
      }

      .section-title {
        font-size: 1.5rem;
      }

      .section-subtitle {
        font-size: 0.95rem;
      }

      .feature-card {
        padding: 1.25rem;
      }

      .feature-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
      }

      .feature-title {
        font-size: 1rem;
      }

      .mobile-app-card {
        margin-bottom: 1.5rem;
      }

      .cta-card {
        padding: 2rem;
      }

      .cta-title {
        font-size: 1.5rem;
      }
    }

    /* ============================================
       UTILITY CLASSES
    ============================================ */
    .bg-primary-gradient {
      background: linear-gradient(135deg, #696cff 0%, #5a5fc7 100%);
    }

    .text-gradient {
      background: linear-gradient(135deg, #696cff 0%, #5a5fc7 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
@endsection
