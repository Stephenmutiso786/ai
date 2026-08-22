import httpx
class TwelveDataProvider:
    def __init__(self, config=None): self.key=(config or {}).get('api_key')
    async def candles(self, symbol, interval='1h', outputsize=500):
        if not self.key: raise RuntimeError('Twelve Data API key must be supplied by STETECH Super Admin Settings')
        async with httpx.AsyncClient(timeout=30) as c:
            r=await c.get('https://api.twelvedata.com/time_series',params={'symbol':symbol,'interval':interval,'outputsize':outputsize,'apikey':self.key}); r.raise_for_status(); d=r.json()
        if d.get('status')=='error': raise RuntimeError(d.get('message','Twelve Data error'))
        return [{'timestamp':x['datetime'],'open':float(x['open']),'high':float(x['high']),'low':float(x['low']),'close':float(x['close']),'volume':float(x.get('volume') or 0)} for x in reversed(d.get('values',[]))]
