from datetime import datetime, timezone
from typing import Iterable

class DataQualityError(ValueError):
    pass

def validate_candles(candles: Iterable[dict], max_gap_factor: float = 3.0) -> list[dict]:
    rows = list(candles)
    if not rows:
        raise DataQualityError('No candles supplied')
    normalized=[]
    for i,c in enumerate(rows):
        try:
            ts = c.get('time') or c.get('timestamp')
            dt = datetime.fromisoformat(ts.replace('Z','+00:00')) if isinstance(ts,str) else None
            o,h,l,cl = map(float,(c['open'],c['high'],c['low'],c['close']))
        except Exception as e:
            raise DataQualityError(f'Invalid candle at index {i}: {e}')
        if min(o,h,l,cl) <= 0 or h < max(o,cl,l) or l > min(o,cl,h):
            raise DataQualityError(f'Invalid OHLC range at index {i}')
        normalized.append({'time':dt.astimezone(timezone.utc).isoformat(),'open':o,'high':h,'low':l,'close':cl,'volume':float(c.get('volume',0) or 0)})
    times=[datetime.fromisoformat(r['time']) for r in normalized]
    if any(times[i] <= times[i-1] for i in range(1,len(times))):
        raise DataQualityError('Candles must be strictly increasing without duplicates')
    return normalized
