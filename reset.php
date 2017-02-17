<?php
/**
 * reset.php
 * Created by Maddish
 *
 * Show the form to collect new password
 * call proc/reset-ps.php
 * that performs data validation:
 * checks username, email, token and code
 *
 * Returns error if there are some
 *
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
if($user_home->is_logged_in())
{
        $user_home->redirect('index.php');
}
$urltoken=isset($_GET['token'])?$_GET['token']:'';
$error=isset($_GET["error"])?$_GET["error"]:'';
$msg1=$msg2=$msg3=$msg="";
switch($error){
  case "1":
    $msg1 ="
    <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i> Las dos  
contraseñas no coincide con que has insertado</span>";
  break;
  case "2":
    $msg2="
    <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i>Nombre de usuario no válido
  </span>";
    break;
  case "3":
    $msg3="
  <span class='error'><i class='fa fa-exclamation-triangle icon checkko alert-danger'></i>Código de verificación no válido</span>";
    break;
  case "4":
    $msg="
    <div class='alert alert-error'>
    <button class='close' data-dismiss='alert'>×</button>
    Código de verificación caducado 
    </div>";
    break;
  case "5":
    $msg="
    <div class='alert alert-error'>
    <button class='close' data-dismiss='alert'>×</button>
    Ha habido un error. No se ha podido restablecer la contraseña. 
    </div>";
    break;
  case "":
    $msg1=$msg2=$msg3=$msg="";
    break;
  default:
}

?>
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
                     <?php printf(_("Restablecer contraseña"));?></h1>
                </header>
                <div class="auth-content">



                <div class="container-fluid" id="login">

<?php 
                echo $msg;
    		echo '<form role="form" action="proc/reset-ps.php" method="POST" class="form-signin standard jquery-check">
		<h2 class="form-signin-heading">Introduce los datos</h2>
		<hr>
                <div class="form-group">
              <label for="user">Nombre de usuario: </label><input class="form-control" id="user" type="text" name="user" required/><p> ' . $msg2 .'</p>
                </div>

                <div class="form-group">
                <label for="usercode">Código de verificación: </label><input class="form-control" id="usercode" type="text" name="usercode" required/><p> ' . $msg3 .'</p>
                </div>

                <div class="form-group">
                <label for="pswd1">Nueva contraseña: </label><input class="form-control" id="pswd1" type="password" name="pswd1" required/>
                <label for="pswd2">Repetir contraseña: </label><input class="form-control"  id="pswd2" type="password" name="pswd2" required/><p> ' . $msg1 .'</p><div id="pswresult"></div>
                </div>
                <input type="hidden" name="token" value="'.$urltoken.'" />
                <input type="submit" name="submit" value="Submit" class="btn btn-large btn-primary" />


    		</form>';
?>
                </div>
              </div>
            </div>
          </div>
	</div><!--container-->
    </body>
</html>
