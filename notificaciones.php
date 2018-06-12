<?php 

session_start();
$message='';
require_once('header.php');
if ($ldapconn){

  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $binddn=LDAP_BASE;
  $filter="(vd=*)";
$base_dn = 'ou=logmail_custom,ou=conf,ou=cpanel,' . SUFFIX;

if(isset($_POST['logmailactive'])){
      $mail=array();
      $mails_status_log = (isset($_POST['logactive']))?'true':'false';
      $mail["status"] = $mails_status_log;
      $change_log = $Ldap->modifyRecord($ldapconn, $base_dn, $mail);//Check if a custom mail has been set
      // Lock cpanel
      $Ldap->lock_cpanel_puppet_master();
}

/* Check if log mail is active
*/
$admin_mail = $Ldap->get_admin_email();

$is_log_active = $Ldap->search($ldapconn, $base_dn, '(&(objectClass=organizationalUnit)(status=true))');
//Check if a custom mail has been set

if ($is_log_active["count"]>0) {

  $logs_status = sprintf(_("Actualmente, estos mensajes se están enviando a la dirección de correo %s"),$admin_mail);
  $ischecked = 'checked="checked"';
  $logs_status_change =sprintf(_("Para dejar de recibir los Logs, desactiva la siguiente casilla. Los correos se enviarán a una cuenta a la que tiene acceso el equipo técnico de MaadiX, para que se puedan consultar en el caso que se produzcan errores."));

} else {

  $ischecked = '';
  $logs_status =sprintf(_("Actualmente, estos mensajes se están enviando %s"),DEFAULT_FQDN);
  $logs_status_change =sprintf(_("Puedes cambiar esta configuración para recibir los Logs de sistema a la cuenta de correo %s"),$admin_mail);

}


/* Check if custom log mail is activaded or not
*/
//Modifiy sender email account 
  if(isset($_POST['chmail_notif'])){
    $modifydn=$_POST['selmail'];
    #$modifs['objectClass'][0] = 'metaInfo';
    #$modifydn='mail=' . $mailaccount . ',vd='.$domain.','.LDAP_BASE;
    $entry["cn"] =$modifydn; 
    ldap_mod_replace($ldapconn,'ou=sendermail,' . SUFFIX, $entry);
  }
  


  if ($ldapbind) {
    $mailsenderou= $Ldap->search($ldapconn,'ou=sendermail,' . SUFFIX ,'(&(objectClass=organizationalUnit)(objectClass=metaInfo))');
  }
  //Check if object senderemail existe. if not create it
  if(!$mailsenderou){
    $senderemail_exist=$Ldap->search($ldapconn,SUFFIX,"(ou=senderemail)");
    if ($senderemail_exist["count"]==0){
        //create ou=sendermail if not exist
        $Ldap->addSenderObject($ldapconn);
    }
  }

  $fqdn=trim(shell_exec('hostname -f'));
  $sender_email = (isset($mailsenderou[0]["cn"][0]))?$mailsenderou[0]["cn"][0]: 'www-data@'.$fqdn;

  $result = $Ldap->search($ldapconn, LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');
require_once('sidebar.php');

}?>
<article class="content forms-page">
    <div class="title-block">
      <h3 class="title"> <?php printf(_("Configurar notificaciones"));?></h3>
    </div>
    <section class="section">
        <div class="row">

          <div class="col-sm-12">
            <div class="inner" id="maincol">
              <?php
              echo $message; 
              echo '<h5>'. sprintf(_("Configurar remitente")) . '</h5>';
              echo '<p>' . sprintf(_("Tu sistema puede enviar correos de notificaciones a los usuarios. Por ejemplo, si creas una cuenta VPN, puedes enviar las instrucciones al usuario para configurar correctamente su conexión.")) . '<br>' .
              sprintf(_("Actualmente, el correo electrónico desde el que se envían los correos del sistema es:")) . '<br>
              <div class="box-placeholder">'. $sender_email .'</div>';
              if($result["count"] == 0){
                  printf(_('Todavía no has activado ninguna cuenta de correo en el servidor. Cuando hayas creado al menos una, podrás designarla como remitente para las notificaciones'));

              } else {
                echo '<p>';
                printf(_('Puedes cambiar esta configuración y elegir una de las cuentas de correo activadas en tu sistema para que sea el remitente de las notificaciones.'));
                echo '<br>';
                printf(_('Para cambiar este valor, elige un correo electrónico disponible en el listado y haz clic en Guardar'));
                echo '</p>';
                ?>
                <br>
                <form autocomplete="off" action="" method="POST" class="form-signin standard">
                  <div class="form-group">
                <?php
                echo '<select id="selmail" name="selmail" required>';
                echo '<option value="">' . sprintf(_("Seleccionar correo")) .'</option>';
                for ($c=0; $c<$result["count"]; $c++) {
                    //$selected=($queryvar==$result[$c]["mail"][0])?"selected":"";
                    echo '<option value="' . $result[$c]["mail"][0] .'">' . $result[$c]["mail"][0] . '</option>';
                }
                echo '<option value="www-data@'.$fqdn . '">www-data@'.$fqdn . '</option>';
                echo '</select></span>';
              };?>
                </div><!--form-group-->
              <br>
              <input type="submit" name="chmail_notif" value="<?php printf(_('Guardar'));?>" class="btn btn-small btn-primary" />
            </form>
              <hr>
              <?php
              echo '<h5>'. sprintf(_("Configurar destinatario de Logs")) . '</h5>';
              echo '<p>' . sprintf(_("Tu sistema envia periodcamente correos con información sobre su estado como fallos de los servicios, errores o actualizaciones.")) . '<br>' .
              $logs_status .

              '<br>' .
              $logs_status_change .
              '<br>';?>

              <form action="" method="POST" class="form-signin standard">
                  <div> <label>
                    <input type="checkbox" name="logactive" id="logactive" class="checkbox" type="checkbox" <?php echo $ischecked;?> />
                    <span></span>
                  </label> </div>
                <?php
              ?>

              <br>
              <p>
              <?php printf(_("Para este cambio, se bloqueará el acceso al panel de control durante unos minutos. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso termine, se activará el formulario para volver a acceder."));?>
              </p>
              <input type="submit" name="logmailactive" value="<?php printf(_('Guardar'));?>" class="btn btn-small btn-primary" />
            </form>

            <?php 
           //end if domain not =  0?>
        </div><!--inner-->

      </div><!--col-sm-12-->
  </div><!--row-->
</section>
</article>
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');
