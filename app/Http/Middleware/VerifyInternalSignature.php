<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.ai.internal_callback_secret');
        if ($secret === '') abort(503, 'Internal callback signing is not configured.');
        $timestamp = (string) $request->header('X-STETECH-Timestamp');
        $signature = (string) $request->header('X-STETECH-Signature');
        if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300) abort(401, 'Invalid callback timestamp.');
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (!hash_equals($expected, $signature)) abort(401, 'Invalid callback signature.');
        return $next($request);
    }
}
