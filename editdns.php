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
$binddn=LDAP_BASE;
$filter="(vd=" . $domain . ")";
$result=$Ldap->search($ldapconn,$binddn, $filter);

// Check if current domain has the email service activated
$ismailActive = $result[0]["accountactive"][0];
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
$domain_ip=($resultA[0]['ip'])?($resultA[0]['ip']):'No hay registro';
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$staterr='<i class="fa fa-exclamation-triangle icon checkko"></i>';
$allMX[]='';

/* Save correct DNS  Values into variables for check tasks and user hints*/

$correct_mx=$fqdn;
$correct_spf= "v=spf1 mx ip4:" . $server_ipaddr ." a:" . $fqdn . " ~all";
$domain_dkim_file = file_get_contents("/etc/opendkim/keys/$domain/default.txt"); 

if ($domain_dkim_file) {
  //Just get the public key value
  preg_match_all('/\"p\=(.*)\" \)/',$domain_dkim_file,$the_result_array);
  $dkim_public_key_value = $the_result_array[1][0];
  //Rebuild the txt record for dkim TXT entry
  $correct_dkim = "v=DKIM1; k=rsa; p=" . $dkim_public_key_value;
} else {
  $correct_dkim = sprintf(_('No se ha generado ninguna clave dkim para el dominio %s. Averigua que el dominio esté activado en el panel de control'),$domain);
}
// Strip blank spaces from correct dkim record and cuurent dkim record to check if they're equal.
// Spaces doesn't matter
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
/*
        echo 'TXT: ';
        echo '<pre>';
        echo $resultDKIM;
        //print_r ($result);
        //echo 'Count: ' . count($resultA) . 'errors' . $errors;
        //echo 'dkim file content: ' . $domain_dkim_file;
        echo 'preg_match: ';
        echo '<br>';
        //print_r($resultMX);
        echo $nospace_public_dkim;
        echo '<br>';
        echo $nospace_correct_dkim;
        echo '</pre>';
*/
        foreach($resultMX as $value){
          array_push($allMX,$value['target']);
        }
 
