# -*- coding: utf-8 -*-
"""
Python logging handler for sending logs to a centralized aggregation API.
"""

__author__ = "Cline"
__version__ = "0.1.0"

# Import the main handler class to make it available directly from the package
# e.g., from log_aggregator_handler import LogAggregatorHandler
from .handler import LogAggregatorHandler

# Define what symbols are exported when using 'from log_aggregator_handler import *'
__all__ = ['LogAggregatorHandler']
