<?php


function securisationSTR($req){
	$reqsec = htmlspecialchars($req); 	// pour sécuriser le formulaire contre les failles html ssx
	$reqsec = trim($reqsec);		// pour supprimer les espaces dans la requête de l'internaute
	$reqsec = strip_tags($reqsec);		// pour supprimer les balises html dans la requête
	$reqsec = addslashes($reqsec);		// Retourne la chaîne str, après avoir échappé tous les caractères qui doivent l'être, 
						//pour être utilisée. Ces caractères sont les guillemets simples ('), guillemets doubles ("), 
						//antislash (\) et NULLE (le caractère NULL). 

	return $reqsec;
}



function verifExons(){
	// "Mapping" des coordonnées chromosomiques des exons en INPUT dans la table Even_ASE a partir du fichier exons_list.csv
	// si abs -> ajout dans un fichier Abs.csv
	// si mauvais nom gene_exon -> ajout dans un fichier Bad_name.csv
	// Creation d'un nouveau fichier exons_list2.csv pour la suite des etapes
	// Affichage des fichiers Abs.csv et Bad_name.csv dans l'onglet "List Of Exons"
	
	// acces bdd
	include 'dbConfig.php';

	// coordonnes et nom d exons dans un array
	$select_exons = $bdd->prepare("SELECT DISTINCT gene_symbol, exon_skipped, coordonnees FROM Events_ASE");
	$select_exons->execute();
	$EXONS = array();
	while($exon_trouve = $select_exons->fetch()){
			$EXONS[$exon_trouve['coordonnees']][] = ''.$exon_trouve['gene_symbol'].'_'.$exon_trouve['exon_skipped'];
		//$EXONS[''.$exon_trouve['gene_symbol'].'_'.$exon_trouve['exon_skipped']] = $exon_trouve['coordonnees'] ; 
	}
	$select_exons->closeCursor();

	// coordonnee et non d exons dans la liste 
	$infos = "";
	$newCSV = "gene_symbol;chr;start;end;deltaPSI;p-value\n";
	//$query = array();
	$file = fopen("./tmp/exons_list.csv", "r");
	$lineNumber = 1;
	$newTable = "<table style='font-size: 14px;'><thead>";
	$newTable .= "<tr><th>Exon Name</th><th>chr</th><th>start</th><th>end</th>";
	$newTable .= "<th>DeltaPSI</th><th>p-value</th></tr>";
	$newTable .= "</thead><tbody>";
	while (!feof($file)) {
		$exon_name = "";
                $row = fgets($file, 1024);
		if ($lineNumber != 1){
			if ($row != ''){
				$cells = explode(";", $row);
				$exon_name = $cells[0];
				$coordonnate = $cells[1].":".$cells[2]."-".$cells[3] ;
				//$query[$coordonnate][] = $exon_name ;

				if (in_array($exon_name , $EXONS[$coordonnate])){
					$newCSV .= $row;
					$newTable .= "<tr><td>".$exon_name."</td>";
					$newTable .= "<td>".$cells[1]."</td>";
					$newTable .= "<td>".$cells[2]."</td>";
					$newTable .= "<td>".$cells[3]."</td>";
					$newTable .= "<td>".$cells[4]."</td>";
					$newTable .= "<td>".$cells[5]."</td></tr>";
				} else {
					if (array_key_exists($coordonnate ,$EXONS)) {
						$infos .= "- The name <b style='color:#E69F00;'>". $exon_name."</b> does not exist. The chromosomal coordinates <b style='color:#41ab5d;'>".$coordonnate."</b> indicate as exon name : <b style='color:#41ab5d;'>";
						$infos .= implode(" ", $EXONS[$coordonnate])."</b> <br />";
					} else {
						$infos .= "- There is no exon detected on these coordinates <b style='color:#E69F00;'>".$coordonnate." ( ".$exon_name." )</b> in our database.<br />";
					}
				}	
			}
		}
		$lineNumber ++;
	}
	fclose($file);
	$newTable .= "</tbody></table>";
	
	$infos .= "<br /><br />".$newTable."<br />" ;

	$file="./tmp/exons_list2.csv";
	$fp=fopen($file,"w" ); // ouverture du fichier 
	fputs($fp,$newCSV); // enregistrement des données ds le fichier 
	fclose($fp);


	return($infos);
}


