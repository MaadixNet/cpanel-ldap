<?php
/**
 * Created by Maddish
 *
 * function file called by check-updates.php
 * Returns a window for confirmation
 *
 */

$release=$_POST['release'];
session_start();
printf(_("El proceso de actualización durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso de termine se activará el formulario para volver a acceder."));
echo '<br />';
echo '<br />';

if ($release=="pending"){
    printf(_("Confirmas que quiere aplicar los cambios pendientes ahora?"));
} else {
    printf(_("Confirmas que quiere actualizar a la nueva versión %s ahora?"), $release);
}
