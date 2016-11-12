<?php require_once 'site-config.php';?>
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
		<!--<link href="bootstrap/css/bootstrap.css" rel="stylesheet" media="screen">-->
        <link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet" media="screen">
        <link href="assets/styles.css" rel="stylesheet" media="screen">
        <!--<link href="css/custom.css" rel="stylesheet">-->
	<link href="css/client.css" rel="stylesheet">
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
	<link href='http://fonts.googleapis.com/css?family=Roboto+Condensed:400,300,700' rel='stylesheet' type='text/css'>
        <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
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
  						<a class="navbar-brand brand" href="/capnel">

						<img src="images/logo_maadix.png" tilte="Cpanel Home" alt="Cpanel" class"logo"/><h3>Cpanel</h3>
						</a>
				</div><!-- navbar-header-->
                   <?php $permissions= $_SESSION["login"]["level"];
                   if (isset($permissions)){ ?>
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
                                </ul>
                            </li>
                        </ul>
                        <ul class="nav">
                            <li>
							<a href="/<?php echo BASE_PATH;?>">Detalles</a>
                            </li>
							<?php if (is_dir('/var/www/html/ownclowd')){?>
                            <li>
                                <a href="https://<?php echo $_SERVER['HTTP_HOST'];?>/owncloud">Owncloud</a>
							</li>
							<?php }?>
							<?php if ($permissions == '10') {?>
							<li><a href="/<?php echo BASE_PATH;?>/edomains.php">Dominios </a></li>
							<?php } ?>

							<li><a href="/<?php echo BASE_PATH;?>/mails.php">Email</a></li>
                                                        <li><a href="/<?php echo BASE_PATH;?>/usuarios.php">Usuarios</a></li>

                            <?php if ($permissions >= '4') {?>
							<li><a href="/<?php echo BASE_PATH;?>/notificaciones.php">Notificaciones</a></li>
							<?php } ?>	
							<li><a href="/<?php echo BASE_PATH;?>/vpn.php">VPN</a></li>

                            <li class="dropdown">
                                <a href="#" data-toggle="dropdown" class="dropdown-toggle">Tutoriales <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu" id="menu1">
                                    <li><a href="#">Owncloud</a></li>
                                     <li><a href="#">Emails</a></li>
                                    <li><a href="#">Vpn</a></li>
                                    <li><a href="#">Dominios</a></li>
                                </ul>
                           </li>
			</ul>
			</div><!--nav-collapse-->
                    <?php } ?>
		</div><!--container-fluid-->
	</div><!--navbar inner-->
</div><!--navbar fixed-->
<div id="main" class="container-fluid">

