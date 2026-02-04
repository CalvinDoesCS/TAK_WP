{{-- Form for creating/editing plans --}}
<form id="planForm" method="POST" action="{{ $formAction }}">
    @csrf
    @if(isset($plan) && $plan->exists)
        @method('PUT')
    @endif

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-12 col-lg-8">

            {{-- Basic Information Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-info-circle me-2"></i>
                        {{ __('Basic Information') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Plan Name --}}
                        <div class="col-md-8">
                            <label for="name" class="form-label">{{ __('Plan Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $plan->name ?? old('name') }}" placeholder="{{ __('e.g., Professional, Enterprise') }}" required>
                        </div>

                        {{-- Is Active --}}
                        <div class="col-md-2">
                            <label class="form-label d-block">{{ __('Status') }}</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ ($plan->is_active ?? old('is_active', true)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                        </div>

                        {{-- Is Featured --}}
                        <div class="col-md-2">
                            <label class="form-label d-block">{{ __('Featured') }}</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ ($plan->is_featured ?? old('is_featured', false)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">{{ __('Featured') }}</label>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="{{ __('Brief description of what this plan offers') }}">{{ $plan->description ?? old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Module Access Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-grid-alt me-2"></i>
                        {{ __('Module Access') }}
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Allow All Modules Toggle --}}
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_all_modules"
                                name="allow_all_modules" value="1"
                                {{ (isset($plan) && $plan->hasAllModulesAccess()) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="allow_all_modules">
                                {{ __('Allow All Modules') }}
                            </label>
                        </div>
                        <small class="text-muted">
                            {{ __('Enable this for unlimited plans that include all current and future addon modules.') }}
                        </small>
                    </div>

                    {{-- Core Modules Section --}}
                    @if(count($coreModules) > 0)
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted mb-2">
                                <i class="bx bx-check-shield text-success"></i>
                                {{ __('Core Modules') }}
                                <span class="badge bg-label-success">{{ __('Always Included') }}</span>
                            </h6>
                            <p class="text-muted small mb-3">
                                {{ __('These modules are automatically included in every plan and cannot be removed.') }}
                            </p>
                            <div class="row g-3">
                                @foreach($coreModules as $module)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked disabled id="core_module_{{ $loop->index }}">
                                                <label class="form-check-label text-muted fw-semibold" for="core_module_{{ $loop->index }}">
                                                    {{ $module }}
                                                </label>
                                            </div>
                                            <span class="badge bg-success mt-2">
                                                <i class="bx bx-check-circle"></i> {{ __('Core') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <hr class="my-4">
                    @endif

                    {{-- Addon Modules Section --}}
                    <div id="addon-modules-section">
                        <div class="mb-3">
                            <h6 class="text-uppercase text-muted mb-2">
                                <i class="bx bx-package"></i>
                                {{ __('Add-on Modules') }}
                            </h6>
                            <p class="text-muted small mb-3">
                                {{ __('Select specific addon modules to include in this plan. If no modules are selected, the plan will only have core modules.') }}
                            </p>
                        </div>

                        <div class="row g-3">
                        @forelse($addonModules as $module)
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input module-checkbox" type="checkbox" name="restrictions[modules][]" value="{{ $module }}" id="module_{{ $loop->index }}"
                                            {{ (isset($plan) && !$plan->hasAllModulesAccess() && in_array($module, $plan->getAllowedModules() ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="module_{{ $loop->index }}">
                                            {{ $module }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bx bx-info-circle"></i>
                                    {{ __('No addon modules available') }}
                                </div>
                            </div>
                        @endforelse
                    </div>

                        @if(count($addonModules) > 0)
                            <div class="mt-4 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="selectAllModules">
                                    <i class="bx bx-check-square"></i> {{ __('Select All') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="deselectAllModules">
                                    <i class="bx bx-square"></i> {{ __('Deselect All') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="col-12 col-lg-4">

            {{-- Pricing Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-dollar me-2"></i>
                        {{ __('Pricing') }}
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Price --}}
                    <div class="mb-3">
                        <label for="price" class="form-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ \Modules\MultiTenancyCore\App\Models\SaasSetting::get('general_currency_symbol', '$') }}</span>
                            <input type="number" class="form-control" id="price" name="price" value="{{ $plan->price ?? old('price', 0) }}" step="0.01" min="0" required>
                        </div>
                    </div>

                    {{-- Billing Period --}}
                    <div class="mb-3">
                        <label for="billing_period" class="form-label">{{ __('Billing Period') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="billing_period" name="billing_period" required>
                            <option value="monthly" {{ (($plan->billing_period ?? old('billing_period')) == 'monthly') ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                            <option value="yearly" {{ (($plan->billing_period ?? old('billing_period')) == 'yearly') ? 'selected' : '' }}>{{ __('Yearly') }}</option>
                            <option value="lifetime" {{ (($plan->billing_period ?? old('billing_period')) == 'lifetime') ? 'selected' : '' }}>{{ __('Lifetime') }}</option>
                        </select>
                    </div>

                    {{-- Trial Days --}}
                    <div class="mb-0">
                        <label for="trial_days" class="form-label">{{ __('Trial Days') }}</label>
                        <input type="number" class="form-control" id="trial_days" name="trial_days" value="{{ $plan->trial_days ?? old('trial_days', 0) }}" min="0">
                        <small class="text-muted">{{ __('Number of free trial days (0 = no trial)') }}</small>
                    </div>
                </div>
            </div>

            {{-- Usage Limits Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-slider me-2"></i>
                        {{ __('Usage Limits') }}
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        {{ __('Set resource limits for this plan. Use -1 for unlimited access.') }}
                    </p>

                    {{-- Max Users --}}
                    <div class="mb-3">
                        <label for="max_users" class="form-label">
                            {{ __('Max Users') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="max_users" name="restrictions[max_users]" value="{{ $plan->getMaxUsers() ?? old('restrictions.max_users', -1) }}" required>
                        <small class="text-muted">{{ __('-1 = Unlimited') }}</small>
                    </div>

                    {{-- Max Employees --}}
                    <div class="mb-3">
                        <label for="max_employees" class="form-label">
                            {{ __('Max Employees') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="max_employees" name="restrictions[max_employees]" value="{{ $plan->getMaxEmployees() ?? old('restrictions.max_employees', -1) }}" required>
                        <small class="text-muted">{{ __('-1 = Unlimited') }}</small>
                    </div>

                    {{-- Max Storage GB --}}
                    <div class="mb-0">
                        <label for="max_storage_gb" class="form-label">
                            {{ __('Max Storage') }} (GB) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="max_storage_gb" name="restrictions[max_storage_gb]" value="{{ $plan->getMaxStorageGb() ?? old('restrictions.max_storage_gb', -1) }}" step="0.1" required>
                        <small class="text-muted">{{ __('-1 = Unlimited') }}</small>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i>
                    {{ isset($plan) && $plan->exists ? __('Update Plan') : __('Create Plan') }}
                </button>
                <a href="{{ route('multitenancycore.admin.plans.index') }}" class="btn btn-label-secondary">
                    <i class="bx bx-x me-1"></i>
                    {{ __('Cancel') }}
                </a>
            </div>

        </div>
    </div>
</form>
