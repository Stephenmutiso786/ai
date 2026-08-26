<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Auth\TotpAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function createLogin(): View
    {
        return view('auth.login');
    }

    public function createRegister(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderByRaw('price_usd_weekly is null, price_usd_weekly asc')
            ->get();

        $selectedPlan = request()->filled('plan')
            ? $plans->firstWhere('slug', request('plan'))
            : $plans->first();

        return view('auth.register', [
            'plans' => $plans,
            'plan' => $selectedPlan,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => "Too many attempts. Try again in {$seconds}s."])->onlyInput('email');
        }

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['email' => 'The provided credentials are incorrect.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $user = Auth::user();

        if ($user->twoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('2fa.user_id', $user->id);
            $request->session()->put('2fa.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan' => ['required', 'exists:subscription_plans,slug'],
        ]);

        $plan = SubscriptionPlan::where('slug', $data['plan'])->where('is_active', true)->firstOrFail();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'client',
            'kyc_status' => 'pending',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addWeek(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', "Account created. Your {$plan->name} plan is active.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function challenge()
    {
        if (! session('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, TotpAuthenticator $totp)
    {
        $userId = session('2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $data = $request->validate(['code' => 'required|string']);

        $verified = false;

        try {
            if (preg_match('/^\d{6}$/', $data['code'])) {
                $verified = $totp->verify(Crypt::decryptString($user->two_factor_secret), $data['code']);
            } else {
                $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes ?? '[]'), true) ?? [];
                if (in_array($data['code'], $codes, true)) {
                    $verified = true;
                    $remaining = array_values(array_diff($codes, [$data['code']]));
                    $user->update(['two_factor_recovery_codes' => Crypt::encryptString(json_encode($remaining))]);
                }
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['code' => 'Your 2FA setup is not ready yet. Please sign in again and refresh the code.']);
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'That code is invalid, expired, or too old. Please use the current code.']);
        }

        Auth::login($user, session('2fa.remember', false));
        $request->session()->forget(['2fa.user_id', '2fa.remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
