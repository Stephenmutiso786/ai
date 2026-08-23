<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\AiModel;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed the platform super admin directly so it bypasses the
        // client-only demo subscription hook in User::booted.
        User::updateOrCreate(
            ['email' => 'stephemutiso19@gmail.com'],
            [
                'name' => 'Stephen Mutiso',
                'password' => Hash::make('2006@shawn_Mutiso'),
                'role' => 'admin',
                'is_super_admin' => true,
                'kyc_status' => 'verified',
            ]
        );

        SubscriptionPlan::where('slug', 'demo')->delete();

        foreach ([
            ['name' => 'Basic', 'slug' => 'basic', 'price_usd_weekly' => 9, 'runs_per_week' => 6, 'total_runs_lifetime' => null, 'automation_allowed' => false, 'broker_connections_limit' => 1],
            ['name' => 'Standard', 'slug' => 'standard', 'price_usd_weekly' => 15, 'runs_per_week' => 12, 'total_runs_lifetime' => null, 'automation_allowed' => true, 'broker_connections_limit' => 4],
            // Pro's price wasn't specified -- seeded null ("Contact us") and
            // editable from Admin > Plans rather than guessed permanently.
            ['name' => 'Pro', 'slug' => 'pro', 'price_usd_weekly' => null, 'runs_per_week' => null, 'total_runs_lifetime' => null, 'automation_allowed' => true, 'broker_connections_limit' => 10],
        ] as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }

        // Custom is a hidden template row -- never assigned directly, only
        // used as the vehicle for admin-approved custom-package terms.
        SubscriptionPlan::firstOrCreate(
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

        AiModel::firstOrCreate(
            ['name' => 'STETECH Core'],
            [
                'version' => '1.0.0',
                'framework' => 'fallback',
                'status' => 'live',
                'notes' => 'Seeded live model so AI analysis always has a deployment target.',
            ]
        );
    }
}
