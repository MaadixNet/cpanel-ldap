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
  $domain_ip=($resultA && $resultA[0]['ip'])?($resultA[0]['ip']):'No hay registro';

  $fqdn=trim(shell_exec('hostname -f'));
  $correct_mx=$fqdn;
  $allMX[]='';

  # Get all MX record into array
  foreach($resultMX as $value){
    array_push($allMX,$value['target']);
  }

  $message='';
 if ($server_ipaddr==$domain_ip && in_array($correct_mx , $allMX)):
          $message = sprintf(_('El sistema está creando la configuración necesaria para que tu nuevo dominio sea accesible en este servidor. Esto te permitirá alojar una aplicación web o crear cuentas de correo para este dominio.<br>
    Este proceso puede tardar hasta 5 minutos. Consulta la página de <a class="alert-link" href="view-domains.php">dominios</a> para ver el estado de la operación'));
          $result=1;
  elseif(!in_array($correct_mx , $allMX) && $server_ipaddr==$domain_ip):
          # Need this in case somebody wish to  create emails account to prepare a migration and avoid service blackout
    $message = sprintf(_('<div class="card-block"><p>Los DNS del dominio %s no están configurados para que el correo electrónico sea administrado por este servidor. Esto significa que el correo electrónico será entregado al servidor actualmente configurado en los registros MX del dominio.
      </br>
      Puedes crear igualmente la cuenta que empezará a recibir correo en este servidor cuando los DNS estén configurados correctamente. Si estás planteando una migración del dominio hacía este servidor, revisa la configuración actual y consulta cual es la correcta en esta página: <a href="editdns.php?domain=%s">Configuración de DNS activa para el dominio %s</a></p></div>'),$domain, $domain, $domain);
          $result=2;
   
  else:
          $message =sprintf(_( 'La configuración de los DNS no es la correcta para que todos los servicios disponibles puedan ser activados. Consulta la página de <a class="alert-link" href="editdns.php?domain=' . $domain . '">configuración de DNS</a> para saber más.'));
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

/** Get all inputs fields in form and apply 
*  Correct sanitze type
* 
**/


function sanitizeData($input){
  $data= array();
  foreach ($input as $k => $v){
    if(is_array($v)){
      //TODO 
      //if is an array get all values 
      //It's happening when creating domain
    } else {
      $val = trim($v);
    $sanitized_field = applySanitize($k, $val);
    $data[$k][] = $sanitized_field;
    }
  }
  return $data;
}

function applySanitize($key, $val) {
  $message='';
  switch($key) {
    case "username":
    case "user":
    case "seluser":
        if(!check_syntax ('account',$val, $length="2") || !$val) {
        $message = sprintf(_("%s Nombre de usuario no válido"), $val);
        $value = '';
      } else {
        $value = filter_var($val, FILTER_SANITIZE_STRING);
        $value = filter_var($value ,FILTER_SANITIZE_SPECIAL_CHARS);
      }
    case "surname":
    case "commonname":
    case "firstname":
      $value = filter_var($val, FILTER_SANITIZE_STRING);
      $value = filter_var($value ,FILTER_SANITIZE_SPECIAL_CHARS);
      break;
    case "usermail":
      $value = filter_var($val, FILTER_SANITIZE_EMAIL);
      if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
          $message = sprintf(_("%s no es una dirección de correo válida"), $value);
      }      
      break;
    case "domain":
      //Only chek syntax. Used in proc/chec-fields.php 
      //Dns check is performed by dns_get_record()
        $value = filter_var($val, FILTER_SANITIZE_STRING);
        $value = filter_var($value ,FILTER_SANITIZE_SPECIAL_CHARS);
        $value=htmlspecialchars($value);
        if(!check_syntax ('domain',$value) || !$value) {
          $message = sprintf(_("%s no es un nombre de dominio válido"), $value);
          $value = "";
        } else {
          //$value = filter_var($val, FILTER_SANITIZE_STRING);
        }
      break;
    default:
      $value=$val;
      break;
  }
  //return $value;
        //echo $message;
        return array('value' => $value,
                    'message' => $message
                            );

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

      echo '<li>' . sprintf (_("Servidor: %s"), $fqdn) . '</li>';
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

      echo '<li>' . sprintf (_("Servidor: %s"), $fqdn) . '</li>';
      echo '<li>' . sprintf (_("Puerto: 995" )) . '</li>';
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
                     $bSecure=true, $bHttpOnly=true, $bReplace=false) {
 
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

/******************** Get release info ***************************/

function getreleaseinfo($Ldap,$ldapconn,$ldapbind,$route){


  //Get current release
  if ($ldapbind) {
    $release_info= $Ldap->search($ldapconn, 'ou=cpanel,dc=example,dc=tld',  '(objectclass=*)');
    $release = $release_info[0]['type'][0];
  }

  //Get credentials and BASE API url
  if ($ldapbind) {
    $credentials = $Ldap->search($ldapconn, 'ou=api,dc=example,dc=tld',  '(objectclass=*)');
    $api_userid = $credentials[0]['uid'][0];
    $api_usertoken = $credentials[0]['userpassword'][0];
    $api_url = $credentials[0]['host'][0];
  }

  //API url
  $url = $api_url.$route;

  //Initiate cURL.
  $ch = curl_init($url);

  //VM name
  $vmname = gethostname();


  //The JSON data.
  $jsonData = array(
    'vmname' => $vmname,
    'release' => $release
  );

  //Encode the array into JSON.
  $jsonDataEncoded = json_encode($jsonData);

  //Tell cURL that we want to send a POST request.
  curl_setopt($ch, CURLOPT_POST, 1);

  // Will return the response, if false it print the response
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  //Attach our encoded JSON string to the POST fields.
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

  //Set the content type to application/json and add credentials to header
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Auth-Token: ' . $api_usertoken,
    'X-User-Id: ' . $api_userid
  ));

  //Execute the request
  $result = curl_exec($ch);

  // Json
  $release_info = json_decode($result, true);


  //Debug
 /* 
  echo '<pre>';
  print_r ($release_info);
  print_r ($result);
  print_r ($credentials);
  print_r ($url);
  print_r ($api_url);
  echo '</pre>';
  */

  return $release_info;
}

/******************** Get puppet status from API ***************************/

function getpuppetstatus($Ldap,$ldapconn,$ldapbind){


  //Get credentials and BASE API url
  if ($ldapbind) {
    $credentials = $Ldap->search($ldapconn, 'ou=api,' . SUFFIX,  '(objectclass=*)');
    $api_userid = $credentials[0]['uid'][0];
    $api_usertoken = $credentials[0]['userpassword'][0];
    $api_url = $credentials[0]['host'][0];
  }

  //VM name
  $vmname = gethostname();

  //API url
  $url = $api_url."vmstatus/".$vmname;

  //Initiate cURL.
  $ch = curl_init($url);

  // Will return the response, if false it print the response
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  //Set the content type to application/json and add credentials to header
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Auth-Token: ' . $api_usertoken,
    'X-User-Id: ' . $api_userid
  ));

  //Execute the request
  $result = curl_exec($ch);

  // Json
  $status_info = json_decode($result, true);

  //Debug
  /*
  echo '<pre>';
  print_r ($result);
  print_r ($url);
  print_r ($api_url);
  print_r ($status_info);
  echo '</pre>';
   */
  return $status_info["puppetstatus"];
}
/* Function to print intput fields for groups 
 * Used in service-available
 * @param $service_data : an array from the api that contains dependencies for groups
 *
*/ 
function dependencies_input_fields($service_data){
  $inputs_fields=$hidden_fields=$text='';
  $field=array();
  foreach ($service_data['dependencies'] as $dependency){ 
        if (strpos($dependency, ".") !== false){
            // This field needs input
            /*we know the string has three elements divided by dot
            * [0] = id
            * [1] = type
            * [2] = Lable
            * Explode the string and create an array withe these trhee data
            * Maybe we should add a more explicit value such as input: id.type.label.input?
             */
            $deps= explode(".", $dependency);
            $keys = array('id','type', 'label');
            $field = array_combine($keys,$deps);
            //First print the user input fields
            $inputs_fields.=sprintf(_('<label class="modalfield hide" for="%s">%s</label>'),$field['id'],$field['label']);
            $inputs_fields.=sprintf(_('<p class="form-control-static underlined hide modalfield">%s</p>'),get_input_field_description($field['id']));
            $inputs_fields.=sprintf(_('<div id="error-%s-%s" class="hide modalfield"></div>'),$service_data['id'],$field['id']);
            $inputs_fields.=sprintf(_('<input class="modalfield hide form-control" data-dependency="%s" placeholder="%s" type="%s" name="%s[%s]" />'),$field['id'],$field['label'],$field['type'],$field['id'],$service_data['id']);
            
            $hidden_fields.=sprintf(_('<input class="dependency" type="hidden" name="%s[%s]" />'),$field['id'],$service_data['id']);
        } else {
           //don' t print user input filed 
            $hidden_fields.=sprintf(_('<input class="dependency noinput" type="hidden" name="depNoInput" value="%s" />'),$dependency);
       }
  }
return array('hiddenHtml' => $hidden_fields, 'inputHtml' => $inputs_fields);
}

function get_input_field_description($field){
  switch($field){
    case 'domain':
      $text=sprintf(_("Esta aplicación necesita ser instalada bajo un dominio o subdominio propio. Inserta un dominio váldio que no esté siendo usado por ninguna otra aplicación y cuyos dns estén ya apuntando a la IP de este servidor: %s"), $_SERVER["SERVER_ADDR"]);
      break;
    case 'email':
      $text=sprintf(_("Esta aplicación necesita una cuenta de email asocicada. Inserta una diercción de correo electrónico válida:"));
      break;
    default:
      $text=sprintf(_("Inserta un texto"));
      break;
  }
  return $text;
}
