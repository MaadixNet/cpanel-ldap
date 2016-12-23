<?php 
error_reporting(-1);
ini_set('display_errors', 'On');

session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$permissions=$_SESSION["login"]["level"];
require_once('header.php');?>

<?php

//Set variables for ldap connection
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();

if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}

$message='';
$ldaptree    = LDAP_PEOPLE;

if (isset($_POST['updateuser'])){
  $username=trim($_POST["username"]);
  $modifydn='uid='.$username. ',' . $ldaptree;
  $psw1=trim($_POST['pswd1']);
  $psw2=trim($_POST['pswd2']);
  $user_email=$_POST['usermail'];
  # Only update pswd if fields are filled and matches

  if ((!empty($psw1)) && (!empty($psw2)) && ($psw2==$psw1) ) {
    $newpass=ldap_password_hash($psw2,'ssha');
    $info['userpassword'][0]=$newpass;
    $info['shadowlastchange'][0] = floor(time()/86400);
  }

  $info['cn']=$_POST['commonname'];
  $info['sn']=$_POST['surname'];
  $info['mail']=$user_email;

  ## Check authorizesServices
  #
  # ssh is alway active
  # just check vpn 
  #

  if (isset($_POST['vpn'])){
      $info['authorizedservice'][0]='sshd';
      $info['authorizedservice'][1]='openvpn';
  }else {
    $info['authorizedservice']='sshd';
  }
  $edit_user=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
  if($edit_user && isset($_POST["sendinstruction"]) && isset($_POST["vpn"]))$Ldap->send_vpn_instructions($user_email,$username);
  $message=$edit_user["message"];
}

# Get current user data from ldap
 if($ldapbind){
   $filteruser="(&(objectClass=person)(objectClass=metaInfo)(gidnumber=27))";
   $result=$Ldap->search($ldapconn,$ldaptree, $filteruser);
 }
/*echo '<pre>';
print_r($result);
echo '</pre>';
 */
$username=$result[0]['uid'][0];
$usermail = (isset($result[0]['mail'][0]))?$result[0]['mail'][0]:'';
?>
<div id="admin-content" class="content">
<?php echo $message;?>
  <h1 class=""><?php printf(_("Editar Usuario %s"), $username);?>  </h1>
  <hr>
  <h4><?php printf(_("Este es el usuario por defecto del sistema y tiene total acceso al servidor. Por esto no lo puedes borrar ni puedes administrar su acceso sftp, que siempre está garantizado"));?></h4>
	<div class="clear"></div>
		<form autocomplete="off" action="" method="POST" class="form-signin jquery-check">

                <label for="commonname"><h4><?php echo  sprintf(_("Nombre"));?></h4></label><input id="commonname" name="commonname" type="text" maxlength="64" value="<?php echo $result[0]['cn'][0];?>" />                  

               <label for="surname"><h4><?php echo  sprintf(_("Apellidos"));?></h4></label><input id="surname" name="surname" type="text" maxlength="64" value="<?php echo $result[0]['sn'][0];?>" />

              <label for="usermail"><h4><?php printf(_("Correo electrónico"));?></h4></label> 
            
              <input id="usermail" class="usermail" type="mail" name="usermail" value="<?php echo $usermail;?>" required />  
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
              <label for="pswd1"><h4><?php printf(_("Nueva Contraseña"));?></h4></label><input size='4' id='pswd1' type='password' name='pswd1' />

              <label for="pswd2"><h4><?php printf(_("Confirma Contraseña"));?></h4></label><input type='password' id='pswd2' name='pswd2' /><div id="pswresult"></div>
                
                <?php 

                #
                # Get all activated services for user
                #
                
                $services=(isset($result[0]['authorizedservice']))?$result[0]['authorizedservice']:array();
                $vpn=(in_array("openvpn",$services))?'checked="checked"':'';
                ?>

                <div class="clear"></div>
                <h4><?php printf(_("Acceso al servidor Sftp/ssh"));?></h4>
                <?php

                  echo '<p>'. sprintf(_("Directorio Personal")) . '</p>';
                  echo '<pre>' . $result[0]['homedirectory'][0] . '</pre>';
                ?>
                <?php if ($Ldap->check_installed_service('openvpn')){?>
                  <h4><?php printf(_("Cuenta VPN"));?></h4>
                  <input type="checkbox" name="vpn" id="vpn" <?php echo $vpn;?> />
                  <label for="vpn" class="togglehidden" >&nbsp;</label>

                  <div id="hidden">
                  <h4><?php printf(_("Instrucciones"));?></h4>
                  <p><?php printf(_("Puedes enviar al usuario un email con instrucciones para configurar el cliente VPN"));?></p>
                  <p><?php printf(_("NOTA: Las instrucciones incluyen todos los datos necesarios menos la contraseña. Por razones de seguridad proporciona al usuario la  contraseña por otro canal"));?></p>
                  <input type="checkbox" name="sendinstruction" id="sendinstruction" />
                  <label for="sendinstruction" class="left small">&nbsp;</label>&nbsp;<span><?php printf(_("Enviar instrucciones"));?></span></h4> 
                  </div>
                <?php } ?>

                <div class="clear"></div>
                <hr>
                <input type="hidden" name="username" id="username" value="<?php echo $username;?>" />
		<input type="submit" name="updateuser" value="Guardar Cambios" class="btn btn-small btn-primary" />
		</form>

</div><!--admin-content-->
<?php ldap_close($ldapconn);
require_once('footer.php');?>
