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
$ldapconn=$Ldap->connect();
if ($ldapconn){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]); 
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
	$values = $_POST["values"];
    $domain_new = $_POST["domain_new"];
	$syntax= check_syntax ('domain',$domain_new);
    $password = $_POST["password"];
    $entry["objectclass"][0]    = "top";
    $entry["objectclass"][1]    = "VirtualDomain";

    $entry["vd"]                = $domain_new;
    $entry["lastChange"]        = time();
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
	   $message = "
		<div class='alert alert-success'>
		<button class='close' data-dismiss='alert'>&times;</button>
		<strong>Dominio " . $domain_new ." añadido correctamente</strong> 
		</div>
        ";
	} else {
		$errorttpe 	= (ldap_errno($ldapconn)==68)?"El dominio " . $domain_new . " ya existe": $errorttpe;
	   	$message=  "
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
        <strong>Dominio " . $domain_new ." eliminado</strong> 
        </div>
        ";
    } else {
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>Ha habido un error.</strong> 
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
            <label for="domain">Nombre de Dominio </label><p class="little">(Activa un nuevo dominio para poder crear cuentas de correo)</p><input id="domain_new" type="text" name="domain_new" required />
            <label for="password">Contraseña: </label><input id="password" type="password" name="password" required />
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
            <th>Contraseña para Administrador de dominio </th>
			<th>Cuentas email </th>
			<th>DNS</th>
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

        echo "</td>";
        echo "<td>";
		echo "<a href='/cpanel/mails.php?domain=" . $domain ."'>Administrar cuentas de correo</a> ";
        echo "</td>";
		echo "<td>";
		echo "<a href='editdns.php?domain=" . $domain ."'>Ver</a>";
        if($_SESSION["login"]["level"] == '10') {
            echo "<td>";
	        echo "<form action='#' method='POST' class='form-table'><input type='hidden' name='domainid' value='". $domain."' /> <input type='submit' name='deldomain' value='Eliminar' class='btn btn-small btn-primary'  onclick=\"return confirm('Quieres borrar el dominio " . $domain ."? Si Aceptas borrarás todo el contenido relacionado con el mismo:  La carpeta /var/www/html/webistes/". $domain." y también todas las cuentas de correo electrónico creadas para este dominio así como su contenido (bandeja de euntrada,bandeja de salida, borradores, etc etc)');\" /></form>";
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
