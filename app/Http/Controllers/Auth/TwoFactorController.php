<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TotpAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class TwoFactorController extends Controller
{
    public function show(Request $request, TotpAuthenticator $totp)
    {
        $user = Auth::user();

        if ($user->twoFactorEnabled()) {
            return view('auth.two-factor-manage', ['enabled' => true]);
        }

        $secret = $request->session()->get('2fa.pending_secret') ?? $totp->generateSecret();
        $request->session()->put('2fa.pending_secret', $secret);

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'uri' => $totp->provisioningUri($secret, $user->email),
        ]);
    }

    public function confirm(Request $request, TotpAuthenticator $totp)
    {
        $data = $request->validate(['code' => 'required|string']);
        $secret = $request->session()->get('2fa.pending_secret');

        if (! $secret || ! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'That code did not match. Scan the QR again and try the current 6-digit code.']);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();

        Auth::user()->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->forget('2fa.pending_secret');

        return view('auth.two-factor-recovery-codes', ['codes' => $recoveryCodes]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        Auth::user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication disabled.');
    }
}

