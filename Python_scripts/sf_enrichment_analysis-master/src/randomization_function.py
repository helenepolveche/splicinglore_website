#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

"""
Description: Contains function dedictaed to perform the randomization tests
"""

from math import floor, log
from typing import Any, Dict, Iterable, List, Tuple
import pandas as pd
from .databases_functions import get_pbar
import numpy as np
from loguru import logger
from statsmodels.stats.multitest import multipletests


class IterationNumberError(Exception):
    pass


def update_dict(main_dic: Dict[str, List[int]],
                dic: Dict[str, int]) -> Dict[str, List[int]]:
    """
    Update main_dict withthe value f dict

    :param main_dic: A dictionary linking a key to a list of value
    :param dic: A dictionary linking a key to a value
    :return: Update main_dic with the value of dic
    """
    for k in main_dic:
        main_dic[k].append(dic[k])
    return main_dic


def perform_randomization(df_sf: pd.DataFrame, test_size: int,
                          list_sf: List[str],
                          iteration: int = 10000) -> Dict[str, List[int]]:
    """
    Perfom the randomization

    :param test_content: A dictionary containing the number of exons in \
    the input list regulated by a splicing factor
    :param df_sf: The dataframe containing exons regulated by every \
    splicing factor
    :param list_sf: The list of splicing factor of interest
    :param test_size: The number of exons inside the input file
    :param iteration: The number of iteration to make, defaults to 10000
    :return: A dictionary containing the number of exon regulated by a
    splicing factor in each control group
    """
    res_dic: Dict[str, List[int]] = {x: [] for x in list_sf}
    progress = get_pbar()
    tmp_df = df_sf.drop("exons", axis=1)
    #with progress:
    for _ in range(iteration):
        tmp = tmp_df.sample(test_size).sum().to_dict()
        res_dic = update_dict(res_dic, tmp)
    return res_dic


def get_regulation(
        impov_pvalue: float, enrich_pvalue: float, alpha: float,
        iteration: int) -> Tuple[float, str]:
    """
    Get the regulation from p-values

    :param impov_pvalue: impoverished p-value
    :param enrich_pvalue: enriched p-value
    :param alpha: Type I error
    :param iteration: The number of iteration

    :return: The regulation and p-value
    """
    regulation = " . "
    if impov_pvalue < enrich_pvalue:
        pval = impov_pvalue
        if pval <= alpha:
            regulation = " - "
    else:
        pval = enrich_pvalue
        if pval <= alpha:
            regulation = " + "
    if iteration < 20:
        regulation = " . "
    return pval, regulation


@logger.catch(reraise=True)
def get_pvalue(values: Iterable[Any], target: float, iteration: int,
               alpha: float = 0.05, alternative: str = "two_sided",
               correction: bool = True) -> Tuple[float, str]:
    """
    Return The p-value and the regulation
    :param values:  The list of control values
    :param target: The test value
    :param iteration: The iteration of interest
    :param alpha: Type 1 error threshold
    :param alternative: two_sided, greater or less
    :param correction: Account for misestimation of the p-value
    :return: The p-value and the regulation
    >>> get_pvalue(np.arange(10), 1.75, 10)
    (0.2, ' . ')
    >>> get_pvalue(np.arange(10), -1, 10)
    (0.0, ' . ')
    >>> get_pvalue(np.arange(10), 9.75, 10)
    (0.0, ' . ')
    >>> get_pvalue(np.arange(10), 8.8, 10)
    (0.1, ' . ')
    >>> get_pvalue(np.arange(100), 98.8, 100)
    (0.01, ' + ')
    >>> get_pvalue(np.arange(100), 100, 100)
    (0.0, ' + ')
    >>> get_pvalue(np.arange(100), -1, 100)
    (0.0, ' - ')
    >>> get_pvalue(np.arange(0, 0.10, 0.01), 0.05, 10)
    (0.5, ' . ')
    >>> get_pvalue(np.arange(100), 98.8, 100, 0.05, "two_sided")
    (0.01, ' + ')
    >>> get_pvalue(np.arange(100), 98.8, 100, 0.05, "greater")
    (0.01, ' + ')
    >>> get_pvalue(np.arange(100), 98.8, 100, 0.05, "less")
    (0.99, ' . ')
    >>> get_pvalue(np.arange(1, 101), 1, 100, 0.05)
    (0.01, ' - ')
    >>> get_pvalue(np.arange(1, 101), 1, 100, 0.05, 'two_sided')
    (0.01, ' - ')
    >>> get_pvalue(np.arange(1, 101), 1, 100, 0.05, 'less')
    (0.01, ' - ')
    >>> get_pvalue(np.arange(1, 101), 1, 100, 0.05, 'greater')
    (1.0, ' . ')
    >>> get_pvalue(np.arange(1, 101), 1, 100, 0.05, 'greater', correction=True)
    (1.0, ' . ')
    >>> get_pvalue(np.arange(100), 98.8, 100, 0.05, correction=True)
    (0.0198, ' + ')
    >>> get_pvalue(np.arange(100), 100, 100, correction=True)
    (0.0099, ' + ')
    """
    if np.nan in values:
        return np.nan, '.'
    values = np.sort(values)  # type: ignore
    val_len = len(values)
    if val_len != iteration:
        msg = f"Error the length of values vector {len(values)} " \
              f"is different than iteration {iteration}"
        raise IterationNumberError(msg)
    idx = values[values <= target].size
    impov = idx / val_len
    if correction:
        impov = (idx + 1) / (val_len + 1)
    idx = values[values >= target].size
    enrich = idx / val_len
    if correction:
        enrich = (idx + 1) / (val_len + 1)
    if alternative == "greater":
        impov = 1
    elif alternative == "less":
        enrich = 1
    pval, regulation = get_regulation(impov, enrich, alpha, iteration)
    return round(pval, 8), regulation


