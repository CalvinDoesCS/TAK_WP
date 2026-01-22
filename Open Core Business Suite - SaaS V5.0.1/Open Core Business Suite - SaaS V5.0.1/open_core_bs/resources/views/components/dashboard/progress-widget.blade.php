@props([
    'title' => '',
    'subtitle' => null,
    'icon' => 'bx-trending-up',
    'items' => [],
    'colClass' => 'col-lg-4 col-md-6',
])

<div class="{{ $colClass }} mb-4">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="bx {{ $icon }}"></i>
                {{ $title }}
            </h5>
            @if($subtitle)
                <small class="text-muted">{{ $subtitle }}</small>
            @endif
        </div>
        <div class="card-body">
            @if(count($items) > 0)
                @foreach($items as $item)
                    <div class="mb-3 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted">{{ $item['label'] }}</span>
                            <span class="fw-semibold">
                                {{ $item['value'] ?? 0 }}
                                @if(isset($item['total']))
                                    <span class="text-muted">/ {{ $item['total'] }}</span>
                                @endif
                                @if(isset($item['percentage']))
                                    <span class="text-{{ $item['percentageColor'] ?? 'primary' }}">
                                        ({{ $item['percentage'] }}%)
                                    </span>
                                @endif
                            </span>
                        </div>
                        @if(isset($item['progress']))
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $item['progressColor'] ?? 'primary' }}"
                                     role="progressbar"
                                     style="width: {{ $item['progress'] }}%"
                                     aria-valuenow="{{ $item['progress'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-4">
                    <p class="text-muted mb-0">{{ __('No data available') }}</p>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
