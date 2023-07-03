<?php
include 'core_page/header.php';
include 'dbConfig.php';

unset($_POST);
// Si tout va bien, on peut continuer
?>
	<img style="max-width: 30%; height: auto; position: absolute;" src="./img/book4_3_petit.png" alt="">

        <fieldset style="margin-left: 30%; width: 65%;">
                <legend style="color: darkgreen; font-family: GeosansLight; font-size:200%;">Acknowledgements</legend>
                <p>
                <li><a style="color: darkgreen; font-family: GeosansLight;Font-Weight: Bold;" href='https://www.ncbi.nlm.nih.gov/geo/'>GEO</a> , an international public repository that archives and freely distributes microarray, next-generation sequencing, and other forms of high-throughput functional genomics data submitted by the research community.</li>
                <li> The <a style="color: darkgreen; font-family: GeosansLight;Font-Weight: Bold;" href='https://www.encodeproject.org/'>ENCODE</a> (Encyclopedia of DNA Elements) Consortium is an international collaboration of research groups funded by the National Human Genome Research Institute (NHGRI). The goal of ENCODE is to build a comprehensive list of functional elements in the human genome, including elements that act at the protein and RNA levels, and regulatory elements that control cells and circumstances in which a gene is active. </li>
                <li> We gratefully acknowledge support from the <psmn style="color: darkgreen; font-family: GeosansLight;Font-Weight: Bold;">PSMN</psmn> (P&ocirc;le Scientifique de Mod&eacute;lisation Num&eacute;rique) of the ENS de Lyon for the computing resources.</li>
                </p>
        </fieldset></br></br>
<div id='toTop'>&nbsp;&nbsp; To The Top &nbsp;&nbsp;</div>

<?php

$affichage = "<br />";
$query = $bdd->query("SELECT * FROM rnaseq_projects_SF, Cell_line WHERE rnaseq_projects_SF.id_cell_line=Cell_line.id_cell_line AND show_in_website = 1 ORDER BY project_name, name_CL;");
$query->execute();
$affichage .= "<table><thead><tr>";
$affichage .= "<th>Projects</th><th>Cell line</th><th>GEO Omnibus / ENCODE </th></tr></thead><tbody>";
foreach($query as $row){

        $affichage .= "<tr><td><a href='./csvfiles_data/".$row['project_name']."_".$row['id'].".csv'>".$row['project_name']."</a></td><td>".$row['name_CL']."</td><td> ".$row["db_id_project"]."</td></tr>";
}
$affichage .= "</tbody></table>";
echo $affichage ;
$query = null;
$bdd = null;



include 'core_page/footer.php';
?>

<script src="./js/jquery-3.6.0.js"></script>
<script src="./js/jquery-ui-1.13.1.js"></script>
<script>

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
