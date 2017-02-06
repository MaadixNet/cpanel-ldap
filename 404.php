<?php 

session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
if(!$user_home->is_logged_in())
{
	$user_home->redirect('login.php');
}

require_once('header.php');

?>
<div id="admin-content" class="content">
<h3>Oooooops! Página no encontrada.</h3>
</div><!--admin-content-->
<?php require_once('footer.php');?>

