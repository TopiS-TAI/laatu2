<?php
// tietokantayhteys
include 'yhteys.php'; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$kt=			$_SESSION["kt"];
$tunniste=		$_SESSION["tunniste"];

$log_file = "php.log";
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', $log_file);

$nytaika = time();
$nyt = date("Y-m-d H:i:s", $nytaika);


if ($_POST['toiminto'] == "poistatiedosto")
{
    
    $tiedostoid = $_POST['tiedostoid'];
    
    $poisto = $yhteys_pdo->prepare("UPDATE $tietokanta.tiedostot set poistettu = '1' WHERE id = :rivi");
    $poisto->execute(array(':rivi'=>$tiedostoid));

    $palautus['pois'] = "tiedostorivi".$tiedostoid;

    echo json_encode($palautus); 
	exit;

}

if ($_POST['toiminto'] == "uusitiedosto")
{
   
    $kategoria = $_POST['kategoria'];
    $annettunimi = $_POST['uunimi'];
    $kommentti = $_POST['kommentti'];

    $annettunimi2 = $_POST['uunimi2'];
    
    $juuri = "tiedostot/";

    // master -tiedosto
    $nimi = $_FILES['uutiedosto']['name'];
    $tmp = $_FILES['uutiedosto']['tmp_name'];
    $ext = strtolower(pathinfo($nimi, PATHINFO_EXTENSION));
    $uusinimi = $nytaika."_".strtolower($nimi);

    $pois = array("ä", "ö", "å");
    $tilalle   = array("a", "o", "a");
    $uusinimi = str_replace($pois, $tilalle, $uusinimi);

    if ($annettunimi == '')
        $annettunimi = $nimi;
    else
        $annettunimi = $annettunimi.".".$ext;

    $polku = $juuri.$uusinimi;
 
    // julkaistava tiedosto
    $nimi2 = $_FILES['uutiedosto2']['name'];
    $tmp2 = $_FILES['uutiedosto2']['tmp_name'];
    $ext2 = strtolower(pathinfo($nimi2, PATHINFO_EXTENSION));
    $uusinimi2 = $nytaika."_".strtolower($nimi2);

    $uusinimi2 = str_replace($pois, $tilalle, $uusinimi2);

    if ($annettunimi2 == '')
        $annettunimi2 = $nimi2;
    else
        $annettunimi2 = $annettunimi2.".".$ext;
 
    $polku2 = $juuri.$uusinimi2;

    $master = 0;
    $julkaistava = 0;

    if(move_uploaded_file($tmp, $polku)) 
    {
        $lisays = $yhteys_pdo->prepare("INSERT INTO $tietokanta.tiedostot(kategoria, master_nimi, master_polku, master_paate, master_kommentti, lisatty, lisaaja) values (?,?,?,?,?,?,?)");
        $lisays->execute(array($kategoria, $annettunimi, $polku, $ext, $kommentti, $nyt, $kt));

        $rivi = $yhteys_pdo->lastInsertId();

        $master = 1;
    }

    if(move_uploaded_file($tmp2, $polku2)) 
    {
        if ($rivi <> '')
        {
            $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.tiedostot SET nimi = ?, polku = ?, paate = ? WHERE id = ?");
            $paivitys->execute(array($annettunimi2, $polku2, $ext2, $rivi));
        }
        else
        {
            $lisays = $yhteys_pdo->prepare("INSERT INTO $tietokanta.tiedostot(kategoria, nimi, polku, paate, lisatty, lisaaja) values (?,?,?,?,?,?)");
            $lisays->execute(array($kategoria, $annettunimi2, $polku2, $ext2, $nyt, $kt));

            $rivi = $yhteys_pdo->lastInsertId();
        }

        $haku = $yhteys_pdo->prepare("SELECT DATE_FORMAT(t.lisatty, '%d.%m.%Y') as lisatty, k.sukunimi, k.etunimi FROM $tietokanta.tiedostot t 
        LEFT JOIN $tietokanta.kayttajat k ON k.id = t.lisaaja
        WHERE t.id = ?");
        $haku->execute(array($rivi));
       
        $ti = $haku->fetch(PDO::FETCH_ASSOC);

        if($kommentti == '')
            $kommentti = "[ ei kommenttia ]";

        $jono = "
            <li class=\"collection-item\" id=\"tiedostorivi".$rivi."\" style=\"padding-bottom: 0px !important;\">
                <div class=\"row\">
                    <div class=\"col s4\">
                        <span class=\"title\">Julkaistu tiedosto</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.9em;\"><a href=\"".$polku2."\" target=\"_blank\">".$annettunimi2."</a></p>
                    </div>
                    <div class=\"col s4\">
                        <span class=\"title\">Master -tiedosto</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.9em;\"><a href=\"".$polku."\" target=\"_blank\">".$annettunimi."</a></p>
                    </div>
                    <div class=\"col s3\">
                        <span class=\"title\" style=\"font-size: 0.9em\">".$kommentti."</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.8em;\">".$ti['sukunimi']." ".$ti['etunimi'].", ".$ti['lisatty']."</p>
                    </div>
                    <div class=\"col s1\">
                        <br />
                        <i class=\"material-icons modal-trigger\" id=\"muokkaa".$rivi."\" data-target=\"modal1\" style=\"cursor: pointer\" >refresh</i>
                        <i class=\"material-icons\" id=\"poista<?php echo $rivi;?>\" style=\"cursor: pointer\">delete_forever</i>
                    </div>
                </div>                                        
            </li>";

        $julkaistava = 1;
    }

    // jono -muuttujaan tallennetaan mitä näytetään 

    $palautus['jono'] = $jono;
    $palautus['kategoria'] = $kategoria;
    echo json_encode($palautus); 
	exit;

}

if ($_POST['toiminto'] == "paivitatiedosto")
{
    $tiedostoid = $_POST['tiedostoid'];
    
    $juuri = "tiedostot/";
    $nimi = $_FILES['tiedosto']['name']; // julkaistavan tiedoston nimi
    $tmp = $_FILES['tiedosto']['tmp_name'];
    $nimi2 = $_FILES['tiedosto2']['name']; // master -tiedoston nimi
    $tmp2 = $_FILES['tiedosto2']['tmp_name'];

    $muunimi = $_POST['muunimi']; // julkaistun tiedoston näkyvä nimi jos muutettu
    $muunimi2 = $_POST['muunimi2']; // master -tiedoston näkyvä nimi jos muutettu

    $kommentti = $_POST['kommentti'];

    // haetaan vanha polku
    $haku = $yhteys_pdo->prepare("SELECT polku, master_polku, paate, master_paate FROM $tietokanta.tiedostot WHERE id = :rivi");
    $haku->execute(array(':rivi'=>$tiedostoid));
    $ha = $haku->fetch(PDO::FETCH_ASSOC);

    // tallennetaan julkaistava tiedosto (jos lomakkeella)

        $ext = strtolower(pathinfo($nimi, PATHINFO_EXTENSION));

        if ($muunimi == '')
            $annettunimi = $nimi;
        else
            $annettunimi = $muunimi.".".$ext;

        $pois = array("ä", "ö", "å"," ");
        $tilalle   = array("a", "o", "a","");
        $annettunimi = str_replace($pois, $tilalle, $annettunimi);

        $uusinimi = $nytaika."_".strtolower($annettunimi); // tiedoston tallennusnimi

        if ($ha['paate'] == $ext) // eli pääte täsmää
            $polku = $ha['polku'];
        else
            $polku = $juuri.$uusinimi;

        if(move_uploaded_file($tmp,$polku)) 
        {
            $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.tiedostot SET nimi = :nimi, polku = :polku, lisatty = :nyt, paate = :paate, lisaaja = :kayttaja WHERE id = :rivi");
            $paivitys->execute(array(':nimi'=>$annettunimi, ':polku'=>$polku, ':nyt'=>$nyt, ':paate'=>$ext, ':kayttaja'=>$kt, ':rivi'=>$tiedostoid)); 
        }

    // tallennetaan master -tiedosto (jos lomakkeella)

        $ext2 = strtolower(pathinfo($nimi2, PATHINFO_EXTENSION));        

        $uusinimi2 = $nytaika."_".strtolower($nimi2);

        $uusinimi2 = str_replace($pois, $tilalle, $uusinimi2);

        if ($muunimi2 == '')
            $annettunimi2 = $nimi2;
        else
            $annettunimi2 = $muunimi2.".".$ext2;

        if ($ha['master_paate'] == $ext2) // eli pääte täsmää
            $polku2 = $ha['master_polku'];
        else
            $polku2 = $juuri.$uusinimi2;

        if(move_uploaded_file($tmp2,$polku)) 
        {
            $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.tiedostot SET master_nimi = :nimi, master_polku = :polku, lisatty = :nyt, master_paate = :paate, lisaaja = :kayttaja WHERE id = :rivi");
            $paivitys->execute(array(':nimi'=>$annettunimi2, ':polku'=>$polku2, ':nyt'=>$nyt, ':paate'=>$ext2, ':kayttaja'=>$kt, ':rivi'=>$tiedostoid)); 
        }   

    // tallennetaan kommentti
        if ($kommentti <> '')
        {
            $paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.tiedostot SET master_kommentti = :kommentti WHERE id = :rivi");
            $paivitys->execute(array(':kommentti'=>$kommentti, ':rivi'=>$tiedostoid));         
        }

    $haku = $yhteys_pdo->prepare("SELECT DATE_FORMAT(t.lisatty, '%d.%m.%Y') as lisatty, t.nimi, t.polku, t.master_nimi, t.master_polku, k.sukunimi, k.etunimi FROM $tietokanta.tiedostot t 
        LEFT JOIN $tietokanta.kayttajat k ON k.id = t.lisaaja
        WHERE t.id = ?");
        $haku->execute(array($tiedostoid));
       
        $ti = $haku->fetch(PDO::FETCH_ASSOC);

        if ($annettunimi == '')
        {
            $annettunimi = $ti['nimi'];
            $polku = $ti['polku'];
        }
        if($annettunimi2 == '')
        {
            $annettunimi2 = $ti['master_nimi'];
            $polku2 = $ti['master_polku'];
        }

    $jono = "   <div class=\"row\">
                    <div class=\"col s4\">
                        <span class=\"title\">Julkaistu tiedosto</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.9em;\"><a href=\"".$polku."\" target=\"_blank\">".$annettunimi."</a></p>
                    </div>
                    <div class=\"col s4\">
                        <span class=\"title\">Master -tiedosto</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.9em;\"><a href=\"".$polku2."\" target=\"_blank\">".$annettunimi2."</a></p>
                    </div>
                    <div class=\"col s3\">
                        <span class=\"title\" style=\"font-size: 0.9em\">".$kommentti."</span>
                        <p style=\"margin-bottom: 0px; font-size: 0.8em;\">".$ti['sukunimi']." ".$ti['etunimi'].", ".$ti['lisatty']."</p>
                    </div>
                    <div class=\"col s1\">
                        <br />
                        <i class=\"material-icons modal-trigger\" id=\"muokkaa".$tiedostoid."\" data-target=\"modal1\" style=\"cursor: pointer\" >refresh</i>
                        <i class=\"material-icons\" id=\"poista<?php echo $tiedostoid;?>\" style=\"cursor: pointer\">delete_forever</i>
                    </div>
                </div>";


    $palautus['jono'] = $jono;
    $palautus['rivi'] = $tiedostoid;
    echo json_encode($palautus); 
	exit;

}
if ($_POST['toiminto'] == "muokkaussisalto")
{
    $tiedostoid = $_POST['tiedostoid'];    

    $haku = $yhteys_pdo->prepare("SELECT nimi, master_nimi, date_format(lisatty, '%d.%m.%Y') as lisatty FROM $tietokanta.tiedostot WHERE id = :rivi");
    $haku->execute(array(':rivi'=>$tiedostoid));
    $ha = $haku->fetch(PDO::FETCH_ASSOC);

    $jono = "<div class=\"row\"><div class=\"col s12\" style=\"margin-top: -15px\"><p style=\"margin-top: 0px\">Voit päivittää ns. master -tiedoston, julkaistavan tiedoston tai molemmat. Päivitettävä tiedosto ei tarvitse olla saman niminen.
    Tiedostoja muokattu viimeksi ".$ha['lisatty']."</div>
    
                <div class=\"col s12\">
                    <form accept-charset=\"multipart/form-data\" id=\"tiedostopaivitys\">
                    <input type=\"hidden\" id=\"tiedostoid\" name=\"tiedostoid\" value=\"".$tiedostoid."\">
                    Päivitä julkaistava tiedosto <b>".$ha['nimi']."</b> valitsemalla tiedosto
                </div>
                <div class=\"input-field col s12\" style=\"margin-top: 20px\">
                    <input placeholder=\"Jätä tyhjäksi jos nimi on hyvä\" id=\"muunimi\" name=\"muunimi\" type=\"text\">
                    <label for=\"muunimi\" class=\"active\">Julkaistavan tiedoston nimi (pdf)</label>
                </div>
                <div class=\"file-field input-field col s12\">
                    <div class=\"btn\">
                        <span>Tiedosto</span>
                        <input type=\"file\" name=\"mtiedosto\" id=\"mtiedosto\">
                    </div>
                    <div class=\"file-path-wrapper\">
                        <input class=\"file-path validate\" type=\"text\">
                    </div>
                </div>
                <div class=\"col s12\">
                    Päivitä master -tiedosto <b>".$ha['master_nimi']."</b> valitsemalla tiedosto
                </div>
                <div class=\"input-field col s12\">
                    <input placeholder=\"Jätä tyhjäksi jos nimi on hyvä\" id=\"muunimi2\" name=\"muunimi2\" type=\"text\">
                    <label for=\"muunimi2\" class=\"active\">Master -tiedoston nimi (pdf)</label>
                </div>               
                <div class=\"file-field input-field col s12\">
                    <div class=\"btn\">
                        <span>Tiedosto</span>
                        <input type=\"file\" name=\"mtiedosto2\" id=\"mtiedosto2\">
                    </div>
                    <div class=\"file-path-wrapper\">
                        <input class=\"file-path validate\" type=\"text\">
                    </div>
                </div>
                
                
                <div class=\"col s12\">
                    <label for=\"muokattumasterkommentti\">Master -tiedoston selite</label>
                    <textarea class=\"browser-default\" style=\"padding: 6px; height: 50px;\" rows=\"5\" id=\"muokattumasterkommentti\" name=\"muokattumasterkommentti\"></textarea>                        
                </div>
            </form>
        </div>";
    $palautus['jono'] = $jono;
    echo json_encode($palautus); 
	exit;
}