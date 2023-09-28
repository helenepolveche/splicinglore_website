#!/usr/bin/Rscript

## Script to create score betwwen in exons list query & SF list for significative siFactor skipping exons.
#args <- commandArgs(trailingOnly = TRUE)
#name_liste_exons <- args[ 1 ]
#name_Query <- args[ 2 ]
#date_dossier <- args[ 3 ]

## 2021-10-29
## Test de permutation de label

library(tidyverse)


ctrl.mat <- function(mat.initiale, vec.T, iter = 3){
  mat.initiale2 <- as_tibble(data.frame(exons = rownames(mat.initiale), mat.initiale))
  mat.initiale3 <- mat.initiale2 %>% 
    filter(exons %in% names(vec.T)) %>% 
    pivot_longer(!exons, names_to = "projects", values_to = "DeltaPSI") %>% 
    drop_na() %>% 
    group_by(exons) %>%
    sample_n(iter, replace = T) %>%
    mutate( iteration = rep( c(1:iter), 1) ) %>% 
    ungroup() %>%
    pivot_wider(id_cols = exons,names_from = iteration , values_from = DeltaPSI) 
  
  mat.init4 <- as.data.frame(mat.initiale3)
  rownames(mat.init4) <- mat.initiale3$exons
  mat.init4 <- as.matrix(mat.init4[, c(2:ncol(mat.init4)) ])
  return(mat.init4)
}

correlation.vecT.Ctrl <- function(mat.ctrl, vec.T, iter = 3){
  mat.Ctrl2 <- mat.ctrl[which(rownames(mat.ctrl) %in% names(vec.T)), ]
  vec.T <- vec.T[order(names(vec.T))]
  if( length(vec.T) == 1){
	cor.Pearson <- NA
  } else {
  	cor.Pearson <- array(cor(vec.T, mat.Ctrl2))
  }
  return(cor.Pearson)
}

cor.input.SFi <- function(vec.T, vec.SF){
  vec.SF.corr <- vec.SF[which(names(vec.SF) %in% names(vec.T))]
  vec.T2 <- vec.T[which(names(vec.T) %in% names(vec.SF.corr))]
  vec.T2 <- vec.T2[order(names(vec.T2))]
  vec.SF.corr <- vec.SF.corr[order(names(vec.SF.corr))]
  vec.leng <- length(vec.T2)
  cor.Pearson <- cor(vec.T2, vec.SF.corr)
  return(list("cor" = cor.Pearson, "size" = vec.leng, "vec" = vec.T2 ))
}

whatvalue <- function(list_pval){ 
  pval1or2 <- list_pval[1:2]
  pval_corr_anticorr <- pval1or2[which(pval1or2 %in% min(pval1or2))]
  names(pval_corr_anticorr) <- names(pval1or2[which(pval1or2 %in% min(pval1or2))])
  
  pval_corr_anticorr <- c(pval_corr_anticorr, list_pval[3] )
  
  if(length(pval_corr_anticorr[which(
    pval_corr_anticorr %in% max(pval_corr_anticorr))]) == 2 ) {
    return(c(pval_corr_anticorr["iteration"], 
             paste0("p-value < x / ", names(pval_corr_anticorr[1]))))
  } else if(names(pval_corr_anticorr[which(
    pval_corr_anticorr %in% max(pval_corr_anticorr))
  ]) == "iteration"){
    return(c(pval_corr_anticorr["iteration"], 
             paste0("p-value < x / ", names(pval_corr_anticorr[1]))))
  } else {
    return(c(pval_corr_anticorr[1], 
             paste0("p-value = x / ", names(pval_corr_anticorr[1]) )))
  }
}


commun.sig <- function(vec.T, vec.SF, name.SF, sig){
  # % d'exons sig en communs entre input et SF
  vec.SF.corr <- vec.SF[which(names(vec.SF) %in% names(vec.T))]
  mat.sig <- sig[which(rownames(sig) %in% names(vec.SF.corr)), name.SF]
  mat.sig <- na.omit(mat.sig)
  t.sig <- table(mat.sig)
  nbr.sig.commun <- as.numeric(t.sig["TRUE"])
  return(nbr.sig.commun)
}

# % du nbre d'exons retrouvé dans les exons sig vs tous les significatifs du SF



