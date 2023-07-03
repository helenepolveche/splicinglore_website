<?php
include 'core_page/header.php';
include 'dbConfig.php';

unset($_POST);
// Si tout va bien, on peut continuer
?>

<fieldset style="float:left; height: 65%;">
<legend style="color: darkgreen; font-family: GeosansLight; font-size:150%; Font-Weight: Bold;">Input format</legend>
<p><u>With statistical values as input</u> ( Method 1) </p>
Each exon is defined by a <b>name</b> (eg. gene name_exon number), its chromosomal coordinates (<b>chromosome</b>, position of the <b>first</b> nucleotide, position of the <b>last</b> nucleotide)  and a <b>DeltaPSI</b> and <b><i>p</i>-value</b> (comma or dot). Values must be separated by a space or a semicolon. </br>
<div style ="font-family: monospace; font-size: small;">
UBE2CBP_2;6;83772872;83772964;0.78;0</br>
TRPC1_3;3;142462327;142462428;0.6;1.13E-14</br></div></br>
<p><u>Without statistical values</u> ( Method 2) </p>

List of exons defined by <b>chromosome</b> and <b> Chromosomal coordinates </b> ( FasterDB annotation ). Values must be separated by a space or a semicolon.
<div style ="font-family: monospace; font-size: small;">
6;83772872;83772964<br />
3;142462327;142462428<br />
</div>
</fieldset>

<fieldset style="float:right; margin-bottom: 10px; margin-right: 50px;"><!-- height: 40%;"> -->
<legend style="color: darkgreen; font-family: GeosansLight; font-size:150%; Font-Weight: Bold;">Exons to analyse ( Method 1 )</legend>

<form id="monFormGene" action="./results.php" method="post">
	<p id="box2">
	<textarea style ="font-family: monospace; font-size: small; width: 90%;" id="AREAexons" rows="8" cols="55" name="AREAexons" /></textarea></p>
	<p><input type="submit" value="Go" /></p>
	<p>List of differentially included exons for a given splicing factor: </p>
<?php
	$csvSelect = "<select name='files' id='liste'>";
	$csvSelect .= "<option value=''>--Choose a splicing factor--</option>";
	$dir = './csvfiles/*.csv';
        $files = glob($dir,GLOB_BRACE);
	foreach($files as $csv){
		$c= str_replace($dir,'',$csv);
		$title = str_replace('./csvfiles/',"", $c);
		$title = str_replace('.csv',"", $title);
		$csvSelect .= "<option value=".$title.">".$title."</option>" ;
	}
	$csvSelect .= "</select>";
	echo $csvSelect ;	
?>
	<br />
</form>
</fieldset>
<br />


<fieldset style="float:left; background-color:#11ffee00;border:0px;box-shadow: 0px 0px 0px; " >
</fieldset>

<fieldset style="float:right; margin-bottom: 20px; margin-right: 50px;"><!-- height: 40%;"> -->
<legend style="color: darkgreen; font-family: GeosansLight; font-size:150%; Font-Weight: Bold;">Exons to analyse ( Method 2 )</legend>

<form id="monFormGene" action="./resultsM2.php" method="post">
        <p id="box2">
        <textarea style ="font-family: monospace; font-size: small; width: 90%;" id="AREAexons2" rows="8" cols="55" name="AREAexons2" /></textarea></p>
        <p><input type="submit" value="Go" /></p>
        <br />
</form>


</fieldset>

<br />

<?php 
include 'core_page/footer.php';
?>

  <script src="./js/jquery-3.6.0.js"></script>
  <script src="./js/jquery-ui-1.13.1.js"></script>
  <script type="application/javascript" src="./js/stupidtable.min.js"></script>
<script>

$("select").change(function() {
	$('#AREAexons').html('');
	nameF = $(this).val() ;

	$.get('./csvfiles/' + nameF + '.csv', function(data) {    
	    var lines = data.split("\n");

	    $.each(lines, function(n, elem) {
	    $('#AREAexons').append(elem);
    		});
	});

});

</script>

