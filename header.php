<?php require_once 'site-config.php';
//If cpanel is locked close session
$ds=ldap_connect("localhost");
if ($ds) {
   $sr=ldap_search($ds, "ou=cpanel," . SUFFIX, "ou=cpanel");
   $info = ldap_get_entries($ds, $sr);
   ldap_close($ds);
   if ($info[0]['status'][0]=='running' || $info[0]['status'][0]=='locked') { 
     session_destroy();
     header('Location: /cpanel');
   }
} else {
  session_destroy();
  header('Location: /cpanel');
}
//unset $info var for latest taemplates use
unset($info);


require_once 'classes/class.locale.php';
require_once 'classes/class.ldap.php';

/////////////////////////////////////////////////////////////////
//// Set language and locale
$locale = new CpanelLocale();

if (isset($_POST["language"]))
    $locale->change_language($_POST["language"]);
    setlocale(LC_ALL, $locale->get_language());
    bindtextdomain("messages", "locales");
    textdomain("messages");
    bind_textdomain_codeset("messages","UTF-8");

/////////////////////////////////////////////////////////////////
    //
$fqdn=trim(shell_exec('hostname -f'));
$Ldap= new LDAP();
//$current_page=basename(__FILE__);
$url=$_SERVER['REQUEST_URI'];
$tokens = explode('/', $url);
$current_page = $tokens[sizeof($tokens)-1];
if ($current_page !='activate.php') {
  $Ldap->check_login_or_redirect($current_page);
}
if($Ldap->is_logged_in()){

  $ldapconn=$Ldap->connect();
  $psw=$Ldap->decrypt_psw();
  if ($ldapconn){
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
  }
  //Get all installed services to show or not in menu
  if ($ldapbind) {
    $serv_installed= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled)(type=available))');
    /* Get data for notifications. 
     ** Available Notifications are:
     ** Reboot needed :  when ou=reboot,ou=cpanel,dc=example,dc=tld  attribute info=reboot
     ** Update available: when ou=cpanel,dc=example,dc=tld attrinute status=pending
    */
    // Notification bell string 
    $notification_count=0;
    $notification_string = '<i class="fa fa-bell-o"></i>';
    // Check if a reboot is needed
    $need_reboot = $Ldap->check_reboot_needed($ldapconn);
    // Check available updates 
    
    //$vm_status= getpuppetstatus($Ldap,$ldapconn,$ldapbind); 
    $vm_status=$Ldap->getpuppetstatus();
    $has_updates = $Ldap->check_available_updates();
  }
}
$permissions= (isset($_SESSION["login"]["level"]))?$_SESSION["login"]["level"]:"";
//$serv_installed = $Ldap->check_installed_service();
?>
<!doctype html>
<html class="no-js" lang="en"  ng-app="linuxDash">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title> Cpanel </title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" href="favicon.png">
        <!-- Place favicon.ico in the root directory -->
         <link rel="shortcut icon" href="favicon.ico">
        <link rel="stylesheet" href="css/vendor.css">
        <!-- Theme initialization -->
        <script src="js/styles.js"></script>
        <link rel="stylesheet" href="css/client.css?v=1.1">
    </head>

    <body>

        <div class="main-wrapper">
            <div class="app" id="app">
                <header class="header">
                    <div class="header-block header-block-collapse hidden-lg-up"> <button class="collapse-btn" id="sidebar-collapse-btn">
                        <i class="fa fa-bars"></i>
                </button> </div>
                    <div class="header-block header-block-search hidden-sm-down">
                    </div>
                    <div class="header-block header-block-buttons">
                    <?php if( !empty($serv_installed) && array_search('owncloud', array_column(array_column($serv_installed, 'ou'),0)) !== false){ ?>
                    <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/owncloud" class="btn btn-sm header-btn" target="_blank"> <i class="fa fa-cloud"></i> <span>Owncloud</span> </a>
                    <?php }
                     if( !empty($serv_installed) && array_search('rainloop', array_column(array_column($serv_installed, 'ou'),0)) !== false){ ?>                       
                     <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/rainloop" class="btn btn-sm header-btn" target="_blank"> <i class="fa fa-envelope"></i> <span>Webmail</span> </a>
                    <?php }
                    if( !empty($serv_installed) && array_search('phpmyadmin', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                        <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/phpmyadmin" target="_blank" class="btn btn-sm header-btn"> <i class="fa fa-tasks"></i> <span>PhpMyAdmin</span> </a>
                    <?php } ?>
                    </div>
                    <div class="header-block header-block-nav">
                        <ul class="nav-profile">
                            <li class="notifications new">
                              <?php echo  $Ldap->notifications_header_dropdown($ldapconn, $vm_status);?>
                            </li>
                            <li class="profile dropdown">
                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-user icon"></i> 
                                <span class="name"><?php echo $_SESSION["login"]["username"] ?></span> </a>
                                <div class="dropdown-menu profile-dropdown-menu" aria-labelledby="dropdownMenu1">
                                    <a class="dropdown-item" href="profile.php"> <i class="fa fa-user icon"></i> Profile </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="logout.php"> <i class="fa fa-power-off icon"></i> Logout </a>

                                </div>
                            </li>
                        </ul>
                    </div>
                </header>
