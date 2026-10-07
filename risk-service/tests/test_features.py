import pytest
from app.features import BookingFeatures, FEATURE_COLUMNS


def test_booking_features_defaults():
    f = BookingFeatures()
    assert f.lead_time_hours == 24.0
    assert f.prior_no_shows == 0
    assert f.day_of_week == 1


def test_to_feature_vector_shape_and_columns():
    f = BookingFeatures(
        lead_time_hours=48.0,
        prior_no_shows=2,
        prior_completed=2,
        day_of_week=4,
        hour_of_day=15,
        service_duration_min=45,
        service_price=50.0,
        deposit_paid=True,
    )
    df = f.to_feature_vector()

    assert list(df.columns) == FEATURE_COLUMNS
    assert len(df) == 1
    assert df["no_show_ratio"].iloc[0] == 0.5
    assert df["deposit_paid"].iloc[0] == 1
