<?php 
 require_once __DIR__.('/site-config.php');

// The admin bind dn 
# Get value from function. if it doesn't work set it manually
$binddn =  get_bind_dn();
define ("BINDDN",$binddn);

 # Get admin name dinamically throug a ESXTERNAL bind connection
 # Ldap need to be configured in oreder to allow 
 # anonymous bind connection for apache
 # If you don't want this kind od configuratiosn for your ldap
 # server define BINDDN manually...as eg: cn=admin,dc=example,dc=tld 

## Get the admin bame
function get_bind_dn () {
      //Special connection Only read mode to rertieve cn and mail
      $host = "ldapi:///";
      $base = SUFFIX;

      $ds = ldap_connect($host);

      //buscamos cualquier entrada
      $filter="(&(objectclass=extensibleObject)(!(cn=uidNext)))";
      //de las entradas solo queremos cn 
      $justthese = array("cn");

      $sr=ldap_search($ds, $base, $filter, $justthese);
      $info = ldap_get_entries($ds, $sr);
      $adminname = $info[0]["dn"];
      return $adminname;
}



/* Send mails using PHPmailer class
* @param $from
* @param $to
* @param $message - HTML format
* @param $subject
* @param $attacmhents - Can be a list of comma separated files
*/



function send_mail($from,$to,$message,$subject,$attachments='')

  {                                             
    require_once __DIR__.('/mailer/class.phpmailer.php');
    $mail = new PHPMailer();
    $mail->From =$from; 
    $mail->addAddress($to);
    $mail->SetFrom($from,$from);
    $mail->AddReplyTo($from,$from); 
    $mail->CharSet = 'UTF-8';
    //Provide file path and name of the attachments
    $mail->addAttachment($attachments); //Filename is optional
    $mail->Subject = $subject;
    $mail->MsgHTML($message);
    if(!$mail->send()) 
    {
      echo "Error: " . $mail->ErrorInfo;
    } 
    else 
    {
  //    echo "Instrucciones enviadas con éxito";
    }
  }     

