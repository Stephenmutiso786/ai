<?php

/*
 * Every credential the platform can use, grouped by integration.
 * Admin -> Settings renders one masked input per key below. Values are
 * saved encrypted via App\Models\Setting and read anywhere in the app
 * with the setting('key') helper -- nothing here is hardcoded into code,
 * and pasting a new value in the admin UI takes effect immediately
 * (subject to the 10-minute settings cache).
 */

return [

    'Currency / FX' => [
        'fx_provider' => ['label' => 'FX rate provider name', 'help' => 'e.g. exchangerate.host, Open Exchange Rates, Fixer'],
        'fx_api_key' => ['label' => 'FX rate provider API key', 'help' => 'Used by the scheduled currency-refresh job'],
    ],

    'Geolocation' => [
        'geoip_provider' => ['label' => 'Geo-IP provider name', 'help' => 'Leave blank to keep using the free ip-api.com lookup'],
        'geoip_api_key' => ['label' => 'Geo-IP provider API key', 'help' => 'Optional -- only needed for a paid provider'],
    ],

    'STETECH AI service' => [
        'ai_service_url' => ['label' => 'AI service URL', 'help' => 'Private/internal URL of the STETECH Python AI service'],
        'ai_service_token' => ['label' => 'AI service access token', 'secret' => true, 'help' => 'Bearer token used by Laravel to authenticate to the AI service'],
    ],

    'Market data' => [
        'ai_market_data_provider' => ['label' => 'Active AI market-data provider', 'help' => 'Choose the provider name used by live AI analysis: oanda or twelve_data'],
        'market_data_provider' => ['label' => 'General market-data provider', 'help' => 'Optional provider name for ingestion jobs'],
        'market_data_api_key' => ['label' => 'General market-data API key', 'secret' => true],
        'twelve_data_api_key' => ['label' => 'Twelve Data API key', 'secret' => true, 'help' => 'Used only when ai_market_data_provider is twelve_data'],
        'oanda_api_token' => ['label' => 'OANDA API token', 'secret' => true, 'help' => 'Used for OANDA candles and OANDA execution'],
        'oanda_account_id' => ['label' => 'OANDA account ID', 'secret' => true, 'help' => 'The OANDA account used by the configured OANDA integration'],
        'oanda_api_url' => ['label' => 'OANDA API base URL', 'help' => 'Example: https://api-fxtrade.oanda.com or https://api-fxpractice.oanda.com'],
    ],

    'TradingView workspace' => [
        'tradingview_exchange' => ['label' => 'TradingView exchange code', 'help' => 'Example: OANDA, FX, FOREXCOM'],
        'tradingview_symbol_prefix' => ['label' => 'TradingView symbol prefix', 'help' => 'Optional prefix for broker symbols, e.g. EURUSD or FX:'],
    ],

    'News & economic calendar' => [
        'news_api_key' => ['label' => 'News / sentiment API key', 'help' => 'e.g. Finnhub, NewsAPI'],
        'economic_calendar_api_key' => ['label' => 'Economic calendar API key', 'help' => 'e.g. Trading Economics, Finnhub'],
    ],

    'Broker / execution' => [
        'mt5_bridge_url' => ['label' => 'MT5 bridge URL', 'help' => 'Private HTTPS URL of the Windows host running the MT5 connector'],
        'mt5_bridge_token' => ['label' => 'MT5 bridge auth token', 'secret' => true, 'help' => 'Bearer token shared only with the broker connector'],
    ],

    'Payments' => [
        'mpesa_consumer_key' => ['label' => 'M-Pesa Daraja consumer key'],
        'mpesa_consumer_secret' => ['label' => 'M-Pesa Daraja consumer secret'],
        'mpesa_shortcode' => ['label' => 'M-Pesa shortcode'],
        'mpesa_passkey' => ['label' => 'M-Pesa passkey'],
        'stripe_secret_key' => ['label' => 'Stripe secret key', 'secret' => true, 'help' => 'For international card payments'],
        'stripe_webhook_secret' => ['label' => 'Stripe webhook signing secret', 'secret' => true],
        'mpesa_base_url' => ['label' => 'M-Pesa API base URL', 'help' => 'Production: https://api.safaricom.co.ke'],
        'payment_usd_to_kes_rate' => ['label' => 'USD to KES billing rate', 'help' => 'Used only to quote KES M-Pesa amounts from USD weekly plan prices'],
    ],

    'Notifications' => [
        'email_api_key' => ['label' => 'Transactional email API key', 'help' => 'e.g. Resend, SendGrid'],
        'sms_api_key' => ['label' => 'SMS / WhatsApp API key', 'help' => "e.g. Africa's Talking, Twilio"],
    ],

    'Operations & alerting' => [
        'ops_alert_webhook_url' => ['label' => 'Operations alert webhook URL', 'secret' => true, 'help' => 'Optional Slack/Teams/custom incident webhook'],
        'ops_alert_email' => ['label' => 'Operations alert email', 'help' => 'Critical alerts are also sent here using the configured mail transport'],
        'backup_storage_url' => ['label' => 'Backup storage destination', 'help' => 'Use a secure provider-managed backup destination; never expose credentials in logs'],
    ],

    'AI assistant (optional)' => [
        'openai_api_key' => ['label' => 'LLM explanation-assistant API key', 'help' => 'Only for plain-language trade explanations -- never for execution decisions'],
    ],

];
