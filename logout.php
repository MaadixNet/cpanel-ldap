<?php
session_start();
require_once 'classes/class.ldap.php';
$user = new LDAP();

if(!$user->is_logged_in())
{
	$user->redirect('index.php');
}

if($user->is_logged_in()!="")
{
	$user->logout();	
	$user->redirect('index.php');
}
?>