function get_service_data($service){
  $result=array();
  switch ($service)
  {
  case 'mail':
    $image = "images/services/postfix-dovecot.png";
    $title = sprintf(_("Servidor de correo electrónico"));
    $link_url = '/' . BASE_PATH . '/mails.php';
    $link_text = sprintf(_("Administrar email"));
    $software = sprintf(_("Postfix. Dovecot"));
    $description = sprintf(_("Puedes añadir infinitas cuentas de correo por cada dominio o subdominio que tengas activado en el Cpanel. Para ello tienes que primero <a href='/cpanel/add-domain.php'>Activar un dominio</a> y luego podrás <a href='/cpanel/mails.php'>crear cuentas de correo electrónico</a> asociadas al mismo dominio. Puedes también consultar este tutorial para más información : <a href='http://docs.maadix.net/email' target='_blank'>Cómo crear cuentas de correo electrónico</a>"));

    break;

  case 'openvpn':
    $image = "images/services/openvpn.png";
    $title = sprintf(_("Servidor VPN"));
    $link_url = '/' .  BASE_PATH . '/usuarios.php';
    $link_text = sprintf(_("Activar cuentas VPN "));
    $software = sprintf(_("OpenVpn"));
    $description = sprintf(_("Activa  cuentas VPN para habilitar conexiones seguras y cifradas al servidor, o para navegaqr por Internet.
       Las cuentas VPN van asoicadas a cunetas de usuarios. Para utlizar este servicio puedes <a href='/cpanel/usuarios.php'>crear un nuevo usuario o bien editar uno</a> ya existente activando la casilla 'Cuenta VPN'. <br>
       Puedes también consultar este tutorial para más información : <a href='http://docs.maadix.net/vpn'>Cómo activar cuentas VPN</a> "));
    break;

  case 'owncloud':
    $image = "images/services/owncloud.png";
    $title = sprintf(_("Servidor de alojamiento y sincronización de archivos"));
    $link_url = $_SERVER['HTTP_HOST'] . '/owncloud';
    $link_text = sprintf(_("Ir a la Aplicación "));
    $software = sprintf(_("Owncloud"));
    $description = sprintf(_("Trabaja en documentos, presentaciones, hojas de cálculo, bases de datos... siempre a mano y listos para compartir. Edita y comenta documentos colaborativamente. Impórtalos y expórtalos de otras plataformas. Trabaja en ellos en cualquier momento y desde cualquier dispositivo incluso cuando estás sin conexión. Importa automàticamente tus contactos desde Gmail, Outlook, thunderbird, Yahoo u otros formatos de archivos. Organiza tu trabajo en línea decidiendo el nivel de acceso que quieres darle a cada usuario que autorices.
          <br>  <a href='/owncloud' target='_blank'>Ir a la aplicación</a>"));

    break;

  case 'rainloop':
    $image = "images/services/rainloop.png";
    $title = sprintf(_("Webmail"));
    $link_url = $_SERVER['HTTP_HOST'] . '/rainloop';
    $link_text = sprintf(_("Ir a la Aplicación "));

    $software = sprintf(_("Rainloop"));
    $description = sprintf(_("Interfaz web para consulta e envío de correo electrónico desde el navegador. <br> <a href='/rainloop/' title='Rainloop' target=_'blank'>Ir a la aplicación</a>"));
    
    break;

  case 'afterlogic':
    $image = "images/services/email-text.png";
    $title = sprintf(_("Webmail"));
    $link_url = $_SERVER['HTTP_HOST'] . '/afterlogic';
    $link_text = sprintf(_("Ir a la Aplicación "));

    $software = sprintf(_("After Logic"));
    $description = sprintf(_("Interfaz web para consulta e envío de correo electrónico. Puedeis ver una demo aquí: <a href='https://lite.afterlogic.com/' title='After Logic demo' target=_'blank'>https://lite.afterlogic.com/</a>"));
    break;

  case 'roundcube':
    $image = "images/services/email-text.png";
    $title = sprintf(_("Webmail"));
    $link_url = $_SERVER['HTTP_HOST'] . '/roundcube';
    $link_text = sprintf(_("Ir a la Aplicación "));

    $software = sprintf(_("Roundcube"));
    $description = sprintf(_("Interfaz web para consulta e envío de correo electrónico. Web del proyecto: <a href='https://roundcube.net/about/' title='Roundcube website' target=_'blank'>https://roundcube.net/about/</a>"));
    break;

  case 'phpmyadmin':
    $image = "images/services/phpmyadmin.png";
    $title = sprintf(_("Mysql"));
    $link_url = $_SERVER['HTTP_HOST'] . '/phpmyadmin';
    $link_text = sprintf(_("Ir a la Aplicación "));
    $software = sprintf(_("phpMyAdmin"));
    $description = sprintf(_("Interfaz web para administración de base de datos mysql. Para proteger ultriormente tus bases de datos, esta aplicación está protegida con doble contraseña. Solo los usuarios que tengan activado el acceso sftp y el usuario por defecto del sistema pueden acceder a este interfaz. Para la prinera autentificación, que se muestra con una ventana emergente, tendrás que insertar un usuario válido (sftp o usuario por defecto del sistema). En la segunda tienes que utilizar las credenciales de un usuario Mysql que tengas creado. Es aconsejable crear un usuario Mysql diferente por cada base de datos.<br> Tanto los usuarios como las mismas base de datos se pueden crear desde este interfaz. Por defecto solo el usuario root de Mysql tiene los privilegios necesarios para crear nuevas bases de datos, nuevos usuarios,  y otorgar permisos a cada uno de ellos. <br> <a href='/phpmyadmin/' title='PhpMyAdmin' target=_'blank'>Ir a la aplicación</a>"));
    break;

  case 'piwik':
    $image = "images/services/piwik.png";
    $title = sprintf(_("Estadísticas"));
    $link_url = $_SERVER['HTTP_HOST'] . '/piwik';
    $link_text = sprintf(_("Ir a la Aplicación "));
    $software = sprintf(_("Piwik"));
    $description = sprintf(_("Alternativa OpenSource a Google Analytics. Rastrea en tiempo real páginas vistas y visitas de tus sitio web. Piwik muestra infoo
      rmes con respecto a la ubicación geográfica de las visitas, origen de las visitas,el tiempo de visitas y más. <a href='cpanel/piwik' title='Piwik' target=_'bb
      lank'>Ir al a aplicación</a>"));
    break;

  case 'wordpress':
    $image = "images/services/wordpress.png";
    $title = sprintf(_("Web"));
    $software = sprintf(_("Wordpress"));
    $description=sprintf(_("Sistema de gestión de contenidos o CMS enfocado a la creación de cualquier tipo de sitio web. <a href='https://es.wordpress.org/' target=_'blank'>https://es.wordpress.org/</a>"));
    break;

  case 'drupal':
    $image = "images/services/drupal.png";
    $title = sprintf(_("Web"));
    $software = sprintf(_("Drupal"));
    $description = sprintf(_("Sistema de gestión de contenidos o CMS enfocado a la creación de cualquier tipo de sitio web. <a href='https://www.drupal.org/' target=_'blank'>https://www.drupal.org/</a>"));
    break;

  case 'etherpad':
    $image = "images/services/etherpad.png";
    $title = sprintf(_("Edición colaborativa online"));
    $link_url = $_SERVER['HTTP_HOST'] . '/etherpad';
    $link_text = sprintf(_("Ir a la Aplicación "));
    $software = sprintf(_("Etherpad Lite"));
    $description=sprintf(_("Editor web basado en la colaboración en tiempo real, lo que permite a varios autores editar simultáneamente un documento de texto, y ver todos los participantes en las ediciones en tiempo real, con la capacidad de mostrar el texto de cada autor en diferente color. También hay una ventana de chat en la barra lateral para permitir la comunicación directa.<href='http://etherpad.org/' target=_'blank'>http://etherpad.org/</a>"));
    break;

  case 'mailman':
    $image = "images/services/mailman.png";
    $title = sprintf(_("Listas de correo"));
    $link_url = $_SERVER['HTTP_HOST'] . '/mailman';
    $link_text = sprintf(_("Ir a la Aplicación "));
    $software = sprintf(_("GNU Mailman"));
    $description = sprintf(_("aplicación de software del proyecto GNU, que maneja listas de correo electrónico o simplemente listas de correo. <href='http://www.list.org/' target=_'blank'>http://www.list.org/</a>"));
    break;

  default:
    break;
  }
    $result=array (
      'image' => $image,
      'link_url' => $link_url,
      'link_text' => $link_text,
      'title' =>$title,
      'software' => $software,
      'description' => $description
    );

  return $result;
}
 

function ssha_hash_password($password) // SSHA with random 4-character salt
{

  $salt = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',4)),0,4);
  return '{SSHA}' . base64_encode(sha1( $password.$salt, TRUE ). $salt);

}

function create_password(){
  $chrList = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  // Minimum/Maximum times to repeat character List to seed from
  $chrRepeatMin = 1; // Minimum times to repeat the seed string
  $chrRepeatMax = 10; // Maximum times to repeat the seed string

  // Length of Random String returned
  $chrRandomLength = mt_rand(8, 15);

  // The ONE LINE random command with the above variables.
  return substr(str_shuffle(str_repeat($chrList, mt_rand($chrRepeatMin,$chrRepeatMax))),1,$chrRandomLength);


}

function ldap_password_hash($password_clear,$enc_type)
{
    $salt = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',4)),0,4);
    $enc_type = strtolower($enc_type);

    switch($enc_type)
    {

    case 'crypt':

        $password_hash = '{CRYPT}'.crypt($password_clear);
        break;

    case 'md5':

    $password_hash = '';
    $md5_hash = md5($password_clear);

    for ( $i = 0; $i < 32; $i += 2 )
      $password_hash .= chr( hexdec( $md5_hash{ $i + 1 } ) + hexdec( $md5_hash{ $i } ) * 16 );
      $password_hash = '{MD5}'.base64_encode($password_hash);
      break;
    case 'ssha':

    if (function_exists('mhash') && function_exists('mhash_keygen_s2k')) {
      mt_srand((double)microtime()*1000000);
      $salt = mhash_keygen_s2k(MHASH_SHA1,$password_clear,substr(pack('h*',md5(mt_rand())),0,8),4);
      $password_hash = sprintf('{SSHA}%s',base64_encode(mhash(MHASH_SHA1,$password_clear.$salt).$salt));

    } else {
        error(_('Your PHP install does not have the mhash() or mhash_keygen_s2k() function. Cannot do S2K hashes.'),'error','index.php');
    }

      break;

    case 'md5crypt':

      if (! defined('CRYPT_MD5') || CRYPT_MD5 == 0)

        error(_('Your system crypt library does not support md5crypt encryption.'),'error','index.php');

        $password_hash = sprintf('{CRYPT}%s',crypt($password_clear,'$1$'.$salt));

      break;

    case 'clear':
        $password_hash = $password_clear;
        break;

    default:
        $password_hash = '{CRYPT}'.crypt($password_clear);
        break;
    }

    return $password_hash;
}

/**
 * FROM phpldapadmin code
 * Used to generate a random salt for crypt-style passwords. Salt strings are used
 * to make pre-built hash cracking dictionaries difficult to use as the hash algorithm uses
 * not only the user's password but also a randomly generated string. The string is
 * stored as the first N characters of the hash for reference of hashing algorithms later.
 *
 * @param int The length of the salt string to generate.
 * @return string The generated salt string.
 */
function random_salt($length) {

    $possible = '0123456789'.
        'abcdefghijklmnopqrstuvwxyz'.
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
        './';
    $str = '';
    mt_srand((double)microtime() * 1000000);

    while (strlen($str) < $length)

        $str .= substr($possible,(rand()%strlen($possible)),1);

    return $str;
}

/**
* Check DNS for domain 
*
* @author Maddish
*
* @param string $domain The domain to check
* @return  array (message) 
**/

function check_domain_dns($domain){

  #Current server pubblic IP addres
  $server_ipaddr=$_SERVER["SERVER_ADDR"];

  # Give domain dns records
  $resultA=dns_get_record ( $domain,  DNS_A );
  $resultMX=dns_get_record ( $domain,  DNS_MX );
  $resultNS = dns_get_record($domain,  DNS_NS );
  $domain_ip=($resultA[0]['ip'])?($resultA[0]['ip']):'No hay registro';

  $fqdn=trim(shell_exec('hostname -f'));
  $correct_mx=$fqdn;
  $allMX[]='';

  # Get all MX record into array
  foreach($resultMX as $value){
    array_push($allMX,$value['target']);
  }

  $message='';
  if(!$resultA):
          $message = '<div class="alert alert-error">Este dominio no existe. Tienes que registrarlo antes de poder utilizarlo. </div>';
          $result=0;
  elseif ($server_ipaddr==$domain_ip && in_array($correct_mx , $allMX)):
          $message = 'El sistema está creando la configuración para que tu nuevo dominio sea accesible. Esto te permitirá alojar una aplicación web o crear cuentas de correo para este dominio.<br>
    Este proceso puede tardar tardar hasta 5 minutos. Comsulta la página de <a class="alert-link" href="view-domains.php">dominios</a> para ver el el estado de la operación';
          $result=1;
/*  elseif(!in_array($correct_mx , $allMX)):
          # Need this in case somebody wish to use webmaiol with external mail server (eg: google or any other)
          $message .= '';
          $result=2;
   */
  else:
          $message = 'La configuración de los DNS no es la correcta para que todos los servicios disponibles puedan ser activados. Consulta la página de <a class="alert-link" href="editdns.php?domain=' . $domain . '">configuración de DNS</a> para sbaer más.';
          $result=3;
  endif;

  //return  $message;
  return array('message' => $message,
                'result' => $result
              );


}




/**
* Various syntax check (IP address, domain, email address...)
*
* @author Alessandro De Zorzi <adezorzi@rhx.it>
* @todo Check if IP 0 < number <255
*
* @param string $type The kind of data
* @param string $arg The value
* @param int $length The min length of string
* @todo name
* @return bool
**/

function check_syntax ($type,$arg,$length="0")
{
    if (strlen($arg) < $length)
    {
        return false;
    }

    // IP Address
    if ($type == 'ip')
    {
        if (!preg_match ("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $arg))
        {
            return FALSE;
        }

        /*$numbers = explode('.',$arg);

        foreach ($numbers as $number)
        {
            if ($number > 255)
            return FALSE;
        } */

        else
        {
            return TRUE;
        }
    }
   // DOMAIN
    elseif ($type == 'domain')
    {
        if (!preg_match("/^([0-9a-z][0-9a-z-]+\.)+[a-z]{2,7}$/i", $arg))
        {
            return FALSE;
        }

        else
        {
            return TRUE;
        }
    }

 // ALIAS and ACCOUNT
    elseif ($type == 'account')
    {
        if (!preg_match("/^[\._a-z0-9-]+$/i", $arg))
        {
            return FALSE;
        }

        else
        {
            return TRUE;
        }
    }

    // Password
    elseif ($type == 'password')
    {
        if (!preg_match("/^[\._a-z0-9-]+$/i", $arg))
        {
            return false;
        }
        else
        {
          return true;
        }
    }

    // Email
    elseif ($type == 'email')
    {
        if (!preg_match("/^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$/i", $arg))
        {
            return FALSE;
        }

        else
        {
            return TRUE;
        }  
    }

    // Name
    elseif ($type == 'name')
    {
        return true;
    }
}
function print_mail_client_settings($email,$domain){
      $fqdn=$fqdn=trim(shell_exec('hostname -f'));
      echo '<div class="card sameheight-item">
              <div class="card-block">
              <!-- Nav tabs -->'; 
      echo '<div class="card-title-block">
            <h3 class="title">' . sprintf (_("Configurar cliente de correo") ) .  '</h3>
            </div>';
      echo '<ul class="nav nav-tabs nav-tabs-bordered">
              <li class="nav-item"> <a href="#imap" class="nav-link active" data-target="#imap" data-toggle="tab" aria-controls="imap" role="tab">IMAP</a></li>
              <li class="nav-item"> <a href="#pop" class="nav-link" data-target="#pop" aria-controls="pop" data-toggle="tab" role="tab">POP</a> </li>
              </ul>';
      echo '<div class="tab-content tabs-bordered">
                <div class="tab-pane fade in active" id="imap">
                  <h4>IMAP</h4>
                  <p>';
      echo '<h4>' . sprintf (_("Correo entrante")) . '</h4>';
      echo '<ul>';

      echo '<li>' . sprintf (_("Servidor: %s"), $domain) . '</li>';
      echo '<li>' . sprintf (_("Puerto: 993" )) . '</li>';
      echo '<li>' . sprintf (_("Seguridad: SSL/TLS" )) . '</li>';
      echo '<li>' . sprintf (_("Nombre de usuario: %s"),$email) . '</li>';
      echo '<li>' . sprintf (_("Contraseña: Normal ")) . '</li>';
      echo '</ul>';
      echo '<h4>' . sprintf (_("Correo saliente")) . '</h4>';
      echo '<ul>';
      $fqdn=trim(shell_exec('hostname -f')) . '</li>';
      echo '<li>' . sprintf (_("Servidor: %s"), $fqdn) . '</li>';
      echo '<li>' . sprintf (_("Puerto: 465" )); 
      echo '<li>' . sprintf (_("Seguridad: SSL/TLS" )) . '</li>';
      echo '<li>' . sprintf (_("Nombre de usuario: %s"),$email) . '</li>';
      echo '<li>' . sprintf (_("Contraseña: Normal")) . '</li>';
      echo '</ul>';
      echo '</p>
            </div>';

      echo '<div class="tab-pane fade" id="pop">
                  <h4>POP</h4>
                  <p>';
      echo '<h4>' . sprintf (_("Correo entrante")) . '</h4>';
      echo '<ul>';

      echo '<li>' . sprintf (_("Servidor: %s"), $domain) . '</li>';
      echo '<li>' . sprintf (_("Puerto: 110" )) . '</li>';
      echo '<li>' . sprintf (_("Seguridad: STARTTLS" )) . '</li>';
      echo '<li>' . sprintf (_("Nombre de usuario: %s"),$email) . '</li>';
      echo '<li>' . sprintf (_("Contraseña: Normal ")) . '</li>';
      echo '</ul>';
      echo '<h4>' . sprintf (_("Correo saliente")) . '</h4>';
      echo '<ul>';
      $fqdn=trim(shell_exec('hostname -f')) . '</li>';
      echo '<li>' . sprintf (_("Servidor: %s"), $fqdn) . '</li>';
      echo '<li>' . sprintf (_("Puerto: 465" ));
      echo '<li>' . sprintf (_("Seguridad: SSL/TLS" )) . '</li>';
      echo '<li>' . sprintf (_("Nombre de usuario: %s"),$email) . '</li>';
      echo '<li>' . sprintf (_("Contraseña: Normal")) . '</li>';
      echo '</ul>';
      echo '</p>
            </div>';

      echo '</div>
          <!-- /.card-block -->
            </div>
          <!-- /.card -->
          </div>';
}
/* from Squirrelmail code
 * http://squirrelmail.org/docs/devel-code/__filesource/fsource_squirrelmail__functionsstrings.php.html#a585
 */

 /** Encrypts password
 *
 * These functions are used to encrypt the password before it is
 * stored in a cookie. The encryption key is generated by
 * OneTimePadCreate();
 *
 * @param string $string the (password)string to encrypt
 * @param string $epad the encryption key
 * @return string the base64-encoded encrypted password
 * @since 1.0
 */
function OneTimePadEncrypt ($string, $epad) {
    $pad = base64_decode($epad);
 
    if (strlen($pad)>0) {
        // make sure that pad is longer than string
        while (strlen($string)>strlen($pad)) {
            $pad.=$pad;
        }
    } else {
        // FIXME: what should we do when $epad is not base64 encoded or empty.
    }
 
    $encrypted = '';
    for ($i = 0; $i < strlen ($string); $i++) {
        $encrypted .= chr (ord($string[$i]) ^ ord($pad[$i]));
    }
 
    return base64_encode($encrypted);
}
 
/**
 * Decrypts a password from the cookie
 *
 * Decrypts a password from the cookie, encrypted by OneTimePadEncrypt.
 * This uses the encryption key that is stored in the session.
 *
 * @param string $string the string to decrypt
 * @param string $epad the encryption key from the session
 * @return string the decrypted password
 * @since 1.0
 */
function OneTimePadDecrypt ($string, $epad) {
    $pad = base64_decode($epad);
 
    if (strlen($pad)>0) {
        // make sure that pad is longer than string
        while (strlen($string)>strlen($pad)) {
            $pad.=$pad;
        }
    } else {
        // FIXME: what should we do when $epad is not base64 encoded or empty.
    }
 
    $encrypted = base64_decode ($string);
    $decrypted = '';
    for ($i = 0; $i < strlen ($encrypted); $i++) {
        $decrypted .= chr (ord($encrypted[$i]) ^ ord($pad[$i]));
    }
 
    return $decrypted;
}
 
/**
 * Creates encryption key
 *
 * Creates an encryption key for encrypting the password stored in the cookie.
 * The encryption key itself is stored in the session.
 *
 * Pad must be longer or equal to encoded string length in 1.4.4/1.5.0 and older.
 * @param int $length optional, length of the string to generate
 * @return string the encryption key
 * @since 1.0
 */
function OneTimePadCreate ($length=100) {
    $pad = '';
    for ($i = 0; $i < $length; $i++) {
        $pad .= chr(mt_rand(0,255));
    }
 
    return base64_encode($pad);
}

 
/**
 * Set a cookie
 *
 * @param string  $sName     The name of the cookie.
 * @param string  $sValue    The value of the cookie.
 * @param int     $iExpire   The time the cookie expires. This is a Unix
 *                            timestamp so is in number of seconds since
 *                            the epoch.
 * @param string  $sPath     The path on the server in which the cookie
 *                            will be available on.
 * @param string  $sDomain   The domain that the cookie is available.
 * @param boolean $bSecure   Indicates that the cookie should only be
 *                            transmitted over a secure HTTPS connection.
 * @param boolean $bHttpOnly Disallow JS to access the cookie (IE6 only)
 * @param boolean $bReplace  Replace previous cookies with same name?
 *
 * @return void 
 *
 * @since 1.4.16 and 1.5.1
 *
 */

function sqsetcookie($sName, $sValue='deleted', $iExpire=0, $sPath="", $sDomain="",
                     $bSecure=false, $bHttpOnly=true, $bReplace=false) {
 
    // some environments can get overwhelmed by an excessive
    // setting of the same cookie over and over (e.g., many
    // calls to this function via sqsession_is_active() result
    // in repeated setting of the session cookie when $bReplace
    // is FALSE, but something odd happens (during login only)
    // if we change that to default TRUE) ... so we keep our own
    // naive per-request name/value cache and only set the cookie
    // if its value is changing (or never seen before)
    static $cookies = array();
    if (isset($cookies[$sName]) && $cookies[$sName] === $sValue)
        return;
    else
        $cookies[$sName] = $sValue;
 
/*TODO check https 
    // if we have a secure connection then limit the cookies to https only.
    global $is_secure_connection;
    if ($sName && $is_secure_connection)
        $bSecure = true;
 */
 
    if (false && check_php_version(5,2)) {
       // php 5 supports the httponly attribute in setcookie, but because setcookie seems a bit
       // broken we use the header function for php 5.2 as well. We might change that later.
       //setcookie($sName,$sValue,(int) $iExpire,$sPath,$sDomain,$bSecure,$bHttpOnly);
    } else {
        if (!empty($sDomain)) {
            // Fix the domain to accept domains with and without 'www.'.
            if (strtolower(substr($sDomain, 0, 4)) == 'www.')  $sDomain = substr($sDomain, 4);
            $sDomain = '.' . $sDomain;
 
            // Remove port information.
            $Port = strpos($sDomain, ':');
            if ($Port !== false)  $sDomain = substr($sDomain, 0, $Port);
        }
        if (!$sValue) $sValue = 'deleted';
        header('Set-Cookie: ' . rawurlencode($sName) . '=' . rawurlencode($sValue)
                            . (empty($iExpire) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $iExpire) . ' GMT')
                            . (empty($sPath) ? '' : '; path=' . $sPath)
                            . (empty($sDomain) ? '' : '; domain=' . $sDomain)
                            . (!$bSecure ? '' : '; secure')
                            . (!$bHttpOnly ? '' : '; HttpOnly'), $bReplace);
    }
}
