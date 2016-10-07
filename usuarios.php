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
        $password=$_POST['password'];
        $add_user=$Ldap->add_sftp_user($newuser,$password); 
        $message=$add_user['message'];
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
