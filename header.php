<?php require_once 'site-config.php';
require_once 'classes/class.locale.php';
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
?>
<!DOCTYPE html>
<html class="no-js">
    
    <head>
    		<meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width">
                <meta name="description" content="A Bootstrap based app landing page template">
                <meta name="author" content="">
                <meta name="robots" content="noindex">
                <link rel="shortcut icon" href="assets/ico/favicon.ico">

        <title>Panel de control</title>
        <!-- Bootstrap -->
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet" media="screen">
        <link href="assets/styles.css" rel="stylesheet" media="screen">
	<link href="css/client.css" rel="stylesheet">
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
	<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,300,700' rel='stylesheet' type='text/css'>
        <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
            <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        
    </head>
    
    <body>
    <div id="wrap">
        <div class="navbar">
            <div class="navbar-inner">
                <div class="container-fluid">
                    <div class="navbar-header">
                          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span>
                           <span class="icon-bar"></span>
                           <span class="icon-bar"></span>
                          </a>
                          <a class="navbar-brand brand" href="/cpanel">

                            <img src="images/logo_maadix.png" tilte="Cpanel Home" alt="Cpanel" class"logo"/><h3>Cpanel</h3>
                          </a>

                    </div><!-- navbar-header-->
                   <?php $permissions= isset($_SESSION["login"]["level"])?$_SESSION["login"]["level"]:"";
                   if (!empty($permissions)){ ?>
                    <div class="nav-collapse collapse">
                        <ul class="nav pull-right">
                            <li class="dropdown">
                                <a href="#" role="button" class="dropdown-toggle" data-toggle="dropdown"> <i class="icon-user"></i> 
                                    <?php echo $_SESSION["login"]["username"] ?> <i class="caret"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a tabindex="-1" href="logout.php">Logout</a>
                                    </li>
                                    <li>
                                        <a tabindex="-1" href="profile.php">Perfil</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                        <?php if(isset($_SESSION["login"]["status"]) && $_SESSION["login"]["status"] == "active"){ ?>
                        <ul class="nav">
                            <li>
                              <a href="/<?php echo BASE_PATH;?>"><?php printf(_("Detalles"));?></a>
                            </li>

                            <?php if (is_dir('/var/www/html/ownclowd')){?>
                              <li>
                                <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/owncloud"><?php printf(_("Owncloud"));?></a>
                              </li>
                            <?php }?>

                            <?php if ($permissions == '10') {?>
                            <li class="dropdown">
                                <a href="#" data-toggle="dropdown" class="dropdown-toggle"><?php printf (_("Dominios"));?><b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu" id="menu0">
                                  <li><a href="/<?php echo BASE_PATH;?>/view-domains.php"><?php printf(_("Ver Dominios"));?></a></li>
                                  <li><a href="/<?php echo BASE_PATH;?>/add-domain.php"><?php printf(_("Añadir Dominio"));?></a></li>
                                </ul>
                              </li>                        

                            <?php } ?>

                            <li><a href="/<?php echo BASE_PATH;?>/mails.php"><?php printf(_("Email"));?></a></li>
                            <li><a href="/<?php echo BASE_PATH;?>/usuarios.php"><?php printf(_("Usuarios"));?></a></li>

                            <?php if ($permissions >= '4') {?>
                            <li><a href="/<?php echo BASE_PATH;?>/notificaciones.php"><?php printf(_("Notificaciones"));?></a></li>
                            <?php } ?>	

                            <li class="dropdown">
                                <a href="#" data-toggle="dropdown" class="dropdown-toggle">Tutoriales <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu" id="menu1">
                                <li><a href="#" target="_blank"><?php printf(_("Owncloud"));?></a></li>
                                <li><a href="http://docs.maadix.net/dominios/" target="_blank"><?php printf(_("Dominios"));?></a></li>
                                <li><a href="http://docs.maadix.net/email" target="_blank"><?php printf(_("Email"));?></a></li>
                                <li><a href="http://docs.maadix.net/vpn/" target="_blank"><?php printf(_("VPN"));?></a></li>
                                </ul>
                           </li>
                        </ul>
                      <?php } ?>
			</div><!--nav-collapse-->
                    <?php } ?>
		</div><!--container-fluid-->
	</div><!--navbar inner-->
</div><!--navbar fixed-->
<div id="main" class="container-fluid">
