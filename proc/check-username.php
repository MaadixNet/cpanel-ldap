<?php 
session_start();
require_once __DIR__.('/../classes/class.ldap.php');
$Ldap = new LDAP();

if(!$Ldap->is_logged_in())
{
  $Ldap->redirect('login.php');
}
$permissions=$_SESSION["login"]["level"];
if ($Ldap->is_logged_in()&& $permissions != '10'){

  $Ldap->redirect('404.php');
}
$newuser=$_POST["username"];

$ldaptree    = LDAP_PEOPLE;
$filter="(&(objectClass=person)(uid=*))";
//First we check if username is available, including system users, outside ldap Directory using getent
$cmnd="getent passwd " .$newuser;
$userexist=exec($cmnd);

if(!empty($userexist)) {
  echo "<span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i>". sprintf(_('El nombre de usuario %s no está disponible'), $newuser) ."</span>";

  //We check syntax for usename
} elseif(!check_syntax ('account',$newuser, $length="2")) {

  echo "<span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i> ". sprintf(_('%s no es un nombre de usuario válido. El nombre tiene que tener mínimo dos carácteres y solo puede contener cifras y/o números. Los carácteres especiales y los espacios no están admitidos'), $newuser) ."</span>"; 

} else {
  echo "<span class='success'><i class='fa fa-check icon alert-success' aria-hidden='true'></i><span>";
}
