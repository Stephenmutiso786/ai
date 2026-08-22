<x-app-layout>
<div class="max-w-7xl mx-auto py-8 space-y-8">
 <h1 class="text-2xl font-bold">Broker Certification & Trading Operations</h1>
 <p>Certification performs read-only production checks: connection, account snapshot, symbol discovery, contract specifications and position reads. No test order is sent from this screen.</p>
 @if(session('status'))<div class="p-3 rounded bg-green-100">{{ session('status') }}</div>@endif
 <div class="overflow-x-auto bg-white shadow rounded"><table class="min-w-full text-sm"><thead><tr><th>Account</th><th>Broker</th><th>Platform</th><th>Certification</th><th></th></tr></thead><tbody>@foreach($accounts as $account)<tr class="border-t"><td class="p-3">{{ $account->name ?? $account->account_number ?? $account->id }}</td><td>{{ $account->broker ?? '-' }}</td><td>{{ $account->platform ?? '-' }}</td><td>{{ optional($certifications->firstWhere('broker_account_id',$account->id))->status ?? 'not run' }}</td><td><form method="POST" action="{{ route('admin.broker-certification.run',$account) }}">@csrf<button class="px-3 py-1 rounded bg-black text-white">Run Certification</button></form></td></tr>@endforeach</tbody></table></div>
 <h2 class="text-xl font-bold">Failed Executions</h2>
 <div class="overflow-x-auto bg-white shadow rounded"><table class="min-w-full text-sm"><thead><tr><th>ID</th><th>Stage</th><th>Error</th><th>Status</th><th></th></tr></thead><tbody>@foreach($failures as $f)<tr class="border-t"><td class="p-3">{{ $f->id }}</td><td>{{ $f->stage }}</td><td>{{ $f->error }}</td><td>{{ $f->status }}</td><td><form method="POST" action="{{ route('admin.execution-failures.resolve',$f) }}">@csrf<button class="underline">Resolve</button></form></td></tr>@endforeach</tbody></table></div>
</div>
</x-app-layout>
