# V18 Merge Audit and Hardening

This package is based on the uploaded merged review tree. It is **not** a blind overwrite.

## Reconciled fixes
- Extracted `RiskDecision` into its own PSR-4 file to prevent autoload/test-order coupling.
- Preserved the extended `RiskDecision::approve(lotSize, riskMoney, estimatedMargin)` contract and added validation.
- Risk sizing now reads `margin_available` as a supported broker snapshot field.
- Contract specifications are fetched for the exact signal instrument instead of assuming they exist inside an account snapshot.
- Broker certification methods are now part of `BrokerAdapterInterface`, matching the concrete adapters and certification service.
- Replaced the health test that accepted `200` **or** `404` with a real `200` assertion.
- Added an explicit default-live-trading safety assertion.

## Still requires real commissioning
- Broker adapters must be tested against real supported APIs/bridges.
- MT5 bridge health must verify authenticated trading capability, not only HTTP reachability.
- OANDA/cTrader symbol and volume semantics need broker-account-specific certification.
- Reconciliation must not infer a trade is closed solely because a snapshot omits it; broker history/status lookup is required for definitive closure.
- Full risk-engine tests must cover daily loss, lot step, margin buffer, exposure and fail-closed behavior.
