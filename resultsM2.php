<?php
include 'core_page/header.php';
include 'dbConfig.php';
include 'functions.php';


//$ExonsF = "";
$nbr_cells = "";
$ExonsF = "<table><tbody><tr><th>Exons</th></tr>" ;

unlink('./tmp/exons_list_M2.csv');

if (isset($_POST["AREAexons2"]) ){
	if ($_POST["AREAexons2"] != ""){
		$Exons = securisationSTR($_POST["AREAexons2"]);
		$Exons = str_replace(',', '.', $Exons);
		$Exons = str_replace(';', "\t", $Exons);
		//print_r($Exons) ;
		$retour_chariot = strpos($Exons, "\n");
		if ($retour_chariot != false ){			// Si une seule ligne, message error
			$EXs = explode("\n", $Exons);
			if ($EXs != ""){			// verification lignes non vide
				foreach($EXs as $line){
					file_put_contents('./tmp/exons_list_M2.csv', $line."\n", FILE_APPEND);
					$ExonsF .= "<tr><td> ".$line." </td></tr>";
				}
			}
		} else {
			$ExonsF .= "<tr>You only indicated one exon.</tr>";
		}
	} else {
		$ExonsF .= "<tr>Input value is empty.</tr>";
	}

$ExonsF .= "</tbody></table>";
 /* Affichage web */
?>

<div id='toTop'>&nbsp;&nbsp; To The Top &nbsp;&nbsp;</div>

<div id="tabs">
  <ul>
    <li><a href="#tabs-1">List of Exons</a></li>
    <li><a href="#tabs-2">Scores</a></li>
  </ul>
  <div id="tabs-1">
<?php

        echo $ExonsF;
?>
  </div>
  <div id="tabs-2">

<button type="button" style=" position: absolute; left: 8%; font-size: 12px;" id="export" onclick="tableToExcel('table-a-trier', 'Tableau Excel')" class="btn btn-primary reset-selection">Export (.xls)&nbsp;&nbsp;<i class="fa fa-cog fa-fw"></i></button>
<a id="dlink"  style="display:none;"></a>

<p align="justify" style="position: relative; left: 18%; width: 80%; font-size: 14px;">
<u>Permutation test.</u><br />

- <b>SF</b>: column contains the splicing factor considered.<br />
- <b>count</b>: Number of exons in the input list regulated by the splicing factor. <br />
- <b>freq</b>: Percentage of exons in the input list regulated by the splicing factor.<br />
- <b>count_ITERATION_ctrl</b>: Number of exons regulated by the splicing factor in control lists. <br />
- <b>freq_ITERATION_ctrl</b>:  Percentage of exons regulated by the splicing factor in control lists.<br />
- <b><i>p</i>-val</b>: The uncorrected p-value.<br />
- <b><i>p</i>-adj</b>: The corrected p-value using the Benjamini-Hotchberg procedure. <br />
- <b>reg</b>: Indicates if the input list is enriched in exons regulated by the splicing factor. <br />
- <b>Exons</b>: The list of exons given in input and regulated by a splicing factor.<br />

<div id="loading_div" style="display:none;">
<center><b style="color: #41ab5d;">Please be patient while the permutation test is being performed. </b><br /><br /><img src="./img/Spinner-3.gif" />
</center></div>
</p> 

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
        url: 'permut_scoreM2.php',
        type: "POST",
        beforeSend: function(){
         $("#loading_div").show();
       },
        success: function(retour){
            $('#loading_div').html(retour);
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
