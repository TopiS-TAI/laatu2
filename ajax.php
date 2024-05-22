<?php
// tietokantayhteys
include 'yhteys.php'; 

$log_file = "php.log";
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', $log_file);

$nytaika = time();
$nyt = date("Y-m-d H:i:s", $nytaika); 


if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "paivitamaster")
{
	$kayttaja = $_POST['kayttaja'];
	$otsikko = $_POST['otsikko'];
	$kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli']; // tämä on master -rooli

	$khaku = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.kayttajat WHERE tunniste = :tunniste");
	$khaku->execute(array(':tunniste'=>$kayttaja));
	$kh = $khaku->fetch(PDO::FETCH_ASSOC);

	$pois = $yhteys_pdo->prepare("DELETE FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND master_rooli = :mrooli");
	$pois->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikko, ':mrooli'=>$rooli));

	$haku = $yhteys_pdo->prepare("SELECT rooli FROM $tietokanta.master_temp WHERE kayttaja = :kayttaja AND kategoria = :kategoria AND otsikkoid = :otsikko");
	$haku->execute(array(':kayttaja'=>$kayttaja, ':kategoria'=>$kategoria, ':otsikko'=>$otsikko));
	$c=0;
	while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
	{

		$lisa = $yhteys_pdo->prepare("INSERT INTO $tietokanta.master (tekija, otsikkoid, kategoria, rooli, aika, master_rooli, kaytossa) values (?,?,?,?,?,?,?)");
		$lisa->execute(array($kh['id'], $otsikko, $kategoria, $ha['rooli'], $nyt, $rooli, '1'));	

		$c++;
	}

	if($c > 0)
	{
		// lisätään myös se josta kopioidaan (jos sitä ei vielö ole)
		$tark = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.master WHERE otsikkoid = :otsikko AND kategoria = :kategoria AND rooli = :rooli AND master_rooli = :mrooli");
		$tark->execute(array(':otsikko'=>$otsikko, ':kategoria'=>$kategoria, ':rooli'=>$rooli, ':mrooli'=>$rooli));
		$ta = $tark->fetch(PDO::FETCH_ASSOC);

		if($ta['id'] == '')
		{
			$lisa = $yhteys_pdo->prepare("INSERT INTO $tietokanta.master (tekija, otsikkoid, kategoria, rooli, aika, master_rooli, kaytossa) values (?,?,?,?,?,?,?)");
			$lisa->execute(array($kh['id'], $otsikko, $kategoria, $rooli, $nyt, $rooli, '1'));

			$c++;
		}
	}
	else // kopioitavia rooleja ei ole. Varmistetaan että myös isäntä poistuu
	{
		$pois = $yhteys_pdo->prepare("DELETE FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND rooli = :rooli AND master_rooli = :mrooli");
		$pois->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikko, ':rooli'=>$rooli, ':mrooli'=>$rooli));
	}

	if ($c == 1)
		$c=0;

	$palautus['jono'] = $c;
    echo json_encode($palautus); 
	exit;


}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "valitsesync")
{

	$kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli'];
	$otsikko = $_POST['otsikko'];
	$kayttaja = $_POST['kayttaja'];
	$valittu = $_POST['valittu'];

	if ($valittu == 1)
	{
		$lisaa = $yhteys_pdo->prepare("INSERT INTO $tietokanta.master_temp(kayttaja, kategoria, rooli, otsikkoid) values (?,?,?,?)");
		$lisaa->execute(array($kayttaja, $kategoria, $rooli, $otsikko));
	}
	else
	{
		$pois = $yhteys_pdo->prepare("DELETE FROM $tietokanta.master_temp WHERE kayttaja = :kayttaja AND kategoria = :kategoria AND rooli = :rooli AND otsikkoid = :otsikko");
		$pois->execute(array(':kayttaja'=>$kayttaja, ':kategoria'=>$kategoria, ':rooli'=>$rooli, ':otsikko'=>$otsikko));
	}


	exit;

}
if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "avaasynkronointimodal")
{

	$kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli'];
	$otsikko = $_POST['otsikko'];
	$kayttaja = $_POST['kayttaja'];

	// haetaan sisältö synkronointimodaliin. Nöytetään roolit tietokannasta ja katsotaan onko rooleja valittuna
	$haku = $yhteys_pdo->prepare("SELECT rooli, nro FROM $tietokanta.roolit order by jarjestys");
	$haku->execute();

	// haetaan nykyisen roolin nimi
	$haku3 = $yhteys_pdo->prepare("SELECT rooli FROM $tietokanta.roolit WHERE nro = :rooli");
	$haku3->execute(array(':rooli'=>$rooli));
	$varooli = $haku3->fetch(PDO::FETCH_ASSOC);

	// poistetaan temp -taulusta mahdolliset aiemmat valinnat
	$pois = $yhteys_pdo->prepare("DELETE FROM $tietokanta.master_temp WHERE kayttaja = :kayttaja");
	$pois->execute(array(':kayttaja'=>$kayttaja));

	$jono = "<div style=\"margin: 20px 20px 20px 0\">Tässä voit valita synkronoidaanko sisältö tallennettaessa jollekin muullekin, kuin valitulle roolille. Huomaa, että jos sisältöä pitää muuttaa myöhemmin, 
	se pitää tehdä tälle nyt valitulle roolille (".$varooli['rooli'].").</div><p><b>Synkronoi sisältö seuraaville rooleille</b></p><ul><form>";

	while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
	{
		// katsotaan onko rooli valittu aimmin
		$haku2 = $yhteys_pdo->prepare("SELECT id, master_rooli FROM $tietokanta.master WHERE otsikkoid = :otsikko AND kategoria = :kategoria AND rooli = :rooli AND kaytossa = '1'");
		$haku2->execute(array(':otsikko'=>$otsikko, ':kategoria'=>$kategoria, ':rooli'=>$ha['nro']));
		$va = $haku2->fetch(PDO::FETCH_ASSOC); // jos palauttaa jotain, on rooli valittu synkronoitavaksi

		if($va['id'] <> '')
		{
			$laatikko = "checked=\"checked\"";
			// lisätään tieto temp -tauluun 
			$lisa = $yhteys_pdo->prepare("INSERT INTO $tietokanta.master_temp(kayttaja, kategoria, rooli, otsikkoid) values (?,?,?,?)");
			$lisa->execute(array($kayttaja, $kategoria, $ha['nro'], $otsikko));
		}
		else
			$laatikko = "";
		
		if($ha['nro'] == $rooli)
		{
			$jono.="<li>
			<label>
				<input type=\"checkbox\" disabled id=\"ssynkro".$ha['nro']."\" $laatikko />
				<span>".$ha['rooli']."</span>
			</label></li>";
		}
		else if ($va['master_rooli'] <> '' AND $va['master_rooli'] <> $rooli)
		{
			$jono.="<li>
			<label>
				<input type=\"checkbox\" disabled id=\"ssynkro".$ha['nro']."\" $laatikko />
				<span>".$ha['rooli']."--</span>
			</label></li>";			
		}
		else{
			$jono.="<li>
			<label>
				<input type=\"checkbox\" id=\"ssynkro".$ha['nro']."\" $laatikko />
				<span>".$ha['rooli']."</span>
			</label></li>";			
		}
	}

		$jono.="</ul></form><div style=\"margin: 20px 20px 20px 0\">Huomaa, että valintojen muuttaminen tässä ei vielä synkronoi sisältöä valitsemillesi rooleille. Synkronointi tapahtuu tallentamalla sisältö (julkaise tai talleta vedoksena).</div>";

	$palautus['jono'] = $jono;
    echo json_encode($palautus); 
	exit;

}


