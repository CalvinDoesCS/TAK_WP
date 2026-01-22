@props([
    'client',
    'showLocation' => false,
    'showCategory' => false,
    'linkRoute' => 'fieldmanager.clients.show',
    'avatarSize' => 'sm'
])

@if($client)
    <div class="d-flex justify-content-start align-items-center">
        <div class="avatar-wrapper">
            <div class="avatar avatar-{{ $avatarSize }} me-3">
                @if(isset($client->image_url) && $client->image_url)
                    <img src="{{ $client->image_url }}" alt="Client" class="rounded-circle" />
                @else
                    <span class="avatar-initial rounded-circle bg-label-info">
                        {{ strtoupper(substr($client->name, 0, 2)) }}
                    </span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column">
            <a href="{{ route($linkRoute, $client->id) }}"
               class="text-heading text-truncate">
                <span class="fw-medium">{{ $client->name }}</span>
            </a>
            @if($showLocation && ($client->city || $client->state))
                <small class="text-muted">
                    {{ implode(', ', array_filter([$client->city, $client->state])) }}
                </small>
            @elseif($showCategory && $client->category)
                <small class="text-muted">{{ $client->category }}</small>
            @elseif($client->email)
                <small class="text-muted">{{ $client->email }}</small>
            @elseif($client->phone)
                <small class="text-muted">{{ $client->phone }}</small>
            @else
                <small class="text-muted">—</small>
            @endif
        </div>
    </div>
@else
    <div class="d-flex justify-content-start align-items-center">
        <div class="avatar-wrapper">
            <div class="avatar avatar-{{ $avatarSize }} me-3">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                    <i class="bx bx-buildings"></i>
                </span>
            </div>
        </div>
        <div class="d-flex flex-column">
            <span class="text-muted text-truncate">
                <span class="fw-medium">{{ __('No Client') }}</span>
            </span>
            <small class="text-muted">—</small>
        </div>
    </div>
@endif
