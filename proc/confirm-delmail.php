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
printf(_("Esta acción desactiva la cuenta pero no borra los mensajes enviados y recibidos hasta ahora. Si en un futuro vuelves a crear la misma cuenta, los mismos mensajes volverán a estar accesibles. Para eliminar definitivamente todos los mensajes  del servidor tendrás que hacerlo desde la herramienta webmail, antes de eliminar la cuenta.<br> ¿Quieres continuar y desactivar esta cuenta de correo?"));
echo '<div class="modal-footer">
      <form action=""  method="POST" class=""><input type="hidden" name="userid" value="'. $mail. '" /><input type="hidden" name="domain" value="' . $domain . '" /> <button type="submit"  name="deluser" class="btn btn-primary">' . sprintf(_('Borrar')) . '</button><button type="button" class="btn btn-secondary" data-dismiss="modal">' . sprintf (_("Cancelar")) . '</button> </form>
      </div>';

