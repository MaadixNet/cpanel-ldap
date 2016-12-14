<?php
/**
 * Created by Maddish
 *
 * function file called by domain.php
 * Returns a window for confirmation
 * which includes data about domain
 *
 */
$user=$_POST['user'];
printf(_("¿Estás seguro de que quieres eliminar el usuario %s?"), $user);
echo "<div class='modal-footer'>
      <form action='' method='POST'><input type='hidden' name='userid' value='". $user ."' /> <button type='submit' name='deluser' class='btn btn-small btn-primary'>". sprintf(_('Borrar')) ."</button><button type='button' class='btn btn-secondary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button></form>
      </div>";

