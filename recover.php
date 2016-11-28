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
require_once('header.php');
?>
<div class="cpntainer container-fluid" id="login">

<?php 

$code=md5(uniqid(rand(), true));
$token=getToken($length=32);


if(isset($_POST['username']) && isset($_POST['mail'])){
    $username = $_POST['username'];
    $to = $_POST['mail'];
    //Provisional : we stores admin username and email in site-config.php
    //Then we will probably create a mysql database	
    if ($username !=ADMIN || $to!=ADMINEMAIL){
        $error=
        "<div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>×</button>
        Credenciales de administrador inválidas 
        </div>";

    } elseif ( $username==ADMIN && $to==ADMINEMAIL){
      
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
        $message = "
        <html>
        <head>
        <title>Instrucciones para recuperar contraseña del Cpanel</title>
        </head>
        <body>
        <p>Alguien ha solicitado la recuperación de la contraseña de administrador para  Panel de Control de " .$_SERVER['HTTP_HOST']. ".</p>
        <p><b>Código de verificación:</b><br>
        ". $code ."</p>
        <p>Copia el código de verificación e insertalo en el campo correspondiente que encontrarás en el siguiente enlace:<br>
        <a href='http://".$_SERVER['HTTP_HOST']."/cpanel/reset.php?token=" . $token ."'>".$_SERVER['HTTP_HOST']."/cpanel/reset.php?token=" . $token ."</a></p>           
        
        </body>
        </html>
        ";
        $sendmail=mail($to, $subject, $message, $headers);
        if (!$sendmail){
          $error= "<div class='alert alert-error'><button class='close' data-dismiss='alert'>×</button>Error! No se ha podido enviar el mail<div>";
        }

        else
        
        {
          echo "<div class='alert alert-success'><h4>Se ha enviado un correo electrónico a la cuenta de correo que has especificado. Por favor, revisa tu bandeja ded entrada y sigue las instruccones que encontrarás en el mensaje</div>";
        } 
    }

}	
  print_rec_form($error);


echo ADMIN;
function print_rec_form($error){
                if (isset($error)) echo $error;
    		echo '<form action="#" method="POST" class="form-signin">
		<h2 class="form-signin-heading">Recuperar contraseña</h2>
                <h5>Para recuperar la contraseña de acceso al Cpanel necesitas conocer el nombre de usuario y el correo electrónico asociado al mismo. Si has olvidado estos datos, y no los has cambiado, los encontrarás en tu <a href="https://maadix.net/client-area/" target="_blank">Área Cliente</a></h5>
		<hr>

                <label for="username">Usuario: </label>
                <p class="little">Inserta el nombre de usuario del administrador del Cpanel</p>
                <input id="username" type="text" name="username" />
                <label for="mail">Email: </label>
                <p class="little">Inserta la cuenta de correo electrónico asociada al usuario administrador</p>
                <input id="mail" type="mail" name="mail" />
                <input type="submit" name="submit" value="Submit" class="btn btn-large btn-primary" />
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
	</div><!--container-->
	<?php include 'footer.php';?>
	</body>
</html>
