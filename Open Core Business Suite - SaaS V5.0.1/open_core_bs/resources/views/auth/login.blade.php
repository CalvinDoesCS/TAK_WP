@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
  $addonService = app(\App\Services\AddonService\IAddonService::class);
  $isReCaptchaEnabled = $addonService->isAddonEnabled('GoogleReCAPTCHA');
  $isMultiTenancyEnabled = $addonService->isAddonEnabled('MultiTenancyCore');

  // Get current tenant if we're on a tenant subdomain
  $currentTenant = null;
  if ($isMultiTenancyEnabled && app()->has('tenant')) {
      $currentTenant = app('tenant');
  }

  // Determine if we're on tenant or central domain
  $isTenantDomain = $currentTenant !== null;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/page-auth.scss'
  ])
  <style>
    .auth-wrapper {
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      overflow: hidden;
    }

    .auth-wrapper::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
      pointer-events: none;
    }

    .auth-wrapper::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 200px;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='0.1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") no-repeat bottom;
      background-size: cover;
      pointer-events: none;
    }

    .auth-container {
      max-width: 450px;
      width: 100%;
      position: relative;
      z-index: 1;
    }

    .brand-section {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .brand-logo-wrapper {
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.75rem;
    }

    .brand-icon {
      width: 50px;
      height: 50px;
      background: #ffffff;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .brand-icon img {
      width: 32px;
      height: 32px;
      object-fit: contain;
    }

    .brand-name {
      font-size: 1.75rem;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .auth-card {
      background: #ffffff;
      border: none;
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
    }

    .auth-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2f3349;
      margin-bottom: 0.5rem;
    }

    .auth-subtitle {
      color: #8692a6;
      margin-bottom: 2rem;
      font-size: 0.9375rem;
    }

    .form-label {
      font-weight: 600;
      color: #2f3349;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
    }

    .form-control {
      height: 48px;
      border: 1px solid #e7e7e9;
      border-radius: 8px;
      padding: 0 1rem;
      font-size: 0.9375rem;
      transition: all 0.2s;
    }

    .form-control:focus {
      border-color: #696cff;
      box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.08) !important;
      outline: none;
    }

    .form-control::placeholder {
      color: #a8b1bd;
    }

    .input-group-merge .form-control {
      border-top-right-radius: 0 !important;
      border-bottom-right-radius: 0 !important;
    }

    .input-group-merge .input-group-text {
      background: white;
      border: 1px solid #e7e7e9;
      border-left: none;
      border-top-left-radius: 0 !important;
      border-bottom-left-radius: 0 !important;
      border-radius: 0 8px 8px 0;
      cursor: pointer;
    }

    .input-group-merge .form-control:focus {
      box-shadow: none !important;
      z-index: 3;
      border-right: 1px solid #696cff;
    }

    .input-group-merge .form-control:focus + .input-group-text {
      border-color: #696cff;
      z-index: 3;
    }

    .input-group-merge:focus-within {
      box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.08);
      border-radius: 8px;
    }

    .form-check-input:checked {
      background-color: #696cff;
      border-color: #696cff;
    }

    .btn-primary {
      height: 48px;
      background: #696cff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9375rem;
      transition: all 0.2s;
    }

    .btn-primary:hover {
      background: #5f61e6;
    }

    .divider {
      position: relative;
      text-align: center;
      margin: 2rem 0;
    }

    .divider::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      width: 100%;
      height: 1px;
      background: #e7e7e9;
    }

    .divider span {
      position: relative;
      background: white;
      padding: 0 1rem;
      color: #8692a6;
      font-size: 0.875rem;
    }

    .demo-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .demo-btn {
      padding: 0.75rem;
      border: 1px solid #e7e7e9;
      border-radius: 8px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }

    .demo-btn:hover {
      border-color: #696cff;
      background: #f9f9fc;
    }

    .demo-btn i {
      font-size: 1.5rem;
      display: block;
      margin-bottom: 0.25rem;
    }

    .demo-btn.admin i { color: #696cff; }
    .demo-btn.hr i { color: #03c3ec; }
    .demo-btn.employee i { color: #71dd37; }
    .demo-btn.tenant i { color: #ff3e1d; }

    .demo-btn small {
      font-weight: 600;
      color: #2f3349;
      font-size: 0.8125rem;
    }

    .credentials-box {
      background: #f9f9fc;
      border: 1px solid #e7e7e9;
      border-radius: 12px;
      padding: 1.25rem;
    }

    .credentials-title {
      font-size: 0.875rem;
      font-weight: 600;
      color: #2f3349;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .credentials-title i {
      color: #696cff;
    }

    .credential-row {
      padding: 0.875rem 0;
      border-bottom: 1px solid #e7e7e9;
    }

    .credential-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .credential-role {
      font-weight: 600;
      color: #2f3349;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
    }

    .credential-role i {
      font-size: 1.125rem;
    }

    .credential-role.admin i { color: #696cff; }
    .credential-role.hr i { color: #03c3ec; }
    .credential-role.employee i { color: #71dd37; }
    .credential-role.tenant i { color: #ff3e1d; }

    .credential-info {
      font-size: 0.8125rem;
      color: #8692a6;
      display: grid;
      gap: 0.375rem;
    }

    .credential-info code {
      background: white;
      border: 1px solid #e7e7e9;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8125rem;
      color: #2f3349;
    }

    .forgot-link {
      color: #696cff;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .forgot-link:hover {
      color: #5f61e6;
      text-decoration: underline;
    }

    .auth-footer {
      position: relative;
      z-index: 1;
      text-align: center;
      margin-top: 2rem;
      padding: 1.5rem;
      color: #ffffff;
    }

    .auth-footer-content {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      align-items: center;
    }

    .auth-footer-text {
      font-size: 0.875rem;
      opacity: 0.9;
    }

    .auth-footer-links {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .auth-footer-link {
      color: #ffffff;
      text-decoration: none;
      font-size: 0.875rem;
      opacity: 0.9;
      transition: opacity 0.2s;
    }

    .auth-footer-link:hover {
      opacity: 1;
      color: #ffffff;
      text-decoration: underline;
    }

    .auth-footer-divider {
      width: 1px;
      height: 16px;
      background: rgba(255, 255, 255, 0.3);
    }

    @media (max-width: 576px) {
      .auth-card {
        padding: 2rem 1.5rem;
      }

      .demo-grid {
        grid-template-columns: 1fr;
      }

      .auth-footer-links {
        flex-direction: column;
        gap: 0.5rem;
      }

      .auth-footer-divider {
        display: none;
      }
    }
  </style>
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js'
  ])

    @if($isReCaptchaEnabled)
    @include('googlerecaptcha::components.script')
  @endif
  <script>
    function customerLogin() {
      document.getElementById('email').value = 'admin@demo.com';
      document.getElementById('password').value = 'password123';
      document.getElementById('formAuthentication').submit();
    }

    function hrLogin(){
      document.getElementById('email').value = 'hr@demo.com';
      document.getElementById('password').value = 'password123';
      document.getElementById('formAuthentication').submit();
    }

    function employeeLogin(){
      document.getElementById('email').value = 'employee@demo.com';
      document.getElementById('password').value = 'password123';
      document.getElementById('formAuthentication').submit();
    }

    function tenantLogin(){
      document.getElementById('email').value = 'admin@acme.demo.com';
      document.getElementById('password').value = 'password123';
      document.getElementById('formAuthentication').submit();
    }

    @if($isTenantDomain && $currentTenant)
    function tenantAdminLogin(){
      document.getElementById('email').value = '{{ $currentTenant->email }}';
      document.getElementById('password').value = 'password123';
      document.getElementById('formAuthentication').submit();
    }
    @endif
  </script>
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/pages-auth.js'
  ])
@endsection

@section('content')
  <div class="auth-wrapper">
    <div class="auth-container">
      <!-- Brand Section -->
      <div class="brand-section">
        <div class="brand-logo-wrapper">
          <div class="brand-icon">
            @if($isTenantDomain && $currentTenant)
              {{-- Show tenant logo if available --}}
              <img src="{{ $currentTenant->logo ? asset('storage/'.$currentTenant->logo) : asset('assets/img/logo.png')}}" alt="Logo">
            @else
              {{-- Show central application logo --}}
              <img src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png')}}" alt="Logo">
            @endif
          </div>
          <h1 class="brand-name">
            @if($isTenantDomain && $currentTenant)
              {{-- Show tenant name --}}
              {{ $currentTenant->name }}
            @else
              {{-- Show central app name --}}
              {{ $settings->app_name ?? config('variables.templateFullName') }}
            @endif
          </h1>
        </div>
      </div>

      <!-- Login Card -->
      <div class="auth-card">
        <h2 class="auth-title">@lang('Welcome back')</h2>
        <p class="auth-subtitle">@lang('Sign in to your account to continue')</p>

        <form id="formAuthentication" action="{{route('auth.loginPost')}}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="email" class="form-label">@lang('Email')</label>
            <input type="text" class="form-control" id="email" name="email" placeholder="@lang('Enter your email')" autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">@lang('Password')</label>
            <div class="input-group input-group-merge">
              <input type="password" id="password" class="form-control" name="password" placeholder="@lang('Enter your password')" aria-describedby="password"/>
              <span class="input-group-text"><i class="bx bx-hide"></i></span>
            </div>
          </div>

          <div class="mb-4 d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
              <label class="form-check-label" for="rememberMe">
                @lang('Remember Me')
              </label>
            </div>
            <a href="{{route('password.request')}}" class="forgot-link">@lang('Forgot Password?')</a>
          </div>

          @if($isReCaptchaEnabled ?? false)
            <div class="mb-4">
              @include('googlerecaptcha::components.recaptcha')
            </div>
          @endif

          <button type="submit" class="btn btn-primary w-100">@lang('Sign in')</button>
        </form>

        {{-- Show registration link only on central domain when MultiTenancy is enabled --}}
        @if($isMultiTenancyEnabled && !$isTenantDomain)
          <p class="text-center mb-6 mt-3">
            <span>@lang('New on our platform?')</span>
            <a href="{{ route('auth.register') }}">
              <span>@lang('Create an account')</span>
            </a>
          </p>
        @endif

        {{-- Demo credentials section --}}
        @if(env('APP_DEMO') || env('APP_TEST_MODE'))
          @if($isTenantDomain && $currentTenant)
            {{-- Tenant-specific demo credentials --}}
            <div class="divider">
              <span>@lang('Or try demo')</span>
            </div>

            <div class="demo-grid" style="grid-template-columns: 1fr;">
              <button type="button" class="demo-btn admin" onclick="tenantAdminLogin()">
                <i class="bx bxs-user-badge"></i>
                <small>@lang('Admin')</small>
              </button>
            </div>

            <div class="credentials-box">
              <div class="credentials-title">
                <i class="bx bx-info-circle"></i>
                @lang('Demo Credentials for') {{ $currentTenant->name }}
              </div>

              <div class="credential-row">
                <div class="credential-role admin">
                  <i class="bx bxs-user-badge"></i>
                  @lang('Admin')
                </div>
                <div class="credential-info">
                  <div><strong>@lang('Email'):</strong> <code>{{ $currentTenant->email }}</code></div>
                  <div><strong>@lang('Password'):</strong> <code>password123</code></div>
                </div>
              </div>
            </div>
          @else
            {{-- Central application demo credentials (all roles) --}}
            <div class="divider">
              <span>@lang('Or try demo')</span>
            </div>

            <div class="demo-grid">
              <button type="button" class="demo-btn admin" onclick="customerLogin()">
                <i class="bx bxs-user-badge"></i>
                <small>@lang('Admin')</small>
              </button>
              <button type="button" class="demo-btn hr" onclick="hrLogin()">
                <i class="bx bx-user-pin"></i>
                <small>@lang('HR')</small>
              </button>
              <button type="button" class="demo-btn employee" onclick="employeeLogin()">
                <i class="bx bx-user"></i>
                <small>@lang('Employee')</small>
              </button>
            </div>

            @if($isMultiTenancyEnabled)
              <button type="button" class="demo-btn tenant w-100 mb-3" onclick="tenantLogin()" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="bx bx-buildings" style="margin-bottom: 0;"></i>
                <small>@lang('Tenant Login')</small>
              </button>
            @endif

            @if(env('APP_DEMO'))
              <div class="credentials-box">
                <div class="credentials-title">
                  <i class="bx bx-info-circle"></i>
                  @lang('Demo Credentials')
                </div>

                <div class="credential-row">
                  <div class="credential-role admin">
                    <i class="bx bxs-user-badge"></i>
                    @lang('Admin')
                  </div>
                  <div class="credential-info">
                    <div><strong>@lang('Email'):</strong> <code>admin@demo.com</code></div>
                    <div><strong>@lang('Password'):</strong> <code>password123</code></div>
                  </div>
                </div>

                <div class="credential-row">
                  <div class="credential-role hr">
                    <i class="bx bx-user-pin"></i>
                    @lang('HR')
                  </div>
                  <div class="credential-info">
                    <div><strong>@lang('Email'):</strong> <code>hr@demo.com</code></div>
                    <div><strong>@lang('Password'):</strong> <code>password123</code></div>
                  </div>
                </div>

                <div class="credential-row">
                  <div class="credential-role employee">
                    <i class="bx bx-user"></i>
                    @lang('Employee')
                  </div>
                  <div class="credential-info">
                    <div><strong>@lang('Email'):</strong> <code>employee@demo.com</code></div>
                    <div><strong>@lang('Password'):</strong> <code>password123</code></div>
                  </div>
                </div>

                @if($isMultiTenancyEnabled)
                  <div class="credential-row">
                    <div class="credential-role tenant">
                      <i class="bx bx-buildings"></i>
                      @lang('Tenant')
                    </div>
                    <div class="credential-info">
                      <div><strong>@lang('Email'):</strong> <code>admin@acme.demo.com</code></div>
                      <div><strong>@lang('Password'):</strong> <code>password123</code></div>
                    </div>
                  </div>
                @endif
              </div>
            @endif
          @endif
        @endif
      </div>

      <!-- Footer -->
      <div class="auth-footer">
        <div class="auth-footer-content">
          <div class="auth-footer-text">
            &copy; <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="{{ (!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '') }}" target="_blank" class="auth-footer-link">{{ (!empty(config('variables.creatorName')) ? config('variables.creatorName') : '') }}</a>
          </div>
          <div class="auth-footer-text" style="opacity: 0.7; font-size: 0.8125rem;">
            v{{ config('variables.templateVersion') }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
