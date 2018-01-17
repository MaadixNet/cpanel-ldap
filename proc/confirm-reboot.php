<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Returns a window for confirmation for rebbotin servert
 *
 */
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';

  printf(_("El proceso de reinicio durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso de reinicio termine se activará el formulario para volver a acceder."));
  echo '<br />';
  echo '<br />';
  printf(_("Confirmas que quieres reiniciar el servidor ahora?"));
  echo '<br />';
  echo '<br />';

  echo "<div class='modal-footer'>
  <form action='' method='POST'>
      <button type='submit' name='reboot' class='btn btn-small btn-primary'>". sprintf(_('Reinicar')) ."</button>
      <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button>
  </form>
  </div>";
