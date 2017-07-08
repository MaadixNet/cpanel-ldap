<?php
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
$current_page=basename(__FILE__);
$user_home->check_login_or_redirect($current_page);
require_once 'classes/class.DiskStatus.php';
require_once('header.php');
require_once('sidebar.php');
?>
<article class="content">
    <div class="title-block">
    <h1 class="title"> <?php printf(_("Instrucciones para configurar dominios"));?></h1>
    </div>
  <section class="section">
    <div class="row">
      <?php
      echo '<h4>';
      printf(_("Añadir dominios al sistema te permite activar varios servicios. Puedes activar cualquier dominio desde la página <a href='add-domain.php'><button type='button' class='btn btn-pill-right btn-primary'>Añadir dominios</button></a>"));
      echo '</h4><p>';
        printf(_("Dependiendo de la configuración de sus DNS podrás utilizar uno o todos los servicios disponibles en el servidor."));
      echo '</p><hr>';

      echo '<h4 class="center">';
        printf(_("Página Web"));
      echo '</h4>';
     
      echo '<h6 class="pink">';
        printf(_("Registro ded tipo A"));
      echo '</h6>'; 
        echo '<p>';
          printf (_("Para que una página web sea disponibile visitando tu dominio desde un navegador, es neceario que el valor del registro A de los dns del dominio sea el mismo que la IP de este servidor."));
        echo '</p>';
          echo '<pre><b>IP de este servidor:</b> ' . $_SERVER['SERVER_ADDR'] . '</pre>';
          echo '<p>';
          printf (_("Si cuando añades un dominio no hay coincidencia entre los dos valores el sistema te permitirá igualmente efectuar la operación, pero no creará la configuración interna necesaria para que una página web sea accesible a través de este dominio.<br>
          Periódicamente el sistema preguntará si existe esta coincidencia, y en cuanto reciba respuesta afirmativa creará la configuración necesaria y podrás visitar tu aplicación web a través del dominio.
          "));
          echo '</p>';
          echo '<hr><h4 class="center">';
            printf(_("Servidor de Correo"));
          echo '</h4>';

          echo '<p>';
          printf(_("La configuración de DNS para el servicio de email es más compleja que la anterior e implica la activación de más de un registro para un buen funcionamiento. Algunos de estos registros son opcionales, pero su ausencia podría provocar que tus emails fueran tratados como spam. Los registros que tendrás que configurar son 3:"));
          echo '<ul>';
          echo '<li>MX</li>';
          echo '<li>SPF</li>';
          echo '<li>DKIM</li>';
          echo '</ul>';

          echo '<h6 class="pink">';
            printf(_("Registro de tipo MX"));
          echo '</h6>'; 
          echo '<p>';
            printf (_("Este registro es estrictamente indispensable para que el electrónico sea gestionado por tu servidor y no por otro servicio externo. El valor del registro MX de los DNS de tu domminio  tendrá que ser el mismo que el el nombre de dominio asociado a ese equipo."));
          echo '</p>';
           $fqdn=trim(shell_exec('hostname -f'));
          echo '<pre><b>MX </b> ' . $fqdn . '</pre>';


          echo '<h6 class="pink">';
            printf(_("Registro SPF (Sender Policy Framework)"));
          echo '</h6>';
          echo '<p>';
            printf(_("El SPF es un registro de tipo TXT que especifica qué servidores pueden enviar correo electrónico en nombre de tu dominio. Los proveedores de servicios de correo electrónico a menudo requieren registros de SPF (Sender Policy Framework) válidos. Un registro SPF ausente o incorrecto puede provocar que tu correo electrónico sea enviado a la carpeta de correo no deseado. Algunos operadores pueden incluso bloquear tus correos por completo. Para evitar estos problemas tendrás que añadir el siguiente registro de tipo TXT a cada dominio que quieras utilizar para crear cuentas de correo electrónico (además del registro MX):"));
          echo '</p>';
          echo '<pre>TXT    "v=spf1 mx ip4:' . $_SERVER["SERVER_ADDR"] .' a:' . $fqdn .  ' ~all" ';

          echo '</pre>';
          echo '<h6 class="pink">';
            printf(_("Registro DKIM (DomainKeys Identified Mail)"));
          echo '</h6>';
          echo '<p>';
            printf(_("El objetivo de DKIM es asegurar de que un mensaje enviado por example.com es realmente de example.com. Para ello DKIM agrega automáticamente una firma digital a cada mensaje. Esta firma se basa en una clave privada conocida únicamente por el servidor que envía el correo. El servidor receptor puede utilizar la clave pública (incluida en el registro dkim de los DNS) para decodificar la firma y asegurarse de que el mensaje no ha sido alterado. Aunque el propósito inicial era la seguridad, muchos ISP, incluyendo Gmail, Yahoo y otros, usan la información de DKIM para validar la calidad y la autenticidad del mensaje. Si la firma DKIM falla, hay una probabilidad muy alta de que el mensaje sea enviado a la carpeta de spam.<br />
Vista la complejidad de su configuración es mejor asegurarse de que se ha insertado correctamente su valor en los DNS ya que, al igual que los registros SPF, es mejor no tener ningún registro DKIM que tener uno incorrecto.<br />
Para comprobar si el registro DKIM que has creado para los DNS de tu dominio es correcto puedes usar la siguiente página, introduciendo el nombre de tu dominio y seleccionando DKIM Lookup desde el desplegable: <a href='https://mxtoolbox.com/SuperTool.aspx' target='_blank'>https://mxtoolbox.com/SuperTool.aspx</a> "));
          echo '</p>';
          echo '<pre>TXT    "v=spf1 mx ip4:' . $_SERVER["SERVER_ADDR"] .' a:' . $fqdn .  ' ~all" ';

          echo '</pre>';

          echo '<hr><h4 class="center">';
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
    </div><!--row-->
  </section>
</article>
<?php require_once('footer.php');?>
