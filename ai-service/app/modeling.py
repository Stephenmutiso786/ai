from sklearn.ensemble import HistGradientBoostingClassifier
from sklearn.metrics import accuracy_score, roc_auc_score

FEATURE_COLUMNS = ['return_1','return_5','trend','rsi_14','atr_14']

def train_classifier(df, config=None):
    config = config or {}
    split = max(int(len(df)*0.8), 1)
    train, test = df.iloc[:split], df.iloc[split:]
    model = HistGradientBoostingClassifier(max_iter=int(config.get('max_iter', 200)), learning_rate=float(config.get('learning_rate', 0.08)), random_state=42)
    model.fit(train[FEATURE_COLUMNS], train['target'])
    metrics = {'train_rows': int(len(train)), 'test_rows': int(len(test))}
    if len(test):
        p = model.predict_proba(test[FEATURE_COLUMNS])[:,1]
        y = test['target']
        metrics['accuracy'] = float(accuracy_score(y, (p >= .5).astype(int)))
        if len(set(y)) > 1: metrics['roc_auc'] = float(roc_auc_score(y,p))
    return model, metrics
