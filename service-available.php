<?php

session_start();
require_once('header.php');
//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}

$status = $Ldap->getpuppetstatus();
$available='';
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
$release_info = $Ldap->getreleaseinfo('release');
// Get available groups in the release
$obj = $release_info['groups'];
//Create mpty array for dependencies
$dep_form_fields=array();
// Create an array with all installable goups to create divs in form
$installable_service=array();
// A string with all hidden inputs /value for dependencies 
 $formelements='';
/****************** Perform install after submitting form **********/

$groups=$inputDep=array();
if(isset($_POST['install']) && isset($_POST['release'])){

  $release = $_POST['release'];
  //$groups = $_POST['groups'];
  $groups = (isset($_POST['installGroup']))?$_POST['installGroup']:'';
  //$domains = $_POST['domain'];
  if (isset($_POST['inputDep'])) $inputDep = $_POST['inputDep'];
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
 
  
  foreach ($inputDep as $key => $value){

    foreach ($value as $dep => $val){
    $entrydn='ou='. $dep .',ou=' . $key .',' . LDAP_SERVICES;
   if (!$Ldap->search($ldapconn, $entrydn, '(objectclass=*)')){
      $entry["objectclass"][0] = "organizationalUnit";
      $entry["objectclass"][1] = "metaInfo";
      $entry["ou"] = $dep;
      $addGroup=$Ldap->addRecord($ldapconn,$entrydn,$entry);
    }
      echo 'depName: ' . $dep . ' depValue: ' . $val;

      //Modify domain object
      $info = array();
      $info['status'] = $val;
      ldap_mod_replace($ldapconn,$entrydn,$info);
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
//sidebar
require_once('sidebar.php');
/****************** End perform update after submitting form *******/
// Repopulate $serv_installed with all the applicatios that are installed, also if they are disabled,
// so they don't appear between available apllicatio.
// $serv_installed originally comes from header, and it ocly includes active apps
// Check if there is any group to install



$serv_installed= $Ldap->search($ldapconn, LDAP_SERVICES ,'(|(&(objectClass=organizationalUnit)(status=enabled)(type=available))(&(objectClass=organizationalUnit)(status=disabled)(type=installed)))');

foreach ($obj as $service_data){
  if (array_search($service_data['id'], array_column(array_column($serv_installed, 'ou'),0)) === false) $available=1;
}
//sidebar
//require_once('sidebar.php');

//print_r ($status);
if (empty($release_info)) { ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("No hay ninguna actualización disponible en este momento"));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Inténtalo de nuevo pasados unos minutos."));?> </p>
            </div>
    </article>

<?php }else{ 
?>
  <article class="content cards-page">
          <?php if ($available != 1){ ?>

            <div class="title-block">
                <h4 class="title"> <?php printf(_("No hay aplicaciones para instalar"));?> </h4>
            </div>

          <?php } else { ?>

            <div class="title-block">
                <h4 class="title"> <?php printf(_("Aplicaciones Disponibles para Instalar"));?> </h4>
                <br>
                <h5 class=""> <?php printf(_("Selecciona las aplicaciones que quieras instalar y haz clic en el botón 'Instalar' que encontrarás al fondo de esta página para empezar el proceso de instalación"));?> </h5>

            </div>

                 <section class="section">
                        <div class="row ">
                       <?php $c = 0;?>
                       <?php foreach ($obj as $service_data ) {

                          if ( $c % 3 == 0 ){; ?>
                           <div class="clearfix visible-xs"></div>
                          <?php
                                            }
                          ?>
                            <?php if( !empty($serv_installed) && array_search($service_data['id'], array_column(array_column($serv_installed, 'ou'),0)) === false){
                            $installable_service[$c]['id']=$service_data['id']; 
                            $installable_service[$c]['name']=$service_data['name'];?>
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
                                                    <p><div class='img service-img'><img src="<?php echo ASSETS_URI;?>/c-panel/images/services/<?php echo $service_data['img'];?> " /></div></p>
                                                  </div>
                                                  <div class="col-md-6">
                                                     <?php
                                                        $depNeedsInput='';
                                                        $dep_form_fields=array();
                                                      if(array_key_exists('dependencies', $service_data)) {
                                                        //If there are dependencies, print the input fields that ill be cloned in the modal
                                                         $dep_form_fields= dependencies_input_fields($service_data);
                                                      }
                                                      if (!empty($dep_form_fields['inputHtml'])) {
                                                        $depNeedsInput='depNeedsInput';
                                                        echo '<div id="#modalfields">';
                                                        echo $dep_form_fields['inputHtml'];
                                                        echo '</div>';
                                                        
                                                      }
                                                    ?>  
                                                    <div><label>
                                                      <input type="checkbox" class="installGroups checkbox <?php echo $depNeedsInput;?>" name="groups" value="<?php echo $service_data['id']; ?>" />
                                                    <span><?php printf(_("Seleccionar"));?></span></label></div>
                                                      <input type="hidden" class="groupname" name="groupname" value="<?php echo $service_data['name']; ?>" />
                                                    <?php if (isset($dep_form_fields['hiddenHtml'])) echo $dep_form_fields['hiddenHtml'];?>
                                                    <span class="activatedDomain"></span>
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
        <form id="groups-to-install" action='' method='POST'>

          <div id="modal-response"></div>
              <input type='hidden' name='release' value='<?php echo $release_info['release'];?>' />
                <?php 
              // Create one empty div for each available gorup. Then punt the input hidden in each of them
              foreach($installable_service as $value) {
                printf(_('<div id="install-group-%s" data-groupname="%s" class="group-inputs hide"></div>'),$value['id'],$value['name']);
              }
             ?>
            </form>
          
        </div><!--modal-body-->
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->
<?php
$appnameSpan='<span class="appnameSpan"></span>';
?>
<div class="bd-example">
  <div class="modal fade" id="fieldsModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="userModalLabel"><?php printf(_("Campos requeridos para %s"),$appnameSpan );?></h4>
        </div>
        <div class="modal-body" id="modal-body">
          <form id="fieldset">
            <div class="form-group">
            </div>

            <div class='modal-footer'>
              <input type="submit" class="btn btn-primary btn-sm" id="fieldsSave" name="fieldsSave" value="<?php printf(_("Guardar"));?>"  />
              <div class="fields-info" id="fields-info">
              </div>
            </div>

          </form>
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

<?php } //end if empty $release_info ?>

<?php
  ldap_close($ldapconn);
  require_once('footer.php');


