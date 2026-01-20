@php
use Illuminate\Support\Facades\Route;
use App\Services\AddonService\IAddonService;
$addonService = app(IAddonService::class);
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

    {{-- Check addon availability --}}
    @if(isset($submenu->addon))
      @php
        if(!$addonService->isAddonEnabled($submenu->addon)){
          continue;
        }
      @endphp
    @endif

    {{-- Hide SaaS-only menus from tenant context --}}
    @if(isset($submenu->addon) && $submenu->addon === 'MultiTenancyCore')
      @php
        if(function_exists('isTenant') && isTenant()){
          continue;
        }
      @endphp
    @endif

    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName =  Route::currentRouteName();

      if ($currentRouteName === $submenu->slug) {
          // If there's a submenu, use 'active open' to expand it, otherwise just 'active'
          $activeClass = isset($submenu->submenu) ? $active : 'active';

          // Additional check for query parameters in URL
          if (isset($submenu->url) && str_contains($submenu->url, '?')) {
              // Parse query string from submenu URL
              $urlParts = parse_url($submenu->url);
              if (isset($urlParts['query'])) {
                  parse_str($urlParts['query'], $submenuParams);
                  $currentParams = request()->query();

                  // Check if current URL has the same query parameters
                  $paramsMatch = true;
                  foreach ($submenuParams as $key => $value) {
                      if (!isset($currentParams[$key]) || $currentParams[$key] != $value) {
                          $paramsMatch = false;
                          break;
                      }
                  }

                  // Only mark as active if query parameters match
                  if (!$paramsMatch) {
                      $activeClass = null;
                  }
              }
          }
      }
      elseif (isset($submenu->submenu)) {
        if (gettype($submenu->slug) === 'array') {
          foreach($submenu->slug as $slug){
            if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = $active;
            }
          }
        }
        else{
          if (str_contains($currentRouteName,$submenu->slug) and strpos($currentRouteName,$submenu->slug) === 0) {
            $activeClass = $active;
          }
        }
      }
    @endphp

      <li class="menu-item {{$activeClass}}">
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
           class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
           @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
          <i class="{{ $submenu->icon }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endisset
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
  @endif
</ul>
