<?php
// tietokantayhteys
include 'yhteys.php'; 

$log_file = "php.log";
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', $log_file);

$nytaika = time();
$nyt = date("Y-m-d H:i:s", $nytaika);


if ($_POST['toiminto'] == "poistatiedosto")
{
    
    $tiedostoid = $_POST['tiedostoid'];
    
    $poisto = $yhteys_pdo->prepare("DELETE FROM $tietokanta.kuvat WHERE id = :rivi");
    $poisto->execute(array(':rivi'=>$tiedostoid));

    $palautus['pois'] = "tiedostorivi".$tiedostoid;

    echo json_encode($palautus); 
	exit;

}

if ($_POST['toiminto'] == "uusitiedosto")
{
   
    $kt = $_POST['kayttaja'];
    $kategoria = $_POST['kategoria'];
    $annettunimi = $_POST['uunimi'];
    
    $juuri = "kuvat/";
    $nimi = $_FILES['uutiedosto']['name'];
    $tmp = $_FILES['uutiedosto']['tmp_name'];



    $ext = strtolower(pathinfo($nimi, PATHINFO_EXTENSION));
    $uusinimi = $nytaika."_".strtolower($nimi);

    if ($annettunimi == '')
        $annettunimi = $nimi;
    else
        $annettunimi = $annettunimi.".".$ext;

    $polku = $juuri.$uusinimi;

    if(move_uploaded_file($tmp,$polku)) 
    {
        $lisays = $yhteys_pdo->prepare("INSERT INTO $tietokanta.kuvat(kategoria, nimi, polku, paate, lisatty, lisaaja) values (?,?,?,?,?,?)");
        $lisays->execute(array($kategoria, $annettunimi, $polku, $ext, $nyt, $kt));

        $rivi = $yhteys_pdo->lastInsertId();

        $jono = "<div class=\"col s6 m3 l2\" id=\"tiedostorivi".$rivi."\">
                    <div class=\"card\">
                    <div class=\"card-image\">
                        <img src=\"".$polku."\">
                     
                    </div>
                    <div class=\"card-content\">
                        <p>".$annettunimi."</p>
                    </div>
                    <div class=\"card-action\">
                        <i class=\"material-icons modal-trigger\" id=\"muokkaa".$rivi."\" data-target=\"modal1\" style=\"cursor: pointer\" >refresh</i>
                        <i class=\"material-icons\" id=\"poista".$rivi."\" style=\"cursor: pointer\">delete_forever</i>
                    </div>
                    </div>
                </div>";             
        

    }

    $palautus['jono'] = $jono;
    $palautus['kategoria'] = $kategoria;
    echo json_encode($palautus); 
	exit;

}

if ($_POST['toiminto'] == "paivitatiedosto")
{
    $tiedostoid = $_POST['tiedostoid'];
    $kt = $_POST['kayttaja'];
    
    $juuri = "kuvat/";
    $nimi = $_FILES['tiedosto']['name'];
    $tmp = $_FILES['tiedosto']['tmp_name'];

    $ext = strtolower(pathinfo($tiedosto, PATHINFO_EXTENSION));
    $uusinimi = $nytaika."_".strtolower($nimi);

    // haetaan vanha polku
    $haku = $yhteys_pdo->prepare("SELECT polku, paate FROM $tietokanta.kuvat WHERE id = :rivi");
    $haku->execute(array(':rivi'=>$tiedostoid));
    $ha = $haku->fetch(PDO::FETCH_ASSOC);

    if ($ha['paate'] == $ext) // eli pääte täsmää
        $polku = $ha['polku'];
    else
        $polku = $juuri.$uusinimi;

    if(move_uploaded_file($tmp,$polku)) 
    {
        $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.kuvat SET polku = :polku, lisatty = :nyt, paate = :paate, lisaaja = :kayttaja WHERE id = :rivi");
        $paivitys->execute(array(':polku'=>$polku, ':nyt'=>$nyt, ':paate'=>$ext, ':kayttaja'=>$kt, ':rivi'=>$tiedostoid)); 

    }

    $palautus['jono'] = $tiedostoid;
    echo json_encode($palautus); 
	exit;

}
if ($_POST['toiminto'] == "muokkaussisalto")
{
    $tiedostoid = $_POST['tiedostoid'];    

    $haku = $yhteys_pdo->prepare("SELECT nimi, paate, date_format(lisatty, '%d.%m.%Y') as lisatty FROM $tietokanta.kuvat WHERE id = :rivi");
    $haku->execute(array(':rivi'=>$tiedostoid));
    $ha = $haku->fetch(PDO::FETCH_ASSOC);

    $jono = "<div class=\"row\"><div class=\"col s12\"><p style=\"margin-top: 0px\">Haluatko varmasti päivittää kuvan<br /><b><i>".$ha['nimi'].".".$ha['paate']."</i></b><br />joka on lisätty ".$ha['lisatty']."?</p><p>Jos haluat päivittää kuvan, valitse uusi tiedosto</p></div>
    
        <div class=\"col s12\">
            <form accept-charset=\"multipart/form-data\" id=\"tiedostopaivitys\">
                <input type=\"hidden\" id=\"tiedostoid\" name=\"tiedostoid\" value=\"".$tiedostoid."\">
                <div class=\"file-field input-field\">
                <div class=\"btn\">
                    <span>Tiedosto</span>
                    <input type=\"file\" name=\"tiedosto\" id=\"tiedosto\">
                </div>
                <div class=\"file-path-wrapper\">
                    <input class=\"file-path validate\" type=\"text\">
                </div>
                </div>
            </form>
        </div>";
    $palautus['jono'] = $jono;
    echo json_encode($palautus); 
	exit;
}