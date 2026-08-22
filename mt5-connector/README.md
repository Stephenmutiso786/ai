# STETECH MT5 Connector (real execution agent)
Run this on a Windows host where the official MetaTrader 5 terminal is installed and logged into the client-authorized account. Set `STETECH_CONNECTOR_TOKEN` to a long random secret and expose it only through HTTPS/private networking. The central STETECH platform pairs the account using `connector_url` and `connector_token` stored encrypted in `BrokerAccount`.

Install: `pip install -r requirements.txt` then `uvicorn app.main:app --host 0.0.0.0 --port 8100`.
