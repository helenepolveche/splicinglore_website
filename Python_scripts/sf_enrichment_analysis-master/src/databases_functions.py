#!/usr/bin/env python3

# -*- coding: UTF-8 -*-

import doctest
from pathlib import Path
from typing import Callable, Dict, Tuple, List
from .config import Config
import pandas as pd
from rich import progress as pr
from loguru import logger
import pymysql
from .conf import Conf 

def connection():
    """
    :return (pymysql object to connected to the SplicingLore BDD
    """
    cnx = pymysql.connect(user=Conf.user, password=Conf.password, host=Conf.sl_host, database=Conf.fasterdb)
    return cnx


def get_pbar():
    """
    Create a progress bar using rich package
    """
    return pr.Progress(
        pr.TextColumn("[progress.description][cyan]{task.description}"),
        pr.BarColumn(bar_width=None),
        pr.TaskProgressColumn(), "•",
        pr.MofNCompleteColumn(), "• [cyan]⧖",
        pr.TimeRemainingColumn(), "• [orange3]",
        pr.TimeElapsedColumn())


def get_projects_links_to_a_splicing_factor(cnx,
                                            sf_name: str) -> List[str]:
    """
    Get the ID of every projects corresponding to a particular splicing factor.

    :param cnx: connexion to the ChIA-PET database
    :param sf_name: the splicing factor name, e.g: PTBP1
    :return id_project: a list of id_project (table cin_project_splicing_lore),
    corresponding to a particular splicing factor. E.g: [7, 30, 96, 135]
    """
    cursor = cnx.cursor()
    query = f"""SELECT id
               FROM cin_project_splicing_lore
               WHERE sf_name = '{sf_name}' """

    cursor.execute(query)
    res = cursor.fetchall()

    return [val[0] for val in res if val[0] not in [244, 245, 249, 251, 260, 263]]


def get_ase_events(cnx, id_project: str
                   ) -> List[Tuple[str, int, str]]:
    """
    Get every exons regulated (down or up) according to a particular project.

    :param cnx: connexion to the ChIA-PET database
    :param id_project: a project ID of the table cin_project_splicing_lore
    :return nres: each sublist corresponds to an exon (exon_regulation +
    gene_id + exon_position on gene), e.g: ['down', 18673, '18673_17']
    """
    cursor = cnx.cursor()
    query = f"""SELECT delta_psi, gene_id, exon_id
               FROM cin_ase_event
               WHERE id_project = '{id_project}'
               AND (delta_psi >= 0.1 OR delta_psi <= -0.1)
               AND pvalue_glm_cor <= 0.05"""
    #print(query)
    cursor.execute(query)
    res = cursor.fetchall()

    if len(res) == 0:
        query = f"""SELECT delta_psi, gene_id, exon_id
                       FROM cin_ase_event
                       WHERE id_project = '{id_project}'
                       AND (delta_psi >= 0.1 OR delta_psi <= -0.1)
                       AND pvalue <= 0.05"""
        cursor.execute(query)
        res = cursor.fetchall()

    nres = []
    for exon in res:
        nexon = list(exon[1:3])
        ntuple = ("down" if exon[0] < 0 else "up",
                  int(nexon[0]), str(nexon[1]))
        nres.append(ntuple)
    return nres


def washing_events(exon_list: List[Tuple[str, int, str]]) -> List[List[str]]:
    """
    Remove redundant exons or remove exons showing different regulation.

    :param exon_list: each sublist corresponds to an exon (exon_regulation +
    gene_id + exon_position on gene), e.g: ['down', 18673, '18673_17']
    Every exon regulated by a splicing factor in different projects.
    :return new_exon_list: each sublist corresponds to an exon (exon_regulation
    + gene_id + exon_position on gene), e.g: ['down', '18962', '18962', '14'].
    Every exon regulated by a splicing factor in different projects without
    redundancy.
    """
    replace_dic = {"up": "down", "down": "up"}
    dic: Dict[str, int] = {}
    prefix_list = []
    for exon in exon_list:
        exon_prefix = f"{exon[1]}_{exon[2]}"
        exon_name = f"{exon[0]}_{exon_prefix}"
        if exon_name in dic:
            dic[exon_name] += 1

        elif exon_prefix not in prefix_list:
            dic[exon_name] = 1
            prefix_list.append(exon_prefix)
        else:
            reverse_name = exon_name.replace(exon[0], replace_dic[exon[0]])
            if reverse_name in dic:
                del (dic[reverse_name])
    new_exon_list = []
    for key in dic:
        my_exon = key.split("_")
        new_exon_list.append(my_exon)
    return new_exon_list


def get_every_events_4_a_sl(cnx, sf_name: str,
                            regulation: str) -> List[str]:
    """
    Get every splicing events for a given splicing factor.

    :param cnx: connexion to the ChIA-PET database
    :param sf_name: the splicing factor name, e.g: PTBP1
    :param regulation: up or down
    :return sf_reg: a dictionary with a list of regulated exons depending on a
    splicing factor and its regulation, e.g: {'PTBP1_up': ['345_38', '681_2',
    '781_4', '1090_16', '1291_12']}
    :return: List of exons regulated by the splicing factor
    """
    exons_list = []
    id_projects = get_projects_links_to_a_splicing_factor(cnx, sf_name)
    #print(id_projects, sf_name)

    for id_project in id_projects:
        ase_event = get_ase_events(cnx, id_project)
        exons_list += ase_event
        #print(id_project, ase_event)

    washed_exon_list = washing_events(exons_list)
    #print(washed_exon_list)
    #exit(1)
    if regulation in {"up", "down"}:
        return [f"{exon[2]}_{exon[3]}" for exon in washed_exon_list
                if exon[0] == regulation]
    else:
        return [f"{exon[2]}_{exon[3]}" for exon in washed_exon_list]


