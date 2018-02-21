<?php

session_start();
require_once('header.php');

//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}

// Check puppet status
    if(isset($_POST['update']) && isset($_POST['release'])){

      $release = $_POST['release'];
      //$groups = $_POST['groups'];

      //Update ou=cpanel object to lock cpanel
      $modifydn='ou=cpanel,' . SUFFIX;
      $info = array();
      if (!empty($release) && $release != 'pending'){
        $info['type']= $release;
      }
      $info['status']= 'locked';
      $updaterelease=$Ldap->modifyRecord($ldapconn, $modifydn, $info );

      //Clear this sessions
      session_destroy();

      //Redirect to home
      header('Location: /cpanel');

    }
//$status = getpuppetstatus($Ldap,$ldapconn,$ldapbind);
$status = $Ldap->getpuppetstatus();
if (!isset($_POST['update']) && !isset($_POST['release'])) {
  require_once('sidebar.php');
}
switch ($status) :
  case "error" :
    //sidebar
    //require_once('sidebar.php');
    //Set the correct value for the update type: pending, error or new release
    $update_type = ''; 
    ?>

    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("La tarea de Mantenimiento o Actualización ha producido un error."));?> </h3>
                <br />
                <p class="title-description"> <?php printf(_("El equipo técnico ha sido notificado y está ahora trabajando en la solución."));?> </p>
                <p class="title-description"> <?php printf(_("En breve se pondrá en contacto contigo."));?> </p>
                <p class="title-description"> <?php printf(_("Disculpa las molestias"));?> </p>
            </div>
    </article>
    <?php
  break;
  case "pending" :
    $update_type = 'pending';
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
    //$updates = getreleaseinfo($Ldap,$ldapconn,$ldapbind, 'updates');
    $updates =$Ldap->getreleaseinfo('updates');

    if (empty($updates)) { ?>
      <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("No hay Actualizaciones Disponibles"));?> </h3>
            </div>
      </article>

    <?php }else{
    $update_type = $updates['release'];?>

      <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Actualización disponible"));?> </h3>
                <p class="title-description"> <?php printf(_("Hay una nueva versión de sistema disponible."));?> </p>
            </div>
                 <section class="section">
                     <div class="row ">
                            <div class="col-xl-12">
                                <div class="card ">
                                    <div class="card-block">
                                        <!-- Nav tabs -->
                                        <div class="card-title-block">
                                        <h3 class="title"><?php printf(_("Existe una nueva versión del panel de control. Desde esta página puedes actualizaar a la última versión, para que tu sistema incluya los cambios listados a continuación."));?></h3>
                                        <br>
                                        <h3 class="title"><?php printf(_("Detalles"));?></h3>
                                            <br>
                                            <ul>
                                              <li><?php printf(_("Nombre de la versión: %s"),$updates['release']);?></li>
                                              <li><?php printf(_("Descripción: %s"), $updates['description']);?></li>
                                            </ul>
                                            <br>
                                        <h3 class="title"><?php printf(_(" Haz click en el botón 'Actualizar' para empezar el proceso." ));?></h3>
                                        </div>
                                    </div>
                                    <!-- /.card-block -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col-xl-4 -->
                      </div>
                 </section>

                 <?php
                  echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updateModal" data-release ="' . $updates['release'] . '">' . sprintf (_('Actualizar')) . '</button>';
                 ?> 
      </article>

    <?php } //end if empty $updates
  break;
  default:
    ?>
    <article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Np hay actualizaciones disponibles en este momento."));?> </h3>
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
        <div class='modal-footer'>
        <form action='' method='POST'>
          <input type='hidden' name='release' value='<?php echo $update_type;?>' />
          <button type='submit' name='update' class='btn btn-small btn-primary'><?php printf(_('Actualizar'));?></button>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'><?php printf (_("Cancelar"));?></button>
        </form>
      </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

<?php
  ldap_close($ldapconn);
  require_once('footer.php');
