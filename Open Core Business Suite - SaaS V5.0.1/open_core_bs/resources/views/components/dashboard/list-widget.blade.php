@props([
    'title' => '',
    'icon' => 'bx-list-ul',
    'items' => [],
    'emptyMessage' => null,
    'viewAllUrl' => null,
    'viewAllText' => null,
    'colClass' => 'col-lg-4 col-md-6',
])

<div class="{{ $colClass }} mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between pb-0">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="bx {{ $icon }}"></i>
                {{ $title }}
            </h5>
            @if($viewAllUrl)
                <a href="{{ $viewAllUrl }}" class="text-muted">
                    <small>{{ $viewAllText ?? __('View All') }}</small>
                </a>
            @endif
        </div>
        <div class="card-body">
            @if(count($items) > 0)
                <ul class="list-unstyled mb-0">
                    @foreach($items as $item)
                        <li class="mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start">
                                @if(isset($item['icon']))
                                    <div class="me-2">
                                        <i class="bx {{ $item['icon'] }} text-{{ $item['iconColor'] ?? 'primary' }}"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    @if(isset($item['title']))
                                        <h6 class="mb-0">{{ $item['title'] }}</h6>
                                    @endif
                                    @if(isset($item['description']))
                                        <small class="text-muted">{{ $item['description'] }}</small>
                                    @endif
                                    @if(isset($item['meta']))
                                        <div class="text-muted">
                                            <small>{{ $item['meta'] }}</small>
                                        </div>
                                    @endif
                                </div>
                                @if(isset($item['badge']))
                                    <span class="badge bg-label-{{ $item['badgeColor'] ?? 'primary' }}">
                                        {{ $item['badge'] }}
                                    </span>
                                @endif
                                @if(isset($item['url']))
                                    <a href="{{ $item['url'] }}" class="ms-2 text-muted">
                                        <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-4">
                    <i class="bx bx-info-circle bx-lg text-muted mb-2"></i>
                    <p class="text-muted mb-0">{{ $emptyMessage ?? __('No items found') }}</p>
                </div>
            @endif

            {{ $slot }}
        </div>
        @if($viewAllUrl)
            <div class="card-footer text-center border-top">
                <a href="{{ $viewAllUrl }}" class="text-primary">
                    {{ $viewAllText ?? __('View All') }}
                </a>
            </div>
        @endif
    </div>
</div>
