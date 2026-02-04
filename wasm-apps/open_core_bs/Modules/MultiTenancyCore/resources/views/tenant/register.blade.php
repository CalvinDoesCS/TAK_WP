@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
  $addonService = app(\App\Services\AddonService\IAddonService::class);
  $isReCaptchaEnabled = $addonService->isAddonEnabled('GoogleReCAPTCHA');
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Create Your Account'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/page-auth.scss',
    'Modules/MultiTenancyCore/resources/assets/sass/app.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'
  ])
  @if($isReCaptchaEnabled)
    @include('googlerecaptcha::components.script')
  @endif
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        validate: @json(route('multitenancycore.register.validate')),
        register: @json(route('multitenancycore.register.post')),
        login: @json(route('login'))
      },
      labels: {
        enterPassword: @json(__('Enter a password')),
        weak: @json(__('Weak')),
        fair: @json(__('Fair')),
        good: @json(__('Good')),
        strong: @json(__('Strong')),
        excellent: @json(__('Excellent')),
        emailAvailable: @json(__('Email is available')),
        phoneAvailable: @json(__('Phone number is available')),
        subdomainAvailable: @json(__('Subdomain is available')),
        fillAllFields: @json(__('Please fill all required fields correctly')),
        fillCompanyInfo: @json(__('Please fill all company information and agree to terms')),
        checkAllFields: @json(__('Please check all fields before submitting')),
        registrationFailed: @json(__('Registration failed. Please try again.')),
        firstNameRequired: @json(__('First name is required')),
        lastNameRequired: @json(__('Last name is required')),
        genderRequired: @json(__('Please select a gender')),
        phoneRequired: @json(__('Phone number is required')),
        phoneNotValidated: @json(__('Please wait for phone validation')),
        emailRequired: @json(__('Email address is required')),
        emailInvalid: @json(__('Please enter a valid email address')),
        emailNotValidated: @json(__('Please wait for email validation')),
        passwordRequired: @json(__('Password is required')),
        passwordTooShort: @json(__('Password must be at least 8 characters')),
        confirmPasswordRequired: @json(__('Please confirm your password')),
        passwordMismatch: @json(__('Passwords do not match')),
        companyNameRequired: @json(__('Company name is required')),
        subdomainRequired: @json(__('Subdomain is required')),
        subdomainTooShort: @json(__('Subdomain must be at least 3 characters')),
        subdomainNotValidated: @json(__('Please wait for subdomain validation')),
        termsRequired: @json(__('You must accept the terms and conditions'))
      },
      settings: {
        baseDomain: @json(parse_url(config('app.url'), PHP_URL_HOST)),
        minPasswordLength: 8
      }
    };
  </script>
  @vite(['Modules/MultiTenancyCore/resources/assets/js/tenant/register.js'])
@endsection

