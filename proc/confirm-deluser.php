<?php
/**
 * Created by Maddish
 *
 * function file called by domain.php
 * Returns a window for confirmation
 * which includes data about domain
 *
 */
$user=$_POST['user'];
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';
# Check if user is admin for some domain.
# if so, do not allow removing it, but force to change adminid for
# that domain
#
$Ldap=new LDAP();
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
}

$binddn= LDAP_BASE;
$filter="(&(vd=*)(adminid=".$user."))";
$results=$Ldap->search($ldapconn,$binddn,$filter);
$number = $results["count"];
if ($number>0) {
    $plural=($number>1)?'s':'';
    printf(_("El usuario %s es actualmente el administrador de %d dominio%s Para poder eliminar el usuario tienes antes que asignar otro administrador para los siguientes dominios:"), $user,$number, $plural);
  
      echo "<ul>";
      for ($i=0; $i<$results["count"]; $i++) {
        $domain= $results[$i]["vd"][0];
        echo "<li>" . $domain . "</li>";
      }
      echo "</ul>";

    echo "<div class='modal-footer'>";
    echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>";
    echo "</div>";
}
else 
{

    printf(_("¿Estás seguro de que quieres eliminar el usuario %s?"), $user);
    echo "<div class='modal-footer'>
          <form action='' method='POST'><input type='hidden' name='userid' value='". $user ."' /> <button type='submit' name='deluser' class='btn btn-small btn-primary'>". sprintf(_('Borrar')) ."</button><button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button></form>
          </div>";
}
ldap_close($ldapconn);
