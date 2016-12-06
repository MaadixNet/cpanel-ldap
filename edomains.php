<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

if(!$Ldap->is_logged_in())
{
	$Ldap->redirect('login.php');
}

require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
	$permissions= $_SESSION["login"]["level"];
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



//Add new domain
if(isset($_POST['adddomain'])){
    $webmaster=$_POST['seluser'];
    if($webmaster=='newuser'){
      $webmaster=$_POST['new_username'];
      $password = $_POST['webmaster_password'];
      $add_user=$Ldap->add_sftp_user($webmaster,$password);
      $message=$add_user['message'];

    }


    $values = $_POST["values"];
    $domain_new = $_POST["domain_new"];
    $syntax= check_syntax ('domain',$domain_new);
    $password = $_POST["password"];
    $entry["objectclass"][0]    = "top";
    $entry["objectclass"][1]    = "VirtualDomain";

    $entry["vd"]                = $domain_new;
    $entry["lastChange"]        = time();
    $entry["adminid"]           = $webmaster;
    // Merge static values with domain values

    $entry = array_merge($entry,$values["domain"]);

	######Create cn=Postmaster ###########3


    $entrypm = array();
    $entrypm["objectclass"][0]    = "top";
    $entrypm["objectclass"][1]    = "VirtualMailAlias";
    $entrypm["cn"] = "Postmaster";
    $entrypm["sn"] = "Postmaster";
    $entrypm["mail"] = "postmaster@".$domain_new;
    $entrypm["userPassword"]  =ldap_password_hash($password,'md5crypt');
    $entrypm["maildrop"] = "postmaster";
    $entrypm["accountActive"]     = "TRUE";
    $entrypm["creationDate"]      = date('Ymd');
    $entrypm["lastChange"]        = time();
    // Create abuse alias
    $entry_abuse["objectclass"][0]  = "top";
    $entry_abuse["objectclass"][1]  = "VirtualMailAlias";
    $entry_abuse["cn"] = "Abuse";
    $entry_abuse["sn"] = "Abuse";
    $entry_abuse["mail"] = "abuse@".$domain_new;
    $entry_abuse["maildrop"] = "postmaster";
    $entry_abuse["accountActive"] = "TRUE";
    $entry_abuse["creationDate"] = date('Ymd');
    $entry_abuse["lastChange"] = time();

    // iCheck Domain syntax
    if (!$syntax){
      $errorttpe="El dominio " . $domain_new . " no es válido";
    } else {
      //if syntax is ok add records	
      $addDomain=$Ldap->addRecord($ldapconn, 'vd='.$domain_new.','.LDAP_BASE, $entry);
      $addDomainpm=$Ldap->addRecord($ldapconn, 'cn=postmaster,vd='.$domain_new.','.LDAP_BASE, $entrypm);
      $addAbuse=$Ldap->addRecord($ldapconn,'mail=abuse@'.$domain_new.',vd='.$domain_new.','.LDAP_BASE,$entry_abuse); 
      }
    if ($addDomain && $addAbuse && $addDomainpm) {
       $message .= "
		<div class='alert alert-success'>
		<button class='close' data-dismiss='alert'>&times;</button>
		<strong>Dominio " . $domain_new ." añadido correctamente</strong> 
		</div>
        ";
      } else {
          $errorttpe 	= (ldap_errno($ldapconn)==68)?"El dominio " . $domain_new . " ya existe": $errorttpe;
          $message .=  "
		<div class='alert alert-error'>
		<button class='close' data-dismiss='alert'>&times;</button>
		<strong>Ha habido un error. " . $errorttpe ." </strong> 
		</div>
		";
      }
  }
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

//Modifiy Passord
if(isset($_POST['chpsw'])){
    //$ldapbind=$Ldap->bind($ldapconn, $_SESSION["login"]["dn"] , $_SESSION["login"]["password"]);
    //$modifydn='uid='. $_POST['userid']. ',' . $ldaptree;
	$domain=$_POST['domainid'];
	$modifydn='cn=postmaster,vd='.$domain.','.LDAP_BASE;
    #$info['userpassword'][0]="{MD5}".base64_encode(pack("H*",md5($_POST['changepsw'])));
	$info['userpassword'][0] =ldap_password_hash($_POST['changepsw'],'md5crypt');
    if($permissions==2) {
    $Ldap->modifyRecord($ldapconn, $modifydn, $info );
    } else {
        $modifs = [
    [
        "attrib"  => "userPassword",
        "modtype" => LDAP_MODIFY_BATCH_REMOVE,
        "values"  => [$_SESSION["login"]["password"]],
    ],
    [
        "attrib"  => "userPassword",
        "modtype" => LDAP_MODIFY_BATCH_ADD,
        "values"  => [$info['userpassword'][0]],
    ],
];
    ldap_modify_batch($ldapconn, $modifydn, $modifs);
    }
}

