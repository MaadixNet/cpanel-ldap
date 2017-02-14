<?php
session_start();
require_once 'site-config.php';
require_once 'classes/class.ldap.php';
$Ldap = new LDAP();

$message=$msg1=$msg2="";
$ldapconn=$Ldap->connect();
if ($ldapconn){
  $psw=$Ldap->decrypt_psw();
  //$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
}
/* Get guid for goup web
$groupinfo = posix_getgrnam("web");
$grid=$groupinfo["gid"];
$users_group_tree = "cn=web,ou=groups,ou=People," . SUFFIX;
$users_in = $Ldap->search($ldapconn, $users_group_tree ,'(&(objectClass=*))');
var_dump($users_in);
   */
//check which services are enabled and set to enabled in ldap directory

$str = shell_exec('cat /etc/facter/facts.d/classifier.yaml | grep default_groups | cut -d "[" -f2 | cut -d "]" -f1');
$activated_services =   preg_replace('/\s+/', '', $str);
$serv_array = explode(',' , $activated_services);

$groups= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=*))');

for ($i=0; $i<$groups["count"]; $i++) {

  $name=$groups[$i]['ou'][0];
  if (in_array($name, $serv_array)) {
    $infog['status']= 'enabled';
  } else {
    $infog['status']= 'disabled';
  }
  $Ldap->modifyRecord($ldapconn, 'ou='.$name.','.LDAP_SERVICES, $infog);

}




if (isset($_POST["activate"])){
    $oldpsw = $_POST["oldpsw"];
    $pass1=$_POST['pswd1'];
    $pass2=$_POST['pswd2'];

    $pass3=$_POST['pswd4'];
    $pass4=$_POST['pswd4'];

    if ($oldpsw != $psw){
        $message="
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>×</button>"
        .sprintf(_("La contraseña actual no coincide con que has insertado"))." 
         </div>";
    }
    if($pass1==$pass2 && $psw==$_POST["oldpsw"] && $pass3==$pass4){
          #ldap admin user
          $modifydn=$_SESSION["login"]["dn"];
          $hashedpsw=ldap_password_hash($pass2,'ssha');
          $info['userpassword'][0]=$hashedpsw;
          $newemail=$_POST["usermail"];
          $info['email'][0]=$newemail;
          $info['status'][0] = 'active';
          # Sudo user
          $sudoname=$_POST["sudoname"];
          $modifysudodn='uid='. $sudoname . ',' . LDAP_PEOPLE;
          $sudohashedpsw=ldap_password_hash($pass4,'ssha');
          $sudoinfo['userpassword'][0]=$sudohashedpsw;
          $sudoinfo['authorizedservice'][0]='sshd';
          $sudoinfo['authorizedservice'][1]='apache';
          $change_sudo=$Ldap->modifyRecord($ldapconn, $modifysudodn, $sudoinfo);
          $message=$change_sudo["message"];
          if ($change_sudo ){
              $change_success=ldap_mod_replace($ldapconn, $modifydn, $info );
              if ($change_success) {
                $Ldap->logout();
                $Ldap->redirect('login.php?chpsw=ok');

              } else {
              $error="3";
              }
          }
      }
}

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

# Get ldap Manager data
$permissions=$_SESSION["login"]["level"];
$user=$_SESSION["login"]["username"];
$ldaptree    =  SUFFIX;
$filter="(&(objectClass=extensibleObject)(cn=$user))";
$rootuser=$Ldap->search($ldapconn,$ldaptree, $filter);
$rootusermail=$rootuser[0]["email"][0];
$rootusername = $rootuser[0]["cn"][0];
## Get sudo user data
$filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";
$ldapuserstree    = LDAP_PEOPLE;
$sudouser=$Ldap->search($ldapconn,$ldapuserstree, $filtersudo);
$sudousername = $sudouser[0]["uid"][0];

require_once('header.php');
require_once('sidebar.php');?>

