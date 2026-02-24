@props([
    'route' => null,
    'icon' => 'bi-circle',
    'label' => 'Link',
    'badge' => null,
    'badgeClass' => 'bg-primary',
    'checkRoute' => false, // Set to true to check if route exists
])

@php
    // Check if route exists if requested
    if ($checkRoute) {
        try {
            $routeExists = Route::has($route);
        } catch (Exception $e) {
            $routeExists = false;
        }
    } else {
        $routeExists = true;
    }

    // Only set active if route exists
    $isActive = $routeExists ? request()->routeIs($route) : false;

    // Generate URL - if route doesn't exist, use #
    $url = $routeExists ? route($route) : '#';
@endphp

@if($routeExists)
<li>
    <a href="{{ $url }}"
       class="nav-link {{ $isActive ? 'active' : '' }}"
       title="{{ $label }}"
       @if(!$routeExists) onclick="event.preventDefault(); console.warn('Route not defined: {{ $route }}')" @endif>
        <i class="{{ $icon }}"></i>
        <span class="menu-text">{{ $label }}</span>
        @if($badge !== null && $badge > 0)
            <span class="badge {{ $badgeClass }} ms-auto">
                {{ $badge > 99 ? '99+' : $badge }}
            </span>
        @endif
    </a>
</li>
@endif
