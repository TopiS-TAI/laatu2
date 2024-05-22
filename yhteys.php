<?php

//$tietokkayttaja = "laatu";
//$tietoksalasana = "sdhNasjsd%gs22V";
//$tietokanta = "laatu";

//Muutos koodiin
//Muutos koodiin2

$tietokkayttaja = "root";
$tietoksalasana = "";
$tietokanta = "mikanlaatu";

$osoite = '127.0.0.1';

try {
	    $yhteys_pdo = new PDO("mysql:host=$osoite; dbname=$tietokanta", $tietokkayttaja, $tietoksalasana,
	        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	    $yhteys_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
catch (PDOException $e) {
	    die("Virhe! : " . $e->getMessage());
	}


?>