@extends('layouts.layoutMaster')

@section('title', __('SaaS Settings'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/quill/editor.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/quill/quill.js'
    ])
@endsection

@section('page-script')
    @vite(['Modules/MultiTenancyCore/resources/assets/js/saas-settings.js'])
@endsection

@section('content')
    <x-breadcrumb
        :title="__('SaaS Settings')"
        :breadcrumbs="[
            ['name' => __('Admin'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('SaaS Settings'), 'url' => '']
        ]"
    />

    @if($isDemo)
    <div class="alert alert-warning alert-dismissible mb-4" role="alert">
        <i class="bx bx-info-circle me-2"></i>
        <strong>{{ __('Demo Mode') }}</strong> - {{ __('Settings are read-only. Sensitive information is masked and modifications are disabled.') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row mb-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#payment-gateways">
                            <i class="bx bx-credit-card me-1"></i>
                            <span class="d-none d-sm-block">{{ __('Payment Gateways') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#general-settings">
                            <i class="bx bx-cog me-1"></i>
                            <span class="d-none d-sm-block">{{ __('General Settings') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#email-templates">
                            <i class="bx bx-envelope me-1"></i>
                            <span class="d-none d-sm-block">{{ __('Email Templates') }}</span>
                        </a>
                    </li>
                    @if($landingPageEnabled)
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#landing-page">
                            <i class="bx bx-home-alt me-1"></i>
                            <span class="d-none d-sm-block">{{ __('Landing Page') }}</span>
                        </a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content shadow-none">
                    {{-- Payment Gateways Tab --}}
                    <div class="tab-pane fade show active" id="payment-gateways">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-4">{{ __('Configure Payment Gateways') }}</h5>
                                <p class="text-muted mb-4">{{ __('Enable and configure payment methods for tenant subscriptions.') }}</p>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- Offline Payment (Core) --}}
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-0">
                                                <i class="bx bx-money me-2"></i>{{ __('Offline Payment (Bank Transfer)') }}
                                            </h5>
                                            <small class="text-muted">{{ __('Core payment method - Always available') }}</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input gateway-toggle" type="checkbox" id="offlineEnabled" data-gateway="offline" {{ $gatewayStatus['offline'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="offlineEnabled">{{ __('Enabled') }}</label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form id="offlinePaymentForm" onsubmit="updateOfflineSettings(event)">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="bank_name">{{ __('Bank Name') }}</label>
                                                    <input type="text" class="form-control" id="bank_name" name="bank_name"
                                                           value="{{ $offlineSettings['bank_name'] }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="account_name">{{ __('Account Name') }}</label>
                                                    <input type="text" class="form-control" id="account_name" name="account_name"
                                                           value="{{ $offlineSettings['account_name'] }}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label" for="account_number">{{ __('Account Number') }}</label>
                                                    <input type="text" class="form-control" id="account_number" name="account_number"
                                                           value="{{ $offlineSettings['account_number'] }}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label" for="routing_number">{{ __('Routing Number') }}</label>
                                                    <input type="text" class="form-control" id="routing_number" name="routing_number"
                                                           value="{{ $offlineSettings['routing_number'] }}">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label" for="swift_code">{{ __('SWIFT Code') }}</label>
                                                    <input type="text" class="form-control" id="swift_code" name="swift_code"
                                                           value="{{ $offlineSettings['swift_code'] }}">
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <label class="form-label" for="bank_address">{{ __('Bank Address') }}</label>
                                                    <textarea class="form-control" id="bank_address" name="bank_address" rows="2" required>{{ $offlineSettings['bank_address'] }}</textarea>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <label class="form-label" for="payment_instructions">{{ __('Payment Instructions') }}</label>
                                                    <textarea class="form-control" id="payment_instructions" name="payment_instructions" rows="3" required>{{ $offlineSettings['payment_instructions'] }}</textarea>
                                                    <small class="form-text text-muted">{{ __('These instructions will be shown to customers after they choose bank transfer.') }}</small>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-2"></i>{{ __('Save Offline Payment Settings') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- PayPal Gateway --}}
                            @if($paymentGateways['PayPalGateway'])
                                <div class="col-lg-6">
                                    @include('paypalgateway::admin.settings-partial')
                                </div>
                            @else
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bx bx-credit-card-alt bx-lg text-muted mb-3"></i>
                                            <h5>{{ __('PayPal Gateway') }}</h5>
                                            <p class="text-muted mb-3">{{ __('PayPal payment gateway addon is not installed or enabled.') }}</p>
                                            <a href="{{ route('addons.index') }}" class="btn btn-label-primary">
                                                <i class="bx bx-cog me-2"></i>{{ __('Manage Addons') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Stripe Gateway --}}
                            @if($paymentGateways['StripeGateway'])
                                <div class="col-lg-6">
                                    @include('stripegateway::admin.settings-partial')
                                </div>
                            @else
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bx bx-credit-card bx-lg text-muted mb-3"></i>
                                            <h5>{{ __('Stripe Gateway') }}</h5>
                                            <p class="text-muted mb-3">{{ __('Stripe payment gateway addon is not installed or enabled.') }}</p>
                                            <a href="{{ route('addons.index') }}" class="btn btn-label-primary">
                                                <i class="bx bx-cog me-2"></i>{{ __('Manage Addons') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Razorpay Gateway --}}
                            @if($paymentGateways['RazorpayGateway'])
                                <div class="col-lg-6">
                                    @include('razorpaygateway::admin.settings-partial')
                                </div>
                            @else
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bx bx-rupee bx-lg text-muted mb-3"></i>
                                            <h5>{{ __('Razorpay Gateway') }}</h5>
                                            <p class="text-muted mb-3">{{ __('Razorpay payment gateway addon is not installed or enabled.') }}</p>
                                            <a href="{{ route('addons.index') }}" class="btn btn-label-primary">
                                                <i class="bx bx-cog me-2"></i>{{ __('Manage Addons') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- General Settings Tab --}}
                    <div class="tab-pane fade" id="general-settings">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">{{ __('General SaaS Settings') }}</h5>
                                <p class="text-muted">{{ __('Configure general settings for the SaaS platform.') }}</p>
                                
                                <form id="generalSettingsForm" onsubmit="updateGeneralSettings(event)">
                                    <div class="row">
                                        {{-- Registration Settings --}}
                                        <div class="col-12 mb-4">
                                            <h6 class="text-muted text-uppercase">{{ __('Registration Settings') }}</h6>
                                            <hr class="mt-0">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="allow_tenant_registration" 
                                                       name="allow_tenant_registration" {{ $generalSettings['allow_tenant_registration'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="allow_tenant_registration">
                                                    {{ __('Allow Tenant Registration') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Enable public registration for new tenants') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="auto_approve_tenants" 
                                                       name="auto_approve_tenants" {{ $generalSettings['auto_approve_tenants'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_approve_tenants">
                                                    {{ __('Auto Approve Tenants') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Automatically approve new tenant registrations') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="require_email_verification" 
                                                       name="require_email_verification" {{ $generalSettings['require_email_verification'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="require_email_verification">
                                                    {{ __('Require Email Verification') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Require email verification before tenant activation') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="default_plan_id">{{ __('Default Plan') }}</label>
                                            <select class="form-select" id="default_plan_id" name="default_plan_id">
                                                <option value="">{{ __('No default plan') }}</option>
                                                @foreach($plans as $plan)
                                                    <option value="{{ $plan->id }}" {{ $generalSettings['default_plan_id'] == $plan->id ? 'selected' : '' }}>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">{{ __('Default subscription plan for new tenants') }}</small>
                                        </div>
                                        
                                        {{-- Trial Settings --}}
                                        <div class="col-12 mb-4 mt-3">
                                            <h6 class="text-muted text-uppercase">{{ __('Trial Settings') }}</h6>
                                            <hr class="mt-0">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable_trial" 
                                                       name="enable_trial" {{ $generalSettings['enable_trial'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="enable_trial">
                                                    {{ __('Enable Free Trial') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Allow tenants to start with a free trial') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="trial_days">{{ __('Trial Duration (Days)') }}</label>
                                            <input type="number" class="form-control" id="trial_days" name="trial_days" 
                                                   value="{{ $generalSettings['trial_days'] }}" min="1" max="365">
                                            <small class="form-text text-muted">{{ __('Number of days for free trial period') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="require_payment_for_trial" 
                                                       name="require_payment_for_trial" {{ $generalSettings['require_payment_for_trial'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="require_payment_for_trial">
                                                    {{ __('Require Payment Method for Trial') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Collect payment method during trial signup') }}</small>
                                        </div>
                                        
                                        {{-- Subscription Settings --}}
                                        <div class="col-12 mb-4 mt-3">
                                            <h6 class="text-muted text-uppercase">{{ __('Subscription Settings') }}</h6>
                                            <hr class="mt-0">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="grace_period_days">{{ __('Grace Period (Days)') }}</label>
                                            <input type="number" class="form-control" id="grace_period_days" name="grace_period_days"
                                                   value="{{ $generalSettings['grace_period_days'] }}" min="0" max="30">
                                            <small class="form-text text-muted">{{ __('Days to allow access after subscription expires') }}</small>
                                        </div>

                                        {{-- Branding Settings --}}
                                        <div class="col-12 mb-4 mt-3">
                                            <h6 class="text-muted text-uppercase">{{ __('Branding Settings') }}</h6>
                                            <hr class="mt-0">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="platform_name">{{ __('Platform Name') }}</label>
                                            <input type="text" class="form-control" id="platform_name" name="platform_name" 
                                                   value="{{ $generalSettings['platform_name'] }}" required>
                                            <small class="form-text text-muted">{{ __('Your SaaS platform name') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="support_email">{{ __('Support Email') }}</label>
                                            <input type="email" class="form-control" id="support_email" name="support_email"
                                                   value="{{ $generalSettings['support_email'] }}" required>
                                            <small class="form-text text-muted">{{ __('Email for tenant support inquiries') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="currency">{{ __('Currency Code') }}</label>
                                            <input type="text" class="form-control" id="currency" name="currency"
                                                   value="{{ $generalSettings['currency'] }}" required maxlength="10" placeholder="USD">
                                            <small class="form-text text-muted">{{ __('Currency code for pricing (e.g., USD, EUR, SAR)') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="currency_symbol">{{ __('Currency Symbol') }}</label>
                                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol"
                                                   value="{{ $generalSettings['currency_symbol'] }}" required maxlength="10" placeholder="$">
                                            <small class="form-text text-muted">{{ __('Currency symbol to display (e.g., $, €, ﷼)') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="terms_url">{{ __('Terms of Service URL') }}</label>
                                            <input type="url" class="form-control" id="terms_url" name="terms_url" 
                                                   value="{{ $generalSettings['terms_url'] }}">
                                            <small class="form-text text-muted">{{ __('Link to your terms of service') }}</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="privacy_url">{{ __('Privacy Policy URL') }}</label>
                                            <input type="url" class="form-control" id="privacy_url" name="privacy_url" 
                                                   value="{{ $generalSettings['privacy_url'] }}">
                                            <small class="form-text text-muted">{{ __('Link to your privacy policy') }}</small>
                                        </div>
                                        
                                        {{-- Database Provisioning Settings --}}
                                        <div class="col-12 mb-4 mt-3">
                                            <h6 class="text-muted text-uppercase">{{ __('Database Provisioning Settings') }}</h6>
                                            <hr class="mt-0">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="tenant_auto_provisioning"
                                                       name="tenant_auto_provisioning" {{ $generalSettings['tenant_auto_provisioning'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="tenant_auto_provisioning">
                                                    {{ __('Auto Database Provisioning') }}
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">{{ __('Automatically create databases for new tenants (VPS mode)') }}</small>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <div class="alert alert-info mb-0">
                                                <i class="bx bx-info-circle me-2"></i>
                                                <strong>{{ __('MySQL Server Credentials') }}</strong> - {{ __('These credentials are used to connect to MySQL server to create tenant databases') }}
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="tenant_db_host">{{ __('MySQL Host') }}</label>
                                            <input type="text" class="form-control" id="tenant_db_host" name="tenant_db_host"
                                                   value="{{ $generalSettings['tenant_db_host'] ?? 'localhost' }}" required>
                                            <small class="form-text text-muted">{{ __('MySQL server host address') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="tenant_db_port">{{ __('MySQL Port') }}</label>
                                            <input type="text" class="form-control" id="tenant_db_port" name="tenant_db_port"
                                                   value="{{ $generalSettings['tenant_db_port'] ?? '3306' }}" required>
                                            <small class="form-text text-muted">{{ __('MySQL server port') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="tenant_db_username">{{ __('MySQL Username') }}</label>
                                            <input type="text" class="form-control" id="tenant_db_username" name="tenant_db_username"
                                                   value="{{ $generalSettings['tenant_db_username'] ?? 'root' }}" required>
                                            <small class="form-text text-muted">{{ __('MySQL user with CREATE DATABASE privileges') }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="tenant_db_password">{{ __('MySQL Password') }}</label>
                                            <input type="password" class="form-control" id="tenant_db_password" name="tenant_db_password"
                                                   value="{{ $generalSettings['tenant_db_password'] ?? '' }}">
                                            <small class="form-text text-muted">{{ __('MySQL user password') }}</small>
                                        </div>

                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-2"></i>{{ __('Save General Settings') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Email Templates Tab --}}
                    <div class="tab-pane fade" id="email-templates">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <div>
                                        <h5 class="card-title mb-2">{{ __('Email Templates') }}</h5>
                                        <p class="text-muted mb-0">{{ __('Customize email templates for tenant communications. Variables in {brackets} will be replaced with actual values.') }}</p>
                                    </div>
                                    <button type="button" class="btn btn-label-primary" onclick="testSelectedTemplate()">
                                        <i class="bx bx-mail-send me-1"></i>{{ __('Test Template') }}
                                    </button>
                                </div>
                                
                                @php
                                    $emailTemplates = \Modules\MultiTenancyCore\App\Models\SaasNotificationTemplate::orderBy('category')->orderBy('name')->get();
                                    $emailCategories = [
                                        'tenant' => __('Tenant Management'),
                                        'subscription' => __('Subscriptions'), 
                                        'payment' => __('Payments'),
                                        'system' => __('System')
                                    ];
                                @endphp
                                
                                @foreach($emailCategories as $categoryKey => $categoryName)
                                    @php
                                        $categoryTemplates = $emailTemplates->where('category', $categoryKey);
                                    @endphp
                                    
                                    @if($categoryTemplates->count() > 0)
                                        <h6 class="text-muted text-uppercase mt-4 mb-3">{{ $categoryName }}</h6>
                                        
                                        @foreach($categoryTemplates as $template)
                                            <div class="card mb-3 border">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">{{ $template->name }}</h6>
                                                            <p class="text-muted mb-2 small">{{ __('Subject') }}: {{ $template->subject }}</p>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            @if($template->is_active)
                                                                <span class="badge bg-label-success">{{ __('Active') }}</span>
                                                            @else
                                                                <span class="badge bg-label-secondary">{{ __('Inactive') }}</span>
                                                            @endif
                                                            <button type="button" class="btn btn-sm btn-icon btn-label-primary" 
                                                                    onclick="editEmailTemplate({{ $template->id }})">
                                                                <i class="bx bx-edit"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="email-template-content" id="template-content-{{ $template->id }}">
                                                        @php
                                                            $displayBody = str_replace('\n', "\n", $template->body);
                                                            $displayBody = \Illuminate\Support\Str::limit($displayBody, 150);
                                                        @endphp
                                                        <pre class="mb-0 text-muted small" style="white-space: pre-wrap; font-family: inherit;">{{ $displayBody }}</pre>
                                                    </div>
                                                    
                                                    <div class="email-template-form d-none" id="template-form-{{ $template->id }}">
                                                        <form onsubmit="updateEmailTemplate(event, {{ $template->id }})">
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('Subject') }}</label>
                                                                <input type="text" class="form-control" name="subject" value="{{ $template->subject }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('Body') }}</label>
                                                                <div id="editor-{{ $template->id }}" class="mb-2" style="height: 200px;"></div>
                                                                <textarea class="form-control d-none" name="body" id="body-{{ $template->id }}" required>{{ str_replace('\n', "\n", $template->body) }}</textarea>
                                                                <small class="text-muted">{{ __('Available variables') }}: 
                                                                    @foreach($template->variables ?? [] as $var)
                                                                        <code>{{{ $var }}}</code>{{ !$loop->last ? ', ' : '' }}
                                                                    @endforeach
                                                                </small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="is_active" 
                                                                           id="active-{{ $template->id }}" {{ $template->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="active-{{ $template->id }}">
                                                                        {{ __('Active') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" class="btn btn-primary btn-sm">
                                                                    <i class="bx bx-save me-1"></i>{{ __('Save') }}
                                                                </button>
                                                                <button type="button" class="btn btn-label-secondary btn-sm" 
                                                                        onclick="cancelEditTemplate({{ $template->id }})">
                                                                    {{ __('Cancel') }}
                                                                </button>
                                                                <button type="button" class="btn btn-label-info btn-sm ms-auto" 
                                                                        onclick="sendTestEmail({{ $template->id }})">
                                                                    <i class="bx bx-send me-1"></i>{{ __('Test') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Landing Page Tab --}}
                    @if($landingPageEnabled)
                    <div class="tab-pane fade" id="landing-page">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">{{ __('Landing Page Settings') }}</h5>
                                <p class="text-muted">{{ __('Configure your landing page settings and content management.') }}</p>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_landing_page" 
                                                   name="enable_landing_page" {{ \Modules\MultiTenancyCore\App\Models\SaasSetting::get('general_enable_landing_page', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_landing_page">
                                                {{ __('Enable Landing Page') }}
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">{{ __('Show landing page on root URL for non-authenticated users') }}</small>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h6 class="mb-3">{{ __('Quick Links') }}</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('landingPage.settings.index') }}" class="btn btn-outline-primary w-100">
                                                <i class="bx bx-cog me-2"></i>{{ __('Content Settings') }}
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('landingPage.features.index') }}" class="btn btn-outline-primary w-100">
                                                <i class="bx bx-list-check me-2"></i>{{ __('Manage Features') }}
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('landingPage.reviews.index') }}" class="btn btn-outline-primary w-100">
                                                <i class="bx bx-star me-2"></i>{{ __('Manage Reviews') }}
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('landingPage.faqs.index') }}" class="btn btn-outline-primary w-100">
                                                <i class="bx bx-help-circle me-2"></i>{{ __('Manage FAQs') }}
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('landingPage.contactSubmissions.index') }}" class="btn btn-outline-primary w-100">
                                                <i class="bx bx-envelope me-2"></i>{{ __('Contact Submissions') }}
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="/landing" target="_blank" class="btn btn-outline-success w-100">
                                                <i class="bx bx-show me-2"></i>{{ __('Preview Landing Page') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        // Pass server-side data to JavaScript
        window.pageData = {
            urls: {
                toggleGateway: @json(route('multitenancycore.admin.saas-settings.toggle-gateway')),
                updateOffline: @json(route('multitenancycore.admin.saas-settings.update-offline')),
                updateGeneral: @json(route('multitenancycore.admin.saas-settings.update-general'))
            },
            isDemo: @json($isDemo),
            translations: {
                demoModeError: @json(__('Settings cannot be modified in demo mode.'))
            }
        };
    </script>
@endsection
