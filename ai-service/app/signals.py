from __future__ import annotations
from typing import Any
import pandas as pd
from .features import make_features
from .modeling import FEATURE_COLUMNS

def build_signal(model_bundle: dict, rows: list[dict[str, Any]], symbol: str, timeframe: str) -> dict[str, Any]:
    raw = pd.DataFrame(rows)
    frame = make_features(raw).dropna().reset_index(drop=True)
    if frame.empty:
        raise ValueError('Not enough validated market history to compute model features')
    latest = frame.iloc[-1]
    x = frame[FEATURE_COLUMNS].tail(1)
    p = float(model_bundle['model'].predict_proba(x)[0, 1])
    direction = 'buy' if p >= 0.55 else ('sell' if p <= 0.45 else 'wait')
    confidence = round(abs(p - 0.5) * 200, 2)
    entry = float(latest['close'])
    atr = float(latest.get('atr_14', 0.0))
    if atr <= 0:
        raise ValueError('ATR is unavailable; refusing to fabricate execution risk levels')
    stop_distance = atr * 1.5
    reward_distance = stop_distance * 2.0
    stop_loss = entry - stop_distance if direction == 'buy' else (entry + stop_distance if direction == 'sell' else None)
    take_profit = entry + reward_distance if direction == 'buy' else (entry - reward_distance if direction == 'sell' else None)
    trend = float(latest.get('trend_gap', 0.0))
    regime = 'trending' if abs(trend) > max(abs(entry) * 0.0002, 1e-8) else 'ranging'
    return {
        'symbol': symbol,
        'timeframe': timeframe,
        'direction': direction,
        'confidence': confidence,
        'probability_up': p,
        'entry': entry if direction != 'wait' else None,
        'stop_loss': stop_loss,
        'take_profit': take_profit,
        'risk_reward': 2.0 if direction != 'wait' else None,
        'market_regime': regime,
        'reasoning': f'Model probability_up={p:.4f}; ATR-based risk levels; regime={regime}.',
    }