/*
	if (($server_ipaddr==$domain_ip && in_array($correct_mx , $allMX)) || ($server_ipaddr==$domain_ip && in_array($fqdn, $allMX))): 
		echo '<div class="alert alert-success">La configuración de tu dominio es correcta para que funcione en tu servidor</div>';
	else:
		echo '<div class="alert alert-error">El dominio '. $domain . ' está incluido correctamente en tu sistema. Sin embargo necesitas cambiar ciertos parámetros del dominio para que todos los servicios funcionen en tu servidor.</br>Sigue los pasos a continuación.</div>';
	endif;
*/

        echo '<h2 class="center">';
          printf(_("Servidor Web"));
        echo '</h2>';
        echo '<h4 class="center">';
          printf(_("Configuración DNS encontrada para %s"), $domain);
        echo '</h4>';
        if (count($resultA) ==  0) {
          printf(_("<p class='center'>No se ha encontrado ningún registro para el dominio %s</p>"), $domain);
        } else if (count($resultA) ==  1 && $resultA[0]['ip']== $server_ipaddr ){ 
          printf(_("<p class='center'>La configuración de DNS para el servidor web es correcta</p>")); 
        } else if (count($resultA) > 1 && in_array($server_ipaddr , $resultA)){
          printf(_("<p class='center'>El dominio %s tiene configurado más de un registro de tipo A. Esta configuración puede provocar anomalías. A menos que sepas exactamente lo que estás haciendo es aconsejable que dejes un solo registro.</p>"), $domain); 
        } else {
          printf(_("<p class='center'>La configuración de los DNS no es correcta para el servidor web. Sigue las instrucciones a continuación para corregirla</p>"), $domain); 
          $errors=1;
        }
        echo '<table><thead><tr>';
        echo '<th>Type</th>';
        echo '<th>Name</th>';
        echo '<th>Valor Actual</th>';
        echo '<th>Valor Requerido</th>';
        echo '<th>Status</th>';
        echo '</tr></thead><tbody>';
        if (!$resultA) {
          echo '<tr>';
          echo '<td>A</td>';
          echo '<td>' . $domain . '</td>';
          echo '<td>No hay registro</td>';
          echo '<td>' . $server_ipaddr . '</td>';
          echo '<td>';
          echo $staterr . " <a href='#ACorrect'>Como Corregir? </a>";
          echo '</td>';
          echo '</tr>';
          $i++;


        }
        $i=0;
        foreach ( $resultA as $ip) {
          echo '<tr>';
          echo '<td>A</td>';
          echo '<td>' . $domain . '</td>';
          echo '<td>' . $ip['ip'] . '</td>';
          $print_ip= ($i==0)?$server_ipaddr:"Eliminar registro";
          echo '<td>' . $print_ip  . '</td>';
          echo '<td>';
          $domain_stat=($ip['ip']==$server_ipaddr)?$statok:$staterr . " <a href='#ACorrect'>Como Corregir? </a>";
          echo $domain_stat;
          echo '</td>';
          echo '</tr>';
          $i++;
        }
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
          printf(_("<span class='alert-warning'>El servidor de correo no está activado para el dominio %s. En el caso quisieras activarlo la siguiente tabla te muestra los valores DNS correctos</span>"),$domain); 
        }
	echo '
	<table id="dns">
		<thead>
		<tr>
			<th>Type</th>
                        <th>Name</th>
			<th class="longRecord">Valor Actual</th>
			<th class="longRecord">Valor Requerido</th>
			<th>Estado</th>
		</tr>
		</thead>
		<tbody>';
			$i=1;
			foreach($resultMX as $value){
				echo "<tr>";
				echo "<td>MX</td>";
                                echo "<td>" . $value['host'] . "</td>";
				echo "<td>";
				echo $value['target'];
				echo "</td>";
				echo "<td>";
				echo $correct_mx;
				echo "</td>";
				$mx_stat=(($value['target']== $correct_mx) || ($value['target']== $fqdn ))?$statok:$staterr . " <a href='#mxCorrect'>Cómo Corregir?</a>";
				if ($i>1)$mx_stat='Eliminar. Un solo registro MX será necesario para una correcta configuración';
				echo '<td class="center">' . $mx_stat . '</td>';
				$i++;
				echo "</tr>";
                          } //end foreach mx as value
                          /*start  spf Records*/
                              $c=0;
                              foreach($resultTXT as $txtvalue){
                                echo "<tr>";
                                echo "<td>TXT</td>";
                                echo "<td>" . $value['host'] . "</td>";
                                echo "<td>";
                                $spf_record = ($resultTXT[$c]['entries'][0])?$resultTXT[$c]['entries'][0]:'No hay registro';
                                echo '"'. $spf_record .'"';
                                echo "</td>";
                                echo "<td>";
                                $correctSPF=($c<1)?'"' . $correct_spf . '"':'';

                                echo $correctSPF;
                                echo "</td>";
                                $spf_stat=($spf_record == $correct_spf )?$statok:$staterr . " <a href='#spfCorrect'>Cómo Corregir?</a>";
                                if ($c>0)$spf_stat='Eliminar. Un solo registro SPF será necesario para una correcta configuración';
                                echo '<td class="center">' . $spf_stat . '</td>';
                                echo "</tr>";
                                $c++;
                              } //end foreach spf txt reocrds
                          /* Start dkim records*/
                                echo "<tr>";
                                echo "<td>TXT</td>";
                                echo "<td>default._domainkey." . $domain . "</td>";
                                echo "<td class='longRecord'>";
                                $dkim_record = ($resultDKIM)?$resultDKIM:'No hay registro';
                                echo $dkim_record;
                                echo "</td>";
                                echo "<td class='longRecord'>";
                                echo '"'.$correct_dkim .'"';
                                echo "</td>";
                                $dkim_stat=($nospace_public_dkim == $nospace_correct_dkim)?$statok:$staterr . " <a href='#dkimCorrect'>Cómo Corregir?</a>";
                                echo '<td class="center">' . $dkim_stat . '</td>';
                                echo "</tr>";

                          echo '
                          </tbody>
                          </table>
                          </br>';

		echo '<h4 class="center">Dirección IP</h4>';
                if ($resultA){
                  echo '<h5>Esta es la IP actualmente configurada para el dominio ' . $domain . '</h5>';
                  echo '<pre>' . $resultA[0]['ip'] . '</pre>';
                } else {
                  echo '<h5>El dominio ' . $domain . ' no está registrado o no está creado. </h5>';
                }
		if ($server_ipaddr==$domain_ip){
                  printf(_("<p>La configuración es correcta para que puedas acceder a tus aplicaciones desde el navegador, usando el dominio %s</p><br>"),$domain);
                } else {
                  echo '<p>
                  Cuando registramos un dominio hay un apartado en su configuración llamado DNS.
                  Los DNS son los que pemiten transfomar nombres de dominio entendibles por humanos, en números que corresponden a las diferentes máquinas conectadas y accesibles públicamente en internet.</p>
		
                  <p>En tu caso el número asociado a tu dominio no corresponde a tu máquina</p>

                  <p>Hay diferentes tipos de contenidos que un servidor puede mostrar. Entre ellos los más comunes son páginas webs y correo.
                  Para que estos servicios funcionen correctamente y desde cualquier ubicación utilizando nombres en lugar que números, hay que comunicar públicamente en cual máquina están alojados los sdrvicios. Esta comunicación se lleva a cabo configurando correctamente los registros DNS. </p>
                  <hr>
                  <h4 id="ACorrect" class="center">Registro de tipo "A" para contenido web</h4>
                  <p>Para que puedas acceder a tus aplicaciones desde el navegador usando tu propio dominio tendrás que cambiar la configuración de sus DNS en el panel de configuración que te proporciona tu proveedor de dominio.</br>
                  Esto te permitirá crear una página web en este servidor que se mostrará al visitar <em>https://' . $domain . '</em> desde cualquier navegador.</br> Además podrás utilizar el dominio para acceder a los servicios que tengas instalados, en lugar de hacerlo a través de ' . $fqdn .'.</br> Por ejemplo podrás acceder al Panel de Control utilizando la siguiente dirección</br></br>
                
                  <pre>https://' . $domain . '/cpanel</pre>

                  Para ello sigue los siguientes pasos:
                  <ul>
                    <li>entra en el panel de administración que te proprciona tu provedor de dominio.</li>
                    <li>Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em></li>
                    <li>Edita el registro de tipo A cambiando la actual IP ' . $resultA[0]['ip'] .' por ' . $server_ipaddr . '. Los dominios principales se definen normalmente con el carácter "@" mientras que los subdominios se suelen definir emitiendo la parte relativa al domninio (para el subdominio <em>subdomain.example.com</em> el nombre de la entrada sería solo <em>subdomain</em>. La sintaxis varía dependiendo de la interfaz que proporciona tu proveedor de dominio.</li>
                    <li>Guarda los cambios</li>
                    <li>Este cambio puede tardar entre 0 i 72 horas en ser operativo, dependiendo de la configuración de tu provedor de dominio. Sé paciente</li>
                  </ul>

		</p>';
		}
	        echo '<hr>';	
		echo '<h4 id="mxCorrect" class="center">Registros de tipo "MX" para correo electrónico</h4>';
                if($ismailActive=="FALSE" && $result) {
                  $edit_link= '<a href=/' . BASE_PATH .'/edit-domain.php?domain=' . $domain .'>Editar ' .$domain .'</a>';
                  printf(_("<span class='alert-warning'>El servidor de correo no está activado para el dominio %s. Puedes activarlo desde la página de edición del dominio: %s</span>.<br>Hasta que no lo actives, el servicio de correo electrónico no funcionará en este servidor."),$domain,$edit_link);
                }

		if(in_array($correct_mx , $allMX)|| in_array($fqdn, $allMX)){
                    echo '<p>La configuración del registro MX es correcta</p>';
		} else {
                    echo '
                    <p>La configuración del registro MX actual no es correcta. Sigue las siguientes instrucciones: 
                    </br>
                    <ul>
                      <li>entra en el panel de administración que te proprciona tu provedor de dominio.</li>
                      <li>Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em></li>
                      <li>Edita los registro de tipo MX cambiando el valor actual por el nuevo:
                      </br>
                      </br>
                      <table id="dns">
                       <thead><tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Valor actual</th>
                        <th>Valor Requerido</th>
                        <th>Priority</th>
                        </tr></thead><tbody>';

                              $i=1;	
                              if(!$resultMX)$resultMX[0]["target"]='no hay registro';
                              foreach($resultMX as $value){
                                echo "<tr>";
                                echo '<td>';
                                echo 'MX';  
                                echo '</td>';                              
                                echo "<td>";
                                echo $resultMX[0]["host"];
                                echo "</td>";
                                echo "<td>";
                                echo $resultMX[0]["target"];
                                echo "</td>";
                                echo "<td>";
                                if ($i>1)$correct_mx='Eliminar. Un solo registro MX será necesario para una correcta configuración';
                                echo $correct_mx;
                                echo "</td>";
                                echo "<td>";
                                echo "10";
                                echo "</td>";
                                echo "</tr>";
                                $i++;
                              }
                            echo '
                            </tbody>
                            </table>
                            </br>
                            </br>';
                            //if there is more than one MX record tell user that one is enough...he can delete all the others
                            if ($i>2){
                                echo '
                                <p>Tu actual configuración tiene más de un registro MX. Elimina todos los restantes. Un solo registro es necesario para poder usar el servidor mail instalado en esta máquina</p>';
                            }
                            echo '	
                            </li>
                            <li>Guarda los cambios</li>
                            <li>Este cambio puede tardar entre 0 i 72 horas en ser operativo, dependiendo de la configuración de tu provedor de dominio. Esta fase es conocida como propagación de los DNS. </br> 
 Para averiguar si los DNS se han propagado ya, puedes volver a vistar esta misma página. Cuando el estado en la primera tablilla se ponga en "OK" Ya podrás empezar a usar tu nuevo servidor de correo electrónico.</li>
                            </ul></p>';
                          }

                // If spf record is not correct, give more details about how to fix it

                 echo '<hr>';    
                 echo '<h4 id="spfCorrect" class="center">Registros de tipo SPF  para correo electrónico</h4>';
              if ($spf_stat!=$statok){
                echo 'SPF (Sender Policy Framework) es un registro de tipo TXT que especifica qué servidores pueden enviar correo electrónico en nombre de tu dominio. Los proveedores de servicios de correo electrónico a menudo requieren registros de SPF  válidos. Un registro SPF ausente o incorrecto puede provocar que tu correo electrónico sea enviado a la carpeta de correo no deseado. Algunos operadores pueden incluso bloquear tus correos por completo. Para evitar estos problemas tendrás que añadir el siguiente registro de tipo TXT a cada dominio que quieras utilizar para crear cuentas de correo electrónico (además del registro MX):';

                echo '<pre>TXT   ' . $correct_spf.' </pre>'; 

                echo '
                  <table>
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th class="longRecord">Valor Requerido</th>
                      </tr>
                      </thead>
                      <tbody>';
                echo '<tr><td>TXT</td>
                      <td>' . $domain . '</td>
                      <td>"' . $correct_spf . '"</td></tr>';
 
                echo '</tr>
                </thead>
                <tbody>';
                echo '</tbody></table>';
                // End SPF Instructions
              } else {
                echo  'La configuración del registro SPF es correcta';
              }

                echo '<br>';



             echo '<hr>';
             echo '<h4 id="dkimCorrect" class="center">Registros de tipo DKIM para correo electrónico</h4>';
             if ($dkim_stat!=$statok){
                echo '
El objetivo de DKIM (DomainKeys Identified Mail) es asegurar que un mensaje enviado por example.com sea realmente de example.com.
Vista la complejidad de su configuración es mejor asegurarse de que se ha insertado correctamente su valor en los DNS ya que, al igual que los registros SPF, es mejor no tener ningún registro DKIM que tener uno incorrecto. 

<br>Lamentablemente este tipo de registro tiene una sintaxis diferente dependiendo del proveedor con el que tengas contratado el dominio. Estos son los valores dkim  correctos para el dominio ' .$domain ;


                echo '
                  <table>
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th class="longRecord">Valor Requerido</th>
                      </tr>
                      </thead>
                      <tbody>';
                echo '<tr><td>TXT</td>
                      <td>default._domainkey.' . $domain . '</td>
                      <td>"' . $correct_dkim . '"</td></tr>';

                echo '</tr>
                </thead>
                <tbody>';
                echo '</tbody></table>';
                // End dkim Instructions
                
                echo '<br>';
                echo '<p>La sintaxis para el registro dkim varia en función del proveedor con el que tengas contratado el dominio y de si se trata de un dominio de primer nivel o de un subdominio. En esta página hemos recopilado las posibles fórmulas de configuración que podrías encontrar: <a href=\'https://docs.maadix.net/dns/#registro-dkim\' target=\'_blank\'>h:ttps://docs.maadix.net/dns/#registro-dkim<a></p>';

                echo '<p>En la misma página encontrarás un enlace a una herramienta para averiguar que el registro dkim creado sea correcto</p>';
              } else {
                echo 'La configuración del registro DKIM es correcta';
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
<?php 
?>
  </div><!--admin-content-->
  </section>
</article>
<?php 
	ldap_close($ldapconn);
	require_once('footer.php');?>
