@props([
    'title' => '',
    'value' => 0,
    'icon' => 'bx-bar-chart',
    'iconColor' => 'primary',
    'url' => null,
    'change' => null,
    'changeType' => 'neutral', // 'up', 'down', 'neutral'
    'loading' => false
])

<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="card-info flex-grow-1">
                    <p class="card-text mb-1">{{ $title }}</p>
                    @if($loading)
                        <div class="spinner-border spinner-border-sm text-{{ $iconColor }}" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                    @else
                        <h4 class="card-title mb-0">{{ $value }}</h4>
                    @endif

                    @if($change !== null && !$loading)
                        <small class="text-{{ $changeType === 'up' ? 'success' : ($changeType === 'down' ? 'danger' : 'muted') }}">
                            @if($changeType === 'up')
                                <i class="bx bx-up-arrow-alt"></i>
                            @elseif($changeType === 'down')
                                <i class="bx bx-down-arrow-alt"></i>
                            @endif
                            {{ $change }}
                        </small>
                    @endif
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-{{ $iconColor }} rounded p-2">
                        <i class="bx {{ $icon }} bx-sm"></i>
                    </span>
                </div>
            </div>
        </div>
        @if($url)
            <div class="card-footer border-top">
                <a href="{{ $url }}" class="text-{{ $iconColor }} d-flex align-items-center justify-content-center">
                    {{ __('View Details') }}
                    <i class="bx bx-right-arrow-alt ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</div>
