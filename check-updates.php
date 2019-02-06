<?php

session_start();
require_once('header.php');
$msg ='';
//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}
// Send report
if(isset($_POST['report'])){
  $sendreport = $Ldap->sendReport($ldapconn);
    if ($sendreport){
      $message = (_('La operación se ha efectuado con éxito. En cuanto el informe esté generado será enviado al equipo técnico de MaadiX. También recibirás una copia a la cuenta de correo asociada al administrador del panel de control'));
      $msg = " <div class='alert alert-success'><button class='close' data-dismiss='alert'>&times;</button>". $message . "</div>";
    } else {
      $message = (_("Se ha producido un error en la operación de solicitud del informe del sistema"));
      $msg = " <div class='alert alert-error'><button class='close' data-dismiss='alert'>&times;</button>". $message . "</div>";
  }
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
    $update_type = '';
    $str = shell_exec('cat /etc/debian_version');
    echo 'debian version is? : ' . (int)$str;
    // Check for updates
    //$updates = getreleaseinfo($Ldap,$ldapconn,$ldapbind, 'updates');
    $updates =$Ldap->getreleaseinfo('updates');
    if ((int)$str < 9){?>
      <article class="content cards-page">
       
        <div class="title-block">
          <h3 class="title"> <?php printf(_("Actualización Disponible"));?> </h3>
        </div>
        <div><?php echo $msg;?></div>
       <section class="section">
      <?php 
      printf(_("<p>La versión actual del sistema es Debian %s Jessie. Esta versión dejará de tener soporte por parte de la misma distribución y deberías actualizarla cuanto antes a la siguiente Debian Stretch. MaadiX pone a disposición un sistema automatizado para realizar la actualización que puedes empezar desde esta página.</p>"), $str);
      printf(_("<p>Se trata de una actualización de todo el sistema por lo que es necesario, como primer paso, averiguar que la configuración de tu sistema sea compatible con la automatización desarrollada por MaadiX. Para ello hemos habilitado una función que te permitirá solictar al equipo técnico de MaadiX la activación del proceso que incluye las siguientes fases:</p>"));
      printf(_("<ul><li>Enviar al equipo técnico de MaadiX un informe sobre el estado del sistema. Este informe comprueba que el sistema no contenga configuraciones especiales que se puedan haber activado de forma manual y que podrían generar incompatibilidades o pérdidas en el proceso de actualización</li>
                <li>Nuestro equipo se pondrá en contacto contigo por correo electrónico para notificarte el resultado del informe y autorizar el proceso de actualización. Dicho proceso necesitará una intervención manual por parte de nuestro equipo en nuestra infraestructura además de requerir apagar el servidor. Nos pondremos en contacto contigo por correo electrónico para consensuar el momento en el que se llevará a cabo la actualización, con tal de minimizar las molestias generadas por la falta de conexion al servidor. </li></ul>"));
      echo "<form action='' method='POST'>";    
      echo '<button type="submit" class="btn btn-primary" name="report">' . sprintf (_('Enviar Informe')) . '</button>';
      echo "</form>";
  ?>
        </div>
    </article>

   <?php } else if (empty($updates)) { ?>
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
