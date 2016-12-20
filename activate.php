<?php
session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

/* Custom login behaviour only for this page
** If account has already been activated and user is logged in 
** Redirect to index.php
** Else if not logged in redirect to login
** Else show this page
*/
          if (isset($_SESSION["login"]["dn"]) && isset( $_SESSION["login"]["status"]) && $_SESSION["login"]["status"] == "active")
          {

              $Ldap->redirect('index.php');;
    
          }
    
           elseif (!isset($_SESSION["login"]["dn"]))

          {
    
              $Ldap->redirect('login.php');

          }

$message="";
$ldapconn=$Ldap->connect();
if ($ldapconn){
  $psw=$Ldap->decrypt_psw();
  //$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
if (isset($_POST["activate"])){
  $oldpsw = $_POST["oldpsw"];
  $pass1=$_POST['pswd1'];
  $pass2=$_POST['pswd2'];
  
  if ($oldpsw != $psw){
    $message="
    <div class='alert alert-error'>
    <button class='close' data-dismiss='alert'>×</button>"
    .sprintf(_("La contraseña actual no coincide con que has insertado"))." 
     </div>"; 
  }
  if($pass1==$pass2 && $psw==$_POST["oldpsw"]){
        $modifydn=$_SESSION["login"]["dn"];
        $hashedpsw=ldap_password_hash($pass2,'ssha');
        $info['userpassword'][0]=$hashedpsw;
        $newemail=$_POST["usermail"];
        $info['email'][0]=$newemail;
        $info['status'][0] = 'active';
        $change_success=ldap_mod_replace($ldapconn, $modifydn, $info );
        if ($change_success) {
          $Ldap->logout();
          $Ldap->redirect('newlogin.php');
        } else {
          $error="3";
        }
  }
}
# Get Manager user data
$permissions=$_SESSION["login"]["level"];
$user=$_SESSION["login"]["username"];
$ldaptree    =  SUFFIX;
$filter="(&(objectClass=extensibleObject)(cn=$user))";
$rootuser=$Ldap->search($ldapconn,$ldaptree, $filter);
$rootusermail=$rootuser[0]["email"][0];



require_once('header.php');?>
<div id="admin-content" class="content">
    <h2><?php printf(_("Activar Cpanel"));?></h2>
    <h3><?php printf(_("Antes de poder empezar a utilizar el panel de control tienes que  personalizar tu cuenta. La contraseña que acabas de insertar para acceder a esta página es provisional y por razones de seguridad estás forzado a cambiarla."));?></h3>

    <hr>
    <h4><?php printf(_(" Esta página solo se muestra la primera vez que accedes al Cpanel. Si quieres volver a cambiar algun parámetro de tu cuenta, lo podrás hacer en cualquier momento desde la página de 'Perfil' "));?></h4>
    <hr>
    <?php echo $message;?>
    <?php echo 'login level: ' . $_SESSION["login"]["level"];?>
<form action='' autocomplete='off' method='POST' class='jquery-check form'  id="activate">
    
      <label for="oldpsw"><h4><?php printf(_("Contraseña actual"));?></h4></label>    
      <input id='oldpsw' type='password' name='oldpsw' required /><?php echo $msg1 ;?>

      <label for="pswd1"><h4><?php printf(_("Nueva contraseña"));?></h4></label>
      <input id='pswd1' type='password' name='pswd1' required /> <?php echo $msg2 ;?>

      <label for="pswd2"><h4><?php printf(_("Repetir nueva contraseña"));?></h4></label><div id="pswresult"></div>
      <input id='pswd2' type='password' name='pswd2' required /> 

      <label for='usermail'><h4><?php printf(_("Correo electrónico"));?></h4></label><div id="emailresult"></div>
      <p><?php printf(_("Averigua que el correo electrónico asociado a tu cuenta sea válido y que tengas acceso a él: si pierdes la contraseña solo podrás resetearla a través de este correo electrónico."),$user );?></p>
      <input id='usermail' class='usermail' type='email' name='usermail' value="<?php echo $rootusermail;?>" required />

      <div id="formresult"></div>
      <hr>

      <input type='submit' class="btn btn-small btn-primary" name='activate' value='Cambiar contraseña' class='btn btn-small btn-primary' />
      </form>
</div>

<?php 
ldap_close($ldapconn);
require_once('footer.php');?>
