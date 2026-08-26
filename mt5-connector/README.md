# STETECH MT5 Connector

Run this on a Windows host where the official MetaTrader 5 terminal is installed and logged into the client-authorized account.

## What it exposes

- `GET /health`
- `GET /account`
- `GET /symbols`
- `GET /symbols/{symbol}/specification`
- `GET /positions`
- `POST /orders`
- `POST /positions/{ticket}/close`
- `POST /positions/close-all`

## Setup

1. Install Python dependencies:
   `pip install -r requirements.txt`
2. Set `STETECH_CONNECTOR_TOKEN` to a long random secret.
3. Optionally set `STETECH_MAGIC` if you want a custom MT5 magic number.
4. Start the service:
   `uvicorn app.main:app --host 0.0.0.0 --port 8100`

## Laravel pairing

Store the connector URL and token in Admin -> Settings under:

- `mt5_bridge_url`
- `mt5_bridge_token`

The Laravel MT5 adapter will use account-level credentials first and fall back to those settings if present.
