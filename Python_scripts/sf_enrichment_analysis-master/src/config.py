#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

"""
Description: This file contains variables used in this submodule
"""

from pathlib import Path


class Config:
    """
    Variable used in this module
    """
    data = Path(__file__).parents[1] / "data"
    db_file = data / "sl.db"
