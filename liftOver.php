<?php
include 'core_page/header.php';
include 'dbConfig.php';

unset($_POST);
// Si tout va bien, on peut continuer
?>
	<img style="max-width: 30%; height: auto; position: absolute; " src="./img/book4_3_petit.png" alt="">

        <fieldset style="margin-left: 30%; width: 65%; margin-bottom: 100px;">
		<legend style="color: darkgreen; font-family: GeosansLight; font-size:200%;">Protocol</legend>
		<p>To convert your <b>hg38 chromosome coordinates to FasterDB (hg19)</b>, enter the coordinates as in this example (fields separated by a <b>;</b> ).</br>
		Identifier (unique)<b>;</b>chromosome<b>;</b>start<b>;</b>end</br></p>
	
		<div style ="font-family: monospace; font-size: small;">
ZBTB48;chr1;6588201;6588277</br>
TOTO;chr15;85323482;85325782</br>
RAB11FIP3;chr16;503350;505523</br>
CAPN15;chr16;537298;546816</br>
ANYWORD1;chr16;855264;857037</br>
LMF1;chr16;881505;882079</br>
FOXK2;chr17;82571870;82585903</br>
ANYWORD2;chrX;154532802;154533575</br>
</div>		
		</br>
	<form id="monFormGene" action="./resultsliftOver.php" method="POST">
        	<p id="box2">
		<textarea style ="font-family: monospace; font-size: small; width: 90%;" id="AREAexons3" rows="8" cols="55" name="AREAexons3" /></textarea></p>
		<table style ="border: none;"><tbody><tr><td style ="border: none;">
			<input type="radio" id="hg38toFasterDB" name="sens" value="hg38toFasterDB" checked />
			<label for="hg38toFasterDB">hg38 to FasterDB</label>
		</td><td style ="border: none;">
			<input type="radio" id="FasterDBtohg38" name="sens" value="FasterDBtohg38" />
			<label for="FasterDBtohg38">FasterDB to hg38</label>
		</td></tr></tbody></table>
        	<p><input type="submit" value="Go" /></p>
        	<br />
	</form>
	</fieldset></br></br>

<?php
include 'core_page/footer.php';
?>
