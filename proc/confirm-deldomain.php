<?php
/**
 * Created by Maddish
 *
 * function file called by domain.php
 * Returns a window for confirmation
 * which includes data about domain
 *
 */
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';

$domain=$_POST['domain'];
$Ldap= new LDAP();

if(!$Ldap->is_logged_in())
{
          $Ldap->redirect('login.php');
}
$ldapconn=$Ldap->connect();

$psw=$Ldap->decrypt_psw();
$binddn=LDAP_BASE;
$filter="(vd=". $domain .")";

if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
}
$result=$Ldap->search($ldapconn,$binddn, $filter);
$queryvar='vd='. $domain .',';
$resultmail=$Ldap->search($ldapconn,$queryvar . LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');

  if($resultmail["count"]>0) {
    echo '<p>' . sprintf(_("El dominio %s tiene las siguientes cuentas de correo electrónico activadas y serán inaccesibles si eliminas el dominio."),$domain) .'  
        </p><p><ul>';
      for ($c=0; $c<$resultmail["count"]; $c++) {
          echo '<ol>'. $resultmail[$c]["mail"][0] .'</ol>'; 
      }
    echo '</ul></p>';

      echo '<p>' . sprintf(_("Los mensajes para estas cuentas de correo no se borrarán del servidor y volverán a aparecer en sus carpetas si en un futuro la vuelves a crear. Si quieres vaciar permanentemente el buzón hazlo usando la aplicación webmail o un cliente de correo que tengas configurado con IMAP, antes de eliminar el dominio."));
    }
$admin_id=$result[0]['adminid'][0];
$sudouser=$Ldap->get_sudo_user();
  echo '<p>';
  if ($admin_id != $sudouser){
     printf(_("El usuario '%s' es actualmente el webmaster de este dominio. Al eliminar el dominio no se borra ni el usuario ni la carpeta /var/www/html/%s. El nuevo propietario de la carpeta será el usuario por defetcto %s, para que pueda acceder a los archivos y editarlos o eliminarlos manualmente a través de una conexión ssh/sftp"), $admin_id, $domain,$sudouser );
  } else {
    printf(_("¿Seguro que quieres eliminar el dominio %s?"),$domain);
  }
  echo '</p>';
// print_r($result)
echo'
      </div>
      <div class="modal-footer">
        <form action="" autocomplete="off" method="POST" class="" id="del-domain"><input type="hidden" name="domainid" value="'. $domain.'" /> <button data-domain="'. $domain.'"  type="submit" name="deldomain"  class="btn btn-primary confirm">' . sprintf (_("Eliminar")) . '</button><button type="button" class="btn btn-secondary" data-dismiss="modal">' . sprintf (_("Cancelar")) . '</button></form>
      </div>';

/*if ($error=="0")
  $url = '../processing.php';
else
  $url = '../reset.php?token=' . $urltoken .'&error='. $error;

header("Location: $url");
 */
ldap_close($ldapconn);
