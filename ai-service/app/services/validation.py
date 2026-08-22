from dataclasses import dataclass

@dataclass
class ValidationResult:
    approved: bool
    reasons: list[str]
    metrics: dict

def validate_model(metrics: dict, rules: dict | None = None) -> ValidationResult:
    rules = rules or {'min_profit_factor':1.10,'max_drawdown_pct':20.0,'min_trades':100,'min_sharpe':0.25}
    reasons=[]
    checks=[
      ('profit_factor','min_profit_factor',lambda v,r:v>=r,'profit factor too low'),
      ('max_drawdown_pct','max_drawdown_pct',lambda v,r:v<=r,'drawdown too high'),
      ('total_trades','min_trades',lambda v,r:v>=r,'too few trades'),
      ('sharpe','min_sharpe',lambda v,r:v>=r,'Sharpe ratio too low'),
    ]
    for metric,rule,fn,msg in checks:
        if metric not in metrics: reasons.append(f'missing {metric}')
        elif not fn(float(metrics[metric]), float(rules[rule])): reasons.append(msg)
    return ValidationResult(not reasons,reasons,metrics)
