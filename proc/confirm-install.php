<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Returns a window for confirmation
 *
 */

$release=$_POST['release'];
$groups=$_POST['groups'];
$dependencies_all=$_POST['dependencies'];
//force adding dependencies
foreach ($groups as $group){
  if (isset($dependencies_all[$group])){
    foreach ($dependencies_all[$group] as $dep){
      if (!in_array($dep, $groups)) $dependencies[]=$dep;
    }
  }
}
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';

if (count($groups)>0){
  printf(_("Las siguientes aplicaciones se han seleccionado para ser instaladas"));
  echo '<br />';
  echo '<br />';
  echo '<ul>';
  foreach ($groups as $group){
    echo '<li class="update-groups">'.$group.'</li>';
  }
  echo '</ul>';

  if (count($dependencies)>0){
    printf(_("Además se instalarán las siguientes dependencias:"));
    echo '<br />';
    echo '<br />';
    echo '<ul>';
    foreach ($dependencies as $dep){
      echo '<li class="update-groups">'.$dep.'</li>';
    }
    echo '</ul>';
  }
  printf(_("Confirma que quiere instalar las nuevas aplicaciones?"));
  echo '<br />';
  echo '<br />';
  printf(_("Al aceptar, su sesión en el cpanel se cerrará y no podrá hacer login hasta que el proceso de instalación finalice."));
  echo '<br />';
  echo '<br />';

  echo "<div class='modal-footer'>
        <form action='' method='POST'>
          <input type='hidden' name='release' value='". $release ."' />";
  //merge in groups, selected groups and dependencies
  foreach ($groups as $group) {
    echo "    <input type='hidden' name='groups[]' value='". $group ."' />";
  }
  foreach ($dependencies as $group) {
    echo "    <input type='hidden' name='groups[]' value='". $group ."' />";
  }
  echo "    <button type='submit' name='install' class='btn btn-small btn-primary'>". sprintf(_('Instalar')) ."</button>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>
        </form>
      </div>";
} else {

  printf(_("No se ha seleccionado nada para instalar."));
  echo '<br />';
  echo '<br />';

  echo "<div class='modal-footer'>
        <form action='' method='POST'>
          <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Volver")) . "</button>
        </form>
      </div>";
}

ldap_close($ldapconn);
