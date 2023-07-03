#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

"""
Description: Contains functions needed to perform the sf enrichment analysis
"""

from pathlib import Path
from typing import Dict, List, Optional
from .databases_functions import recover_sf_table
from .randomization_function import get_randomization_result
import pandas as pd
from .filter_function import filter_sf_dataframe, read_input, add_regulated_gene_columns


def get_test_counts(df_sf: pd.DataFrame, list_exons: List[str]
                    ) -> Dict[str, int]:
    """
    Get the dictionary of tests count

    :param df_sf: A dataframe containing exons regulated by every splicing \
    factors
    :param list_exons: The list of exons of interest
    :return: A dictionnary containing the number of exons regulated by all
    splicing factors inside our input list of exons
    """
    tmp = df_sf[df_sf["exons"].isin(list_exons)].drop("exons", axis=1).sum()
    return tmp.to_dict()  # type: ignore


def sf_enrichment_maker(input_file: Path, iteration: int = 10000,
                        filter_reg: str = "one", threshold: int = 50,
                        reg: str = "down", output: Path = Path("."),
                        filter_path: Optional[Path] = None,
                        create_ireg_col: bool = False) -> None:
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
    :param output: folder where the output fillebe created
    :param filter_path: A file containing genes or exons to remove in the \
    the analysis
    :param create_ireg_col: Create the column exon_ireg in the output folder,
    defaults to False
    """
    exons_list = read_input(input_file)
    df_sf = recover_sf_table(output, filter_reg, threshold, reg)
    list_sf = [x for x in df_sf.columns if x != "exons"]
    dic_test = get_test_counts(df_sf, exons_list)
    ndf_sf = filter_sf_dataframe(df_sf, filter_path)
    df = get_randomization_result(
        dic_test, ndf_sf, len(exons_list),
        list_sf, iteration)
    if create_ireg_col:
        df_ireg = add_regulated_gene_columns(df_sf, exons_list)
        df = df.merge(df_ireg, on="SF", how="left")
    outfile = output / \
        f"{input_file.stem}_filter-sf-{threshold}-{filter_reg}_{reg}.txt"
    df.to_csv(outfile, sep="\t", index=False)
