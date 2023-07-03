# SplicingLore: a web resource for studying the regulation of cassette exons by human splicing factors

2023-06-30 

Helene Polveche, Jessica Valat, Nicolas Fontrodona, Audrey Lapendry, Stephane Janczarski, Franck Mortreux, Didier Auboeuf, Cyril F Bourgeois 

doi: https://doi.org/10.1101/2023.06.30.547181 

https://splicinglore.ens-lyon.fr/index.php 

## Installation 

### Debian
```
sudo apt install libcurl4-openssl-dev libxml2-dev libssl-dev pandoc libfontconfig1-dev libharfbuzz-dev libfribidi-dev libfreetype6-dev libpng-dev libtiff5-dev libjpeg-dev librsvg2-dev libpq-dev  libudunits2-dev unixodbc-dev libproj-dev libgdal-dev libcairo2-dev libxt-dev 

sudo apt install build-essential zlib1g-dev libncurses5-dev libgdbm-dev libnss3-dev libreadline-dev libffi-dev libsqlite3-dev libbz2-dev
```

### R packages ( 4.2 )
```r
install.packages("tidyverse", lib = "/usr/local/lib/R/site-library", dependencies = T) 
install.packages("plotly", lib = "/usr/local/lib/R/site-library", dependencies = T) 
install.packages("htmlwidgets", lib = "/usr/local/lib/R/site-library", dependencies = T) 
```

### Python ( 3.10 )
```
pip3.10 install numpy panda loguru
pip3.10 install lazyparser statsmodels rich pymysql
```


