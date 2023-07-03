<?php
include "./functions.php";
$tabb = "";
$butt = "";
if ( file_exists("./tmp/exons_list_M2.csv")){
	////Python_scripts/enrichmentNF_bash.sh
	unlink('./tmp/output_py/exons_list_M2_filter-sf-50-one_all.txt');
	exec("./Python_scripts/enrichmentNF_bash.sh"); 
	if (file_exists("./tmp/output_py/exons_list_M2_filter-sf-50-one_all.txt")){
	
		$tabb = "<script type='application/javascript' src='./js/stupidtable.min.js'></script>";
        	$tabb .= "<script>";
        	$tabb .= "$(document).ready(function(){";
        	$tabb .= "$('#table-a-trier').stupidtable()})";
		$tabb .= "</script>";

		$tabb .= "<table style ='font-size: small; table-layout:fixed; width:80%;' id='table-a-trier'><thead><tr><th data-sort='string' id='trierTH'>SF</th>";
		$tabb .= "<th data-sort='int' id='trierTH'>count</th>";
		$tabb .= "<th data-sort='float' id='trierTH'>freq</th>";
		$tabb .= "<th data-sort='float' id='trierTH'>count_10K_ctrl</th>";
		$tabb .= "<th data-sort='float' id='trierTH'>freq_10K_ctrl</th>";
		$tabb .= "<th data-sort='float' id='trierTH'>p-val</th>";
		$tabb .= "<th data-sort='float' id='trierTH'>p-adj</th>";
		$tabb .= "<th data-sort='string' id='trierTH'>reg</th>";
		$tabb .= "<th style='width: 200px;'>Exons</th>";
		$tabb .= "</tr></thead>";
		$tabb .= "<tbody>";

		$file = fopen("./tmp/output_py/exons_list_M2_filter-sf-50-one_all.txt", "r");
		$lineNumber = 1;
	
                while (!feof($file)) {
                        $row = fgets($file);
			if ($lineNumber != 1){
				//echo $row."<br>" ;
                                if ($row != ''){
                                        $tabb .= "<tr>";
                                        $cells = explode("\t", $row);
                                        $numb_cell = 0;
					foreach($cells as $c){
						//echo $numb_cell ;
						if ($numb_cell != 8 ){
							$tabb .= "<td>".$c."</td>" ;
						} else {
							$tabb .= "<td><div style='width: 200px; height: 50px; margin: 0; padding: 0; overflow: scroll;'>".$c."</div></td>" ;
						}
						$numb_cell++;

					}
					$tabb .= "</tr>";
				}
			}
			$lineNumber++;
		}
		fclose($file);
		$tabb .= "</tbody></table>";
		//echo $butt."<br /><br />" ;
		echo $tabb ; 
	} else {
	echo "An error has occurred.";
	}
} else {
	echo "Input value is empty.";
}

?>
