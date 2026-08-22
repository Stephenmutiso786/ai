import os
from fastapi import FastAPI, Depends, HTTPException
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel, Field
try:
    import MetaTrader5 as mt5
except Exception as e:
    mt5 = None

app=FastAPI(title='STETECH MT5 Connector', version='1.0.0')
security=HTTPBearer()
TOKEN=os.getenv('STETECH_CONNECTOR_TOKEN','')

def auth(c:HTTPAuthorizationCredentials=Depends(security)):
    if not TOKEN or c.credentials!=TOKEN: raise HTTPException(401,'Unauthorized')

def ensure():
    if mt5 is None: raise HTTPException(503,'MetaTrader5 package/terminal unavailable')
    if not mt5.initialize(): raise HTTPException(503, mt5.last_error())

class Order(BaseModel):
    symbol:str
    side:str
    volume:float=Field(gt=0)
    stop_loss:float|None=None
    take_profit:float|None=None

@app.get('/health')
def health(_:None=Depends(auth)):
    ensure(); return {'status':'ok','terminal':mt5.terminal_info().path}

@app.get('/account')
def account(_:None=Depends(auth)):
    ensure(); a=mt5.account_info()
    if a is None: raise HTTPException(503, mt5.last_error())
    return {'balance':a.balance,'equity':a.equity,'margin_available':a.margin_free,'currency':a.currency,'login':str(a.login),'server':a.server}

@app.post('/orders')
def order(p:Order,_:None=Depends(auth)):
    ensure(); info=mt5.symbol_info(p.symbol)
    if info is None: raise HTTPException(422,'Unknown symbol')
    if not info.visible: mt5.symbol_select(p.symbol,True)
    tick=mt5.symbol_info_tick(p.symbol)
    typ=mt5.ORDER_TYPE_BUY if p.side.lower()=='buy' else mt5.ORDER_TYPE_SELL
    price=tick.ask if typ==mt5.ORDER_TYPE_BUY else tick.bid
    req={'action':mt5.TRADE_ACTION_DEAL,'symbol':p.symbol,'volume':p.volume,'type':typ,'price':price,'deviation':20,'magic':int(os.getenv('STETECH_MAGIC','20260820')),'comment':'STETECH AI'}
    if p.stop_loss is not None: req['sl']=p.stop_loss
    if p.take_profit is not None: req['tp']=p.take_profit
    res=mt5.order_send(req)
    if res is None or res.retcode not in (mt5.TRADE_RETCODE_DONE,mt5.TRADE_RETCODE_PLACED): raise HTTPException(502, str(res))
    return {'order_id':str(res.order),'deal_id':str(res.deal),'retcode':res.retcode,'comment':res.comment}

@app.post('/positions/{ticket}/close')
def close(ticket:int,_:None=Depends(auth)):
    ensure(); ps=mt5.positions_get(ticket=ticket)
    if not ps: raise HTTPException(404,'Position not found')
    p=ps[0]; tick=mt5.symbol_info_tick(p.symbol); typ=mt5.ORDER_TYPE_SELL if p.type==mt5.POSITION_TYPE_BUY else mt5.ORDER_TYPE_BUY
    price=tick.bid if typ==mt5.ORDER_TYPE_SELL else tick.ask
    r=mt5.order_send({'action':mt5.TRADE_ACTION_DEAL,'position':p.ticket,'symbol':p.symbol,'volume':p.volume,'type':typ,'price':price,'deviation':20,'magic':int(os.getenv('STETECH_MAGIC','20260820')),'comment':'STETECH CLOSE'})
    if r is None or r.retcode!=mt5.TRADE_RETCODE_DONE: raise HTTPException(502,str(r))
    return {'closed':str(p.ticket),'order_id':str(r.order),'deal_id':str(r.deal)}

@app.post('/positions/close-all')
def close_all(_:None=Depends(auth)):
    ensure(); positions=mt5.positions_get() or []; results=[]
    for p in positions:
        try: results.append(close(int(p.ticket)))
        except HTTPException as e: results.append({'position':str(p.ticket),'error':e.detail})
    return {'results':results}
