@extends('layouts.layoutMaster')

@section('title', __('Company Profile'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/jquery/jquery.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Company Profile') }}</h2>
                    <p class="text-muted">{{ __('Manage your company information and settings') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Company Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $tenant->name }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" id="email" value="{{ $tenant->email }}" disabled>
                                    <small class="text-muted">{{ __('Contact support to change your email address') }}</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">{{ __('Phone') }}</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="{{ $tenant->phone }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">{{ __('Website') }}</label>
                                    <input type="url" class="form-control" id="website" name="website" value="{{ $tenant->website }}" placeholder="https://example.com">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="tax_id" class="form-label">{{ __('Tax ID / VAT Number') }}</label>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id" value="{{ $tenant->tax_id }}">
                                </div>

                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="mb-3">{{ __('Address Information') }}</h6>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">{{ __('Street Address') }}</label>
                                    <textarea class="form-control" id="address" name="address" rows="2">{{ $tenant->address }}</textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">{{ __('City') }}</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ $tenant->city }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="state" class="form-label">{{ __('State / Province') }}</label>
                                    <input type="text" class="form-control" id="state" name="state" value="{{ $tenant->state }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="country" class="form-label">{{ __('Country') }}</label>
                                    <input type="text" class="form-control" id="country" name="country" value="{{ $tenant->country }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label">{{ __('Postal Code') }}</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $tenant->postal_code }}">
                                </div>

                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="mb-3">{{ __('Account Information') }}</h6>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Account Status') }}</label>
                                    <div>
                                        <span class="badge bg-label-{{ $tenant->status === 'active' ? 'success' : ($tenant->status === 'approved' ? 'info' : 'warning') }}">
                                            {{ ucfirst($tenant->status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Member Since') }}</label>
                                    <div>{{ $tenant->created_at->format('M d, Y') }}</div>
                                </div>

                                @if($tenant->subdomain)
                                    @php
                                        $appDomain = parse_url(config('app.url'), PHP_URL_HOST);
                                    @endphp
                                    <div class="col-12 mb-3">
                                        <label class="form-label">{{ __('Your Subdomain') }}</label>
                                        <div>
                                            <a href="{{ $tenant->getSubdomainUrl() }}" target="_blank" rel="noopener noreferrer" class="text-primary">
                                                {{ $tenant->subdomain }}.{{ $appDomain }}
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary" id="saveBtn">
                                    <i class="bx bx-save me-2"></i>{{ __('Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        const pageData = {
            updateUrl: "{{ route('multitenancycore.tenant.profile.update') }}",
            translations: {
                success: "{{ __('Success!') }}",
                error: "{{ __('Error!') }}",
                saving: "{{ __('Saving...') }}",
                saveChanges: "{{ __('Save Changes') }}",
                validationError: "{{ __('Validation Error') }}",
                validationMessage: "{{ __('Please check the form and try again.') }}",
                errorOccurred: "{{ __('An error occurred. Please try again.') }}"
            }
        };
    </script>
    @vite(['Modules/MultiTenancyCore/resources/assets/js/tenant/profile.js'])
@endsection