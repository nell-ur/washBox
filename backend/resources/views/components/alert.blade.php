@props(['type' => 'info', 'message' => ''])

@if(session('success') || session('error') || session('warning') || session('info') || $message)
<div class="alert
    @if(session('success') || $type == 'success') alert-success
    @elseif(session('error') || $type == 'error') alert-danger
    @elseif(session('warning') || $type == 'warning') alert-warning
    @else alert-info
    @endif
    alert-dismissible fade show" role="alert">

    {{ session('success') ?? session('error') ?? session('warning') ?? session('info') ?? $message }}

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
