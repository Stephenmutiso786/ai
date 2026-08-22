import pandas as pd

def validate_ohlcv(df: pd.DataFrame) -> pd.DataFrame:
    required = {'timestamp','open','high','low','close'}
    missing = required - set(df.columns)
    if missing:
        raise ValueError(f'Missing OHLCV columns: {sorted(missing)}')
    out = df.copy()
    out['timestamp'] = pd.to_datetime(out['timestamp'], utc=True)
    out = out.sort_values('timestamp').drop_duplicates('timestamp')
    for c in ['open','high','low','close']:
        out[c] = pd.to_numeric(out[c], errors='coerce')
    out = out.dropna(subset=['open','high','low','close'])
    if (out['high'] < out['low']).any():
        raise ValueError('Invalid candle: high below low')
    return out.reset_index(drop=True)
