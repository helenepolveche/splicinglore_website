#!/bin/bash


echo $(date) >> ./tmp/infos_NF_log.log


#. ./Python_scripts/py310_sl/bin/activate
echo "Hi !!!" >> ./tmp/infos_NF_log.log
export PYTHONPATH="$PYTHONPATH:./Python_scripts/sf_enrichment_analysis-master/"
echo $PYTHONPATH >> ./tmp/infos_NF_log.log

#pip3 install numpy pandas loguru  # 1.22.3 # 1.4.2 # 0.6.0
#pip3 install lazyparser statsmodels rich pymysql    # 0.2.1 # 0.13.2 

python3 -m src -c -i "./tmp/exons_list_M2.csv" -r 'all' -o "./tmp/output_py/"

#deactivate