permutation.test.SFs <- function(mat.initiale, vec.T, iter = 8, sig){
  mat.ctrl <- ctrl.mat(mat.initiale = mat.initiale, vec.T = vec.T, iter = iter)
  SFs.pvalue <- as.data.frame(matrix(nc= 7, nr = ncol(mat.initiale)))
  colnames(SFs.pvalue) <- c("projects", "Score", "p.value", "direction", 
                            "#exons", "percent.common.sig", "percent.sig.SF")
  for (i in 1:ncol(mat.initiale)){
    vec.SF <- mat.sl[, i]
    names(vec.SF) <- rownames(mat.sl)
    vec.SF <- na.omit(vec.SF)
    SFs.pvalue[i, "projects"] <- colnames(mat.initiale)[i]
    #message(colnames(mat.initiale)[i])
    cor.QSF <- cor.input.SFi(vec.T, vec.SF)
    vec.cor.Ictrl <- correlation.vecT.Ctrl(mat.ctrl = mat.ctrl, 
                                           vec.T = cor.QSF$vec, iter = iter)
    
    SFs.pvalue[i, "#exons"] <- cor.QSF$size
    if ( is.na(vec.cor.Ictrl)){

	SFs.pvalue[i , "p.value"] <- NA
	SFs.pvalue[i , "direction"] <- "Exon commun < 1"
    	SFs.pvalue[i , "percent.common.sig"] <- NA
	SFs.pvalue[i , "percent.sig.SF"] <- NA

		
    } else {
    	pval_corr <-  sum(vec.cor.Ictrl >= cor.QSF$cor) / iter 
    	pval_anticorr <- sum(vec.cor.Ictrl <= cor.QSF$cor) / iter
    
    	list_pvals <- c(pval_anticorr, 
    	                pval_corr, 
    	                1/iter)
    	names(list_pvals) <- c("anticorrelate", 
                           "correlate", 
                           "iteration")
    	p.val <- whatvalue(list_pval = list_pvals)
    	SFs.pvalue[i , "p.value"] <- as.numeric(p.val[1])
    	SFs.pvalue[i , "direction"] <- p.val[2]
    
    	csig <- commun.sig(vec.T = cor.QSF$vec, vec.SF = vec.SF, 
                       name.SF = colnames(mat.initiale)[i], sig = sig)
    	SFs.pvalue[i , "percent.common.sig"] <- round(csig * 100 / length(vec.T), 2 )
    
    	SF.sig <- na.omit(sig[, colnames(mat.initiale)[i]])
    	true.SF.sig <- as.numeric(table(SF.sig)["TRUE"])
    	SFs.pvalue[i , "percent.sig.SF"] <- round(csig * 100 / true.SF.sig, 2 )
    
    	if(SFs.pvalue[i , "p.value"] <= 0.05){
    	  SFs.pvalue[i, "Score"] <- round(mean(c(1 - SFs.pvalue[i , "p.value"],
                                       SFs.pvalue[i , "percent.common.sig"] / 100,
                                       SFs.pvalue[i , "percent.sig.SF"] / 100 )),
    	                                  2)
    	} else {
    	  SFs.pvalue[i, "Score"] <- 0
    	}
    }
  }  
  return(SFs.pvalue)
}

mat.sl <- read.csv2("./Rscripts/SplicingLore_Ctrl_DeltaPSI.csv", 
                    header = T, row.names = 1, sep =",", dec = ".", 
                    stringsAsFactors = F)
mat.sl <- as.matrix(mat.sl)

sig.sl <- read.csv2("./Rscripts/SplicingLore_SFs_significant.csv", 
                    header = T, row.names = 1, sep =",", dec = ".", 
                    stringsAsFactors = F)

HNRNPC.raw <- read.csv2("./tmp/exons_list.csv", header = T, sep = ";", dec = ".")
HNRNPC <- as_tibble(HNRNPC.raw) %>% 
  mutate(exons = paste0(gene_symbol, "_", chr,":", start, "-", end)) %>% 
  select(exons, deltaPSI) 
vec.HNRNPC.query <- HNRNPC$deltaPSI
names(vec.HNRNPC.query) <- HNRNPC$exons

#head(vec.HNRNPC.query)

HNRNPC.permu.df <- permutation.test.SFs(mat.initiale = mat.sl,
                                        vec.T = vec.HNRNPC.query,
                                        iter = 10000,
                                        sig = sig.sl)
write.table(HNRNPC.permu.df[c(order(HNRNPC.permu.df$Score, decreasing = TRUE)),], 
           "./tmp/permutations_scores.csv", row.names = F, sep = ";", dec = ".")


#vec.T <- vec.HNRNPC.query[order(vec.HNRNPC.query)]
#sig.sl[is.na(sig.sl)] <- FALSE