function rcalcul(){
	// Execution du script R, calcul des scores de proximite
	unlink('./tmp/permutations_scores.csv'); //suppression de l'ancien fichier de resultats
	unlink('./tmp/permutations_details.csv'); //suppression de l'ancien fichier de resultats
        exec("R --vanilla < ./Rscripts/permutation_test_label.r");

	$tableResults = "<script type='application/javascript' src='./js/stupidtable.min.js'></script>";
	$tableResults .= "<script>";
	$tableResults .= "$(document).ready(function(){";
	$tableResults .= "$('#table-a-trier').stupidtable()})";
	$tableResults .= "</script>";

        // partie tableau de Score
        $tableResults .= "<table style ='font-size: small;' id='table-a-trier'><thead>";
        $tableResults .= "<tr><th data-sort='string' id='trierTH'>Projects</th>";
        $tableResults .= "<th data-sort='float' id='trierTH'>Score</th>";
        $tableResults .= "<th data-sort='float' id='trierTH'><i>p</i>-value</th>";
        $tableResults .= "<th data-sort='string' id='trierTH'>Direction</th>";
        $tableResults .= "<th data-sort='int' id='trierTH'>#exons</th>";
        $tableResults .= "<th data-sort='float' id='trierTH'>percent.sig.input</th>";
        $tableResults .= "<th data-sort='float' id='trierTH'>percent.sig.SF</th></tr></thead><tbody>";


	if (file_exists("./tmp/permutations_scores.csv")) {

	        $file = fopen("./tmp/permutations_scores.csv", "r");
	        $lineNumber = 1;
	        while (!feof($file)) {
	                $row = fgets($file, 1024);
			if ($lineNumber != 1){
				if ($row != ''){
	                        	$tableResults .= "<tr>";
					$cells = explode(";", $row);
					$numb_cell = 0;
					foreach($cells as $c){
					//echo "numb_cell = ".$numb_cell."<br />" ;
						if ($numb_cell == 1){
							$c2 = str_replace('"', "", $c);
							$c3 = 100 + (intval($c2*150));
							$tableResults .= "<td style='background: rgb(0,".$c3.",0);'>".$c2."</td>";
	
						} else if ($c == '"p-value < x / correlate"' || $c == '"p-value = x / correlate"'){
							$tableResults .= "<td style='background: #729FCF;'>".str_replace('"', "", $c)."</td>" ;
						} else if ($c == '"p-value < x / anticorrelate"' || $c == '"p-value = x / anticorrelate"') {
							$tableResults .= "<td style='background: #E8E2A2;'>".str_replace('"', "", $c)."</td>" ;
						} else {	
							$tableResults .= "<td>".str_replace('"', "", $c)."</td>" ;
						}
						$numb_cell++;	
	                        	}
					$tableResults .= "</tr>";
				}
			} 
	                $lineNumber++;
	        }
	        $tableResults .= "</tbody></table>";
	        fclose($file);
	} else  {
		$tableResults .= "<tr><td><b style='color: #E69F00;'>Warning : An error has occurred in your input data </b></td></tr>" ;
		$tableResults .= "</tbody></table>";
	}	
	return($tableResults);
}

