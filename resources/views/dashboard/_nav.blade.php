<nav class="border-b border-line bg-panel">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="flex items-baseline gap-2 shrink-0">
                    <span class="font-display italic text-xl">STETECH</span>
                    <span class="font-mono text-[10px] tracking-[0.2em] text-brass">AI TRADER</span>
                </a>
                <div class="lg:hidden">
                    @include('partials.currency-switcher')
                </div>
            </div>
            <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:justify-end text-sm text-muted">
                @auth
                    <div class="flex items-center gap-2 rounded border border-line bg-ink/50 px-3 py-2 text-xs" title="Users active in the last five minutes">
                        <span class="h-2 w-2 rounded-full bg-gain animate-pulse"></span>
                        <span class="font-mono text-slate-300">ONLINE {{ $onlineUsers?->count() ?? 0 }}</span>
                        <span class="hidden xl:inline text-muted">|</span>
                        <span class="hidden xl:flex flex-wrap gap-x-3 gap-y-1">
                            @forelse($onlineUsers ?? [] as $onlineUser)
                                <span class="inline-flex items-center gap-1 text-muted">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gain"></span>
                                    {{ $onlineUser->name }}
                                    <span class="text-[10px] text-brass">{{ $onlineUser->isSuperAdmin() ? 'Super Admin' : $onlineUser->roleLabel() }}</span>
                                </span>
                            @empty
                                <span>No active users</span>
                            @endforelse
                        </span>
                    </div>
                @endauth
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-slate-100">Dashboard</a>
                    <a href="{{ route('broker.connect') }}" class="hover:text-slate-100">Broker</a>
                    <a href="{{ route('custom-package.create') }}" class="hover:text-slate-100">Custom package</a>
                    @auth
                        <a href="{{ route('two-factor.show') }}" class="hover:text-slate-100">Two-factor</a>
                        @can('view-admin-dashboard')
                            <a href="{{ route('admin.control-center') }}" class="hover:text-brass">Control center</a>
                        @endcan
                        @can('manage-ai-lab')
                            <a href="{{ route('admin.ai-lab.index') }}" class="hover:text-brass">AI Lab</a>
                        @endcan
                        @can('manage-users')
                            <a href="{{ route('admin.users.index') }}" class="hover:text-brass">Users</a>
                        @endcan
                        @can('manage-plans')
                            <a href="{{ route('admin.plans.index') }}" class="hover:text-brass">Plans</a>
                        @endcan
                        @can('manage-custom-requests')
                            <a href="{{ route('admin.custom-requests.index') }}" class="hover:text-brass">Requests</a>
                        @endcan
                        @can('manage-settings')
                            <a href="{{ route('admin.settings') }}" class="hover:text-brass">Settings</a>
                        @endcan
                        @can('manage-broker-certification')
                            <a href="{{ route('admin.broker-certification.index') }}" class="hover:text-brass">Broker certification</a>
                        @endcan
                        @can('manage-operations')
                            <a href="{{ route('admin.operations.index') }}" class="hover:text-brass">Operations</a>
                        @endcan
                        <span class="font-mono text-xs text-slate-300 flex items-center gap-2">
                            {{ auth()->user()->name }}
                            @if(auth()->user()->isSuperAdmin())
                                <span class="px-2 py-0.5 rounded border border-brass/40 text-[10px] tracking-wide text-brass">SUPER ADMIN</span>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-xs uppercase tracking-wide text-muted hover:text-loss">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-slate-100">Login</a>
                        <a href="{{ route('register') }}" class="hover:text-brass">Register</a>
                    @endauth
                </div>
                <div class="hidden lg:block">
                    @include('partials.currency-switcher')
                </div>
            </div>
        </div>
    </div>
</nav>
