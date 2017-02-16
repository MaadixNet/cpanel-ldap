<?php 
//error_reporting(-1);
//ini_set('display_errors', 'On');

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
require_once('sidebar.php');
?>
<?php echo $message;?>
<article class="content forms-page">
  <div class="title-block">
  <h3 class="title"> <?php printf(_("Editar Usuario %s"), $username);?></h3>
  </div>
    <div class="subtitle-block">
    <h3 class="subtitle"> <?php printf(_("Este es el usuario por defecto del sistema y tiene total acceso al servidor. Por esto no lo puedes borrar ni puedes administrar su acceso sftp, que siempre está garantizado"));?></h3>
    </div>
  <section class="section">
  <div id="admin-content" cass="row">
  <hr>
          <div class="card card-block">
		<form role="form" autocomplete="off" action="" method="POST" class="form-signin standard jquery-check">
                <div class="form-group">
                <label class="control-label" for="commonname"><?php echo  sprintf(_("Nombre"));?></label><input id="commonname" name="commonname" type="text" maxlength="64"  class="form-control" value="<?php echo $result[0]['cn'][0];?>" />                  
                </div>
                <div class="form-group">
               <label class="control-label" for="surname"><?php echo  sprintf(_("Apellidos"));?></label><input id="surname" name="surname" type="text" maxlength="64" class="form-control"  value="<?php echo $result[0]['sn'][0];?>" />
              </div>
              <div class="form-group">
              <label class="control-label" for="usermail"><?php printf(_("Correo electrónico"));?></label> 
              <div class="clearfix"></div> 
              <input id="usermail" class="usermail form-control col-sm-4" type="mail" name="usermail" value="<?php echo $usermail;?>" required />  
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
              <div class="form-group">
              <label class="control-label"  for="pswd1"><?php printf(_("Nueva Contraseña"));?></label>
              <div id="pswcheck"></div>
              <input class="form-control" size='4' id='pswd1' type='password' name='pswd1' readonly />
              </div>
              <div class="form-group">
              <label class="control-label" or="pswd2"><?php printf(_("Confirma Contraseña"));?></label><input class="form-control" type='password' id='pswd2' name='pswd2' /><div id="pswresult"></div>
               </div> 
                <?php 

                #
                # Get all activated services for user
                #
                
                $services=(isset($result[0]['authorizedservice']))?$result[0]['authorizedservice']:array();
                $vpn=(in_array("openvpn",$services))?'checked="checked"':'';
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
                    <span><?php printf(_("Activar Cuenta VPN"));?></span>
                  </label> </div>

                  <div id="hidden">
                  <h4><?php printf(_("Instrucciones"));?></h4>
                  <p><?php printf(_("Envia al usuario un email con los archivos de configuración y las instrucciones para configurar el cliente VPN."));?></p>
                  <p><?php printf(_("NOTA: Las instrucciones incluyen todos los datos necesarios menos la contraseña. Por razones de seguridad proporciona al usuario la  contraseña por otro canal"));?></p>

                  <div> <label>
                    <input class="checkbox small" type="checkbox" name="sendinstruction" id="sendinstruction" />
                    <span><?php printf(_("Enviar instrucciones"));?></span>
                   </label> </div>

                  </div>
                <?php } ?>

                <div class="clear"></div>
                <hr>
                <input type="hidden" name="username" id="username" value="<?php echo $username;?>" />
		<input type="submit" name="updateuser" value="Guardar Cambios" class="btn btn-small btn-primary" />
		</form>
      </div><!--class="card card-block sameheight-item"-->
    </div><!--admin-content-->
  </section>
</article>
<?php ldap_close($ldapconn);
require_once('footer.php');?>
