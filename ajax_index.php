<?php
include 'yhteys.php';

$log_file = "php.log";
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', $log_file);

if (session_status() == PHP_SESSION_NONE) { 
	session_start();
}

$nytaika = time();
$nyt = date("Y-m-d H:i:s", $nytaika); 
$virhe = 0;
$tunniste = "";

if ($_POST['toiminto'] == "kirjaudu") 
{
    $kt = $_POST['kt'];
    $ss = $_POST['ss'];

    if ($kt <> '' && $ss <> '')
    {
        $haku = $yhteys_pdo->prepare("SELECT id, tunniste FROM $tietokanta.kayttajat WHERE ktunnus = :ktunnus AND ssana = :salasana");
        $haku->execute(array(':ktunnus'=>$kt, ':salasana'=>$ss));
        $ha = $haku->fetch(PDO::FETCH_ASSOC);

        if (isset($ha['tunniste']))
        { 
            $_SESSION['kt'] = $ha['id'];
            $_SESSION['tunniste'] = $ha['tunniste'];

            $tunniste = $ha['tunniste'];
            $palautus['osoite'] = 'hallinta.php';            
        }
        else
        {
            $virhe = 1;
        }

    }
    else
    {
        $virhe = 1;
    }
    
    $palautus['tunniste'] = $tunniste;
    $palautus['virhe'] = $virhe;
    echo json_encode($palautus);
    exit;

}

