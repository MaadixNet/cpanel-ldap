<?php

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
require_once('header.php');
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 
}

//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}

//require_once('sidebar.php');

// Check puppet status
$status = getpuppetstatus($Ldap,$ldapconn,$ldapbind);

if ($status == 'error' || $status == 'pending'){
  //sidebar
  require_once('sidebar.php');
  ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Hay una tarea de mantenimiento pendiente"));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("En estos momentos no se pueden instalar nuevas aplicaciones."));?> </p>
            </div>
    </article>
  <?php
  ldap_close($ldapconn);
  require_once('footer.php');
  exit;
}

// Get current release info
$release_info = getreleaseinfo($Ldap,$ldapconn,$ldapbind, 'release');
// Get available groups in the release
$obj = $release_info['groups'];

/****************** Perform install after submitting form **********/

if(isset($_POST['install']) && isset($_POST['release'])){

  $release = $_POST['release'];
  $groups = $_POST['groups'];

  //Modify new groups to ldap with status 'install'
  foreach ($groups as $group){
    if ($Ldap->search($ldapconn, 'ou=' . $group . ',' . LDAP_SERVICES, '(objectclass=*)')){
      //Modify status of existing group
      $info = array();
      $modifydn='ou=' . $group . ',' . LDAP_SERVICES;
      $info['status']= 'install';
      $updategroup=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
    }else{
      //Add new group to ldap
      $entry = array();
      $entry["objectclass"][0] = "organizationalUnit";
      $entry["objectclass"][1] = "metaInfo";
      $entry["ou"] = $group;
      $entry["status"] = "install";
      $entry["type"] = "available";
      $entrydn='ou=' . $group .',' . LDAP_SERVICES;
      $addGroup=$Ldap->addRecord($ldapconn,$entrydn,$entry);
    }
  }

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

/****************** End perform update after submitting form *******/

// Check if there is any group to install
foreach ($obj as $service_data){
  if (array_search($service_data['id'], array_column(array_column($serv_installed, 'ou'),0)) === false) $available=1;
}

//sidebar
require_once('sidebar.php');

if (empty($release_info)) { ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("No hay ninguna actualización disponile en este momento"));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Inténtalo de nuevo pasados unos minutos."));?> </p>
            </div>
    </article>

<?php }else{ ?>

  <article class="content cards-page">
          <?php if ($available != 1){ ?>

            <div class="title-block">
                <h4 class="title"> <?php printf(_("No hay aplicaciones para instalar"));?> </h4>
            </div>

          <?php } else { ?>

            <div class="title-block">
                <h4 class="title"> <?php printf(_("Aplicaciones Disponibles para Instalar"));?> </h4>
            </div>
            <h4 class=""> <?php printf(_("Selecciona las aplicaciones que quieras instalar y haz click en el botón 'Instalar' que encontrarás al fondo de esta página para empezar el proceso de instalación"));?> </h4>
                 <section class="section">
                        <div class="row ">
                       <?php $c = 0;?>
                       <?php foreach ($obj as $service_data ) {
                          if ( $c % 3 == 0 ){; ?>
                           <div class="clearfix visible-xs"></div>
                          <?php
                                            }
                          ?>
                            <?php if( !empty($serv_installed) && array_search($service_data['id'], array_column(array_column($serv_installed, 'ou'),0)) === false){ ?>
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
                                            <h4><?php echo $service_data['name'];?></h4>
                                                <div class="row">
                                                  <div class="col-md-6">
                                                    <p><div class='img service-img'><img src="images/services/<?php echo $service_data['img'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                     <label><?php printf(_("Instalar"));?></label>
                                                     <input type="checkbox" name="groups" value="<?php echo $service_data['id']; ?>" />
                                                     <?php
                                                      $ii=0;
                                                      if(array_key_exists('dependencies', $service_data)) {
                                                         foreach ($service_data['dependencies'] as $dependency){ ?>
                                                           <input class="dependency" type="hidden" name="dependencies[<?php echo $service_data['id']; ?>][<?php echo $ii; ?>]" value="<?php echo $service_data['dependencies'][$ii]; ?>" />
                                                         <?php
                                                           $ii=$ii+1;
                                                         }
                                                       } ?>
                                                  </div>
                                                </div>

                                            </div>
                                            <div class="tab-pane fade" id="desc-<?php echo $c;?>">
                                                <h4><?php echo $service_data['name'];?></h4>
                                                <p><?php echo $service_data['description'];?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.card-block -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col-xl-4 -->
                          <?php $c++;
                            }
                          };?>
                        </div>
                    </section>

            <div class="title-block">
               <?php
                 echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#installModal" data-release ="' . $release_info['release'] . '">' . sprintf (_('Instalar')) . '</button>';
               ?>
            </div>

          <?php } ?>

</article>

<div class="bd-example">
  <div class="modal fade" id="installModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"></h4>
        </div>
        <div class="modal-body" id="modal-body">
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

<?php } //end if empty $release_info ?>

<?php
  ldap_close($ldapconn);
  require_once('footer.php');
