{{-- Module Details Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="moduleDetailsOffcanvas" aria-labelledby="moduleDetailsOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="moduleDetailsOffcanvasLabel">
            <i class='bx bx-package me-2'></i>{{ __('Module Details') }}
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Module Header with Icon --}}
        <div class="text-center mb-4">
            <div class="avatar avatar-xl mb-3">
                <span class="avatar-initial rounded bg-label-primary">
                    <i class='bx bx-category-alt bx-lg'></i>
                </span>
            </div>
            <h4 class="mb-1" id="module-detail-name">{{ __('Module Name') }}</h4>
            <p class="text-muted" id="module-detail-version">{{ __('Version') }} 1.0.0</p>
        </div>

        {{-- Module Information --}}
        <div class="mb-4">
            <h6 class="mb-3">{{ __('Information') }}</h6>

            {{-- Description --}}
            <div class="mb-3">
                <label class="form-label fw-medium">{{ __('Description') }}</label>
                <p class="text-muted" id="module-detail-description">{{ __('No description available') }}</p>
            </div>

            {{-- Category Badge --}}
            <div class="mb-3">
                <label class="form-label fw-medium">{{ __('Category') }}</label>
                <div>
                    <span class="badge bg-label-info" id="module-detail-category">{{ __('General') }}</span>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="mb-3">
                <label class="form-label fw-medium">{{ __('Status') }}</label>
                <div>
                    <span class="badge" id="module-detail-status">{{ __('Disabled') }}</span>
                </div>
            </div>
        </div>

        {{-- Dependencies Section --}}
        <div class="mb-4" id="dependencies-section">
            <h6 class="mb-3">
                <i class='bx bx-link-alt me-2'></i>{{ __('Dependencies') }}
            </h6>
            <div id="module-detail-dependencies">
                <p class="text-muted small">{{ __('No dependencies') }}</p>
            </div>
        </div>

        {{-- Dependents Section --}}
        <div class="mb-4" id="dependents-section">
            <h6 class="mb-3">
                <i class='bx bx-sitemap me-2'></i>{{ __('Required By') }}
            </h6>
            <div id="module-detail-dependents">
                <p class="text-muted small">{{ __('No modules depend on this') }}</p>
            </div>
        </div>

        {{-- Purchase Link (if available) --}}
        <div class="mb-4" id="purchase-section" style="display: none;">
            <div class="alert alert-info d-flex align-items-center">
                <i class='bx bx-info-circle me-2'></i>
                <div class="flex-grow-1">
                    <strong>{{ __('Premium Module') }}</strong>
                    <p class="mb-0 small">{{ __('This is a premium module') }}</p>
                </div>
            </div>
            <a href="#" id="module-detail-purchase-link" class="btn btn-primary w-100" target="_blank">
                <i class='bx bx-cart me-1'></i>{{ __('Purchase Module') }}
            </a>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex flex-column gap-2" id="module-actions">
            {{-- Enable/Disable Button --}}
            <button type="button" class="btn btn-success" id="module-enable-btn" style="display: none;">
                <i class='bx bx-check-circle me-1'></i>{{ __('Enable Module') }}
            </button>
            <button type="button" class="btn btn-warning" id="module-disable-btn" style="display: none;">
                <i class='bx bx-x-circle me-1'></i>{{ __('Disable Module') }}
            </button>

            {{-- Configure Button (if module has settings) --}}
            <button type="button" class="btn btn-outline-secondary" id="module-configure-btn" style="display: none;">
                <i class='bx bx-cog me-1'></i>{{ __('Configure Module') }}
            </button>

            {{-- Uninstall Button (only in non-demo mode) --}}
            @if(!env('APP_DEMO'))
                <button type="button" class="btn btn-outline-danger" id="module-uninstall-btn">
                    <i class='bx bx-trash me-1'></i>{{ __('Uninstall Module') }}
                </button>
            @endif
        </div>

        {{-- Demo Mode Notice --}}
        @if(env('APP_DEMO'))
            <div class="alert alert-warning mt-3">
                <small>
                    <i class='bx bx-info-circle me-1'></i>
                    {{ __('Some actions are restricted in demo mode') }}
                </small>
            </div>
        @endif
    </div>
</div>
