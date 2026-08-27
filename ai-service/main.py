import os
from pathlib import Path
import pandas as pd
from fastapi import FastAPI, Depends, HTTPException
from app.schemas import TrainingRun, Backtest, InferenceRequest, LiveSignalRequest
from app.security import require_token
from app.market_data import validate_ohlcv
from app.features import make_features
from app.modeling import train_classifier, FEATURE_COLUMNS
from app.backtesting import run_backtest
from app.signals import build_signal
from app.providers.oanda import OandaProvider
from app.providers.twelvedata import TwelveDataProvider
import joblib

ROOT = Path(__file__).resolve().parent
DATA = ROOT / 'data'; MODELS = ROOT / 'artifacts'
DATA.mkdir(exist_ok=True); MODELS.mkdir(exist_ok=True)
app = FastAPI(title='STETECH AI Service', version='2.0.0')

def dataset_path(dataset_id): return DATA / f'dataset-{dataset_id}.csv'

def model_path(model_id): return MODELS / f'model-{model_id}.joblib'

def latest_model_path():
    candidates = sorted(MODELS.glob('*.joblib'), key=lambda p: p.stat().st_mtime, reverse=True)
    return candidates[0] if candidates else None

def load_bundle(model_id: int):
    path = model_path(model_id)
    if not path.exists():
        path = latest_model_path()
    if not path or not path.exists():
        raise HTTPException(404, 'Model artifact not found')
    return joblib.load(path)

@app.get('/health')
def health(): return {'status':'ok','service':'stetech-ai','version':'2.0.0'}

@app.post('/training-runs', dependencies=[Depends(require_token)])
def training_run(payload: TrainingRun):
    if payload.dataset_id is None: raise HTTPException(422,'dataset_id is required')
    path = dataset_path(payload.dataset_id)
    if not path.exists(): raise HTTPException(404,'Dataset artifact not available to AI service')
    raw = validate_ohlcv(pd.read_csv(path))
    frame = make_features(raw)
    model, metrics = train_classifier(frame, payload.config)
    joblib.dump({'model':model,'features':FEATURE_COLUMNS,'metrics':metrics}, model_path(payload.model_id))
    return {'job_reference':f'train-{payload.run_id}','accepted':True,'status':'completed','metrics':metrics}

@app.post('/backtests', dependencies=[Depends(require_token)])
def backtest(payload: Backtest):
    bundle = load_bundle(payload.model_id)
    dataset_id = payload.config.get('dataset_id')
    if not dataset_id: raise HTTPException(422,'config.dataset_id is required')
    data_path = dataset_path(int(dataset_id))
    if not data_path.exists(): raise HTTPException(404,'Dataset artifact not found')
    frame = make_features(validate_ohlcv(pd.read_csv(data_path)))
    frame['prediction'] = bundle['model'].predict_proba(frame[FEATURE_COLUMNS])[:,1]
    results = run_backtest(frame, payload.config)
    return {'job_reference':f'backtest-{payload.backtest_id}','accepted':True,'status':'completed','results':results}


@app.post('/signals/live', dependencies=[Depends(require_token)])
async def live_signal(payload: LiveSignalRequest):
    bundle = load_bundle(payload.model_id)
    try:
        plan = payload.provider_config.get('plan', {})
        provider = payload.provider.lower()
        if provider == 'oanda': rows = await OandaProvider(payload.provider_config).candles(payload.symbol, payload.timeframe, payload.count)
        elif provider in ('twelve','twelvedata','twelve_data'): rows = await TwelveDataProvider(payload.provider_config).candles(payload.symbol, payload.timeframe, payload.count)
        else: raise HTTPException(422, 'Unsupported market-data provider')
        return build_signal(bundle, rows, payload.symbol, payload.timeframe, plan=plan)
    except HTTPException: raise
    except Exception as e: raise HTTPException(502, str(e))

@app.post('/inference', dependencies=[Depends(require_token)])
def inference(payload: InferenceRequest):
    bundle = load_bundle(payload.model_id)
    missing = [f for f in bundle['features'] if f not in payload.features]
    if missing: raise HTTPException(422, f'Missing features: {missing}')
    row = pd.DataFrame([{f:payload.features[f] for f in bundle['features']}])
    p = float(bundle['model'].predict_proba(row)[0,1])
    direction = 'buy' if p >= .55 else ('sell' if p <= .45 else 'wait')
    return {'symbol':payload.symbol,'timeframe':payload.timeframe,'probability_up':p,'direction':direction,'confidence':round(abs(p-.5)*200,2)}