<article class="content typography-page">
  <div class="title-block">
    <h3 class="title"> <?php printf(_("Activar Cpanel"));?></h3>
  </div>
  <section class="section">
      <div class="card card-info">
        <div class="card-header">
          <div class="header-block">
            <p class="title"><?php printf(_("Antes de poder empezar a utilizar el panel de control tienes que  personalizar tu cuenta. La contraseña que acabas de insertar para acceder a esta página es provisional y por razones de seguridad estás forzado a cambiarla."));?></p>
         </div><!--header-block-->
      </div><!--card-header-->
      <div class="card-block">
        <?php printf(_("<h5><strong class='pink'>NOTA IMPORTANTE:</strong> Tu servidor ha sido creado con dos usuarios que tienen altos privilegios en el sistema para poder administrar datos y procesos. Estos usuarios son:</h5>"));?>
        <hr>
        <?php printf(_("<h5 calss='text-left'><mark>%s</mark> <small class='pink'>Es el usuario con el que te acabas de indentificar y que tiene acceso al Cpanel. El Cpanel es el interfaz gráfico para gestionar tareas y otros usuarios del sistema.</small></h5>"),$rootusername);
        printf(_("<h5><mark>%s</mark> <small class='pink'>Este usuario no tiene acceso a través del Cpanel. Solo tiene acceso por ssh o sftp. Sin embargo este usuario tiene más privilegios que el anterior, ya que tiene acceso ilimitado al sistema. Este es tu usario root (SuperUsuario).</small></h5>"), $sudousername);?>
        <hr>
        <?php printf(_("<h5>Cambia las contraseñas de ambos usuarios, para que solamente tú las conozcas. Apunta en un lugar seguro tanto el nombre de usuario como la nueva contraseña para ambos. Una vez completado este proceso tendrás que volver a entrar al Cpanel  con las nuevas credenciales para el usuario <mark>%s</mark></h5>"), $rootusername);?>
        <p><?php printf(_(" Esta página solo se muestra la primera vez que accedes al Cpanel. Si quieres volver a cambiar algun parámetro de tu cuenta, lo podrás hacer en cualquier momento desde la página de 'Perfil' "));?></p>
        <hr>
      </div><!--card-block-->
    </div><!--card-info-->
  </section>

  <section class="section">
    <?php echo $message;?>
    <div class="card card-block">
    <?php printf(_("<h4>Usuario con acceso al Cpanel - <mark>%s</mark></h4>"),$rootusername); ?>

      <form action=''role=" form"  autocomplete='off' method='POST' class='jquery-check standard form'  id="activate">
        
      <div class="form-group"> 
        <label for="oldpsw"><h4><?php printf(_("Contraseña actual <small>La que acabas de insertar</small>"));?></h4></label>    
        <input class="form-control" id='oldpsw' type='password' name='oldpsw' required /><?php echo $msg1 ;?>
      </div>

      <div class="form-group">
        <label for="pswd1"><h4><?php printf(_("Nueva contraseña"));?></h4></label>
        <div id="pswcheck"></div>
        <input class="form-control" id='pswd1' type='password' name='pswd1' required /> <?php echo $msg2 ;?>
      </div>

      <div class="form-group">
        <label for="pswd2"><h4><?php printf(_("Repetir nueva contraseña"));?></h4></label><div id="pswresult"></div>
        <input class="form-control" id='pswd2' type='password' name='pswd2' required /> 
      </div>

      <div class="form-group">
        <label for='usermail'><h4><?php printf(_("Correo electrónico"));?></h4></label><div id="emailresult"></div>
        <p><?php printf(_("Averigua que el correo electrónico asociado a tu cuenta sea válido y que tengas acceso a él: si pierdes la contraseña solo podrás resetearla a través de este correo electrónico."),$user );?></p>
        <input id='usermail' class='usermail form-control' type='email' name='usermail' value="<?php echo $rootusermail;?>" required />
      </div>

     <?php printf(_("<h3>Usuario root con acceso ssh/sftp - <mark>%s</mark></h3>"),$sudousername); ?>   

      <div class="form-group">
      <label for="pswd3"><h4><?php printf(_("Nueva contraseña"));?></h4></label>
      <div id="pswchecksudo"></div>
      <input class="form-control" id='pswd3' type='password' name='pswd3' required /> <?php echo $msg2 ;?>
      </div>

      <div class="form-group">
        <label for="pswd4"><h4><?php printf(_("Repetir nueva contraseña"));?></h4></label><div id="pswresultsudo"></div>
        <input class="form-control" id='pswd4' type='password' name='pswd4' required /> 
        <input type="hidden" name="sudoname" value="<?php echo $sudousername;?>" />
        <div id="formresult"></div>
      </div>
      <hr>

      <input type='submit' class="btn btn-small btn-primary" name='activate' value='Cambiar contraseñas' class='btn btn-small btn-primary' />
      </form>
    </div><!--card-block-->
  </section>
</article>
<?php 
ldap_close($ldapconn);
require_once('footer.php');?>
