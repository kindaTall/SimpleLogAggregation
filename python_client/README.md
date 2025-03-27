# Python Log Aggregator Handler

A custom Python logging handler for sending log messages to a centralized log aggregation API.

## Overview

This handler extends the standard `logging.Handler` to format log records as JSON and send them via HTTP POST requests to a specified API endpoint.

## Features

- Formats log records into JSON.
- Sends logs to a configurable API endpoint.
- Allows custom host identification.
- Maps standard Python logging levels to API-specific levels.
- Converts timestamps to ISO 8601 format.
- Includes optional authentication support.
- Implements basic retry logic for network issues (to be detailed).
- Optional buffering for batch sending (future enhancement).
- Connection health check against the API's `/api/health` endpoint.

## Installation

```bash
pip install log-aggregator-handler # Or install from source/wheel file
```

Ensure the `requests` library is installed:
```bash
pip install requests
```

## Usage Example

```python
import logging
import os
from log_aggregator_handler import LogAggregatorHandler # Assuming the handler class is named LogAggregatorHandler

# --- Configuration ---
API_ENDPOINT = "http://your-log-api.com/api/logs" # Replace with your actual API endpoint
AUTH_TOKEN = os.environ.get("LOG_API_TOKEN") # Example: Get token from environment variable

# Get the root logger or a specific logger
logger = logging.getLogger("my_application")
logger.setLevel(logging.DEBUG) # Set the desired logging level for the logger

# --- Create and configure the handler ---
# Basic configuration
# handler = LogAggregatorHandler(api_endpoint=API_ENDPOINT)

# Configuration with authentication and custom host
handler = LogAggregatorHandler(
    api_endpoint=API_ENDPOINT,
    auth=("user", AUTH_TOKEN), # Example: Basic Auth or a token tuple/dict
    host="my-custom-hostname" # Optional: overrides socket.gethostname()
)

# Optional: Set a specific log level for the handler
handler.setLevel(logging.INFO)

# Optional: Add a formatter if needed (handler formats to JSON by default)
# formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
# handler.setFormatter(formatter) # Note: Default JSON format might be sufficient

# --- Add the handler to the logger ---
logger.addHandler(handler)

# --- Test the connection (optional but recommended) ---
if handler.check_connection():
    logger.info("Successfully connected to the log aggregation API.")
else:
    logger.warning("Could not connect to the log aggregation API. Logs might be lost.")

# --- Log messages ---
logger.debug("This is a debug message.")
logger.info("Application started successfully.")
logger.warning("Potential issue detected.")
logger.error("An error occurred during processing.", exc_info=True) # Include exception info
logger.critical("Critical system failure!")

# Example with extra data
logger.info("User logged in.", extra={"user_id": 123, "ip_address": "192.168.1.100"})

# --- Important: Ensure logs are sent before exit (if using buffering/async) ---
# logging.shutdown() # Call this if necessary, depends on handler implementation details
```

## Configuration Options

- `api_endpoint` (str, optional): The URL of the log aggregation API endpoint. If not provided, the handler will attempt to read the endpoint from the `LOG_AGGREGATOR_API_ENDPOINT` environment variable. If the `python-dotenv` package is installed, the handler will also attempt to load environment variables from a `.env` file. If no API endpoint is found, a ValueError will be raised.
- `host` (str, optional): Custom identifier for the host machine. Defaults to `socket.gethostname()`.
- `auth` (tuple/dict/callable, optional): Authentication credentials for the API (e.g., `('user', 'pass')` for Basic Auth, `{'Authorization': 'Bearer <token>'}` headers dict, or a callable returning headers). Defaults to `None`.
- `timeout` (float, optional): Request timeout in seconds. Defaults to `5.0`.
- `retry_attempts` (int, optional): Number of retry attempts on network failure. Defaults to `3`.
- `retry_delay` (float, optional): Delay between retries in seconds. Defaults to `1.0`.
- `verify_ssl` (bool, optional): Whether to verify the server's TLS certificate. Defaults to `True`.
- `level_map` (dict, optional): Custom mapping from Python log level names (UPPERCASE) to API level strings. Defaults to standard mapping.

*(Buffering options will be added here if implemented)*

### .env File Support (Optional)

To use a `.env` file for configuration, install the `python-dotenv` package:

```bash
pip install log-aggregator-handler[dotenv]
```

The handler will then automatically load environment variables from a `.env` file in the current directory.

### Configuration Precedence

The handler checks for settings in the following order:

1.  `api_endpoint` argument passed to the constructor.
2.  `LOG_AGGREGATOR_API_ENDPOINT` variable in a `.env` file (if `[dotenv]` extra is installed).
3.  `LOG_AGGREGATOR_API_ENDPOINT` environment variable in the system.

If no API endpoint is found, a ValueError will be raised.

## Logging Levels Mapping

The handler maps Python's standard logging levels to string representations expected by the API:

| Python Level | API Level String |
|--------------|------------------|
| `DEBUG`      | `"DEBUG"`        |
| `INFO`       | `"INFO"`         |
| `WARNING`    | `"WARNING"`      |
| `ERROR`      | `"ERROR"`        |
| `CRITICAL`   | `"CRITICAL"`     |

This mapping can be customized via the `level_map` constructor argument.

## Timestamp Format

Timestamps are automatically converted to **ISO 8601 format** with timezone information (UTC by default, or using the record's timestamp). Example: `2025-03-27T08:02:51.123456+00:00`.

## Connection Health Check

The handler provides a `check_connection()` method that sends a GET request to the API's `/api/health` endpoint (relative to the `api_endpoint`). It returns `True` if the API responds with a 2xx status code, `False` otherwise. This can be used at application startup to verify connectivity.

## Error Handling

- **Network Failures:** The handler attempts retries based on `retry_attempts` and `retry_delay`. If retries fail, the error is logged to `sys.stderr` (or a configured fallback logger) and the log record is dropped.
- **API Errors:** Non-2xx responses from the API are treated as errors. The status code and response body (if available) are logged to `sys.stderr`, and the record is dropped.
- **Fallback Logging:** In case of persistent failures, consider adding a standard `logging.FileHandler` or `logging.StreamHandler` to your logger alongside this handler to ensure critical logs are not lost entirely.
- **Monitoring:** Monitor `sys.stderr` or the fallback log file for errors reported by the handler. Implement application-level health checks that include the result of `handler.check_connection()`.

## Testing Recommendations

- **Unit Tests:** Use `unittest.mock` or `requests-mock` to mock the `requests.post` and `requests.get` calls. Verify that the handler formats the JSON payload correctly, includes correct headers (like authentication), and handles different log levels and `extra` data. Test retry logic and error handling paths.
- **Integration Tests:** Set up a development instance of the log aggregation API. Configure the handler to point to this instance and send actual log messages. Verify that logs appear correctly in the aggregation system.
- **Load Testing:** Simulate high-volume logging scenarios to test the handler's performance and the API's capacity. Monitor resource usage (CPU, memory, network) of the application using the handler. Consider potential bottlenecks if using synchronous HTTP requests in a high-throughput application.

## Contributing

*(Placeholder for contribution guidelines)*

## License

This project is licensed under the MIT License - see the LICENSE file for details. *(A LICENSE file should be added)*
