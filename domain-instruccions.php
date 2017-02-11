<?php
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
$current_page=basename(__FILE__);
$user_home->check_login_or_redirect($current_page);
require_once 'classes/class.DiskStatus.php';
require_once('header.php');
?>
<div id="admin-content" class="content">
<?php
echo '<h1 class="pink">'. sprintf(_("Instrucciones para configurar dominios")). '</h1>';
echo '<h3>';
printf(_("Añadir dominios al sistema te permite activar varios servicios. Puedes activar cualquier dominio desde la página <a href='add-domain.php'>'Añadir dominios'</a>."));
echo '</h3>';
echo '</h4>'; 
  printf(_("Dependiendo de la configuración de sus DNS podrás utilizar uno o todos los servicios disponibles en el servidor."));
echo '</h4><hr>';

echo '<h4 class="pink">';
  printf(_("Página Web"));
echo '</h4>';
  echo '<p>';
    printf (_("Para que una página web sea disponibile visitando tu dominio desde un navegador, es neceario que el valor del registro A de los dns del dominio sea el mismo que la IP de este servidor."));
  echo '</p>';
    echo '<pre><b>IP de este servidor:</b> ' . $_SERVER['SERVER_ADDR'] . '</pre>';
    echo '<p>';
    printf (_("Si cuando añades un dominio no hay coincidencia entre los dos valores el sistema te permitirá igualmente efectuar la operación, pero no creará la configuración interna necesaria para que una página web sea accesible a través de este dominio.<br>
    Periódicamente el sistema preguntará si existe esta coincidencia, y en cuanto reciba respuesta afirmativa creará la configuración necesaria y podrás visitar tu aplicación web a través del dominio.
    "));
    echo '</p>';
    echo '<hr><h4 class="pink">';
      printf(_("Servidor de Correo"));
    echo '</h4>';
    echo '<p>';
      printf (_("Si quieres que el correo electrónico sea gestionado por tu servidor y no por otro servicio externo, es necesario que el valor del registro MX de los DNS de tu domminio  sea el mismo que el el nombre de dominio asociado a ese equipo."));
    echo '</p>';
     $fqdn=trim(shell_exec('hostname -f'));
    echo '<pre><b>Nombre de dominio de este equipo:</b> ' . $fqdn . '</pre>';

    echo '<p>';
    printf (_("Hasta que no haya coincidencia entre los dos valores, el servicio de correo electrónico no podrá ser gestionado por este equipo. Los mensaje serán enviados al servidor que aparece en el registro MX del dominio.")); 
    echo '</p>';
    echo '<hr><h4 class="pink">';
        printf(_("Webmail (Correo web)"));
        echo '</h4>';
        echo '<p>';
        printf (_("Con este término se entiende un interfaz web por el que puedes acceder a tu correo electrónico para consultar o enviar mails.<br>
        Tienes la posibilidad de utilizar el interfaz webmail de este equipo para consultar cualquier cuenta existenete a la que tengas acceso, aunque sea operada por otro servidor. Así por ejemplo podrías consultar tu cuenta de gmail, o de cualquier otro dominio que tengas activado con otro provedor. Para ello tienes primero que añadir el dominio a través de la página '<a href='add-domain.php'>Añadir dominio</a>' (por ejemplo gmail.com) y luego visitar el interfaz webmail identificándote con la cuenta de correo de electrónico y su contraseña (por ejemplo example@gmail.com). "));
        echo '</p>';
        echo '<p>';
        printf (_("En este caso el sistema de entrega de los mensajes estáría gestionado por gmail, y por supuesto no habría coincidencia con los registros MX ya que el dominio gmail.com estará configurado para funcionar con sus propios servidores.<br> 
          Así que desde el cpanel no pdrías crear nuevas cuentas de correo electronico para este dominio. No puedes dar de alta nuevas cuentas de gmail desde este equipo, pero sí puedes descargarte y consultar todos lo mensajes.<br>
Este mismo principio funciona para otras cuentas bajo otros dominios."));
        echo '</p>';
?>
</div><!--admin-content-->
<?php require_once('footer.php');?>
