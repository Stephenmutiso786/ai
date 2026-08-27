<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class BrokerAccount extends Model
{
    protected $fillable = [
        'user_id', 'broker', 'platform', 'server', 'account_number',
        'trading_mode', 'connection_status', 'connected_at', 'verified_at',
        'balance', 'equity', 'margin_available', 'currency',
    ];

    protected $hidden = ['credential_payload_encrypted'];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'balance' => 'decimal:2',
            'equity' => 'decimal:2',
            'margin_available' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->connection_status === 'connected' && ! empty($this->verified_at);
    }

    /**
     * Store a credential payload encrypted at rest, using the dedicated
     * BROKER_CREDENTIAL_CIPHER_KEY rather than the app's general APP_KEY.
     * The payload should come from the broker's own supported API/token
     * exchange \u2014 never a raw account password captured by STETECH.
     */
    public function credentialPayload(): array
    {
        if (! $this->credential_payload_encrypted) {
            return [];
        }

        $raw = $this->credential_payload_encrypted;
        if (str_starts_with($raw, 'v1:')) {
            $raw = substr($raw, 3);
            $decrypted = $this->decryptPayload($raw);

            return json_decode($decrypted, true) ?: [];
        }

        try {
            return json_decode(Crypt::decryptString($raw), true) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function setCredentialPayload(array $payload): void
    {
        $this->credential_payload_encrypted = 'v1:'.$this->encryptPayload(json_encode($payload));
    }

    private function encryptionKey(): string
    {
        $key = (string) config('services.broker_credential_cipher_key');

        if ($key === '') {
            throw new RuntimeException('BROKER_CREDENTIAL_CIPHER_KEY is not configured.');
        }

        return hash('sha256', $key, true);
    }

    private function encryptPayload(string $payload): string
    {
        $cipher = 'AES-256-CBC';
        $iv = random_bytes(openssl_cipher_iv_length($cipher));
        $ciphertext = openssl_encrypt($payload, $cipher, $this->encryptionKey(), OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Unable to encrypt broker credential payload.');
        }

        return base64_encode($iv.$ciphertext);
    }

    private function decryptPayload(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Broker credential payload is invalid.');
        }

        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($decoded, 0, $ivLength);
        $ciphertext = substr($decoded, $ivLength);
        $plaintext = openssl_decrypt($ciphertext, $cipher, $this->encryptionKey(), OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new RuntimeException('Unable to decrypt broker credential payload.');
        }

        return $plaintext;
    }
}
