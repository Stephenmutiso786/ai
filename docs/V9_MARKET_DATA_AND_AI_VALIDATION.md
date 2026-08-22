# Version 9: Market Data and AI Validation

This version adds provider-failover primitives, strict OHLCV quality validation, and explicit model promotion gates. Production configuration must still provide licensed market-data credentials and storage infrastructure. A model cannot be promoted solely because it has been trained; it must pass defined quantitative validation rules and then complete the configured paper/shadow process.

Recommended production jobs:
- continuous candle ingestion per instrument/timeframe
- historical backfill before training
- quality checks before persistence and inference
- provider health/failover monitoring
- periodic model performance and drift review
