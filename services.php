<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$message='';
require_once('header.php');
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 
}
  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $permissions= $_SESSION["login"]["level"];

//Gett all installed services
if ($ldapbind) {
  $serv_enabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled))');
}

/*echo '<pre>';
var_dump($serv_enabled);
echo '</pre>';
 */
require_once('sidebar.php');

?>
<article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Aplicaciones Instaladas"));?> </h3>
            </div>
                 <section class="section">
                        <div class="row ">
                       <?php for ($c=0; $c<$serv_enabled["count"]; $c++) {
                          $service=$serv_enabled[$c]["ou"][0];
                          $service_data=get_service_data($service);
                          if ( $c % 3 == 0 ){; ?>
                           <div class="clearfix visible-xs"></div>
                          <?php }
                          ?>
                            <div class="col-xl-4">
                                <div class="card ">
                                    <div class="card-block">
                                        <!-- Nav tabs -->
                                        <div class="card-title-block">
                                            <h3 class="title"><?php echo  $service_data['title'];?></h3>
                                        </div>
                                        <ul class="nav nav-tabs nav-tabs-bordered">
                                            <li class="nav-item"> <a href="#home-<?php echo $c;?>" class="nav-link active" data-target="#home-<?php echo $c;?>" data-toggle="tab" aria-controls="home-<?php echo $c;?>" role="tab">App</a> </li>
                                            <li class="nav-item"> <a href="#desc-<?php echo $c;?>" class="nav-link" data-target="#desc-<?php echo $c;?>" aria-controls="desc-<?php echo $c;?>" data-toggle="tab" role="tab"><?php printf(_("Descripción"));?></a> </li>
 
                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabs-bordered">
                                            <div class="tab-pane fade in active" id="home-<?php echo $c;?>">
                                                <h4></h4>
                                                <div class="row">
                                                  <div class="col-md-6">
                                                    <p><div class='img service-img'><img src="<?php echo $service_data['image'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                  <?php if(isset($service_data['link_text'])){ ?>
                                                   <a href="<?php echo $service_data['link_url'];?>" class='btn btn-small btn-primary'><?php echo $service_data['link_text'];?></a>
                                                  <?php } ?>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="desc-<?php echo $c;?>">
                                                <h4><?php echo $service_data['software'];?></h4>
                                                <p><?php echo $service_data['description'];?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.card-block -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col-xl-4 -->
                          <?php  ;
                          };?> 
                        </div>
                    </section>



</article>
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');?>


