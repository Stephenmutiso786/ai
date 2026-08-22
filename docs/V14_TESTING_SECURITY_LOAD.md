# Version 14 — Testing, Security & Load Validation

## Test gates
- `php artisan test`
- PHP syntax lint for application files
- Python compile validation for AI service
- secret scanning before deployment
- dependency vulnerability scanning should run in CI/CD (`composer audit`, `pip-audit`)

## Load testing
Use k6 against staging only. Never stress a live broker or production execution endpoint. Start with the health/read-only APIs, then authenticated dashboards, then isolated queue workloads.

## Security gates
- 2FA for privileged roles before production enablement
- encrypted settings and broker credentials
- least-privilege service accounts
- webhook signature verification
- HMAC internal callbacks with replay protection
- idempotency for payment/execution actions
- rate limits and WAF at the edge
- external penetration test before unrestricted client live trading

## Release gate
No release to live trading unless tests pass, migrations are reviewed, backups are restorable, monitoring is healthy, and rollback is tested.
