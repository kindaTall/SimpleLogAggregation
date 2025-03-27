# -*- coding: utf-8 -*-
import logging
import requests
import os
import socket
import datetime
import time
import json
import traceback
from urllib.parse import urljoin

# Define default level mapping
DEFAULT_LEVEL_MAP = {
    logging.DEBUG: "DEBUG",
    logging.INFO: "INFO",
    logging.WARNING: "WARNING",
    logging.ERROR: "ERROR",
    logging.CRITICAL: "CRITICAL",
}

class LogAggregatorHandler(logging.Handler):
    """
    A logging handler that sends log records as JSON to a remote API endpoint.
    """
    def __init__(self, api_endpoint: str = None,
                 host: str = None,
                 auth=None,
                 timeout: float = 5.0,
                 retry_attempts: int = 3,
                 retry_delay: float = 1.0,
                 verify_ssl: bool = True,
                 level_map: dict = None):
        """
        Initialize the handler.

        :param api_endpoint: The URL of the log aggregation API endpoint.
        :param host: Custom identifier for the host machine. Defaults to socket.gethostname().
        :param auth: Authentication credentials for the API (e.g., ('user', 'pass') for Basic Auth,
                     a dict for headers, or a callable returning headers). Defaults to None.
        :param timeout: Request timeout in seconds. Defaults to 5.0.
        :param retry_attempts: Number of retry attempts on network failure. Defaults to 3.
        :param retry_delay: Delay between retries in seconds (simple backoff). Defaults to 1.0.
        :param verify_ssl: Whether to verify the server's TLS certificate. Defaults to True.
        :param level_map: Custom mapping from Python log level numbers to API level strings.
                          Defaults to standard mapping.
        """
        super().__init__()

        # Load .env file if python-dotenv is installed
        # First check if api_endpoint was provided directly
        self.api_endpoint = api_endpoint

        # If not provided, try loading from environment
        if not self.api_endpoint:
            self.api_endpoint = os.environ.get("LOG_AGGREGATOR_API_ENDPOINT")

        if not self.api_endpoint:
            try:
                import dotenv
                dotenv.load_dotenv()
                self.api_endpoint = os.environ.get("LOG_AGGREGATOR_API_ENDPOINT")
            except ImportError:
                pass  # python-dotenv not installed, ignore
            
        # Raise error if still not found
        if not self.api_endpoint:
            raise ValueError(
                "api_endpoint must be provided either as a constructor argument or via the LOG_AGGREGATOR_API_ENDPOINT environment variable"
            )

        self.host = host or socket.gethostname()
        self.auth = auth
        self.timeout = timeout
        self.retry_attempts = max(0, retry_attempts) # Ensure non-negative
        self.retry_delay = max(0.1, retry_delay) # Ensure minimum delay
        self.verify_ssl = verify_ssl
        self.level_map = level_map or DEFAULT_LEVEL_MAP

        # Create a requests session for potential connection pooling and default headers
        self.session = requests.Session()
        self.session.verify = self.verify_ssl
        # Set auth if provided
        if self.auth:
            if isinstance(self.auth, dict):
                # Assume it's a header dictionary
                self.session.headers.update(self.auth)
            elif isinstance(self.auth, tuple) and len(self.auth) == 2:
                # Assume it's basic auth (user, pass)
                self.session.auth = self.auth
            elif callable(self.auth):
                # Defer auth handling to the callable during request
                pass # Handled in _send_log
            else:
                self.handleError(None, "Invalid auth type provided. Must be tuple, dict, or callable.")


    def format_record(self, record: logging.LogRecord) -> dict:
        """
        Format the log record into a dictionary suitable for JSON serialization.
        """
        log_entry = {
            "timestamp": self.format_timestamp(record.created),
            "level": self.level_map.get(record.levelno, record.levelname),
            "message": self.format(record), # Use formatter if set, else raw message
            "host": self.host,
            "host_process": record.name, # Use logger name as host_process
            "logger_name": record.name,
            "module": record.module,
            "filename": record.filename,
            "lineno": record.lineno,
            "funcName": record.funcName,
            "process": record.process,
            "thread": record.thread,
            "threadName": record.threadName,
        }

        # Include exception information if available
        if record.exc_info:
            log_entry["exception"] = "".join(
                traceback.format_exception(*record.exc_info)
            )
        elif record.exc_text:
             log_entry["exception"] = record.exc_text

        # Include extra attributes
        extra_attrs = {
            key: record.__dict__[key]
            for key in record.__dict__
            if key not in log_entry and key not in (
                'args', 'asctime', 'created', 'exc_info', 'exc_text', 'levelno',
                'levelname', 'message', 'module', 'msecs', 'msg', 'name',
                'pathname', 'process', 'processName', 'relativeCreated',
                'stack_info', 'thread', 'threadName', 'filename', 'lineno', 'funcName'
            )
        }
        if extra_attrs:
            log_entry["extra"] = extra_attrs

        return log_entry

    def format_timestamp(self, timestamp: float) -> str:
        """
        Format UNIX timestamp to ISO 8601 string.
        """
        dt = datetime.datetime.fromtimestamp(timestamp, tz=datetime.timezone.utc)
        return dt.isoformat(timespec='microseconds')

    def emit(self, record: logging.LogRecord):
        """
        Format the record and send it to the API endpoint.
        Handles retries on network errors.
        """
        try:
            log_data = self.format_record(record)
            self._send_log(log_data)
        except Exception:
            self.handleError(record) # Default handler logs to stderr

    def _send_log(self, log_data: dict):
        """
        Internal method to send the formatted log data with retry logic.
        """
        headers = {'Content-Type': 'application/json'}
        auth = self.session.auth # Use session auth if set (e.g., basic)
        request_headers = self.session.headers.copy() # Start with session headers

        # Handle callable auth - call it to get dynamic headers/auth
        if callable(self.auth):
            try:
                auth_result = self.auth()
                if isinstance(auth_result, dict):
                    request_headers.update(auth_result) # Update headers
                    auth = None # Clear basic auth if headers are provided
                elif isinstance(auth_result, tuple):
                    auth = auth_result # Use tuple as basic auth
                # else: ignore invalid result from callable
            except Exception as e:
                 self.handleError(None, f"Auth callable failed: {e}")
                 return # Don't proceed if auth fails

        current_retry = 0
        while current_retry <= self.retry_attempts:
            try:
                response = self.session.post(
                    self.api_endpoint,
                    json=log_data,
                    headers=request_headers,
                    auth=auth, # Pass auth tuple if applicable
                    timeout=self.timeout
                )
                # Raise HTTPError for bad responses (4xx or 5xx)
                response.raise_for_status()
                # Log sent successfully
                return

            except requests.exceptions.Timeout as e:
                error_msg = f"Request timed out after {self.timeout}s: {e}"
            except requests.exceptions.ConnectionError as e:
                error_msg = f"Connection error: {e}"
            except requests.exceptions.RequestException as e:
                # Includes HTTPError for bad status codes
                status_code = e.response.status_code if e.response is not None else 'N/A'
                response_text = e.response.text if e.response is not None else 'N/A'
                error_msg = f"Request failed: {e} (Status: {status_code}, Response: {response_text[:200]})"
                # Don't retry on client errors (4xx) or likely persistent server errors (unless configured)
                if e.response is not None and 400 <= e.response.status_code < 500:
                     self.handleError(None, error_msg) # Log error but don't retry 4xx
                     return

            # If we reached here, it's a potentially retryable error
            current_retry += 1
            if current_retry <= self.retry_attempts:
                time.sleep(self.retry_delay * (current_retry)) # Simple exponential backoff
                # Optionally log retry attempt here if needed
            else:
                # Max retries reached, handle final error
                self.handleError(None, f"Failed to send log after {self.retry_attempts + 1} attempts. Last error: {error_msg}")
                break # Exit loop

    def check_connection(self) -> bool:
        """
        Check connectivity with the API's health endpoint.
        Assumes health endpoint is '/api/health' relative to api_endpoint base.
        """
        try:
            # Construct health check URL relative to the main API endpoint
            # Assumes api_endpoint might be 'http://host/api/logs', we want 'http://host/api/health'
            base_url = self.api_endpoint.rsplit('/', 1)[0] # Get base path
            health_url = urljoin(base_url + '/', 'health') # Join robustly

            auth = self.session.auth
            request_headers = self.session.headers.copy()
            if callable(self.auth):
                 auth_result = self.auth()
                 if isinstance(auth_result, dict):
                     request_headers.update(auth_result)
                     auth = None
                 elif isinstance(auth_result, tuple):
                     auth = auth_result

            response = self.session.get(
                health_url,
                timeout=self.timeout,
                headers=request_headers,
                auth=auth,
            )
            response.raise_for_status() # Check for 2xx status
            return True
        except requests.exceptions.RequestException as e:
            status_code = e.response.status_code if e.response is not None else 'N/A'
            self.handleError(None, f"Health check failed: {e} (Status: {status_code})")
            return False
        except Exception as e:
            self.handleError(None, f"Health check failed with unexpected error: {e}")
            return False

    def handleError(self, record, message=None):
        """
        Handle errors during logging.
        Logs details to sys.stderr.
        Overwrites default logging.Handler.handleError to provide more context.
        """
        import sys
        ei = sys.exc_info()
        if ei and ei[0]: # If there's an active exception
            exc_type, exc_value, exc_tb = ei
            traceback_str = "".join(traceback.format_exception(exc_type, exc_value, exc_tb))
            emsg = f"--- Logging error ---\n"
            if message:
                emsg += f"{message}\n"
            emsg += f"Handler: {self.__class__.__name__}\n"
            if record:
                emsg += f"Record Logger: {record.name}\n"
                emsg += f"Record Message: {record.getMessage()}\n"
            emsg += f"Exception: {exc_type.__name__}: {exc_value}\n"
            emsg += f"Traceback:\n{traceback_str}"
            emsg += "---------------------\n"
        else: # If no active exception, just log the message
            emsg = f"--- Logging error ---\n"
            if message:
                emsg += f"{message}\n"
            emsg += f"Handler: {self.__class__.__name__}\n"
            if record:
                emsg += f"Record Logger: {record.name}\n"
                emsg += f"Record Message: {record.getMessage()}\n"
            emsg += "---------------------\n"

        print(emsg, file=sys.stderr)
        # Clean up exception info if we captured it
        if ei and ei[0]:
            del ei # Avoid dangling references

    def close(self):
        """
        Close the handler, releasing resources (e.g., closing the session).
        """
        self.session.close()
        super().close()

# Example of how to handle errors without printing traceback directly
# logging.Handler.handleError = handleError # Monkey patch if needed globally
