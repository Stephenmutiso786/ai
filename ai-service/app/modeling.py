from __future__ import annotations

import numpy as np

FEATURE_COLUMNS = ['return_1', 'return_3', 'return_5', 'return_10', 'trend', 'trend_50', 'rsi_14', 'atr_14', 'atr_pct', 'range_pct', 'body_pct', 'upper_wick_pct', 'lower_wick_pct', 'vol_ma_20', 'vol_ratio', 'compression']


class LinearSignalModel:
    def __init__(self, feature_columns: list[str], learning_rate: float = 0.05, iterations: int = 2000):
        self.feature_columns = feature_columns
        self.learning_rate = learning_rate
        self.iterations = iterations
        self.coef_ = None
        self.intercept_ = 0.0
        self.mean_ = None
        self.scale_ = None

    def _prepare(self, X):
        arr = np.asarray(X, dtype=float)
        if self.mean_ is None:
            self.mean_ = arr.mean(axis=0)
            self.scale_ = arr.std(axis=0)
            self.scale_[self.scale_ == 0] = 1.0
        return (arr - self.mean_) / self.scale_

    def fit(self, X, y):
        Xn = self._prepare(X)
        y = np.asarray(y, dtype=float)
        n_features = Xn.shape[1]
        self.coef_ = np.zeros(n_features, dtype=float)
        self.intercept_ = 0.0

        for _ in range(self.iterations):
            linear = Xn @ self.coef_ + self.intercept_
            probs = 1.0 / (1.0 + np.exp(-linear))
            error = probs - y
            grad_w = (Xn.T @ error) / len(Xn)
            grad_b = float(error.mean())
            self.coef_ -= self.learning_rate * grad_w
            self.intercept_ -= self.learning_rate * grad_b

        return self

    def predict_proba(self, X):
        arr = np.asarray(X, dtype=float)
        arr = (arr - self.mean_) / self.scale_
        linear = arr @ self.coef_ + self.intercept_
        probs = 1.0 / (1.0 + np.exp(-linear))
        return np.column_stack([1.0 - probs, probs])


def train_classifier(df, config=None):
    config = config or {}
    split = max(int(len(df) * 0.8), 1)
    train, test = df.iloc[:split], df.iloc[split:]
    learning_rate = float(config.get('learning_rate', 0.05))
    iterations = int(config.get('iterations', 2500))
    model = LinearSignalModel(FEATURE_COLUMNS, learning_rate=learning_rate, iterations=iterations)
    model.fit(train[FEATURE_COLUMNS].to_numpy(), train['target'].to_numpy())
    metrics = {'train_rows': int(len(train)), 'test_rows': int(len(test))}
    if len(test):
        p = model.predict_proba(test[FEATURE_COLUMNS].to_numpy())[:, 1]
        y = test['target'].to_numpy()
        metrics['accuracy'] = float(((p >= 0.5).astype(int) == y).mean())
        if len(np.unique(y)) > 1:
            order = np.argsort(p)
            ranked = y[order]
            cum_pos = np.cumsum(ranked)
            total_pos = cum_pos[-1]
            total_neg = len(y) - total_pos
            if total_pos > 0 and total_neg > 0:
                auc = (cum_pos[ranked == 0].sum() / (total_pos * total_neg)) if total_pos and total_neg else 0.5
                metrics['roc_auc'] = float(max(0.0, min(1.0, auc)))
    return model, metrics