def human_format(number):
    """
    Turn a number into an human readable number

    :param number: A number
    :return: An human readable number
    """
    units = ['', 'K', 'M', 'G', 'T', 'P']
    k = 1000.0
    magnitude = int(floor(log(number, k)))
    return '%.3g%s' % (number / k**magnitude, units[magnitude])


def get_all_pvalues(dic_ctrl: Dict[str, List[int]], dic_test: Dict[str, int],
                    size_test: int,
                    iteration: int) -> pd.DataFrame:
    """
    Get the pvalue for every factors

    :param dic_ctrl: A dictionary containing control counts
    :param dic_test: A dictionary containing test counts
    :param size_test: The total number of exon inside the input list
    :param iteration: The number of iteration performed
    :return: A dataframe containing for every splicing factor an enrichment \
    p-value of our input list
    """
    dic_tmp = {k: get_pvalue(dic_ctrl[k], dic_test[k], iteration)
               for k in dic_ctrl}
    dic_mean_ctrl = {k: np.mean(dic_ctrl[k]) for k in dic_ctrl}
    dic_prop_ctrl = {
        k: dic_mean_ctrl[k] / size_test * 100 for k in dic_mean_ctrl}
    dic_pval = {k: dic_tmp[k][0] for k in dic_tmp}
    dic_reg = {k: dic_tmp[k][1] for k in dic_tmp}
    dic_prop = {k: dic_test[k] / size_test * 100 for k in dic_test}
    hm = human_format(iteration)
    content = {"count": dic_test, "freq": dic_prop,
               f"count_{hm}_ctrl": dic_mean_ctrl,
               f"freq_{hm}_ctrl": dic_prop_ctrl, "p-val": dic_pval,
               "reg": dic_reg}
    df = pd.DataFrame(content)
    df["p-adj"] = multipletests(df["p-val"], method="fdr_bh")[1]
    df.loc[df["p-adj"] > 0.05, "reg"] = " . "
    cols = ["SF"] + [x for x in df.columns if x != "reg"] + ["reg"]
    df = df.reset_index()
    df.rename({"index": "SF"}, axis=1, inplace=True)
    return df[cols].sort_values(["p-adj", "freq"], ascending=[True, False])


def get_randomization_result(test_content: Dict[str, int],
                             df_sf: pd.DataFrame, test_size: int,
                             list_sf: List[str],
                             iteration: int = 10000) -> pd.DataFrame:
    """
    Get the randomization results

    :param test_content: A dictionary containing the number of exons in \
    the input list regulated by a splicing factor
    :param df_sf: The dataframe containing exons regulated by every \
    splicing factor
    :param list_sf: The list of splicing factor of interest
    :param test_size: The number of exons inside the input file
    :param iteration: The number of iteration to make, defaults to 10000
    :return: A dictionary containing the number of exon regulated by a
    splicing factor in each control group
    """
    logger.info("Performing permutations")
    dic_ctrl = perform_randomization(df_sf, test_size,
                                     list_sf, iteration)
    return get_all_pvalues(dic_ctrl, test_content, test_size, iteration)
