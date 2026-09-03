@extends('layouts.app')
@section('content')

<div class="flex min-h-screen">
    <aside class="w-60 shrink-0 border-r border-line bg-panel flex flex-col">
        <a href="{{ route('home') }}" class="flex items-baseline gap-2 px-5 py-5 border-b border-line">
            <span class="font-display italic text-xl">STETECH</span>
            <span class="font-mono text-[9px] tracking-[0.2em] text-brass">AI TRADER</span>
        </a>

        <nav class="flex-1 overflow-y-auto py-4 space-y-6">
            <div>
                <p class="px-5 font-mono text-[10px] tracking-[0.15em] text-muted mb-2">TRADING</p>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('dashboard') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Dashboard</a>
                <a href="{{ route('broker.connect') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('broker.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Broker connection</a>
                <a href="{{ route('custom-package.create') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('custom-package.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Custom package</a>
            </div>

            <div>
                <p class="px-5 font-mono text-[10px] tracking-[0.15em] text-muted mb-2">ACCOUNT</p>
                <a href="{{ route('two-factor.show') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('two-factor.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Two-factor auth</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-left flex items-center gap-2.5 px-5 py-2 text-sm text-slate-300 hover:bg-ink/60">Sign out</button>
                </form>
            </div>

            @can('view-admin-dashboard')
                <div>
                    <p class="px-5 font-mono text-[10px] tracking-[0.15em] text-brass mb-2">ADMIN</p>
                    @can('view-admin-dashboard')
                        <a href="{{ route('admin.control-center') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.control-center') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Control center</a>
                    @endcan
                    @can('manage-ai-lab')
                        <a href="{{ route('admin.ai-lab.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.ai-lab.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">AI Lab</a>
                    @endcan
                    @can('manage-users')
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.users.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Users</a>
                    @endcan
                    @can('manage-plans')
                        <a href="{{ route('admin.plans.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.plans.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Plans</a>
                    @endcan
                    @can('manage-custom-requests')
                        <a href="{{ route('admin.custom-requests.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.custom-requests.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Custom requests</a>
                    @endcan
                    @can('manage-settings')
                        <a href="{{ route('admin.settings') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.settings') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Settings / API keys</a>
                    @endcan
                    @can('manage-broker-certification')
                        <a href="{{ route('admin.broker-certification.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.broker-certification.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Broker certification</a>
                    @endcan
                    @can('manage-operations')
                        <a href="{{ route('admin.operations.index') }}" class="flex items-center gap-2.5 px-5 py-2 text-sm {{ request()->routeIs('admin.operations.*') ? 'text-brass bg-ink' : 'text-slate-300 hover:bg-ink/60' }}">Operations</a>
                    @endcan
                </div>
            @endcan
        </nav>

        <div class="px-5 py-4 border-t border-line flex items-center justify-between">
            <span class="font-mono text-xs text-slate-400">{{ auth()->user()?->name }}</span>
            @include('partials.currency-switcher')
        </div>
    </aside>

    <main class="flex-1 min-w-0">
        <div class="px-6 py-8">
            @yield('shell-content')
        </div>
    </main>
</div>

@endsection
