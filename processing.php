<?php
/* processing.php
 *
 * Page where user is redirected after 
 * succesfully request a reset password
 *
 */
require_once('header.php');?>
<div id="admin-content" class="content">
<?php
echo '<h3>'. sprintf(_("Processando operación")). '</h3>';
echo '<p>'. sprintf(_("Todos los datos para recuperar la contraseña han sido insertados correctamente.")). '</p>';
echo '<p>'. sprintf(_("Recibirás un mail de confirmación en cuanto le contraseña haya sido restablecida.")). '</p>';
echo '<p>'. sprintf(_("Este proceso puede tardar hasta 10 minutos.")). '</p>';
?>
</div><!--admin-content-->
<?php require_once('footer.php');?>
