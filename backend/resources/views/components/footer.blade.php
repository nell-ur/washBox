@props(['role' => 'admin'])

<footer class="app-footer bg-white border-top py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="footer-left d-flex align-items-center gap-3">
            <div class="small text-muted">&copy; {{ date('Y') }} <span class="fw-bold">WASHBOX</span></div>
            <div class="small text-muted">&middot;</div>
            <div class="small text-muted">Version {{ config('app.version', env('APP_VERSION', '1.0')) }}</div>

            @if($role === 'staff' && auth()->check() && optional(auth()->user())->branch)
                <div class="small text-muted">&middot; Branch: {{ strtoupper(optional(auth()->user()->branch)->name) }}</div>
            @endif
        </div>

        <div class="footer-right d-flex align-items-center gap-3">
            <a href="{{ url('/help') }}" class="small text-muted">Support</a>
            <a href="{{ url('/terms') }}" class="small text-muted">Terms</a>

            @if($role === 'admin')
                <a href="{{ url('/admin/system') }}" class="small text-muted">System</a>
            @endif

            <div class="vr mx-2"></div>

            <div class="small text-muted">
                Signed in as <span class="fw-bold">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</span>
            </div>
        </div>
    </div>
</footer>

<style>
    .app-footer { min-height: 56px; }
    .app-footer .small { font-size: .8rem; }
    .app-footer a.small { text-decoration: none; }
    .app-footer .vr { height: 20px; width: 1px; background: rgba(0,0,0,0.08); }
</style>
