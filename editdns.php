<?php 
session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
$domain=(isset($_GET["domain"]))?$_GET["domain"]:'';
// get current domain attributes value
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 
/* Get current domain data */
$binddn=LDAP_BASE;
$filter="(vd=" . $domain . ")";
$result=$Ldap->search($ldapconn,$binddn, $filter);

/*
 * Check if current domain has the email service activated
 * This check is only valid for Hosting domains
 * Mailman domains can't have email activated
 * If domain is not in result, set the value to a string that will
 * not match "FALSE" to  skip alert
 * $result[0]["accountactive"][0] can be FALSE o TRUE in ldap
 *
 */
if ($result['count'] > 0) {
  $ismailActive = ($result[0]["accountactive"][0]);
} else {
  $ismailActive = 'mailmandomain';
}
require_once('header.php');
$errorttpe="";
$message="";
/* Set variables */
$errors = 0; // If there are no errors in current DNS Records don't show alerts
/* Get local values on this server */
$server_ipaddr=$_SERVER["SERVER_ADDR"];
$fqdn=trim(shell_exec('hostname -f'));
/* Get all current DNS values for selected domain through dig*/
$resultA=dns_get_record ( $domain,  DNS_A );
$resultMX=dns_get_record ( $domain,  DNS_MX );
$resultNS = dns_get_record($domain,  DNS_NS );
$resultTXT = dns_get_record($domain,  DNS_TXT );
$command= "dig default._domainkey." . $domain ." TXT | grep -o -P \"(?<=TXT).*\"";
$resultDKIM = shell_exec($command);
$resultDKIM = str_replace('\\', '', $resultDKIM);

/* Found some dig that returns spf for domainkey. 
 * So we empty the vriable if ther is not DKIM string 
*/
if (strpos($resultDKIM, 'v=DKIM') == false) $resultDKIM='';
$domain_ip=( $resultA && $resultA[0]['ip'])?($resultA[0]['ip']):'No hay registro';
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$staterr='<i class="fa fa-exclamation-triangle icon checkko"></i>';
$allMX[]='';
/* Save correct DNS  Values into variables for check tasks and user hints*/
$correct_mx=$fqdn;
$correct_spf= "v=spf1 mx ip4:" . $server_ipaddr ." a:" . $fqdn . " ~all";

$binddkim = 'ou=' . $domain . ',ou=opendkim,ou=cpanel,' . SUFFIX;
$filterdkim = "(objectClass=organizationalUnit)";
$has_dkim = $Ldap->search($ldapconn,$binddkim, $filterdkim);

/* Check if is a host domain or a mailman domaiin
 * If is mailman domain there is no entry in vh=domainname
*/
if ($has_dkim || $ismailActive == 'mailmandomain') {
  /*
   * Current domain has dkim enabled
   * look for dkim file. If exists get the dkim key
  */
  $domain_dkim_file =( file_exists("/etc/opendkim/keys/$domain/default.txt"))?file_get_contents("/etc/opendkim/keys/$domain/default.txt"):false; 
  if ($domain_dkim_file) {
    //Just get the public key value
    preg_match_all('/\"p\=(.*)\" \)/',$domain_dkim_file,$the_result_array);
    $dkim_public_key_value = $the_result_array[1][0];
    //Rebuild the txt record for dkim TXT entry
    $correct_dkim = "v=DKIM1; k=rsa; p=" . $dkim_public_key_value;
  } else {
    $correct_dkim = sprintf(_('Todavía no se ha generado ninguna clave dkim para el dominio %s. Este proceso puede tardar unos minutos.'),$domain);
  }
} else {
  /* 
   * dkim is insactive for current doamin       
   * Return correct message forthis case
  */
  $correct_dkim = sprintf(_('La clave DKIM no está activada para este dominio')); 
}


/*
 * Strip blank spaces from correct dkim record and cuurent dkim record to check if they're equal.
 * Spaces doesn't matter
*/

