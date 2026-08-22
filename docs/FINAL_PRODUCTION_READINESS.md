# Final Production Readiness Gate

This system must not be declared ready for unrestricted live client trading until the deployment operator completes and records:
1. Real broker sandbox/live certification for each supported adapter.
2. Market-data licensing and provider failover tests.
3. End-to-end order placement, rejection, partial-fill and reconciliation tests.
4. Backup restore test from an independent environment.
5. Payment webhook signature and duplicate-delivery tests.
6. Security review and independent penetration test.
7. Load test on staging using realistic traffic.
8. Legal/regulatory review for the jurisdictions and business model actually used.
9. Incident response and emergency stop drill.
10. Explicit Super Admin production approval with `LIVE_TRADING_ENABLED=true` only after all gates pass.
