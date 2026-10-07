import time
from typing import Any, Dict, List, Optional
import joblib
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from .config import (
    DEFAULT_DEPOSIT_RATIO,
    HIGH_RISK_THRESHOLD,
    MEDIUM_RISK_THRESHOLD,
    MIN_DEPOSIT_AMOUNT,
    MODEL_PATH,
)
from .features import BookingFeatures

app = FastAPI(
    title="SlotSaver No-Show Risk Service",
    description="Explainable Logistic Regression service predicting appointment cancellation & no-show risk",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global model container
model = None


@app.on_event("startup")
def load_model():
    global model
    if MODEL_PATH.exists():
        model = joblib.load(MODEL_PATH)
        print(f"Loaded trained model from {MODEL_PATH}")
    else:
        print(f"WARNING: Model not found at {MODEL_PATH}. Run python model/train.py first.")


class RiskFactor(BaseModel):
    factor: str
    impact: str
    description: str


class RiskScoreResponse(BaseModel):
    risk_score: float
    risk_tier: str
    requires_deposit: bool
    suggested_deposit_amount: float
    top_risk_factors: List[RiskFactor]
    latency_ms: float


@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "model_loaded": model is not None,
        "version": "1.0.0",
    }


@app.post("/score", response_model=RiskScoreResponse)
def score_booking(features: BookingFeatures):
    start_time = time.perf_counter()

    if model is None:
        raise HTTPException(status_code=503, detail="Risk scoring model is not initialized.")

    try:
        X = features.to_feature_vector()
        proba = float(model.predict_proba(X)[0, 1])
        risk_score = round(proba, 4)

        if risk_score >= HIGH_RISK_THRESHOLD:
            risk_tier = "high"
            requires_deposit = True
        elif risk_score >= MEDIUM_RISK_THRESHOLD:
            risk_tier = "medium"
            requires_deposit = False
        else:
            risk_tier = "low"
            requires_deposit = False

        # Suggested deposit amount
        if requires_deposit:
            suggested_deposit = max(MIN_DEPOSIT_AMOUNT, round(features.service_price * DEFAULT_DEPOSIT_RATIO, 2))
        else:
            suggested_deposit = 0.0

        # Explainability: Extract key factors
        top_factors = []
        if features.prior_no_shows > 0:
            top_factors.append(RiskFactor(
                factor="prior_no_shows",
                impact=f"+{features.prior_no_shows * 0.4:.2f}",
                description=f"Customer has missed {features.prior_no_shows} appointment(s) in the past.",
            ))

        if features.lead_time_hours > 72:
            days = round(features.lead_time_hours / 24, 1)
            top_factors.append(RiskFactor(
                factor="lead_time_hours",
                impact="+0.25",
                description=f"Appointment booked {days} days in advance (high lead time increases forgetfulness).",
            ))

        if features.day_of_week in (4, 5):  # Friday or Saturday
            top_factors.append(RiskFactor(
                factor="peak_weekend",
                impact="+0.10",
                description="Weekend appointment slots experience higher competition and last-minute schedule conflicts.",
            ))

        if features.deposit_paid:
            top_factors.append(RiskFactor(
                factor="deposit_secured",
                impact="-0.50",
                description="Deposit is already secured, drastically reducing abandonment risk.",
            ))

        latency_ms = round((time.perf_counter() - start_time) * 1000, 2)

        return RiskScoreResponse(
            risk_score=risk_score,
            risk_tier=risk_tier,
            requires_deposit=requires_deposit,
            suggested_deposit_amount=suggested_deposit,
            top_risk_factors=top_factors,
            latency_ms=latency_ms,
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Scoring failed: {str(e)}")
