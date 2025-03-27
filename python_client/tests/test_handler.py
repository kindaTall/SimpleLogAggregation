# -*- coding: utf-8 -*-
import logging
import pytest
import requests_mock
import json
import socket
import os
from datetime import datetime, timezone

from log_aggregator_handler import LogAggregatorHandler

# Define a dummy API endpoint for testing
TEST_API_ENDPOINT = "http://test-log-api.com/api/logs"

def test_emit_basic_log(requests_mock):
    """
    Test that a basic log record is formatted and sent correctly.
    """
    # Mock the POST request to the API endpoint
    requests_mock.post(TEST_API_ENDPOINT, text='OK', status_code=200)

    # Create handler instance
    handler = LogAggregatorHandler(api_endpoint=TEST_API_ENDPOINT, retry_attempts=0)

    # Create a log record
    logger_name = "test_logger"
    log_message = "This is a test message"
    record = logging.LogRecord(
        name=logger_name,
        level=logging.INFO,
        pathname="/path/to/test.py",
        lineno=10,
        msg=log_message,
        args=[],
        exc_info=None,
        func="test_function"
    )

    # Emit the record
    handler.emit(record)

    # Assertions
    assert requests_mock.called, "API endpoint was not called"
    assert requests_mock.call_count == 1, "API endpoint called more than once"

    history = requests_mock.request_history
    assert history[0].method == 'POST'
    assert history[0].url == TEST_API_ENDPOINT

    # Check the JSON payload
    payload = json.loads(history[0].text)

    assert "timestamp" in payload
    assert "level" in payload
    assert "message" in payload
    assert "host" in payload
    assert "host_process" in payload
    assert "logger_name" in payload

    # Validate specific fields
    assert payload["level"] == "INFO"  # Default mapping for logging.INFO
    assert payload["message"] == log_message
    assert payload["host"] == socket.gethostname()  # Default host
    assert payload["host_process"] == logger_name  # Default host_process (logger name)
    assert payload["logger_name"] == logger_name

    # Validate timestamp format (ISO 8601)
    try:
        # Attempt to parse the timestamp to ensure it's valid ISO 8601
        timestamp_dt = datetime.fromisoformat(payload["timestamp"].replace('Z', '+00:00'))
        # Check if it's timezone-aware (should be UTC)
        assert timestamp_dt.tzinfo is not None and timestamp_dt.tzinfo.utcoffset(timestamp_dt).total_seconds() == 0
    except ValueError:
        pytest.fail(f"Timestamp '{payload['timestamp']}' is not in valid ISO 8601 format")

    # Clean up handler session
    handler.close()


def test_health_check_success(requests_mock):
    """
    Test the health check method when the API is healthy.
    """
    health_url = "http://test-log-api.com/api/health"
    requests_mock.get(health_url, text='OK', status_code=200)

    handler = LogAggregatorHandler(api_endpoint=TEST_API_ENDPOINT)
    is_healthy = handler.check_connection()

    assert is_healthy is True
    assert requests_mock.called
    assert requests_mock.call_count == 1
    assert requests_mock.request_history[0].method == 'GET'
    assert requests_mock.request_history[0].url == health_url

    handler.close()


def test_health_check_failure(requests_mock):
    """
    Test the health check method when the API is unhealthy.
    """
    health_url = "http://test-log-api.com/api/health"
    requests_mock.get(health_url, text='Error', status_code=503)

    handler = LogAggregatorHandler(api_endpoint=TEST_API_ENDPOINT)
    is_healthy = handler.check_connection()

    assert is_healthy is False
    assert requests_mock.called
    assert requests_mock.call_count == 1
    assert requests_mock.request_history[0].method == 'GET'
    assert requests_mock.request_history[0].url == health_url

    handler.close()


def test_emit_no_api_endpoint(requests_mock, monkeypatch):
    """
    Test that a ValueError is raised when no API endpoint is provided.
    """
    # Clear the environment variable
    monkeypatch.delenv("LOG_AGGREGATOR_API_ENDPOINT", raising=False)

    with pytest.raises(ValueError, match="api_endpoint must be provided either as a constructor argument or via the LOG_AGGREGATOR_API_ENDPOINT environment variable"):
        LogAggregatorHandler()


def test_emit_api_endpoint_from_env(requests_mock, monkeypatch):
    """
    Test that the API endpoint is read from the environment variable.
    """
    # Set the environment variable
    monkeypatch.setenv("LOG_AGGREGATOR_API_ENDPOINT", TEST_API_ENDPOINT)

    # Mock the POST request to the API endpoint
    requests_mock.post(TEST_API_ENDPOINT, text='OK', status_code=200)

    # Create handler instance (no api_endpoint argument)
    handler = LogAggregatorHandler(retry_attempts=0)

    # Create a log record
    logger_name = "test_logger"
    log_message = "This is a test message"
    record = logging.LogRecord(
        name=logger_name,
        level=logging.INFO,
        pathname="/path/to/test.py",
        lineno=10,
        msg=log_message,
        args=[],
        exc_info=None,
        func="test_function"
    )

    # Emit the record
    handler.emit(record)

    # Assertions
    assert requests_mock.called, "API endpoint was not called"
    assert requests_mock.call_count == 1, "API endpoint called more than once"

    history = requests_mock.request_history
    assert history[0].method == 'POST'
    assert history[0].url == TEST_API_ENDPOINT

    # Clean up handler session
    handler.close()


def test_emit_api_endpoint_from_argument(requests_mock):
    """
    Test that the API endpoint is read from the constructor argument.
    """
    # Mock the POST request to the API endpoint
    requests_mock.post(TEST_API_ENDPOINT, text='OK', status_code=200)

    # Create handler instance (with api_endpoint argument)
    handler = LogAggregatorHandler(api_endpoint=TEST_API_ENDPOINT, retry_attempts=0)

    # Create a log record
    logger_name = "test_logger"
    log_message = "This is a test message"
    record = logging.LogRecord(
        name=logger_name,
        level=logging.INFO,
        pathname="/path/to/test.py",
        lineno=10,
        msg=log_message,
        args=[],
        exc_info=None,
        func="test_function"
    )

    # Emit the record
    handler.emit(record)

    # Assertions
    assert requests_mock.called, "API endpoint was not called"
    assert requests_mock.call_count == 1, "API endpoint called more than once"

    history = requests_mock.request_history
    assert history[0].method == 'POST'
    assert history[0].url == TEST_API_ENDPOINT

    # Clean up handler session
    handler.close()

# Add more tests here for:
# - Different log levels
# - Exception formatting
# - 'extra' data handling
# - Authentication (mocking different auth types)
# - Retry logic (mocking network errors)
# - Custom host configuration
# - Custom level mapping
