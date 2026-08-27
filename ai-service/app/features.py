import pandas as pd
import numpy as np

def make_features(df: pd.DataFrame) -> pd.DataFrame:
    x = df.copy()
    x['return_1'] = x['close'].pct_change()
    x['return_3'] = x['close'].pct_change(3)
    x['return_5'] = x['close'].pct_change(5)
    x['return_10'] = x['close'].pct_change(10)
    x['ma_10'] = x['close'].rolling(10).mean()
    x['ma_30'] = x['close'].rolling(30).mean()
    x['ma_50'] = x['close'].rolling(50).mean()
    x['trend'] = (x['ma_10'] - x['ma_30']) / x['close']
    x['trend_50'] = (x['ma_30'] - x['ma_50']) / x['close']
    delta = x['close'].diff()
    gain = delta.clip(lower=0).rolling(14).mean()
    loss = (-delta.clip(upper=0)).rolling(14).mean().replace(0, np.nan)
    rs = gain / loss
    x['rsi_14'] = 100 - (100 / (1 + rs))
    tr = pd.concat([(x['high']-x['low']), (x['high']-x['close'].shift()).abs(), (x['low']-x['close'].shift()).abs()], axis=1).max(axis=1)
    x['atr_14'] = tr.rolling(14).mean()
    x['atr_pct'] = x['atr_14'] / x['close']
    x['range_pct'] = (x['high'] - x['low']) / x['close']
    x['body_pct'] = (x['close'] - x['open']).abs() / x['close']
    x['upper_wick_pct'] = (x['high'] - x[['open', 'close']].max(axis=1)) / x['close']
    x['lower_wick_pct'] = (x[['open', 'close']].min(axis=1) - x['low']) / x['close']
    x['vol_ma_20'] = x['volume'].rolling(20).mean() if 'volume' in x.columns else np.nan
    x['vol_ratio'] = x['volume'] / x['vol_ma_20'] if 'volume' in x.columns else np.nan
    x['compression'] = x['range_pct'] / x['atr_pct'].replace(0, np.nan)
    future_3 = x['close'].shift(-3)
    edge_threshold = (x['atr_14'] * 0.35).fillna(0)
    x['future_return_3'] = (future_3 - x['close']) / x['close']
    x['edge_strength_3'] = (future_3 - x['close']) / x['atr_14'].replace(0, np.nan)
    x['target'] = (future_3 > (x['close'] + edge_threshold)).astype(int)
    return x.dropna().reset_index(drop=True)
