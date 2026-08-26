<?php

namespace Tests\Feature;

use App\Models\BrokerAccount;
use App\Services\Execution\Mt5ConnectorAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Mt5ConnectorAdapterTest extends TestCase
{
    public function test_it_uses_a_real_connector_payload_and_can_read_account_snapshot(): void
    {
        config()->set('services.broker_credential_cipher_key', 'test-broker-key');

        $account = new BrokerAccount();
        $account->setCredentialPayload([
            'connector_url' => 'https://connector.example.test',
            'connector_token' => 'secret-token',
        ]);

        Http::fake([
            'https://connector.example.test/*' => Http::response([
                'balance' => 1250.50,
                'equity' => 1280.75,
                'margin_available' => 980.25,
                'currency' => 'USD',
                'login' => '123456',
                'server' => 'MetaQuotes-Demo',
                'positions' => [],
            ], 200),
        ]);

        $snapshot = app(Mt5ConnectorAdapter::class)->accountSnapshot($account);

        $this->assertSame(1250.50, $snapshot['balance']);
        $this->assertSame(1280.75, $snapshot['equity']);
        $this->assertSame('USD', $snapshot['currency']);
    }

    public function test_it_can_encrypt_and_decrypt_the_broker_payload_with_the_dedicated_key(): void
    {
        config()->set('services.broker_credential_cipher_key', 'test-broker-key');

        $account = new BrokerAccount();
        $account->setCredentialPayload([
            'connector_url' => 'https://connector.example.test',
            'connector_token' => 'secret-token',
        ]);

        $this->assertStringStartsWith('v1:', $account->credential_payload_encrypted);

        $decoded = $account->credentialPayload();

        $this->assertSame('https://connector.example.test', $decoded['connector_url']);
        $this->assertSame('secret-token', $decoded['connector_token']);
    }
}
