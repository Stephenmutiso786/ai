<?php
namespace Tests\Feature;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    public function test_health_endpoint_must_exist_and_not_404(): void
    {
        $response = $this->get('/health');
        $response->assertOk();
    }

    public function test_live_trading_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('live_trading.enabled', false));
    }
}
