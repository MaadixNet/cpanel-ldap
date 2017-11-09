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
require_once('header.php');?>

        <link rel="stylesheet" type="text/css" href="css/stats/main.css">
        <link rel="stylesheet" type="text/css" href="css/stats/animate.css">

        <script src="js/angular.min.js" type="text/javascript"></script>
        <script src="js/angular-route.js" type="text/javascript"></script>


<?php require_once('sidebar.php');
?>
<article class="content dashboard-page">
  <section class="section">
        <!-- Templates Get Rendered Here -->
        <div class="hero">
        <h4 class="title"><?php printf(_("Detalles del servidor"));?></h4>
            <nav-bar></nav-bar>
        </div>

        <!-- Templates Get Rendered Here -->
        <div 
            id="plugins"  
            class="animated fadeInDown" 
            ng-view>
        </div>
        <!-- Javascript-->
        <!-- Placed at the end of the document so the pages load faster -->


  </section>
</article>

<!--admin-content-->
<?php require_once('footer.php');?>
        <!-- Javascript-->
        <script src="js/linuxDash.js" type="text/javascript"></script>
        <script src="js/modules.js" type="text/javascript"></script>
        <script src="js/smoothie.min.js" type="text/javascript"></script>
