@extends('layouts.app')
@section('title', 'Users — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-6xl mx-auto px-6 py-8">
    <h1 class="font-display text-3xl mb-6">Manage accounts</h1>

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="bg-panel border border-line rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted font-mono text-[11px] border-b border-line">
                    <th class="px-5 py-2 font-normal">Name</th>
                    <th class="px-5 py-2 font-normal">Email</th>
                    <th class="px-5 py-2 font-normal">Role</th>
                    <th class="px-5 py-2 font-normal">Super Admin</th>
                    <th class="px-5 py-2 font-normal">KYC</th>
                    <th class="px-5 py-2 font-normal">Plan</th>
                    <th class="px-5 py-2 font-normal">Runs used</th>
                    <th class="px-5 py-2 font-normal"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-3">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-muted">{{ $user->email }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $user->roleLabel() }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $user->isSuperAdmin() ? 'YES' : 'NO' }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ strtoupper($user->kyc_status) }}</td>
                        <td class="px-5 py-3">{{ $user->subscription?->plan?->name ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $user->subscription?->runs_used_this_period ?? 0 }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.users.edit', $user) }}" class="text-brass text-xs hover:underline">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</div>

@endsection
