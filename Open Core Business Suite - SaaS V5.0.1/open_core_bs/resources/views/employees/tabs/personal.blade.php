@php
    use Carbon\Carbon;
    $settings = \App\Models\Settings::first();
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Personal Information') }}</h5>
        @if (!$isExitedEmployee)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasEditBasicInfo" onclick="loadEditBasicInfo()">
                <i class="bx bx-edit me-1"></i>{{ __('Edit') }}
            </button>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-4">
            {{-- Basic Information --}}
            <div class="col-12">
                <h6 class="text-muted mb-3">{{ __('Basic Information') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Full Name') }}</label>
                        <p class="mb-0 fw-medium">{{ $user->getFullName() }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Gender') }}</label>
                        <p class="mb-0">{{ $user->gender ? ucfirst(is_object($user->gender) ? $user->gender->value : $user->gender) : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Date of Birth') }}</label>
                        <p class="mb-0">{{ $user->dob ? Carbon::parse($user->dob)->format('d M Y') . ' (' . Carbon::parse($user->dob)->age . ' ' . __('years') . ')' : __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Blood Group') }}</label>
                        <p class="mb-0">{{ $user->blood_group ?? __('N/A') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-12"><hr></div>

            {{-- Contact Information --}}
            <div class="col-12">
                <h6 class="text-muted mb-3">{{ __('Contact Information') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Email Address') }}</label>
                        <p class="mb-0">
                            <i class="bx bx-envelope me-1"></i>
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Phone Number') }}</label>
                        <p class="mb-0">
                            <i class="bx bx-phone me-1"></i>
                            @if($settings && $settings->phone_country_code)
                                {{ $settings->phone_country_code }}-{{ $user->phone }}
                            @else
                                {{ $user->phone }}
                            @endif
                        </p>
                    </div>
                    @if ($user->alternate_number)
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Alternate Number') }}</label>
                            <p class="mb-0">
                                <i class="bx bx-phone-call me-1"></i>
                                @if($settings && $settings->phone_country_code)
                                    {{ $settings->phone_country_code }}-{{ $user->alternate_number }}
                                @else
                                    {{ $user->alternate_number }}
                                @endif
                            </p>
                        </div>
                    @endif
                    <div class="col-md-12 mb-3">
                        <label class="form-label text-muted small">{{ __('Address') }}</label>
                        <p class="mb-0">
                            <i class="bx bx-map me-1"></i>
                            {{ $user->address ?? __('N/A') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12"><hr></div>

            {{-- Emergency Contact --}}
            <div class="col-12">
                <h6 class="text-muted mb-3">{{ __('Emergency Contact') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Contact Name') }}</label>
                        <p class="mb-0">{{ $user->emergency_contact_name ?? __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Relationship') }}</label>
                        <p class="mb-0">{{ $user->emergency_contact_relationship ?? __('N/A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Phone Number') }}</label>
                        <p class="mb-0">
                            <i class="bx bx-phone me-1"></i>
                            {{ $user->emergency_contact_phone ?? __('N/A') }}
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">{{ __('Address') }}</label>
                        <p class="mb-0">{{ $user->emergency_contact_address ?? __('N/A') }}</p>
                    </div>
                </div>
            </div>

            @if ($user->bankAccount)
                <div class="col-12"><hr></div>

                {{-- Bank Account Details --}}
                <div class="col-12">
                    <h6 class="text-muted mb-3">{{ __('Bank Account Details') }}</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Bank Name') }}</label>
                            <p class="mb-0">{{ $user->bankAccount->bank_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Account Name') }}</label>
                            <p class="mb-0">{{ $user->bankAccount->account_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Account Number') }}</label>
                            <p class="mb-0 font-monospace">{{ $user->bankAccount->account_number }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">{{ __('Branch') }}</label>
                            <p class="mb-0">{{ $user->bankAccount->branch_name }} ({{ $user->bankAccount->branch_code }})</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
