@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', __('Subscription Expired'))

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<!-- Error -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">403</h1>
    <h4 class="mb-2 mx-2">{{ __('Subscription Expired') }} ⚠️</h4>
    <p class="mb-6 mx-2">{{ __('Your subscription has expired. Please renew to continue using the service.') }}</p>
    <a href="{{ config('app.url') }}" class="btn btn-primary">{{ __('Go to Main Site') }}</a>
    <div class="mt-6">
      <img src="{{asset('assets/img/illustrations/page-misc-error-'.$configData['style'].'.png')}}" alt="subscription-expired" width="500" class="img-fluid" data-app-light-img="illustrations/page-misc-error-light.png" data-app-dark-img="illustrations/page-misc-error-dark.png">
    </div>
  </div>
</div>
<!-- /Error -->
@endsection