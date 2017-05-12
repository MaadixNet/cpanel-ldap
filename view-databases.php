<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
require_once 'mysql/connect.php';
$mysql_start= new MysqlCreator();
$DBquery=$mysql_start->show_databases();
require_once('header.php');
$errorttpe="";

$message="";

$psw=$Ldap->decrypt_psw();
?>
<div id="admin-content" class="content">
	<?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">
	<div class="result"></div>

        <pre>
        <?php var_dump($DBquery);?>
        <?php echo 'result' . $result;?>
        </pre>

         </div><!--ineer-->

	</div><!--row-->
<?php 
?>
</div><!--admin-content-->
<?php 
	ldap_close($ldapconn);
	require_once('footer.php');
