<?php 

session_start();
require_once 'classes/class.ldap.php';

$current_page=basename(__FILE__);

$message='';
require_once('header.php');
  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $permissions= $_SESSION["login"]["level"];

//Gett all installed services
if ($ldapbind) {
  $serv_enabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled)(type=available))');
  $serv_disabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=disabled)(type=installed))');
}
// Change status and type for grou if it is deactivated
if(isset($_POST['changeStatus'])){
$deactivateGroup = (isset($_POST['deactivateGroup']))?$_POST['deactivateGroup']:'';
$activateGroup = (isset($_POST['activateGroup']))?$_POST['activateGroup']:'';
  if (!empty($deactivateGroup)) {
    foreach ($deactivateGroup as $value){
      $entry = array();
      $modifydn='ou=' . $value . ',' . LDAP_SERVICES;
      $entry['type'] = 'installed';
      $entry['status'] = 'disabled';
      $updategroup=$Ldap->modifyRecord($ldapconn, $modifydn, $entry );

    }
  }

if (!empty($activateGroup)) {
    foreach ($activateGroup as $value){
      $entry = array(); 
      $modifydn='ou=' . $value . ',' . LDAP_SERVICES;
      $entry['type'] = 'available';
      $entry['status'] = 'enabled';
      $updategroup=$Ldap->modifyRecord($ldapconn, $modifydn, $entry );
    
    }
  }
// Lock cpanel and destroy session
  if ($updategroup){
    //Update ou=cpanel object with lock status
    $modifydn='ou=cpanel,' . SUFFIX ;
    $info = array();
    $info['status']= 'locked';
    $updaterelease=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
    //Clear this sessions
    session_destroy();
    //Redirect to home
    header('Location: /cpanel');
  }
}
// Get current release info
$release_info = $Ldap->getreleaseinfo('release');

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
  //if group has domain as dependency, search in ldap the domain to build the link
  if (isset($gr['dependencies']) && in_array("domain.domain.Dominio", $gr['dependencies'])) {
    if ($ldapbind) {
      $domain= $Ldap->search($ldapconn,'ou=domain,ou='.$gr["id"].','.LDAP_SERVICES ,'(objectClass=organizationalUnit)');
    }
    $group_info[$gr['id']]['link_url']='https://'.$domain[0]['status'][0];
  } else {
    $group_info[$gr['id']]['link_url']=(isset($gr['link_url']))?$gr['link_url']:'';
  }
  $group_info[$gr['id']]['target']=(isset($gr['target']))?$gr['target']:'';
  $group_info[$gr['id']]['link_text']=(isset($gr['link_text']))?$gr['link_text']:'';
}

// Sidebar
require_once('sidebar.php');

