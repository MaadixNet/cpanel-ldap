<?php
/**
 * Created by Maddish
 */
// Start PHP session
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();

if($user_home->is_logged_in())
{
        $user_home->redirect('index.php');
}
?>
<!DOCTYPE html>
<html class="no-js">

    <head>
                <meta charset="utf-8">
                        <meta http-equiv="X-UA-Compatible" content="IE=edge">
                        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
                        <meta name="description" content="A Bootstrap based app landing page template">
                        <meta name="author" content="">
						<meta name="robots" content="noindex">
                        <link rel="shortcut icon" href="assets/ico/favicon.ico">

        <title>Login in</title>
        <!-- Bootstrap -->
        <!--<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">-->
        <link href="bootstrap/css/bootstrap.css" rel="stylesheet" media="screen">
        <link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
        <link href="assets/styles.css" rel="stylesheet" media="screen">
        <link href="css/custom.css" rel="stylesheet">
        <link href="css/client.css" rel="stylesheet">
        <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

    </head>

    <body>
	<div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container-fluid">
                    <a class="navbar-brand brand" href="/">
					<img class"logo"="" alt="Cpanel" tilte="Cpanel Home" src="images/logo_maadix.png">
					<h3>Cpanel</h3>
                    </a>
                </div><!--container-fluid-->
        </div><!--navbar inner-->
	</div><!--navbar fixed-->
	<div class="cpntainer container" id="login">

<?php 
if(isset($_POST['username']) && isset($_POST['password'])){
	$user_home->connect();
    $username = $_POST['username'];
    $password = $_POST['password'];

	
	if($user_home->login($username,$password))
	{
		$user_home->redirect('index.php');
	} else {
	echo "<div class='alert alert-error'>
		<button class='close' data-dismiss='alert'>&times;</button>
		Error de autentificación'
		</div>";
	print_form();
	}
} else {	
 	print_form();
}

function print_form(){
    		echo '<form action="#" method="POST" class="form-signin">
		<h2 class="form-signin-heading">Entrar</h2>
		<hr>
        	<label for="username">Username: </label><input id="username" type="text" name="username" />
        	<label for="password">Password: </label><input id="password" type="password" name="password" />        <input type="submit" name="submit" value="Submit" class="btn btn-large btn-primary" />
    		</form>';
}
?>
	</div><!--container-->
	<?php include 'footer.php';?>
	</body>
</html>
