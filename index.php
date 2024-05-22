<?php

if (isset($_GET['act']) AND $_GET['act'] == "out")
{ ?>
<script>
  localStorage.removeItem('taitunniste');
</script>
<?php
} 
?>
<!DOCTYPE html> 
  <html>
    <head>
    <meta charset="UTF-8">
      <!--Import Google Icon Font-->
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <!--Import materialize.css-->
      <link type="text/css" rel="stylesheet" href="css/materialize.min.css"  media="screen,projection"/>

      <!--Let browser know website is optimized for mobile-->
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>TAI</title>
    </head>

    <body>
      <!--Import jQuery before materialize.js-->
      <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
      <script type="text/javascript" src="js/materialize.min.js"></script>


<div class="container"> 
  <div class="row"> 
  <div class="col s10 m8 l6 offset-l3 offset-m2 offset-s1"> 
    <h2></h2>
      <div class="card grey lighten-5">
        <div class="row" style="background-color: #455a64; margin: -2px; padding: 15px;"><span class="card-title white-text" style="margin-left: 10px">TAI</span>

        </div>
        <div class="card-content">
          <div class="row">
            
            <form>
             
            <div class="input-field col s12"><i class="material-icons prefix">person</i>
              <input type="text" id="kt" name="kayttajanimi" autofocus>
              <label class="black-text">Käyttäjänimi</label>
            </div>
            <div class="input-field col s12"><i class="material-icons prefix">vpn_key</i>
              <input type="password" id="ss" name="salasana" autoComplete="new-password">
              <label id="ss" class="black-text active">Salasana</label>
            </div>
			  <p style="margin-top: 20px; margin-left: 43px">
				  <label>
					<input type="checkbox" id="muistaminut" name="muistaminut" value="1" class="filled-in"  />
					<span>Muista minut tällä laitteella</span>
				  </label>
				</p>
            
            <div style="text-align:center;">
              <input class="btn blue-grey darken-2 white-text" id="kirjaudu" type="submit" style="margin-top: 25px;" value="Kirjaudu">
            </div>
            </form>
        </div>
      </div>
    </div>
  </div>
  </div>
</div>
  <script>
  	$(document).ready(function(){

    $( "#kirjaudu" ).click(function(e) {

        e.preventDefault();	
        var kt = $('#kt').val();
        var ss = $('#ss').val();

        console.log('Kirjaudutaan');

        var tiedot = 'kt=' + kt + '&ss=' + ss + '&toiminto=kirjaudu';

        $.ajax({
              type: "POST",
              url: "ajax_index.php", 
              data: tiedot,
              dataType: 'json',
              cache: false,
              success: function(data){	
              
                console.log('ok, ' + data.virhe );  
                
                if ( data.virhe !== 1)
                {
                    localStorage.taitunniste = data.tunniste;
                    window.location.href = data.osoite;
                }   

              },
              error: function(data){
                          
                          
              }
            });


    });
	
    $('#password').focus(function(){ $(this).select() });
	
    $('#kt').val(localStorage.getItem('kt'));
    $('#ss').val(localStorage.getItem('ss'));
    
    if (localStorage.getItem('muistaminut') == 1)
    {
      $('#muistaminut').prop('checked', true);
    }
    
    M.updateTextFields();
    
    $('body').on('change', '[id^=muistaminut]', function(e){
    
        if ($('#muistaminut').is(':checked')) {
      
        console.log ( 'jep' );
              // tallennetaan kt ja ss localstorageen
              localStorage.kt = $('#kt').val();
              localStorage.ss = $('#ss').val();
              localStorage.muistaminut = $('#muistaminut').val();
          } else {
      
              console.log ( 'Nono' );
              localStorage.kt = '';
              localStorage.ss = '';
              localStorage.muistaminut = '';
          }
      });

	});
	
  </script>
</body>
</html>
        