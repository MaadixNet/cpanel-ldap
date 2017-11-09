<?php
/**
 * Created by Maddish
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
require_once 'site-config.php';
$user_home = new LDAP();

if($user_home->is_logged_in())
{
        $user_home->redirect('index.php');
}
?>
<!doctype html>
<html class="no-js" lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title> ModularAdmin - Free Dashboard Theme | HTML Version </title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->
        <link rel="stylesheet" href="css/vendor.css">
        <!-- Theme initialization -->
        <script src="js/styles.js"></script>
    </head>
        <div class="auth">
            <div class="auth-container">
                <div class="card">
                    <header class="auth-header">
                        <h1 class="auth-title">
                         <?php printf(_("Recuperar contraseña"));?></h1>
                    </header>
                    <div class="auth-content">
<?php 

$code=md5(uniqid(rand(), true));
$token=getToken($length=32);

//Special connection Only read mode to rertieve cn and mail
$host = "ldapi:///";
$base = SUFFIX;

$ds = ldap_connect($host);

//buscamos cualquier entrada
$filter="(&(objectclass=extensibleObject)(!(cn=uidNext)))";
//de las entradas solo queremos cn y mail
$justthese = array("email", "cn");

//como usuario anonimo solo tenemos acceso al primer nivel de la base de
//datos, asi que solo tenemos acceso al dn de admin. y 
//a su atributo cn e email.

$sr=ldap_search($ds, $base, $filter, $justthese);
$info = ldap_get_entries($ds, $sr);
/*echo $info["count"]." entradas devueltas\n";
echo "<pre>";
print_r ($info);
echo "</pre>";
*/
$adminname = $info[0]["cn"][0];
$adminmail = $info[0]["email"][0];


if(isset($_POST['user']) && isset($_POST['usermail'])){
    $saniitised_input = sanitizeData($_POST);
    $username = $saniitised_input['user'][0]['value'];
    $to = $saniitised_input['usermail'][0]['value'];
    //Provisional : we stores admin username and email in site-config.php
    //Then we will probably create a mysql database	
    if ($username != $adminname || $to != $adminmail){
        $error=
        "<div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>×</button><h4>" .
        sprintf(_("Credenciales de administrador no válidas")) .  
        "</h4></div>";

    } elseif ( $username == $adminname && $to == $adminmail){
      
        //Generate 1 token for reset pswd url and one code to copy and paste as code

        $code=md5(uniqid(rand(), true));
        $token=getToken($length=32);
        $when=date("Y-m-d H:i:s"); 

        //For now we just use a file to save data. Maybe a mysql database is better
        // So we could also set a random name for this file ??

        $checkfile = fopen("/tmp/checkfile.txt", "w") or die("Unable to open file!");
        $txt=array('code' => $code, 'token' => $token, 'when' => $when, 'username' => $username);
        fwrite($checkfile, json_encode($txt));
        //fwrite($checkfile, $txt);
        fclose($checkfile); 
        $subject="Recuperar Contraseña";
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: no-reply' . "\r\n";
        $message = "
        <html>
        <head>
        <title>" . sprintf(_('Instrucciones para recuperar la contraseña')) ."</title>
        </head>
        <body>
        <p>" . sprintf(_('Alguien ha solicitado la recuperación de la contraseña de administrador para el  panel de control de %s .'),$_SERVER['HTTP_HOST']) . "</p>
        <p><b>" . sprintf(_('Código de verificación:')) . "</b><br>
        ". $code ."</p>
        <p>" . sprintf(_('Copia el código de verificación e insértalo en el campo correspondiente que encontrarás en el siguiente enlace:')) . "<br>
        <a href='http://".$_SERVER['HTTP_HOST']."/cpanel/reset.php?token=" . $token ."'>".$_SERVER['HTTP_HOST']."/cpanel/reset.php?token=" . $token ."</a></p>           
        
        </body>
        </html>
        ";
        $sendmail=mail($to, $subject, $message, $headers);
        if (!$sendmail){
          $error= "<div class='alert alert-error'><button class='close' data-dismiss='alert'>×</button>" . sprintf(_('¡Error! No se ha podido enviar el correo')) ."<div>";
        }

        else
        
        {
          $error='';
          $messagesent= "<div class='alert alert-success'><h4>" . sprintf (_("Se ha enviado un correo electrónico a la cuenta de correo %s . Por favor, revisa tu bandeja de entrada y sigue las instrucciones que encontrarás en el mensaje"), $to)." </div>";
        } 
    } else {
      
      $error=
       "<div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>×</button>" .
        sprintf(_("Error")) .   
        "</div>";
    }
}	

  if(isset($messagesent)) {
    echo $messagesent;
  } else {
  print_rec_form($error);
  }

function print_rec_form($error){
                if (isset($error)) echo $error;
    		echo '<form action="" method="POST" class="form-signin">
                <h5>' . sprintf(_("Para recuperar la contraseña de acceso al panel de control necesitas conocer el nombre de usuario y el correo electrónico asociado al mismo.")) .'</a></h5>
		<hr>
                <div class="form-group">
                <label for="user">' . sprintf(_("Usuario")) .': </label>
                <p class="little">' . sprintf(_("Inserta el nombre de usuario del administrador del panel de control")) . '</p>
                <input class="form-control " id="user" type="text" name="user" required />
                </div>
                <div class="form-group">
                <label for="usermail">Email: </label>
                <p class="little">' . sprintf(_("Inserta la cuenta de correo electrónico asociada al usuario administrador")) . '</p>
                <input class="form-control" id="usermail" class="usermail" type="mail" name="usermail" required />
                </div>
                <hr>
                <input type="submit" name="submit" value="'. sprintf(_("Submit")) .'" class="btn btn-large btn-primary" />
    		</form>';
}
function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
}

function getToken($length=32){
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for($i=0;$i<$length;$i++){
        $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
    }
    return $token;
}
?>
                    </div>
                </div>
                <div class="text-xs-center">
                <a href="/<?php echo BASE_PATH;?>/login.php" class="btn btn-secondary rounded btn-sm"> <i class="fa fa-arrow-left"></i> Back to login </a>
                </div>
            </div>
        </div>
        <!-- Reference block for JS -->
        <div class="ref" id="ref">
            <div class="color-primary"></div>
            <div class="chart">
                <div class="color-primary"></div>
                <div class="color-secondary"></div>
            </div>
        </div>
        <script src="js/vendor.js"></script>
        <script src="js/app.js"></script>
        <script src="js/customscript.js"></script>

  <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
  </body>
</html>
