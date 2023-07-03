<?php
include "./functions.php";

if ( file_exists("./tmp/exons_list2.csv")){
	$graphs = rgaphs();
	echo $graphs ;
} else {
	echo "Input value is empty.";
}

?>
