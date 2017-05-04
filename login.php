<?php
/**
 * Created by Maddish
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
$message=$permission="";
$url='index.php';
$ldapconn=$user_home->connect();
if($user_home->is_logged_in())
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

if ($permission==10 && $_SESSION["login"]["status"] != "active") {

    $url='activate.php';

}
  $user_home->redirect($url);
}


$result=(isset($_GET["chpsw"]))?$_GET["chpsw"]:'';
if ($result=='ok'){
      $message="
    <div class='alert alert-success'>
    <button class='close' data-dismiss='alert'>×</button><h4>"
    .sprintf(_("Cambio registrado con éxito")) . "<br>" . 
    sprintf(_("Utiliza la nueva contraseña para entrar"))." 
     </h4></div>";
}

if(isset($_POST['user']) && isset($_POST['password'])){
    $username = trim($_POST['user']);
    $password = $_POST['password'];

	
    if($user_home->login($username,$password))
    {  
     # Check for first login
     # if so force user to edit his profile: set email account and change password
      #
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

       if ($permission && $_SESSION["login"]["status"] != "active") {

            $user_home->redirect('activate.php');

        } else {

            $user_home->redirect($url);


        }

          
    } else {
     $message= "<div class='alert alert-error'>
            <button class='close' data-dismiss='alert'>&times;</button>".
            sprintf (_("Usuario o Contraseña no válido.")).
            "</div>";
    }
}
function print_form(){
    		echo '<form action="" method="POST" class="form-signin" style="display:none">
		<h2 class="form-signin-heading">Entrar</h2>
		<hr>
                <div class="form-group">
        	<label for="user">'. sprintf(_("Usuario")) . ' </label><input class="form-control underlined" id="user" type="text" name="user" />
                </div>
                <div class="form-group">
        	<label for="password">'. sprintf(_("Contraseña")) . '</label><input class="form-control underlined" id="password" type="password" name="password" />        <br><input type="submit" name="submit" value="Submit" class="btn btn-large btn-primary" />
                </div>
                <br>
                <a class="navbar-nav" href="recover.php">' . sprintf(_("He olvidado la contraseña")) . '</a>
    		</form>';
}

function updating(){
    		echo '<div class="updating" style="display:none">
  		        <h2 class="form-signin-heading">' . sprintf(_("Actualizando sistema..")) . '</h2>
		        <hr>
                        <div class="form-group">
        	          <span>' . sprintf(_("Esta operación tardará unos minutos, en cuanto el sistema esté actualizado aparecerá el formulario para hacer login")) . '</span>
                        </div>
    		      </div>';
}

?>
<!doctype html>
<html class="no-js" lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Login | Cpanel</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->
        <link rel="stylesheet" href="css/vendor.css">
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
                    <div class="auth-content">
<?php

echo $message;
print_form();
updating();
?>


                    </div>
                </div>
            </div>
        </div>
      <script src="js/vendor.js"></script>
      <script src="js/app.js"></script>
      <script src="js/customscript.js"></script>
      <script src="js/status.js"></script>

    </body>

</html>
