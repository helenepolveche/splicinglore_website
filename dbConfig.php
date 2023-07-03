<?php
//Database credentials
$dbHost     = 'XXXX';
$dbUsername = 'XXXX';
$dbPassword = 'XXXX';
$dbName     = 'XXXX';

try{
//Connect and select the database
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
$bdd ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(Exception $e)
{
        // En cas d'erreur, on affiche un message et on arrête tout
        die('Erreur : '.$e->getMessage());
}
?>
