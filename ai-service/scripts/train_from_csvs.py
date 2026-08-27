from __future__ import annotations

import argparse
import sys
from pathlib import Path
from typing import Iterable

import joblib
import pandas as pd

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from app.features import make_features
from app.market_data import validate_ohlcv
from app.modeling import FEATURE_COLUMNS, train_classifier


def load_csv(path: Path, timeframe: str) -> pd.DataFrame:
    df = pd.read_csv(
        path,
        header=None,
        names=["timestamp", "open", "high", "low", "close", "volume"],
    )
    df["timeframe"] = timeframe
    return validate_ohlcv(df)


def infer_timeframe(path: Path) -> str:
    stem = path.stem.upper()
    for token in ["M1", "M5", "M15", "M30", "H1", "H4", "D1", "1440", "240", "60", "30", "15", "5", "1"]:
        if stem.endswith(token):
            return token
    return "H1"


def build_dataset(paths: Iterable[Path]) -> pd.DataFrame:
    frames = []
    for path in paths:
        timeframe = infer_timeframe(path)
        raw = load_csv(path, timeframe)
        feat = make_features(raw)
        feat["timeframe"] = timeframe
        frames.append(feat)

    if not frames:
        raise ValueError("No CSV files were provided.")

    df = pd.concat(frames, ignore_index=True)
    df = pd.get_dummies(df, columns=["timeframe"], prefix="tf")
    return df


def train(df: pd.DataFrame, output: Path) -> dict:
    feature_cols = FEATURE_COLUMNS
    model, metrics = train_classifier(df[feature_cols + ["target"]], {"learning_rate": 0.02, "iterations": 600})
    metrics["feature_count"] = len(feature_cols)
    payload = {"model": model, "features": feature_cols, "metrics": metrics}
    output.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(payload, output)
    return metrics


def main() -> int:
    parser = argparse.ArgumentParser(description="Train a real EURUSD model from supplied CSV files.")
    parser.add_argument("csv", nargs="+", help="One or more OHLCV CSV files")
    parser.add_argument("--output", default="artifacts/model-eurusd-multitimeframe.joblib")
    parser.add_argument("--max-rows-per-file", type=int, default=20000)
    args = parser.parse_args()

    paths = [Path(p) for p in args.csv]
    df = build_dataset(paths)
    if args.max_rows_per_file and len(df) > args.max_rows_per_file * len(paths):
        frames = []
        for path in paths:
            timeframe = infer_timeframe(path)
            raw = load_csv(path, timeframe)
            feat = make_features(raw)
            feat["timeframe"] = timeframe
            frames.append(feat.tail(args.max_rows_per_file))
        df = pd.concat(frames, ignore_index=True)
        df = pd.get_dummies(df, columns=["timeframe"], prefix="tf")
    metrics = train(df, Path(args.output))
    print(metrics)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
