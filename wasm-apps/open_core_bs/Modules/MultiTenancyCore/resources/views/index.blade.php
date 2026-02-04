@extends('multitenancycore::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('multitenancycore.name') !!}</p>
@endsection
