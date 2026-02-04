@extends('layouts.layoutMaster')

@section('title', $moduleName)

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
    @php
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Settings'), 'url' => '']
        ];
    @endphp
    <x-breadcrumb :title="__('Settings')" :breadcrumbs="$breadcrumbs" />

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="{{ $moduleIcon }} me-2"></i>
                    <h5 class="mb-0">{{ $moduleName }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">{{ $moduleDescription }}</p>

                        <form id="accountingCoreSettingsForm">
                            @csrf

                            @foreach($sections as $section)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <i class="{{ $section['icon'] }} me-2"></i>
                                            <div>
                                                <h6 class="mb-1">{{ $section['title'] }}</h6>
                                                <small class="text-muted">{{ $section['description'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($section['fields'] as $field)
                                                @if($field['type'] === 'text')
                                                    <div class="col-md-6 mb-3">
                                                        <label for="{{ $field['name'] }}" class="form-label">
                                                            {{ $field['label'] }}
                                                            @if($field['required'] ?? false)
                                                                <span class="text-danger">*</span>
                                                            @endif
                                                        </label>
                                                        <input type="text"
                                                               class="form-control"
                                                               id="{{ $field['name'] }}"
                                                               name="{{ $field['name'] }}"
                                                               value="{{ $currentValues[$field['name']] ?? $field['default'] ?? '' }}"
                                                               {{ ($field['required'] ?? false) ? 'required' : '' }}>
                                                        @if($field['help'] ?? false)
                                                            <small class="form-text text-muted">{{ $field['help'] }}</small>
                                                        @endif
                                                    </div>
                                                @elseif($field['type'] === 'number')
                                                    <div class="col-md-6 mb-3">
                                                        <label for="{{ $field['name'] }}" class="form-label">
                                                            {{ $field['label'] }}
                                                            @if($field['required'] ?? false)
                                                                <span class="text-danger">*</span>
                                                            @endif
                                                        </label>
                                                        <input type="number"
                                                               class="form-control"
                                                               id="{{ $field['name'] }}"
                                                               name="{{ $field['name'] }}"
                                                               value="{{ $currentValues[$field['name']] ?? $field['default'] ?? '' }}"
                                                               min="{{ $field['min'] ?? '' }}"
                                                               max="{{ $field['max'] ?? '' }}"
                                                               {{ ($field['required'] ?? false) ? 'required' : '' }}>
                                                        @if($field['help'] ?? false)
                                                            <small class="form-text text-muted">{{ $field['help'] }}</small>
                                                        @endif
                                                    </div>
                                                @elseif($field['type'] === 'switch')
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">{{ $field['label'] }}</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   id="{{ $field['name'] }}"
                                                                   name="{{ $field['name'] }}"
                                                                   value="1"
                                                                   {{ ($currentValues[$field['name']] ?? $field['default'] ?? false) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="{{ $field['name'] }}">
                                                                @if($field['help'] ?? false)
                                                                    {{ $field['help'] }}
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
                            </button>
                            <button type="reset" class="btn btn-outline-secondary ms-2">
                                <i class="bx bx-reset me-1"></i> {{ __('Reset') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        const pageData = {
            urls: {
                update: "{{ route('accountingcore.settings.update') }}"
            },
            labels: {
                success: @json(__('Success')),
                error: @json(__('Error')),
                settingsUpdated: @json(__('Settings updated successfully')),
                errorOccurred: @json(__('An error occurred while saving settings'))
            }
        };
    </script>
    @vite(['Modules/AccountingCore/resources/assets/js/settings.js'])
@endsection