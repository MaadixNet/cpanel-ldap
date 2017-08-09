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
            $fqdn=trim(shell_exec('hostname -f'));
            printf (_("Este registro es estrictamente indispensable. Para que el electrónico sea gestionado por tu servidor y no por otro servicio externo el valor del registro MX de los DNS de tu domminio o subdominio tendrá que ser el mismo que el el nombre de ese equipo: %s . Algunos proveedores de dominio pueden necesitar que añadas un punto despues del valor"), $fqdn);

printf(_("<h6>Ejemplos de registros MX para dominio example.com y subdominio sub.example.com</h6>"));

        echo '<table><thead><tr>';
        echo '<th>Type</th>';
        echo '<th>Name</th>';
        echo '<th>Value</th>';
        echo '<th>Priority</th>';
        echo '</tr></thead><tbody>';
        echo '<tr>';
        echo '<td>MX</td>';
        echo '<td>@</td>';
        echo '<td>' . $fqdn. '</td>';
        echo '<td>10</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>MX</td>';
        echo '<td>sub</td>';
        echo '<td>' . $fqdn. '</td>';
        echo '<td>10</td>';
        echo '</tr>';
        echo '</tbody></table>';
        echo '<br />';
 printf(_("<h6>Ejemplos de registros MX  para dominio example.com y subdominio sub.example.com en texto (para aquellos proveedores que no proporcionan formulario)</h6>"));

          echo '<pre>@ 10800 IN MX 10 ' . $fqdn . '<br />';
          echo 'sub 10800 IN MX 10 ' . $fqdn . '</pre>';

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
Para comprobar si el registro DKIM que has creado para los DNS de tu dominio es correcto puedes usar la siguiente página, introduciendo el nombre de tu dominio + :default y seleccionando DKIM Lookup desde el desplegable: <a href='https://mxtoolbox.com/SuperTool.aspx' target='_blank'>https://mxtoolbox.com/SuperTool.aspx</a>. Si tu dominio fuera example.com, deberías insertar example.com:default."));
printf(_("Lamentablemente este tipo de registro tiene una sintaxis diferente dependiendo del proveedor con el que tengas contratado el dominio. Aquí hay algunos ejemplos de sintaxis válidas para dkim. Tendrás que probar hasta encontrar la correcta para tu proveedor, o bien contactar con ellos para que te guien. El valor de la cadena de carácteres p=...  es la clave publica y cambia para cada dominio. Encontrarás la cadena correcta en la sección de DNS correspondiente a tu dominio o subdominio de este panel de control."));

printf(_("<h6>Ejemplos de registros DKIM para dominio example.com (Nota la presencia o no de la comillas en en la campo Value. En algunos casos es necesaria y en otros no)</h6>"));

        echo '<table><thead><tr>';
        echo '<th>Type</th>';
        echo '<th>Name</th>';
        echo '<th>Value</th>';
        echo '</tr></thead><tbody>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey</td>';
        echo '<td>"v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"</td>';

        echo '</tr>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey</td>';
        echo '<td>v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.example.com</td>';
        echo '<td>"v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"</td>';
        
        echo '</tr>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.example.com</td>';
        echo '<td>v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB</td>';
        
        echo '</tr>';

        echo '</tbody></table>';
        echo '<br>';
        printf(_("<h6>Ejemplos de registros DKIM para dominio example.com en texto (para aquellos proveedores que no proporcionan formulario)</h6>"));
        echo '<pre>';    
        echo 'default._domainkey 10800 IN TXT    "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"<br>';
        echo 'default._domainkey.example.com. 10800 IN TXT    "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"<br>';

        echo '</pre>';

printf(_("<h6>Ejemplos de registros DKIM para subdominio sub.example.com (Nota la presencia o no de la comillas en en la campo Value. En algunos casos es necesaria y en otros no)</h6>"));

        echo '<table><thead><tr>';
        echo '<th>Type</th>';
        echo '<th>Name</th>';
        echo '<th>Value</th>';
        echo '</tr></thead><tbody>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.sub</td>';
        echo '<td>"v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"</td>';

        echo '</tr>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.sub</td>';
        echo '<td>v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.sub.example.com</td>';
        echo '<td>"v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"</td>';

        echo '</tr>';
        echo '<tr>';
        echo '<td>TXT</td>';
        echo '<td>default._domainkey.sub.example.com</td>';
        echo '<td>v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB</td>';

        echo '</tr>';

        echo '</tbody></table>';
        echo '<br>';
        printf(_("<h6>Ejemplos de registros DKIM para dominio example.com en texto (para aquellos proveedores que no proporcionan formulario)</h6>"));
        echo '<pre>';
        echo 'default._domainkey.sub 10800 IN TXT    "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"<br>';
        echo 'default._domainkey.sub.example.com. 10800 IN TXT    "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZTroWVFpkfXklFulqg8pdg/cWS+ORgoi1x97l5NyqlqL7R1qazoIVQ63TYUS8yjxU87z4FAr/zb/+p2ayk+wVqXA7twWLuHPhEHdiBQM5cEbaPX/Q3fMYULTPkmuVLA/aAcNUr3xagyNfpOzUmUAvJfOfozaSik9/ZmHRFFvzdwIDAQAB"<br>';

        echo '</pre>';

      ?>
    </div><!--row-->
  </section>
</article>
<?php require_once('footer.php');?>
