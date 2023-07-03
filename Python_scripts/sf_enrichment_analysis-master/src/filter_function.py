#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

"""
Description: Contains functions needed to filter exons in permutation data
"""


from pathlib import Path
import pymysql

from typing import List, Optional
import pandas as pd
from loguru import logger
from .config import Config
from .conf import Conf


def connection():
    """
    :return (pymysql object to connected to the SplicingLore BDD
    """
    cnx = pymysql.connect(user=Conf.user, password=Conf.password, host=Conf.sl_host, database=Conf.fasterdb)
    return cnx



class KindError(Exception):
    pass


def filter_exon_coord(cnx, df: pd.DataFrame) -> List[str]:
    """
    Return the list of exons selected from exons coordinates

    :param cnx: Connection to database
    :param df: A dataframe containing exons
    :return: The list of selected exons
    """
    query = "SELECT id, chromosome, start, stop FROM cin_exon"
    df_exon = pd.read_sql_query(query, cnx)
    df_exon["coord"] = df_exon["chromosome"].astype(str) + ":" + \
        df_exon["start"].astype(str) + "-" + df_exon["stop"].astype(str)
    df["coord"] = df["chromosome"].astype(
        str) + ":" + df["start"].astype(str) + "-" + df["stop"].astype(str)
    df_exon = df_exon[df_exon["coord"].isin(df["coord"].values)]
    dup = df_exon.duplicated(subset="coord").sum()
    if dup > 0:
        logger.warning(
            f"{dup} exons will be lost because they share the "
            "same coordinates of at least one exons in your filter file")
        df_exon = df_exon.drop_duplicates(keep=False, subset="coord").copy()
    iexon = len(df["coord"].unique())
    lexon = len(df_exon["coord"].unique())
    if iexon != lexon:
        logger.warning(f"{iexon - lexon} exons lost during exons recovering")
    logger.info(f"{lexon} exons to keep for permutation analyses")
    return df_exon["id"].to_list()  # type: ignore


def filter_exon_name(
        cnx, exon_names: List[str],
        reverse: bool = False) -> List[str]:
    """
    Return the list of exon ids selected from exon names

    :param cnx: Connection to database
    :param exon_names: The list of exons names
    :param reverse: If true the function return the name of exons from their \
    ids.
    :return: The list of selected exon ids
    """
    query = "SELECT id, name FROM cin_exon"
    id_col, name_col = "id", "name"
    if reverse:
        name_col, id_col = "id", "name"
    df_exon = pd.read_sql_query(query, cnx)
    df_exon = df_exon[df_exon[name_col].isin(exon_names)]
    iexon = len(exon_names)
    lexon = len(df_exon[id_col].unique())
    if not reverse:
        if iexon != lexon:
            logger.warning(
                f"{iexon - lexon} exons lost during exons recovering")
        logger.info(f"{lexon} exons to keep for permutation analyses")
    return df_exon[id_col].to_list()  # type: ignore


def add_regulated_gene_columns(df_sf: pd.DataFrame, exon_list: List[str]
                               ) -> pd.DataFrame:
    """
    Creates a dataframe linking each factor to the list of genes inside \
    the input and regulated by it

    :param df_sf: A dataframe containing the list exons regulated by \
    each splicing factor
    :param exon_list: The list of exons of interest
    :return: A dataframe linking each factor to the list of genes inside \
    the input and regulated by it
    """
    cnx =connection()
    df_sf = df_sf[df_sf["exons"].isin(exon_list)].copy()
    sf_list = [c for c in df_sf.columns if c != "exons"]
    dic = {"exon_ireg": [], "SF": []}
    for sf in sf_list:
        tmp = filter_exon_name(cnx,
                               df_sf.loc[df_sf[sf] == 1, "exons"].tolist(),
                               True)
        dic["exon_ireg"].append(", ".join(tmp))
        dic["SF"].append(sf)
    return pd.DataFrame(dic)


