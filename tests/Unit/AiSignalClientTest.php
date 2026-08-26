<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Services\AI\AiSignalClient;
use Tests\TestCase;

class AiSignalClientTest extends TestCase
{
    public function test_it_refuses_to_fabricate_signals_when_the_ai_service_is_not_configured(): void
    {
        config()->set('services.broker_credential_cipher_key', 'test-broker-key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI service URL/token are not configured.');

        app(AiSignalClient::class)->liveSignal(new AiModel(), 'EURUSD', 'H1');
    }
}