def get_sfname(
        output: Path, regulation: str = "one", threshold: int = 50) -> List[str]:
    """
    Function that return the splicing factor names in the ChIA-PET database \
    that up or down-regulates at least `threshold` exons.

    :param output: Folder where the results will be created
    :param regulation: up, down, all, one. If regulation is up or down, \
    then the function returns only the splicing factor names up or down \
    regulating at least threshold exons. If 'all' is chosen then \
    only splicing factor that up-regulates and down-regulates at least \
    `threshold` exons are returned. If 'one' is chosen, then \
    the splicing factor must have at least `threshold` up-regulated exons OR \
    down-regulated exons.
    :param threshold: The minimum number of  exons that must be regulated \
    by the splicing factor
    :return: The list of splicing factor regulating at least threshold \
    exons

    """
    outfile = output / \
        f"sf_list_reg-{regulation}_t{threshold}.txt"
    if outfile.is_file():
        return outfile.open("r").read().strip(r"[|]").replace(
            "'", "").split(", ")
    cnx = connection()
    c = cnx.cursor()
    query = "SELECT DISTINCT Splicing_factors.name FROM rnaseq_projects_SF, Splicing_factors WHERE rnaseq_projects_SF.id_SF=Splicing_factors.id_SF AND rnaseq_projects_SF.show_in_website = 1"    #"SELECT DISTINCT sf_name from cin_project_splicing_lore"
    c.execute(query)
    res = [sf[0] for sf in c.fetchall()]
    return res #get_good_factors(cnx, res, outfile, regulation, threshold,
                #            get_every_events_4_a_sl)


def get_all_exons(cnx) -> List[str]:
    """
    Gets all exons defined in the database cnx

    :param cnx: A connection to chia-pet network database
    :return: All exons defined in cnx
    """
    c = cnx.cursor()
    query = "SELECT DISTINCT id FROM cin_exon"
    c.execute(query)
    return [e[0] for e in c.fetchall()]


def create_a_complete_regulation_table(
        output: Path, regulation: str = "one", threshold: int = 50, reg:
        str = "down") -> pd.DataFrame:
    """
    Create a regulating table indicating for each splicing factor if an exon \
    is regulated by it

    :param output:  Folder where the results will be created
    :param regulation: :param regulation: up, down, all, one. If regulation \
    is up or down, \
    then the function returns only the splicing factor names up or down \
    regulating at least threshold exons. If 'all' is chosen then \
    only splicing factor that up-regulates and down-regulates at least \
    `threshold` exons are returned. If 'one' is chosen, then \
    the splicing factor must have at least `threshold` up-regulated exons OR \
    down-regulated exons, defaults to 'one'
    :param threshold: The minimum number of exons that should be regulated by
    the factor to display it , defaults to 50
    :return: A dataframe containing the exons and their regulation for every \
    splicing factor

    >>> r = create_a_complete_regulation_table()
    >>> r[["DAZAP1", "SRSF1"]].sum()
    DAZAP1     555
    SRSF1     1366
    dtype: int64
    >>> r.loc[r["DAZAP1"] == 1,["exons", "DAZAP1", "SRSF1"]].head()
             exons  DAZAP1  SRSF1
    447    10038_2       1      0
    656    10053_2       1      0
    968    10076_7       1      0
    1305  10102_32       1      0
    1598   10127_7       1      0
    """
    cnx = connection()
    exons = get_all_exons(cnx)
    sf_names = get_sfname(output, regulation, threshold)
    df = pd.DataFrame({"exons": exons})
    progress = get_pbar()
    with progress:
        for sfn in progress.track(sf_names, description="Getting SF events"):
            events = get_every_events_4_a_sl(cnx, sfn, reg)
            df[sfn] = [0] * df.shape[0]
            df.loc[df["exons"].isin(events), sfn] = 1
    cnx.close()
    return df


def recover_sf_table(output: Path, regulation: str = "one",
                     threshold: int = 50,
                     reg: str = "down") -> pd.DataFrame:
    """
    Create a regulating table indicating for each splicing factor if an exon \
    is regulated by it

    :param output:  Folder where the results will be created
    :param regulation: :param regulation: up, down, all, one. If regulation \
    is up or down, \
    then the function returns only the splicing factor names up or down \
    regulating at least threshold exons. If 'all' is chosen then \
    only splicing factor that up-regulates and down-regulates at least \
    `threshold` exons are returned. If 'one' is chosen, then \
    the splicing factor must have at least `threshold` up-regulated exons OR \
    down-regulated exons, defaults to 'one'
    :param threshold: The minimum number of exons that should be regulated by
    the factor to display it , defaults to 50
    :return: A dataframe containing the exons and their regulation for every \
    splicing factor
    """
    noutput = output / "sf_files"
    noutput.mkdir(exist_ok=True)
    outfile = noutput / f"SF_{reg}_{threshold}_{regulation}_table.txt"
    if outfile.is_file():
        logger.info("Recovering table of exons regulated by SF")
        df = pd.read_csv(outfile, sep="\t")
    else:
        logger.info("Creating table of exons regulated by SF")
        df = create_a_complete_regulation_table(noutput,
                                                regulation, threshold, reg)
        df.to_csv(outfile, sep="\t", index=False)
    return df


if __name__ == "__main__":
    doctest.testmod()
