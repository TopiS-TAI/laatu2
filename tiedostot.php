<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { 
	session_start();
}

$kt=			$_SESSION["kt"];
$tunniste=		$_SESSION["tunniste"];

// tietokantayhteys
include 'yhteys.php'; 

//  haetaan kategoriat
$haku = $yhteys_pdo->prepare("SELECT id, paa, otsikko FROM $tietokanta.sisallysluettelo WHERE taso1 IS NULL and taso2 IS NULL order by paa");
$haku->execute();
$c=0;
while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
{
    $ka_id[$c] = $ha['paa'];
    $ka_otsikko[$c] = $ha['otsikko'];
    $c++;
}

// haetaan tiedostot
$haku = $yhteys_pdo->prepare("SELECT t.id, t.kategoria, t.nimi, t.paate, t.polku, t.master_nimi, t.master_polku, t.master_paate, t.master_kommentti, DATE_FORMAT(t.lisatty, '%d.%m.%Y') as lisatty, t.poistettu, k.sukunimi, k.etunimi FROM $tietokanta.tiedostot t 
LEFT JOIN $tietokanta.kayttajat k ON k.id = t.lisaaja
WHERE t.poistettu = '0' 
order by t.kategoria, t.lisatty");
$haku->execute();
$f=0;
while($ti = $haku->fetch(PDO::FETCH_ASSOC))
{
    $kat = $ti['kategoria'];
    $t_id[$kat][$f] = $ti['id'];
    $t_kategoria[$kat][$f] = $ti['kategoria'];
    $t_nimi[$kat][$f] = $ti['nimi'];
    $t_paate[$kat][$f] = $ti['paate'];
    $t_polku[$kat][$f] = $ti['polku'];

    if ($ti['master_polku'] <> '')
        $t_master_polku[$kat][$f] = $ti['master_polku'];
    else
        $t_master_polku[$kat][$f] = "";
    
    if ($ti['master_nimi'] <> '')
    {
        if (strlen($ti['master_nimi']) > '30')
        {
            $t_master_nimi[$kat][$f] = substr($ti['master_nimi'], 0, 25)."...".$ti['master_paate']; 
        }
        else
        {
            $t_master_nimi[$kat][$f] = $ti['master_nimi'];
        }
        
    }    
    else
        $t_master_nimi[$kat][$f] = "[ puuttuu ]";

    $t_master_kommentti[$kat][$f] = $ti['master_kommentti'];
    $t_lisatty[$kat][$f] = $ti['lisatty'];
    $t_lisaaja[$kat][$f] = $ti['sukunimi']." ".$ti['etunimi'];
    $t_poistettu[$kat][$f] = $ti['poistettu'];
    $f++;
}

?>

<!DOCTYPE html>
  <html>
    <head>
        <title>Laatukäsikirja - Tiedostot</title>
      <!--Import Google Icon Font-->
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <!--Import materialize.css-->
      <link type="text/css" rel="stylesheet" href="css/materialize.min.css"  media="screen,projection"/>
      <link type="text/css" rel="stylesheet" href="css/muutokset.css"  media="screen,projection"/>
      <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
           
      <!-- https://artisansweb.net/trix-an-open-source-wysiwyg-editor-for-websites/ -->

      <!--Let browser know website is optimized for mobile-->
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    </head>

    <body>
    <nav>
        <div class="nav-wrapper">
            <div class="row">
                <div class="col s12">
                    <a href="hallinta.php" class="brand-logo"><img style="margin-top: 3px;" src="img/tai_logo_valkoinen_nettiin.png" /></a>
                    <ul id="nav-mobile" class="right hide-on-med-and-down">
                        <li><a href="hallinta.php">Sisältö</a></li>
                        <li class="active"><a href="tiedostot.php">Tiedostot</a></li>
                        <li><a href="kuvat.php">Kuvat</a></li>
                        <li style="width: 60px">&nbsp;</li>
                        <li><a href="index.php?act=out">Kirjaudu ulos</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>   
    <div class="row">
        <div class="col s12">
            <div class="card grey lighten-5">
                <div class="card-content black-text" style="padding-bottom: 5px;">
                    <span class="card-title">
                        Tiedostot
                    </span>                    
                    <div class="row">
                        <?php
                        for ($cc=0;$cc<$c;$cc++)
                        {                          
                            ?>
                            <div class="col s12">
                                <ul class="collection with-header" id="tiedostokategoria<?php echo $ka_id[$cc];?>"><span style="float: right; margin: 13px 15px">
                                <a id="lisaatiedosto<?php echo $ka_id[$cc];?>" class="modal-trigger waves-effect waves-light btn-small" data-target="modal2">Lisää tiedosto</a></span>
                                    <li class="collection-header"><h6 style="font-size: 1.2em"><?php echo $ka_otsikko[$cc];?></h6></li>
                                
                        
                            <?php
                            for ($ff=0;$ff<$f;$ff++)
                            { 
                                if (ISSET($t_kategoria[$ka_id[$cc]][$ff]) AND $t_kategoria[$ka_id[$cc]][$ff] == $ka_id[$cc])
                                { 
                                    if ($t_master_kommentti[$ka_id[$cc]][$ff] == '')
                                        $t_master_kommentti[$ka_id[$cc]][$ff] = "[ ei kommenttia ]";  
                                    
                                    
                                    ?>
                                    <li class="collection-item" id="tiedostorivi<?php echo $t_id[$ka_id[$cc]][$ff];?>" style="padding-bottom: 1px !important;">
                                        <div class="row">
                                            <div class="col s4">
                                                <span class="title">Julkaistu tiedosto</span>
                                                <p style="margin-bottom: 0px; font-size: 0.9em;"><a href="<?php echo $t_polku[$ka_id[$cc]][$ff];?>" target="_blank"><?php echo $t_nimi[$ka_id[$cc]][$ff];?></a></p>
                                            </div>
                                            <div class="col s4">
                                                <span class="title">Master -tiedosto</span>
                                                <p style="margin-bottom: 0px; font-size: 0.9em;"><a href="<?php echo $t_master_polku[$ka_id[$cc]][$ff];?>" target="_blank"><?php echo $t_master_nimi[$ka_id[$cc]][$ff];?></a></p>
                                            </div>
                                            <div class="col s3">
                                                <span class="title" style="font-size: 0.9em"><?php echo $t_master_kommentti[$ka_id[$cc]][$ff];?></span>
                                                <p style="margin-bottom: 0px; font-size: 0.8em;"><?php echo $t_lisaaja[$ka_id[$cc]][$ff];?>, <?php echo $t_lisatty[$ka_id[$cc]][$ff];?></p>
                                            </div>
                                            <div class="col s1">
                                                <div style="margin-top: 15px; color: #000;">
                                                    <i class="material-icons modal-trigger" id="muokkaa<?php echo $t_id[$ka_id[$cc]][$ff];?>" data-target="modal1" style="cursor: pointer" >refresh</i>
                                                    <i class="material-icons" id="poista<?php echo $t_id[$ka_id[$cc]][$ff];?>" style="cursor: pointer">delete_forever</i>
                                                </div>
                                            </div>
                                        </div>                                        
                                    </li>                               
                                <?php
                                }
                            } ?>
                    
                            
                                </ul>
                        </div>
                        <?php
                        }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
       

    </div>   
    <!-- Tiedoston muokkaus -modal -->
    <div id="modal1" class="modal" style="max-width: 600px;">
			<div class="row">
				<div class="col s12 teal lighten-2" style="padding: 0px 5px 5px 15px; box-sizing: border-box;">
					<h5 class="white-text" id="otsake">Tiedoston päivitys<span style="float: right; margin-right: 15px"><i class="material-icons modal-close">close</i></span></h5>
				</div>
			</div>	
			<div class="modal-content" id="paivitys">
			
			</div>
			<div class="modal-footer" style="padding-right: 20px" style="box-sizing: border-box;">
                <a  class="waves-effect waves-light btn-small yellow black-text modal-close ">Peruuta</a>
                <a id="talletapaivitys" class="waves-effect waves-light btn-small modal-close ">Päivitä</a>
			</div>
	</div>

    <div id="modal2" class="modal modal-fixed-footer" style="max-width: 600px;">
    <div class="row">
				<div class="col s12 teal lighten-2" style="padding: 0px 5px 5px 15px;">
					<h5 class="white-text" id="otsake">Uusi tiedosto<span style="float: right; margin-right: 15px"><i class="material-icons modal-close">close</i></span></h5>
				</div>
			</div>	
            <div class="modal-content" id="paivitys" style="height: auto !important;">
                <form accept-charset="multipart/form-data" id="uusitiedosto">
                    <input type="hidden" id="kategoria" name="kategoria">
                    <fieldset style="margin-top: -10px; border: 1px solid #696969;">
                    <legend style="padding: 0 10px 0 10px;">Julkaistava tiedosto</legend>
                    <div class="input-field col s12">
                        <input placeholder="Jätä tyhjäksi jos nimi on hyvä" id="muunimi2" name="muunimi2" type="text">
                        <label for="muunimi2" style="left: 0 !important;">Julkaistavan tiedoston nimi (pdf)</label>
                    </div>
                    <div class=" col s12 file-field input-field">                   
                        <div class="btn">
                            <span>Tiedosto</span>
                            <input type="file" name="uutiedosto2" id="uutiedosto2">
                        </div>
                        <div class="col s12 file-path-wrapper">
                            <input class="file-path validate" type="text">
                        </div>
                    </div>
                    </fieldset>
                    <fieldset style="border: 1px solid #696969; margin-top: 20px;">
                        <legend style="padding: 0 10px 0 10px;">Master -tiedosto</legend>
                    <div class="input-field col s12">
                        <input placeholder="Jätä tyhjäksi jos nimi on hyvä" id="muunimi" name="muunimi" type="text">
                        <label for="muunimi" style="left: 0 !important;">Master -tiedoston nimi</label>
                    </div>
                   
                    <div class=" col s12 file-field input-field">                   
                        <div class="btn">
                            <span>Tiedosto</span>
                            <input type="file" name="uutiedosto" id="uutiedosto">
                        </div>
                        <div class="col s12 file-path-wrapper">
                            <input class="file-path validate" type="text">
                        </div>
                    </div>
                    <div class="col s12">
                        <label for="masterkommentti">Master -tiedoston selite</label>
                        <textarea class="browser-default" style="width: 100%; padding: 6px; height: 50px; margin-bottom: 20px;" id="umasterkommentti" name="masterkommentti"></textarea>                        
                    </div>
                    </fieldset>
                    
                    
                </form>
            </div>   
			
			<div class="modal-footer">
                <a  class="waves-effect waves-light btn-small yellow black-text modal-close ">Peruuta</a>
                <a id="talletatiedosto" style="margin-right: 20px;" class="waves-effect waves-light btn-small modal-close ">Talleta</a>
			</div>
	</div>

    <script>
		  $(document).ready(function(){
            
            $('.modal').modal();
            $('select').formSelect();

            var tunniste = localStorage.getItem('taitunniste');

            if (tunniste === null)
            {
                window.location.replace("index.html");
            }

            $('body').on('click', '[id^=lisaatiedosto]', function(e){ 

                var id  = $(this).attr('id');  
                var nro = id.match(/\d+/);

                $('#kategoria').val(nro);

            });

            $('body').on('click', '[id^=poista]', function(e){ 

                var id  = $(this).attr('id');  
                var nro = id.match(/\d+/);
                var tiedot = 'tiedostoid=' + nro + '&toiminto=poistatiedosto';

                $.ajax({  

                    type: "POST", 
                    url: "ajax_tiedostot.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        $('#' + data.pois ).fadeOut('slow', function() { remove(); });  

                    },
                    error: function(data){ 							           		
                                            
                    }
                    });

            });


            $('body').on('click', '[id^=muokkaa]', function(e){ 

                var id  = $(this).attr('id');  
				var nro = id.match(/\d+/);
				var tiedot = 'tiedostoid=' + nro + '&toiminto=muokkaussisalto';

                $.ajax({  

                    type: "POST", 
                    url: "ajax_tiedostot.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        console.log('Info ' + data.info );

                        $('#paivitys').html( data.jono );   

                    },
                    error: function(data){ 							           		
                                            
                    }
                    });
            
          });

          $('body').on('click', '[id=talletatiedosto]', function(e){ 

            var tiedot = new FormData(); 
            var form_data = $('#uusitiedosto').serializeArray();

            var kategoria = $('#kategoria').val();
            var uunimi = $('#uunimi').val();

            var kommentti = $('#masterkommentti').val();
            
            var file_data = $('input[name="uutiedosto"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("uutiedosto", file_data[i]);                
            }
            var file_data = $('input[name="uutiedosto2"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("uutiedosto2", file_data[i]);                
            }

            tiedot.append("kategoria", kategoria);
            tiedot.append("uunimi", uunimi);
            tiedot.append("kommentti", kommentti);
            tiedot.append("toiminto", "uusitiedosto");

            console.log('Talletetaan');

            $.ajax({  

                type: "POST", 
                url: "ajax_tiedostot.php",
                data: tiedot,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data){

                    $('#uusitiedosto').trigger("reset");
                    $('#tiedostokategoria' + data.kategoria).append( data.jono );
                   
                },
                error: function(data){ 							           		
                                        
                }
                });

            });

          $('body').on('click', '[id=talletapaivitys]', function(e) {
            
            var tiedostoid = $('#tiedostoid').val();
            var kommentti = $('#muokattumasterkommentti').val();

            var muunimi = $('#muunimi').val(); // julkaistavan uusi nimi
            var muunimi2 = $('#muunimi2').val(); // masterin uusi nimi

            var tiedot = new FormData(); 
            var form_data = $('#tiedostopaivitys').serializeArray();
            
            var file_data = $('input[name="mtiedosto"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("tiedosto", file_data[i]);                
            }

            var file_data = $('input[name="mtiedosto2"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("tiedosto2", file_data[i]);                
            }
            tiedot.append("muunimi", muunimi);
            tiedot.append("muunimi2", muunimi2);
            tiedot.append("kommentti", kommentti);
            tiedot.append("tiedostoid", tiedostoid);
            tiedot.append("toiminto", "paivitatiedosto");

            $.ajax({
                url: "ajax_tiedostot.php",
                type: "POST",
                data: tiedot,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data){

                    console.log ('Päivitetään ' + data.rivi );
                    $('#tiedostorivi' + data.rivi).html( data.jono );

                }
            });

          });



        })
    </script>
      <!--JavaScript at end of body for optimized loading-->
      <script type="text/javascript" src="js/materialize.min.js"></script>
    </body>
