<div class="d-flex flex-wrap gap-1">
    @php
        $restrictions = $plan->restrictions ?? [];
        $maxUsers = $restrictions['max_users'] ?? null;
        $maxEmployees = $restrictions['max_employees'] ?? null;
        // Use getAllowedModules() to properly distinguish null from empty array
        $modules = $plan->getAllowedModules();
    @endphp

    @if($maxUsers !== null)
        <span class="badge bg-label-info">
            {{ $maxUsers == -1 ? __('Unlimited Users') : $maxUsers . ' ' . __('Users') }}
        </span>
    @endif

    @if($maxEmployees !== null)
        <span class="badge bg-label-info">
            {{ $maxEmployees == -1 ? __('Unlimited Employees') : $maxEmployees . ' ' . __('Employees') }}
        </span>
    @endif

    @if($modules === null)
        <span class="badge bg-label-success">{{ __('All Modules') }}</span>
    @elseif(empty($modules))
        <span class="badge bg-label-warning">{{ __('Core Only') }}</span>
    @else
        <span class="badge bg-label-info">{{ count($modules) }} {{ __('Modules') }}</span>
    @endif
</div>
