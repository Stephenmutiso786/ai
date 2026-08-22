# V15 Broker Certification & Trading Operations Center

## What is implemented
- Admin broker certification workflow.
- Read-only certification checks: authenticated connection, account snapshot, symbol discovery, contract specification retrieval and open-position retrieval.
- Certification history persisted per broker account.
- Failed execution queue model for operational review.
- Admin resolution workflow for investigated execution failures.

## Safety
Certification intentionally does not place a real order. Real order-path certification must be performed using a broker-approved test account or a dedicated operator-approved certification workflow because an automated certification order on a client account is unsafe.

## Go-live procedure
1. Connect a non-client broker test account.
2. Run certification.
3. Verify symbol and contract metadata.
4. Execute an operator-approved minimal test order.
5. Verify reconciliation and closure.
6. Mark the connector certified in the operational register.
7. Only then allow the broker/platform combination for eligible client accounts.
