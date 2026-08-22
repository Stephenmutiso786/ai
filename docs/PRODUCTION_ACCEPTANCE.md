# Production acceptance checklist

The system must not be marked ready for client live trading until all items are evidenced in the deployment record.

- [ ] Real broker sandbox/demo integration tests pass for every supported adapter.
- [ ] Live trading is disabled by default and enabled per environment through an explicit production secret.
- [ ] Broker credentials are encrypted at rest and rotated.
- [ ] AI callbacks are HMAC signed and replay-protected.
- [ ] Every execution request has an idempotency key.
- [ ] Execution events are persisted and reconciled against broker state.
- [ ] Redis queue failures, dead jobs, database health and AI service health alert an operator.
- [ ] Backup restore has been tested, not merely configured.
- [ ] A model cannot deploy without validation evidence and approval.
- [ ] Model/data version and trade decision are auditable.
- [ ] Emergency stop is tested against every supported broker adapter.
- [ ] Payment, legal, privacy and regulatory obligations have been reviewed for each operating jurisdiction.

Passing this checklist still does not guarantee profitability or regulatory authorization.
