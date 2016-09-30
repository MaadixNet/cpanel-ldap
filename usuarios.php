<?php 
/*error_reporting(-1);
ini_set('display_errors', 'On');
*/
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
if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
}
$message='';
$ldaptree    = 'ou=People,' . SUFFIX;
$filter="(&(objectClass=person)(uid=*))";

//Add new User
if(isset($_POST['adduser'])){
        $newuser=$_POST['username'];
        
        //First we check if username is available, including system users, outside ldap Directory using getent
        $cmnd="getent passwd " .$newuser;
        $userexist=exec($cmnd);
        if($userexist) {
           $message=  "
        
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong> El usuario ". $newuser ." ya existe en el sistema. Por favor escoge otro nombre</strong> 
          </div>
          ";
          //We check syntax for usename
        } elseif(!check_syntax ('account',$newuser, $length="2")) {

           $message=  "
        
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>'" . $newuser ."' no es un nombre de usuario válido. El nombre tiene que tener mínimo dos carácteres y solo puede contener cifras y/o números. Los carácteres especiales y los espacios no están admitidos</strong> 
          </div>
          ";

        
        } else {

          $adddn='uid='. $newuser . ',' . $ldaptree;
          $info=array();
          $info['objectclass'][0]='person';
          $info['objectclass'][1]='organizationalPerson';
          $info['objectclass'][2]='inetOrgPerson';
          $info['objectclass'][3]='posixAccount';
          $info['objectclass'][4]='top';
          $info['objectclass'][5]='shadowAccount';
          $info['cn']=$_POST['username'];
          $info['uid']=$_POST['username'];
          $info['sn']=$_POST['username'];
          $info['userpassword']=ldap_password_hash($_POST['password'],'md5crypt');
          $info['shadowlastchange'] = floor(time()/86400); 
          ## “shadowMax”: days after which password must be changed
          ## For now we just set it as longer than a human life.
          ## Then we will see if we want to include this function
          $info['shadowmax']='99999';
          ## “shadowWarning”: days before password is to expire that user is warned
          $info['shadowwarning']='7';
          $info['loginshell']='/bin/bash';

          ## Check Netxuid number to sssign to new user
          ## for that we use a fake autoincrement system:
          ## cn=uidNext,dc=example,dc=tld May have attribute uidNumber or not
          #  If attribute is present and has a value we assign it to a variable and delete it in order to avoid
          # other process to use same value
          # When we finish with new user creation we set back the uidNumber attribute to the stored value + 1 
          $netxuid_number=$Ldap->search($ldapconn,'cn=uidNext,'. SUFFIX, '(&(objectClass=uidNext)(uidnumber=*))');
          $uidNext=($netxuid_number)? $netxuid_number[0]['uidnumber'][0]:NULL;
          if($uidNext){
            //First delete uidNumber attribute from Directory
            $entry['uidnumber']=array();
            $success=ldap_mod_del($ldapconn,'cn=uidNext,'. SUFFIX,$entry);
            if($success){
              //Only if deletion was succesfully we go on. Otherwise somebody else coud use same uid
              //We set next uidNumber to an incremente value by 1
              $insertuid=$uidNext+1;
              $entry['uidnumber']=(int)$insertuid;
              $success=ldap_mod_add($ldapconn,'cn=uidNext,'. SUFFIX,$entry);
              $info['uidnumber']=(int)$uidNext;
              $info['gidnumber']=(int)$uidNext;
              $info['homedirectory']='/home/' . $_POST['username'];
              $info['gecos']=$_POST['username'].',,,';
              $addUser=$Ldap->addRecord($ldapconn, $adddn, $info);
            }

          } else { //No uidNumber found. We cannot add user
            $errorttpe = 'Probablemente alguien estaba añdadiendo un usuario en el mismo instante y se ha bloqueado tu acción para evitar conflictos en el sistema. Por favor vuelve a intentarlo';
          }

	if ($addUser){
         $message = "
          <div class='alert alert-success'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>Cuenta añadida con éxito para el usuario " . $_POST['username'] . "</strong> 
                  </div>";
	} else {
		 $errorttpe  = (ldap_errno($ldapconn)==68)?"El usuario " . $_POST['username']. " ya existe": "";
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>Ha habido un error. " . $errorttpe ." </strong> 
        </div>
        ";
	}
    } //End if user exist in getent passwd

}

//Modifiy Passord
if(isset($_POST['chpsw'])){
   	$modifydn='uid='. $_POST['userid']. ',' . $ldaptree;
 	$info['userpassword'][0]=ldap_password_hash($_POST['changepsw'],'md5crypt');
        $info['shadowlastchange'][0] = floor(time()/86400);
	$Ldap->modifyRecord($ldapconn, $modifydn, $info );
}

//delete user
if(isset($_POST['deluser'])){
    $deletedn='uid='. $_POST['userid']. ',' . $ldaptree;
	$Ldap->deleteRecord($ldapconn, $deletedn, $recursive = false);
}

    if ($ldapbind) {
         $result=$Ldap->search($ldapconn,$ldaptree, $filter);
    }
?>
<div id="admin-content" class="content">
	<?php echo $message;?>
	<h1 class="navbar-nav"> Usuarios de sistema activados </h1>
	<span><button class="togglevisibility btn btn-small btn-secondary">Añadir usuario</button>	</span>
	<div class="clear"></div>
	<div id="change">
		<form autocomplete="off" action="#" method="POST" class="form-signin">
		<hr>
		<label for="username"><h4>Nombre de usuario:</h4> </label>
		<input id="username" type="text" name="username" required />
		<label for="password">Contraseña: </label><input id="password" type="password" name="password" required />        
		<p class="little"> <input type="checkbox" name="sendinstruction" value="Yes" /> Enviar email con instrucciones al usuario</p>
		<input type="submit" name="adduser" value="Guardar" class="btn btn-small btn-primary" />
		</form>
	</div><!--change-->
	<table id="users">
		<thead>
		<tr>
			<th>Usuario</th>
                        <th>Carpeta</th>
			<th>Contraseña</th>
		        <th>Borrar</th>
		</tr>
		</thead>
		<tbody>

		<?php 
                for ($i=0; $i<$result["count"]; $i++) {
		$oldpsw=$result[$i]['userpassword'][0];
		$username = $result[$i]["uid"][0];
		echo "<tr>";
		echo "<td>";
		echo $username;
		echo "</td>";
		echo "<td>";
		echo $result[$i]["homedirectory"][0];
		echo "</td>";
		echo "<td>";
		echo "<a class='showform'>Cambiar Contaseña</a>";
		echo "<form action='#' autocomplete='off' method='POST' class='form-table sub-form'><input size='4' id='username' type='password' name='changepsw' /><input type='hidden' name='userid' value='". $username ."' /><input type='submit' name='chpsw' value='Cambiar' class='btn btn-small btn-primary' /></form>";
		echo "</td>";
                echo "<td>";
                echo "<form action='#' method='POST' class='form-table'><input type='hidden' name='userid' value='". $username ."' /> <input type='submit' name='deluser' value='Borrar' class='btn btn-small btn-primary' onclick=\"return confirm('Quieres borrar la cuenta para el usuario " . $username .". Esto eliminará su acceso al servidor');\" /></form>";
                echo "</td>";

		echo "</tr>";

            }
            ?>
		</tbody>
	</table>
</div><!--admin-content-->
<?php ldap_close($ldapconn);
require_once('footer.php');?>
