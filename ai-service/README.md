# STETECH AI Service

Production AI/data service. Configure real provider credentials via environment or a secret manager; never commit keys.

Supported real providers in this build:
- OANDA v20 REST: candles (and Laravel OANDA adapter for account/order execution)
- Twelve Data REST: market candles

Run:
`uvicorn main:app --host 0.0.0.0 --port 8001`

All internal endpoints except `/health` require `AI_SERVICE_TOKEN`.
