<?php 
//error_reporting(-1);
//ini_set('display_errors', 'On');

session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$permissions=$_SESSION["login"]["level"];
require_once('header.php');
require_once('sidebar.php');
?>
<?php

//Set variables for ldap connection
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
//set $info as empty
$info='';
$message='';
$ldaptree    = LDAP_PEOPLE;
$selecteduser=(isset($_GET['user']))?$_GET['user']:'';
$users_group_tree = "cn=web,ou=groups,ou=People," . SUFFIX;
$users_in = $Ldap->search($ldapconn, $users_group_tree ,"(&(memberuid=$selecteduser))");
#$all_users_in = $Ldap->search($ldapconn, $users_group_tree ,"(&(memberuid=*))");
if (isset($_POST['updateuser']) && (!empty($selecteduser))){
  $modifydn='uid='.$selecteduser. ',' . $ldaptree;
  $psw1=$_POST['pswd1'];
  $psw2=$_POST['pswd2'];
  # Only update pswd if fields are filled and matches

  if ((!empty($psw1)) && (!empty($psw2)) && ($psw2==$psw1) ) {
    //First ebcrynt password so no plain text is used
    $newpass=ldap_password_hash($psw2, "ssha");
    $_POST['pswd1'] = $newpass;
    $_POST['pswd2'] = $newpass;
    $info['userpassword'][0]=$newpass;
    $info['shadowlastchange'][0] = floor(time()/86400);
  }
  //Sanitize user inputs
  $sanitised_data= sanitizeData($_POST);
  $user_email=$sanitised_data['usermail'][0]['value'];
  $info['cn']=$sanitised_data['commonname'][0]['value'];
  $info['sn']=$sanitised_data['surname'][0]['value'];
  $info['mail']=$user_email;

  ## Check authorizesServices
  #
  # ssh has been checked
  #

  $c=0;
  if (isset($_POST['sshd'])){
      $groupinfo = posix_getgrnam("sftpusers");
      $grid=$groupinfo["gid"];
      $info['gidnumber']=(int)$grid;
      $info['loginshell']='/bin/bash';
      $info['homedirectory']='/home/sftpusers/' . $selecteduser;
      $info['authorizedservice'][$c]='sshd';
      $c++;
      if ($users_in["count"] == 0){
        //This user was not in the web gorup so Add  user to  web group as he has sftp 
            $group['memberuid'] = $selecteduser;
            ldap_mod_add($ldapconn, $users_group_tree, $group);
      }
  } else {

      $info['loginshell']='none';
      $info['homedirectory']='none';
      //dont know if in php set an empty attribute wors as to remove it
      $info['authorizedservice']='';
      if ($users_in["count"] > 0){
        //This user was  in the web gorup sftp access has been removed, so remove user from group
        // web and from apache authorized service
        $group['memberuid'] = $selecteduser;
        ldap_mod_del($ldapconn, $users_group_tree, $group); 
      }
    
  }
  if (isset($_POST['apache'])){
      $info['authorizedservice'][$c]='apache';
        $c++;

  }

  if (isset($_POST['vpn'])){
      $info['authorizedservice'][$c]='openvpn';
      $c++;

  }
  if(!(isset($_POST['sshd'])) && !(isset($_POST['vpn'])) && !(isset($_POST['apache']))) {
    $info['authorizedservice']='none';
  }

  $edit_user=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
  if($edit_user && isset($_POST["sendinstruction"]) && isset($_POST["vpn"]))$Ldap->send_vpn_instructions($user_email,$selecteduser);
  $message.=$edit_user["message"];
}

# Get current user data from ldap
 if($selecteduser && $ldapbind){
   $filteruser="(&(objectClass=person)(uid=$selecteduser))";
   $result=$Ldap->search($ldapconn,$ldaptree, $filteruser);
 }

