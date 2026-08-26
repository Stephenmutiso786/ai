<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    public function test_mt5_bridge_settings_are_listed_in_the_admin_integration_config(): void
    {
        $groups = config('integrations');

        $this->assertArrayHasKey('Broker / execution', $groups);
        $this->assertArrayHasKey('mt5_bridge_url', $groups['Broker / execution']);
        $this->assertArrayHasKey('mt5_bridge_token', $groups['Broker / execution']);
    }
}
