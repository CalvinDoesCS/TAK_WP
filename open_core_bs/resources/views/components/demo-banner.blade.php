@if(config('app.demo', false))
<div class="demo-banner-wrapper">
    <div class="demo-banner">
        <div class="demo-banner-content">
            <div class="demo-banner-text">
                <i class='bx bx-info-circle'></i>
                <span>{{ __('You are viewing a demo version.') }}</span>
            </div>
            <div class="demo-banner-actions">
                <a href="{{ config('variables.purchaseUrl') }}" target="_blank" class="btn btn-sm btn-light demo-banner-btn">
                    <i class='bx bx-cart'></i>
                    {{ __('Purchase Now') }}
                </a>
                <a href="{{ config('variables.addonsUrl') }}" target="_blank" class="btn btn-sm btn-outline-light demo-banner-btn-outline">
                    <i class='bx bx-package'></i>
                    {{ __('View Addons') }}
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Demo banner wrapper - fixed at top */
.demo-banner-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9999;
    height: 52px;
}

.demo-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 0;
    width: 100%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.demo-banner-content {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.demo-banner-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    font-size: 0.9375rem;
}

.demo-banner-text i {
    font-size: 1.25rem;
}

.demo-banner-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.demo-banner-btn {
    background: white;
    color: #667eea;
    font-weight: 600;
    border: none;
    padding: 0.375rem 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.demo-banner-btn:hover {
    background: rgba(255, 255, 255, 0.9);
    color: #764ba2;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.demo-banner-btn i {
    font-size: 1.125rem;
}

.demo-banner-btn-outline {
    background: transparent;
    color: white;
    font-weight: 600;
    border: 2px solid white;
    padding: 0.375rem 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.demo-banner-btn-outline:hover {
    background: white;
    color: #667eea;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.demo-banner-btn-outline i {
    font-size: 1.125rem;
}

/* Push down the entire application content */
body:has(.demo-banner-wrapper) {
    padding-top: 52px !important;
}

/* Adjust layout elements to account for demo banner */
body:has(.demo-banner-wrapper) .layout-wrapper {
    margin-top: 0 !important;
}

body:has(.demo-banner-wrapper) .layout-navbar {
    top: 52px !important;
}

body:has(.demo-banner-wrapper) .layout-menu {
    top: 52px !important;
}

body:has(.demo-banner-wrapper) .layout-page {
    padding-top: 0 !important;
}

/* Specific fix for horizontal layout (tenant portal) */
body:has(.demo-banner-wrapper) .layout-navbar-full .layout-navbar {
    position: relative !important;
    top: 0 !important;
}

body:has(.demo-banner-wrapper) .layout-menu-horizontal {
    position: relative !important;
    top: 0 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .demo-banner-actions {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
    }

    .demo-banner-actions a {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .demo-banner-wrapper {
        height: auto;
        min-height: 100px;
    }

    .demo-banner-content {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }

    .demo-banner-text {
        justify-content: center;
    }

    .demo-banner {
        padding: 1rem 0;
    }

    body:has(.demo-banner-wrapper) {
        padding-top: 100px !important;
    }

    body:has(.demo-banner-wrapper) .layout-navbar {
        top: 100px !important;
    }

    body:has(.demo-banner-wrapper) .layout-menu {
        top: 100px !important;
    }

    /* Keep horizontal layout relative on mobile too */
    body:has(.demo-banner-wrapper) .layout-navbar-full .layout-navbar {
        position: relative !important;
        top: 0 !important;
    }

    body:has(.demo-banner-wrapper) .layout-menu-horizontal {
        position: relative !important;
        top: 0 !important;
    }
}
</style>
@endif
