import pandas as pd
import numpy as np

def make_features(df: pd.DataFrame) -> pd.DataFrame:
    x = df.copy()
    x['return_1'] = x['close'].pct_change()
    x['return_5'] = x['close'].pct_change(5)
    x['ma_10'] = x['close'].rolling(10).mean()
    x['ma_30'] = x['close'].rolling(30).mean()
    x['trend'] = (x['ma_10'] - x['ma_30']) / x['close']
    delta = x['close'].diff()
    gain = delta.clip(lower=0).rolling(14).mean()
    loss = (-delta.clip(upper=0)).rolling(14).mean().replace(0, np.nan)
    rs = gain / loss
    x['rsi_14'] = 100 - (100 / (1 + rs))
    tr = pd.concat([(x['high']-x['low']), (x['high']-x['close'].shift()).abs(), (x['low']-x['close'].shift()).abs()], axis=1).max(axis=1)
    x['atr_14'] = tr.rolling(14).mean()
    x['target'] = (x['close'].shift(-1) > x['close']).astype(int)
    return x.dropna().reset_index(drop=True)
