<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

if(!$Ldap->is_logged_in())
{
  $Ldap->redirect('login.php');
}
$message='';
require_once('header.php');
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 
}
  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $permissions= $_SESSION["login"]["level"];

//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}

//Check if a custom mail has been set
if ($ldapbind) {
  $serv_avail= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=disabled)(type=available))');
}
// var_dump($serv_enabled);
    

// Get current language and swith url
if ($_SESSION["language"]=="en_GB"){ 
  $url = 'https://maadix.net/en/services-available.php';
} else {
  $url = 'https://maadix.net/services-available.php';  
}
    //echo file_get_contents($url);
?>


<div id="admin-content" class="content">
        <div class="col-sm-12">
            <div class="inner" id="maincol">
            <?php
            //echo "<h2>" . sprintf(_("Servicios disponibles")) . "</h2>";

            echo file_get_contents($url);
            //$num_serv=$serv_enabled["count"];
            /*for ($c=0; $c<$serv_avail["count"]; $c++) {
              $service=$serv_avail[$c]["ou"][0];
              $service_data=get_service_data($service);
              echo "<div class='row'>";
              echo "<div class='col-sm-2'>";
              echo "<div class='img service-img'><img src='" . $service_data['image'] . "' /></div>";
              echo "</div>";
              echo "<div class='col-sm-12'>";
              echo "<h3 class='title'>" . $service_data['title'] . "</h3>";
              echo "<div class='img service-img'><img src='" . $service_data['image'] . "' /></div>";
              echo "<h4>Software: " . $service_data['software'] ."</h4>";
              echo "<p>" . $service_data['description'] . "</p>";
              echo "</div>";//col-md-10
              echo "</div>";//row
              echo "<hr>";
                ;
            };*/
?>

        <br>
            </div><!--change-->

          </div><!--ineer-->
      </div><!--col-sm-8-->

  </div><!--row-->
</div><!--admin-content-->
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');?>


