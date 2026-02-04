@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Forgot Password')

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

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: #696cff;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .back-link:hover {
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

    @media (max-width: 576px) {
      .auth-card {
        padding: 2rem 1.5rem;
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
            <img src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png')}}" alt="Logo">
          </div>
          <h1 class="brand-name">{{$settings->app_name ?? config('variables.templateFullName')}}</h1>
        </div>
      </div>

      <!-- Forgot Password Card -->
      <div class="auth-card">
        <h2 class="auth-title">@lang('Forgot Password?') 🔒</h2>
        <p class="auth-subtitle">@lang('Enter your email and we\'ll send you instructions to reset your password')</p>

        <form id="formAuthentication" action="{{route('password.email')}}" method="POST">
          @csrf

          <div class="mb-4">
            <label for="email" class="form-label">@lang('Email')</label>
            <input type="text" class="form-control" id="email" name="email" placeholder="@lang('Enter your email')" autofocus>
          </div>

          <button class="btn btn-primary w-100 mb-4" type="submit">@lang('Send Reset Link')</button>

          <div class="text-center">
            <a href="{{route('login')}}" class="back-link">
              <i class="bx bx-chevron-left"></i>
              @lang('Back to login')
            </a>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="auth-footer">
        <div class="auth-footer-content">
          <div class="auth-footer-text">
            &copy; <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="{{ (!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '') }}" target="_blank" class="auth-footer-link">{{ (!empty(config('variables.creatorName')) ? config('variables.creatorName') : '') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
