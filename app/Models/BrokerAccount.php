<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerAccount extends Model
{
    protected $fillable = [
        'user_id', 'broker', 'platform', 'server', 'account_number',
        'trading_mode', 'connection_status', 'connected_at',
    ];

    protected $hidden = ['credential_payload_encrypted'];

    protected function casts(): array
    {
        return ['connected_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Store a credential payload encrypted at rest, using the dedicated
     * BROKER_CREDENTIAL_CIPHER_KEY rather than the app's general APP_KEY.
     * The payload should come from the broker's own supported API/token
     * exchange \u2014 never a raw account password captured by STETECH.
     */
    public function credentialPayload(): array
    {
        if (! $this->credential_payload_encrypted) return [];
        return json_decode(\Illuminate\Support\Facades\Crypt::decryptString($this->credential_payload_encrypted), true) ?: [];
    }

    public function setCredentialPayload(array $payload): void
    {
        $key = config('services.broker_credential_cipher_key');

        $this->credential_payload_encrypted = \Illuminate\Support\Facades\Crypt::encryptString(
            json_encode($payload)
        );
    }
}
