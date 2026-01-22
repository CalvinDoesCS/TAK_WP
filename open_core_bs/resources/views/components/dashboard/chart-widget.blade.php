@props([
    'title' => '',
    'subtitle' => null,
    'icon' => 'bx-bar-chart-alt',
    'chartId' => 'chart-' . uniqid(),
    'height' => '300',
    'headerActions' => null,
    'colClass' => 'col-lg-6 col-md-12',
])

<div class="{{ $colClass }} mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="bx {{ $icon }}"></i>
                    {{ $title }}
                </h5>
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if($headerActions)
                <div class="dropdown">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
        <div class="card-body">
            <div id="{{ $chartId }}" style="min-height: {{ $height }}px;"></div>
            {{ $slot }}
        </div>
    </div>
</div>
