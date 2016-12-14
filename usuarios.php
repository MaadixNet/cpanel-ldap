<?php 
error_reporting(-1);
ini_set('display_errors', 'On');

session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

if(!$Ldap->is_logged_in())
{
	$Ldap->redirect('login.php');
}
$permissions=$_SESSION["login"]["level"];
if ($Ldap->is_logged_in()&& $permissions != '10'){

        $Ldap->redirect('404.php');
}

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
$groupinfo = posix_getgrnam("sftpusers");
$grid=$groupinfo["gid"];
//Only show sftpusers
$filtersftp="(&(objectClass=person)(uid=*)(!(gidnumber=27)))";
$filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";
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
          $entry['gidnumber']=(int)$grid;
          $entry['loginshell']='/bin/bash';
          $entry['homedirectory']='/home/sftpusers/' . $newuser;
          $entry['authorizedservice'][$c]='sshd';
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
    $deletedn='uid='. $_POST['userid']. ',' . $ldaptree;
    $del_user=$Ldap->deleteRecord($ldapconn, $deletedn, $recursive = false);
    $message=$del_user['message'];
### @TODO: if deleted user is webadmin for some domain, we need to update
    # the adminID for this domain and set to default user
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
		<form autocomplete="off" action="" method="POST" class="form-signin" id="adduser-form">
		<hr>
                <h2>Crea un nuevo usuario:</h2>                  
		<label for="username"><h4><?php printf(_("Nombre de usuario"));?> *</h4></label>
		<input id="username" type="text" name="username" required /><div id="result"></div>
                <label for="firstname"><?php printf(_("Nombre"));?></label>
                <input id="firstname" type="text" name="firstname" />
                <label for="surname"><?php printf(_("Apeliidos"));?></label>
                <input id="surname" type="text" name="surname" />

                <label for="usermail"><?php printf(_("Correo electrónico"));?> *</label>
                <p class="little"><?php printf(_("Puedes insertar un correo electrónico externo o elegir una entre las cuentas creadas en el servidor"));?></p>
                <input id="usermail" type="mail" name="usermail" required />  
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

                <hr>
                <h4><?php printf(_("Acceso Sftp"));?></h4>
                <input type="checkbox" name="sshd" id="sshd" />
                <label for="sshd">&nbsp;</label>
              
                <hr>
                <h4><?php printf(_("Cuenta VPN"));?></h4>
                <input type="checkbox" name="vpn" id="vpn" />
                <label for="vpn" class="togglehidden" >&nbsp;</label></h4>
  
                <div id="hidden">
                <h4><?php printf(_("Instrucciones"));?></h4> 
                <p><?php printf(_("Puedes enviar al usuario un email con instrucciones para configurar el cliente VPN"));?></p>
                <p><?php printf(_("NOTA: Las instrucciones incluyen todos los datos necesarios menos la contraseña. Por razones de seguridad proporciona al usuario la  contraseña por otro canal"));?></p>
                <input type="checkbox" name="sendinstruction" id="sendinstruction" />
                <label for="sendinstruction" class="left small">&nbsp;</label>&nbsp;<span><?php printf(_("Enviar instrucciones"));?></span></h4>   
                </div>

               <div class="clear"></div> 
              <hr>
              <label for="pswd1"><?php printf(_("Contraseña"));?> *</label><input id="pswd1" type="password" name="pswd1" required />
              <label for="pswd2"><?php printf(_("Confirma contraseña"));?> *</label><input id="pswd2" type="password" name="pswd2" required />
              <div id="pswresult"></div>
              <hr>

		<input type="submit" name="adduser" value="Guardar" class="btn btn-small btn-primary" />
		</form>
	</div><!--change-->
	<table id="users">
		<thead>
		<tr>
                <th><?php printf (_('Usuario'));?></th>
                <th><?php printf (_('Acceso sftp'));?></th>
                <th><?php printf (_('Acceso VPN'));?></th>
                <th><?php printf (_('Contraseña'));?></th>
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
                echo $username;
                echo "</td>";
                echo "<td class='center'>";
                echo $issftp; 
                echo "</td>";

                echo "<td class='center'>";
                echo $isvpn;
                echo "</td>";
                echo "<td>";
                echo "Opción no disponible para este usuario";
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
		echo "<a class='showform'>". sprintf(_('Cambiar Contaseña')) ."</a>";
		echo "<form action='#' autocomplete='off' method='POST' class='form-table sub-form'><input size='4' id='username' type='password' name='changepsw' /><input type='hidden' name='userid' value='". $username ."' /><input type='submit' name='chpsw' value='Cambiar' class='btn btn-small btn-primary' /></form>";
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

