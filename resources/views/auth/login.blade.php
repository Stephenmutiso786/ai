@extends('layouts.app')
@section('title', 'Login — STETECH AI')
@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md bg-panel border border-line rounded-lg p-8">
        <div class="mb-8">
            <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">SIGN IN</p>
            <h1 class="font-display text-4xl">Open your trading dashboard</h1>
        </div>

        @if($errors->any())
            <div class="mb-5 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-muted mb-2">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full bg-ink border border-line rounded px-4 py-3 outline-none focus:border-brass">
            </div>
            <div>
                <label class="block text-sm text-muted mb-2">Password</label>
                <div class="relative">
                    <input id="login-password" name="password" type="password" required class="w-full bg-ink border border-line rounded px-4 py-3 pr-12 outline-none focus:border-brass">
                    <button type="button" id="toggle-login-password" class="absolute inset-y-0 right-0 px-3 text-muted hover:text-slate-100" aria-label="Toggle password visibility">👁</button>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" class="accent-brass">
                Remember me
            </label>
            <button class="w-full bg-brass text-ink rounded py-3 font-medium hover:bg-brass/90 transition">Sign in</button>
        </form>

        <p class="mt-6 text-sm text-muted">
            No account yet? <a href="{{ route('register') }}" class="text-brass hover:underline">Create one</a>
        </p>
    </div>
</div>
<script>
const loginPassword = document.getElementById('login-password');
const toggleLoginPassword = document.getElementById('toggle-login-password');
if (loginPassword && toggleLoginPassword) {
    toggleLoginPassword.addEventListener('click', () => {
        loginPassword.type = loginPassword.type === 'password' ? 'text' : 'password';
    });
}
</script>
@endsection
