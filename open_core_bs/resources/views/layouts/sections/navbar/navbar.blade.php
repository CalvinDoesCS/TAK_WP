@php
  use Illuminate\Support\Facades\Auth;
  $containerNav = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
  $navbarDetached = ($navbarDetached ?? '');
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
  <nav
    class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme modern-navbar"
    id="layout-navbar">
    @endif
    @if(isset($navbarDetached) && $navbarDetached == '')
      <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme modern-navbar"
           id="layout-navbar">
        <div class="{{$containerNav}}">
          @endif

          <!--  Brand demo (display only for navbar-full and hide on below xl) -->
          @if(isset($navbarFull))
            <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
              <a href="{{url('/')}}" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img src="{{ $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : asset('assets/img/logo.png')}}"
                       alt="Logo"
                       width="27">
                </span>
                <span class="app-brand-text demo menu-text fw-bold text-heading">
                  {{ $settings->app_name ?? config('variables.templateName') }}
                </span>
              </a>

              @if(isset($menuHorizontal))
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                  <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
                </a>
              @endif
            </div>
          @endif

          <!-- ! Not required for layout-without-menu -->
          @if(!isset($navbarHideToggle))
            <div
              class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="bx bx-menu bx-md"></i>
              </a>
            </div>
          @endif

          <div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">

            @if(!isset($menuHorizontal))
              <!-- Search (hidden for tenant users) -->
              @if($configData['displaySearch'] == true && !(auth()->check() && auth()->user()->hasRole('tenant')))
                <div class="navbar-nav align-items-center flex-grow-1">
                  <div class="nav-item modern-search-wrapper w-100">
                    <div class="input-group input-group-merge">
                      <span class="input-group-text border-0 bg-transparent ps-0">
                        <i class="bx bx-search bx-md text-muted"></i>
                      </span>
                      <input type="text"
                             class="form-control border-0 bg-transparent modern-search-input"
                             placeholder="@lang('Search anything...')"
                             aria-label="Search">
                      <span class="input-group-text border-0 bg-transparent pe-0">
                        <kbd class="bg-body-secondary text-muted border-0 px-2 py-1">Ctrl+K</kbd>
                      </span>
                    </div>
                  </div>
                </div>
              @endif
              <!-- /Search -->
            @endif

            <ul class="navbar-nav flex-row align-items-center ms-auto gap-1 flex-shrink-0">
              @if(isset($menuHorizontal))
                <!-- Search (hidden for tenant users) -->
                @if($configData['displaySearch'] == true && !(auth()->check() && auth()->user()->hasRole('tenant')))
                  <li class="nav-item navbar-search-wrapper">
                    <a class="nav-link modern-icon-btn" href="javascript:void(0);">
                      <i class="bx bx-search bx-md"></i>
                    </a>
                  </li>
                @endif
                <!-- /Search -->
              @endif

              @if(auth()->check() && !auth()->user()->hasRole('tenant'))
                {{-- Application-specific items - hide for tenant users --}}
                @if($configData['displayQuickCreate'] == true)
                  @include('layouts.sections.menu.quickCreateMenu')
                @endif

                <!-- Notification -->
                @if($configData['displayNotification'] == true)
                  @include('layouts.sections.navbar.notifications')
                @endif
                <!--/ Notification -->

                <!-- Divider -->
                <li class="nav-item">
                  <div class="navbar-divider"></div>
                </li>

                <!-- Secondary Actions Group -->
                @if(auth()->user()->hasRole(['super_admin', 'admin']))
                  <li class="nav-item">
                    <a class="nav-link modern-icon-btn" href="{{route('settings.index')}}"
                       data-bs-toggle="tooltip"
                       data-bs-placement="bottom"
                       title="@lang('Settings')">
                      <i class="bx bx-cog bx-md"></i>
                    </a>
                  </li>
                @endif

                @if($configData['displayAddon'] == true && auth()->user()->hasRole(['super_admin', 'admin']) && !(function_exists('tenant') && tenant()))
                  <li class="nav-item">
                    <a class="nav-link modern-icon-btn" href="{{route('addons.index')}}"
                       data-bs-toggle="tooltip"
                       data-bs-placement="bottom"
                       title="@lang('Addons')">
                      <i class="bx bx-category bx-md"></i>
                    </a>
                  </li>
                @endif
              @endif

              <!-- Language -->
              @if($configData['displayLanguage'] == true)
                <li class="nav-item dropdown">
                  <a class="nav-link modern-icon-btn" href="javascript:void(0);" data-bs-toggle="dropdown"
                     aria-expanded="false">
                    <i class='bx bx-globe bx-md'></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                    <li class="dropdown-header text-uppercase small text-muted">@lang('Language')</li>
                    <li>
                      <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                         href="{{url('lang/en')}}"
                         data-language="en">
                        <span class="align-middle">English</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}"
                         href="{{url('lang/fr')}}"
                         data-language="fr">
                        <span class="align-middle">French</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}"
                         href="{{url('lang/ar')}}"
                         data-language="ar">
                        <span class="align-middle">Arabic</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item {{ app()->getLocale() === 'de' ? 'active' : '' }}"
                         href="{{url('lang/de')}}"
                         data-language="de">
                        <span class="align-middle">German</span>
                      </a>
                    </li>
                  </ul>
                </li>
              @endif
              <!-- /Language -->

              @if($configData['hasCustomizer'] == true)
                <!-- Theme Switcher -->
                <li class="nav-item dropdown dropdown-style-switcher">
                  <a class="nav-link modern-icon-btn" href="javascript:void(0);"
                     data-bs-toggle="dropdown"
                     aria-expanded="false">
                    <i class='bx bx-palette bx-md'></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                    <li class="dropdown-header text-uppercase small text-muted">@lang('Theme')</li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                        <i class='bx bx-sun me-2'></i>
                        <span class="align-middle">Light</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                        <i class="bx bx-moon me-2"></i>
                        <span class="align-middle">Dark</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                        <i class="bx bx-desktop me-2"></i>
                        <span class="align-middle">System</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ Theme Switcher -->
              @endif

              <!-- User Profile (only for horizontal layout) -->
              @if(Auth::check() && isset($menuHorizontal))
                <li class="nav-item navbar-dropdown dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar avatar-online">
                      @if(!is_null(Auth::user()->profile_picture))
                        <img src="{{ Auth::user()->getProfilePicture() }}" alt="{{ Auth::user()->getFullName() }}" class="rounded-circle">
                      @else
                        <span class="avatar-initial rounded-circle bg-primary">{{ Auth::user()->getInitials() }}</span>
                      @endif
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              @if(!is_null(Auth::user()->profile_picture))
                                <img src="{{ Auth::user()->getProfilePicture() }}" alt="{{ Auth::user()->getFullName() }}" class="rounded-circle">
                              @else
                                <span class="avatar-initial rounded-circle bg-primary">{{ Auth::user()->getInitials() }}</span>
                              @endif
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-0">{{ Auth::user()->getFullName() }}</h6>
                            <small class="text-muted">{{ Auth::user()->roles()->first()->name ?? __('User') }}</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    @if(!auth()->user()->hasRole('tenant'))
                      <li>
                        <a class="dropdown-item" href="{{ route('account.myProfile') }}">
                          <i class="bx bx-user me-2"></i>
                          <span class="align-middle">@lang('My Profile')</span>
                        </a>
                      </li>
                      <li>
                        <div class="dropdown-divider"></div>
                      </li>
                    @endif
                    <li>
                      <a class="dropdown-item text-danger" href="{{ route('auth.logout') }}"
                         onclick="event.preventDefault(); document.getElementById('logout-form-navbar').submit();">
                        <i class='bx bx-power-off me-2'></i>
                        <span class="align-middle">@lang('Logout')</span>
                      </a>
                    </li>
                  </ul>
                  <form method="POST" id="logout-form-navbar" action="{{ route('auth.logout') }}" class="d-none">
                    @csrf
                  </form>
                </li>
              @endif
              <!--/ User Profile -->

            </ul>
          </div>

          @if(isset($navbarDetached) && $navbarDetached == '')
        </div>
        @endif
      </nav>
      <!-- / Navbar -->
