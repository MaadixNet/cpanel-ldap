<?php
/**
 * Created by Maddish
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
$message="";

if($user_home->is_logged_in())
{
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
    $ldapconn=$user_home->connect();
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
    		echo '<form action="" method="POST" class="form-signin">
		<h2 class="form-signin-heading">Entrar</h2>
		<hr>
        	<label for="user">Username: </label><input id="user" type="text" name="user" />
        	<label for="password">Password: </label><input id="password" type="password" name="password" />        <input type="submit" name="submit" value="Submit" class="btn btn-large btn-primary" />
    		</form>';
}

?>
<?php require_once('header.php');
?>
<div class="cpntainer container" id="login">

<?php

echo $message;
print_form();
?>
	</div><!--container-->
	<?php include 'footer.php';?>
	</body>
</html>
