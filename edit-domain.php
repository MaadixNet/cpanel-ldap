<?php 

session_start();
require_once 'classes/class.ldap.php';
//$Ldap= new LDAP();

//$current_page=basename(__FILE__);
//$Ldap->check_login_or_redirect($current_page);
require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

$domain=($_GET["domain"])?$_GET["domain"]:'';;
$binddn=LDAP_BASE;
$filter="(vd=". $domain . ")";

//$ldapconn=$Ldap->connect();
//$psw=$Ldap->decrypt_psw();
/*if ($ldapconn){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
	$permissions= $_SESSION["login"]["level"];
}
 */

//delete domain 
/* No deletion of domain available from this page*/
/*
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
 */
if(isset($_POST["update-domain"]) && (!empty($domain) ))

{
  
  $newwebmaster=trim($_POST["seladmin"]);  
  $old_webmaster=trim($_POST["old-webmaster"]);
  // Bollean not working for accontactive value. Only string is working. This is odd
  $mailstatus = (isset($_POST["mailactive"]))?'TRUE':'FALSE';
  $entry["adminid"]= $newwebmaster;
  $entry["accountactive"]= $mailstatus;

  $modifydomaindn='vd='. $domain. ',' . LDAP_BASE;     
  $webmaster_changed=$Ldap->modifyRecord($ldapconn,$modifydomaindn,$entry);
  $message=$webmaster_changed["message"];
  $dkimCheck = (isset($_POST["dkimactive"]))?1:0;
  $oldDkim=$_POST["old-dkim"];
  if($dkimCheck != $oldDkim) {
    // dkim has changed
    if (isset($_POST["dkimactive"])) {
      $Ldap->addDkimkey($ldapconn,$domain);
    } else {
      $Ldap->removeDkim($ldapconn,$domain);
    }
  }
}
//Query domains in database
if ($ldapbind) {
    $result=$Ldap->search($ldapconn,$binddn, $filter);
    //Get a list of all available usrs wu¡ith sftp access
    $filtersftp="(&(objectClass=person)(uid=*)(authorizedService=sshd))";
    $ldaptree    = LDAP_PEOPLE;
    $allsftpusers=$Ldap->search($ldapconn,$ldaptree, $filtersftp);
    $binddkim = 'ou=' . $domain . ',ou=opendkim,ou=cpanel,' . SUFFIX;
    $filterdkim = "(objectClass=organizationalUnit)";
    $has_dikm = $Ldap->search($ldapconn,$binddkim, $filterdkim);

}
/*
echo '<pre>';
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
      <form role="form"  autocomplete="off" id="up-domain" class="form-signin standard" method="POST" action="">
        <div class="form-group">
        <h5><?php printf(_("Cambiar Webmaster"));?></h5>
        <p><?php printf(_("Establece qué usuario tendrá permiso para editar los archivos del dominio %s"), $domain);?></p>

          <label class="control-label" for="webmaster"><?php printf(_("Webmaster (Administrador del sitio web)"));?></label> 
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
        </div>
        <?php $activemailstatus= $result[0]["accountactive"][0];
            if ($activemailstatus == 'TRUE'){
              $mailtitle = sprintf(_("Desactivar servidor de correo para este dominio"));
              $mailchecked = "checked='checked'";
              $mailmessage = sprintf(_("Desactiva esta casilla si quieres que el correo electrónico para este dominio sea gestionado por un servidor externo. Recuerda que el registro MX de los  DNS tendrá que apuntar al nombre del servidor externo"));
              $checkbox =  sprintf(_("Activado"));
             } else {
              $fqdn=trim(shell_exec('hostname -f'));
              $mailtitle = sprintf(_("Activar servidor de correo para este dominio"));
              $mailchecked = "";
              $mailmessage = sprintf(_("Activa esta casilla si quieres que el correo electrónico para este dominio sea gestionado por este servidor. Recuerda que el registro MX de los DNS tendrá que ser %s. Puedes averiguar cual es la configuración de DNS actual <a href='editdns.php?domain=" . $domain ."'>haciendo click aquí</a>."), $fqdn);
              $checkbox =  sprintf(_("Desactivado"));
            } 
            if ($has_dikm){
              $dkimtitle = sprintf(_("Desactivar DKIM para este dominio"));
              $dkimchecked = "checked='checked'";
              $dkimmessage = sprintf(_("Desactiva esta casilla si quieres desactivar la clave DKIM para este dominio"));
              $dkimcheckbox =  sprintf(_("Activado"));
              $old_dkim_value=1;
             } else {
              $fqdn=trim(shell_exec('hostname -f'));
              $dkimtitle = sprintf(_("Activar DKIM  para este dominio"));
              $dkimchecked = "";
              $dkimmessage = sprintf(_("Activa esta casilla si quieres activar la clave DKIM. Puedes averiguar cual es la configuración de DNS necesaria para usar DKIM <a href='editdns.php?domain=" . $domain ."'>haciendo click aquí</a>."));
              $dkimcheckbox =  sprintf(_("Desactivado"));
              $old_dkim_value=0;
            }
          
          ?> 
        <div class="form-group">
        <h5><?php echo $mailtitle;?></h5>
        <p><?php echo $mailmessage;?></p>
        <div> <label>
        <input name="mailactive" id="mailactive" class="checkbox" type="checkbox"  <?php echo $mailchecked;?>>
        <span><?php echo $checkbox ;?></span>
        </label> </div>

        <h5><?php echo $dkimtitle;?></h5>
        <p><?php echo $dkimmessage;?></p>
        <div> <label>
        <input name="dkimactive" id="dkimactive" class="checkbox" type="checkbox"  <?php echo $dkimchecked;?>>
        <span><?php echo $dkimcheckbox ;?></span>
        </label> </div>

        <input type="hidden" name="old-webmaster" value="<?php echo $curwebmaster;?>" />
        <input type="hidden" name="old-dkim" value="<?php echo $old_dkim_value;?>" />
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
  require_once('footer.php');
