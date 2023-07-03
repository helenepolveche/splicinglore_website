# SF enrichment program

This program is dedicated to check if an input list of human exons is enriched (using a randomization test) in exons regulated by Splicing Factors

# Prerequisites


**The database generated with [this program](https://gitbio.ens-lyon.fr/LBMC/regards/chia-pet_network/-/tree/dev) must be placed in the data folder of the project directory and be named sl.db**

This program was developped with python 3.10 and requires the following modules:

* numpy (1.22.3)
* pandas (v1.4.2)
* loguru (v0.6.0)
* lazyparser (0.2.1)
* statsmodels (0.13.2)
* rich

# Usage

## Without docker image

To display the program help, enter:

```sh
python -m src -h
```

This should display:

```
usage: __main__.py [-h] -i STR [-I INT] [-F STR] [-t INT] [-r STR] [-f STR] [-o STR] [-l STR]

Perform an enrichment analysis for all splicing factors

Optional arguments:
  -h, --help             show this help message and exit
  -I, --iteration INT    Number of iteration to perform, defaults to 10000
  -F, --filter_reg STR   Choose from [up, down, all, one]. If regulation is up or down, then only splicing factors
                         regulating at least threshold exons with this regulation are kept. If 'all' is chosen then only
                         splicing factor that up-regulates and down-regulates at least `threshold` exons are returned.
                         If 'one' is chosen, then splicing factors must have at least `threshold` up-regulated exons OR
                         down-regulated exons, defaults to "one"
  -t, --threshold INT    Threshold used to filter splicing factors, defaults to 50
  -r, --reg STR          Choose from 'all', 'up', 'down'. Use for enrchiment analysis: Consider only exons having those
                         regulations, defaults to "down"
  -f, --filter_file STR  param filter_file
  -o, --output STR       folder where the output fillebe created
  -c, --create_ireg_col  Create the column exon_ireg in the output folder, defaults to False
  -l, --level STR        The level of data to display

Required arguments:
  -i, --input_file STR   A file containing exons in fasterDB id format (e.g 10_1 for the first exons of gene 10)
```

The `input_file` parameter can have 3 formats:

1. The fasterDB exon id formed by the gene id and the exon position within the gene:
```
1_1
15_20
78_2
```
2. The gene name followed by the position of the exons within the gene
```
DSC1_1
DSC1_2
```
3. A tab-separated file containing, an exon name and the chromosome hosting it and its start and end coordinates (in FasterDB annotation). To get the FasterDB start and stop coordinates of your exons you can visit https://fasterdb.ens-lyon.fr/faster/home.pl.
```
DSC2_1	18	28681865	28682388
DSC2_2	18	28681183	28681432
DSC2_3	18	28673521	28673606
DSC2_4	18	28672063	28672263
DSC2_5	18	28671489	28671530
DSC2_6	18	28670990	28671110
```

The `iteration` parameter is used to defined the number of control lists of exons (having the same size as the input list) to generate. Those lists will be used as a background for the tests.

The `reg` parameter contains the regulation of exons by a splicing factor to consider. If set to `down`, then only exons that are less included when the splicing factor is not expressed are considered. If set to `up`, then only exons that are more included when the splicing factor is not expressed are considered. If set to `all`, Then every exons whose inclusion changes in the absence of a splicing factor are considerede.

The `filter_file` is a file containing genes or exons. If this file contains exons, then only those exons are used to build the control sets. It it contains genes then all exons within those genes are used to build the control sets. The filter file can have the same format as those presented for the `input_file` parameter. For genes it can have two format:
1. It can contains the list of FasterDB genes id to consider:
```
1
2
3
```
2. It can contains the list of gene names to consider
```
DSC2
DSC1
DSG1
```

The `output` contains the folder where the results will be created.

# Output description

The result format has the following structure:

| SF          | count | freq  | count\_10K\_ctrl | freq\_10K\_ctrl | p-val      | p-adj      | reg | exon_ireg   |
| ----------- | ----- | ----- | ---------------- | --------------- | ---------- | ---------- | --- | ----------- |
| DDX5\_DDX17 | 53    | 53.54 | 1.9587           | 1.98            | 1.00E-04   | 0.00016665 | +   | NRSN2_3 ... |
| U2AF1       | 40    | 40.40 | 1.8087           | 1.83            | 1.00E-04   | 0.00016665 | +   | C20orf...   |
| CELF1       | 0     | 0.00  | 0.0344           | 0.03            | 0.96630337 | 0.99110089 | .   | ...         |
| SAFB2       | 0     | 0.00  | 0.0137           | 0.01            | 0.98650135 | 0.99110089 | .   | ...         |
| SART3       | 0     | 0.00  | 0.03             | 0.03            | 0.97020298 | 0.99110089 | .   | ...         |
| SMNDC1      | 0     | 0.00  | 0.0293           | 0.03            | 0.97160284 | 0.99110089 | .   | ...         |
| TAF15       | 0     | 0.00  | 0.0202           | 0.02            | 0.97990201 | 0.99110089 | .   | ...         |

* `SF` column contains the splicing factor considered
* `count`: Number of exons in the input list regulated by the splicing factor
* `freq`: Percentage of exons in the input list regulated by the splicing factor
* `count_ITERATION_ctrl`: Number of exons regulated by the splicing factor in control lists
* `freq_ITERATION_ctrl`: Percentage of exons regulated by the splicing factor in control lists
* `p-val` The uncorrected p-value
* `p-adj`: The corrected p-value using the Benjamini-Hotchberg procedure
* `reg`: Indicates if the input list is enriched in exons regulated by the splicing factors
* `exon_ireg`: The list of exons given in input and regulated by a splicing factor. This column is present only if the flag `-c, --create_ireg_col` is used.

# With docker image

To download the docker image run:

```
docker pull nfontrodona/sf-exon_enrichment:latest
```

Then to run the program's help run:

```
docker container run --rm -it -v $PWD:$PWD --workdir $PWD nfontrodona/sf-exon_enrichment python3 -m script.src -h
```

This should display the same help message as above. 
