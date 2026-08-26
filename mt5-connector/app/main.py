import os
from typing import Any

from fastapi import Depends, FastAPI, HTTPException
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel, Field
try:
    import MetaTrader5 as mt5
except Exception as e:
    mt5 = None

app=FastAPI(title='STETECH MT5 Connector', version='1.0.0')
security=HTTPBearer()
TOKEN=os.getenv('STETECH_CONNECTOR_TOKEN','')
MAGIC=int(os.getenv('STETECH_MAGIC','20260820'))

def auth(c:HTTPAuthorizationCredentials=Depends(security)):
    if not TOKEN or c.credentials!=TOKEN: raise HTTPException(401,'Unauthorized')

def ensure():
    if mt5 is None: raise HTTPException(503,'MetaTrader5 package/terminal unavailable')
    if not mt5.initialize(): raise HTTPException(503, str(mt5.last_error()))
    terminal = mt5.terminal_info()
    if terminal is None:
        raise HTTPException(503, str(mt5.last_error()))

def _require_tick(symbol: str):
    tick = mt5.symbol_info_tick(symbol)
    if tick is None:
        raise HTTPException(503, f'No live tick available for {symbol}')
    return tick

def _ensure_symbol(symbol: str):
    info = mt5.symbol_info(symbol)
    if info is None:
        raise HTTPException(422, 'Unknown symbol')
    if not info.visible and not mt5.symbol_select(symbol, True):
        raise HTTPException(503, f'Unable to select symbol {symbol}')
    return info

def _normalize_position(position: Any) -> dict:
    return {
        'id': str(position.ticket),
        'position_id': str(position.ticket),
        'symbol': position.symbol,
        'side': 'buy' if position.type == mt5.POSITION_TYPE_BUY else 'sell',
        'volume': position.volume,
        'price_open': position.price_open,
        'price_current': position.price_current,
        'sl': position.sl,
        'tp': position.tp,
        'profit': position.profit,
        'swap': position.swap,
        'comment': position.comment,
        'magic': position.magic,
        'opened_at': getattr(position, 'time', None),
    }

def _symbol_spec(info: Any) -> dict:
    return {
        'symbol': info.name,
        'description': getattr(info, 'description', None),
        'path': getattr(info, 'path', None),
        'digits': info.digits,
        'spread': getattr(info, 'spread', None),
        'trade_mode': getattr(info, 'trade_mode', None),
        'contract_size': getattr(info, 'trade_contract_size', None),
        'min_lot': getattr(info, 'volume_min', None),
        'max_lot': getattr(info, 'volume_max', None),
        'lot_step': getattr(info, 'volume_step', None),
        'tick_size': getattr(info, 'trade_tick_size', None),
        'tick_value': getattr(info, 'trade_tick_value', None),
        'margin_initial': getattr(info, 'margin_initial', None),
        'margin_maintenance': getattr(info, 'margin_maintenance', None),
        'raw': info._asdict() if hasattr(info, '_asdict') else None,
    }

class Order(BaseModel):
    symbol:str
    side:str
    volume:float=Field(gt=0)
    stop_loss:float|None=None
    take_profit:float|None=None

@app.get('/health')
def health(_:None=Depends(auth)):
    ensure()
    terminal = mt5.terminal_info()
    return {'status':'ok','terminal':terminal.path, 'connected': terminal.connected}

@app.get('/account')
def account(_:None=Depends(auth)):
    ensure(); a=mt5.account_info()
    if a is None: raise HTTPException(503, mt5.last_error())
    positions = mt5.positions_get() or []
    return {
        'balance': a.balance,
        'equity': a.equity,
        'margin_available': a.margin_free,
        'currency': a.currency,
        'login': str(a.login),
        'server': a.server,
        'positions': [_normalize_position(p) for p in positions],
    }

@app.get('/symbols')
def symbols(_:None=Depends(auth)):
    ensure()
    items = mt5.symbols_get() or []
    return {'symbols': [item.name for item in items if getattr(item, 'name', None)]}

@app.get('/symbols/{symbol}/specification')
def symbol_specification(symbol: str, _:None=Depends(auth)):
    ensure()
    info = _ensure_symbol(symbol)
    return _symbol_spec(info)

@app.get('/positions')
def positions(_:None=Depends(auth)):
    ensure()
    positions = mt5.positions_get() or []
    return {'positions': [_normalize_position(p) for p in positions]}

@app.post('/orders')
def order(p:Order,_:None=Depends(auth)):
    ensure()
    _ensure_symbol(p.symbol)
    tick = _require_tick(p.symbol)
    side = p.side.lower()
    if side not in {'buy', 'sell'}:
        raise HTTPException(422, 'side must be buy or sell')
    typ=mt5.ORDER_TYPE_BUY if side=='buy' else mt5.ORDER_TYPE_SELL
    price=tick.ask if typ==mt5.ORDER_TYPE_BUY else tick.bid
    req={'action':mt5.TRADE_ACTION_DEAL,'symbol':p.symbol,'volume':p.volume,'type':typ,'price':price,'deviation':20,'magic':MAGIC,'comment':'STETECH AI'}
    if p.stop_loss is not None: req['sl']=p.stop_loss
    if p.take_profit is not None: req['tp']=p.take_profit
    res=mt5.order_send(req)
    if res is None or res.retcode not in (mt5.TRADE_RETCODE_DONE,mt5.TRADE_RETCODE_PLACED): raise HTTPException(502, str(res))
    position_id = str(getattr(res, 'position', '') or getattr(res, 'order', ''))
    return {'order_id':str(res.order),'deal_id':str(res.deal),'position_id':position_id,'retcode':res.retcode,'comment':res.comment}

@app.post('/positions/{ticket}/close')
def close(ticket:int,_:None=Depends(auth)):
    ensure(); ps=mt5.positions_get(ticket=ticket)
    if not ps: raise HTTPException(404,'Position not found')
    p=ps[0]; tick=_require_tick(p.symbol); typ=mt5.ORDER_TYPE_SELL if p.type==mt5.POSITION_TYPE_BUY else mt5.ORDER_TYPE_BUY
    price=tick.bid if typ==mt5.ORDER_TYPE_SELL else tick.ask
    r=mt5.order_send({'action':mt5.TRADE_ACTION_DEAL,'position':p.ticket,'symbol':p.symbol,'volume':p.volume,'type':typ,'price':price,'deviation':20,'magic':MAGIC,'comment':'STETECH CLOSE'})
    if r is None or r.retcode!=mt5.TRADE_RETCODE_DONE: raise HTTPException(502,str(r))
    return {'closed':str(p.ticket),'order_id':str(r.order),'deal_id':str(r.deal)}

@app.post('/positions/close-all')
def close_all(_:None=Depends(auth)):
    ensure(); positions=mt5.positions_get() or []; results=[]
    for p in positions:
        try: results.append(close(int(p.ticket)))
        except HTTPException as e: results.append({'position':str(p.ticket),'error':e.detail})
    return {'results':results}
