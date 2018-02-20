<?php
/**
 * Created by Maddish
 *
 * function file called by check-updates.php
 * Returns a window for confirmation
 *
 */

$release=$_POST['release'];
//$groups=$_POST['groups'];
//$dependencies_all=$_POST['dependencies'];
//force adding dependencies
/*
foreach ($groups as $group){
  if (isset($dependencies_all[$group])){
    foreach ($dependencies_all[$group] as $dep){
      if (!in_array($dep, $groups)) $dependencies[]=$dep;
    }
  }
}
 */
session_start();
//require_once  __DIR__.'/../classes/class.ldap.php';
//require_once __DIR__.'/../site-config.php';
/*
if (count($groups)>0){
  printf(_("Las siguientes aplicaciones se han seleccionado para ser instaladas en esta actualización:"));
  echo '<br />';
  echo '<br />';
  echo '<ul>';
  foreach ($groups as $group){
    echo '<li class="update-groups">'.$group.'</li>';
  }
  echo '</ul>';
}
if (count($dependencies)>0){
  printf(_("Las siguientes dependencias son necesarias para las aplicaciones seleccionadas y serán instaladas automáticamente:"));
  echo '<br />';
  echo '<br />';
  echo '<ul>';
  foreach ($dependencies as $dep){
    echo '<li class="update-groups">'.$dep.'</li>';
  }
  echo '</ul>';
}
echo '<br />';
echo '<br />';
 */
printf(_("El proceso de actualización durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso de termine se activará el formulario para volver a acceder."));
echo '<br />';
echo '<br />';

if ($release=="pending"){
    printf(_("Confirmas que quiere aplicar los cambios pendientes ahora?"));
} else {
    printf(_("Confirmas que quiere actualizar a la nueva versión %s ahora?"), $release);
}


echo "<div class='modal-footer'>
        <form action='' method='POST'>
          <input type='hidden' name='release' value='". $release ."' />";
//here are merged in groups, selected groups and dependencies
/*
foreach ($groups as $group) {
echo "    <input type='hidden' name='groups[]' value='". $group ."' />";
}
foreach ($dependencies as $group) {
echo "    <input type='hidden' name='groups[]' value='". $group ."' />";
}
 */
echo "    <button type='submit' name='update' class='btn btn-small btn-primary'>". sprintf(_('Actualizar')) ."</button>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>
        </form>
      </div>";
