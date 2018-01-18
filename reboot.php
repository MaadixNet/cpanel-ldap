<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$message='';
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 

  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $permissions= $_SESSION["login"]["level"];
  if ($permissions >=10) {

    //Modifiy sender email account 
    if(isset($_POST['reboot'])){
      // set ou=reboot info to locked, so the local puppet will perform the reboot
      // info= ready means that the usaer has already performed a reboot,in case there was a pending reboot for some reason e.g. Kernel update)
      $entry=array();
      $entry["info"] ='ready'; 
      //status=locked means that the user wants to perform a reboot. The local puppet module reads this values to know in a reboot has to be done
      $entry["status"]='locked';
      $Ldap->modifyRecord($ldapconn,'ou=reboot,ou=cpanel,' . SUFFIX, $entry);
      //Update ou=cpanel object with lock status to avoid user login
      $modifydn='ou=cpanel,' . SUFFIX ;
      $info = array();
      $info['status']= 'locked';
      $lockcpanel=$Ldap->modifyRecord($ldapconn, $modifydn, $info );

      //Clear this sessions
      session_destroy();
      //Redirect to home
      header('Location: /cpanel');
    }
require_once('header.php');
require_once('sidebar.php');
 
    //$reboot_attr= $Ldap->search($ldapconn,'ou=reboot,ou=cpanel,' . SUFFIX ,'(objectClass=metaInfo)');

    ?>
    <article class="content forms-page">
      <div class="title-block">
        <h3 class="title"> <?php printf(_("Reiniciar el Servidor"));?></h3>
      </div>
      <section class="section">
        <div class="row">

          <div class="col-sm-12">
            <div class="inner"i id="maincol">
              <?php
              echo $message; 
              echo '<p>' . sprintf(_("En esta página puedes reinciar el servidor.")) . '<br>' .
                sprintf(_("El proceso de reincio tardará unos segundos durante los cuales los servicios no estarán disponibles")) . '<br>';?>
                <div class="title-block">
                   <?php
                     echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#rebootModal">' . sprintf (_('Reinicar')) . '</button>';
                   ?>
                </div>

            <?php 
           //end if domain not =  0?>
          </div><!--inner-->

        </div><!--col-sm-12-->
      </div><!--row-->
    </section>
  </article>
<div class="bd-example">
  <div class="modal fade" id="rebootModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"></h4>
        </div>
        <div class="modal-body" id="modal-body">
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

<?php } //End permission checks
  } //End if ldapbind
ldap_close($ldapconn);   
require_once('footer.php');