//chargement du tableau ./tmp/permutations_details.csv
function tab_details(){

	$tableDet = "<script type='application/javascript' src='./js/stupidtable.min.js'></script>";
        $tableDet .= "<script>";
        $tableDet .= "$(document).ready(function(){";
        $tableDet .= "$('#table-a-trier2').stupidtable()})";
        $tableDet .= "</script>";

	// partie tableau de Score
	// style='writing-mode: vertical-rl; text-orientation: mixed;'
	$tableDet .= "<br /><table style ='font-size: x-small; width: 800px;' id='table-a-trier2'><thead>";
        $tableDet .= "<tr><th data-sort='string' id='trierTH'>Projects</th>";
        $tableDet .= "<th data-sort='float' id='trierTH'>UP <br /> % negative corr</th>";
        $tableDet .= "<th data-sort='float' id='trierTH'>UP <br /> % positive corr</th>";
        $tableDet .= "<th data-sort='int' id='trierTH'>UP <br /> #negative corr</th>";
	$tableDet .= "<th data-sort='int' id='trierTH'>UP <br /> #positive corr</th>";

	$tableDet .= "<th data-sort='float' id='trierTH'>DOWN <br /> % negative corr</th>";
        $tableDet .= "<th data-sort='float' id='trierTH'>DOWN <br /> % positive corr</th>";
        $tableDet .= "<th data-sort='int' id='trierTH'>DOWN <br /> #negative corr</th>";
	$tableDet .= "<th data-sort='int' id='trierTH'>DOWN <br /> #positive corr</th>";

	$tableDet .= "<th data-sort='int' id='trierTH'>#exons</th>";
	$tableDet .= "<th data-sort='int' id='trierTH'>#exons common <br /> significative</th>";
        $tableDet .= "<th data-sort='float' id='trierTH'> % common <br /> significative</th>";
	$tableDet .= "<th data-sort='float' id='trierTH'> % significative <br /> SplicingFactor</th></tr></thead><tbody>";


	if (file_exists("./tmp/permutations_details.csv")) {
	        $file = fopen("./tmp/permutations_details.csv", "r");
	        $lineNumber = 1;
	        while (!feof($file)) {
	                $row = fgets($file, 1024);
	                if ($lineNumber != 1){
	                        if ($row != ''){
	                                $tableDet .= "<tr>";
	                                $cells = explode(";", $row);
	                                $numb_cell = 0;
	                                foreach($cells as $c){
                                        //echo "numb_cell = ".$numb_cell."<br />" ;
                                        /*if ($numb_cell == 1){
                                                $c2 = str_replace('"', "", $c);
                                                $c3 = 100 + (intval($c2*150));
                                                $tableResults .= "<td style='background: rgb(0,".$c3.",0);'>".$c2."</td>";

					} else if ($c == '"p-value < x / correlate"' || $c == '"p-value = x / correlate"'){
                                                $tableResults .= "<td style='background: #729FCF;'>".str_replace('"', "", $c)."</td>" ;
                                        } else if ($c == '"p-value < x / anticorrelate"' || $c == '"p-value = x / anticorrelate"') {
                                                $tableResults .= "<td style='background: #E8E2A2;'>".str_replace('"', "", $c)."</td>" ;
					} else {*/
	                                        $tableDet .= "<td>".str_replace('"', "", $c)."</td>" ;
                                        //}
	                                        $numb_cell++;
	                                }
	                                $tableDet .= "</tr>";
	                        }
	                }
	                $lineNumber++;
	        }
	        $tableDet .= "</tbody></table>";
		fclose($file);
	} else {
		$tableDet .= "<tr><td><b style='color: #E69F00;'>Warning : An error has occurred in your input data </b></td></tr>" ;
                $tableDet .= "</tbody></table>";
	}	
	return($tableDet);
}




// creation des images de correlations:
function rgaphs(){

	array_map('unlink', glob('./tmp_img/*.png')); // suppression des anciennes images png
	//rmdir("./tmp_img/*");
	exec("R --vanilla < ./Rscripts/generate_graphs.r");

	$dir = './tmp_img/*.{jpg,jpeg,gif,png}';
        $files = glob($dir,GLOB_BRACE);
        $plots = "";
	$i = 0 ;
	//rename("./tmp_img/tmp_img/lib", "./tmp_img/lib");

        foreach($files as $image){
		$f= str_replace($dir,'',$image);
		//echo $f."<br />";
                $title = str_replace('./tmp_img/IMG_DeltaPSIQuery_vs_',"", $f);
                $title = str_replace('.png',"", $title);
                $plots .= "<button style='width: 25px; border-radius: 50%;' type=\"button\" onclick=\"toggle_div(this,'id_du_div".$i."');\">+</button> ".$title." :<br />";
		$plots .= "<div id='id_du_div".$i."' style='display: none;'>";
		$plots .= "<br /><a id='files' href='./tmp_img/DeltaPSIQuery_vs_".$title.".csv'> Download DeltaPSI file </a><br />";
		$plots .= "<a href='./tmp_img/IMG_DeltaPSIQuery_vs_".$title.".html' target='_blank'> Show graphics dynamicaly (full size) </a><br />";
		$plots .= "<center><img src='".$f."' alt=''>";
		$plots .= "</center></div><br /><br />";
		//$plots .= "<center><img src='".$f."' alt=''></center></div><br /><br />";
	$i++ ;
        }
        echo $plots;
}

?>
