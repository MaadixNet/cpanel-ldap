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

              <article class="content error-404-page">
                    <section class="section">
                        <div class="error-card">
                            <div class="error-title-block">
                                <h1 class="error-title">404</h1>
                                <h2 class="error-sub-title"> Sorry, page not found </h2>
                            </div>
                            <div class="error-container">
                                <p>You better try our awesome search:</p>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div class="input-group"> <input type="text" class="form-control"> <span class="input-group-btn">
                      <button class="btn btn-primary" type="button">Search</button>
                    </span> </div>
                                    </div>
                                </div> <br> <a class="btn btn-primary" href="index.html"><i class="fa fa-angle-left"></i> Back to Dashboard</a> </div>
                        </div>
                    </section>
                </article>
<!--admin-content-->
<?php require_once('footer.php');?>
