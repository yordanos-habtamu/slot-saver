from fastapi.testclient import TestClient
import pytest
from app.main import app, load_model


@pytest.fixture(autouse=True)
def setup_app():
    load_model()


def test_health_check_endpoint():
    client = TestClient(app)
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["model_loaded"] is True


def test_score_endpoint_low_risk_customer():
    client = TestClient(app)
    payload = {
        "lead_time_hours": 2.0,
        "prior_no_shows": 0,
        "prior_completed": 10,
        "day_of_week": 1,
        "hour_of_day": 10,
        "service_duration_min": 30,
        "service_price": 25.0,
        "deposit_paid": False,
    }
    response = client.post("/score", json=payload)
    assert response.status_code == 200
    data = response.json()

    assert data["risk_score"] < 0.60
    assert data["requires_deposit"] is False
    assert data["latency_ms"] >= 0.0


def test_score_endpoint_high_risk_customer():
    client = TestClient(app)
    payload = {
        "lead_time_hours": 168.0,  # 7 days lead time
        "prior_no_shows": 3,       # 3 prior misses
        "prior_completed": 0,
        "day_of_week": 5,          # Saturday
        "hour_of_day": 17,
        "service_duration_min": 60,
        "service_price": 60.0,
        "deposit_paid": False,
    }
    response = client.post("/score", json=payload)
    assert response.status_code == 200
    data = response.json()

    assert data["risk_score"] >= 0.60
    assert data["risk_tier"] == "high"
    assert data["requires_deposit"] is True
    assert data["suggested_deposit_amount"] >= 10.0
    assert len(data["top_risk_factors"]) > 0