//Query domains in database
if ($ldapbind) {
    $result=$Ldap->search($ldapconn,$binddn, $filter);
}

?>
<div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <?php if($_SESSION["login"]["level"] == '10'){//Only admin can add Domains 
    ?>
    <span><button class="togglevisibility btn btn-small btn-secondary">Añadir dominio</button>  </span>
    <div class="clear"></div>
    <div id="change">
    <form autocomplete="off" action="#" method="POST" class="form-signin">
        <hr>
            <label for="domain">Nombre de Dominio </label><p class="little">(Inserta un nombre de dominio válido. Para los dominios activado en este panel podrás crear cuentas de correo electrónico o páginas web)</p><input id="domain_new" type="text" name="domain_new" required />
            <label for="password">Contraseña: </label><p class="little">Esta contraseña se puede utilizar para acceder a este mismo panel de control como administrador del dominio identificándose con <b>User:</b> <em>Nombre Dominio</em> <b>Contraseña: </b><em>La que insertes en este campo</em>. El administrador de dominio tiene privilegios límitados y sólo podrá crear, editar y borrar las cuentas de cooreo electrónico asociadas a su dominio. No podrá en ningún caso acceder a ninguna otra función y no podrá eliminar el dominio</p><input id="password" type="password" name="password" required />
            <label for="webmaster">Webmaster (Administrador sito web) </label><p class="little">Por cada dominio que actives en este panel se creará una carpeta en la que puedes subir tu aplicación web, accesible desde un navegador. Pudedes permitir que un usuario concreto tenga acceso a la carpeta para que pueda editar sus archivos. Puedes elegir un usuario ya creado o crear uno nuevo. Si no asignas ningún usuario solo podrá editar los archivos el usuario por defecto del sistema, tanto por ssh como por sftp</p>
             <?php 
            $ldaptree    = LDAP_PEOPLE;
            $filter="(&(objectClass=person)(uid=*)(authorizedService=sshd))";
            $filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";
            $allusers=$Ldap->search($ldapconn,$ldaptree, $filter);
            $sudouser=$Ldap->search($ldapconn,$ldaptree, $filtersudo);

            // This is the default user which has sudo and will be the default owner
            // in case no webmaster is selected or created
            $sudo_username=$sudouser[0]["uid"][0];

            //default webmaster will be user with sudo (uid 10000);
            echo '<select id="seluser" name="seluser">';
            echo '<option value="' . $sudo_username .'">Seleccionar Administrador web</option>';
            echo '<option value="' . $sudo_username .'">' . $sudo_username .' - Usuario por defecto</option>';
            echo '<option value="newuser">Crear nuevo usuario</option>';
            for ($c=0; $c<$allusers["count"]; $c++) {
            $usernames = $allusers[$c]["uid"][0];
            echo '<option value="' . $allusers[$c]["uid"][0] .'">' . $allusers[$c]["uid"][0] . '</option>';
                  }
            echo '</select>';
            echo '<div class="newuser" id="new_user" style="display:none;">';
            echo '<label for="new_username">Nombre de usuario</label>';
            echo '<input id="new_username" type="text" name="new_username" />';
            echo '<label for="webmaster_password">Contraseña: </label><input id="webmaster_password" type="password" name="webmaster_password" />';
            echo '</div>';?>
            <input class="form-control" type="hidden" name="values[domain][maxmail]" value="100">
            <input class="form-control" type="hidden" name="values[domain][maxalias]" value="100">
            <input class="form-control" type="hidden" name="values[domain][maxquota]" value="100">
            <input class="form-control" type="hidden" name="values[domain][accountactive]" value="TRUE">
            <input class="form-control" type="hidden" name="values[domain][editav]" value="TRUE">
            <input class="form-control" type="hidden" name="values[domain][delete]" value="FALSE">
            <input class="form-control" type="hidden" name="values[mail][editaccounts]" value="TRUE">
            <input class="form-control" type="hidden" name="values[domain][postfixtransport]" value="maildrop:">
            <input type="submit" name="adddomain" value="Guardar" class="btn btn-small btn-primary" />

            </form>
    </div><!--change-->
    <?php } ?>
    <div class="row">

	<div class="inner"i id="maincol">
  		<table>
        <thead>
        <tr>
            <th>Dominio</th>
            <th>Contraseña Cpanel</th>
            <th>Cuentas email </th>
            <th>Administrador web</th>
            <th>DNS</th>
            <th>Status</th>
             <?php if($_SESSION["login"]["level"] == '10') echo '<th>Borrar</th>';//Only admin can delete Domains  ?>
        </tr>
        </thead>
        <tbody>

