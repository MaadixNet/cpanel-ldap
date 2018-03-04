<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Returns a window for confirmation
 *
 */
$activateGroups=$deactivateGroups=array();
$activateGroups=$_POST['activategroups'];
$deactivateGroups=$_POST['deactivategroups'];

//$domains=$_POST['inputDep'];
if (count($activateGroups)>0 || count($deactivateGroups)>0) {
  if (count($activateGroups)>0){
    printf(_("Las siguientes aplicaciones se han seleccionado para ser activadas"));
    echo '<br />';
    echo '<br />';
    echo '<ul>';
    foreach ($activateGroups as $group){
      echo '<li class="update-groups">'.$group.'</li>';
    }
    echo '</ul>';
  }
  if (count($deactivateGroups)>0){
    printf(_("Las siguientes aplicaciones se han seleccionado para ser desactivadas. No se borrarán los archivos de la aplicación, ni los datos. Las aplicaciones desactivadas podrán reactivarse, manteniendo la configuración actual. "));
    echo '<br />';
    echo '<br />';
    echo '<ul>';
    foreach ($deactivateGroups as $deact){
      echo '<li class="update-groups">'.$deact.'</li>';
    }
    echo '</ul>';
  }
  printf(_("El proceso durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando la operación termine se activará el formulario para volver a acceder."));
  echo '<br />';
  echo '<br />';
  printf(_("Confirmas que quieres cambiar el estado de las aplicaciones ahora?"));
  echo '<br />';
  echo '<br />';
  echo "<div class='modal-footer'>";
  echo "    <button type='submit' name='changeStatus' class='btn btn-small btn-primary'>". sprintf(_('Confirmar')) ."</button>
            <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>";
  echo "</div>";
} else {
  printf(_("No hay ninguna aplicación seleccionada."));
  echo '<br />';
  echo '<br />';

  echo "<div class='modal-footer'>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Volver")) . "</button>
      </div>";
}
