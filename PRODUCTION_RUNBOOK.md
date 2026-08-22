# STETECH Production Runbook

## Required secrets
Set unique production values for APP_KEY, DB_PASSWORD, AI service token, market-data keys, broker credentials and payment webhooks. Never commit `.env`.

## Start
1. Copy `.env.example` to `.env` and set real secrets.
2. Run `docker compose up -d --build`.
3. Run `docker compose exec app php artisan migrate --force`.
4. Configure TLS/reverse proxy (for example Nginx) before exposing the application.

## Workers
`app`, `queue`, and `scheduler` are separate production processes. Do not run trading/reconciliation work inside a web request.

## Safety
Keep global trading disabled until broker reconciliation, market data, model deployment, risk checks, and execution monitoring are all healthy. On service degradation, fail closed and disable new execution.

## Backups
Take automated encrypted PostgreSQL backups and test restores regularly. Preserve audit logs and model/version metadata.

## Monitoring
Monitor HTTP health, queue depth/failures, scheduler heartbeat, database, Redis, AI service, broker connector availability, rejected orders, reconciliation mismatches, and emergency-stop state.
