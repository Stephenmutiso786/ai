<?php

namespace App\Services\Auth;

class TotpAuthenticator
{
    protected int $period = 30;
    protected int $digits = 6;

    public function generateSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public function provisioningUri(string $secret, string $email, string $issuer = 'STETECH AI'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            $this->digits,
            $this->period
        );
    }

    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $currentStep = (int) floor(time() / $this->period);

        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->codeAt($secret, $currentStep + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn () => strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4))))
            ->all();
    }

    protected function codeAt(string $secret, int $step): string
    {
        $key = $this->base32Decode($secret);
        $binaryStep = pack('N*', 0, $step);
        $hash = hash_hmac('sha1', $binaryStep, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($truncated % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }

    protected function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $bits = '';

        foreach (str_split($input) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }

            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}

