<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin is created directly (bypasses the client-only Demo
        // auto-subscribe hook in User::booted).
        User::create([
            'name' => 'STETECH Admin',
            'email' => 'admin@stetech.ai',
            'password' => Hash::make('change-me-immediately'),
            'role' => 'admin',
            'kyc_status' => 'verified',
        ]);

        foreach ([
            ['name' => 'Demo', 'slug' => 'demo', 'price_usd_weekly' => null, 'runs_per_week' => null, 'total_runs_lifetime' => 1, 'automation_allowed' => false, 'is_demo' => true],
            ['name' => 'Basic', 'slug' => 'basic', 'price_usd_weekly' => 9, 'runs_per_week' => 6, 'total_runs_lifetime' => null, 'automation_allowed' => false],
            ['name' => 'Standard', 'slug' => 'standard', 'price_usd_weekly' => 15, 'runs_per_week' => 12, 'total_runs_lifetime' => null, 'automation_allowed' => true],
            // Pro's price wasn't specified -- seeded null ("Contact us") and
            // editable from Admin > Plans rather than guessed permanently.
            ['name' => 'Pro', 'slug' => 'pro', 'price_usd_weekly' => null, 'runs_per_week' => null, 'total_runs_lifetime' => null, 'automation_allowed' => true],
        ] as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        // Custom is a hidden template row -- never assigned directly, only
        // used as the vehicle for admin-approved custom-package terms.
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'custom'],
            ['name' => 'Custom', 'is_custom_template' => true, 'is_active' => false]
        );

        foreach ([
            ['symbol' => 'EURUSD', 'category' => 'major'],
            ['symbol' => 'GBPUSD', 'category' => 'major'],
            ['symbol' => 'USDJPY', 'category' => 'major'],
            ['symbol' => 'AUDUSD', 'category' => 'major'],
            ['symbol' => 'GBPJPY', 'category' => 'cross'],
            ['symbol' => 'XAUUSD', 'category' => 'metal'],
        ] as $instrument) {
            Instrument::firstOrCreate(['symbol' => $instrument['symbol']], $instrument);
        }
    }
}