?>
<div id="admin-content" class="content">
<?php echo $message;?>
 <h1 class=""><?php printf(_("Editar Usuario %s"), $selecteduser);?>  </h1>
 <hr>
            <div class="card card-block">
		<form role="form"  autocomplete="off" action="" method="POST" class="form-signin standard jquery-check">
                
                <div class="form-group">
                  <label for="commonname"><?php echo  sprintf(_("Nombre"));?></label><input class="form-control" id="commonname" name="commonname" type="text" maxlength="64" value="<?php echo $result[0]['cn'][0];?>" />                  
                </div>

              <div class="form-group">
                 <label for="surname"><?php echo  sprintf(_("Apellidos"));?></label><input class="form-control" id="surname" name="surname" type="text" maxlength="64" value="<?php echo $result[0]['sn'][0];?>" />
              </div>

              <div class="form-group">
                <label for="usermail"><?php printf(_("Correo electrónico"));?></label> 
                <p><?php printf(_("Puedes insertar un correo electrónico externo o elegir uno entre las cuentas creadas en el servidor"));?></p>
                <div id="emailresult"><?php if(isset($sanitised_data['usermail'][0]['message'])) echo '<span class="alert-danger">' . $sanitised_data['usermail'][0]['message'] . '</span>';?></div>
                <input id="usermail" class="form-control col-sm-4 usermail" type="mail" name="usermail" value="<?php echo $result[0]['mail'][0];?>" required />  
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
                <br>
               <div class="clear"></div>
              <div class="form-group">
                <label for="pswd1"><?php printf(_("Nueva Contraseña"));?></label>
                <div id="pswcheck"></div>
                <input  class="form-control" id='pswd1' type='password' name='pswd1' readonly />
              </div>

              <div class="form-group">
                <label for="pswd2"><?php printf(_("Confirma Contraseña"));?></label><input type='password' class="form-control" id='pswd2' name='pswd2' /><div id="pswresult"></div>
              </div>
                
                <?php 

                #
                # Get all activated services for user
                #
                
                $services=(isset($result[0]['authorizedservice']))?$result[0]['authorizedservice']:array();
                $sshd=(in_array("sshd",$services))?'checked="checked"':'';
                $vpn=(in_array("openvpn",$services))?'checked="checked"':'';
                $apache=(in_array("apache",$services))?'checked="checked"':'';
                ?>

                <div class="clear"></div>
                <h4><?php printf(_("Acceso SFTP"));?></h4>
                    <div> <label>
                    <input name="sshd" id="sshd" class="checkbox" type="checkbox"  <?php echo $sshd;?>>
                    <span><?php printf(_("Activar acceso SFTP"));?></span>
                    </label> </div>
                <?php

                if(!empty($sshd)){                       
                  echo '<p>'. sprintf(_("Directorio Personal")) . '</p>';
                  echo '<pre>' . $result[0]['homedirectory'][0] . '</pre>';
                }?>
                <?php if ($Ldap->check_installed_service('phpmyadmin')){?>
                  <hr>
                  <h4><?php printf(_("Acceso Aplicación phpMyAdmin"));?></h4>
                  <div> <label>
                    <input type="checkbox" name="apache" id="iapache" class="checkbox" type="checkbox" <?php echo $apache;?> />
                     <span><?php printf(_("Activar acceso aplicación protegida phpMyAdmin"));?></span>
                   </label> </div>

                <?php } ?>



                <?php if ($Ldap->check_installed_service('openvpn')){?>
                  <hr>
                  <h4><?php printf(_("Cuenta VPN"));?></h4>
                  <div> <label>
                    <input type="checkbox" name="vpn" id="vpn" class="checkbox togglehidden" type="checkbox" <?php echo $vpn;?> />
                     <span><?php printf(_("Activar cuenta VPN"));?></span>
                  </label> </div>

                  <div id="hidden">
                  <h4><?php printf(_("Instrucciones"));?></h4>
                  <p><?php printf(_("Envia al usuario un email con los archivos de configuración y las instrucciones para configurar el cliente VPN."));?></p>
                  <p><?php printf(_("NOTA: Las instrucciones incluyen todos los datos necesarios menos la contraseña. Por razones de seguridad proporciona al usuario la  contraseña por otro canal."));?></p>

                  <div> <label>
                    <input type="checkbox" name="sendinstruction" id="sendinstruction" class="checkbox small" type="checkbox"  />
                     <span><?php printf(_("Enviar instrucciones"));?></span>
                   </label> </div>
                  </div>
                <?php } ?>

                <div class="clear"></div>
                <hr>

		<input type="submit" name="updateuser" value="Guardar Cambios" class="btn btn-small btn-primary" />
		</form>
        </div>
</div><!--admin-content-->
<?php ldap_close($ldapconn);
require_once('footer.php');?>
