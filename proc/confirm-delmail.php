<?php
/**
 * Created by Maddish
 *
 * function file called by domain.php
 * Returns a window for confirmation
 * which includes data about domain
 *
 */
$mail=$_POST['mail'];
$domain= $_POST ['domain'];
echo '
     ¿Estás seguro de que quieres eliminar esta cuenta de correo? Esta acción no podrá deshacerse.
      <div class="modal-footer">
      <form action=""  method="POST" class=""><input type="hidden" name="userid" value="'. $mail. '" /><input type="hidden" name="domain" value="' . $domain . '" /> <button type="submit"  name="deluser" class="btn btn-primary">' . sprintf(_('Borrar')) . '</button><button type="button" class="btn btn-secondary" data-dismiss="modal">' . sprintf (_("Cancelar")) . '</button> </form>
      </div>';

