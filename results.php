<?php
include 'core_page/header.php';
include 'dbConfig.php';
include 'functions.php';


$ExonsF = "";
$nbr_cells = "";

// supression du fichier exons_list.csv et exonsl_list2.csv
array_map('unlink', glob('./tmp/*.csv'));


if (isset($_POST["AREAexons"]) ){
	if ($_POST["AREAexons"] != ""){
		$Exons = securisationSTR($_POST["AREAexons"]);
		$Exons = str_replace(',', '.', $Exons);
		$retour_chariot = strpos($Exons, "\n");
		if ($retour_chariot != false ){			// Si une seule ligne, message error
			$EXs = explode("\n", $Exons);
			if ($EXs != ""){			// verification lignes non vide
				$semicolons = strpos($Exons, ";");
				if ($semicolons != false ){	// si les cellules sont bien separes par un ;
					file_put_contents('./tmp/exons_list.csv', "gene_symbol;chr;start;end;deltaPSI;p-value\n");
					foreach($EXs as $line){
						$EXl = explode(";", $line);
						if (count($EXl) == 6){
							file_put_contents('./tmp/exons_list.csv', $line."\n", FILE_APPEND);
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


 /* Affichage web */
?>

<div id='toTop'>&nbsp;&nbsp; To The Top &nbsp;&nbsp;</div>

<div id="tabs">
  <ul>
    <li><a href="#tabs-1">List of Exons</a></li>
    <li><a href="#tabs-2">Scores</a></li>
    <li><a href="#tabs-3">Graphics</a></li>
    <li><a href="#tabs-4">Details Up/Down</a></li>
  </ul>
  <div id="tabs-1">
<?php

        echo $ExonsF;
	if ( file_exists("./tmp/exons_list.csv")){
        	if ($nbr_cells != ""){
        	        echo "- The number of cells is <b style='color:#E69F00;'>not equal to 6</b> for the row(s): <br />";
        	        echo $nbr_cells;
        	        echo "<br />";
		}
                $ASE = verifExons();
                echo $ASE ;

	}
?>
  </div>
  <div id="tabs-2">
	
<button type="button" style=" position: absolute; left: 8%; font-size: 12px;" id="export" onclick="tableToExcel('table-a-trier', 'Tableau Excel')" class="btn btn-primary reset-selection">Export (.xls)&nbsp;&nbsp;<i class="fa fa-cog fa-fw"></i></button>
<a id="dlink"  style="display:none;"></a>

<p align="justify" style="position: relative; left: 18%; width: 80%; font-size: 14px;">
<u>Permutation test.</u><br />

- <b><i>p</i>-value</b>: We use the Pearson correlation between the ΔPSI of two sets of exons. The computed empirical p-value is the probability of observing a Pearson correlation as high or higher as the observed correlation when considering a set of randomly associated exons. This probability was computed as the empirical cumulative distribution function generated by computing the Pearson correlation for 1e10^4 random set of exons (which leads to a maximal p-value resolution of 1e10^-4).<br />
- <b>percent.sig.input</b>: The fraction of significantly regulated exons from the Input list of exons that are found in the list of exons regulated by the indicated splicing factor. <br />
- <b>percent.sig.SF</b>: The fraction of significantly regulated exons from the list of exons regulated by the indicated splicing factor  that are found in  the Input list of exons.<br />
- <b>Score</b>: It was set up to facilitate the identification of candidate splicing factors. It is linked to a permutation statistic test, which also gives a p-value, the « percent.sig.input » and the « percent.sig.SF ». The score is between 0 and 1. The closer the score is to 1, the more the effect of the splicing factor is correlated or anti-correlated to the regulation of the list of  exons.<br />

<div id="loading_div" style="display:none;">
<center><b style="color: #41ab5d;">Please be patient while the permutation test is being performed. </b><br /><br /><img src="./img/Spinner-3.gif" />
</center></div>
</p>



  </div>
  <div id="tabs-3">
	<p>- <b style='color: #999999;'>not statistically significant in condition of splicing factor knock down.</b><br />
	- <b style='color: #E69F00;'>statistically significant in condition of splicing factor knock down. ( |DeltaPSI| >= 0.1, <i>p</i>-value <= 0.05 ) </b></p>

	<div id="loading_div2" style="display:none;">
	<center><b style="color: #41ab5d;">Please be patient while Graphics are being generated. </b><br /><br /><img src="./img/Spinner-3.gif" />
	</center></div>
  </div>


  <div id="tabs-4">

<button type="button" style=" position: absolute; left: 8%; font-size: 12px;" id="export" onclick="tableToExcel('table-a-trier2', 'Tableau Excel')" class="btn btn-primary reset-selection">Export (.xls)&nbsp;&nbsp;<i class="fa fa-cog fa-fw"></i></button>
<a id="dlink"  style="display:none;"></a><br /><br />

	<div id="loading_div3" style="display:none;">
	<center><b style="color: #41ab5d;">Please be patient while analyses are running. </b><br /><br /><img src="./img/Spinner-3.gif" />
        </center>
	</div>
  </div>
</div>
<br /><br />
	


<?php
}

include 'core_page/footer.php';
?>

  <script src="./js/jquery-3.6.0.js"></script>
  <script src="./js/jquery-ui-1.13.1.js"></script>
  <script type="application/javascript" src="./js/stupidtable.min.js"></script>
  <script>
  $( function() {
    $( "#tabs" ).tabs();
  } );


//$("#table-a-trier").stupidtable();

$(document).ready(function(){
	//$("#table-a-trier").stupidtable();


	$.ajax({
        url: 'permut_score.php',
        type: "POST",
        beforeSend: function(){
         $("#loading_div").show();
       },
        success: function(retour){
            $('#loading_div').html(retour);
         }
       });


        $.ajax({
        url: 'generate_graphs.php',
        type: "POST",
        beforeSend: function(){
         $("#loading_div2").show();
       },
        success: function(retour){
            $('#loading_div2').html(retour);
         }
       });

	        $.ajax({
        url: 'details_up_down.php',
        type: "POST",
        beforeSend: function(){
         $("#loading_div3").show();
       },
        success: function(retour){
            $('#loading_div3').html(retour);
         }
       });


});

// montrer / cacher avec un bouton
function toggle_div(bouton, id) { // On dÃ©clare la fonction toggle_div qui prend en param le bouton et un id
                var div = document.getElementById(id); // On rÃ©cupÃ¨re le div ciblÃ© grÃ¢ce Ã  l'id
                if(div.style.display=="none") { // Si le div est masquÃ©...
                        div.style.display = "block"; // ... on l'affiche...
                        bouton.innerHTML = "-"; // ... et on change le contenu du bouton.
                } else { // S'il est visible...
                        div.style.display = "none"; // ... on le masque...
                        bouton.innerHTML = "+"; // ... et on change le contenu du bouton.
                }
}

/*$(document).ready(function($) {
	$("#table-a-trier").stupidtable();
});*/

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
		document.getElementById("dlink").download = "SplicingLore.xls";
                document.getElementById("dlink").click();
            }
})()

$(window).scroll(function() {
    if ($(this).scrollTop()) {
        $('#toTop').fadeIn();
    } else {
        $('#toTop').fadeOut();
    }
});

$("#toTop").click(function () {
   //1 second of animation time
   //html works for FFX but not Chrome
   //body works for Chrome but not FFX
   //This strange selector seems to work universally
   $("html, body").animate({scrollTop: 0}, 1000);
});
</script>
