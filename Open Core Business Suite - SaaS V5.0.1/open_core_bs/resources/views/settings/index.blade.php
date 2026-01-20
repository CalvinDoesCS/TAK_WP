@php
  use App\Services\AddonService\IAddonService;
  $addonService = app(IAddonService::class);
@endphp
@extends('layouts/layoutMaster')

@section('title', __('Settings'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('Dashboard'), 'url' => route('superAdmin.dashboard')],
      ['name' => __('Settings'), 'url' => '']
    ];
  @endphp
  <x-breadcrumb :title="__('Settings')" :breadcrumbs="$breadcrumbs" />

  <div class="row">
    <!-- Settings Navigation Sidebar -->
    <div class="col-md-3">
      <div class="card mb-4 sticky-top" style="top: 80px;">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="bx bx-cog me-2"></i>{{ __('Settings') }}
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush" id="settingsNav">
            <a href="#" class="list-group-item list-group-item-action active border-0 px-4 py-3" data-section="general">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-primary">
                      <i class="bx bx-cog"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('General') }}</h6>
                  <small class="text-muted">{{ __('Basic settings') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="branding">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-info">
                      <i class="bx bx-palette"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Branding') }}</h6>
                  <small class="text-muted">{{ __('Logo & favicon') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="company">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-success">
                      <i class="bx bx-buildings"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Company') }}</h6>
                  <small class="text-muted">{{ __('Organization info') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="employee">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-warning">
                      <i class="bx bx-user"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Employee') }}</h6>
                  <small class="text-muted">{{ __('Default settings') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="maps">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-danger">
                      <i class="bx bx-map"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Maps') }}</h6>
                  <small class="text-muted">{{ __('Location settings') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="code-prefix">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-secondary">
                      <i class="bx bx-code-block"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Code Prefix') }}</h6>
                  <small class="text-muted">{{ __('Auto-generated codes') }}</small>
                </div>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="mail">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-primary">
                      <i class="bx bx-envelope"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-0">{{ __('Mail') }}</h6>
                  <small class="text-muted">{{ __('SMTP configuration') }}</small>
                </div>
              </div>
            </a>
            @if($addonService->isAddonEnabled(ModuleConstants::GOOGLE_RECAPTCHA))
              <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="recaptcha">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="avatar avatar-sm">
                      <span class="avatar-initial rounded bg-label-info">
                        <i class="bx bx-shield-alt-2"></i>
                      </span>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">{{ __('reCAPTCHA') }}</h6>
                    <small class="text-muted">{{ __('Security settings') }}</small>
                  </div>
                </div>
              </a>
            @endif
            @if($addonService->isAddonEnabled('AgoraCall'))
              <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3" data-section="agora">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="avatar avatar-sm">
                      <span class="avatar-initial rounded bg-label-success">
                        <i class="bx bx-video"></i>
                      </span>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">{{ __('Agora Call') }}</h6>
                    <small class="text-muted">{{ __('Video/audio calls') }}</small>
                  </div>
                </div>
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Settings Content Area -->
    <div class="col-md-9">
      <!-- General Settings -->
      <div class="settings-section" id="section-general">
        <form id="form-general">
          @csrf
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <i class="bx bx-cog me-2"></i>
              <h5 class="mb-0">{{ __('General Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="appName" class="form-label">{{ __('App Name') }}</label>
                  <input type="text" class="form-control" id="appName" name="appName" value="{{ $settings->app_name ?? '' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="country" class="form-label">{{ __('Country') }}</label>
                  <input type="text" class="form-control" id="country" name="country" value="{{ $settings->country ?? '' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="phoneCountryCode" class="form-label">{{ __('Phone Country Code') }}</label>
                  <input type="text" class="form-control" id="phoneCountryCode" name="phoneCountryCode" value="{{ $settings->phone_country_code ?? '' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="currency" class="form-label">{{ __('Currency') }}</label>
                  <input type="text" class="form-control" id="currency" name="currency" value="{{ $settings->currency ?? '' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="currencySymbol" class="form-label">{{ __('Currency Symbol') }}</label>
                  <input type="text" class="form-control" id="currencySymbol" name="currencySymbol" value="{{ $settings->currency_symbol ?? '' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="distanceUnit" class="form-label">{{ __('Distance Unit') }}</label>
                  <select id="distanceUnit" class="form-select" name="distanceUnit" required>
                    <option value="km" {{ ($settings->distance_unit ?? 'km') == 'km' ? 'selected' : '' }}>{{ __('Kilometers') }}</option>
                    <option value="miles" {{ ($settings->distance_unit ?? 'km') == 'miles' ? 'selected' : '' }}>{{ __('Miles') }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('Enable Helper Text') }}</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="isHelperTextEnabled" name="isHelperTextEnabled" {{ $settings->is_helper_text_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="isHelperTextEnabled">
                      {{ $settings->is_helper_text_enabled ? __('Enabled') : __('Disabled') }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Branding Settings -->
      <div class="settings-section d-none" id="section-branding">
        <form id="form-branding" enctype="multipart/form-data">
          @csrf
          <div class="card mb-4">
            <div class="card-header">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="mb-0"><i class="bx bx-palette me-2"></i>{{ __('Branding Settings') }}</h5>
                  <small class="text-muted">{{ __('Customize your application appearance') }}</small>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <!-- App Logo Section -->
                <div class="col-12">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Application Logo') }}</label>
                    <p class="text-muted small mb-3">{{ __('This logo will be displayed in the application header and login pages.') }}</p>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                      <div class="card shadow-none border">
                        <div class="card-body p-3">
                          <div class="text-center">
                            <small class="text-muted d-block mb-2">{{ __('Current Logo') }}</small>
                            <div class="bg-light rounded p-3" style="min-height: 160px; display: flex; align-items: center; justify-content: center;">
                              <img id="appLogoPreview"
                                   src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png') }}"
                                   alt="{{ __('App Logo') }}"
                                   class="img-fluid"
                                   style="max-height: 140px; max-width: 100%; object-fit: contain;">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                      <div class="upload-zone border border-2 border-dashed rounded p-4 text-center" id="logoUploadZone" style="cursor: pointer; transition: all 0.3s;">
                        <input type="file"
                               class="d-none"
                               id="appLogo"
                               name="app_logo"
                               accept="image/png,image/jpeg,image/jpg">
                        <div class="upload-content">
                          <div class="mb-3">
                            <i class="bx bx-cloud-upload display-4 text-primary"></i>
                          </div>
                          <h5 class="mb-2">{{ __('Drop your logo here or click to browse') }}</h5>
                          <p class="text-muted mb-3">{{ __('PNG, JPG or JPEG format') }}</p>
                          <div class="mb-3">
                            <span class="badge bg-label-primary me-2">
                              <i class="bx bx-info-circle me-1"></i>{{ __('Max size: 2MB') }}
                            </span>
                            <span class="badge bg-label-success">
                              <i class="bx bx-check-circle me-1"></i>{{ __('Transparent background recommended') }}
                            </span>
                          </div>
                          <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('appLogo').click()">
                              <i class="bx bx-upload me-1"></i> {{ __('Choose File') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resetLogoBtn">
                              <i class="bx bx-reset me-1"></i> {{ __('Reset') }}
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="mt-2 d-none" id="logoFileName">
                        <small class="text-success">
                          <i class="bx bx-check-circle me-1"></i>
                          <span id="logoFileNameText"></span>
                        </small>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12"><hr class="my-4"></div>

                <!-- Favicon Section -->
                <div class="col-12">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Favicon') }}</label>
                    <p class="text-muted small mb-3">{{ __('This icon will appear in browser tabs and bookmarks.') }}</p>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                      <div class="card shadow-none border">
                        <div class="card-body p-3">
                          <div class="text-center">
                            <small class="text-muted d-block mb-2">{{ __('Current Favicon') }}</small>
                            <div class="bg-light rounded p-3" style="min-height: 160px; display: flex; align-items: center; justify-content: center;">
                              <img id="appFaviconPreview"
                                   src="{{ $settings->app_favicon ? asset('assets/img/favicon/'.$settings->app_favicon) : asset('assets/img/favicon/favicon.ico') }}"
                                   alt="{{ __('Favicon') }}"
                                   class="img-fluid"
                                   style="max-height: 64px; max-width: 64px; object-fit: contain; image-rendering: -webkit-optimize-contrast;">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                      <div class="upload-zone border border-2 border-dashed rounded p-4 text-center" id="faviconUploadZone" style="cursor: pointer; transition: all 0.3s;">
                        <input type="file"
                               class="d-none"
                               id="appFavicon"
                               name="app_favicon"
                               accept="image/x-icon,image/png,image/jpeg,image/jpg">
                        <div class="upload-content">
                          <div class="mb-3">
                            <i class="bx bx-cloud-upload display-4 text-info"></i>
                          </div>
                          <h5 class="mb-2">{{ __('Drop your favicon here or click to browse') }}</h5>
                          <p class="text-muted mb-3">{{ __('ICO, PNG, JPG or JPEG format') }}</p>
                          <div class="mb-3">
                            <span class="badge bg-label-info me-2">
                              <i class="bx bx-info-circle me-1"></i>{{ __('Max size: 512KB') }}
                            </span>
                            <span class="badge bg-label-warning">
                              <i class="bx bx-info-circle me-1"></i>{{ __('32x32 or 64x64 pixels') }}
                            </span>
                          </div>
                          <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-info" onclick="document.getElementById('appFavicon').click()">
                              <i class="bx bx-upload me-1"></i> {{ __('Choose File') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resetFaviconBtn">
                              <i class="bx bx-reset me-1"></i> {{ __('Reset') }}
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="mt-2 d-none" id="faviconFileName">
                        <small class="text-success">
                          <i class="bx bx-check-circle me-1"></i>
                          <span id="faviconFileNameText"></span>
                        </small>
                      </div>
                    </div>
                  </div>
                </div>

                @if($settings->is_helper_text_enabled)
                  <div class="col-12">
                    <div class="alert alert-primary d-flex" role="alert">
                      <span class="alert-icon rounded-circle">
                        <i class="bx bx-info-circle"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="alert-heading mb-2">{{ __('Branding Tips') }}</h5>
                        <ul class="mb-0 ps-3">
                          <li>{{ __('Use high-quality images for better display on all devices') }}</li>
                          <li>{{ __('Logos with transparent backgrounds work best') }}</li>
                          <li>{{ __('Keep favicon simple and recognizable at small sizes') }}</li>
                          <li>{{ __('Changes will take effect immediately after saving') }}</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
            <div class="card-footer">
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">{{ __('Last updated: Just now') }}</small>
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Company Settings -->
      <div class="settings-section d-none" id="section-company">
        <form id="form-company" enctype="multipart/form-data">
          @csrf
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="bx bx-buildings me-2"></i>{{ __('Company Settings') }}</h5>
              <small class="text-muted">{{ __('Manage your organization information') }}</small>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <!-- Company Logo -->
                <div class="col-12">
                  <label class="form-label fw-semibold">{{ __('Company Logo') }}</label>
                  <div class="card shadow-none border">
                    <div class="card-body p-4">
                      <div class="d-flex align-items-center gap-4">
                        <div class="flex-shrink-0">
                          <div class="border-2 border-dashed rounded p-3 text-center" style="width: 150px; height: 150px; overflow: hidden; cursor: pointer; background: #f8f9fa; display: flex; align-items: center; justify-content: center;" onclick="document.getElementById('companyLogo').click();">
                            <img id="companyLogoPreview"
                                 src="{{ $settings->company_logo ? asset('storage/images/'.$settings->company_logo) : 'https://placehold.co/150x150/f8f9fa/6c757d?text=Logo' }}"
                                 alt="{{ __('Company Logo') }}"
                                 class="img-fluid"
                                 style="max-width: 140px; max-height: 140px; object-fit: contain;">
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <input type="file" class="form-control d-none" id="companyLogo" name="company_logo" accept="image/*">
                          <h6 class="mb-2">{{ __('Upload Company Logo') }}</h6>
                          <p class="text-muted small mb-3">{{ __('This logo will appear on documents and reports') }}</p>
                          <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('companyLogo').click()">
                              <i class="bx bx-upload me-1"></i> {{ __('Upload Logo') }}
                            </button>
                            @if($settings->company_logo)
                              <button type="button" class="btn btn-sm btn-label-danger" id="removeLogoButton">
                                <i class="bx bx-trash me-1"></i> {{ __('Remove') }}
                              </button>
                            @endif
                          </div>
                          <small class="text-muted d-block mt-2">
                            <i class="bx bx-info-circle me-1"></i>{{ __('JPG, PNG. Max: 2MB') }}
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Basic Information -->
                <div class="col-12">
                  <h6 class="mb-3">{{ __('Basic Information') }}</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="companyName" class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-buildings"></i></span>
                        <input type="text" class="form-control" id="companyName" name="company_name" value="{{ $settings->company_name ?? '' }}" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="companyPhone" class="form-label">{{ __('Company Phone') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                        <input type="text" class="form-control" id="companyPhone" name="company_phone" value="{{ $settings->company_phone ?? '' }}">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="companyEmail" class="form-label">{{ __('Company Email') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                        <input type="email" class="form-control" id="companyEmail" name="company_email" value="{{ $settings->company_email ?? '' }}">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="companyWebsite" class="form-label">{{ __('Company Website') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-globe"></i></span>
                        <input type="text" class="form-control" id="companyWebsite" name="company_website" value="{{ $settings->company_website ?? '' }}" placeholder="https://">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Address Information -->
                <div class="col-12">
                  <h6 class="mb-3">{{ __('Address Information') }}</h6>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="companyAddress" class="form-label">{{ __('Company Address') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-map"></i></span>
                        <textarea class="form-control" id="companyAddress" name="company_address" rows="2">{{ $settings->company_address ?? '' }}</textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="companyCity" class="form-label">{{ __('City') }}</label>
                      <input type="text" class="form-control" id="companyCity" name="company_city" value="{{ $settings->company_city ?? '' }}">
                    </div>
                    <div class="col-md-6">
                      <label for="companyState" class="form-label">{{ __('State/Province') }}</label>
                      <input type="text" class="form-control" id="companyState" name="company_state" value="{{ $settings->company_state ?? '' }}">
                    </div>
                    <div class="col-md-6">
                      <label for="companyCountry" class="form-label">{{ __('Country') }}</label>
                      <input type="text" class="form-control" id="companyCountry" name="company_country" value="{{ $settings->company_country ?? '' }}">
                    </div>
                    <div class="col-md-6">
                      <label for="companyZipcode" class="form-label">{{ __('Zip/Postal Code') }}</label>
                      <input type="text" class="form-control" id="companyZipcode" name="company_zipcode" value="{{ $settings->company_zipcode ?? '' }}">
                    </div>
                  </div>
                </div>

                <!-- Legal Information -->
                <div class="col-12">
                  <h6 class="mb-3">{{ __('Legal Information') }}</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="companyTaxId" class="form-label">{{ __('Tax ID / VAT Number') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-id-card"></i></span>
                        <input type="text" class="form-control" id="companyTaxId" name="company_tax_id" value="{{ $settings->company_tax_id ?? '' }}">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="companyRegNo" class="form-label">{{ __('Registration Number') }}</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-file"></i></span>
                        <input type="text" class="form-control" id="companyRegNo" name="company_reg_no" value="{{ $settings->company_reg_no ?? '' }}">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Employee Settings -->
      <div class="settings-section d-none" id="section-employee">
        <form id="form-employee">
          @csrf
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <i class="bx bx-user me-2"></i>
              <h5 class="mb-0">{{ __('Employee Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="defaultPassword" class="form-label">{{ __('Default Password') }}</label>
                  <input type="password" class="form-control" id="defaultPassword" name="defaultPassword" value="{{ $settings->default_password }}" required minlength="8">
                  <small class="form-text text-muted">{{ __('Default password for new employees') }}</small>
                </div>
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Maps Settings -->
      <div class="settings-section d-none" id="section-maps">
        <form id="form-maps">
          @csrf
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <i class="bx bx-map me-2"></i>
              <h5 class="mb-0">{{ __('Maps Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="mapProvider" class="form-label">{{ __('Map Provider') }}</label>
                  <select id="mapProvider" class="form-select" name="mapProvider" required>
                    <option value="google" {{ ($settings->map_provider ?? 'google') == 'google' ? 'selected' : '' }}>Google Maps</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="mapZoomLevel" class="form-label">{{ __('Map Zoom Level') }}</label>
                  <input type="number" class="form-control" id="mapZoomLevel" name="mapZoomLevel" value="{{ $settings->map_zoom_level ?? 3 }}" required min="1" max="20">
                </div>
                <div class="col-md-6">
                  <label for="centerLatitude" class="form-label">{{ __('Center Latitude') }}</label>
                  <input type="text" class="form-control" id="centerLatitude" name="centerLatitude" value="{{ $settings->center_latitude ?? '18.418983770139405' }}" required>
                </div>
                <div class="col-md-6">
                  <label for="centerLongitude" class="form-label">{{ __('Center Longitude') }}</label>
                  <input type="text" class="form-control" id="centerLongitude" name="centerLongitude" value="{{ $settings->center_longitude ?? '49.67194361588897' }}" required>
                </div>
                <div class="col-12">
                  <label for="mapApiKey" class="form-label">{{ __('Map API Key') }}</label>
                  <input type="text" class="form-control" id="mapApiKey" name="mapApiKey" value="{{ $settings->map_api_key ?? '' }}" required>
                  <small class="form-text text-muted">{{ __('Your Google Maps API key for map features') }}</small>
                </div>
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Code Prefix Settings -->
      <div class="settings-section d-none" id="section-code-prefix">
        <form id="form-code-prefix">
          @csrf
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <i class="bx bx-code-block me-2"></i>
              <h5 class="mb-0">{{ __('Code Prefix & Suffix') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="employeeCodePrefix" class="form-label">{{ __('Employee Code Prefix') }}</label>
                  <input type="text" class="form-control" id="employeeCodePrefix" name="employee_code_prefix" value="{{ $settings->employee_code_prefix ?? 'EMP' }}">
                  <small class="form-text text-muted">{{ __('Prefix for employee codes (e.g., EMP-001)') }}</small>
                </div>
                @if(Nwidart\Modules\Facades\Module::has('ProductOrder'))
                  <div class="col-md-6">
                    <label for="orderPrefix" class="form-label">{{ __('Order Prefix') }}</label>
                    <input type="text" class="form-control" id="orderPrefix" name="order_prefix" value="{{ $settings->order_prefix ?? 'FM_ORD' }}">
                    <small class="form-text text-muted">{{ __('Prefix for order numbers') }}</small>
                  </div>
                @endif
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Mail Settings -->
      <div class="settings-section d-none" id="section-mail">
        <form id="form-mail">
          @csrf
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <i class="bx bx-envelope me-2"></i>
              <h5 class="mb-0">{{ __('Mail Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12">
                  <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    {{ __('Configure your SMTP settings to enable email notifications, password resets, and other email features.') }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="mail_driver" class="form-label">{{ __('Mail Driver') }}</label>
                  <select class="form-select" id="mail_driver" name="mail_driver">
                    <option value="smtp" {{ ($settings->mail_driver ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                  </select>
                  <small class="form-text text-muted">{{ __('Currently only SMTP is supported') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_host" class="form-label">{{ __('SMTP Host') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="mail_host" name="mail_host" value="{{ $settings->mail_host ?? '' }}" placeholder="smtp.gmail.com">
                  <small class="form-text text-muted">{{ __('Your SMTP server hostname') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_port" class="form-label">{{ __('SMTP Port') }} <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="mail_port" name="mail_port" value="{{ $settings->mail_port ?? 587 }}" placeholder="587">
                  <small class="form-text text-muted">{{ __('Common ports: 587 (TLS), 465 (SSL), 25 (No encryption)') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_encryption" class="form-label">{{ __('Encryption') }}</label>
                  <select class="form-select" id="mail_encryption" name="mail_encryption">
                    <option value="tls" {{ ($settings->mail_encryption ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ ($settings->mail_encryption ?? 'tls') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ ($settings->mail_encryption ?? 'tls') == '' ? 'selected' : '' }}>{{ __('None') }}</option>
                  </select>
                  <small class="form-text text-muted">{{ __('Recommended: TLS for port 587, SSL for port 465') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_username" class="form-label">{{ __('SMTP Username') }}</label>
                  <input type="text" class="form-control" id="mail_username" name="mail_username" value="{{ $settings->mail_username ?? '' }}" placeholder="your-email@example.com">
                  <small class="form-text text-muted">{{ __('Usually your email address') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_password" class="form-label">{{ __('SMTP Password') }}</label>
                  <input type="password" class="form-control" id="mail_password" name="mail_password" value="{{ $settings->mail_password ?? '' }}" placeholder="••••••••">
                  <small class="form-text text-muted">{{ __('Your SMTP password or app-specific password') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_from_address" class="form-label">{{ __('From Email Address') }} <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" value="{{ $settings->mail_from_address ?? ($settings->company_email ?? '') }}" placeholder="noreply@example.com">
                  <small class="form-text text-muted">{{ __('Email address that appears as sender') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="mail_from_name" class="form-label">{{ __('From Name') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="{{ $settings->mail_from_name ?? ($settings->company_name ?? '') }}" placeholder="My Company">
                  <small class="form-text text-muted">{{ __('Name that appears as sender') }}</small>
                </div>

                @if($settings->is_helper_text_enabled)
                  <div class="col-12">
                    <div class="alert alert-warning">
                      <strong>{{ __('Gmail Users:') }}</strong> {{ __('If using Gmail, enable "Less secure app access" or use an "App Password" if 2FA is enabled.') }}
                      <br>
                      <strong>{{ __('Office 365/Outlook:') }}</strong> {{ __('Use smtp.office365.com with port 587 and TLS encryption.') }}
                      <br>
                      <strong>{{ __('SendGrid/Mailgun:') }}</strong> {{ __('Use their SMTP credentials from your dashboard.') }}
                    </div>
                  </div>
                @endif

                <div class="col-12">
                  <hr class="my-3">
                  <h6 class="mb-3">{{ __('Test Email Configuration') }}</h6>
                  <p class="text-muted small">{{ __('Send a test email to verify your SMTP settings are working correctly before saving.') }}</p>

                  <div class="row">
                    <div class="col-md-8">
                      <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                        <input type="email" class="form-control" id="test_email" placeholder="{{ __('Enter email address to test') }}">
                        <button type="button" class="btn btn-outline-primary" id="sendTestEmailBtn">
                          <i class="bx bx-send me-1"></i> {{ __('Send Test Email') }}
                        </button>
                      </div>
                      <small class="form-text text-muted">{{ __('This will use your current settings (saved or unsaved) to send a test email.') }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Mail Settings') }}
              </button>
            </div>
          </div>
        </form>
      </div>

      @if($addonService->isAddonEnabled(ModuleConstants::GOOGLE_RECAPTCHA))
        <!-- Google reCAPTCHA Settings -->
        <div class="settings-section d-none" id="section-recaptcha">
          @include('googlerecaptcha::settings')
        </div>
      @endif

      @if($addonService->isAddonEnabled('AgoraCall'))
        <!-- Agora Call Settings -->
        <div class="settings-section d-none" id="section-agora">
          @include('agoracall::settings')
        </div>
      @endif
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        updateGeneral: "{{ route('settings.updateGeneralSettings') }}",
        updateBranding: "{{ route('settings.updateBrandingSettings') }}",
        updateCompany: "{{ route('settings.updateCompanySettings') }}",
        updateEmployee: "{{ route('settings.updateEmployeeSettings') }}",
        updateMaps: "{{ route('settings.updateMapSettings') }}",
        updateMail: "{{ route('settings.updateMailSettings') }}",
        sendTestEmail: "{{ route('settings.sendTestEmail') }}"
      },
      labels: {
        success: @json(__('Success')),
        error: @json(__('Error')),
        settingsUpdated: @json(__('Settings updated successfully')),
        errorOccurred: @json(__('An error occurred while saving settings')),
        confirmSave: @json(__('Are you sure you want to save these settings?')),
        invalidFileType: @json(__('Invalid file type. Please upload an image file.')),
        fileTooLarge: @json(__('File size is too large. Please upload a smaller file.'))
      }
    };
  </script>
  @vite(['resources/assets/js/settings.js'])
@endsection
