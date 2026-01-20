@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/blankLayout')

@section('title', 'Page Not Found')

@section('page-style')
  <!-- Page -->
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection


@section('content')
  <!-- Error -->
  <div class="container-xxl container-p-y">
    <div class="misc-wrapper">
      <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">404</h1>
      <h4 class="mb-2 mx-2">{{ __('Page Not Found') }} ⚠️</h4>
      <p class="mb-6 mx-2">{{ __('The page you are looking for does not exist or has been moved') }}</p>
      <a href="{{url('/')}}" class="btn btn-primary">{{ __('Back to home') }}</a>
      <div class="mt-6">
        <img src="{{asset('assets/img/illustrations/page-misc-error-'.$configData['style'].'.png')}}" alt="page-misc-error-light" width="500" class="img-fluid" data-app-light-img="illustrations/page-misc-error-light.png" data-app-dark-img="illustrations/page-misc-error-dark.png">
      </div>
    </div>
  </div>
  <!-- /Error -->
@endsection