@section('content')
  <div class="auth-wrapper register-page">
    <div class="auth-container">
      {{-- Brand Section --}}
      <div class="brand-section">
        <div class="brand-logo-wrapper">
          <div class="brand-icon">
            <img src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png') }}" alt="{{ __('Logo') }}">
          </div>
          <h1 class="brand-name">{{ $settings->app_name ?? config('variables.templateFullName') }}</h1>
        </div>
      </div>

      {{-- Register Card --}}
      <div class="auth-card">
        <h2 class="auth-title">{{ __('Start your journey here') }} 🚀</h2>
        <p class="auth-subtitle">{{ __('Create your account in just 2 simple steps') }}</p>

        {{-- Display any session errors --}}
        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
          </div>
        @endif

        <div class="bs-stepper wizard-registration">
          <div class="bs-stepper-header">
            <div class="step" data-target="#step-personal">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle">1</span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">{{ __('Personal Info') }}</span>
                  <span class="bs-stepper-subtitle">{{ __('Your details') }}</span>
                </span>
              </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-company">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle">2</span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">{{ __('Company') }}</span>
                  <span class="bs-stepper-subtitle">{{ __('Business info') }}</span>
                </span>
              </button>
            </div>
          </div>

          <div class="bs-stepper-content">
            <form id="formAuthentication" action="{{ route('multitenancycore.register.post') }}" method="POST">
              @csrf

              {{-- Step 1: Personal Information --}}
              <div id="step-personal" class="content">
                <div class="wizard-section">
                  <h5 class="wizard-section-title">
                    <i class="bx bx-user"></i>
                    {{ __('Personal Information') }}
                  </h5>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="firstName" class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
                      <input type="text"
                             class="form-control @error('firstName') is-invalid @enderror"
                             id="firstName"
                             name="firstName"
                             placeholder="{{ __('John') }}"
                             value="{{ old('firstName') }}"
                             required>
                      @error('firstName')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="lastName" class="form-label">{{ __('Last Name') }} <span class="text-danger">*</span></label>
                      <input type="text"
                             class="form-control @error('lastName') is-invalid @enderror"
                             id="lastName"
                             name="lastName"
                             placeholder="{{ __('Doe') }}"
                             value="{{ old('lastName') }}"
                             required>
                      @error('lastName')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="gender" class="form-label">{{ __('Gender') }} <span class="text-danger">*</span></label>
                      <select class="form-select @error('gender') is-invalid @enderror"
                              id="gender"
                              name="gender"
                              required>
                        <option value="">{{ __('Select Gender') }}</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                      </select>
                      @error('gender')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="phone" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                      <div class="position-relative">
                        <input type="tel"
                               class="form-control @error('phone') is-invalid @enderror"
                               id="phone"
                               name="phone"
                               placeholder="{{ __('+1234567890') }}"
                               value="{{ old('phone') }}"
                               required>
                        <div class="field-validation-spinner">
                          <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">{{ __('Checking...') }}</span>
                          </div>
                        </div>
                      </div>
                      @error('phone')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                    <div class="position-relative">
                      <input type="email"
                             class="form-control @error('email') is-invalid @enderror"
                             id="email"
                             name="email"
                             placeholder="{{ __('john@example.com') }}"
                             value="{{ old('email') }}"
                             required>
                      <div class="field-validation-spinner">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                          <span class="visually-hidden">{{ __('Checking...') }}</span>
                        </div>
                      </div>
                    </div>
                    @error('email')
                      <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
                      <div class="input-group input-group-merge">
                        <input type="password"
                               id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password"
                               placeholder="{{ __('Enter your password') }}"
                               required>
                        <span class="input-group-text password-toggle cursor-pointer">
                          <i class="bx bx-hide"></i>
                        </span>
                      </div>
                      <div class="password-strength-container mt-2">
                        <div class="password-strength-bar">
                          <div class="password-strength-fill" id="passwordStrengthFill"></div>
                        </div>
                        <small class="password-strength-text text-muted" id="passwordStrengthText">
                          {{ __('Enter a password') }}
                        </small>
                      </div>
                      @error('password')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="password_confirmation">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                      <div class="input-group input-group-merge">
                        <input type="password"
                               id="password_confirmation"
                               class="form-control"
                               name="password_confirmation"
                               placeholder="{{ __('Confirm your password') }}"
                               required>
                        <span class="input-group-text password-toggle cursor-pointer">
                          <i class="bx bx-hide"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="wizard-buttons">
                  <div></div>
                  <button type="button" class="btn btn-primary btn-next">
                    {{ __('Next') }} <i class="bx bx-chevron-right"></i>
                  </button>
                </div>
              </div>

              {{-- Step 2: Company Information --}}
              <div id="step-company" class="content">
                <div class="wizard-section">
                  <h5 class="wizard-section-title">
                    <i class="bx bx-buildings"></i>
                    {{ __('Company Information') }}
                  </h5>

                  <div class="mb-3">
                    <label for="company_name" class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('company_name') is-invalid @enderror"
                           id="company_name"
                           name="company_name"
                           placeholder="{{ __('Acme Inc.') }}"
                           value="{{ old('company_name') }}"
                           required>
                    @error('company_name')
                      <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="mb-3">
                    <label for="subdomain" class="form-label">{{ __('Choose Your Subdomain') }} <span class="text-danger">*</span></label>
                    <div class="position-relative">
                      <div class="input-group subdomain-group">
                        <input type="text"
                               class="form-control @error('subdomain') is-invalid @enderror"
                               id="subdomain"
                               name="subdomain"
                               placeholder="{{ __('yourcompany') }}"
                               value="{{ old('subdomain') }}"
                               pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                               required>
                        <span class="input-group-text">.{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span>
                      </div>
                      <div class="field-validation-spinner" style="right: auto; left: calc(100% - 180px);">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                          <span class="visually-hidden">{{ __('Checking...') }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="form-text">{{ __('Letters, numbers and hyphens only. This cannot be changed later.') }}</div>
                    @error('subdomain')
                      <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="mt-4">
                    <div class="form-check @error('terms') is-invalid @enderror">
                      <input class="form-check-input"
                             type="checkbox"
                             id="terms"
                             name="terms"
                             {{ old('terms') ? 'checked' : '' }}
                             required>
                      <label class="form-check-label" for="terms">
                        {{ __('I agree to the') }}
                        @if(!empty($termsUrl))
                          <a href="{{ $termsUrl }}" target="_blank">{{ __('terms and conditions') }}</a>
                        @else
                          <a href="javascript:void(0);">{{ __('terms and conditions') }}</a>
                        @endif
                        @if(!empty($privacyUrl))
                          {{ __('and') }} <a href="{{ $privacyUrl }}" target="_blank">{{ __('privacy policy') }}</a>
                        @endif
                      </label>
                      @error('terms')
                        <div class="invalid-feedback d-block" data-server>{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  @if($isReCaptchaEnabled)
                    <div class="mt-4">
                      @include('googlerecaptcha::components.recaptcha')
                    </div>
                  @endif
                </div>

                <div class="wizard-buttons">
                  <button type="button" class="btn btn-outline-secondary btn-prev">
                    <i class="bx bx-chevron-left"></i> {{ __('Previous') }}
                  </button>
                  <button type="submit" class="btn btn-primary">
                    {{ __('Create Account') }} <i class="bx bx-check"></i>
                  </button>
                </div>
              </div>

            </form>
          </div>
        </div>

        <div class="login-link">
          <span>{{ __('Already have an account?') }}</span>
          <a href="{{ route('login') }}">{{ __('Sign in instead') }}</a>
        </div>
      </div>

      {{-- Footer --}}
      <div class="auth-footer">
        <div class="auth-footer-text">
          &copy; <script>document.write(new Date().getFullYear());</script>, {{ __('made with') }} ❤️ {{ __('by') }}
          <a href="{{ !empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '' }}" target="_blank" class="auth-footer-link">
            {{ !empty(config('variables.creatorName')) ? config('variables.creatorName') : '' }}
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
