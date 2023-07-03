#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

"""
Description: Launch the sf enrichment script
"""

from pathlib import Path
import sys
#import ..py310_sl.lib.python3.10.site-packages.lazyparser as lp
import lazyparser as lp
from .sf_enrichment import sf_enrichment_maker
from loguru import logger

@lp.flag(create_ireg_col=True)
@lp.parse(input_file="file", filter_reg=["one", "all", "up", "down"],
          reg=["up", "down", "all"])  # type: ignore
def launcher(
        input_file: str, iteration: int = 10000, filter_reg: str = "one",
        threshold: int = 50, reg: str = "down", filter_file: str = "",
        output: str = ".", create_ireg_col: bool = False,
        level: str = "INFO") -> None:
    """
    Perform an enrichment analysis for all splicing factors

    :param input_file: A file containing exons in fasterDB id format \
    (e.g  10_1 for the first exons of gene 10)
    :param iteration: Number of iteration to perform, defaults to 10000
    :param filter_reg: Choose from [up, down, all, one]. If regulation \
    is up or down, then only splicing factors \
    regulating at least threshold exons with this regulation are kept. \
    If 'all' is chosen then \
    only splicing factor that up-regulates and down-regulates at least \
    `threshold` exons are returned. If 'one' is chosen, then \
    splicing factors must have at least `threshold` up-regulated exons OR \
    down-regulated exons, defaults to "one"
    :param threshold: Threshold used to filter splicing factors, defaults to 50
    :param reg: Choose from 'all', 'up', 'down'. Use for enrchiment analysis: \
    Consider only exons having those regulations, defaults to "down"
    :param file_filter: A file containing a list of genes exons, used to \
    restrict the number of exon used for permutations, defaults to ""
    :param output: folder where the output fillebe created
    :param create_ireg_col: Create the column exon_ireg in the output folder, \
    defaults to False

    :param level: The level of data to display
    """
    logger.remove()
    logger.add(sys.stderr, level=level)
    if filter_file != "":
        filter_path = Path(filter_file)
        if not filter_path.is_file():
            raise FileNotFoundError(f"The file {filter_path} does not exist")
    else:
        filter_path = None
    sf_enrichment_maker(Path(input_file), iteration, filter_reg, threshold,
                        reg, Path(output), filter_path, create_ireg_col)


if __name__ == "__main__":
    launcher()
