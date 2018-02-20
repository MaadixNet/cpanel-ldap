<?php 
session_start();
require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";

if ($ldapconn){
	switch ($permissions) :
	case "10" :
		$binddn=LDAP_BASE;
		$filter="(vd=*)";
	break;
        case "4" :
                $binddn=LDAP_BASE;
		$who=$_SESSION["phamm"]["domain"];
		$filter="(vd=" . $who .")";
		
	break;
	case "2":
		$who= $_SESSION['login']["username"];
		$binddn="vd=".$_SESSION["phamm"]["domain"].",".LDAP_BASE;		
		$filter="(mail=" . $who .")";
	break;

	default:
	break;
	endswitch;



}



if ($ldapbind) {
    //Query domains in database
    $result=$Ldap->search($ldapconn,$binddn, $filter);

}
require_once('sidebar.php');
?>
<div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">
        <div class="title-block">
          <h3 class="title"> <?php printf(_("Usuarios postmaster"));?><span class="sparkline bar" data-type="bar"></span> </h3>
          <hr>
          <h3 class="subtitle"><p> <?php printf(_("Los usuarios postmaster pueden entrar en el panel de control utilizando su nombre de usuario y contraseña para administrar las cuentas de correo asociadas a su dominio. Cada dominio creado tiene asociado un usuario postmaster."))?>
          <br></p>
          <p><?php printf(_("Estos usuarios tienen un único permiso:"));?></p>
          <ul>
            <li>
            <?php printf(_("Crear, borrar y editar cualquier cuenta de correo, siempre que ésta pertenezca a su propio dominio"));?>
            </li>
          </ul>

          <?php printf(_("En ningún caso podrán efectuar otra operacion."));?>
          <br>
          <span class="sparkline bar" data-type="bar"></span> </h3>

       </div> 

        <?php 
    if($result["count"]>0){
      ?>
        <table id="domains">
          <thead>
            <tr>
              <th><?php printf(_("Usuario"));?></th>
              <th><?php printf(_("Cuentas de correo"));?> </th>
              <?php if($_SESSION["login"]["level"] == '10') {

                echo '<th>' .sprintf (_('Editar')) . '</th>';

              }?>
            </tr>
          </thead>
          <tbody>

          <?php
          for ($i=0; $i<$result["count"]; $i++) {
              $domain= $result[$i]["vd"][0];
              $resultmail=$Ldap->search($ldapconn,'vd='. $domain . ','. LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');
        
              echo "<tr>";
              echo "<td>";
              echo "postmaster@" . $domain;
              echo "</td>";
              echo "<td>";
              echo "<a href='/". BASE_PATH ."/mails.php?domain=" . $domain ."'>" . sprintf(_('Cuentas de correo')) ."  <span class='counter'>" .$resultmail['count'] . "</span></a> ";
              echo "</td>";
              if($_SESSION["login"]["level"] == '10') {
                echo "<td>";
                echo "<a href='edit-postmaster.php?domain=". $domain ."'><button class='btn btn-small'><i class='fa fa-cogs' aria-hidden='true'></i> ". sprintf(_('Editar')) ."</button></a>";
                echo "</td>";

              }

              echo "</tr>";

          }
  
          ?>
        </tbody>
    </table>
  </div><!--ineer-->
  <?php } else {
    $op_link="<a href='add-domain.php'><button type='button' class='btn btn-primary'>";
    $clo_link="</button></a>";
    echo '<h4>' . sprintf(_("No existe ningún usuario postmaster todavía. Los usuarios postmaster se generan al activar un dominio.")) . '</h4> <h4>' . 
    sprintf(_("Puedes activar los dominios desde la página %sAñadir dominios%s"),$op_link, $clo_link) .'</h4>';

    }?>
</div><!--admin-content-->
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');
