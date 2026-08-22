# STETECH AI

A Laravel scaffold for a trading **automation tool** (not a broker): clients connect
their own MT5/broker account, an AI signal engine proposes trades, a risk engine
approves or rejects them, and — once you've proven a strategy — an execution
engine can place the approved trade. This repo gives you the full app shell
working end-to-end in **paper mode**, with the live-money path clearly isolated
and left unimplemented on purpose (see "What's intentionally not built" below).

## What's here

- **Marketing site** (`resources/views/marketing/`) — landing page + pricing.
- **Client dashboard** (`resources/views/dashboard/`) — account summary, AI market
  status panel, risk profile, broker connection form, open (paper) trades.
- **Admin control center** (`resources/views/admin/`) — fleet stats, recent trades,
  a global "emergency stop all" kill switch.
- **Domain models** — users, subscription plans/subscriptions, broker accounts,
  instruments, AI signals, risk profiles, trades, audit logs.
- **`RiskEngine`** (`app/Services/RiskEngine.php`) — the only component allowed
  to approve a trade. Checks the halt flag, open-position count, daily loss,
  and signal confidence before returning an approve/reject decision with a reason.
- **`ExecutionEngine`** (`app/Services/Execution/ExecutionEngine.php`) — the only
  component allowed to open a trade. Ships hard-wired to paper mode.
- **`SignalEngine`** (`app/Services/Signals/SignalEngine.php`) — a placeholder
  generator so the rest of the app has real data to display. It is randomized
  and has **no statistical edge** — swap it for real technical/ML models.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# set BROKER_CREDENTIAL_CIPHER_KEY to a separate random 32-byte key, e.g.:
