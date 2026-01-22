@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', __('Module Access Denied'))

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<!-- Error -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">403</h1>
    <h4 class="mb-2 mx-2">{{ __('Module Access Denied') }}</h4>

    @if($reason === 'plan')
      <p class="mb-4 mx-2">
        {{ __('The :module module is not included in your current subscription plan.', ['module' => preg_replace('/(?<!^)([A-Z])/', ' $1', $moduleName)]) }}
      </p>
      <p class="mb-6 mx-2 text-muted">
        {{ __('Please contact your administrator to access this feature.') }}
      </p>
      <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('Go to Dashboard') }}</a>
    @else
      <p class="mb-6 mx-2">{{ __('You do not have permission to access this feature.') }}</p>
      <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('Go to Dashboard') }}</a>
    @endif

    <div class="mt-6">
      <img src="{{asset('assets/img/illustrations/page-misc-error-'.$configData['style'].'.png')}}" alt="module-access-denied" width="500" class="img-fluid" data-app-light-img="illustrations/page-misc-error-light.png" data-app-dark-img="illustrations/page-misc-error-dark.png">
    </div>
  </div>
</div>
<!-- /Error -->
@endsection
