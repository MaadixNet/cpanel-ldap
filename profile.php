<?php
session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

if(!$Ldap->is_logged_in())
{
          $Ldap->redirect('login.php');
}
$ldapconn=$Ldap->connect();
if ($ldapconn){
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
}

$permissions=$_SESSION["login"]["level"];

$ldaptree    = 'ou=People,' . SUFFIX;
$filter="(&(objectClass=person)(type=root))";
$rootuser=$Ldap->search($ldapconn,$ldaptree, $filter);
$rootusername=$rootuser[0]["uid"][0];

$errors=$_GET["error"];
switch($errors){
  case "1":
    $msg1 ="
      <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i> La  
contraseña actual no coincide con que has insertado</span>";
    break;
  case "2":
    $msg2="
    <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i>Las
 dos contraseñas no coinciden </span>";
    break;
  case "3":
    $msg3="
  <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i>Contraseña incorrecta</span>";
    break;
  default:
    $msg1=$msg2=$msg3="";
}


require_once('header.php');?>
<div id="admin-content" class="content">
  <h2>Editar perfil</h2>
    <h4>Cambiar contraseña del Cpanel</h4>
    <p>Para poder cambiar esta contraseña necesitas conocer la contraseña del usuario de sistema, <?php echo $rootusername;?> 
    <form action='proc/form-functions.php' autocomplete='off' method='POST' class='form' id="changepassword">
    
      <label for="oldpsw">Contraseña antigua</label>    
      <input id='oldpsw' type='password' name='oldpsw' /><?php echo $msg1 ;?>
      <label for="newpsw">Nueva contraseña</label>
      <input id='newpsw' type='password' name='newpsw' />
      <label for="newpsw2">Repetir contraseña</label>
      <input id='newpsw2' type='password' name='newpsw2' /> <?php echo $msg2 ;?>
      <label for="rootpsw">Inserta la contraseña del usuario de sistema "<?php echo $rootusername;?>"</label>
      <input id='rootpsw' type='password' name='rootpsw' /> <?php echo $msg3 ;?>
      <div id="formresult"></div>
      <input type='submit' name='chpsw' value='Cambiar contraseña' class='btn btn-small btn-primary' />
      </form>
</div>
<?php 
ldap_close($ldapconn);
require_once('footer.php');?>
<!-- <script src="js/forms.js" type="text/javascript"></script>-->

