<?php

$log_file = "php.log";
error_reporting(E_ALL & ~E_NOTICE); 
ini_set('log_errors', TRUE);  
ini_set('error_log', $log_file);

// tietokantayhteys
include 'yhteys.php';

// korjataan tietokannasta tyyliongelmat


$luettelo = $yhteys_pdo->prepare("SELECT id, sisalto FROM $tietokanta.sis");
$luettelo->execute();

while ($lu = $luettelo->fetch(PDO::FETCH_ASSOC))
{
    $sis = $lu['sisalto'];
    /*
    $pois = array("font-size: 12pt;","font-size: 14pt;","color: #95a5a6;","color: #7e8c8d;","background-color: #ffffff;","<span style=\"color: #95a5a6;\">",
    "<span style=\"font-size: 12pt;\">","<span style=\"font-size: 14pt;\">","<a style=\"color: #95a5a6;\">","<p> </p>","style=\"\"","<h3>","</h3>","<h2>","</h2>","<h1>","</h1>",
    "color: #000 color: #000","<a href=","<a  href=","<li style=´\"font-size: 12px\">");
    
    $tilalle   = array("","","","","","<span>","<span>","<span>","<a>","","","<p>","</p>","<p>","</p>","<h1>","</h1>","color: #000","<a style=\"color: #000; text-decoration: underline;\" href=",
    "<a style=\"color: #000; text-decoration: underline;\" href=","<li>");
    */
    $pois = array('<span style="text-decoration: underline; color: #000;">','<span style="text-decoration: underline; color: #000">');
    $tilalle = array('<span>','<span>');
    $sis = str_replace($pois, $tilalle, $sis);

    $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.sis SET sisalto = :sis WHERE id = :rivi");
    $paivitys->execute(array(':sis'=>$sis, ':rivi'=>$lu['id']));
}

echo "Valmis!";