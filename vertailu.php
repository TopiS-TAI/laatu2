<?php

$log_file = "php.log";
error_reporting(E_ALL & ~E_NOTICE); 
ini_set('log_errors', TRUE);  
ini_set('error_log', $log_file);

// tietokantayhteys
include 'yhteys.php';

//  haetaan kategoriat

$linkkijono = "[";
$kuvajono = "[";

$haku = $yhteys_pdo->prepare("SELECT paa, otsikko FROM $tietokanta.sisallysluettelo WHERE taso1 IS NULL and taso2 IS NULL and taso3 IS NULL order by paa");
$haku->execute();
while ($ha = $haku->fetch(PDO::FETCH_ASSOC))
{
    $haku2 = $yhteys_pdo->prepare("SELECT nimi, polku FROM $tietokanta.tiedostot 
    WHERE kategoria = :kategoria AND poistettu <> '1'
    order by kategoria, lisatty");
    $haku2->execute(array(':kategoria'=>$ha['paa']));
    $r=0;
    while ($ti = $haku2->fetch(PDO::FETCH_ASSOC))
    {
        $tiedosto[$r] = $ti['nimi'];
        $polku[$r] = $ti['polku'];
        $r++;
    }

    if ($r > 0)
    {
        $linkkijono.="{title: '".$ha['otsikko']."',menu: [";

        for($rr=0;$rr<$r;$rr++)
        {
            $linkkijono.= "{title: '".$tiedosto[$rr]."', value: 'https://suomenitratkaisut.fi/tai/laatu/".$polku[$rr]."'},";
        }
        
        $linkkijono.="],},";
    }

    $haku3 = $yhteys_pdo->prepare("SELECT nimi, polku FROM $tietokanta.kuvat 
    WHERE kategoria = :kategoria AND poistettu <> '1'
    order by kategoria, lisatty");
    $haku3->execute(array(':kategoria'=>$ha['paa']));
    $u=0;
    while ($ti = $haku3->fetch(PDO::FETCH_ASSOC))
    {
        $tiedosto[$u] = $ti['nimi'];
        $polku[$u] = $ti['polku'];
        $u++;
    }

    if ($u > 0)
    {
        $kuvajono.="{title: '".$ha['otsikko']."',menu: [";

        for($uu=0;$uu<$u;$uu++)
        {
            $kuvajono.= "{title: '".$tiedosto[$uu]."', value: 'https://suomenitratkaisut.fi/tai/laatu/".$polku[$uu]."'},";
        }
        
        $kuvajono.="],},";
    }
    
}

// haetaan roolit
$hakur = $yhteys_pdo->prepare("SELECT nro, rooli FROM $tietokanta.roolit ORDER BY jarjestys");
$hakur->execute();
$p=0;
while ($hr = $hakur->fetch(PDO::FETCH_ASSOC))
{
    $rooli_nro[$p] = $hr['nro'];
    $rooli_rooli[$p] = $hr['rooli'];
    $p++;
}

$kuvajono.="],";
$linkkijono.="],";
//echo $linkkijono;

?>

<!DOCTYPE html>
  <html>
    <head>
        <title>Laatukäsikirja - hallinta</title>
      <!--Import Google Icon Font-->
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <!--Import materialize.css-->
      <link type="text/css" rel="stylesheet" href="css/materialize.min.css"  media="screen,projection"/>
      <link type="text/css" rel="stylesheet" href="css/muutokset.css"  media="screen,projection"/>
      <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
           
      <!-- https://artisansweb.net/trix-an-open-source-wysiwyg-editor-for-websites/ -->

      <!--Let browser know website is optimized for mobile-->
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <script type="text/javascript" src='tinymce/tinymce.min.js' referrerpolicy="origin"></script>  
      <script type="text/javascript">
        tinymce.init({
            selector: '#myTextarea',
            images_upload_url: 'postAcceptor.php',
            link_list: <?php echo $linkkijono;?>
            image_list: <?php echo $kuvajono;?>
            language: 'fi',
            entity_encoding : "raw",
            height: '500px',
            plugins: [
            'advlist autolink link image lists charmap print preview hr anchor pagebreak',
            'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime nonbreaking',
            'table template paste help'
            ],
            toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | link image | print fullpage | ' +
            'forecolor backcolor | help | code',
            
            menubar: 'edit view insert format tools table help',
			contextmenu: "link image imagetools lists table",
            content_css: 'css/content.css'
        });
        </script>
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
                    <li><a href="kuvat.php">Kuvat</a></li>
                    <li class="active"><a href="vertailu.php">Vertailu</a></li>
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
                Laatukäsikirjan tietojen päivitys
            </span>
            
            <div class="row"><form id="tiedot">
                <span id="ylaosa">
                <div class="col s12 m6">
                    <h6>Kategoria (päätaso)</h6>
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate1" type="radio" value="1" />
                            <span>Koulutussuunnittelu</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate2" type="radio" value="2" />    
                            <span>Markkinointi ja viestintä</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate3" type="radio" value="3" />
                            <span>Opiskelijavalinta</span>
                        </label>
                    </p> 
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate4" type="radio" value="4" />
                            <span>Koulutuksen aloitus</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate5" type="radio" value="5" />
                            <span>Osaamisen hankkiminen</span>
                        </label>
                    </p>
					<p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate6" type="radio" value="6" />
                            <span>Arviointi ja osaamisen todentaminen</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate7" type="radio" value="7" />
                            <span>Koulutuksen lopetus</span>
                        </label>
                    </p>
					<p>
                        <label>
                            <input class="with-gap" name="kategoria" id="kate8" type="radio" value="8" />
                            <span>Koulutuksen järjestäjän tuki- ja yhteistyöprosessit</span>
                        </label>
                    </p><input type="hidden" id="valittukategoria"><input type="hidden" id="rivi">
                </div>
                <div class="col s12 m3">
                    <h6>Roolit</h6>
                    <p>
                    <?php
                    for ($e=0;$e<7;$e++)
                    { ?>
                        <label>
                            <input type="checkbox" name="rooli" class="with-gap" id="rooli<?php echo $rooli_nro[$e];?>" />
                            <span class="hallintavalinta"><?php echo $rooli_rooli[$e];?></span>
                        </label><br />
                    <?php
                    } ?>                  
                    
                </p>
                </div>
                <div class="col s12 m3">
                    <h6>&nbsp;</h6>
                    <p>
                        <?php
                        for ($e=7;$e<$p;$e++)
                        { ?>
                            <label>
                                <input type="checkbox" name="rooli" class="with-gap" id="rooli<?php echo $rooli_nro[$e];?>" />
                                <span class="hallintavalinta"><?php echo $rooli_rooli[$e];?></span>
                            </label><br />
                        <?php
                        } ?>    
                    </p>
                    <p><button id="vertaa">Hae</button></p>
                </div>
                <div class="col s12"><br />                   
                    <div class="row">
                        <div class="input-field col s12" style="margin-top: -20px;">
                            <input type="text" class="browser-default" disabled id="paataso" style="border: 1px solid #C0C0C0; width: 26px; height: 32px; margin-top: 10px; text-align: center; font-size: 18px;">
                            <input type="text" class="browser-default" disabled id="alataso" style="border: 1px solid #C0C0C0; width: 26px; height: 32px; margin-top: 10px; text-align: center; font-size: 18px;">
                            <input type="text" class="browser-default" disabled id="alataso2" style="border: 1px solid #C0C0C0; width: 26px; height: 32px; margin-top: 10px; text-align: center; font-size: 18px;">
							<input type="text" class="browser-default" disabled id="otsikko" style="border: 1px solid #C0C0C0; width: 730px; height: 32px; margin-top: 8px; padding-left: 5px;font-size: 18px;">
                            <span id="masterspan" style="display: none">
                                <a class="waves-effect waves-light btn-small modal-trigger" id="masterbtn" data-target="modal10" style="margin-top: -7px; margin-left: 20px; height: 31px" ><i class="material-icons right">content_copy</i>synkronoi</a>
                                &nbsp;<span id="syncmaara" style="font-size: 1.0em; color: green; margin-top: -3px; margin-left: 10px;"></span>
                            </span>
                        </div>
                       
                    </div>
                </div>
                </span>
                <div class="col s12 right-align" id="koko" style="margin-top: -40px;">                    
                    <i class="material-icons" id="laajenna" style="cursor: pointer;">arrow_drop_up</i>
                </div>
                <div class="col s12" style="margin-top: -20px;">
                    <div id="editori">
                        <textarea id="myTextarea" style="width: 100%"></textarea>
                    </div>
                </div>
                
                <div class="col s12" id="tallennusnapit">
                    <a class="waves-effect waves-light btn-small" id="talleta" style="margin-top: 15px;">Julkaise</a>
                    <a class="waves-effect waves-light btn-small" id="draftitallennus" style="margin-top: 15px; margin-left: 20px;">Talleta vedoksena</a>
                </div></form>
            </div>
        
        </div>
    </div>
    </div>
    <div class="col s12 m12 l4">
      <div class="card grey lighten-5">
        <div class="card-content black-text">
            <span class="card-title">
                <div class="row">
                    <div class="col s7">
                      Sisällysluettelo
                    </div>
                    
                    <div class="col s5" style="margin-top: -10px;">                           
                        <select id="pdf">
                            <option value="0">Tulosta pdf</option>
                            <option value="1">Valittu kategoria</option>
                            <option value="2">Kaikki</option>
                        </select>
                        
                    </div>
                </div>
            </span>
            <span id="sisallysluettelo">
                <p style="text-align: center; margin-top: 140px; margin-bottom: 80px;"><i class="medium material-icons red-text">info</i>
                <br />Valitse ensin kategoria</p>
            </span>
        </div>
        
      </div>
    </div>
  </div>

  <div id="spinneri" style="display: none;">
    <div class="progress" style="width: 340px">
        <div class="indeterminate"></div>
    </div>
    <div style="text-align: center;"><p style="margin: 15px 0 10px 0">Tulostetta valmistellaan</p></div>
  </div>

  <!-- Modal Structure -->
  <div id="modal10" class="modal modal-fixed-footer">
    <div class="modal-content">
      <h4>Sisällön synkronointi</h4>
      <p id="synkronointisis"></p>
    </div>
    <div class="modal-footer" style="padding-right: 30px;">
        <a href="#!" class="modal-close waves-effect btn" style="margin-right: 15px; background-color: #fff9c4; color: black">Peruuta</a>
      <a href="#!" id="paivitamaster" class="modal-close waves-effect waves-green btn">Päivitä</a>
    </div>
  </div>

	  
    <script>
		  $(document).ready(function(){

            $('.collapsible').collapsible();
            $('select').formSelect();
            $('.modal').modal();
			
			sessionStorage.removeItem('otsikot');
			sessionStorage.removeItem('valittuotsikko');
			sessionStorage.removeItem('rooli');
			sessionStorage.removeItem('kategoria');
			
			var tunniste = localStorage.getItem('taitunniste');

            if (tunniste === null)
            {
                window.location.replace("index.php");
            }              

              var tiedot = 'toiminto=haevalikko'; 

                $.ajax({  
                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){
                                                                           
                            $('#sisrooli').html( data.jono );    
                            $('select').formSelect();                                       
                       
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });

            $('body').on('click', '[id=paivitamaster]', function(e){

                var kategoria = sessionStorage.kategoria;
                var otsikot = sessionStorage.otsikot;
                var tunniste = localStorage.getItem('taitunniste');
                var rooli = sessionStorage.rooli;

                var tunniste = localStorage.getItem('taitunniste');

                var tiedot = 'kayttaja=' + tunniste + '&otsikko=' + otsikot + '&kategoria=' + kategoria + '&rooli=' + rooli + '&toiminto=paivitamaster';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                         $('#tallennusnapit').show();

                         $('#syncmaara').html( data.jono );

                    },
                    error: function(data){ 							           		
                                            
                    }
                }); 

            });

            $('body').on('change', '[id^=ssynkro]', function(e){ 
            
                var id  = $(this).attr('id');  
				var nro = id.match(/\d+/);
				
				var kategoria = sessionStorage.kategoria;
                var otsikot = sessionStorage.otsikot;
                var tunniste = localStorage.getItem('taitunniste');

                if(document.getElementById('ssynkro' + nro).checked)
                {
                    var valittu = 1;
                }
                else
                {
                    var valittu = 0;
                }

                console.log('Valittu ' + valittu);

                var tiedot = 'kayttaja=' + tunniste + '&valittu=' + valittu + '&otsikko=' + otsikot + '&rooli=' + nro + '&kategoria=' + kategoria + '&toiminto=valitsesync';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                         

                    },
                    error: function(data){ 							           		
                                            
                    }
                });


            });

            $('body').on('click', '[id=masterbtn]', function(e){ 

               

                var kategoria = sessionStorage.kategoria;
                var rooli = sessionStorage.rooli;
                var otsikot = sessionStorage.otsikot;
                var tunniste = localStorage.getItem('taitunniste');

                var tiedot = 'kayttaja=' + tunniste + '&kategoria=' + kategoria + '&rooli=' + rooli + '&otsikko=' + otsikot + '&toiminto=avaasynkronointimodal';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        $('#synkronointisis').html( data.jono ); 

                    },
                    error: function(data){ 							           		
                                            
                    }
                });

            });

     
			// vaihdetaan roolia radiobuttoneilla, tuodaan näkyviin muokkauspainikkeet sisällysluetteloon
			$('body').on('click', '[id^=roolix]', function(e){ 
			
				var id  = $(this).attr('id');  
				var nro = id.match(/\d+/);

                console.log('id', id)
				
				var otsikot = sessionStorage.otsikot;
				
				
                var rooli = nro;
				
				sessionStorage.rooli = rooli;			
				
                var kategoria = sessionStorage.kategoria;   
                
                console.log('Vaihdetaan roolia');

                console.log('Rooli: ' + rooli + ', kategoria: ' + kategoria );


                var tiedot = 'otsikot=' + otsikot + '&rooli=' + rooli + '&kategoria=' + kategoria + '&toiminto=haenapit';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        console.log('Infoo ' + data.info );

                        $('#sisallysluettelo').html( data.sisallysluettelo );   
                        $('#syncmaara').html( data.maara );

                        console.log('Piilotetaan ' + data.master );
                        /*
                        if ( data.master === 0)
                        {
                            $('#masterbtn').hide();
                            $('#tallennusnapit').hide();

                            tinymce.activeEditor.setContent( '' );
                            $('#paataso').val('');
                            $('#alataso').val('');
                            $('#alataso2').val('');
                            $('#otsikko').val('');
                   
                        }
                        else
                        {
                            $('#masterbtn').show();
                            $('#tallennusnapit').show();
                        }

                        tinymce.activeEditor.setContent( data.sisalto );

                        */
                            // nämä tuli tilalle
                            $('#masterbtn').hide();
                            $('#tallennusnapit').hide();
                            tinymce.activeEditor.setContent('' );
                            $('#paataso').val('');
                            $('#alataso').val('');
                            $('#alataso2').val('');
                            $('#otsikko').val('');

                        

                        M.updateTextFields();

                    },
                    error: function(data){ 							           		
                                            
                    }
                });

            });

            $('body').on('click', '#vertaa', function(e) {
                e.preventDefault()
                const checkboxes = $('[id^=rooli]')
                var kategoria = sessionStorage.kategoria
                const roolit = []
                for (box of checkboxes) {
                    if (box.checked) {
                        roolinro = parseInt(box.id.slice(5))
                        roolit.push(roolinro)
                    }
                }
                console.log('click', roolit)

                var tiedot = 'roolit=' + roolit + '&kategoria=' + kategoria + '&toiminto=vertaile';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){
                        console.log('data', data)
                    }

                // SELECT * FROM sis WHERE kategoria = 2 AND rooli in (2, 12);
            })
            })

			
			$('body').on('click', '[id^=kate]', function(e){ 
                // vaihdetaan lomakkeella kategoriaa. Haetaan ko. kategorian sisällysluettelo näkyviin

                //napataan klikatun linkin id talteen, vaikka 'kate3'
                var id = $(this).attr('id');
                //napataan id:stä pelkkä numero eli 'kate3' -> '3'
                var nro = id.match(/\d+/);

                console.log('Vaihdetaan kategoria..');
				
				sessionStorage.kategoria = nro;

                $('#rivi').val( '' );

                // haetaan valitun kategorian numero (nyt 1-5)
                $('#valittukategoria').val(nro);
                var rooli = sessionStorage.rooli; // sisällysluettelun rooli -valinta. Oletuksena arvo 99
                console.log ('Rooli: ' + rooli)
                
                var tiedot = 'kategoria=' + nro + '&rooli=' + rooli + '&toiminto=vaihdakategoria';

                $.ajax({  

                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){

                            console.log('jep ' + data.otsikot );    												 																	
													
							sessionStorage.otsikot = data.otsikot;

                            $('#sisallysluettelo').html( data.sisallysluettelo );   

                            // tähän sisältölaatikon ja otsikkokenttien tyhjennys
                            tinymce.activeEditor.setContent( '' );
                            $('#paataso').val('');
                            $('#alataso').val('');
                            $('#alataso2').val('');
                            $('#otsikko').val('');
                            $('#tallennusnapit').hide();
                                                                                   
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });

                });
            
			$('body').on('click', '[id^=muokkaa]', function(e){ 
                // muokataan aiemmin tehtyä sisältöä (tuodaan sisältö näkyviin)
                e.preventDefault();
                //napataan klikatun linkin id talteen, vaikka 'poista3'
                var id = $(this).attr('id');
                //napataan id:stä pelkkä numero eli 'poista3' -> '3'
                var nro = id.match(/\d+/);
				
				// tallennetaan muokattavan otsikon id sessionStorageeb
				sessionStorage.otsikko = nro;
				
				console.log ('Muokataan ' + nro); 
				
                var kategoria = sessionStorage.kategoria;
				var rooli = sessionStorage.rooli;
				
				console.log('Kategoria: ' + kategoria);
				console.log('Rooli: ' + rooli);
                console.log('Otsikko: ' + nro);

                var tiedot = 'kategoria=' + kategoria + '&rooli=' + rooli + '&otsikko=' + nro + '&toiminto=muokkaa';

                $.ajax({  

                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){
 
                            $('#paataso').val( data.taso );
                            $('#alataso').val( data.alataso );
							$('#alataso2').val( data.alataso2 );
	                        $('#otsikko').val( data.otsikko );
                            $('#syncmaara').html( data.maara );
                            $('#masterspan').show();

                            if ( data.master === 0)
                            {
                                $('#masterbtn').hide();
                                $('#tallennusnapit').hide();
                            }
                            else
                            {
                                $('#masterbtn').show();
                                $('#tallennusnapit').show();
                            }

                            tinymce.activeEditor.setContent( data.sisalto );

                            $('#rivi').val( data.rivi );
                         
                            M.updateTextFields();

                            window.scrollTo(0, 0);
                                                     
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });

                });


			$('body').on('click', '[id^=talleta]', function(e){ 
                // talletetaan lomakkeen tiedot. Tallennus Julkaisee tiedot ja tarkoitus on, että kaikilla ei ole oikeutta julkaista.
                // julkaisun yhteydessä aiempi julkaisu (jos sellainen on) tallennetaan draftiksi 
                e.preventDefault();

                // haetaan lomakkeelta tiedot
                var kategoria = sessionStorage.kategoria;
                var rooli = sessionStorage.rooli; // sis'llysluettelun rooli -valinta
                var tunniste = localStorage.getItem('taitunniste');
                
                var sisalto = tinymce.activeEditor.getContent();

                var rivi = $('#rivi').val(); // päivitettävän rivin id
               
				var otsikko = sessionStorage.otsikko;

                console.log('tallennetaan ' + rivi);
				
	            var tiedot = 'tunniste=' + tunniste + '&otsikko=' + otsikko + '&kategoria=' + kategoria + '&rooli=' + rooli + '&sisalto=' + sisalto + '&rivi=' + rivi + '&toiminto=talleta';
                
                $.ajax({  
                
                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){

                            console.log('jep..');

                            // muutetaan valikon otsikon väri vihreäksi
                            //id="otsikkoteksti62" style="color: green"
                            $('#otsikkoteksti' + data.otsikko).css("color", "green");

                            //tyhjennetään editorin sisältö tallennuksen jälkeen     
                            //tinymce.activeEditor.setContent( data.sisalto );
							M.toast({html: 'Tiedot tallennettu!'})
                           
                                                                                 
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });
            
            });


			$('body').on('click', '[id^=draftitallennus]', function(e){ 
                // tallennetaan uusi drafti. Ajatus on, että opiskelijat voivat tallentaa vain drafteja
                e.preventDefault();

                console.log('toimii');

                // haetaan tiedot lomakkeelta
                var kategoria = sessionStorage.kategoria;
                var rooli = sessionStorage.rooli; 
                var otsikko = sessionStorage.otsikko;
                var tunniste = localStorage.getItem('taitunniste');
  
                var sisalto = tinymce.activeEditor.getContent();               

                //var otsikko = sessionStorage.otsikko;

                var tiedot = 'tunniste=' + tunniste + '&rooli=' + rooli + '&kategoria=' + kategoria + '&otsikko=' + otsikko + '&sisalto=' + sisalto + '&toiminto=talletatrafti';

                $.ajax({  

                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){

                            console.log(data.sisalto);

                            //tyhjennetään editorin sisältö tallennuksen jälkeen                              
                            tinymce.activeEditor.setContent( data.sisalto );                     
                                                           
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });

                });

            $('body').on('click', '[id^=versiot]', function(e){ 
                // avataan näkyviin sisällysluettelon otsikon alle draftit
                e.preventDefault();
                //napataan klikatun linkin id talteen, vaikka 'poista3'
                var id = $(this).attr('id');
                //napataan id:stä pelkkä numero eli 'poista3' -> '3'
                var nro = id.match(/\d+/);

                var kategoria = sessionStorage.kategoria;
                var rooli = sessionStorage.rooli; 

                console.log('Haetaan rivin ' + nro + ' versiot');

                var tiedot = 'rivi=' + nro + '&kategoria=' + kategoria + '&rooli=' + rooli + '&toiminto=haeversiot';

                $.ajax({  

                        type: "POST", 
                        url: "ajax.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data){

                            console.log('jep: ' + data.draftit );
                            $('#draftit' + data.rivi ).html( data.draftit ); 
                            $('#draftit' + data.rivi).toggle();                                                       
                        },
                        error: function(data){ 							           		
                                                
                        }
                    });

                });


            $('body').on('change', '[id^=sisrooli]', function(e){ 

                var rooli = $('#sisrooli').val();
                var kategoria = $('#valittukategoria').val();

                console.log('Valittu rooli ' + rooli);

                var tiedot = 'rooli=' + rooli + '&kategoria=' + kategoria + '&toiminto=vaihdarooli';

                $.ajax({  

                    type: "POST", 
                    url: "ajax.php",
                    data: tiedot,
                    dataType: 'json',
                    cache: false,
                    success: function(data){

                        $('#sisallysluettelo').html( data.sisallysluettelo );     
                        $('#tallennusnapit').hide();                                                 
                    },
                    error: function(data){ 							           		
                                            
                    }
                    });

            });
  

            $('body').on('click', '[id^=laajenna]', function(e){ 


                $('#ylaosa').hide();
                
                $('#editori').animate({height:'700', marginTop: '20'}, 500);

                $('#koko').html('<i class="material-icons" id="pienenna" style="cursor: pointer;">arrow_drop_down</i>')
             
       
            });

            $('body').on('click', '[id^=pienenna]', function(e){ 


                $('#ylaosa').show();

                $('#editori').animate({height:'700'}, 400);

                $('#koko').html('<i class="material-icons" id="laajenna" style="cursor: pointer;">arrow_drop_up</i>')


            });

            

            

                $('body').on('click', '[id^=draftimuokkaa]', function(e){ 

                    // klikataan sisällysluettelossa olevan draftin otsikkoa. Haetaan tiedot lomakkeelle

                    e.preventDefault();
                    //napataan klikatun linkin id talteen, vaikka 'draftimuokkaa3'
                    var id = $(this).attr('id');
                    //napataan id:stä pelkkä numero eli 'draftimuokkaa3' -> '3'
                    var nro = id.match(/\d+/);
                    // kategoria ei muutu 

                    var tiedot = 'rivi=' + nro + '&toiminto=draftimuokkaa';

                    $.ajax({  

                            type: "POST", 
                            url: "ajax.php",
                            data: tiedot,
                            dataType: 'json',
                            cache: false,
                            success: function(data){

                                // syötetään ajax:lta saadut tiedot lomakkeelle
                                $('#paataso').val( data.taso );
                                $('#alataso').val( data.alataso );
                                $('#alataso2').val( data.alataso2 );
                                $('#otsikko').val( data.otsikko );

                                console.log( data.alataso );
                                
                                tinymce.activeEditor.setContent( data.sisalto );
                              

                                M.updateTextFields();

                                window.scrollTo(0, 0);

                                                        
                            },
                            error: function(data){ 							           		
                                                    
                            }
                        });

                    });         
            
            

        
            

                $('body').on('change', '[id^=pdf]', function(e) {

                    var kategoria = $("input[name='kategoria']:checked").val();
                    var rooli = $("input[name='rooli']:checked").val();
                    
                    var nro = $('#pdf').val();

                    var tiedot = 'kategoria=' + kategoria + '&rooli=' + rooli + '&mita=' + nro + '&toiminto=pdf';

                    console.log('Tehdaan pdf ' + kategoria + ' ' + rooli + ' ' + nro);

                    if (nro > 0)
                    {
                        $('.col').css('opacity','0.3');
		                $( "#spinneri" ).show();
                        //$('#pdf[value=0]').attr('selected','selected');
                        $("#pdf").val("0").change();
                        $('select').formSelect();
                    }

                    $.ajax({
                        type: "POST",
                        url: "ajax_pdf.php",
                        data: tiedot,
                        dataType: 'json',
                        cache: false,
                        success: function(data) {

                            console.log( data.tpolku );

                            if (nro > 0)
                            {
                                window.open( data.polku );
                            }

                            $('.col').css('opacity','1');
					        $( "#spinneri" ).hide(); 

                            


                        },
                        error: function(data) {

                        }
                    }); 


                });

          });
    </script>
      <!--JavaScript at end of body for optimized loading-->
      <script type="text/javascript" src="js/materialize.min.js"></script>
    </body>
  </html>