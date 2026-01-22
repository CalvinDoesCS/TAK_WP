@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', __('System Not Ready'))

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<!-- Error -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper text-center">
    <div class="mb-4">
      <i class="bx bx-server" style="font-size: 8rem; color: #696cff;"></i>
    </div>
    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">503</h1>
    <h4 class="mb-2 mx-2">{{ __('System Not Ready') }} ⏳</h4>
    <p class="mb-4 mx-2">{{ __('Your system is being set up. Please check back in a few minutes.') }}</p>
    <p class="text-muted mb-6 mx-2">{{ __('Our team has been notified and your database is currently being provisioned.') }}</p>
    <a href="{{ config('app.url') }}" class="btn btn-primary">
      <i class="bx bx-home me-2"></i>{{ __('Go to Main Site') }}
    </a>
  </div>
</div>
<!-- /Error -->
@endsection