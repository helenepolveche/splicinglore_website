#!/usr/bin/Rscript

## script to generate all graphics DeltaPSI of exons between Query and SF projects
## 2022-03-22


message("Start Generate Graphics HTML")

#message(Sys.getenv())
Sys.setenv(HOME="/home/hpolvech/")
message("\n     \n")
#message(Sys.getenv())


library(tidyverse)
library(plotly)#, lib ="../../doc/4.1")
library(htmlwidgets)#, lib ="../../doc/4.1")

mat.sl <- read.csv2("./Rscripts/SplicingLore_Ctrl_DeltaPSI.csv",
                    header = T, row.names = 1, sep =",", dec = ".",
                    stringsAsFactors = F)
mat.sl <- as.matrix(mat.sl)

sig.sl <- read.csv2("./Rscripts/SplicingLore_SFs_significant.csv",
                    header = T, row.names = 1, sep =",", dec = ".",
                    stringsAsFactors = F)

HNRNPC.raw <- read.csv2("./tmp/exons_list2.csv", header = T, sep = ";", dec = ".")
HNRNPC <- as_tibble(HNRNPC.raw) %>%
  mutate(exons = paste0(gene_symbol, "_", chr,":", start, "-", end)) %>%
  select(exons, deltaPSI)
vec.HNRNPC.query <- HNRNPC$deltaPSI
names(vec.HNRNPC.query) <- HNRNPC$exons

vec.T <- vec.HNRNPC.query[order(vec.HNRNPC.query)]
sig.sl[is.na(sig.sl)] <- FALSE

#i <- 2
myColors <- c("#E69F00", "#999999")
names(myColors) <- c( TRUE, FALSE)
#colScale<- scale_colour_manual(name = "significant", values = myColors)

for (i in 1:ncol(mat.sl)){
        #message(i)
        df.sl <- merge(as.data.frame(mat.sl[which(rownames(mat.sl) %in% names(vec.T)), i]),
               as.data.frame(vec.T), by = "row.names")
        rownames(df.sl) <- df.sl$Row.names
        df.sl <- df.sl[,c(2:ncol(df.sl))]
        df.sl <- merge(na.omit(df.sl), as.data.frame(sig.sl),
               by = "row.names")
        rownames(df.sl) <- df.sl$Row.names
        df.sl <- df.sl[,c(2,3, i + 3)]
        colnames(df.sl) <- c(colnames(mat.sl)[i],"Query", "significant")

	        p <- ggplot(data = df.sl, mapping = aes(x = Query, y = df.sl[,1], label = rownames(df.sl), color = significant)) +
          geom_point() +
          geom_hline(yintercept = 0) +
          geom_vline(xintercept = 0) +
          theme_minimal() +
          #theme(legend.position="none") +
          ylab(paste0( colnames(df.sl)[1], " (DeltaPSI)")) +
          xlab("Query (DeltaPSI)") +
          xlim(-1, 1) + ylim(-1, 1) +
          scale_colour_manual(name = "significant", values = myColors)

        png(paste0("./tmp_img/IMG_DeltaPSIQuery_vs_",colnames(mat.sl)[i], ".png"))
        print(p)
        dev.off()

  	ly <- ggplotly(p)
	htmlwidgets::saveWidget(as_widget(ly), 
				file = 	paste0("./tmp_img/IMG_DeltaPSIQuery_vs_",colnames(mat.sl)[i], ".html"),
				selfcontained=TRUE) # , libdir = "./tmp_img/lib") #,colnames(mat.sl)[i]))
	 
	write.table(df.sl, paste0("./tmp_img/DeltaPSIQuery_vs_",colnames(mat.sl)[i], ".csv"), dec = ".", sep = ";", row.names = TRUE)
}
message("Generate Graphics - Done")

