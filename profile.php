<?php
session_start();
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$message=$msg1=$msg2="";
$ldapconn=$Ldap->connect();
if ($ldapconn){
  $psw=$Ldap->decrypt_psw();

  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
$redirect=false;
if (isset($_POST["activate"])){
  $oldpsw = $_POST["oldpsw"];

  if ($oldpsw != $psw){
    $message="
    <div class='alert alert-error'>
    <button class='close' data-dismiss='alert'>×</button>"
    .sprintf(_("La contraseña actual no coincide con que has insertado"))." 
     </div>";
  }
    $pass1=$_POST['pswd1'];
    $pass2=$_POST['pswd2'];
  if($psw==$_POST["oldpsw"]){
        $modifydn=$_SESSION["login"]["dn"];
        if (!empty($pass2) && $pass1==$pass2) {
          $hashedpsw=ldap_password_hash($pass2,'ssha');
          $info['userpassword'][0]=$hashedpsw;
          $redirect=true;
        }
        $sanitised_data = sanitizeData(array("usermail" => $_POST["usermail"]));
        $newemail=$sanitised_data["usermail"][0]['value'];
        $info['email'][0]=$newemail;
        $info['status'][0] = 'active';
        $change_success=ldap_mod_replace($ldapconn, $modifydn, $info );
        if ($change_success && $redirect===true) {
          $Ldap->logout();
          $Ldap->redirect('login.php?chpsw=ok');
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


require_once('header.php');
require_once('sidebar.php');?>

<article class="content forms-page">
    <div class="title-block">
    <h3 class="title"> <?php printf(_("Editar perfil"));?></h3>
        <p class="title-description"> </p>
    </div>
    <section class="section">
        <div class="row">
            <div class="card card-block ">
                <?php echo $message;?>
                <form role="form" action='' autocomplete='off' method='POST' class='form standard jquery-check' id="activate">

                      <div class="form-group">
                      <label for="oldpsw"><?php printf(_("Contraseña actual"));?></label>
                      <input class="form-control" id='oldpsw' type='password' name='oldpsw' required readonly><?php echo $msg1 ;?>
                      </div>

                      <div class="form-group">
                      <label for="pswd1"><?php printf(_("Nueva contraseña"));?></label>
                      <div id="pswcheck"></div>
                      <input readonly class="form-control" id='pswd1' type='password' name='pswd1'  /> <?php echo $msg2 ;?>
                      </div>

                      <div class="form-group">
                      <label for="pswd2"><?php printf(_("Repetir nueva contraseña"));?></label><div id="pswresult"></div>
                      <input class="form-control" id='pswd2' type='password' name='pswd2' />
                      </div>

                      <div class="form-group">
                      <label for='usermail'><?php printf(_("Correo electrónico"));?></label><div id="emailresult"></div>
                      <div id="emailresult"><?php if(isset($sanitised_data['usermail'][0]['message'])) echo '<span class="alert-danger">' . $sanitised_data['usermail'][0]['message'] . '</span>';?></div>
                      <p><?php printf(_("Averigua que el correo electrónico asociado a tu cuenta sea válido y que tengas acceso a él: si pierdes la contraseña solo podrás resetearla a través de este correo electrónico."),$user );?></p>
                      <input id='usermail' class='usermail form-control' type='email' name='usermail' value="<?php echo $rootusermail;?>" required />
                      </div>
                      <div id="formresult"></div>
                    <hr>

                    <input type='submit' class="btn btn-small btn-primary" name='activate' value='<?php printf(_("Guardar cambios"));?>' class='btn btn-small btn-primary' />
                </form>
              </div>
      </div>
  </section>
</article>


<?php 
ldap_close($ldapconn);
require_once('footer.php');
