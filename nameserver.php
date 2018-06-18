<?php 

session_start();
$message='';
require_once('header.php');

/* The fqdn Change can be performed only if the vm has status ready in teh API
*/
$status = $Ldap->getpuppetstatus();

if ($status=='ready'){
  $return_value=array();
  if ($ldapconn){
    if ($ldapbind) {
        $admin_mail= $Ldap->get_admin_email();
    }

    $server_ipaddr=$_SERVER["SERVER_ADDR"];
    $hostname = trim(shell_exec('hostname'));
    $fqdn=trim(shell_exec('hostname -f'));
    $domain_asociated= str_replace($hostname.'.', '',$fqdn);

    /* Gat all deactivated Group
    */
    $serv_disabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=disabled)(type=installed))');
    $count_diabled=count($serv_disabled); 
    /*
    echo '<pre>';
    print_r($serv_disabled);
    echo '</pre>';
    */
    //Modifiy sender email account 
    if(isset($_POST['changenameserver'])){
      $base_dn = "ou=conf,ou=cpanel,dc=example,dc=tld";
      $domain=strtolower($_POST["domain"]);

      /*
      * Record new domain fro server
      * ou=fqdn_domain,ou=conf,ou=cpanel,dc=example,dc=tld
      * status=$domain
      */

      if($domain) { //avoid emnpty values
        $entry=array();
        $entry["status"] = $domain; 
        $ch_domain = $Ldap->modifyRecord($ldapconn, 'ou=fqdn_domain,' . $base_dn, $entry );
      }

      /*
      * Record old domain in Ldap
      * ou=fqdn_domain_old,ou=conf,ou=cpanel,dc=example,dc=tld
      * status= $fqdn
      */

      if ($domain_asociated ) {
        $old_domain = array(); 
        $old_domain["status"] = $domain_asociated;

        /* Check if fqn_domain_old object exists
         * If not, create it
         */
        $fqdnOldexist = $Ldap->search($ldapconn,'ou=fqdn_domain_old,' . $base_dn ,'(&(objectClass=organizationalUnit)(objectClass=metaInfo))');
        if (!$fqdnOldexist){
          $up_fqdn = $Ldap->addFqdnDomainOld($domain_asociated);
        } else { 
          $up_fqdn = $Ldap->modifyRecord($ldapconn,'ou=fqdn_domain_old,' . $base_dn, $old_domain );
        }
      } //end if domain_asociated

      /* 
      * Change admin mail
      * ou=adminmail_custom,ou=conf,ou=cpanel,dc=example,dc=tld
      */

      if(isset($_POST['logmailctive'])){
        $mail=array();
        $mail["status"] = 'true';

        $ch_mail = $Ldap->modifyRecord($ldapconn, 'ou=logmail_custom,' . $base_dn, $mail);
      }

        /*
      * Lock cpanel and destroy session
      *  ou=customfqdn,ou=cpanel,dc=example,dc=tld
      *  status= locked
      */

      if ($up_fqdn && $ch_domain) {

        $modifydn='ou=customfqdn,ou=cpanel,' . SUFFIX ;
        $info['status']= 'locked';
        $ch_fqdn=$Ldap->modifyRecord($ldapconn, $modifydn, $info );


      
        //Clear this sessions
        //session_destroy();
      if($serv_disabled["count"]>0){
         for ($c=0; $c<$serv_disabled["count"]; $c++) {
            $service=$serv_disabled[$c]["ou"][0];
            $entry = array(); 
            $modifydn='ou=' . $service . ',' . LDAP_SERVICES;

            $entry['type'] = 'available';
            $entry['status'] = 'enabled';
            $updategroup=$Ldap->modifyRecord($ldapconn, $modifydn, $entry );
          }
        }
       //Update ou=cpanel object with lock status
        $modifydn='ou=cpanel,' . SUFFIX ;
        $info = array();
        $info['status']= 'locked';
        $updatefqdn=$Ldap->modifyRecord($ldapconn, $modifydn, $info ); 
        //Redirect to home
        header('Location: /cpanel/fqdn-process.php');
      } // end if ($up_fqdn && $ch_domain && $ch_mail)
    } //end if isset($_POST['changenameserver']
  } // end check satuts ready
} // end check satuts ready
require_once('sidebar.php');?>
<article class="content forms-page">
    <div class="title-block">
      <h3 class="title"> <?php printf(_("Cambiar nombre de dominio del servidor"));?></h3>
    </div>
    <section class="section">
        <div class="row">

          <div class="col-sm-12">
            <div class="inner" id="maincol">
              <?php
              if ($status=='ready'){

                $fqdn_example = $hostname.'.example.com';
                $dkim_info_link = '<a href="/' . BASE_PATH . '/domain-instruccions.php#dkim">[+ Info]</a>';
                echo $message; 
                $op_str='<strong>';
                $cl_str='</strong>';
                printf(_('En esta página puedes cambiar el dominio principal asociado a tu servidor.'));
                echo '<br>'; 

                printf(_('El nombre que distingue de forma única el servidor es %s"%s"%s.'), $op_str,$fqdn, $cl_str);
                echo '<br>';
                printf(_('Este valor se denomina %sFQDN%s (sigla en inglés de fully qualified domain name). El FQDN está compuesto por dos partes: "%s" [Nombre del equipo] , y %s [Dominio asociado].'),$op_str,$cl_str, $hostname, $domain_asociated);
                echo '<br>';
                printf(_('En esta página puedes proceder a sustituir el valor del dominio asociado "%s%s%s" por otro, de tu elección.'),$op_str,$domain_asociated,$cl_str);
                echo '<br>';
                printf(_('Para cambiar este valor es indispensable que crees antes las configuraciones de DNS necesarias. Para ello, sigue las siguientes instrucciones.'));
                echo '<br>';
                echo '<br>';
                echo '<h5>';
                printf(_('Configuración actual')); 
                echo '</h5>';
                echo '<table id="nameserver">
                    <thead>
                     <tr>
                      <th>'. sprintf(_("Nombre del servidor")) . '</th>
                      <th>' .sprintf(_("Dominio asoicado")) .'</th>
                      <th>' .sprintf(_("FQDN (Nombre del servidor + dominio asoicado)")). '</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                      <td class="center">'. $hostname . '</td>
                      <td class="center">'. $domain_asociated . '</td>
                      <td class="center">'. $fqdn . '</td>
                    </tr>
                    </tbody>
                  </table>';
                echo '<br>';
                printf(_('El valor "Nombre del servidor" no puede ser cambiado. Solo podrás cambiar el valor "Dominio Asociado". Así, por ejemplo, si el dominio que quieres usar a partir de ahora fuera "example.com", tu nuevo FQDN será "%s".'),$fqdn_example);

                echo '<br>';
                echo '<br>';
                echo '<h5>';
                printf(_('Nueva configuración'));
                echo '</h5>';
                echo '<table id="domains">
                    <thead>
                     <tr>
                      <th>'. sprintf(_("Nombre del servidor")) . '</th>
                      <th>' .sprintf(_("Dominio asoicado")) .'</th>
                      <th>' .sprintf(_("FQDN (Nombre del servidor + dominio asoicado)")). '</th>
                    </tr>
                  </thead>
                <tbody>
                <tr>
                  <td class="center">'. $hostname . '</td>
                  <td class="center">example.com</td>
                  <td class="center">'. $fqdn_example . '</td>
                </tr>
              </tbody>
                </table>';
                echo '<br>';

                printf(_('Para poder cambiar este valor tienes que crear el subdominio y las configuraciones de DNS necesarias.  Estas configuraciones las tendrás que hacer desde el panel de gestión de Zonas DNS, de tu proveedor de dominio.'));
                echo '<br>';
                echo '<br>';

                printf(_('Siguiendo el ejemplo anterior, antes de poder proceder, necesitarás crear los siguientes registros DNS (sustituye example.com por tu propio dominio)'));
                echo '<br>';
                echo '<table><thead><tr>';
                echo '<th>' . sprintf(_("Tipo")) . '</th>';
                echo '<th>' . sprintf(_("Name")) . '</th>';
                echo '<th>' . sprintf(_("Valor Requerido")) . '</th>';
                echo '</tr></thead><tbody>';
                echo '<tr>';
                echo '<td>A</td>';
                echo '<td>' . $fqdn_example . '</td>';
                echo '<td>' . $server_ipaddr . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>MX</td>';
                echo '<td>' . $fqdn_example . '</td>';
                echo '<td>' . $fqdn_example . '</td>';
                echo '</tr>';
                echo '<td>TXT</td>';
                echo '<td>' . $fqdn_example . '</td>';
                echo '<td>"v=spf1 a mx ~all"</td>';
                echo '</tr>'; 

                echo '</tbody></table>';
                echo '<br>';
                echo '<br>';
                echo '<p><span class="advert">' . sprintf(_("ADVERTENCIA")).': </span>';
                printf(_('El nombre del servidor y el FQDN son usados por múltiples aplicaciones, dentro del sistema. Todas ellas se actualizarán automáticamente para que funcionen con la nueva configuracón.'));
                echo '<br>';
                printf(_('Si estás usando software externos en otros dispositivos, por ejemplo, si tienes configurada alguna cuenta de correo en aplicaciones como Thunderbird o Outlook, tendrás que cambiar el valor para el servidor de entrada y de salida, y sustituirlo con el nuevo. Encontrarás los nuevos valores de configuración en la página de edición de las cuentas de correo electrónico de este panel de control.'));
                echo '<br>';
                printf(_('Si tienes activada alguna cuenta de VPN, tendrás también que volver a cargar la configuración en tu cliente, ya que el certificado se modificará, y el anterior dejará de ser válido.'));
                echo '</p>';
                echo '<br>';
                printf(_('Esta herramienta para el cambio de dominio del servidor, efectuará varias tareas:'));
                echo '<br>';
                echo '<ul>';
                if ($count_diabled>0){
                  echo '<li>'.
                      sprintf(_('Reactivar todas las aplicaciones que tengas desactivadas, para que puedan actualizarse con la nueva configuración. Una vez acabado el proceso podrás volver a descativarlas, desde la página del listado de aplicaciones instaladas ')).
                      '</li>';
                }
                echo '<li>'.
                      sprintf(_('Comprobación de la existencia de las entradas DNS necesarias. El cambio de dominio no se aplicará, hatsa que la comprobación devuelva los resultados correctos')).
                      '</li>
                      <li>'.
                      sprintf(_('Bloqueo del Panel de Control durante el proceso')).
                      '</li>
                      <li>'.
                      sprintf(_('Envío de un correo electrónico al equipo técnico de MaadiX, que actualizará los certificados necesarios, para que tu sistema pueda seguir accediendo a las actualizaciones de seguridad y a las nuevas aplicaciones.')).
                      '</li>

                      <li>'.
                      sprintf(_('Se generará la clave DKIM para el nuevo dominio de este servidor, que tendrás que utilizar para crear la entrada DNS correspondiente. Este valor es muy importante para garantizar que los correos que envíes no sean tratados como SPAM. %s'), $dkim_info_link).
                      '</li>
                      </ul>';
                if ($domain_asociated == DEFAULT_FQDN){
                  printf(_('Una vez terminado el proceso de forma satisfactoria, procederemos a eliminar las entradas DNS antiguas, creadas para %s'),$fqdn);
                } 

                echo '<br>';
                echo '<div class="form-group">
                  <span><button class="togglevisibility btn btn-small btn-secondary">';
                  printf(_("continuar"));
                echo '</button>  </span>
                  </div>
                  <div class="clear"></div>';

?>
              <div id="change">
                  <div class="card card-block">
                                    
                     <form autocomplete="off" action="" method="POST" class="form-signin standard">
                      <div class="form-group">
                        <h5>
                          <?php printf(_('Nuevo nombre del servidor'));?>
                        </h5>
                        <p>
                        <?php printf(_('inserta el dominio que quieres asignar al servidor'));?>
                        </p>
                                      
                      </label>
                      <span class="inline"><?php echo $hostname;?>.</span><input id="domain_new" type="text" name="domain_new" class="inline" required />
                    </div>
                    <h5><?php printf(_("Recibir Mails de Log del sistema"));?></h5>
                    <p><?php printf(_("Activa esta casilla si quieres recibir a tu cuenta de correo, informes diarios sobre el estado del sitema, fallos en los servicios, errores o actualizaciones. Podrás cambiar esta configuración en cualquier momento desde la página de Notificaciones"));?></p>
                    <div> <label>
                        <input name="logmailctive" id="logmailctive" class="checkbox" type="checkbox">
                        <span><?php printf(_("Recibir Logs"));?></span>
                    </label> </div>
                    <br>
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#fqdnModal"><?php printf (_('Guardar'));?></button>
                  </form>
                  <br>
                </div><!--card-block-->
              </div><!--change-->
        <?php } else { //end iff status is active

                printf(_('No se puede efectuar el cambio de dominio para el servidor debido a alguna tarea pendiendte'));
                echo '<br>';
                echo '<br>';


        }?>
        </div><!--inner-->

      </div><!--col-sm-12-->
  </div><!--row-->
</section>
</article>
<div class="bd-example">
  <div class="modal fade" id="fqdnModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"><?php printf(_("Cambiar dominio"));?></h4>
        </div> <!--modal-header-->
        <div class="modal-body" id="modal-body">
          <div class="fields-info" id="fields-info">
          </div>
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--fqdnModal-->
</div><!--bd-example-->
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');
