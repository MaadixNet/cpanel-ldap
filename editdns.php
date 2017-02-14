<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

require_once('header.php');
$errorttpe="";

$message="";

$psw=$Ldap->decrypt_psw();
$domain=(isset($_GET["domain"]))?$_GET["domain"]:'';
$server_ipaddr=$_SERVER["SERVER_ADDR"];
require_once('sidebar.php');
?>
<article>
  <section>
  <div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">
<?php
	$resultA=dns_get_record ( $domain,  DNS_A );
	$resultMX=dns_get_record ( $domain,  DNS_MX );
	$resultNS = dns_get_record($domain,  DNS_NS );
	$domain_ip=($resultA[0]['ip'])?($resultA[0]['ip']):'No hay registro';
        $statok='<i class="fa fa-check-circle-o icon checkok"></i>';
	$staterr='<i class="fa fa-exclamation-triangle icon checkko"></i>';
	//$correct_mx= $domain;

        $fqdn=trim(shell_exec('hostname -f'));
        $correct_mx=$fqdn;
        $allMX[]='';
        foreach($resultMX as $value){
          array_push($allMX,$value['target']);
        }
	if (($server_ipaddr==$domain_ip && in_array($correct_mx , $allMX)) || ($server_ipaddr==$domain_ip && in_array($fqdn, $allMX))): 
		echo '<div class="alert alert-success">La configuración de tu dominio es correcta para que funcione en tu servidor</div>';
	else:
		echo '<div class="alert alert-error">El dominio '. $domain . ' está incluido correctamente en tu sistema. Sin embargo necesitas cambiar ciertos parámetros del dominio para que todos los servicios funcionen en tu servidor.</br>Sigue los pasos a continuación.</div>';
	endif;
	echo '
	<h3>Configuración de DNS activa para el dominio ' . $domain . '</h3>
	</br>
	<table id="dns">
		<thead>
		<tr>
			<th>Tipo Registro</th>
                        <th>Servicio</th>
			<th>Configuración Actual</th>
			<th>Configuración Requerida</th>
			<th>Estado</th>
		</tr>
		</thead>
		<tbody>
			<tr>
				<td>A</td>
                                <td>Páginas web</td>
				<td>' . $domain_ip . '</td>
				<td>' . $server_ipaddr .'</td>';
				$domain_stat=($domain_ip==$server_ipaddr)?$statok:$staterr . " <a href='#ACorrect'>Como Corregir? </a>";
			echo '<td class="center">' . $domain_stat . '</td>
			</tr>';
			$i=1;
			foreach($resultMX as $value){
				echo "<tr>";
				echo "<td>MX</td>";
                                echo "<td>Servidor de correo</td>";
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
				}
				echo '
				</tbody>
				</table>
				</br>';

		echo '<h4>Dirección IP</h4>';
                if ($resultA){
                  echo '<h5>Esta es la IP actualmente configurada para el dominio ' . $domain . '</h5>';
                  echo '<pre>' . $resultA[0]['ip'] . '</pre>';
                } else {
                  echo '<h5>El dominio ' . $domain . ' no está registrado o no está creado. </h5>';
                }
		if ($server_ipaddr==$domain_ip){
		echo '<p>La configuración es correcta para que puedas acceder a tus aplicaciones desde el navegador, usando tu propio dominio</p></br>';}
		else {
		echo '<p>
		Cuando registramos un dominio hay un apartado en su configuración llamado DNS.
		Los DNS son los que pemiten transfomar nombres de dominio entendibles por humanos, en números que corresponden a las diferentes máquinas conectadas y accesibles públicamente en internet.</p>
		
		<p>En tu caso el número asociado a tu dominio no corresponde a tu máquina</p>

		<p>Hay diferentes tipos de contenidos que un servidor puede mostrar. Entre ellos los más comunes son páginas webs y correo.
		Para que estos servicios funcionen correctamente y desde cualquier ubicación utilizando nombres en lugar que números,, hay 	que comunicar en cual máquina están alojados los sdrvicios. Esta comunicación se lleva a cabo configurando correctamente los registros DNS para un determinado dominio. </p>
		<h4 id="ACorrect">Registro de tipo "A" para contenido web</h4>
		<p>Para que puedas acceder a tus aplicaciones desde el navegador, usando tu propio dominio</br>
			https://' . $domain . '/'. BASE_PATH . '</br>
			tendrás que cambiar la configuración del mismo.</br>
			
			Para ello sigue los siguientes pasos:
			<ul>
				<li>entra en el panel de administración que te proprciona tu provedor de dominio.</li>
				<li>Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em></li>
				<li>Edita el registro de tipo A cambiando la actual IP ' . $domain_ip .' por ' . $server_ipaddr . '</li>
				<li>Guarda los cambios</li>
				<li>Este cambio puede tardar entre 0 i 72 horas en ser operativo, dependiendo de la configuración de tu provedor de dominio. Sé paciente</li>
			</ul>
		</p>';
		}
		
		echo '<h4 id="mxCorrect">Registros de tipo "MX" para correo electrónico</h4>';
		if(in_array($correct_mx , $allMX)|| in_array($fqdn, $allMX)){
			echo 'La configuración del registro MX es correcta para que puedas enviar y recibir correo electrónico para las cuentas email del dominio '. $domain .' a través de este servidor';
		} else {
		echo '
		<p>En este propio servidor contratado com Maadix, tienes instalado un servidor de correo que puede gestionar todo tu tráfico e-mail sin delegar esta tarea a otros. Para que esto ocurra,  tendrás que cambiar los actuales valores MX de ' . $domain .', por ' .$correct_mx .' 
		</br>
			<ul>
				<li>entra en el panel de administración que te proprciona tu provedor de dominio.</li>
				<li>Localiza una pestaña que indique algo como <em>DNS/editar registros dns</em></li>
				<li>Edita los registro de tipo MX cambiando el valor actual por el nuevo:
				</br>
				</br>
				<table id="dns">
					<thead>
					<tr>
						<th>Actuales</th>
						<th>Nuevos</th>
					</tr>
					</thead>
					<tbody>';
				$i=1;	
                                if(!$resultMX)$resultMX[0]["target"]='no hay registro';
				foreach($resultMX as $value){
						echo "<tr>";
						echo "<td>";
						echo $value['target'];
						echo "</td>";
						echo "<td>";
						if ($i>1)$correct_mx='Eliminar. Un solo registro MX será necesario para una correcta configuración';
						echo $correct_mx;
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
			if($resultNS){
				echo '<h4>DNS</h4>';
				foreach($resultNS as $value){
					echo $value['target'] . '</br>';
				}
			}
		
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
