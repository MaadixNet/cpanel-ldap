<?php 
session_start();
require_once('header.php');?>

<?php
//Clean vars
$message=$info='';
$ldaptree    = LDAP_PEOPLE;

if (isset($_POST['updateuser'])){
  $psw1=$_POST['pswd1'];
  $psw2=$_POST['pswd2'];
  
  if ($psw1 && $psw1==$psw2){
    $newpass=ldap_password_hash($psw2,'ssha');
    $_POST['pswd1']=$_POST['pswd2']=$newpass;
    $info['userpassword'][0]=$newpass;
    $info['shadowlastchange'][0] = floor(time()/86400);
  } 

  $sanitised_data= sanitizeData($_POST);
  $username = $sanitised_data['username'][0]['value'];
  $modifydn='uid='.$username. ',' . $ldaptree;
  $user_email = $sanitised_data['usermail'][0]['value'];
  # Only update pswd if fields are filled and matches


  $info['cn']=$sanitised_data['commonname'][0]['value'];
  $info['sn']=$sanitised_data['surname'][0]['value'];
  $info['mail']=$user_email;

  ## Check authorizesServices
  #
  # ssh is alway active
  # just check vpn && apache
  #
  $info['authorizedservice'][0]='sshd';
  $info['authorizedservice'][1]='cron';
  $c=2;
  if (isset($_POST['apache'])){
      $info['authorizedservice'][$c]='apache';
      $c++;
  }
  if (isset($_POST['vpn'])){
      $info['authorizedservice'][$c]='openvpn';
      $c++;
  }

    $edit_user=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
    $message=$edit_user["message"];
    if($edit_user && isset($_POST["sendinstruction"]) && isset($_POST["vpn"]))$Ldap->send_vpn_instructions($user_email,$username);
}

# Get current user data from ldap
 if($ldapbind){
   $filteruser="(&(objectClass=person)(objectClass=metaInfo)(gidnumber=27))";
   $result=$Ldap->search($ldapconn,$ldaptree, $filteruser);
 }

