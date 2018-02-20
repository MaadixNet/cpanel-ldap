<?php 
session_start();
require_once('header.php');
/* Set some variable to show in table, for domain statuses
 *
*/
$errorttpe="";
$message="";
// show the vhostb domain status
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

/*Show the mail server status. It will be active if email is managed by
 * This server. Inactive to deliver email to qan external server
 *
*/

$mail_active= "<span class='isservice hasaccess'>" . sprintf(_("Activado")) . "</span>";
$mail_inactive= "<span class='isservice noaccess'>" . sprintf(_("Desactivado")) . "</span>";
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
//delete domain 
if(isset($_POST['deldomain'])){
    //$ldapbind = $Ldap->bind($ldapconn, BINDDN , $_SESSION["login"]["password"]);
    $deletedn = 'vd='. $_POST['domainid']. ',' . LDAP_BASE;
    $deldomain = $Ldap->deleteRecord($ldapconn, $deletedn, $recursive = true);
	if ($deldomain){
       $message = "
        <div class='alert alert-success'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>" . sprintf(_("Dominio %s eliminado"), $domain_new ) . "</strong>
        </div>
        ";
    } else {
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>" . sprintf(_("Error")) . "</strong> 
        </div>
        ";
    }

}


if ($ldapbind) {
    //Query domains in database
    $result=$Ldap->search($ldapconn,$binddn, $filter);

    //Get a list of all available usrs wu¡ith sftp access
    $filtersftp="(&(objectClass=person)(uid=*)(authorizedService=sshd))";
    $ldaptree    = LDAP_PEOPLE;
    $allsftpusers=$Ldap->search($ldapconn,$ldaptree, $filtersftp);

}
require_once('sidebar.php');
?>
<div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">

        <?php 
    if($result["count"]>0){
      ?>
        <table id="domains">
        <thead>
        <tr>
        <th><?php printf(_("Dominio"));?></th>
        <th><?php printf(_("Servidor de correo"));?></th>
        <th><?php printf(_("Cuentas de correo"));?></th>
        <th><?php printf(_("Servidor web"));?></th>
        <th><?php printf(_("Administrador web"));?></th>
        <th><?php printf(_("DNS"));?></th>
            <?php if($_SESSION["login"]["level"] == '10') {

              echo '<th>' .sprintf (_('Editar')) . '</th>';
              echo '<th>' . sprintf (_('Eliminar')) . '</th>';//Only admin can edit or delete Domains  

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
        echo $domain;
        echo "</td>";
        echo "<td class='center'>";
        $mail_server = ($result[$i]["accountactive"][0] == "TRUE")?$mail_active:$mail_inactive;
        echo $mail_server;
        echo "</td>";
        echo "<td>";
        echo "<a href='/". BASE_PATH ."/mails.php?domain=" . $domain ."'>" . sprintf(_("Administrar correo")) . "</a>";
        echo "</td>";
        echo "<td class='center domainstatus' data-domain='" . $domain . "'>";
        $status=(file_exists('/etc/apache2/ldap-enabled/' . $domain .'.conf'))?$statok:$loading;
        echo $status . '<span id="domainstatus"></span>';
        echo "</td>";
        $webmaster = trim($result[$i]["adminid"][0]);
        echo "<td data-domain='" . $domain . "' data-webmaster='" . $webmaster . "' class='webmasterstatus'>";
        //echo $result[$i]["adminid"][0];
        $current_admin=trim(shell_exec("find /var/www/html/$domain -maxdepth 0 -printf '%u\n'"));
        $ownersip=($current_admin==$webmaster?$statok:$loading);
        echo $webmaster . '&nbsp;&nbsp; ' . $ownersip . ' <span id="ownershipstatus"></span>';
        echo "<td class='center'>";
        echo "<a title='Ver la configuración de los DNS para " . $domain ."' href='editdns.php?domain=" . $domain ."'>" . sprintf(_("Ver")) . "</a>";
        echo "</td>";
        if($_SESSION["login"]["level"] == '10') {
            echo "<td>";
            echo "<a href='edit-domain.php?domain=". $domain ."'><button class='btn btn-small'><i class='fa fa-cogs' aria-hidden='true'></i> ". sprintf(_('Editar')) ."</button></a>";
            echo "</td>";

            echo "<td>";
            echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-domain="' . $domain .  '">' . sprintf (_('Eliminar')) . '</button>';
          echo "</td>";
        }

        echo "</tr>";

    }
  
?>
        </tbody>
    </table>
  </div><!--ineer-->
<?php
    } else {
        $add_link="<a href='add-domain.php'><button type='button' class='btn btn-primary'>"; 
        $close_link="</button></a>";

        echo "<h5>";
        printf(_("No hay ningún dominio activado."));
        echo "</h5> <h5>";
        printf(_("Puedes activar cualquier dominio desde la página %sAñadir dominios%s"),$add_link,$close_link);
        echo "</h5>";
      }?>
<!-- Modal -->
<div class="bd-example">
  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="exampleModalLabel"></h4>
        </div>
        <div class="modal-body" id="modal-body">
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->
</div><!--row-->
<?php
?>
</div><!--admin-content-->
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');?>