if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "haevalikko") 
{
	$jono = "<option value=\"999\">Kaikki</option>";

	$haku = $yhteys_pdo->prepare("SELECT id, otsikko from $tietokanta.sisallysluettelo WHERE taso1 IS NULL AND taso2 IS NULL AND taso3 IS NULL");
	$haku->execute();

	while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
	{
		$jono.="<option value=\"".$ha['id']."\">".$ha['otsikko']."</option>";
	}

	// $jono.="<option value=\"".$id."\">".$otsikko."</option>";

	$palautus['jono'] = $jono;
    echo json_encode($palautus); 
	exit;
}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "vaihdakategoria")
{
    $kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli'];

	if ($rooli == '' or $rooli == 'undefined')
	{
		$nakyva = "display: none;";
	}
	else
	{
		$nakyva = "display: block;";
	}


	$haku = $yhteys_pdo->prepare("SELECT id, paa, taso1, taso2, taso3, otsikko FROM $tietokanta.sisallysluettelo WHERE paa = :kategoria ORDER BY taso1, taso2, taso3");
	$haku->execute(array(':kategoria'=>$kategoria));
   
    $c=0;
	
	$otsikot = array();
    
    while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
    {
		array_push($otsikot, $ha['id']);	

		if ($rooli <> '') // palautetaan myös otsikot
		{
	
			// tarkistetaan, onko kyseiselle otsikolle julkaisua
			$tark = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.sis WHERE otsikkoid = :otsikko AND rooli = :rooli AND kategoria = :kategoria");
			$tark->execute(array(':otsikko'=>$ha['id'], ':rooli'=>$rooli, ':kategoria'=>$kategoria));
			$ta = $tark->fetch(PDO::FETCH_ASSOC);
			
			if (isset($ta['id']) AND $ta['id'] <> '')
			{
				$tekstivari = "green";
			}
			else
			{
				$tekstivari = "red";
			}
			
			// lasketaan onko drafteja
			$haku2 = $yhteys_pdo->prepare("SELECT count(id) as maara FROM $tietokanta.sis_draftit WHERE otsikkoid = :id AND rooli = :rooli AND kategoria = :kategoria");;
			$haku2->execute(array(':id'=>$ha['id'], ':rooli'=>$rooli, ':kategoria'=>$kategoria));		
			$ha2 = $haku2->fetch(PDO::FETCH_ASSOC);

			if ($c == 0)
			{
				$jono = "<ul class=\"collection\">";
			}
			if ($ha['taso3'] > 0) // 3. tason alaotsikko
			{
				$jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 70px; margin-right: 30px; text-indent:-3.0em;\">".$ha['paa'].".".$ha['taso1'].".".$ha['taso2'].".".$ha['taso3']."  ".$ha['otsikko']."
				</h6>
				<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
					<i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" style=\"".$nakyva."\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
				</div>
				<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
				</li>";
			}
			else if ($ha['taso2'] > 0 AND $ha['taso3'] == '') // 2. tason alaotsikko
			{
				$jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 50px; margin-right: 30px; text-indent:-2.3em;\">".$ha['paa'].".".$ha['taso1'].".".$ha['taso2']."  ".$ha['otsikko']."
				</h6>
				<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
					<i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" style=\"".$nakyva."\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
				</div>
				<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
				</li>";
			}
			else if ($ha['taso1'] > 0 AND $ha['taso2'] == '') // 1. tason alaotsikko
			{
				$jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 30px; margin-right: 30px; text-indent:-1.6em;\">".$ha['paa'].".".$ha['taso1']."  ".$ha['otsikko']."
				</h6>
				<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
					<i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" style=\"".$nakyva."\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
				</div>
				<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
				</li>";
			} 
			else // pääotsikko
			{
				$jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 10px; margin-right: 30px; text-indent:-1.2em;\">".$ha['paa']."  ".$ha['otsikko']."
				</h6>
				<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
					<i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer; ".$nakyva."\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" style=\"".$nakyva."\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
				</div>
				<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
				</li>";
			}

			$palautus['sisallysluettelo'] = $jono;
			$c++;			
		}

    }
	if ($rooli <> '') // palautetaan myös otsikot
	{
		$jono.="</ul>";
	}



    $palautus['otsikot'] = $otsikot; // tämä siis taulukko jossa jokaisen otsikon id

    echo json_encode($palautus); 	
    exit;

}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "haenapit")
{
    $kategoria = $_POST['kategoria'];
    $rooli = $_POST['rooli']; 
	$otsikkoid = $_POST['otsikot'];
	
	$haku = $yhteys_pdo->prepare("SELECT id, paa, taso1, taso2, otsikko FROM $tietokanta.sisallysluettelo WHERE paa = :kategoria ORDER BY taso1, taso2, taso3");
	$haku->execute(array(':kategoria'=>$kategoria));
    $c=0;
    while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
    {
		// tarkistetaan, onko kyseiselle otsikolle julkaisua
		$tark = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.sis WHERE otsikkoid = :otsikko AND rooli = :rooli AND kategoria = :kategoria");
		$tark->execute(array(':otsikko'=>$ha['id'], ':rooli'=>$rooli, ':kategoria'=>$kategoria));
		$ta = $tark->fetch(PDO::FETCH_ASSOC);
		
		if (isset($ta['id']) AND $ta['id'] <> '')
		{
			$tekstivari = "green";
		}
		else
		{
			$tekstivari = "red";
		}
		
		// lasketaan onko drafteja
		$haku2 = $yhteys_pdo->prepare("SELECT count(id) as maara FROM $tietokanta.sis_draftit WHERE otsikkoid = :id AND rooli = :rooli AND kategoria = :kategoria");;
		$haku2->execute(array(':id'=>$ha['id'], ':rooli'=>$rooli, ':kategoria'=>$kategoria));		
		$ha2 = $haku2->fetch(PDO::FETCH_ASSOC);

        if ($c == 0)
        {
            $jono = "<ul class=\"collection\">";
        }
		if (isset($ha['taso3']) AND $ha['taso3'] > 0) // 3. tason alaotsikko
        {
            $jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 70px; margin-right: 30px; text-indent:-3.0em;\">".$ha['paa'].".".$ha['taso1'].".".$ha['taso2'].".".$ha['taso3']."  ".$ha['otsikko']."
			</h6>
			<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
				<i class=\"material-icons\" style=\"cursor: pointer;\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer;\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
			</div>
			<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
            </li>";
        }
        else if ($ha['taso2'] > 0 AND isset($ha['taso3']) AND $ha['taso3'] == '') // 2. tason alaotsikko
        {
            $jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 50px; margin-right: 30px; text-indent:-2.3em;\">".$ha['paa'].".".$ha['taso1'].".".$ha['taso2']."  ".$ha['otsikko']."
			</h6>
			<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
				<i class=\"material-icons\" style=\"cursor: pointer;\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer;\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
			</div>
			<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
            </li>";
        }
        else if ($ha['taso1'] > 0 AND $ha['taso2'] == '') // 1. tason alaotsikko
        {
            $jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 30px; margin-right: 30px; text-indent:-1.6em;\">".$ha['paa'].".".$ha['taso1']."  ".$ha['otsikko']."
			</h6>
			<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
				<i class=\"material-icons\" style=\"cursor: pointer;\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer;\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
			</div>
			<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
            </li>";
        } 
		else // pääotsikko
		{
			$jono.="<li class=\"collection-item\" id=\"otsikkoteksti".$ha['id']."\" style=\"color: ".$tekstivari."\"><h6 style=\"margin-left: 10px; margin-right: 30px; text-indent:-1.2em;\">".$ha['paa']."  ".$ha['otsikko']."
			</h6>
			<div id=\"napit".$ha['id']."\" style=\"width: 40px; height: 55px; position: absolute; top: 0; right: 0;\">
				<i class=\"material-icons\" style=\"cursor: pointer;\" id=\"muokkaa".$ha['id']."\">edit</i><i class=\"material-icons\" style=\"cursor: pointer;\" id=\"versiot".$ha['id']."\">find_replace</i><span class=\"draftimaara\" id=\"draftimaara".$ha['id']."\">".$ha2['maara']."</span>
			</div>
			<div class=\"draftit\" id=\"draftit".$ha['id']."\" style=\"width:100%;\"></div> 
            </li>";
		}	

        $c++;
    }

    $jono.="</ul>";

	$mahaku = $yhteys_pdo->prepare("SELECT m.id, r.rooli FROM $tietokanta.master m LEFT JOIN $tietokanta.roolit r ON r.nro = m.master_rooli WHERE m.kategoria = :kategoria AND m.otsikkoid = :otsikkoid AND m.rooli = :rooli AND m.master_rooli <> :mrooli");
	$mahaku->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':rooli'=>$rooli, ':mrooli'=>$rooli));
	$ma = $mahaku->fetch(PDO::FETCH_ASSOC);

	if (isset($ma['id']) AND $ma['id'] <> '')
	{
		$yh['maara'] = "Tämä rooli kopioituu roolista <i><b>".$ma['rooli']."</b></i>";
		$master = 0;
		
	}
	else
	{
		
		$haku3 = $yhteys_pdo->prepare("SELECT master_rooli FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND rooli = :rooli AND kaytossa = '1'");
		$haku3->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':rooli'=>$rooli));
		$tu = $haku3->fetch(PDO::FETCH_ASSOC);

		$yhthaku = $yhteys_pdo->prepare("SELECT count(id) as maara FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND master_rooli = :mrooli AND kaytossa = '1'");
		$yhthaku->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':mrooli'=>isset($tu['master_rooli']) ? $tu['master_rooli'] : ''));
		$yh = $yhthaku->fetch(PDO::FETCH_ASSOC);

		if ($yh['maara'] == 1)
			$yh['maara'] = 0;
		$master = 1;
	}

	// haetaan editoriin sisältö
	if ($otsikkoid <> '')
	{
		$haku = $yhteys_pdo->prepare("SELECT id, sisalto FROM $tietokanta.sis WHERE otsikkoid = :otsikko AND kategoria = :kategoria AND rooli = :rooli");
		$haku->execute(array(':otsikko'=>$otsikkoid, ':kategoria'=>$kategoria, ':rooli'=>$rooli));
		$ha = $haku->fetch(PDO::FETCH_ASSOC);

		if (isset($ha['id']) AND $ha['id'] <> '')
		{
			$sisalto = $ha['sisalto'];
		}
		else
		{
			$sisalto = "<p>Ei vielä sisältöä....</p>";
		}
	}
	else
	{
		$sisalto = '';
	}

	$palautus['info'] = $rooli."|".$kategoria;
	$palautus['sisallysluettelo'] = $jono;
	$palautus['maara'] = $yh['maara'];
	$palautus['master'] = $master;
	$palautus['sisalto'] = $sisalto;

    echo json_encode($palautus); 	
    exit;

}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "vertaile")
{
	// SELECT * FROM sis WHERE kategoria = 2 AND rooli in (2, 12);
	$kategoria = $_POST['kategoria'];
	$roolit = $_POST['roolit'];
	
	$roolit_lista = explode(',', $roolit);
	$pituus = count($roolit_lista);
	$inclause=implode(',',array_fill(0,$pituus,'?'));
	$sql_pohja = "SELECT * FROM sis WHERE kategoria = ? AND rooli in (%s)";
	$sql_kysely = sprintf($sql_pohja, $inclause);
	
	$tark = $yhteys_pdo->prepare($sql_kysely);
	$tark->execute(array($kategoria, ...$roolit_lista));
	$ta = $tark->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($ta);
	exit;
}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "muokkaa")
{
    $kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli'];
    $otsikkoid = $_POST['otsikko'];	

    $haku = $yhteys_pdo->prepare("SELECT id, sisalto FROM $tietokanta.sis 
	WHERE otsikkoid = :rivi AND rooli = :rooli AND kategoria = :kategoria");
    $haku->execute(array(':rivi'=>$otsikkoid, ':rooli'=>$rooli, ':kategoria'=>$kategoria));    
    $ha = $haku->fetch(PDO::FETCH_ASSOC);
	
	$haku2 = $yhteys_pdo->prepare("SELECT paa, taso1, taso2, taso3, otsikko FROM $tietokanta.sisallysluettelo WHERE id = :otsikko");
	$haku2->execute(array(':otsikko'=>$otsikkoid));
	$ha2 = $haku2->fetch(PDO::FETCH_ASSOC);

	$mahaku = $yhteys_pdo->prepare("SELECT m.id, r.rooli FROM $tietokanta.master m LEFT JOIN $tietokanta.roolit r ON r.nro = m.master_rooli WHERE m.kategoria = :kategoria AND m.otsikkoid = :otsikkoid AND m.rooli = :rooli AND m.master_rooli <> :mrooli");
	$mahaku->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':rooli'=>$rooli, ':mrooli'=>$rooli));
	$ma = $mahaku->fetch(PDO::FETCH_ASSOC);

	

	if ($ma['id'] <> '')
	{
		$yh['maara'] = "Tämä rooli kopioituu roolista <i><b>".$ma['rooli']."</b></i>"; 
		$master = 0;
	}
	else
	{

		$haku3 = $yhteys_pdo->prepare("SELECT master_rooli FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND rooli = :rooli AND kaytossa = '1'");
		$haku3->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':rooli'=>$rooli));
		$tu = $haku3->fetch(PDO::FETCH_ASSOC);

		$yhthaku = $yhteys_pdo->prepare("SELECT count(id) as maara FROM $tietokanta.master WHERE kategoria = :kategoria AND otsikkoid = :otsikkoid AND master_rooli = :mrooli AND kaytossa = '1'");
		$yhthaku->execute(array(':kategoria'=>$kategoria, ':otsikkoid'=>$otsikkoid, ':mrooli'=>$tu['master_rooli']));
		$yh = $yhthaku->fetch(PDO::FETCH_ASSOC);

		if ($yh['maara'] == 1)
			$yh['maara'] = 0;

		$master = 1;
	}


	$rivi = $otsikkoid;
	$paa = $ha2['paa'];
	$taso1 = $ha2['taso1'];
	$taso2 = $ha2['taso2'];
	$taso3 = $ha2['taso3'];
	$otsikko = $ha2['otsikko'];
	
	if (isset($ha['id']) AND $ha['id'] <> '')
		$sisalto = $ha['sisalto'];
	else
		$sisalto = "<p>Ei vielä sisältöä....</p>";
	
	$palautus['maara'] = $yh['maara'];
	$palautus['master'] = $master;
	
	$palautus['rivi'] = $rivi;
    $palautus['taso'] = $paa;
    $palautus['alataso'] = $taso1;
	$palautus['alataso2'] = $taso2;
    $palautus['otsikko'] = $otsikko;
    $palautus['sisalto'] = $sisalto;

    echo json_encode($palautus); 	
    exit;
}

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "talleta")
{
    
    $kategoria  = $_POST['kategoria'];
	$rooli      = $_POST['rooli'];
    $sisalto    = $_POST['sisalto'];
	$otsikko 	= $_POST['otsikko'];
    $rivi       = $_POST['rivi'];
	$tunniste   = $_POST['tunniste'];

	$haku = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.kayttajat WHERE tunniste = :tunniste");
	$haku->execute(array(':tunniste'=>$tunniste));
	$ni = $haku->fetch(PDO::FETCH_ASSOC);

	// tallennetaan valitun roolin sisältö
	$haku = $yhteys_pdo->prepare("SELECT id, otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja FROM $tietokanta.sis WHERE otsikkoid = :rivi and rooli = :rooli AND kategoria = :kategoria");
	$haku->execute(array(':rivi'=>$rivi, ':rooli'=>$rooli, ':kategoria'=>$kategoria));
	$ha = $haku->fetch(PDO::FETCH_ASSOC);
	
	if ($ha['id'] <> '')
	{
		// Siirretään aiempi julkaisu draftiksi
		$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis_draftit (otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
		$tallennus->execute(array($ha['otsikkoid'], $ha['rooli'], $ha['kategoria'], $ha['sisalto'], $ha['muokattu'], $ha['muokkaaja'])); 

		// päivitetään sisältö -taulun tiedot. Tämä siis julkaistaan
		$paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.sis SET sisalto = :sisalto, muokattu = :nyt, muokkaaja = :muokkaaja WHERE id = :rivi");
		$paivitys->execute(array(':sisalto'=>$sisalto, ':nyt'=>$nyt, ':muokkaaja'=>$ni['id'], ':rivi'=>$ha['id']));
	}
	else
	{
		$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis(otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
		$tallennus->execute(array($otsikko, $rooli, $kategoria, $sisalto, $nyt, $ni['id']));
	}

	// katsotaan kopioidaanko muihinkin rooleihin (jos tämä valittu rooli on master)
	$rohaku =  $yhteys_pdo->prepare("SELECT rooli FROM $tietokanta.master WHERE otsikkoid = :otsikko AND kategoria = :kategoria AND master_rooli = :mrooli AND rooli <> :rooli");
	$rohaku->execute(array(':otsikko'=>$otsikko, ':kategoria'=>$kategoria, ':mrooli'=>$rooli, ':rooli'=>$rooli));

	while ($ro = $rohaku->fetch(PDO::FETCH_ASSOC))
	{
		$haku = $yhteys_pdo->prepare("SELECT id, otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja FROM $tietokanta.sis WHERE otsikkoid = :rivi and rooli = :rooli AND kategoria = :kategoria");
		$haku->execute(array(':rivi'=>$rivi, ':rooli'=>$ro['rooli'], ':kategoria'=>$kategoria));
		$ha = $haku->fetch(PDO::FETCH_ASSOC);

		if ($ha['id'] <> '')
		{
			// Siirretään aiempi julkaisu draftiksi
			$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis_draftit (otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
			$tallennus->execute(array($ha['otsikkoid'], $ro['rooli'], $ha['kategoria'], $ha['sisalto'], $ha['muokattu'], $ha['muokkaaja'])); 

			// päivitetään sisältö -taulun tiedot. Tämä siis julkaistaan
			$paivitys = $yhteys_pdo->prepare("UPDATE $tietokanta.sis SET sisalto = :sisalto, muokattu = :nyt, muokkaaja = :muokkaaja WHERE id = :rivi");
			$paivitys->execute(array(':sisalto'=>$sisalto, ':nyt'=>$nyt, ':muokkaaja'=>$ni['id'], ':rivi'=>$ha['id']));
		}
		else
		{
			$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis(otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
			$tallennus->execute(array($otsikko, $ro['rooli'], $kategoria, $sisalto, $nyt, $ni['id']));
		} 
	}
	
 
	
 
    // jostain syystä editorin sisällön resetointi ei toiminut. Palautetaan siis tyhjä editori -ikkuna
    $palautus['sisalto'] = "";
	$palautus['otsikko'] = $otsikko;

    echo json_encode($palautus); 	
    exit;
}


if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "talletatrafti")
{    
	$kategoria  = $_POST['kategoria'];
	$rooli      = $_POST['rooli'];
    $sisalto    = $_POST['sisalto'];
    $rivi       = $_POST['rivi'];
	$tunniste   = $_POST['tunniste'];
	$otsikko 	= $_POST['otsikko'];

	$haku = $yhteys_pdo->prepare("SELECT id FROM $tietokanta.kayttajat WHERE tunniste = :tunniste");
	$haku->execute(array(':tunniste'=>$tunniste));
	$ni = $haku->fetch(PDO::FETCH_ASSOC);
	
	$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis_draftit(otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
	$tallennus->execute(array($otsikko, $rooli, $kategoria, $sisalto, $nyt, '1'));  
	
	// katsotaan kopioidaanko muihinkin rooleihin (jos tämä valittu rooli on master)
	$rohaku =  $yhteys_pdo->prepare("SELECT rooli FROM $tietokanta.master WHERE otsikkoid = :otsikko AND kategoria = :kategoria AND master_rooli = :mrooli AND rooli <> :rooli");
	$rohaku->execute(array(':otsikko'=>$otsikko, ':kategoria'=>$kategoria, ':mrooli'=>$rooli, ':rooli'=>$rooli));

	while ($ro = $rohaku->fetch(PDO::FETCH_ASSOC))
	{
		// Tallenetaan muidenkin synkronoitavien roolien draftit
		$tallennus = $yhteys_pdo->prepare("INSERT INTO $tietokanta.sis_draftit (otsikkoid, rooli, kategoria, sisalto, muokattu, muokkaaja) values (?,?,?,?,?,?)");
		$tallennus->execute(array($rivi, $ro['rooli'], $kategoria, $sisalto, $nyt, $ni['id'])); 
	}
 
    // jostain syystä editorin sisällön resetointi ei toiminut. Palautetaan siis tyhjä editori -ikkuna
    $palautus['sisalto'] = "";

    echo json_encode($palautus); 	
    exit;
}

// tämän alla olevat pitää käydä läpi (tarvitaanko, pitääkö muuttaa)

if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "haeversiot")
{
    $rivi = $_POST['rivi'];
	$kategoria = $_POST['kategoria'];
	$rooli = $_POST['rooli'];
	/*
    $haku = $yhteys_pdo->prepare("SELECT id, taso, alataso, otsikko, sisalto, date_format(muokattu, '%d.%m.%Y %H:%i') as muokattujono, muokkaaja FROM $tietokanta.sisalto_traftit 
    WHERE isanta = :rivi order by muokattu DESC");
	*/
	$haku = $yhteys_pdo->prepare("SELECT t.id, t.otsikkoid, date_format(t.muokattu, '%d.%m.%Y %H:%i') as muokattujono, t.muokkaaja, s.otsikko, k.etunimi, k.sukunimi FROM $tietokanta.sis_draftit t
	LEFT JOIN $tietokanta.sisallysluettelo s ON s.id = t.otsikkoid
	LEFT JOIN $tietokanta.kayttajat k ON k.id = t.muokkaaja
    WHERE t.otsikkoid = :rivi AND t.kategoria = :kategoria AND t.rooli = :rooli order by t.muokattu DESC");

    $haku->execute(array(':rivi'=>$rivi, ':kategoria'=>$kategoria, ':rooli'=>$rooli));    
    
    $jono = "<p style=\"margin: 10px 0 25px 20px; color: #000\">Aiemmat versiot: <ul style=\"margin-left: 20px; margin-top: -20px;\">"; 
    
    while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
    {
        $jono.= "<li id=\"draftimuokkaa".$ha['id']."\" style=\"cursor: pointer;\">".$ha['otsikko']."<br /><div style=\"font-size: 10px; margin-top: -7px; color: #000\">".$ha['sukunimi']." ".$ha['etunimi']." ".$ha['muokattujono']."</div></li>";
    }
    $jono.="</ul></p>";

    $palautus['rivi'] = $rivi;
    $palautus['draftit'] = $jono;
    echo json_encode($palautus); 	
    exit;
}



if (isset($_POST['toiminto']) AND $_POST['toiminto'] == "draftimuokkaa")
{

    $rivi = $_POST['rivi'];
	
    $haku = $yhteys_pdo->prepare("SELECT s.sisalto, sis.paa, sis.taso1, sis.taso2, sis.otsikko FROM $tietokanta.sis_draftit s 
	LEFT JOIN $tietokanta.sisallysluettelo sis ON sis.id = s.otsikkoid 
	WHERE s.id = :rivi");
    $haku->execute(array(':rivi'=>$rivi));    
    $ha = $haku->fetch(PDO::FETCH_ASSOC);


    $palautus['taso'] = $ha['paa'];
    $palautus['alataso'] = $ha['taso1'];
	$palautus['alataso2'] = $ha['taso2'];
    $palautus['otsikko'] = $ha['otsikko'];
    $palautus['sisalto'] = $ha['sisalto'];

	echo json_encode($palautus); 	
    exit;
}
?>