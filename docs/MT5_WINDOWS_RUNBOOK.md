# MT5 Windows Bridge Runbook

Use this when you want to run the STETECH MT5 connector on a Windows host that already has the official MetaTrader 5 terminal installed and logged in.

## What this bridge does

- Authenticates requests with a bearer token.
- Talks directly to the local MT5 terminal through the `MetaTrader5` Python package.
- Exposes broker state to STETECH through HTTPS or a private network path.
- Lets STETECH read account data, list symbols, inspect contract specs, place market orders, and close positions.

## Requirements

- Windows 10 or Windows 11.
- MetaTrader 5 desktop terminal installed.
- The terminal already logged into the client-authorized trading account.
- Python 3.10+.
- Network access from the STETECH app server to the connector host.

## Folder layout

Example:

```text
C:\stetech-mt5-connector\
  app\
    main.py
  requirements.txt
```

## Install

Open PowerShell in the connector folder and run:

```powershell
py -m venv .venv
.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

If PowerShell blocks script activation, allow it for the current session:

```powershell
Set-ExecutionPolicy -Scope Process RemoteSigned
```

## Environment variables

Set these before starting the service:

```powershell
$env:STETECH_CONNECTOR_TOKEN="replace-with-a-long-random-secret"
$env:STETECH_MAGIC="20260820"
```

Optional if you want a different port:

```powershell
$env:PORT="8100"
```

## Start the connector

From the connector folder:

```powershell
uvicorn app.main:app --host 0.0.0.0 --port 8100
```

Leave the terminal window open while testing.

## Smoke test on the Windows host

Use another PowerShell window and send the bearer token in the header.

```powershell
$token = "replace-with-a-long-random-secret"
Invoke-RestMethod -Headers @{ Authorization = "Bearer $token" } -Uri "http://127.0.0.1:8100/health"
```

Expected result:

```json
{
  "status": "ok",
  "terminal": "...\\terminal64.exe",
  "connected": true
}
```

Then check account data:

```powershell
Invoke-RestMethod -Headers @{ Authorization = "Bearer $token" } -Uri "http://127.0.0.1:8100/account"
```

You should see:

- `balance`
- `equity`
- `margin_available`
- `currency`
- `login`
- `server`
- `positions`

Or run the bundled smoke script from the connector folder:

```powershell
.\smoke-test.ps1 -BaseUrl "http://127.0.0.1:8100" -Token "replace-with-a-long-random-secret"
```

To verify the full read path, run:

```powershell
.\smoke-full.ps1 -BaseUrl "http://127.0.0.1:8100" -Token "replace-with-a-long-random-secret"
```

## STETECH pairing

In STETECH Admin -> Settings, set:

- `mt5_bridge_url` to the connector URL
- `mt5_bridge_token` to the same bearer token

If the account has per-account connector credentials stored, STETECH will use those first. If not, it falls back to the admin settings above.

## STETECH verification flow

1. Open Admin -> Broker Certification.
2. Find the MT5 account row.
3. Click `Test bridge`.
4. Confirm the page shows a success flash message.
5. Click `Run certification`.
6. Verify the certification passes and the snapshot is refreshed.

## Live order smoke test

Only do this on an account you are authorized to trade.

1. Make sure `LIVE_TRADING_ENABLED=true` in the STETECH environment.
2. Make sure `BROKER_EXECUTION_MODE=live`.
3. Ensure the broker account is marked `fully_automatic`.
4. Send a small, intentional order through the app flow.
5. Confirm the trade appears in STETECH and in the MT5 terminal.

## Troubleshooting

- `401 Unauthorized`
  - The bearer token is missing or wrong.
- `503 MetaTrader5 package/terminal unavailable`
  - The `MetaTrader5` Python package is not installed or the terminal is not reachable from the connector process.
- `503` from `/health`
  - The terminal is not initialized, not logged in, or blocked by the local MT5 state.
- `422 Unknown symbol`
  - The symbol is not available on the terminal or the name does not match the broker's symbol exactly.
- `No live tick available`
  - The market is closed, the symbol has no current tick, or the symbol was not selected in MT5.

## Operational notes

- Keep the connector on a trusted network.
- Do not expose the bearer token publicly.
- Do not use the bridge as a substitute for broker compliance, account authorization, or STETECH live-trading approvals.
- Restart the connector after MT5 terminal updates or terminal profile changes.
