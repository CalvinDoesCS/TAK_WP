@php
  use App\Services\AddonService\IAddonService;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $modules = \Nwidart\Modules\Facades\Module::all();
  $addonService = app(IAddonService::class);
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  @if(!isset($navbarFull))
    <div class="app-brand demo">
      <a href="{{url('/')}}" class="app-brand-link">
        <span class="app-brand-logo demo">
          <img src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png')}}"
               alt="Logo"
               width="27">
        </span>
        <span class="app-brand-text demo menu-text fw-bold ms-2">
          {{$settings->app_name ?? config('variables.templateName')}}
        </span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
      </a>
    </div>
  @endif

  <div class="menu-inner-shadow"></div>
  <ul class="menu-inner py-1">
    @foreach ($menuData[3]->menu as $menu)

      @if(isset($menu->addon))
        @php

          if(!$addonService->isAddonEnabled($menu->addon)){
            continue;
          }
        @endphp
      @endif

      {{-- Hide SaaS-only menus from tenant context --}}
      @if(isset($menu->addon) && $menu->addon === 'MultiTenancyCore')
        @php
          if(function_exists('isTenant') && isTenant()){
            continue;
          }
        @endphp
      @endif

      {{-- adding active and open class if child is active --}}

      {{-- menu headers --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
      @else

        {{-- active menu method --}}
        @php
          $activeClass = null;
          $currentRouteName = Route::currentRouteName();

          if ($currentRouteName === $menu->slug) {
            // If there's a submenu, use 'active open' to expand it
            $activeClass = isset($menu->submenu) ? 'active open' : 'active';
          }
          elseif (isset($menu->submenu)) {
            if (gettype($menu->slug) === 'array') {
              foreach($menu->slug as $slug){
                if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                  $activeClass = 'active open';
                }
              }
            }
            else{
              if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
                $activeClass = 'active open';
              }
            }
          }
        @endphp

        {{-- main menu --}}
        <li class="menu-item {{$activeClass}}">
          <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
             class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
             @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
            @isset($menu->badge)
              <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
            @endisset
          </a>

          {{-- submenu --}}
          @isset($menu->submenu)
            @include('layouts.sections.menu.submenu',['menu' => $menu->submenu])
          @endisset
        </li>
      @endif
    @endforeach
  </ul>

  <!-- User Profile Card - Sticky Bottom -->
  <div class="menu-user-profile">
    <div class="user-profile-card">
      <div class="dropdown">
        <a class="user-profile-toggle" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="d-flex align-items-center gap-3 p-3">
            <div class="avatar avatar-lg avatar-online">
              @if(Auth::user() && !is_null(Auth::user()->profile_picture))
                <img src="{{ Auth::user()->getProfilePicture() }}" alt="{{ Auth::user()->getFullName() }}" class="rounded-circle">
              @else
                <span class="avatar-initial rounded-circle bg-primary">{{ Auth::user()->getInitials() }}</span>
              @endif
            </div>
            <div class="user-info flex-grow-1">
              <h6 class="mb-0 fw-semibold text-heading">{{Auth::user()->getFullName()}}</h6>
              <small class="text-muted">{{Auth::user()->roles()->first()->name ?? __('User')}}</small>
            </div>
            <i class="bx bx-chevron-up"></i>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end modern-dropdown w-100">
          <li>
            <a class="dropdown-item" href="{{ route('account.myProfile') }}">
              <i class="bx bx-user me-2"></i>
              <span class="align-middle">@lang('My Profile')</span>
            </a>
          </li>
          <li>
            <div class="dropdown-divider"></div>
          </li>
          @if (Auth::check())
            <li>
              <a class="dropdown-item text-danger" href="{{ route('auth.logout') }}"
                 onclick="event.preventDefault(); document.getElementById('logout-form-menu').submit();">
                <i class='bx bx-power-off me-2'></i>
                <span class="align-middle">@lang('Logout')</span>
              </a>
            </li>
            <form method="POST" id="logout-form-menu" action="{{ route('auth.logout') }}">
              @csrf
            </form>
          @endif
        </ul>
      </div>
    </div>
  </div>

</aside>
