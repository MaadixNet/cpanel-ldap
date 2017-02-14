<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

$domain=($_GET["domain"])?$_GET["domain"]:'';;
$binddn=LDAP_BASE;
$filter="(vd=". $domain . ")";

$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
	$permissions= $_SESSION["login"]["level"];
}


//delete domain 
if(isset($_POST['deldomain'])){
    //$ldapbind = $Ldap->bind($ldapconn, BINDDN , $_SESSION["login"]["password"]);
    $deletedn = 'vd='. $_POST['domainid']. ',' . LDAP_BASE;
    $deldomain = $Ldap->deleteRecord($ldapconn, $deletedn, $recursive = true);
	if ($deldomain){
       $message = "
        <div class='alert alert-success'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>" . sprintf(_("Dominio %s eliminado"), $domain_new ) . "</strong>
        </div>
        ";
    } else {
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>" . sprintf(_("Error")) . "</strong> 
        </div>
        ";
    }

}


if(isset($_POST["update-domain"]) && (!empty($domain) ))

{
  $psw1=$_POST['pswd1'];
  $psw2=$_POST['pswd2'];


  # Only change password if inputs fields are not empty and matches
  if ((!empty($psw1)) && (!empty($psw2)) && ($psw2==$psw1) ) {
    $newpass=ldap_password_hash($psw2);
    $modifypswdn='cn=postmaster,vd='.$domain.','.LDAP_BASE;
    $info['userpassword'][0] =ldap_password_hash($psw2,'ssha');
    $psw_changed=$Ldap->modifyRecord($ldapconn, $modifypswdn, $info );
    $message=$psw_changed["message"];
  }

  $newwebmaster=trim($_POST["seladmin"]);  
  $old_webmaster=trim($_POST["old-webmaster"]);

  if ($newwebmaster != $old_webmaster){
    $entry["adminid"]=$newwebmaster;
    $modifydomaindn='vd='. $domain. ',' . LDAP_BASE;     
    $webmaster_changed=$Ldap->modifyRecord($ldapconn,$modifydomaindn,$entry);
    $message=$webmaster_changed["message"];
  }

  if (($newwebmaster == $old_webmaster) && (empty($psw1)) && (empty($psw2)) ){
      $message= "
      <div class='alert alert-info'>
      <button class='close' data-dismiss='alert'>&times;</button>
      <strong>" . sprintf(_('No se ha aplicado ningún cambio. Los datos insertados son los mismos que la configuración anterior')) . "</strong> 
        </div>";
  }
}
//Query domains in database
if ($ldapbind) {
    $result=$Ldap->search($ldapconn,$binddn, $filter);
    //Get a list of all available usrs wu¡ith sftp access
    $filtersftp="(&(objectClass=person)(uid=*)(authorizedService=sshd))";
    $ldaptree    = LDAP_PEOPLE;
    $allsftpusers=$Ldap->search($ldapconn,$ldaptree, $filtersftp);

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
       <h3 class="title"> <?php printf(_("Editar dominio %s"),$domain);?><span class="sparkline bar" data-type="bar"></span> </h3>
    </div> 
    <div class="card card-block">
    <form role="form"  autocomplete="off" id="up-domain" class="jquery-check standard form-signin" method="POST" action="">
    <div class="form-group">
      <label class="control-label" for="pswd1"><?php printf(_("Nueva contraseña"));?></label>
      <div id="pswcheck"></div>
      <input class="form-control boxed" autocomplete="off" readonly id="pswd1" type="password" name="pswd1" value="" size="4" />
    </div>
    <div class="form-group">
      <label class="control-label" or="pswd2"><?php printf(_("Repetir nueva contraseña"));?></label>
      <input class="form-control boxed" id='pswd2' type='password' name='pswd2' value='' /><div id="pswresult"></div>
    </div>
    <div class="form-group">
    <label class="control-label" for="webmaster"><?php printf(_("Webmaster (Administrador sito web)"));?></label> 
    <?php $curwebmaster=$result[0]["adminid"][0];?>
    <select id='seladmin' name='seladmin'>";
        <?php for ($c=0; $c<$allsftpusers["count"]; $c++) {
          $username = $allsftpusers[$c]["uid"][0];
          $grid=  $allsftpusers[$c]["gidnumber"][0];
          $issudo = ($grid == '27')? sprintf(_(" - superusuario")):"";
          # Show all sftpusers in a drop down list and set current webmaste as selected
          $selected = ($username == $curwebmaster) ? "selected='selected'":'';
          echo "<option " . $selected . " value='" . $username . "'>" . $username . $issudo . "</option>";
        }
        echo "</select>";?>
    <input type="hidden" name="old-webmaster" value="<?php echo $curwebmaster;?>" />
    </div>
    <hr>
    <input type='submit' name='update-domain' value='Guardar' class='btn btn-small btn-primary' />
    </form>
    </div>
    
  </div><!--ineer-->

<!-- Modal -->
  <div class="bd-example">
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="exampleModalLabel"></h4>
          </div>
          <div class="modal-body" id="modal-body">
          </div>
        </div><!--modal-content-->
      </div><!--modal-dialog-->
    </div><!--exampleModal-->
  </div><!--bd-example-->
  </div><!--row-->
  <?php
  ?>
  </div><!--admin-content-->
  </section>
</article>
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');?>

