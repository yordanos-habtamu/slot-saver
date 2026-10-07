import os
from pathlib import Path
import joblib
import numpy as np
import pandas as pd
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import classification_report, roc_auc_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


def generate_synthetic_data(n_samples: int = 10000, random_state: int = 42) -> pd.DataFrame:
    np.random.seed(random_state)

    lead_time_hours = np.random.exponential(scale=48, size=n_samples).clip(1, 336)
    prior_no_shows = np.random.poisson(lam=0.6, size=n_samples).clip(0, 8)
    prior_completed = np.random.poisson(lam=3.5, size=n_samples).clip(0, 25)

    total_visits = prior_no_shows + prior_completed
    no_show_ratio = np.where(total_visits > 0, prior_no_shows / total_visits, 0.0)

    day_of_week = np.random.choice(7, size=n_samples)
    hour_of_day = np.random.choice(range(8, 20), size=n_samples)
    service_duration_min = np.random.choice([30, 45, 60, 90], size=n_samples, p=[0.4, 0.3, 0.2, 0.1])
    service_price = service_duration_min * np.random.uniform(0.7, 1.2, size=n_samples)

    # Deposit paid binary
    deposit_paid = np.random.choice([0, 1], size=n_samples, p=[0.85, 0.15])

    # Log-odds calculation for target no_show
    # High lead time + prior no-shows -> high risk; deposit paid -> strong negative risk
    z = (
        -2.2
        + 0.015 * lead_time_hours
        + 1.1 * prior_no_shows
        - 0.15 * prior_completed
        + 2.0 * no_show_ratio
        + 0.1 * (day_of_week >= 4)  # Fri/Sat slightly higher
        - 2.5 * deposit_paid
    )

    probs = 1 / (1 + np.exp(-z))
    no_show = (np.random.uniform(0, 1, size=n_samples) < probs).astype(int)

    df = pd.DataFrame({
        "lead_time_hours": lead_time_hours,
        "prior_no_shows": prior_no_shows,
        "prior_completed": prior_completed,
        "no_show_ratio": no_show_ratio,
        "day_of_week": day_of_week,
        "hour_of_day": hour_of_day,
        "service_duration_min": service_duration_min,
        "service_price": service_price,
        "deposit_paid": deposit_paid,
        "no_show": no_show,
    })

    return df


def train_model():
    model_dir = Path(__file__).resolve().parent
    model_dir.mkdir(parents=True, exist_ok=True)
    model_path = model_dir / "model.joblib"

    print("Generating synthetic appointment training dataset (10,000 samples)...")
    df = generate_synthetic_data()

    feature_cols = [
        "lead_time_hours",
        "prior_no_shows",
        "prior_completed",
        "no_show_ratio",
        "day_of_week",
        "hour_of_day",
        "service_duration_min",
        "service_price",
        "deposit_paid",
    ]

    X = df[feature_cols]
    y = df["no_show"]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    pipeline = Pipeline([
        ("scaler", StandardScaler()),
        ("classifier", LogisticRegression(class_weight="balanced", solver="liblinear", random_state=42)),
    ])

    print("Training Logistic Regression pipeline with balanced class weights...")
    pipeline.fit(X_train, y_train)

    y_pred_proba = pipeline.predict_proba(X_test)[:, 1]
    y_pred = (y_pred_proba >= 0.60).astype(int)

    auc = roc_auc_score(y_test, y_pred_proba)
    print(f"\nModel Performance on Test Set (Threshold >= 0.60):")
    print(f"ROC-AUC: {auc:.4f}")
    print("\nClassification Report:")
    print(classification_report(y_test, y_pred))

    # Feature Importance (Coefficients)
    clf = pipeline.named_steps["classifier"]
    coeffs = pd.Series(clf.coef_[0], index=feature_cols).sort_values(ascending=False)
    print("Standardized Feature Coefficients (Explainability):")
    for feat, coef in coeffs.items():
        print(f"  {feat:<22}: {coef:>+.4f}")

    joblib.dump(pipeline, model_path)
    print(f"\nModel saved successfully to: {model_path}")

    return pipeline


if __name__ == "__main__":
    train_model()
