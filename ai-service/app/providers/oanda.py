import httpx
class OandaProvider:
    def __init__(self, config=None):
        config=config or {}
        self.token=config.get('api_token')
        self.account=config.get('account_id')
        self.base=config.get('base_url','https://api-fxtrade.oanda.com').rstrip('/')
    def configured(self): return bool(self.token and self.account)
    async def candles(self, instrument, granularity='H1', count=500):
        if not self.configured(): raise RuntimeError('OANDA credentials must be supplied by STETECH Super Admin Settings')
        headers={'Authorization':f'Bearer {self.token}'}
        url=f'{self.base}/v3/instruments/{instrument}/candles'
        params={'granularity':granularity,'count':count,'price':'M'}
        async with httpx.AsyncClient(timeout=30) as c:
            r=await c.get(url,headers=headers,params=params); r.raise_for_status(); data=r.json()
        rows=[]
        for x in data.get('candles',[]):
            if not x.get('complete'): continue
            m=x['mid']; rows.append({'timestamp':x['time'],'open':float(m['o']),'high':float(m['h']),'low':float(m['l']),'close':float(m['c']),'volume':float(x.get('volume',0))})
        return rows
