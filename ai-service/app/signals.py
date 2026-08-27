from __future__ import annotations
from typing import Any
import pandas as pd
from .features import make_features
from .modeling import FEATURE_COLUMNS

def build_signal(model_bundle: dict, rows: list[dict[str, Any]], symbol: str, timeframe: str, plan: dict[str, Any] | None = None) -> dict[str, Any]:
    raw = pd.DataFrame(rows)
    frame = make_features(raw).dropna().reset_index(drop=True)
    if frame.empty:
        raise ValueError('Not enough validated market history to compute model features')
    latest = frame.iloc[-1]
    x = frame[FEATURE_COLUMNS].tail(1)
    p = float(model_bundle['model'].predict_proba(x)[0, 1])
    plan = plan or {}
    trading_mode = str(plan.get('trading_mode') or 'signals_only')
    min_confidence = float(plan.get('min_confidence', 60.0))
    entry_threshold = float(plan.get('entry_threshold', 0.55))
    exit_threshold = float(plan.get('exit_threshold', 0.45))
    if trading_mode == 'fully_automatic':
        entry_threshold = max(entry_threshold, 0.60)
        min_confidence = max(min_confidence, 72.0)
    elif trading_mode == 'semi_automatic':
        entry_threshold = max(entry_threshold, 0.57)
        min_confidence = max(min_confidence, 66.0)
    direction = 'buy' if p >= entry_threshold else ('sell' if p <= exit_threshold else 'wait')
    confidence = round(abs(p - 0.5) * 200, 2)
    entry = float(latest['close'])
    atr = float(latest.get('atr_14', 0.0))
    if atr <= 0:
        raise ValueError('ATR is unavailable; refusing to fabricate execution risk levels')
    stop_distance = atr * 1.5
    reward_distance = stop_distance * 2.0
    stop_loss = entry - stop_distance if direction == 'buy' else (entry + stop_distance if direction == 'sell' else None)
    take_profit = entry + reward_distance if direction == 'buy' else (entry - reward_distance if direction == 'sell' else None)
    trend = float(latest.get('trend', 0.0))
    trend_50 = float(latest.get('trend_50', 0.0))
    regime = 'trending' if abs(trend) + abs(trend_50) > max(abs(entry) * 0.00025, 1e-8) else 'ranging'
    if confidence < min_confidence:
        direction = 'wait'
        stop_loss = None
        take_profit = None
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
        'reasoning': f"Model probability_up={p:.4f}; plan_mode={trading_mode}; ATR-based risk levels; trend={trend:.6f}; regime={regime}.",
    }
