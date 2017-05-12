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

// Get current release info
$release_info = getreleaseinfo($Ldap,$ldapconn,$ldapbind, 'release');

// Get available groups in the release
$obj = $release_info['groups'];

// Build groups info for loop
$group_info = [];
foreach ($obj as $gr){
  $group_info[$gr['id']]['id']=$gr['id'];
  $group_info[$gr['id']]['description']=$gr['description'];
  $group_info[$gr['id']]['name']=$gr['name'];
  $group_info[$gr['id']]['img']=(isset($gr['img']))?$gr['img']:'';
  $group_info[$gr['id']]['title']=(isset($gr['title']))?$gr['title']:$gr['title'];
  $group_info[$gr['id']]['link_url']=(isset($gr['link_url']))?$gr['link_url']:'';
  $group_info[$gr['id']]['target']=(isset($gr['target']))?$gr['target']:'';
  $group_info[$gr['id']]['link_text']=(isset($gr['link_text']))?$gr['link_text']:'';
}

/*
echo '<pre>';
print_r ($group_info);
echo '</pre>';
*/

// Sidebar
require_once('sidebar.php');

// If API is available
if (isset ($obj)){
/*  echo '<pre>';
  print_r($release_info );
echo '</pre>';
 */
  ?>
  <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Aplicaciones Instaladas"));?> </h3>
            </div>
                 <section class="section">
                        <div class="row ">
                       <?php for ($c=0; $c<$serv_enabled["count"]; $c++) {
                          $service=$serv_enabled[$c]["ou"][0];
                          if ( $c % 3 == 0 ){; ?>
                           <div class="clearfix visible-xs"></div>
                          <?php }
                          ?>
                            <div class="col-xl-4">
                                <div class="card ">
                                    <div class="card-block">
                                        <!-- Nav tabs -->
                                        <div class="card-title-block">
                                            <h3 class="title"><?php echo  $group_info[$service]['title'];?></h3>
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
                                                    <p><div class='img service-img'><img src="/cpanel/images/services/<?php echo $group_info[$service]['img'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                   <?php if (isset($group_info[$service]['link_url'])){ ?>
                                                   <a href="<?php echo $group_info[$service]['link_url'];?>" target="<?php echo $group_info[$service]['target'];?>"><button type="button" class='btn btn-small btn-primary'><?php echo $group_info[$service]['link_text'];?></button></a>
                                                   <?php } ?>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="desc-<?php echo $c;?>">
                                                <h4><?php echo $group_info[$service]['name'];?></h4>
                                                <p><?php echo $group_info[$service]['description'];?></p>
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
  <?php } else { ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("En este momento no hay descripciones disponibles para las apllicaciones instaladas"));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Inténtalo de nuevo pasados unos minutos."));?> </p>
                <p class="title-description"> <?php printf(_("Disculpa las molestias"));?> </p>
            </div>
    </article>
  <?php
  }
  ldap_close($ldapconn);
  require_once('footer.php');
