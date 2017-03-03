<?php require_once 'site-config.php';
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
$Ldap= new LDAP();
if($Ldap->is_logged_in()){

  $ldapconn=$Ldap->connect();
  $psw=$Ldap->decrypt_psw();
  if ($ldapconn){
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw);
  }
  //Get all installed services to show or not in menu
  if ($ldapbind) {
    $serv_installed= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled))');
  }
}
$permissions= (isset($_SESSION["login"]["level"]))?$_SESSION["login"]["level"]:"";
//:w!$serv_installed = $Ldap->check_installed_service();
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
        <link rel="stylesheet" href="css/client.css">
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
                    <?php if( !empty($serv_installed) && array_search('owncloud', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                    <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/owncloud" class="btn btn-sm header-btn" target="_blank"> <i class="fa fa-cloud"></i> <span>Owncloud</span> </a>
                    <?php}
                     if( !empty($serv_installed) && array_search('rainloop', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>                       
                     <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/rainloop" class="btn btn-sm header-btn" target="_blank"> <i class="fa fa-envelope"></i> <span>Webmail</span> </a>
                    <?php }
                    if( !empty($serv_installed) && array_search('phpmyadmin', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                        <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/phpmyadmin" target="_blank" class="btn btn-sm header-btn"> <i class="fa fa-tasks"></i> <span>PhpMyAdmin</span> </a>
                    <?php } ?>
                    </div>
                    <div class="header-block header-block-nav">
                        <ul class="nav-profile">
                            <li class="notifications new">
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

