<?php
/**
 * Created by Maddish
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();
$message=$permission="";
$url='index.php';

/* This page doesn't load the header
 * Nedd to make a connection + bind 
*/
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
    $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
/*
if($Ldap->is_logged_in())
{
  $permission = $_SESSION["login"]["level"];

  switch ($permission) :
  case 10 :
    $url='index.php';
  break;
  case 4 :
    $url='mails.php';
  break;
  case 2:
    $url='edit-mail.php';
  break;

  default:
  break;
  endswitch;
  $user_home->redirect($url);


}
 */

// Need to reiceive the new url from nameserver.php
// How to do withou get
//$newlink =   
//
/* User will land to this page before session is destroued,
 * So we can get fqdn value from Ldpa
 * After having this value the user is logged out
 */

// get new fqdn
// ou=fqdn_domain,ou=conf,ou=cpanel,dc=example,dc=tld

$new_fqn_arr= $Ldap->search($ldapconn, "ou=fqdn_domain,ou=conf,ou=cpanel," . SUFFIX ,'(&(objectClass=organizationalUnit))');
$new_fqdn = $new_fqn_arr[0]['status'][0];
$hostname = trim(shell_exec('hostname'));
$new_url = $hostname .'.'. $new_fqdn;
session_destroy();
function print_form($new_url){
    		echo '<div class="form-signin hidden-form">
                      <h5 class="text-center auth-title">' . sprintf(_("Proceso terminado")) .'</h5>
                      <hr>
                      <div class="form-group">
                      <a class="full" href="https://' . $new_url .'/cpanel"><button class="btn btn-block btn-primary">' . sprintf(_("Aceeder a la nueva url")) . '</button></a>
                      </div>
                    </div>
                    <br>';
}

function updating($new_url){
                $spinner = '<img src="/cpanel/images/spinner.gif" />';
    		echo '<div class="updating hidden-form">
  		        <h2 class="form-signin-heading">' . sprintf(_("Actualizando sistema %s"), $spinner) . '</h2>
		        <hr>
                        <div class="form-group">
        	          <span>' . sprintf(_("Esta operación tardará varios minutos. En cuanto el proceso termine, se activará en esta misma página, el enlace para acceder al panel de control bajo la nueva dirección https://%s/cpanel"),$new_url) . '</span>
                        </div>
    		      </div>';
}

?>
<!doctype html>
<html class="no-js" lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Procesiing | Cpanel</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root dir -->
        <link rel="shortcut icon" href="favicon.ico">
        <link rel="stylesheet" href="css/vendor.css">
        <link rel="stylesheet" href="css/login.css">
        <!-- Theme initialization -->
        <script src="js/styles.js"></script>    
    </head>
    <body>
        <div class="auth">
            <div class="auth-container">
                <div class="card">
                    <header class="auth-header">
                        <h1 class="auth-title">
                             Panel de Control </h1>
                    </header>
                    <div class="auth-content" id="newUrl" data-url="https://<?php echo $new_url;?>/cpanel/status.php">
                    <?php
                    echo $message;
                    print_form($new_url);
                    updating($new_url);
                    ?>


                    </div>
                </div>
            </div>
        </div>
      <script src="js/vendor.min.js"></script>
      <script src="js/app.min.js"></script>
      <script src="js/customscript.js"></script>
      <script src="js/fetch-status.js"></script>

    </body>

</html>
