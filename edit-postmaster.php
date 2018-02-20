<?php 

session_start();
require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

$domain=($_GET["domain"])?$_GET["domain"]:'';;
$binddn=LDAP_BASE;
$filter="(vd=". $domain . ")";


if(isset($_POST["update-domain"]) && (!empty($domain) ))

{
  $psw1=$_POST['pswd1'];
  $psw2=$_POST['pswd2'];


  # Only change password if inputs fields are not empty and matches
  if ((!empty($psw1)) && (!empty($psw2)) && ($psw2==$psw1) ) {
    $newpass=ldap_password_hash($psw2, 'ssha');
    $modifypswdn='cn=postmaster,vd='.$domain.','.LDAP_BASE;
    $info['userpassword'][0] =ldap_password_hash($psw2,'ssha');
    $psw_changed=$Ldap->modifyRecord($ldapconn, $modifypswdn, $info );
    $message=$psw_changed["message"];
  }



}
//Query domains in database
if ($ldapbind) {
    $result=$Ldap->search($ldapconn,$binddn, $filter);
    //Get a list of all available usrs wu¡ith sftp access

}
/*echo '<pre>';
print_r($result);
echo '</pre>';
 */
require_once('sidebar.php');
?>
<article>
  <section>
    <div id="admin-content" class="content">
      <?php if($message) echo $message;?>
        <div class="title-block">
           <h3 class="title"> <?php printf(_("Editar usuario postmaster para el dominio %s"),$domain);?><span class="sparkline bar" data-type="bar"></span> </h3>
       </div> 
      
       <div class="card card-block">
            <form role="form"  autocomplete="off" id="up-domain" class="jquery-check standard form-signin" method="POST" action="">
              <div class="form-group">
                <label class="control-label" for="username"><?php printf(_("Nombre de usuario"));?></label>
                <h5><?php echo  "postmaster@". $domain;?></h5>
              </div>

              <div class="form-group">
                <label class="control-label" for="pswd1"><?php printf(_("Nueva contraseña"));?></label>
                <div id="pswcheck"></div>
                <input class="form-control boxed" autocomplete="off" readonly id="pswd1" type="password" name="pswd1" value="" size="4" />
              </div>
              <div class="form-group">
                <label class="control-label" or="pswd2"><?php printf(_("Repetir la nueva contraseña"));?></label>
                <input class="form-control boxed" id='pswd2' type='password' name='pswd2' value='' /><div id="pswresult"></div>
              </div>
              <hr>
              <input type='submit' name='update-domain' value='<?php printf(_("Guardar"))?>' class='btn btn-small btn-primary' />
            </form>
      </div>
    

  </div><!--admin-content-->
  </section>
</article>
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');
