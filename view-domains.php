<?php 
session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

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

if (isset($_POST["chadmin"])){
    $webmaster=$_POST['seladmin'];
    $domainid = $_POST['domainid'];
    $entry["adminid"] = $webmaster;    
    $modifydn="vd=" . $domainid ."," . LDAP_BASE;
    $chadmin = $Ldap->modifyRecord($ldapconn, $modifydn, $entry );
    if ($chadmin){
      $message="
        <div class='alert alert-success'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>" . sprintf(_("Cambio registrado con éxito")) . "</strong>
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

if ($ldapbind) {
    //Query domains in database
    $result=$Ldap->search($ldapconn,$binddn, $filter);

    //Get a list of all available usrs wu¡ith sftp access
    $filtersftp="(&(objectClass=person)(uid=*)(authorizedService=sshd))";
    $ldaptree    = LDAP_PEOPLE;
    $allsftpusers=$Ldap->search($ldapconn,$ldaptree, $filtersftp);

}

?>
<div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">
        <table id="domains">
        <thead>
        <tr>
            <th>Dominio</th>
            <th>Cuentas email </th>
            <th>Administrador web</th>
            <th>DNS</th>
            <th>Activado</th>
            <?php if($_SESSION["login"]["level"] == '10') {

              echo '<th>' .sprintf (_('Editar')) . '</th>';
              echo '<th>' . sprintf (_('Eliminar')) . '</th>';//Only admin can edit or delete Domains  

            }?>
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
        echo "<a href='/". BASE_PATH ."/mails.php?domain=" . $domain ."'>Administrar cuentas de correo</a> ";
        echo "</td>";
        echo "<td>";
        //echo $result[$i]["adminid"][0];
        $current_admin=trim(shell_exec("find /var/www/html/$domain -maxdepth 0 -printf '%u\n'"));
        $webmaster = trim($result[$i]["adminid"][0]);
        $ownersip=($current_admin==$webmaster?$statok:$loading);
        echo $webmaster . '&nbsp;&nbsp; ' . $ownersip;
        echo "<td class='center'>";
        echo "<a href='editdns.php?domain=" . $domain ."'>Ver</a>";
        echo "</td>";
        echo "<td class='center'>";
        $status=(file_exists('/etc/apache2/ldap-enabled/' . $domain .'.conf'))?$statok:$loading;
        echo $status;
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
  }
?>
        </tbody>
    </table>
  </div><!--ineer-->

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

