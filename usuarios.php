<?php 
//error_reporting(-1);
//ini_set('display_errors', 'On');

session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$permissions=$_SESSION["login"]["level"];
if ($Ldap->is_logged_in()&& $permissions != '10'){

        $Ldap->redirect('404.php');
}

require_once('header.php');
require_once('sidebar.php');?>

<?php

//Set variables for ldap connection
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
$message='';
$ldaptree    = LDAP_PEOPLE;
$groupinfo = posix_getgrnam("sftpusers");
$grid=$groupinfo["gid"];
//Only show sftpusers
$filtersftp="(&(objectClass=person)(uid=*)(!(gidnumber=27)))";
$filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";

$groupinfo = posix_getgrnam("web");
$webgrid=$groupinfo["gid"];
$users_group_tree = "cn=web,ou=groups,ou=People," . SUFFIX;
$users_in = $Ldap->search($ldapconn, $users_group_tree ,'(&(objectClass=*))');
//Add new User
if(isset($_POST['adduser'])){
        $entry=array();
        $newuser=trim($_POST['username']);
        $first_name=(isset($_POST['firstname']))?$_POST['firstname']:$newuser;
        $second_name=(isset($_POST['surname']))?$_POST['surname']:$newuser;
        $user_email=trim($_POST['usermail']);
        $password=$_POST['pswd2'];
        
        // SEt these variables to none in case in only vpn account
        $entry['loginshell']='none';
        $entry['homedirectory']='none';

        $c=0;
        if (isset($_POST['sshd'])){
          $entry['gidnumber'][0]=(int)$grid;
          $entry['loginshell']='/bin/bash';
          $entry['homedirectory']='/home/sftpusers/' . $newuser;
          $entry['authorizedservice'][$c]='sshd';
          $c++;
          $entry['authorizedservice'][$c]='apache';
          $c++;
        }
        if (isset($_POST['vpn'])){
          $entry['authorizedservice'][$c]='openvpn';
          //$entry['homedirectory']='none';
          $c++;
        }
          $entry['uid']=$newuser;
          $entry['cn']=(!empty($first_name))?$first_name:$newuser;
          $entry['sn']=(!empty($second_name))?$second_name:$newuser;
          $entry['mail']=$user_email;
          $entry['userpassword']=ldap_password_hash($password,'ssha');
          $add_user=$Ldap->add_user($newuser,$entry);
          // if user has been succesfully created and has ssh add to web group
          // he will be able to write in /var/www/html, bur as he is chrooted he will only see his webistes
          if ($add_user && isset($_POST['sshd'])){
            $group['memberUid'] = $newuser;                                   

            ldap_mod_add($ldapconn, $users_group_tree, $group);
          }        
        
          if (isset($_POST["sendinstruction"]) && $add_user)$Ldap->send_vpn_instructions($user_email,$newuser); 
          $message=$add_user['message'];
}

//Modifiy Passord
if(isset($_POST['chpsw'])){
   	$modifydn='uid='. $_POST['userid']. ',' . $ldaptree;
 	$info['userpassword'][0]=ldap_password_hash($_POST['changepsw'],'md5crypt');
        $info['shadowlastchange'][0] = floor(time()/86400);
	$chpass=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
        $message=$chpass['message'];
}

//delete user
if(isset($_POST['deluser'])){
    $selecteduser = $_POST['userid'];
    $deletedn='uid='. $selecteduser . ',' . $ldaptree;
    $users_group_tree = "cn=web,ou=groups,ou=People," . SUFFIX;
    $users_in = $Ldap->search($ldapconn, $users_group_tree ,"(&(memberuid=$selecteduser))");
    if ($users_in["count"] > 0 ){
      $group['memberuid'] = $selecteduser;
      ldap_mod_del($ldapconn, $users_group_tree, $group);
    }
    $del_user=$Ldap->deleteRecord($ldapconn, $deletedn, $recursive = false);
    
    $message=$del_user['message'];
}

