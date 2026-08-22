@extends('layouts.app')
@section('title', 'Settings — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-4xl mx-auto px-6 py-8">
    <h1 class="font-display text-3xl mb-2">API keys & integrations</h1>
    <p class="text-sm text-muted mb-8 leading-relaxed">
        Every key is encrypted at rest and never appears in code or version control.
        Saved values show as <span class="font-mono text-brass">•••• saved</span> — leave a field blank to keep the
        current value, or tick "clear" to remove it.
    </p>

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-10">
        @csrf
        @foreach ($groups as $groupName => $fields)
            <div>
                <h2 class="font-mono text-xs tracking-[0.2em] text-brass mb-4">{{ strtoupper($groupName) }}</h2>
                <div class="bg-panel border border-line rounded-lg divide-y divide-line">
                    @foreach ($fields as $key => $meta)
                        <div class="p-4 flex items-center gap-4">
                            <div class="w-64 shrink-0">
                                <label class="text-sm block">{{ $meta['label'] }}</label>
                                @if(!empty($meta['help']))
                                    <p class="text-xs text-muted mt-0.5">{{ $meta['help'] }}</p>
                                @endif
                            </div>
                            <input type="{{ !empty($meta['secret']) ? 'password' : 'text' }}" name="{{ $key }}" autocomplete="new-password"
                                placeholder="{{ $configured[$key] ? '•••• saved' : 'not set' }}"
                                class="flex-1 bg-ink border border-line rounded px-3 py-2 text-sm font-mono focus:border-brass outline-none">
                            @if($configured[$key])
                                <label class="text-xs text-muted flex items-center gap-1.5 shrink-0">
                                    <input type="checkbox" name="clear_{{ $key }}" value="1" class="accent-loss"> clear
                                </label>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button type="submit" class="bg-brass text-ink px-6 py-2.5 rounded font-medium text-sm hover:bg-brass/90 transition">Save settings</button>
    </form>
</div>

@endsection
