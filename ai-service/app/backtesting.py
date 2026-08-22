def summarize_equity(equity):
    peak = equity[0] if equity else 1.0
    max_dd = 0.0
    for v in equity:
        peak = max(peak, v)
        max_dd = min(max_dd, (v/peak)-1)
    return max_dd

def run_backtest(df, config=None):
    config = config or {}
    risk = float(config.get('risk_fraction', 0.01))
    rr = float(config.get('risk_reward', 2.0))
    equity = 1.0; curve=[equity]; wins=losses=0
    for _, row in df.iterrows():
        score = float(row.get('prediction', 0.5))
        if score >= .55: pnl = risk*rr if row['target']==1 else -risk
        elif score <= .45: pnl = risk*rr if row['target']==0 else -risk
        else: continue
        equity *= (1+pnl); curve.append(equity)
        wins += pnl > 0; losses += pnl <= 0
    trades=wins+losses
    return {'return_pct': round((equity-1)*100,4),'trades':trades,'wins':wins,'losses':losses,'win_rate': round((wins/trades*100) if trades else 0,2),'max_drawdown_pct':round(summarize_equity(curve)*100,4)}