// If API is available
if (isset ($obj)){
//For  some groups there is no option to deactivate. We store all of them into an array
$forbidden_deactivate=array('mail','mongodb','nodejs'); ?>
  <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Aplicaciones instaladas"));?> </h3>
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
                                                    <p><div class='img service-img'><img src="<?php echo ASSETS_URI;?>/c-panel/images/services/<?php echo $group_info[$service]['img'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                    <?php if (!in_array($group_info[$service]['id'], $forbidden_deactivate)){ ?>
                                                    <div><label>
                                                      <input type="checkbox" class="deactivateGroup checkbox" name="gorups" value="<?php echo $group_info[$service]['id']; ?>" />
                                                      <span><?php printf(_("Desactivar"));?></span></label></div>
                                                      <input type="hidden" class="groupname" name="groupname" value="<?php echo $group_info[$service]['id']; ?>" />
                                                    <?php }
                                                   if (!empty($group_info[$service]['link_url'])){ ?>
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


            <!-- Inactive apps -->
            <?php if($serv_disabled["count"]>0){
              // Print this section only if there are some deactivateds apps
            ?>
              <div class="title-block">
                <h3 class="title"> <?php printf(_("Aplicaciones desactivadas"));?> </h3>
              </div>
                 <section class="section inactive">
                        <div class="row ">
                      
                       <?php for ($c=0; $c<$serv_disabled["count"]; $c++) {
                          $service=$serv_disabled[$c]["ou"][0];
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
                                            <li class="nav-item"> <a href="#home-des-<?php echo $c;?>" class="nav-link active" data-target="#home-des-<?php echo $c;?>" data-toggle="tab" aria-controls="home-des-<?php echo $c;?>" role="tab">App</a> </li>
                                            <li class="nav-item"> <a href="#desc-des-<?php echo $c;?>" class="nav-link" data-target="#desc-des-<?php echo $c;?>" aria-controls="desc-des-<?php echo $c;?>" data-toggle="tab" role="tab"><?php printf(_("Descripción"));?></a> </li>

                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabs-bordered">
                                            <div class="tab-pane fade in active" id="home-des-<?php echo $c;?>">
                                                <h4></h4>
                                                <div class="row">
                                                  <div class="col-md-6">
                                                    <p><div class='img service-img'><img src="<?php echo ASSETS_URI;?>/c-panel/images/services/<?php echo $group_info[$service]['img'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                    <?php if (!in_array($group_info[$service]['id'], $forbidden_deactivate)){ ?>
                                                    <div><label>
                                                      <input type="checkbox" class="activateGroup checkbox" name="gorups" value="<?php echo $group_info[$service]['id']; ?>" />
                                                      <span><?php printf(_("Activar"));?></span></label></div>
                                                      <input type="hidden" class="groupname" name="groupname" value="<?php echo $group_info[$service]['id']; ?>" />
                                                    <?php }
                                                   if (!empty($group_info[$service]['link_url'])){ ?>
                                                   <a href="<?php echo $group_info[$service]['link_url'];?>" target="<?php echo $group_info[$service]['target'];?>"><button type="button" class='btn btn-small btn-primary'><?php echo $group_info[$service]['link_text'];?></button></a>
                                                   <?php } ?>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="desc-des-<?php echo $c;?>">
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
                <?php
                // end section if deactivated apps
                }?>
            <div class="title-block">
               <?php
                 echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#changeStatusModal" data-release ="' . $release_info['release'] . '">' . sprintf (_('Continuar')) . '</button>';
               ?>
            </div>
<div class="bd-example">
  <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"><?php printf(_('Cambiar estado de aplicaciones'));?></h4>
        </div>
        <div class="modal-body" id="modal-body">
          <form id="groups-to-change" action='' method='POST'>

            <div id="modal-response"></div>
              <input type='hidden' name='release' value='<?php echo $release_info['release'];?>' />
                <?php 
              // Create one empty div for each available gorup. Then punt the input hidden in each of them
              for($c=0; $c<$serv_enabled["count"]; $c++) {
                $service=$serv_enabled[$c]["ou"][0];
                 if (!in_array($group_info[$service]['id'], $forbidden_deactivate)){
                  printf(_('<div id="deactivate-group-%s" data-groupname="%s" class="group-inputs hide"></div>'),$group_info[$service]['id'],$group_info[$service]['name']);
                }
              }
              for($c=0; $c<$serv_disabled["count"]; $c++) {
                $service=$serv_disabled[$c]["ou"][0];
                printf(_('<div id="activate-group-%s" data-groupname="%s" class="group-inputs hide"></div>'),$group_info[$service]['id'],$group_info[$service]['name']);
              }
             ?>
            </form>
          
        </div><!--modal-body-->
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->


  </article>
  <?php } else { ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("En este momento no hay descripciones disponibles para las aplicaciones instaladas"));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Inténtalo de nuevo pasados unos minutos."));?> </p>
                <p class="title-description"> <?php printf(_("Disculpa las molestias"));?> </p>
            </div>
    </article>
  <?php
  }

  ldap_close($ldapconn);
  require_once('footer.php');