php artisan tinker --execute="echo base64_encode(random_bytes(32));"
php artisan migrate --seed
php artisan serve
```

Default admin login is seeded as `admin@stetech.ai` / `change-me-immediately` —
change that password immediately, and wire in real authentication before any
of this is public (see `routes/auth.php`).

## Plans, usage limits & custom packages

Prices are now denominated in USD (the currency layer converts from there):

| Plan | Price | Runs/week |
|---|---|---|
| Demo | Free | 1 run, ever — per account **and** per device |
| Basic | $9/week | 6 |
| Standard | $15/week | 12 |
| Pro | *unspecified — you didn't give me a price* | Unlimited |

**Pro's price wasn't specified in the request**, so it's seeded as `null`
("Contact us" on the pricing page) and is editable at `/admin/plans` — set
it there rather than in code.

A **"run"** = one click of "Run AI analysis" on the client dashboard, which
regenerates signals across the active instrument list. `App\Services\Usage\RunLimiter`
is the single gate for this — it checks the plan/subscription limit, records
the run in `usage_runs`, and resets the weekly counter on a rolling 7-day
window per subscription.

**On the Demo device limit — an important technical clarification:** no
website, including this one, can read a visitor's MAC address. Browsers
never expose it to any site, by design (this is a web-platform security
boundary, not something PHP or Laravel can work around). What's actually
implemented in `App\Services\Usage\DeviceFingerprint` is a combination of:
a long-lived signed HttpOnly cookie, the visitor's IP, and their User-Agent
string. That's sticky enough to stop casual bypassing (new tab, new account,
same browser) but not a hard guarantee — a different browser or private
mode will look like a new device. If you need a stronger guarantee, gate
the demo run behind phone-number verification via the SMS key you set in
Admin → Settings, rather than device signals alone.

**Custom packages** are a real request → review → provision workflow, not
a self-serve tier:
1. Client submits `/custom-package` with what they need.
2. Admin reviews it at `/admin/custom-requests` and sets a price + runs/week.
3. Approving it creates (or updates) that client's `Subscription` with
   `override_price_usd_weekly` / `override_runs_per_week`, which
   `Subscription::effectivePriceUsdWeekly()` / `effectiveRunsPerWeek()`
   read in place of the plan defaults everywhere else in the app.

## Admin-managed API keys

`/admin/settings` is the single place every integration credential lives —
FX rates, geo-IP, market data, news/economic calendar, the MT5 bridge,
M-Pesa/Stripe, email/SMS, and an optional LLM key for trade explanations
(full list in `config/integrations.php`). Values are encrypted at rest via
`App\Models\Setting` (Laravel's `Crypt` facade, keyed off `APP_KEY`) and
read anywhere in the app with the `setting('key')` helper instead of
`env()` — so pasting a key into the admin UI takes effect immediately
(subject to a 10-minute cache), with no code change or redeploy.

Two integrations are already wired to actually use whatever an admin
saves: `CountryDetector` (optional paid geo-IP provider) and
`CurrencyConverter::refreshRates()` (FX rate provider). The rest —
market data, news, M-Pesa, Stripe, email/SMS — have their key slots ready
in Settings but no client code calling them yet; wire each one up as you
build that feature, reading its key the same way.

I can't verify any of this actually reaches a given provider successfully
from here — no network access in this environment, and no real keys to
test with. The storage, encryption, and retrieval are real and functional;
the "does STETECH successfully talk to exchangerate.host" part is on you
to confirm once you paste in real credentials.

## Currency localization

Prices are stored once in the DB in KES (`price_kes_weekly`) and converted for
display based on the visitor's detected country:

- `App\Services\Currency\CountryDetector` looks up the visitor's country from
  their IP (via ip-api.com's free tier by default — swap in MaxMind GeoLite2
  for high-traffic production use, since that's a local lookup with no
  per-request rate limit).
- `App\Services\Currency\CurrencyConverter` maps country → currency
  (`config/currency.php`) and converts/formats the KES base price.
- `App\Http\Middleware\DetectCurrency` runs once per session, stores the
  result, and **never overwrites a manual choice** — every page has a small
  currency dropdown so a visitor can override a wrong guess.
- Exchange rates in `config/currency.php` are static and will drift. Pick a
  live FX provider and fill in `CurrencyConverter::refreshRates()`; it's
  already scheduled to run every 6 hours in `routes/console.php`, just not
  pointed at a real API yet.

Extend `config/currency.php`'s `country_currency` map as you add markets —
anything not listed falls back to USD.

## What's intentionally not built yet

1. **Real authentication.** `routes/auth.php` deliberately doesn't hand-roll
   login/registration/2FA. Install Fortify or Breeze and add 2FA before this
   holds anyone's broker connection.
2. **A live broker adapter.** `BrokerAdapterInterface` defines the contract;
   nothing implements it. `ExecutionEngine::placeLive()` throws on purpose.
   Building the MT5 bridge (or cTrader, etc.), storing tokens instead of
   passwords, and testing it against a demo account is real, separate work —
   it shouldn't be rubber-stamped in alongside the UI.
3. **A real signal engine.** `SignalEngine` is randomized output, not a
   strategy. Replace it with actual technical/ML models and run it through
   backtesting → walk-forward → out-of-sample → paper trading, in that order,
   before any signal it produces is allowed near `BROKER_EXECUTION_MODE=live`.
4. **Billing.** Subscription/plan tables exist; no payment provider is wired in.
5. **Compliance review specific to automated execution on client accounts.**
   You've noted STETECH is already licensed — worth confirming with whoever
   handles that licensing that it covers *this* code path (automated order
   placement on client-held accounts), not just signal delivery, before
   flipping `BROKER_EXECUTION_MODE` away from `paper`.

None of this is busywork for its own sake — it's the actual sequence the
architecture notes this project started from laid out: brain, then
infrastructure, then platform, then live money, in that order.

## Production AI Lab architecture

This build includes a Super Admin AI Lab with persistent datasets, model registry, versioning, training runs and backtest jobs. Heavy AI work is delegated to the isolated `ai-service/` boundary instead of running inside Laravel web requests. Configure `ai_service_url` and `ai_service_token` through Admin → Settings before dispatching to a real AI worker.

Model promotion is intentionally governed as a lifecycle: draft → training → trained → validating → paper → shadow → approved/live → retired. A live model should only be promoted after documented validation, paper testing and shadow testing.

## Production AI Service v2
The `ai-service` now contains a real, isolated Python pipeline for OHLCV validation, feature engineering, supervised model training, model artifacts, inference, and historical backtesting. It intentionally remains isolated from broker execution. Before production use, connect market-data ingestion to licensed sources, run training/backtests asynchronously via a durable queue, store artifacts in managed object storage, and complete independent validation before any live deployment.

## Production operations added in V5
This release adds queued broker reconciliation, symbol normalization, closed-position reconciliation and scheduler hooks. Production requires a queue worker, scheduler, HTTPS/TLS, persistent Redis/database queues, central log/metrics collection and per-broker credential onboarding. Never enable live execution merely by setting a config flag; verify licensing, broker authorization, risk limits and a real connected account first.

## V6 production signal path
V6 removes randomized signal generation. A signal now requires a `live` AI model, authenticated Python AI service, configured market-data provider, live provider credentials, and a model artifact. The Python service fetches current market data, computes the same feature pipeline used for training, performs model inference, and returns an auditable signal with ATR-derived protective levels. Risk sizing now requires a connected broker account and live account equity; execution is rejected if these prerequisites are unavailable.

## V8 production hardening
This release adds signed internal callbacks, replay-window validation, idempotency storage for external side effects, execution-event persistence, fail-closed signal risk-level generation, and dependency-aware health reporting. Live trading remains explicitly disabled by default via `LIVE_TRADING_ENABLED=false`.

## Version 9 additions
- Market-data provider failover primitives
- Strict OHLCV validation
- Market-data ingestion tracking migration
- AI model quantitative validation gates
- AI validation persistence migration


## Provider credentials
All AI-service and market-data credentials are entered by the Super Admin in **Admin → Settings → API keys & integrations**. Values are encrypted at rest. The Laravel core retrieves the selected provider credentials only when required and passes them over the authenticated internal AI-service request; the Python service does not require market-data API keys to be hardcoded into source files.

## Version 13 operations
The platform includes a Super Admin **Operations** area, persistent health checks/incidents, webhook/email alerting, and PostgreSQL backup/restore scripts. Configure alert destinations through **Admin → Settings → Operations & alerting**. Production backups must be copied to independent access-controlled storage and restore-tested regularly.

# V15
- Added Broker Certification & Trading Operations Center.
- Read-only broker certification, contract metadata checks, symbol discovery, position reads, and execution failure operations queue.

## V16 Trading Workspace
The production workspace reads STETECH-owned normalized candle data from `market_data_candles`; it does not use TradingView market data for AI or execution. The browser chart uses Lightweight Charts for visualization. Populate this table through the production market-data ingestion service, then run `php artisan migrate`.
