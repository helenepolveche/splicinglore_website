<?php
include "./functions.php";

if ( file_exists("./tmp/exons_list2.csv")){
	$tableResults = rcalcul();      //functions.php
	echo $tableResults ;
} else {
	echo "Input value is empty.";
}

?>
