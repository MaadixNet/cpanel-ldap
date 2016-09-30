<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

if(!$Ldap->is_logged_in())
{
	$Ldap->redirect('login.php');
}

require_once('header.php');?>

<?php

//Set variables for ldap connection
$ldapconn=$Ldap->connect();
if ($ldapconn){
    //$ldapbind=$Ldap->bind($ldapconn, BINDDN ,$_SESSION["login"]["password"]); 
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
    $permissions= $_SESSION["login"]["level"];
}
$message='';
$ldaptree    = LDAP_VPN;
$filter="(uid=*)";
//Add new VPN User
if(isset($_POST['adduser'])){
	$adddn='uid='. $_POST['username']. ',' . $ldaptree;
	$info['objectclass'][0]='account';
	$info['objectclass'][1]='simpleSecurityObject';
	$info['objectclass'][2]='top';
	$info['uid'][0]=$_POST['username'];
	$info['userpassword'][0]="{MD5}".base64_encode(pack("H*",md5($_POST['password'])));
	$addUser=$Ldap->addRecord($ldapconn, $adddn, $info);
	if ($addUser){
       $message = "
        <div class='alert alert-success'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>Cuenta añadida con éxito para el usuario " . $_POST['username'] . "</strong> 
		</div>";
	} else {
		 $errorttpe  = (ldap_errno($ldapconn)==68)?"La cuenta " . $_POST['username']. " ya tiene usuario VPN activado": "";
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>Ha habido un error. " . $errorttpe ." </strong> 
        </div>
        ";
	}



	####@TODO  send email not working
	if((isset($_POST['sendinstruction']) &&  $_POST['sendinstruction'] == 'Yes') && ($addUser))
	{
		mail($_POST['username'],'Vpn user addeed', 'hecho');
	}
}

//Modifiy Passord
if(isset($_POST['chpsw'])){
   	$modifydn='uid='. $_POST['userid']. ',' . $ldaptree;
 	$info['userpassword'][0]="{MD5}".base64_encode(pack("H*",md5($_POST['changepsw'])));
	if($permissions==10) {
	$Ldap->modifyRecord($ldapconn, $modifydn, $info );
	# @TODO Allow users to change their own password
	# ACL Rule is needed
	} else {
		$modifs = [
    [
        "attrib"  => "userPassword",
        "modtype" => LDAP_MODIFY_BATCH_REMOVE,
        "values"  => [$_SESSION["login"]["password"]],
    ],
    [
        "attrib"  => "userPassword",
        "modtype" => LDAP_MODIFY_BATCH_ADD,
        "values"  => [$info['userpassword'][0]],
    ],
];
	ldap_modify_batch($ldapconn, $modifydn, $modifs);
	}
}

//delete user
if(isset($_POST['deluser'])){
    $deletedn='uid='. $_POST['userid']. ',' . $ldaptree;
	$Ldap->deleteRecord($ldapconn, $deletedn, $recursive = false);
}

if ($ldapconn){
    switch ($permissions) :
    case "10" :
        $filter="(uid=*)";
    break;
    case "4" :
        $who=$_SESSION["phamm"]["domain"];
        $filter="(uid=*" . $who .")";

    break;
    case "2":
        $who= $_SESSION['login']["username"];
        $filter="(uid=" . $who .")";
    break;

    default:
    break;
    endswitch;
    if ($ldapbind) {
         $result=$Ldap->search($ldapconn,$ldaptree, $filter);
    }

}
if(!$result){
	$vpn_exist=$Ldap->search($ldapconn,SUFFIX,"(ou=vpn)");
	if ($vpn_exist["count"]==0){
		//create ou=vpn if not exist
		$Ldap->addVpnObject($ldapconn);
	}	
}

?>
<div id="admin-content" class="content">
	<?php echo $message;?>
	<h1 class="navbar-nav"> Usuarios VPN activos</h1>
	<?php if($_SESSION["login"]["level"] == '10'){//Only admin can add VPN Users. Maybe also postmaster could do that??
	?>
	<span><button class="togglevisibility btn btn-small btn-secondary">Añadir usuario</button>	</span>
	<div class="clear"></div>
	<div id="change">
		<form autocomplete="off" action="#" method="POST" class="form-signin">
		<hr>
		<label for="username"><h4>Email de usuario:</h4> </label>
		<h5>Puedes insertar un email externo o bien elegir entre las cuentas de correos activadas en este servidor. Ein este caso el usuario podrá modificar su contraseña para la conexión VPN en su panel de control, identificándose con su cuenta de correo electónico </h5>
		<input id="username" type="text" name="username" required />

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
		<label for="password">Contraseña: </label><input id="password" type="password" name="password" required />        
		<p class="little"> <input type="checkbox" name="sendinstruction" value="Yes" /> Enviar email con instrucciones al usuario</p>
		<input type="submit" name="adduser" value="Guardar" class="btn btn-small btn-primary" />
		</form>
	</div><!--change-->
	<?php } ?>
	<table id="vpn">
		<thead>
		<tr>
			<th>Num.</th>
			<th>usuario</th>
			<th>Contraseña para conexión VPN</th>
			 <?php if($_SESSION["login"]["level"] == '10') echo '<th>Borrar</th>';//Only admin can delete  VPN Users?>
		</tr>
		</thead>
		<tbody>

		<?php 
                echo '<pre>';
                print_r($result);
                echo '</pre>';
                for ($i=0; $i<$result["count"]; $i++) {
		$oldpsw=$result[$i]['userpassword'][0];
		$username = $result[$i]["uid"][0];
		echo "<tr>";
		echo "<td>";
		echo $i;
		echo "</td>";
		echo "<td>";
		echo $username; 
		echo "</td>";
		echo "<td>";
		echo "<a class='showform'>Cambiar Contaseña</a>";
		echo "<form action='#' autocomplete='off' method='POST' class='form-table sub-form'><input size='4' id='username' type='password' name='changepsw' /><input type='hidden' name='userid' value='". $username ."' /><input type='submit' name='chpsw' value='Cambiar' class='btn btn-small btn-primary' /></form>";
		echo "</td>";
		if($_SESSION["login"]["level"] == '10') {
			echo "<td>";
			echo "<form action='#' method='POST' class='form-table'><input type='hidden' name='userid' value='". $username ."' /> <input type='submit' name='deluser' value='Borrar' class='btn btn-small btn-primary' onclick=\"return confirm('Quieres borrar la cuenta VPN para el usuario " . $username ."');\" /></form>";
			echo "</td>";
		}

		echo "</tr>";

            }
?>
		</tbody>
	</table>
</div><!--admin-content-->
<?php ldap_close($ldapconn);
require_once('footer.php');?>
<script type="text/javascript">
$('select#selmail').change(function(){
	$('input#username').val($(this).val());
});
</script>