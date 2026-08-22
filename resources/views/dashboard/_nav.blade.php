<nav class="border-b border-line bg-panel">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="flex items-baseline gap-2">
            <span class="font-display italic text-xl">STETECH</span>
            <span class="font-mono text-[10px] tracking-[0.2em] text-brass">AI TRADER</span>
        </a>
        <div class="flex items-center gap-6 text-sm text-muted">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-100">Dashboard</a>
            <a href="{{ route('broker.connect') }}" class="hover:text-slate-100">Broker</a>
            <a href="{{ route('custom-package.create') }}" class="hover:text-slate-100">Custom package</a>
            @auth
                @if(auth()->user()->isAdmin())
                    <span class="w-px h-4 bg-line"></span>
                    <a href="{{ route('admin.control-center') }}" class="hover:text-brass">Control center</a>
                    <a href="{{ route('admin.ai-lab.index') }}" class="hover:text-brass">AI Lab</a>
                    <a href="{{ route('admin.users.index') }}" class="hover:text-brass">Users</a>
                    <a href="{{ route('admin.plans.index') }}" class="hover:text-brass">Plans</a>
                    <a href="{{ route('admin.custom-requests.index') }}" class="hover:text-brass">Requests</a>
                    <a href="{{ route('admin.settings') }}" class="hover:text-brass">Settings</a>
                @endif
                <span class="font-mono text-xs text-slate-300">{{ auth()->user()->name }}</span>
            @endauth
            @include('partials.currency-switcher')
        </div>
    </div>
</nav>
