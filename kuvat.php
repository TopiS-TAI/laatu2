<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$haku = $yhteys_pdo->prepare("SELECT t.id, t.kategoria, t.nimi, t.paate, t.polku, DATE_FORMAT(t.lisatty, '%d.%m.%Y') as lisatty, t.poistettu, k.sukunimi, k.etunimi FROM $tietokanta.kuvat t 
LEFT JOIN $tietokanta.kayttajat k ON k.id = t.lisaaja 
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
                        <li><a href="tiedostot.php">Tiedostot</a></li>
                        <li class="active"><a href="kuvat.php">Kuvat</a></li>
                        <li style="width: 60px">&nbsp;</li>
                        <li><a href="index.php?act=out">Kirjaudu ulos</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>   
    <div class="row">
        <div class="col s12 m12 l8">
            <div class="card grey lighten-5">
                <div class="card-content black-text" style="padding-bottom: 5px;">
                    <span class="card-title">
                        Kuvat
                    </span>                    
                    <div class="row">
                        <?php
                        for ($cc=0;$cc<$c;$cc++)
                        { ?>
                            <div class="col s12" >
                                <ul class="collection with-header">
                                    <li class="collection-header"><h6 style="font-size: 1.2em"><?php echo $ka_otsikko[$cc];?></h6></li>
                                </ul><div class="row" style="margin-top: -20px;" id="tiedostokategoria<?php echo $ka_id[$cc];?>">
                            
                            
                            
                            
                            </div>
                                
                        
                            <?php
                            for ($ff=0;$ff<$f;$ff++)
                            { 
                                if (ISSET($t_kategoria[$ka_id[$cc]][$ff]) AND $t_kategoria[$ka_id[$cc]][$ff] == $ka_id[$cc])
                                { ?>
                                                            
                                    <div class="col s6 m3 l2" id="tiedostorivi<?php echo $t_id[$ka_id[$cc]][$ff];?>">
                                        <div class="card">
                                        <div class="card-image">
                                            <img src="<?php echo $t_polku[$ka_id[$cc]][$ff]?>">
                                        
                                        </div>
                                        <div class="card-content">
                                            <p><?php echo $t_nimi[$ka_id[$cc]][$ff]?></p>
                                        </div>
                                        <div class="card-action">
                                            <i class="material-icons modal-trigger" id="muokkaa<?php echo $t_id[$ka_id[$cc]][$ff];?>" data-target="modal1" style="cursor: pointer" >refresh</i>
                                            <i class="material-icons" id="poista <?php echo $t_id[$ka_id[$cc]][$ff];?>" style="cursor: pointer">delete_forever</i>
                                        </div>
                                        </div>
                                    </div>
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
       
        <div class="col s12 m12 l4">
            <div class="card grey lighten-5">
                <div class="card-content black-text" style="padding-bottom: 5px;">
                    <span class="card-title">
                        Uusi kuva
                    </span>                    
                    <div class="row">
                        <div class="col s12">
                            <div class="row"><form accept-charset="multipart/form-data" id="uusitiedosto">
                                <div class="input-field col s12">
                                    <select id="kategoria" name="kategoria">
                                    <option value="" disabled selected>[valitse]</option>
                                    <?php 
                                    for ($cc=0;$cc<$c;$cc++)
                                    { ?>
                                        <option value="<?php echo $ka_id[$cc];?>"><?php echo $ka_otsikko[$cc];?></option>                                
                                    <?php
                                    } ?>
                                    </select>
                                    <label>Kategoria</label>

                                </div>
                                <div class="input-field col s12">
                                    <input placeholder="Jätä tyhjäksi jos nimi on hyvä" id="uunimi" name="uunimi" type="text">
                                    <label for="nimi">Kuvan nimi</label>
                                </div>
                                <div class="file-field col s12 input-field">
                                    <div class="btn">
                                        <span>Kuva</span>
                                        <input type="file" name="uutiedosto" id="uutiedosto">
                                    </div>
                                    <div class="file-path-wrapper">
                                        <input class="file-path validate" type="text">
                                    </div>
                                </div>
                                <div class="col s12">
                                    <a id="talletatiedosto" class="waves-effect waves-light btn">Talleta</a>
                                </div>
                                </form>
                            </div>
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>   
    <!-- Tiedoston muokkaus -modal -->
    <div id="modal1" class="modal" style="max-width: 500px;">
			<div class="row">
				<div class="col s12 teal lighten-2" style="padding: 0px 5px 5px 15px;">
					<h5 class="white-text" id="otsake">Kuvan päivitys<span style="float: right; margin-right: 15px"><i class="material-icons modal-close">close</i></span></h5>
				</div>
			</div>	
			<div class="modal-content" id="paivitys">
			
			</div>
			<div class="modal-footer" style="padding-right: 20px">
                <a  class="waves-effect waves-light btn-small yellow black-text modal-close ">Peruuta</a>
                <a id="talletapaivitys" class="waves-effect waves-light btn-small modal-close ">Päivitä</a>
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

            $('body').on('click', '[id^=poista]', function(e){ 

                var id  = $(this).attr('id');  
                var nro = id.match(/\d+/);
                var tiedot = 'tiedostoid=' + nro + '&toiminto=poistatiedosto';

                $.ajax({  

                    type: "POST", 
                    url: "ajax_kuvat.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        $('#' + data.pois ).fadeOut('slow', function() { li.remove(); });  

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
                    url: "ajax_kuvat.php",
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
            
            var file_data = $('input[name="uutiedosto"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("uutiedosto", file_data[i]);                
            }
            tiedot.append("kayttaja", tunniste);
            tiedot.append("kategoria", kategoria);
            tiedot.append("uunimi", uunimi);
            tiedot.append("toiminto", "uusitiedosto");

            console.log('Talletetaan');

            $.ajax({  

                type: "POST", 
                url: "ajax_kuvat.php",
                data: tiedot,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data){

                    $('#uusitiedosto').trigger("reset");
                    $('#tiedostokategoria' + data.kategoria).prepend( data.jono );
                 
                },
                error: function(data){ 							           		
                                        
                }
                });

            });

          $('body').on('click', '[id=talletapaivitys]', function(e) {
            
            var tiedostoid = $('#tiedostoid').val();

            var tiedot = new FormData(); 
            var form_data = $('#tiedostopaivitys').serializeArray();
            
            var file_data = $('input[name="tiedosto"]')[0].files;
            for (var i = 0; i < file_data.length; i++) {                
              tiedot.append("tiedosto", file_data[i]);                
            }
            tiedot.append("kayttaja", tunniste);
            tiedot.append("tiedostoid", tiedostoid);
            tiedot.append("toiminto", "paivitatiedosto");

            $.ajax({
                url: "ajax_kuvat.php",
                type: "POST",
                data: tiedot,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data){

                    console.log ('Päivitetään ' + data.jono );

                }
            });

          });



        })
    </script>
      <!--JavaScript at end of body for optimized loading-->
      <script type="text/javascript" src="js/materialize.min.js"></script>
    </body>
