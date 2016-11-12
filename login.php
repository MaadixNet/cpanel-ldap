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
require_once('header.php');
?>
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
