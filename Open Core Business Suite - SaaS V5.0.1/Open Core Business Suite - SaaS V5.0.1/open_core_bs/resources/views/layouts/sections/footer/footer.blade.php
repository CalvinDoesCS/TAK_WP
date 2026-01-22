@php
  use Illuminate\Support\Facades\Session;
  $containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
  $tenant = $settings->app_name ?? config('variables.templateFullName');
    if(config('custom.custom.activationService')){
    $activationService = app()->make(\App\Services\Activation\IActivationService::class);
    $licenseStatus = \Illuminate\Support\Facades\Cache::store('file')->get('license_validity_' . config('app.url'));
  }
@endphp

  <!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        ©
        <script>document.write(new Date().getFullYear());</script>
        , made with ❤️ by <a href="{{ (!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '') }}"
                             target="_blank"
                             class="footer-link">{{ (!empty(config('variables.creatorName')) ? config('variables.creatorName') : '') }}</a>
      </div>
      <div class="d-none d-lg-inline-block">
        {{-- <a href="{{ config('variables.licenseUrl') ? config('variables.licenseUrl') : '#' }}" class="footer-link me-4" target="_blank">License</a>
         <a href="{{ config('variables.moreThemes') ? config('variables.moreThemes') : '#' }}" target="_blank" class="footer-link me-4">More Themes</a>
         <a href="{{ config('variables.documentation') ? config('variables.documentation').'/laravel-introduction.html' : '#' }}" target="_blank" class="footer-link me-4">Documentation</a>
         <a href="{{ config('variables.support') ? config('variables.support') : '#' }}" target="_blank" class="footer-link d-none d-sm-inline-block">Support</a>--}}

       @if(config('custom.custom.activationService'))
          <a href="{{route('activation.index')}}"
             data-bs-toggle="tooltip"
             class="footer-link me-4"
             title="{{$licenseStatus ? "You're running a genuine copy." : "You are running an unlicensed copy."}}">
            <span class="footer-link-text">License Status</span>
            @if($licenseStatus)
              <i class="bx bxs-check-circle text-success ms-1"></i>
            @else
              <i class="bx bxs-x-circle text-danger ms-1"></i>
            @endif
          </a>
        @else
          <a href="{{ config('variables.support') }}"
             target="_blank"
             class="footer-link me-4">
            <span class="footer-link-text">Support</span>
          </a>
          <a href="{{ config('variables.documentation') }}"
             target="_blank"
             class="footer-link me-4">
            <span class="footer-link-text">Documentation</span>
          </a>
        @endif
        <a href="{{ route('system-status.index') }}" class="footer-link" data-bs-toggle="tooltip" title="{{ __('View System Status') }}">
          <span class="text-muted">v{{ config('variables.templateVersion') }}</span>
        </a>
      </div>
    </div>
  </div>
</footer>
<!--/ Footer-->
