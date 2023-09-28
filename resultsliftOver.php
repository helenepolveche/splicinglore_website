<?php
include 'core_page/header.php';
include 'functions.php';

//unset($_POST);
// Si tout va bien, on peut continuer

$ExonsF = "";
$nbr_cells = "";
$tableResults = "";


// supression du fichier exons_list.csv et exonsl_list2.csv
array_map('unlink', glob('./tmp_lift/*.csv'));


if (isset($_POST["AREAexons3"]) ){
	//error_log("On est LAAAAAAAAAAAAAAAAAAAAAAAA !!");
        if ($_POST["AREAexons3"] != ""){
                $Exons = securisationSTR($_POST["AREAexons3"]);
                $Exons = str_replace(',', '.', $Exons);
                $retour_chariot = strpos($Exons, "\n");
                if ($retour_chariot != false ){                 // Si une seule ligne, message error
                        $EXs = explode("\n", $Exons);
                        if ($EXs != ""){                        // verification lignes non vide
                                $semicolons = strpos($Exons, ";");
                                if ($semicolons != false ){     // si les cellules sont bien separes par un ;
                                        file_put_contents('./tmp_lift/exons_list.csv', "desc;chr;start;end\n");
                                        foreach($EXs as $line){
                                                $EXl = explode(";", $line);
                                                if (count($EXl) == 4){
                                                        file_put_contents('./tmp_lift/exons_list.csv', $line."\n", FILE_APPEND);
                                                } else {
                                                        $nbr_cells .= implode(';',$EXl)."<br />";
                                                }
                                        }

                                } else {
                                        $ExonsF .= "Please separate the elements of the table with ';'.";
                                }
                        }
                } else {
                        $ExonsF .= "You only indicated one exon.";
                }
        } else {
                $ExonsF .= "Input value is empty.";
        }

	 if ($_POST["sens"] == "hg38toFasterDB"){
		 $sens = "Results liftOver hg38 to FasterDB (hg19) ";
		 $optsens = $_POST["sens"];

	 } else {
		 $sens = "Results liftOver FasterDB (hg19) to hg 38 ";
		 $optsens = $_POST["sens"] ;

	 }
?>
	<img style="max-width: 30%; height: auto; position: absolute; " src="./img/book4_3_petit.png" alt="">
	
        <fieldset style="margin-left: 30%; width: 65%; margin-bottom: 100px;">
	<legend style="color: darkgreen; font-family: GeosansLight; font-size:200%;"><?php echo $sens ; ?></legend>

	<?php echo $ExonsF; ?>
	<br />
	<button type="button" style=" position: absolute; left: 35%; font-size: 12px; background-color: #faf8f5;" id="export" onclick="tableToExcel('table-a-trier', 'Tableau Excel')" class="btn btn-primary reset-selection">Export (.xls)&nbsp;&nbsp;<i class="fa fa-cog fa-fw"></i></button>
<a id="dlink"  style="display:none;"></a>
	<br />
	<br />
<?php

	if ( file_exists("./tmp_lift/exons_list.csv")){
        	$tableResults = liftcalcul($optsens);      //functions.php
        	echo $tableResults ;
	} else {
	        echo "Input value is empty.";
	}



?>

	</fieldset></br></br>

<?php


} else { ## isset AREA exons
	error_log("on n'est PAS dans AREA exons 3  !!");
}
include 'core_page/footer.php';
?>

<script src="./js/jquery-3.6.0.js"></script>
<script src="./js/jquery-ui-1.13.1.js"></script>
<script type="application/javascript" src="./js/stupidtable.min.js"></script>
<script>

var tableToExcel = (function () {
            var uri = 'data:application/vnd.ms-excel;base64,'
                , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office"xmlns:x="urn:schemas-microsoft-com:office:excel"xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><!--[endif]--></head><body><table>{table}</table></body></html>'
                , base64 = function (s) { return window.btoa(unescape(encodeURIComponent(s))) }
                , format = function (s, c) { return s.replace(/{(\w+)}/g, function (m, p) { return c[p]; }) }
                return function (table, name) {
                if (!table.nodeType) table = document.getElementById(table)
                var ctx = { worksheet: name || 'Worksheet', table: table.innerHTML }
                //window.location.href = uri + base64(format(template, ctx))
                document.getElementById("dlink").href = uri + base64(format(template, ctx));
                document.getElementById("dlink").download = "liftover_splicinglore.xls";
                document.getElementById("dlink").click();
            }
})()

</script>