<?php
    if($result["count"]>0){
    for ($i=0; $i<$result["count"]; $i++) {
        $domain= $result[$i]["vd"][0];
        $resultmail=$Ldap->search($ldapconn,'vd='. $domain . ','. LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');  
        /*$haschildren= ($resultmail>0) ? '<i class="arrowleft"></i><i class="arrowdown"></i>':'';
        echo '<li>';    
        echo '<h4 class="arrow">' . $haschildren . ' ' .$domain .'</h4>';
            if($resultmail>0) {
            echo '<ul class="sub-menu">';
            for ($c=0; $c<$resultmail["count"]; $c++) {
            echo '<li>' . $resultmail[$c]["mail"][0] . '</li>';
            }
            echo '</ul>';
        }
        echo '</li>';
        }
    echo '</ul>';

*/


        echo "<tr>";
        echo "<td>";
        echo $domain;
        echo "</td>";
        echo "<td>";
        $domainpass=$Ldap->search($ldapconn, 'vd='.$domain.','.LDAP_BASE, '(cn=postmaster)');
        $oldpass =  $domainpass[0]['userpassword'][0];
        echo "<a class='showform'>Cambiar Contaseña</a>";
        echo "<form action='#' method='POST' class='form-table sub-form' autocomplete='off'><input id='changepsw' type='password' name='changepsw' /><input type='hidden' name='domainid' value='" . $domain . "' /><input type='submit' name='chpsw' value='Cambiar' class='btn btn-small btn-primary' /></form>";
        echo "</td>";
        echo "<td>";
        echo "<a href='/". BASE_PATH ."/mails.php?domain=" . $domain ."'>Administrar cuentas de correo</a> ";
        echo "</td>";
        echo "<td>";
        echo $result[$i]["adminid"][0];
        echo "</td>";
        echo "<td class='center'>";
        echo "<a href='editdns.php?domain=" . $domain ."'>Ver</a>";
        echo "</td>";
        echo "<td class='center'>";
        $status=(file_exists('/etc/apache2/ldap-enabled/' . $domain .'.conf'))?$statok:$loading;
        echo $status;
        echo "</td>";
        if($_SESSION["login"]["level"] == '10') {
            echo "<td>";
            echo "<form action='' method='POST' class='form-table'><input type='hidden' name='domainid' value='". $domain."' /> <input type='submit' name='deldomain' value='Eliminar' class='btn btn-small btn-primary'  onclick=\"return confirm('Quieres borrar el dominio " . $domain ."? Si Aceptas borrarás todo el contenido relacionado con el mismo:  incluidas todas las cuentas de correo electrónico creadas para este dominio así como su contenido (bandeja de euntrada,bandeja de salida, borradores, etc etc)');\" /></form>";
			/*echo "<form action='#' method='POST' class='form-table'><input type='hidden' name='domainid' value='". $domain."' /> <input type='submit' name='deldomain' value='Eliminar' class='btn btn-small btn-primary confirm' onclick=\"return alertify.confirm('Confirm Message', function(){ alertify.success('Ok') }, function(){ alertify.error('Cancel')})\"  /></form>";	*/
          echo "</td>";
        }

        echo "</tr>";

    }
  }
?>
        </tbody>
    </table>
  </div><!--ineer-->

</div><!--row-->
<?php
?>
</div><!--admin-content-->
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');?>
<script type="text/javascript">
    $(function() {
        $('#seluser').change(function(){
            $('#new_user').hide();
            $('.' + $(this).val()).show();
        });
    });
</script>