$username=$result[0]['uid'][0];
$usermail = (isset($result[0]['mail'][0]))?$result[0]['mail'][0]:'';
require_once('sidebar.php');
?>
<article class="content forms-page">
  <div class="title-block">
    <h1 class=""> <?php printf(_("Editar Usuario %s - Superusuario"), $username);?></h1>
  </div>
  <div class="subtitle-block">
    <h3 class=""> <?php printf(_("Este es el usuario root del sistema y dispone de un acceso total al servidor, con poderes ilimitados. No lo puedes borrar ni eliminar su acceso SFTP/SSH, que está siempre garantizado."));?></h3>
    <h5 class=""> <?php printf(_("Para saber más, visita la siguiente página de nuestra <a href='https://docs.maadix.net/users/#superusuario' target='_blank'>guía de uso</a>"));?></h5>
  </div>
  <section class="section">
  <div id="admin-content" cass="row">
    <hr>
    <?php echo $message;?>
          <div class="card card-block">
            <form role="form" autocomplete="off" action="" method="POST" class="form-signin standard jquery-check">
              <div class="form-group">
                <label class="control-label" for="loginname">Nombre de usuario no editable (Para autenticación SFTP/SSH)</label>
                <pre><?php echo $username;?></pre>
              </div>
                
              <div class="form-group">
                <label class="control-label" for="commonname"><?php echo  sprintf(_("Nombre"));?></label><input id="commonname" name="commonname" type="text" maxlength="64"  class="form-control" value="<?php echo $result[0]['cn'][0];?>" />                  
              </div>
              <div class="form-group">
               <label class="control-label" for="surname"><?php echo  sprintf(_("Apellidos"));?></label><input id="surname" name="surname" type="text" maxlength="64" class="form-control"  value="<?php echo $result[0]['sn'][0];?>" />
              </div>
              <div class="form-group">
                <label class="control-label" for="usermail"><?php printf(_("Correo electrónico"));?></label> 
                <div class="clearfix"></div> 
                <input id="usermail" class="usermail form-control col-sm-4" type="mail" name="usermail" value="<?php echo $usermail;?>" />  
                <?php $resultmail = $Ldap->search($ldapconn,LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');
                $mailcount = $resultmail["count"];
                if($mailcount>0) {
                    echo '<select id="selmail">';
                    echo '<option value="">Seleccionar cuenta existente</option>';
                    for ($c=0; $c<$resultmail["count"]; $c++) {
                        echo '<option value="' . $resultmail[$c]["mail"][0] .'">' . $resultmail[$c]["mail"][0] . '</option>';
                    }
                   echo '</select>';
                };?>
              </div>

              <div class ="clearfix"></div>
              <p></p>
              <div class="form-group">
                <label class="control-label"  for="pswd1"><?php printf(_("Nueva contraseña"));?></label>
                <div id="pswcheck"></div>
                <input class="form-control" size='4' id='pswd1' type='password' name='pswd1' readonly />
              </div>
              <div class="form-group">
                <label class="control-label" or="pswd2"><?php printf(_("Confirma la contraseña"));?></label><input class="form-control" type='password' id='pswd2' name='pswd2' /><div id="pswresult"></div>
              </div> 
              <?php 

                #
                # Get all activated services for user
                #
                
                $services=(isset($result[0]['authorizedservice']))?$result[0]['authorizedservice']:array();
                $vpn=(in_array("openvpn",$services))?'checked="checked"':'';
                $apache=(in_array("apache",$services))?'checked="checked"':'';
                ?>

                <div class="clear"></div>
                <?php

                echo '<p><b>'. sprintf(_("Directorio Personal")) . '</b></p>';
                echo '<div class="box-placeholder">' . $result[0]['homedirectory'][0] . '</div>';
                ?>
                <?php if ($Ldap->check_installed_service('openvpn')){?>
                  <h4><?php printf(_("Cuenta VPN"));?></h4>
                  <div> <label>
                    <input class="checkbox togglehidden" type="checkbox" type="checkbox" name="vpn" id="vpn" <?php echo $vpn;?> />
                    <span><?php printf(_("Activar cuenta VPN"));?></span>
                  </label> </div>

                  <div id="hidden">
                    <h4><?php printf(_("Instrucciones"));?></h4>
                    <p><?php printf(_("Envía al usuario un correo electrónico con los archivos de configuración y las instrucciones para configurar el cliente VPN."));?></p>
                    <p><?php printf(_("Advertencia: Las instrucciones incluyen todos los datos necesarios menos la contraseña, que por razones de seguridad debes proporcionar al usuario  por otro canal"));?></p>

                    <div> <label>
                      <input class="checkbox small" type="checkbox" name="sendinstruction" id="sendinstruction" />
                      <span><?php printf(_("Enviar instrucciones"));?></span>
                    </label> </div>

                  </div>
                <?php } ?>

                <?php if ($Ldap->check_installed_service('phpmyadmin')){?>
                  <h4><?php printf(_("Acceso aplicación phpMyAdmin"));?></h4>
                  <div> <label>
                    <input class="checkbox" type="checkbox" type="checkbox" name="apache" id="apache" <?php echo $apache;?> />
                    <span><?php printf(_("Activar acceso aplicación protegida phpMyAdmin"));?></span>
                  </label> </div>

                <?php } ?>



                <div class="clear"></div>
                <hr>
                <input type="hidden" name="username" id="username" value="<?php echo $username;?>" />
		<input type="submit" name="updateuser" value="Guardar cambios" class="btn btn-small btn-primary" />
		</form>
      </div><!--class="card card-block sameheight-item"-->
    </div><!--admin-content-->
  </section>
</article>
<?php ldap_close($ldapconn);
require_once('footer.php');?>
