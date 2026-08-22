@extends('layouts.app')
@section('title', 'Edit user — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-lg mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">ACCOUNT</p>
    <h1 class="font-display text-3xl mb-8">{{ $user->name }}</h1>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">ROLE</label>
            <select name="role" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none">
                <option value="client" {{ $user->role === 'client' ? 'selected' : '' }}>Client</option>
                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">KYC STATUS</label>
            <select name="kyc_status" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none">
                <option value="pending" {{ $user->kyc_status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="verified" {{ $user->kyc_status === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="rejected" {{ $user->kyc_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">SUBSCRIPTION PLAN</label>
            <select name="subscription_plan_id" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none">
                <option value="">— leave unchanged —</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" {{ $user->subscription?->subscription_plan_id === $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-muted mt-1.5">Assigning a plan here resets the weekly run counter and starts a fresh billing period.</p>
        </div>

        @if($user->riskProfile)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="trading_halted" value="1" {{ $user->riskProfile->trading_halted ? 'checked' : '' }} class="accent-loss">
                Trading halted for this account
            </label>
        @endif

        <button type="submit" class="w-full bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Save changes</button>
    </form>
</div>

@endsection
