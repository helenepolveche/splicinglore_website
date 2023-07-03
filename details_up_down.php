<?php
include "./functions.php";

if ( file_exists("./tmp/exons_list2.csv")){
	sleep(10);
	$n = 1 ;
	while(!file_exists("./tmp/permutations_details.csv")){
		sleep(5);
		#echo $n."<br />";
		#$n++;	
	}

	$resu = tab_details();

	#echo "Helloooooo" ;

	echo $resu ;
} else {
	echo "Input value is empty.";

}
?>