if ($ldapbind) {

  //Get all sftpusers
  $result=$Ldap->search($ldapconn,$ldaptree, $filtersftp);
  $resultsudo=$Ldap->search($ldapconn,$ldaptree, $filtersudo);
}
?>
<div id="admin-content" class="content">
	<?php echo $message;?>
        <?/*php $commuid="getent passwd | awk -F: '{uid[$3]=1}END{for(x=10000; x<=40000; x++) {if(uid[x] != \"\"){}else{print x; exit;}}}'";
$firstuid_availabe=system($commuid);*/?>
	<h1 class="navbar-nav"> <?php printf(_("Usuarios"));?></h1>
	<span><button class="togglevisibility btn btn-small btn-secondary">Añadir usuario</button>	</span>
	<div class="clear"></div>
	<div id="change">
          <div class="card card-block">
              <div class="title-block">
              <h3 class="title"> <?php printf(_("Crear nuevo usuario"));?></h3>
              </div>
		<form role="form" autocomplete="off" action="" method="POST" class="form-signin standard jquery-check"  id="adduser-form">
                <div class="form-group">
                  <label class="control-label" for="username"><?php printf(_("Nombre de usuario"));?> *</label>
                  <input class="form-control" id="username" type="text" name="username" required /><div id="result"></div>
                </div>
        
                <div class="form-group">
                  <label class="control-label" for="firstname"><?php printf(_("Nombre"));?></label>
                  <input id="firstname" type="text" name="firstname"  class="form-control" />
                </div>

                <div class="form-group">
                  <label class="control-label"  for="surname"><?php printf(_("Apellidos"));?></label>
                  <input id="surname" type="text" name="surname" class="form-control" />
                </div>

                <div class="form-group">
                  <label class="control-label"  for="usermail"><?php printf(_("Correo electrónico"));?> *</label>
                  <p class=""><?php printf(_("Puedes insertar un correo electrónico externo o elegir una entre las cuentas creadas en el servidor"));?></p>
                  <input id="usermail" class="usermail form-control col-sm-4"  type="mail" name="usermail" required />  
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
                
                  <div id="emailresult"></div>
                </div>
                <hr>
                <div class="form-group">
                <h4><?php printf(_("Acceso Sftp"));?></h4>
                  <div> <label>
                    <input name="sshd" id="sshd" class="checkbox" type="checkbox">
                    <span><?php printf(_("Activar acceso sftp"));?></span>
                    </label> </div>
                </div>

                <hr>
                <?php if ($Ldap->check_installed_service('openvpn')){?>
                  <div class="form-group">
                  <h4><?php printf(_("Cuenta VPN"));?></h4>
                  <div> <label>

                    <input name="vpn" id="vpn" class="checkbox togglehidden" type="checkbox">
                    <span><?php printf(_("Activar cuenta vpn"));?></span>
                    </label> </div>
                  </div>

                  <div id="hidden">
                  <h4><?php printf(_("Instrucciones"));?></h4> 
                  <p><?php printf(_("Puedes enviar al usuario un email con instrucciones para configurar el cliente VPN"));?></p>
                  <p><?php printf(_("NOTA: Las instrucciones incluyen todos los datos necesarios menos la contraseña. Por razones de seguridad proporciona al usuario la  contraseña por otro canal"));?></p>
                  <div> <label>
                    <input class="checkbox" type="checkbox" name="sendinstruction" id="sendinstruction">
                    <span class="small"><?php printf(_("Enviar instrucciones"));?></span>
                  </label> </div>
                  </div>
                <?php } ?>

               <div class="clear"></div> 
              <hr>
              <div class="form-group">
              <label class="control-label" for="pswd1"><?php printf(_("Contraseña"));?> *</label>
              <div id="pswcheck"></div>
              <input id="pswd1" type="password" name="pswd1" class="form-control" required readonly />
              </div>

              <div class="form-group">
              <label class="control-label" for="pswd2"><?php printf(_("Confirma contraseña"));?> *</label><input class="form-control" id="pswd2" type="password" name="pswd2" required />
              <div id="pswresult"></div>
              </div>
              <hr>

		<input type="submit" name="adduser" value="Guardar" class="btn btn-small btn-primary" />
		</form>
          </div><!--card-->
	</div><!--change-->
	<table id="users">
		<thead>
		<tr>
                <th><?php printf (_('Usuario'));?></th>
                <th><?php printf (_('Acceso sftp'));?></th>
                <th><?php printf (_('Acceso VPN'));?></th>
                <th><?php printf (_('Editar'));?></th>
                <th><?php printf (_('Borrar'))?></th>
		</tr>
		</thead>
		<tbody>

		<?php 
                #list sudo user without pssword change option
                for ($i=0; $i<$resultsudo["count"]; $i++) {
                $username = $resultsudo[$i]["uid"][0];
                $services=$resultsudo[$i]["authorizedservice"];
                $issftp=(in_array('sshd',$services)&& (!empty ($services)))?'<i class="fa fa-check-circle-o icon checkok"></i>':'<i class="fa fa-exclamation-triangle icon checkko"></i>';
                $isvpn=(in_array('openvpn',$services)&& (!empty ($services)))?'<i class="fa fa-check-circle-o icon checkok"></i>':'<i class="fa fa-exclamation-triangle icon checkko"></i>';
                echo "<tr>";
                echo "<td>";
                echo $username . sprintf(_(" - SuperUsuario"));
                echo "</td>";
                echo "<td class='center'>";
                echo $issftp; 
                echo "</td>";

                echo "<td class='center'>";
                echo $isvpn;
                echo "</td>";
                echo "<td>";
                echo "<a href='edit-supuser.php?user=". $username ."'><button class='btn btn-small'><i class='fa fa-cogs' aria-hidden='true'></i> Editar</button></a>";

                echo "</td>";
                echo "<td>";
                echo "No disponible";
                echo "</td>";
                echo "</tr>";

            }
                #List sftpusers with edit options
                for ($i=0; $i<$result["count"]; $i++) {
		$oldpsw=$result[$i]['userpassword'][0];
		$username = $result[$i]["uid"][0];
                $services=(isset($result[$i]["authorizedservice"]))?$result[$i]["authorizedservice"]:array();
                $issftp=(in_array('sshd',$services) && (is_array($services)))?'<i class="fa fa-check-circle-o icon checkok"></i>':'<i class="fa fa-exclamation-triangle icon checkko"></i>';
                $isvpn=(in_array('openvpn',$services)&& (is_array($services)))?'<i class="fa fa-check-circle-o icon checkok"></i>':'<i class="fa fa-exclamation-triangle icon checkko"></i>';
		echo "<tr>";
		echo "<td>";
		echo $username;
		echo "</td>";
                echo "<td class='center'>";
                echo $issftp;
                echo "</td>";

                echo "<td class='center'>";
                echo $isvpn;
                echo "</td>";
                echo "<td>";
                echo "<a href='edit-user.php?user=". $username ."'><button class='btn btn-small'><i class='fa fa-cogs' aria-hidden='true'></i> ". sprintf(_('Editar')) ."</button></a>";
                echo "</td>";
                echo "<td>";
                $deletestring=sprintf(_('¿Quieres borrar la cuenta para el usuario %s? Esto eliminará su acceso al servidor'),$username);
//                echo "<form action='' method='POST' class='form-table'><input type='hidden' name='userid' value='". $username ."' /> <input type='submit' name='deluser' value='". sprintf(_('Borrar')) ."' class='btn btn-small btn-primary' onclick=\"return confirm('" . $deletestring ."');\" /></form>";
                echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal" data-user="' . $username .  '">' . sprintf (_('Eliminar')) . '</button>';
                echo "</td>";

		echo "</tr>";

            }
            ?>
		</tbody>
	</table>

<div class="bd-example">
  <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"></h4>
        </div>
        <div class="modal-body" id="modal-body">
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

</div><!--admin-content-->
<?php ldap_close($ldapconn);
require_once('footer.php');?>
</script>

