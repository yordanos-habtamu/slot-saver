from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
MODEL_PATH = BASE_DIR / "model" / "model.joblib"

# Calibrated classification threshold where deposit requirement is triggered
HIGH_RISK_THRESHOLD = 0.60
MEDIUM_RISK_THRESHOLD = 0.35

# Suggested deposit percentage
DEFAULT_DEPOSIT_RATIO = 0.25
MIN_DEPOSIT_AMOUNT = 10.0
