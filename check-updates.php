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

//sidebar
require_once('sidebar.php');

// Check puppet status
$status = getpuppetstatus($Ldap,$ldapconn,$ldapbind);

switch ($status) :
  case "error" :
    ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("La tarea de Mantenimiento o Actualización ha producido un error."));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("El equipo técnico ha sido notificado y está ahora trabajando en la solución."));?> </p>
                <p class="title-description"> <?php printf(_("En breves se pondrá en contacto contigo."));?> </p>
                <p class="title-description"> <?php printf(_("Disculpa las molestias"));?> </p>
            </div>
    </article>
    <?php
  break;
  case "pending" :

    /****************** Simple lock cpanel after submitting form **********/

    if(isset($_POST['update']) && isset($_POST['release'])){

      $release = $_POST['release'];
      $groups = $_POST['groups'];

      //Update ou=cpanel object to lock cpanel
      $modifydn='ou=cpanel,dc=example,dc=tld';
      $info = array();
      $info['status']= 'locked';
      $updaterelease=$Ldap->modifyRecord($ldapconn, $modifydn, $info );

      //Clear this sessions
      session_destroy();

      //Redirect to home
      header('Location: /cpanel');

    }

    /****************** End perform update after submitting form *******/
    ?>
      <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Una tarea de Mantenimiento o Actualización está pendiente."));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Es necesario aplicar los cambios para que la actualización se complete."));?> </p>
                <br />
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updateModal" data-release ="pending"><?php printf (_('Aplicar cambios')) ?></button>
            </div>
      </article>
    <?php
  break;
  case "ready" :

    // Check for updates
    $updates = getreleaseinfo($Ldap,$ldapconn,$ldapbind, updates);

    // Get available groups in the new release
    $obj = $updates['groups'];

    /****************** Perform update after submitting form **********/

    if(isset($_POST['update']) && isset($_POST['release'])){

      $release = $_POST['release'];
      $groups = $_POST['groups'];

      //Add new groups to ldap with status 'install'
      foreach ($groups as $group){
        if ($Ldap->search($ldapconn, 'ou='.$group.',ou=groups,dc=example,dc=tld', '(objectclass=*)')){
          //Modify status of existing group
          $info = array();
          $modifydn='ou='.$group.',ou=groups,dc=example,dc=tld';
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
          $entrydn='ou=' . $group .',ou=groups,dc=example,dc=tld';
          $addGroup=$Ldap->addRecord($ldapconn,$entrydn,$entry);
        }
      }

      //Update ou=cpanel object with new release name and lock cpanel
      $modifydn='ou=cpanel,dc=example,dc=tld';
      $info = array();
      $info['type']= $release;
      $info['status']= 'locked';
      $updaterelease=$Ldap->modifyRecord($ldapconn, $modifydn, $info );

      //Clear this sessions
      session_destroy();

      //Redirect to home
      header('Location: /cpanel');

    }

    /****************** End perform update after submitting form *******/


    if (empty($updates)) { ?>
      <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("No hay Actualizaciones Disponibles"));?> </h3>
            </div>
      </article>

    <?php }else{ ?>

      <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Actualización disponible"));?> </h3>
                <p class="title-description"> <?php printf(_("Hay una nueva actualización de sistema disponible."));?> </p>
            </div>
                 <section class="section">
                     <div class="row ">
                            <div class="col-xl-12">
                                <div class="card ">
                                    <div class="card-block">
                                        <!-- Nav tabs -->
                                        <div class="card-title-block">
                                            <h3 class="title"><?php echo  $updates['release'];?></h3>
                                        </div>
                                        <ul class="nav nav-tabs nav-tabs-bordered">
                                            <li class="nav-item"> <a href="#release-0" class="nav-link active" data-target="#release-0" data-toggle="tab" aria-controls="release-0" role="tab">Info</a> </li>
                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabs-bordered">
                                            <div class="tab-pane fade in active" id="release-0">
                                                <p><?php echo $updates['description'];?></p>
                                                <?php
                                                  echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updateModal" data-release ="' . $updates['release'] . '">' . sprintf (_('Actualizar')) . '</button>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.card-block -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col-xl-4 -->
                      </div>
                 </section>

            <div class="title-block">
                <h4 class="title"> <?php printf(_("Aplicaciones Disponibles en la Actualización"));?> </h4>
                <p class="title-description"> <?php printf(_("Marque las aplicaciones que desea instalar durante la actualización."));?> </p>
                <p class="title-description"> <?php printf(_("Las aplicaciones que dejes sin instalar, podrás instalarlas más adelante desde el apartado 'Instalar Aplicaciones'"));?> </p>
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
                                                       foreach ($service_data['dependencies'] as $dependency){ ?>
                                                         <input class="dependency" type="hidden" name="dependencies[<?php echo $service_data['id']; ?>][<?php echo $ii; ?>]" value="<?php echo $service_data['dependencies'][$ii]; ?>" />
                                                     <?php
                                                         $ii=$ii+1;
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

      </article>

    <?php } //end if empty $updates
  break;
  default:
    ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Se ha producido un error inesperado."));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("Inténtelo de nuevo pasados unos minutos."));?> </p>
                <p class="title-description"> <?php printf(_("Disculpa las molestias"));?> </p>
            </div>
    </article>
    <?php
  break;
  endswitch;
?>

<div class="bd-example">
  <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
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

<?php
  ldap_close($ldapconn);
  require_once('footer.php');
?>