$nospace_public_dkim=preg_replace('/\s+/', '', $resultDKIM);
$nospace_correct_dkim= preg_replace('/\s+/', '', $correct_dkim);
$nospace_correct_dkim = '"' . $nospace_correct_dkim . '"';
require_once('sidebar.php');
?>
<article>
  <section>
  <div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

      <div class="inner" id="maincol">
      <?php
      foreach($resultMX as $value){
          array_push($allMX,$value['target']);
      }

      echo '<h2 class="center">';
      printf(_("Servidor Web"));
      echo '</h2>';
      echo '<h4 class="center">';
      printf(_("Configuración DNS encontrada para %s"), $domain);
      echo '</h4>';
      echo "<p class='center'>";
      if (count($resultA) ==  0) {
        printf(_("No se ha encontrado ningún registro para el dominio %s"), $domain);
      } else if (count($resultA) ==  1 && $resultA[0]['ip']== $server_ipaddr ){ 
        printf(_("La configuración de DNS para el servidor web es correcta")); 
      } else if (count($resultA) > 1 && in_array($server_ipaddr , $resultA)){
        printf(_("El dominio %s tiene configurado más de un registro de tipo A. Esta configuración puede provocar anomalías. A menos que sepas exactamente lo que estás haciendo es aconsejable que dejes un solo registro."), $domain); 
      } else {
        printf(_("La configuración de los DNS no es correcta para el servidor web. Sigue las instrucciones a continuación para corregirla"), $domain); 
        $errors=1;
      }
      echo "</p>";
      echo '<table><thead><tr>';
      echo '<th>' . sprintf(_("Tipo")) . '</th>';
      echo '<th>' . sprintf(_("Name")) . '</th>';
      echo '<th>' . sprintf(_("Valor Actual")) . '</th>';
      echo '<th>' . sprintf(_("Valor Requerido")) . '</th>';
      echo '<th>' . sprintf(_("Estado")) . '</th>';
      echo '</tr></thead><tbody>';
      if (!$resultA) {
          echo '<tr>';
          echo '<td>A</td>';
          echo '<td>' . $domain . '</td>';
          echo '<td>' . sprintf(_("No hay registro A")) . '</td>';
          echo '<td>' . $server_ipaddr . '</td>';
          echo '<td>';
          echo $staterr . " <a href='#ACorrect'>" . sprintf(_("Como Corregir?")) . "</a>";
          echo '</td>';
          echo '</tr>';
      } else {
        $i=0;
        foreach ( $resultA as $ip) {
          echo '<tr>';
          echo '<td>A</td>';
          echo '<td>' . $domain . '</td>';
          echo '<td>' . $ip['ip'] . '</td>';
          $print_ip= ($i==0)?$server_ipaddr:sprintf(_("Eliminar registro"));
          echo '<td>' . $print_ip  . '</td>';
          echo '<td>';
          $domain_stat=($ip['ip']==$server_ipaddr)?$statok:$staterr . " <a href='#ACorrect'>Como Corregir? </a>";
          echo sprintf(_($domain_stat));
          echo '</td>';
          echo '</tr>';
          $i++;
        }// end foreach
      } //end if !$resultA
      echo '</tbody></table>';
      echo '<br />';
      /* Email configuration */
      echo '<h2 class="center">';
      printf(_("Servidor de Correo "));
      echo '</h2>';
      echo '<h4 class="center">';
      printf(_("Configuración DNS encontrada para %s"), $domain);
      echo '</h4>';
      if($ismailActive=="FALSE") {
        echo "<span class='alert-warning'>";
        printf(_("El servidor de correo no está activado para el dominio %s. En el caso quisieras activarlo la siguiente tabla te muestra los valores DNS correctos"),$domain); 
        echo "</span>";
      }
      echo '
      <table id="dns">
        <thead>
          <tr>
            <th>' . sprintf(_("Tipo")) . '</th>
            <th>' . sprintf(_("Nombre")) . '</th>
            <th class="longRecord">' . sprintf(_("Valor Actual")) . '</th>
            <th class="longRecord">' . sprintf(_("Valor Requerido")) . '</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>';

        if (!$resultMX){
            echo "<tr>";
            echo "<td>MX</td>";
            $mx_value= sprintf(_('No hay registro MX'));
            echo "<td>" . $domain . "</td>";
            echo "<td>" . $mx_value . "</td>";
            echo "<td>";
            echo $correct_mx;
            echo "</td>";
            $mx_stat=$staterr . " <a href='#mxCorrect'>" . sprintf(_("Cómo Corregir?")) . "</a>"; 
            echo '<td class="center">' . $mx_stat . '</td>';
            echo "</tr>";

        } else {
          $i=1;
          foreach($resultMX as $value){
            echo "<tr>";
            echo "<td>MX</td>";
            $mx_value= ($value && $value['host'])? $value['host']:sprintf(_('No hay registro MX'));
            echo "<td>" . $mx_value . "</td>";
            echo "<td>";
            echo $value['target'];
            echo "</td>";
            echo "<td>";
            echo $correct_mx;
            echo "</td>";
            $mx_stat=(($value['target']== $correct_mx) || ($value['target']== $fqdn ))?$statok:$staterr . " <a href='#mxCorrect'>" . sprintf(_("Cómo Corregir?")) . "</a>";
            if ($i>1)$mx_stat= sprintf(_("Eliminar. Un solo registro MX será necesario para una correcta configuración"));
            echo '<td class="center">' . $mx_stat . '</td>';
            $i++;
            echo "</tr>";
          } //end foreach mx as value
        } //endi if $resultMX
        /*start  spf Records*/
        $c=0;

        $spf_present=0;
        if (!$resultTXT){
          echo "<tr>";
          echo "<td>TXT</td>";
          echo "<td>" . $domain . "</td>";
          echo "<td>";
          printf(_("No hay registro SPF"));
          echo "</td>";
          echo "<td>";
          echo '"'. $correct_spf . '"';
          echo "</td>";
          echo "<td class='center'>";
          $spf_stat = $staterr. " <a href='#spfCorrect'>Cómo Corregir?</a>";
          echo $spf_stat;
          echo "</td>";
          echo "</tr>";
      } else {

          foreach($resultTXT as $txtvalue){
          // Get the txt record string
          $spf_record = ($resultTXT && $resultTXT[$c]['entries'][0])?$resultTXT[$c]['entries'][0]:sprintf(_('No hay registro SPF'));
            //Only check spf records
            if (strpos($spf_record, 'v=spf') !== false) {
                echo "<tr>";
                echo "<td>TXT</td>";
                $current_spf = ($txtvalue && $txtvalue['host'])?$txtvalue['host']:'';  
                echo "<td>" . $current_spf . "</td>";
                echo "<td>";
                echo '"'. $spf_record .'"';
                echo "</td>";
                echo "<td>";
                echo '"'. $correct_spf . '"';
                echo "</td>";
                $spf_stat=($spf_record == $correct_spf )?$statok:$staterr. " <a href='#spfCorrect'>Cómo Corregir?</a>";
                echo '<td class="center">' . $spf_stat . '</td>';
                echo "</tr>";
                $c++;
            } // end if spf is present in string
          } //end foreach spf txt reocrds
        } // end if txt found

        /* 
        ** Start dkim records
        */
        
        echo "<tr>";
        echo "<td>TXT</td>";
        echo "<td>default._domainkey." . $domain . "</td>";
        echo "<td class='longRecord'>";
        $dkim_record = ($resultDKIM)?$resultDKIM:sprintf(_('No hay registro DKIM'));

        echo $dkim_record;
        echo "</td>";
        echo "<td class='longRecord'>";
        echo '"'.$correct_dkim .'"';
        echo "</td>";
        $dkim_stat=($nospace_public_dkim == $nospace_correct_dkim || (!$has_dkim && $ismailActive != 'mailmandomain' && !$resultDKIM))?$statok:$staterr . " <a href='#dkimCorrect'>" . sprintf(_("Cómo Corregir?")) ."</a>";
        echo '<td class="center">' . $dkim_stat . '</td>';
        echo "</tr>";
        echo '
        </tbody>
          </table>
            </br>';
        echo '<h4 class="center">' . sprintf(_("Dirección IP")) . '</h4>';
        if ($resultA){
          echo '<h5>' . sprintf(_("Esta es la IP actualmente configurada para el dominio %s"),$domain). '</h5>';
          echo '<pre>' . $resultA[0]['ip'] . '</pre>';
        } else {
          echo '<h5>' . sprintf(_("El dominio %s no está registrado o no está creado."),$domain) .  '</h5>';
        }

        if ($server_ipaddr==$domain_ip){
          echo "<p>";
          printf(_("La configuración es correcta para que puedas acceder a tus aplicaciones desde el navegador, usando el dominio %s"),$domain);
          echo "</p><br />";
        } else {
          echo '<p>';
          printf(_("Cuando registramos un dominio hay un apartado en su configuración llamado DNS.
          Los DNS son los que pemiten transfomar nombres de dominio entendibles por humanos, en números que corresponden a las diferentes máquinas conectadas y accesibles públicamente en internet."));
          echo "</p><p>";
  
          printf(_("En tu caso el número asociado a tu dominio no corresponde a tu máquina"));
          echo "</p><p>";
          printf(_("Hay diferentes tipos de contenidos que un servidor puede mostrar. Entre ellos los más comunes son páginas webs y correo.
          Para que estos servicios funcionen correctamente y desde cualquier ubicación utilizando nombres en lugar que números, hay que comunicar públicamente en cual máquina están alojados los sdrvicios. Esta comunicación se lleva a cabo configurando correctamente los registros DNS."));
          echo "</p>        
            <hr>
            <h4 id='ACorrect' class='center'>";
            printf(_("Registro de tipo 'A' para contenido web"));
            echo "</h4><p>";
            printf(_("Para que puedas acceder a tus aplicaciones desde el navegador usando tu propio dominio tendrás que cambiar la configuración de sus DNS en el panel de configuración que te proporciona tu proveedor de dominio."));
            echo "</p></br><p>";
            printf(_("Esto te permitirá crear una página web en este servidor que se mostrará al visitar <em>https://%s</em> desde cualquier navegador."), $domain);
            echo "</p><p>";
            printf(_("Además podrás utilizar el dominio para acceder a los servicios que tengas instalados, en lugar de hacerlo a través de %s."),$fqdn);
            echo "</p><p>";
            printf(_(" Por ejemplo podrás acceder al Panel de Control utilizando la siguiente dirección:"));
            echo"</p>
            <pre>https://" . $domain . "/cpanel</pre>";
            printf(_("Para ello sigue los siguientes pasos:"));
            echo "
            <ul>
            <li>";
            printf(_("entra en el panel de administración que te proprciona tu provedor de dominio."));
            echo "</li><li>";
            printf(_("Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em>"));
            echo  "</li><li>";
            $curr_ip =  ( $resultA && $resultA[0]['ip'])?$resultA[0]['ip']:''; 
            if($resultA && $resultA[0]['ip']) {
              $curr_ip =  $resultA[0]['ip'];
              $action_A = sprintf(_("Edita el registro de tipo A cambiando la actual IP %s por&nbsp;"), $curr_ip);
            } else {
              $curr_ip = '';
              $action_A = sprintf(_("Crea el registro de tipo A asignando el valor"));
            }
            printf(_("%s %s. Los dominios principales se definen normalmente con el carácter '@' mientras que los subdominios se suelen definir emitiendo la parte relativa al domninio (para el subdominio <em>subdomain.example.com</em> el nombre de la entrada sería solo <em>subdomain</em>. La sintaxis varía dependiendo de la interfaz que proporciona tu proveedor de dominio."),$action_A,$server_ipaddr);
            echo "</li><li>" . sprintf(_("Guarda los cambios")) . "</li><li>";
            printf(_("Este cambio puede tardar entre 0 i 72 horas en ser operativo, dependiendo de la configuración de tu provedor de dominio. Sé paciente"));
            echo "</li>
                </ul>";
          } 
          

          echo '<hr>';  
          echo '<h4 id="mxCorrect" class="center">' . sprintf(_("Registros de tipo 'MX' para correo electrónico")) . '</h4>';
          if($ismailActive=="FALSE" && $result) {
              $edit_link= '<a href=/' . BASE_PATH .'/edit-domain.php?domain=' . $domain .'>' . sprintf(_("Editar %S") , $domain) .'</a>';
              echo "<span class='alert-warning'>";
              printf(_("El servidor de correo no está activado para el dominio %s. Puedes activarlo desde la página de edición del dominio: %s"),$domain,$edit_link);
              echo '</span>.<br>';
              printf(_("Hasta que no lo actives, el servicio de correo electrónico no funcionará en este servidor."));
          }
          if(in_array($correct_mx , $allMX)|| in_array($fqdn, $allMX)){
            echo '<p>' . sprintf(_("La configuración del registro MX es correcta")) . '</p>';
          } else {
            echo '<p>' . sprintf(_("La configuración del registro MX actual no es correcta. Sigue las siguientes instrucciones:")) . ' 
            </br>
            <ul>
              <li>';
                printf(_("Entra en el panel de administración que te proprciona tu provedor de dominio."));
                echo '</li>
              <li>';
                printf(_("Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em>"));
                 echo '</li>
               <li>';
                printf(_("Edita los registro de tipo MX cambiando el valor actual por el nuevo:"));
                echo '
                </br>
                </br>
                <table id="dns">
                 <thead><tr>
                    <th>' . sprintf(_("Tipo")) . '</th>
                    <th>' . sprintf(_("Nombre")) . '</th>
                    <th>' . sprintf(_("Valor actual")) . '</th>
                    <th>' . sprintf(_("Valor Requerido")) . '</th>
                    <th>' . sprintf(_("Priordad")) . '</th>
                  </tr></thead><tbody>';
                  $i=1;     
                  if(!$resultMX){
                      $host_val=$domain;
                      $target_val=sprintf(_('no hay registro'));
                          echo "<tr>";
                          echo '<td>';
                          echo 'MX';
                          echo '</td>';
                          echo "<td>";
                          echo $host_val;
                          echo "</td>";
                          echo "<td>";
                          echo $target_val;
                          echo "</td>";
                          echo "<td>";
                          echo $correct_mx;
                          echo "</td>";
                          echo "<td>";
                          echo "10";
                          echo "</td>";
                          echo "</tr>";

                  } else {
                      foreach($resultMX as $value){
                          echo "<tr>";
                          echo '<td>';
                          echo 'MX';  
                          echo '</td>';                              
                          echo "<td>";
                          $host_val=$value["host"];
                          echo $host_val;
                          echo "</td>";
                          echo "<td>";
                          $target_val=$value["target"];
                          echo $target_val;
                          echo "</td>";
                          echo "<td>";
                          if ($i>1)$correct_mx=sprintf(_('Eliminar. Un solo registro MX será necesario para una correcta configuración'));
                              echo $correct_mx;
                              echo "</td>";
                              echo "<td>";
                              echo "10";
                              echo "</td>";
                              echo "</tr>";
                              $i++;
                      } //end foreach
                  } // end if $resultMX
                        echo '
                        </tbody>
                        </table>
                        </br>
                        </br>';
                        //if there is more than one MX record tell user that one is enough...he can delete all the others
                        if ($i>2){
                            echo '
                            <p>' . sprintf(_("Tu actual configuración tiene más de un registro MX. Elimina todos los restantes. Un solo registro es necesario para poder usar el servidor mail instalado en esta máquina")) . '</p>';
                        }
                          echo '      
                            </li>
                            <li>' . sprintf(_("Guarda los cambios")) . '</li>
                            <li>' . sprintf(_("Este cambio puede tardar entre 0 i 72 horas en ser operativo, dependiendo de la configuración de tu provedor de dominio. Esta fase es conocida como propagación de los DNS."));
                            echo '</br>'; 
                            printf(_("Para averiguar si los DNS se han propagado ya, puedes volver a vistar esta misma página. Cuando el estado en la primera tablilla se ponga en 'OK' Ya podrás empezar a usar tu nuevo servidor de correo electrónico.")) . '</li>
                            </ul></p>';
                        }
                        // If spf record is not correct, give more details about how to fix it
                       echo '<hr>';    
                       echo '<h4 id="spfCorrect" class="center">' . sprintf(_("Registros de tipo SPF para correo electrónico")) . '</h4>';
                       if ($spf_stat && $spf_stat!=$statok){
                       printf(_('SPF (Sender Policy Framework) es un registro de tipo TXT que especifica qué servidores pueden enviar correo electrónico en nombre de tu dominio. Los proveedores de servicios de correo electrónico requieren a menudo registros de SPF  válidos. Un registro SPF ausente o incorrecto puede provocar que tu correo electrónico sea enviado a la carpeta de correo no deseado. Algunos operadores podrían incluso bloquear tus correos por completo. Para evitar estos problemas, tendrás que añadir el siguiente registro de tipo TXT a cada dominio que quieras utilizar para crear cuentas de correo electrónico (además del registro MX):'));
                      echo '<pre>TXT   ' . $correct_spf.' </pre>'; 
                      echo '
                      <table>
                        <thead>
                          <tr>
                            <th>' . sprintf(_("Tipo")) . '</th>
                            <th>' . sprintf(_("Nombre")) . '</th>
                            <th class="longRecord">' . sprintf(_("Valor Requerido")) . '</th>
                          </tr>
                        </thead>
                        <tbody>';
                          echo '<tr><td>TXT</td>
                            <td>' . $domain . '</td>
                            <td>"' . $correct_spf . '"</td></tr>';
                          echo '</tr>';
                      echo '</tbody></table>';
                      // End SPF Instructions
                      } else {
                        printf(_('La configuración del registro SPF es correcta'));
                      }
                      echo '<br>';
                      echo '<hr>';
                      echo '<h4 id="dkimCorrect" class="center">' . sprintf(_("Registros de tipo DKIM para correo electrónico")) . '</h4>';
                      if ($dkim_stat!=$statok){
                      printf(_('
El objetivo de DKIM (DomainKeys Identified Mail) es asegurar que un mensaje enviado por example.com sea realmente de example.com.
Vista la complejidad de su configuración, es mejor asegurarse de que se ha insertado correctamente su valor en los DNS ya que, al igual que pasa con los registros SPF, es mejor no tener ningún registro DKIM que tener uno incorrecto.')); 
                      echo '<br />';
                      printf(_("Lamentablemente, este tipo de registro tiene una sintaxis diferente dependiendo del proveedor con el que tengas contratado el dominio. Estos son los valores DKIM  correctos para el dominio %s."),$domain);
                      echo '
                      <table>
                        <thead>
                          <tr>
                            <th>' . sprintf(_("Tipo")) . '</th>
                            <th>' . sprintf(_("Nombre")) . '</th>
                            <th class="longRecord">' . sprintf(_("Valor Requerido")) . '</th>
                          </tr>
                        </thead>
                        <tbody>';
                        echo '<tr><td>TXT</td>
                          <td>default._domainkey.' . $domain . '</td>
                          <td>"' . $correct_dkim . '"</td></tr>';
                        echo '</tr>';
                        echo '</tbody></table>';
                        // End dkim Instructions
                  
                        echo '<br>';
                        echo '<p>' . sprintf(_("La sintaxis para el registro DKIM varia en función del proveedor con el que tengas contratado el dominio y de si se trata de un dominio de primer nivel o de un subdominio. En esta página hemos recopilado todas las posibles fórmulas de configuración que podrías encontrar: ")) . '<a href=\'https://docs.maadix.net/dns/#registro-dkim\' target=\'_blank\'>h:ttps://docs.maadix.net/dns/#registro-dkim<a></p>';
                        echo '<p>' . sprintf(_("En la misma página encontrarás un enlace a una herramienta para averiguar que el registro DKIM creado sea correcto")) . '</p>';
                      } else {
                        printf(_('La configuración del registro DKIM es correcta'));
                      } 
                      /*
                      if($resultNS){
                        echo '<h4>DNS</h4>';
                        foreach($resultNS as $value){
                                echo $value['target'] . '</br>';
                        }
                      }
                     */
                      ?>
                    <div class="result"></div>


                 </div><!--ineer-->

            </div><!--row-->
        </div><!--admin-content-->
    </section>
</article>
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');?>
