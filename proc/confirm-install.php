<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Returns a window for confirmation
 *
 */
$groups=$deps=array();
if($_POST['groups'])$groups=$_POST['groups'];
$deps=(isset($_POST['deps']))?$_POST['deps']:array();
//$domains=$_POST['inputDep'];
if (count($groups)>0){
  printf(_("Las siguientes aplicaciones se han seleccionado para ser instaladas"));
  echo '<br />';
  echo '<br />';
  echo '<ul>';
  foreach ($groups as $group){
    echo '<li class="update-groups">'.$group.'</li>';
  }
  echo '</ul>';
  if (count($deps)>0){
    printf(_("Además se instalarán las siguientes dependencias:"));
    echo '<br />';
    echo '<br />';
    echo '<ul>';
    foreach ($deps as $dep){
      echo '<li class="update-groups">'.$dep.'</li>';
    }
    echo '</ul>';
  }
  printf(_("El proceso de instalación durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso de instalación termine se activará el formulario para volver a acceder."));
  echo '<br />';
  echo '<br />';
  printf(_("Confirmas que quiere instalar las aplicaciones ahora?"));
  echo '<br />';
  echo '<br />';
  echo "<div class='modal-footer'>";
  echo "    <button type='submit' name='install' class='btn btn-small btn-primary'>". sprintf(_('Instalar')) ."</button>
            <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>";
  echo "</div>";
} else {
  printf(_("No hay ninguna aplicación seleccionada para instalar."));
  echo '<br />';
  echo '<br />';

  echo "<div class='modal-footer'>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Volver")) . "</button>
      </div>";
}
