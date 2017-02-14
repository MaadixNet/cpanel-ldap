<?php
session_start();
require_once 'classes/class.ldap.php';
$user_home = new LDAP();
/*if(!$user_home->is_logged_in())
{
  $user_home->redirect('login.php');
}

if($_SESSION["login"]["level"]<10)$user_home->redirect('404.php');
 */
$current_page=basename(__FILE__);
$user_home->check_login_or_redirect($current_page);
require_once 'classes/class.DiskStatus.php';
try {
  $diskStatus = new DiskStatus('/');
  $freeSpace = $diskStatus->freeSpace();
  $totalSpace = $diskStatus->totalSpace();
  $barWidth = ($diskStatus->usedSpace()/100) * 300;//300 is the width of the bar in pxx
} catch (Exception $e) {
  echo 'Error ('.$e->getMessage().')';
  exit();
}
require_once('header.php');
require_once('sidebar.php');
?>
<div id="admin-content" class="content">
<?php
echo '<h3>'. sprintf(_("Datos del servidor")). '</h3>';
echo '<p><b>IP:</b> ' . $_SERVER['SERVER_ADDR'] . '</p>';
echo '<p><b>'. sprintf(_("Sistema Operativo")). ':</b> ' . php_uname() . ' ' . PHP_OS . '</p>';;
$data = shell_exec('uptime');
$uptime = explode(' up ', $data);
$uptime = explode(',', $uptime[1]);
$uptime = $uptime[0].', '.$uptime[1];
echo '<p><b>'. sprintf(_("Tiempo de actividad del servidor")). ':</b> '.$uptime .'</p>';
echo '<p><b>'. sprintf(_("Espacio en disco")). ':</b></p>';
echo '<div class="disk">';
echo '<div id="disk-used" class="used" data-width="' . round($barWidth) .'px">'. $diskStatus->usedSpace() . '%&nbsp;</div>';
echo '</div>';
echo sprintf(_("Libres")).': ' .$freeSpace .' ('.sprintf(_("de")). $totalSpace . ' )</div>';
?>
<!--admin-content-->
<?php require_once('footer.php');?>
