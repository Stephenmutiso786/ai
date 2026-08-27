from pydantic import BaseModel, Field
from typing import Any, Literal

class TrainingRun(BaseModel):
    run_id: int
    model_id: int
    dataset_id: int | None = None
    config: dict[str, Any] = Field(default_factory=dict)

class Backtest(BaseModel):
    backtest_id: int
    model_id: int
    instrument: str
    timeframe: str
    starts_at: str | None = None
    ends_at: str | None = None
    config: dict[str, Any] = Field(default_factory=dict)

class InferenceRequest(BaseModel):
    model_id: int
    symbol: str
    timeframe: str
    features: dict[str, float]

class CallbackResult(BaseModel):
    status: Literal['completed','failed']
    metrics: dict[str, Any] = Field(default_factory=dict)
    results: dict[str, Any] = Field(default_factory=dict)
    error_message: str | None = None

class LiveSignalRequest(BaseModel):
    model_id: int
    model_artifact_uri: str | None = None
    symbol: str
    timeframe: str = 'H1'
    provider: str = 'oanda'
    provider_config: dict[str, Any] = Field(default_factory=dict)
    count: int = 300
