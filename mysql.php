<?php
session_start();
require_once 'classes/class.ldap.php';
require_once 'mysql/connect.php';
$user_home = new LDAP();
$messg= '';
/*if(!$user_home->is_logged_in())
{
  $user_home->redirect('login.php');
}

if($_SESSION["login"]["level"]<10)$user_home->redirect('404.php');
 */
if (isset($_POST["createdb"])){
  $user=$_POST["mysqluser"];
  $password=$_POST["pswd2"];
  $dbname=$_POST["dbname"];
  $mysql_start= new MysqlCreator();
  $createuser=$mysql_start->createUser($user,$password);
  $result=$createuser["result"];
  if ($result=='true'){

  //close previous connection
    try {
      //Create a nuew connection with the new created user;     
        $DBH = new PDO("mysql:host=localhost", $user,$password);
        $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }
    catch(PDOException $e) {
    echo $e->getMessage();
    }

    $newddbb=$DBH->exec("CREATE DATABASE $dbname;");
    $messg=(isset($newddbb))?'DDBB created': 'no se ha podido crear la base de datos';
  } else {
      $messg= 'no se ha podido crear el usurio';
  }

}
$current_page=basename(__FILE__);
$user_home->check_login_or_redirect($current_page);
require_once('header.php');
echo '<h3>'. sprintf(_("Crear base de datos")). '</h3>';
echo $messg;
?>
<div id="admin-content" class="content">
  <form id="mysql-create" action="" method="POST">
  <label for"dbname"><?php printf(_("Nombre de la base de datos"));?></label>
  <input type="text" name="dbname" id="dbname">
  <label for="mysqluser"><?php printf(_("Usuario de la base de datos"));?></label>
  <input type="text" name="mysqluser" id="mysqluser">
  <label for"pswd1"><?php printf(_("Contraseña de la base de datos"));?></label>
  <input type="password" name="pswd1" id="pswd1">
  <label for"pswd1"><?php printf(_("Confirma la contraseña de la base de datos"));?></label>
  <input type="password" name="pswd2" id="pswd2">
  <input type="submit" name="createdb" value="<?php printf(_('Crear base de datos'));?>">
</form>
<!--admin-content-->
<?php require_once('footer.php');?>
