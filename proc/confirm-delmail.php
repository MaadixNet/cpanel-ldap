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
printf(_("Esta acción desactiva la cuenta, pero no borra los mensajes enviados y recibidos hasta ahora, que se mantendrán almacenados en el servidor.<br> Si en un futuro vuelves a activar la misma cuenta, los mismos mensajes volverán a estar accesibles.<br> Para eliminar definitivamente todos los mensajes del servidor tienes tres opciones:
 <ul><li> Vacíar el buzón desde la herramienta webmail antes de eliminar la cuenta.</li><li>Configurar un cliente mail (Thunderbird, Outlook...) utilizando el protocolo IMAP y borrar los emails desde el mismo cliente. <a href='https://es.wikipedia.org/wiki/Protocolo_de_acceso_a_mensajes_de_Internet' target='_blank'>Saber más</a></li><li> Mediante la línea de comandos en la terminal como root (para usuarios avanzados).</li></ul><br> ¿Quieres continuar y desactivar esta cuenta de correo?"));
echo '<div class="modal-footer">
      <form action=""  method="POST" class=""><input type="hidden" name="userid" value="'. $mail. '" /><input type="hidden" name="domain" value="' . $domain . '" /> <button type="submit"  name="deluser" class="btn btn-small btn-primary">' . sprintf(_('Borrar')) . '</button><button type="button" class="btn btn-small btn-secondary" data-dismiss="modal">' . sprintf (_("Cancelar")) . '</button> </form>
      </div>';
