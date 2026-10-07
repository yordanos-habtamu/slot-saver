from pydantic import BaseModel, Field
import numpy as np
import pandas as pd


FEATURE_COLUMNS = [
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


class BookingFeatures(BaseModel):
    lead_time_hours: float = Field(default=24.0, ge=0.0, description="Hours between booking and appointment")
    prior_no_shows: int = Field(default=0, ge=0, description="Count of past no-shows")
    prior_completed: int = Field(default=0, ge=0, description="Count of past completed appointments")
    day_of_week: int = Field(default=1, ge=0, le=6, description="0=Monday, 6=Sunday")
    hour_of_day: int = Field(default=12, ge=0, le=23, description="Appointment start hour (0-23)")
    service_duration_min: int = Field(default=30, ge=5, le=480, description="Duration in minutes")
    service_price: float = Field(default=30.0, ge=0.0, description="Service cost")
    deposit_paid: bool = Field(default=False, description="Whether deposit is already collected")

    def to_feature_vector(self) -> pd.DataFrame:
        total_visits = self.prior_no_shows + self.prior_completed
        no_show_ratio = (self.prior_no_shows / total_visits) if total_visits > 0 else 0.0

        data = {
            "lead_time_hours": [self.lead_time_hours],
            "prior_no_shows": [self.prior_no_shows],
            "prior_completed": [self.prior_completed],
            "no_show_ratio": [float(no_show_ratio)],
            "day_of_week": [self.day_of_week],
            "hour_of_day": [self.hour_of_day],
            "service_duration_min": [self.service_duration_min],
            "service_price": [self.service_price],
            "deposit_paid": [1 if self.deposit_paid else 0],
        }

        return pd.DataFrame(data)[FEATURE_COLUMNS]