def filter_gene_name(
        cnx, gene_names: List[str]) -> List[str]:
    """
    Return the list of exon ids selected from gene names

    :param cnx: Connection to database
    :param gene_names: The list of exons names
    :return: The list of selected exon ids
    """
    query = """SELECT t1.id, t1.id_gene, t2.name FROM cin_exon t1, cin_gene t2
            WHERE t2.id = t1.id_gene"""
    df_exon = pd.read_sql_query(query, cnx)
    df_exon = df_exon[df_exon["name"].isin(gene_names)]
    lexon = len(df_exon["id"].unique())
    logger.info(f"{lexon} exons to keep for permutation analyses")
    return df_exon["id"].to_list()  # type: ignore


def filter_gene_id(
        cnx, gene_ids: List[int]) -> List[str]:
    """
    Return the list of exon ids selected from gene names

    :param cnx: Connection to database
    :param gene_ids: The list of gene ids
    :return: The list of selected exon ids
    """
    query = "SELECT id, id_gene FROM cin_exon"
    df_exon = pd.read_sql_query(query, cnx)
    df_exon = df_exon[df_exon["id_gene"].isin(gene_ids)]
    lexon = len(df_exon["id"].unique())
    logger.info(f"{lexon} exons to keep for permutation analyses")
    return df_exon["id"].to_list()  # type: ignore


def find_filter_kind(cnx, mfile: Path) -> List[str]:
    """
    Find the type of data contained in filter file

    :param cnx: Connection to database
    :param mfile: A file containing gene/exons in different format
    :return: The exons_id selected
    """
    content = mfile.open("r").read().splitlines()
    tmp = content[0]
    if len(tmp.split("\t")) == 4:
        df = pd.read_csv(
            mfile, names=["name", "chromosome", "start", "stop"],
            sep="\t")
        return filter_exon_coord(cnx, df)
    elif len(tmp.split("\t")) > 1:
        raise KindError(f"Unknown kind of data for {mfile.name} file")
    stmp = tmp.split("_")
    if len(stmp) == 2:
        return content if stmp[0].isdigit() else filter_exon_name(cnx, content)
    return filter_gene_id(
        cnx, list(map(int, content))) if tmp.isdigit() else filter_gene_name(
        cnx, content)


def filter_sf_dataframe(df_sf: pd.DataFrame, mfile: Optional[Path]
                        ) -> pd.DataFrame:
    """
    If a file mfile is set, filter df_sf to only keep exons defined in mfile

    :param df_sf: A dataframe containing exons and their regulation by all \
    splicing lore splicing factors
    :param mfile: A file containing genes or exons. Exons in this files or \
    exons withing genes in this file will be kept for permutation analyses
    :return: Return df_sf filtered
    """
    if mfile is None:
        return df_sf
    cnx = connection()
    selected_exons = find_filter_kind(cnx, mfile)
    cnx.close()
    return df_sf[df_sf["exons"].isin(selected_exons)].copy()


def read_input(mfile: Path) -> List[str]:
    """
    Get the list of exons given in input

    :param mfile: A file containing exons
    :return: The list of exons given in input
    """
    content = mfile.open("r").read().splitlines()
    first_size = len(content[0].split("\t"))
    cnx = connection()
    if first_size == 1:
        scontent = content[0].split("_")
        if len(scontent) != 2:
            raise KindError(
                f"Wrong exon format given in the file {mfile.name}")
        if scontent[0].isdigit():
            return content
        res = filter_exon_name(cnx, content)
        cnx.close()
        return res
    elif first_size in [4, 3]:
        if first_size == 4:
            columns = ["name", "chromosome", "start", "stop"]
        else:
            columns = ["chromosome", "start", "stop"]

        df = pd.read_csv(
            mfile, names=columns,
            sep="\t")
        df["start"] -= 1
        res = filter_exon_coord(cnx, df)
        cnx.close()
        return res
    else:
        raise KindError(f"The input file {mfile.name} has a wrong format")
