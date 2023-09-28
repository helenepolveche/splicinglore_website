## if (!requireNamespace("BiocManager", quietly = TRUE))
##   install.packages("BiocManager", lib = "/usr/local/lib/R/site-library", dependencies=T)
## BiocManager::install("rtracklayer", lib = "/usr/local/lib/R/site-library", dependencies=T)

## if (!require("BiocManager", quietly = TRUE))
##   install.packages("BiocManager", lib = "/usr/local/lib/R/site-library", dependencies=T)
## BiocManager::install("liftOver", lib = "/usr/local/lib/R/site-library", dependencies=T)

#library(rtracklayer)
library(liftOver)
library(tidyverse)

### tiny data ###
# https://pubmed.ncbi.nlm.nih.gov/36601126/
# Table 2

dt <- read.csv2(file = "./tmp_lift/exons_list.csv", header = T, sep = ";")
#colnames(dt) <- c("desc", "chr","start", "end")

grDt <- makeGRangesFromDataFrame(dt, na.rm=TRUE, keep.extra.columns=TRUE)

## ----getch--------------------------------------------------------------------

#path = system.file(package="liftOver", "extdata", "./data/hg38ToHg19.over.chain")

# import the chain file
ch <- import.chain("./Rscripts/hg38ToHg19.over.chain")
#ch
#str(ch[[1]])


# run liftOver
results <- as.data.frame(liftOver(grDt, ch)) %>% 
  mutate(seqnames = as.character(seqnames) ) %>% 
  mutate(chr = str_sub(seqnames, 4,nchar(seqnames)))

bed <- results[,c("chr", "start", "end", "desc")]
write.table(bed, "./tmp_lift/bedExons.bed", col.names = FALSE, sep = "\t", 
            row.names = FALSE, quote = FALSE)


# bedtools intersect -wb -a ./results/bedExons.bed -b ./data/exons_genes_fasterdb.bed > ./results/infos_exons_to_FasterDB.bed

