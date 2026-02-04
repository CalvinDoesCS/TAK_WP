@php
  $configData = Helper::appClasses();
  use App\Config\Constants;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Roles - Apps')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/app/role-index.js',
    ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Roles')"
      :breadcrumbs="[
        ['name' => __('Roles'), 'url' => '']
      ]"
      :home-url="url('/')"
    >
      <button data-bs-target="#addOrUpdateRoleOffcanvas" data-bs-toggle="offcanvas"
              class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>{{ __('Add New Role') }}
      </button>
    </x-breadcrumb>

    {{-- Role Cards --}}
    <div class="row g-6">
    @forelse($roles as $role)
      <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h6 class="fw-normal mb-0 text-body">@lang('Total') {{$role->users()->count()}} @lang('Users')</h6>
              <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                @foreach($role->users()->limit(3)->get() as $user)
                  @php
                    $randomStatusColor = ['primary', 'success', 'danger', 'warning', 'info', 'dark'];
                    $randomColor = $randomStatusColor[array_rand($randomStatusColor)];
                  @endphp
                  <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                      title="{{$user->getFullName()}}"
                      class="avatar pull-up">
                    @if($user->profile_picture)
                      <img class="rounded-circle"
                           src="{{$user->getProfilePicture()}}"
                           alt="Avatar">
                    @else
                      <span
                        class="avatar-initial rounded-circle bg-label-{{$randomColor}}">{{ $user->getInitials() }}</span>
                    @endif
                  </li>
                @endforeach
                @if($role->users()->count() > 3)
                  <li class="avatar">
                    <span class="avatar-initial rounded-circle pull-up" data-bs-toggle="tooltip"
                          data-bs-placement="bottom"
                          title="{{$role->users()->count() - 3}} more">+{{$role->users()->count() - 3}}</span>
                  </li>
                @endif

              </ul>
            </div>
            <div class="d-flex justify-content-between align-items-end">
              <div class="role-heading">
                <h5 class="mb-1">{{$role->name}}</h5>
              </div>
              <div class="d-flex">
                <a href="javascript:void(0);"><i
                    class="bx bx-pencil  bx-md me-2 edit" data-value="{{$role}}"></i></a>
                <a href="javascript:void(0);" onclick="deleteRole({{$role->id}})"><i
                    class="bx bx-trash bx-md text-danger "></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      {{-- No Roles Found --}}
      <div class="col-12">
        <div class="card">
          <div class="card-body text-center py-5">
            <div class="mb-4">
              <i class="bx bx-shield bx-lg text-muted"></i>
            </div>
            <h5 class="mb-2">{{ __('No Roles Found') }}</h5>
            <p class="text-muted mb-4">{{ __('Get started by creating your first role.') }}</p>
            <button data-bs-target="#addOrUpdateRoleOffcanvas" data-bs-toggle="offcanvas"
                    class="btn btn-primary">
              <i class="bx bx-plus me-1"></i>{{ __('Add New Role') }}
            </button>
          </div>
        </div>
      </div>
    @endforelse
    <div class="col-xl-4 col-lg-6 col-md-6">
      <div class="card h-100">
        <div class="row h-100">
          <div class="col-sm-5">
            <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-4 ps-6">
              <img src="{{ asset('assets/img/illustrations/lady-with-laptop-'.$configData['style'].'.png') }}"
                   class="img-fluid" alt="Image" width="120"
                   data-app-light-img="illustrations/lady-with-laptop-light.png"
                   data-app-dark-img="illustrations/lady-with-laptop-dark.png">
            </div>
          </div>
          <div class="col-sm-7">
            <div class="card-body text-sm-end text-center ps-sm-0">
              <button data-bs-target="#addOrUpdateRoleOffcanvas" data-bs-toggle="offcanvas"
                      class="btn btn-sm btn-primary mb-4 text-nowrap add-new-role">{{ __('Add New Role') }}
              </button>
              <p class="mb-0">{{ __('Add new role,') }} <br> {{ __('if it doesn\'t exist.') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- / Role Cards --}}

  @if($settings->is_helper_text_enabled)
    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
      <div class="alert-body d-flex">
        <h6 class="alert-heading">
          <i class="bx bx-info-circle me-2"></i>{{ __('Warning:') }}
        </h6>
        <p class="mb-0">
          {{ __('Do not delete the default system roles') }} <strong>{{ implode(', ', Constants::BuiltInRoles) }}.</strong> {{ __('Deleting these roles will cause the system to malfunction.') }}
        </p>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Add/Update Role Offcanvas --}}
  @include('_partials._modals.role.addOrUpdate-role')
  </div>
@endsection
