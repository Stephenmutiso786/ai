<form method="POST" action="{{ route('currency.set') }}" class="flex items-center gap-2" onchange="this.submit()">
    @csrf
    <select name="currency" class="bg-transparent border border-line rounded px-2 py-1 text-xs font-mono text-muted hover:border-brass/60 focus:border-brass outline-none">
        @foreach (config('currency.selectable') as $code)
            <option value="{{ $code }}" {{ $currentCurrency === $code ? 'selected' : '' }}>{{ $code }}</option>
        @endforeach
    </select>
    @if($currentCurrencyAutoDetected ?? false)
        <span class="text-[10px] text-muted font-mono" title="Detected from your location">auto</span>
    @endif
</form>
