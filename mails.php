<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$message='';
require_once('header.php');
//connect and BInd
$psw=$Ldap->decrypt_psw();
$ldapconn=$Ldap->connect();
if ($ldapconn){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,  $psw); 
	#TODO: Check user level to show and allow differents permissions
	#Level 10= admin : can read and manage all accounts
	#Level 4 postmaste (domain administratod) can read and edit all accounts related to his domain
	#level 2 : simple user. Can read and edit only his own data
	#need LDAP ACL to be set
                        
	switch ($permissions) :
	case "10" :
		$binddn=LDAP_BASE;
		$filter="(vd=*)";
		$col=10; //when there is left sidebar
	break;
        case "4" :
                //$binddn='vd=gadix.net,o=hosting,dc=example,dc=tld';
		$who=$_SESSION["phamm"]["domain"];
		$filter="(vd=" . $who .")";
                $binddn='vd=' . $who . ',o=hosting,dc=example,dc=tld';
		$col=12; // for fullwidth page -no sidebar
		
	break;
	case "2":
		$who= $_SESSION['login']["username"];
		$binddn=$_SESSION["login"]["dn"];
		$filter="";
		$col=12;
	break;

	default:
	break;
	endswitch;
 
//Modifiy Passord
	if(isset($_POST['chpsw'])){
		$domain=$_POST['domainid'];
		$mailaccount = $_POST['mailaccount'];
		$modifydn='mail=' . $mailaccount . ',vd='.$domain.','.LDAP_BASE;
		//$info['userpassword'][0]="{MD5}".base64_encode(pack("H*",md5($_POST['changepsw'])));
		$info['userpassword'][0]=ldap_password_hash($_POST['changepsw'], 'ssha');
		//if($permissions==10) {
		#TODO: Allow lower level users to change his own password
		#User will need to be logged out in order to be able to bind again
		$mod_result=$Ldap->modifyRecord($ldapconn, $modifydn, $info );
                $message=$mod_result["message"];
	}

	//Add mail accounts
	if (isset($_POST['adduser'])){
		$mail_new = $_POST['mailnew'];
		$syntax = check_syntax('account',$mail_new);
		if (!$syntax){
			$message='
			<div class="alert alert-error">
			<button class="close" data-dismiss="alert">×</button>
			<strong>Nombre no válido para cuenta de correo</strong>
			</div>';
			} else {

		$mail_domain = $_POST['maildomain'];
		$mail_account= $mail_new . '@' . $mail_domain;
		$password = $_POST["password"];
		$entry = array();
		$entry["objectclass"][0] = "top";
		$entry["objectclass"][1] = "VirtualMailAccount";
		$entry["objectclass"][2] = "Vacation";
		$entry["objectclass"][3] = "VirtualForward";
		$entry["objectclass"][4] = "amavisAccount";
		$entry["lastchange"][0]	= time();
		$entry["creationDate"]      = date('Ymd');
		$entry["accountActive"]     = "TRUE";
		$entry["amavisspamtaglevel"] = "3.0";
		$entry["amavisspamtag2level"] = "5.5";
		$entry["amavisspamkilllevel"] = "6.0";
		$entry["amavisbypassviruschecks"]     = "TRUE";
		$entry["amavisbypassspamchecks"] = "FALSE" ;
		$entry["forwardactive"] = "FALSE" ;
		$entry["vacationactive"] = "FALSE" ;
		$entry["smtpauth"] = "TRUE";
		$entry["delete"] = "FALSE";
		$entry["mailautoreply"] = $mail_account;
		$entry["uid"] = $mail_new  . '.' . $mail_domain;
		$entry["mailbox"] = $mail_domain .'/' . $mail_new;
		$entry["vdhome"] = '/home/vmail/domains';
		$entry["quota"] = '0';
		$entry["mail"] = $mail_account;
		$entry["userPassword"] = ldap_password_hash($password, 'md5crypt');
		$entry["sn"] = $_POST["surname"];
		$entry["givenname"] = $_POST["givenname"];
		$entry["cn"] = $_POST["givenname"] .' '. $_POST["surname"];
		$maildn='mail=' . $mail_account .',vd=' . $mail_domain .','. LDAP_BASE;
		$addAccount=$Ldap->addRecord($ldapconn,$maildn,$entry);

	if ($addAccount) {
		$message='
		<div class="alert alert-success">
		<button class="close" data-dismiss="alert">×</button>
		<strong>Usario añadido correctamente</strong>
                </div>';
                $fqdn=shell_exec('hostname -f');
                $body='Bienvenido a tu nuevo buzón.' . "\r\n";
                $body .='Por favor, no contestes a este mensaje.';
                $to=$mail_account;
                $from='no-replay@' . $fqdn;
                $subject='Bienvenido';
                $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
                $cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $cabeceras .= 'From: no-replay@' . $fqdn . "\r\n"; 
                mail($to,$subject,$body,$cabeceras); 
		} else {
		$errorttpe  = (ldap_errno($ldapconn)==68)?"La cuenta " . $mail_account . " ya existe": $errorttpe;
		$message = '
		<div class="alert alert-error">
		<button class="close" data-dismiss="alert">×</button>
		<strong>Ha ocurrido un error. ' . $errorttpe . '</strong>
		</div>';
		}
	}
}

//delete user
	if(isset($_POST['deluser'])){
		$mail_account= $_POST['userid'];
		$mail_domain = $_POST['domain']; 
		$deletedn='mail=' . $mail_account .',vd=' . $mail_domain .','. LDAP_BASE;
		$delAccount=$Ldap->deleteRecord($ldapconn, $deletedn, $recursive = false);
		if ($delAccount) {
			$message='
			<div class="alert alert-success">
			<button class="close" data-dismiss="alert">×</button>
			<strong>Cuenta '. $mail_account .' eliminada</strong>
			</div>';
			} else {
			$message = '
			<div class="alert alert-error">
			<button class="close" data-dismiss="alert">×</button>
			<strong>Ha ocurrido un error. La cuenta no se ha podido eliminar</strong>
			</div>';
			}	
		}

  if ($ldapbind) {
      $result=$Ldap->search($ldapconn,$binddn, $filter);
  }
}
require_once('sidebar.php');
?>
<article class="content forms-page">
  <section class="section">
<?php 
  # Check if the domain has correct MX Recodrs for this server
  #
  $queryvar=(isset($_GET['domain']))?$_GET['domain'] :'';

  $this_domain='(vd='. $queryvar . ' )';
  $domain_exist_in_ldap= $Ldap->search($ldapconn,LDAP_BASE, $this_domain);

  $domain_dns=(!empty($queryvar))?check_domain_dns($queryvar):'';
  $dns_result=$domain_dns["result"];

  # Check if rainloop is installed
  $has_webmail = $Ldap->check_installed_service('rainloop');

  #
  # If MX records for this domain are not correct for this server
  # inform user that it's not possible to create or manage accounts
  # but is still possible to use webmail interface to read and write emails
  #

  if ($dns_result===2 && (!empty($queryvar)) && $has_webmail && $domain_exist_in_ldap["count"]>0){
    printf(_("<pre><p>Los DNS del dominio %s no están configurados para que el correo electrónico sea administrado por este servidor. Esto significa que no puedes crear cuentas de email desde este panel. Revisa su configuración en:<a href=' https://client-area.maadix.net/cpanel-ldap/editdns.php?domain=%s'>Configuración de DNS activa para el dominio %s</a>.
      </br>
      Sin embargo, si tienes alguna cuenta de correo electrónico existente para este dominio puedes consultarla desde la aplicación <a href='rainloop.php'>Webmail</a>.</p></pre>
      "), $queryvar);
  } else { ?>
    <div class="row">
      <div class="col-sm-12">


                 <?php   echo $message;
                         if($permissions > 2){
                         # This is only for postmaster or admin. Normal user will only be able to see his own email account
                         $queryvar=(isset($_GET['domain']))?$_GET['domain'] :'';
                         $querymess=($queryvar)?'para el dominio <br>' . $queryvar:'';?>


		<?php if ($permissions ==10) {//Show domains list on left sidebar only to admin ?> 
        <div class="col-sm-3">
          <div class="title-block">
            <h3 class="title"> <?php printf(_("Dominios Activados"));?></h3>
          </div>
            <div id="sidebarleft" class="inner">

				<?php
					if($result["count"]>0){
					$queryvar=(isset($_GET['domain']))?$_GET['domain'] :'';
					echo '<ul id="menu-main-menu-m" class="menul">';
					for ($i=0; $i<$result["count"]; $i++) {
						$domain = $result[$i]["vd"][0];
						$active = ($queryvar == $domain)?'active':'';
						if (!$queryvar && $i==0) $queryvar = $domain;
						$resultmail = $Ldap->search($ldapconn,'vd='. $domain . ','. LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');  
						$mailcount = $resultmail["count"];
						$haschildren = ($mailcount>0) ? '<i class=" arrow arrowleft"></i><i class="arrow arrowdown"></i>':'';
						echo '<li class="' .  $active . '">';	
						echo '<h5>' . $haschildren . ' <a class=" ' .  $active . '"   href="/' .  BASE_PATH . '/mails.php?domain=' . $domain . '">' . $domain .'</a> <span class="counter">' . $mailcount . '</span></h5>';
						if($mailcount>0) {
							echo '<ul class="sub-menu">';
							for ($c=0; $c<$resultmail["count"]; $c++) {
                                                          echo '<li>' . $resultmail[$c]["mail"][0] . '</li>';
                                                        }
							echo '</ul>';
						}
						echo '</li>';
                                                echo '<hr>';
                                            }
					echo '</ul>';
					}?>
                            <br>                    
			</div><!--sidebar-left-->
		</div><!--col-sm.2-->
		<?php } ?>

        <div class="col-sm-9">
          <div class="title-block">
            <h3 class="title"> <?php printf(_("Cuentas de correo activadas %s"),$querymess);?></h3>
          </div>

            <div class="inner" id="maincol">
                <div class="form-group">
                <span><button class="togglevisibility btn btn-small btn-secondary"><?php printf(_("Añadir cuenta"));?></button>  </span>
                </div>
    		<div class="clear"></div>
    		<div id="change">
                  <div class="card card-block">
			<?php if($result["count"] == 0){
				echo '<h3>Para poder crear cuentas de correos tienes que activar antes el dominio correspondiente en la página <a href="add-domain.php">Añadir Dominios</h3>';
			} else { ?>
                              
   	 			<form autocomplete="off" action="" method="POST" class="form-signin standard">

                                <div class="form-group">
                                  <label for="username">Email de usuario: </label>
                                  <p></p>
                                  <div class="row">
                                    <input class="form-control col-sm-4" id="mailnew" type="text" name="mailnew" required />
                                    <?php
                                    if($result["count"] == 1){
					echo '<input type="hidden" name="maildomain" value="' .$result[0]["vd"][0] .'" />';
					echo '<span class="inline">@' . $result[0]["vd"][0] .'</span>';
                                    } else {
                                        echo '<span class="inline">@<select id="seldomain" name="maildomain" style="display:inline" required>';
                                        echo '<option value="">Seleccionar dominio</option>';
                                        for ($c=0; $c<$result["count"]; $c++) {
                                          $selected=($queryvar==$result[$c]["vd"][0])?"selected":"";
                                          # If a domain is recorded in Ldap but MX record is
                                          # not correct , show it but as  disabled
                                          # and not selected

                                          $domain_dns_status=check_domain_dns($result[$c]["vd"][0]);
                                          $domain_MX=$domain_dns_status["result"];
                                          if ($domain_MX===2){
                                            $selected='';
                                            $disabled='disabled';
                                          }
                                          echo '<option ' . $selected . ' ' . $disabled . '  value="' . $result[$c]["vd"][0] .'">' . $result[$c]["vd"][0] . '</option>';
                                        }
                                        echo '</select></span>';
                                    };?>
                                  </div><!--row-->
                                </div><!--form-group-->


                               <div class="form-group">   
                                  <label for="password"><?php printf(_("Contraseña"));?> </label><input class="form-control" d="password" type="password" name="password" required />
                                </div>

                                <div class="form-group">
                                  <label for="givenname"><?php printf(_("Nombre"));?> </label><input class="form-control" id="givenname" type="text" name="givenname" required />
                                </div>

                                <div class="form-group">
                                  <label for="surname"><?php printf(_("Apellidos"));?></label><input id="surname" type="text" name="surname" class="form-control" required />
                                </div>
                                <br>
                                <input type="submit" name="adduser" value="Guardar" class="btn btn-small btn-primary" />
                                </form>
                                <br>
                        <?php 
			} //end if permissions > 2
		} //end if domain not =  0?>
                  </div><!--card-block-->
                </div><!--change-->

			<?php
			if ($permissions == 4 ){
				## Postmaster can only see mails assoicated to his domain
				$domain=$result[0]["vd"][0];
				$resultmail=$Ldap->search($ldapconn,'vd='. $domain . ','. LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))'); 
			} elseif ($permissions ==  2 ){
				 $resultmail=$result;
						
			} else {
				$queryvar=($queryvar)?'vd='. $queryvar. ',':'';
				# Show a full list of email accounts for all domains
				$resultmail=$Ldap->search($ldapconn,$queryvar . LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');
			}
			if($resultmail>0) { 
				echo '<table id="email">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>' . sprintf(_("Correo electrónico")) . '</th>';
				echo '<th>' . sprintf(_("Nombre")) . '</th>';
                                echo '<th>' . sprintf(_("Editar")). '</th>';

                                if($_SESSION["login"]["level"] != 2) echo '<th>Borrar</th>';//Only admin or postamster can delete email account
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';

	
                                  for ($c=0; $c<$resultmail["count"]; $c++) {
                                        $queryvar=(explode("@",$resultmail[$c]["mail"][0]));
                                        $domain=$queryvar[1];
                                        echo "<tr>";
                                        echo "<td>";
                                        echo $resultmail[$c]["mail"][0];
                                        echo "</td>";
                                        echo "<td>";
                                        echo html_entity_decode($resultmail[$c]["cn"][0]);
                                        echo "</td>";
                                        echo "<td>";
                                        echo "<a href='edit-mail.php?mail=". $resultmail[$c]["mail"][0] ."'><button class='btn btn-small'><i class='fa fa-cogs' aria-hidden='true'></i> ". sprintf(_('Ver | Editar')) ."</button></a>";
                                        echo "</td>";
                                        if($permissions > 2) { //a normal user cannot deñlete his own account
                                        echo "<td>";
                                        echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-domain="' . $domain .'" data-language="' . $_SESSION["language"] .'" data-target="#mailModal" data-email="' . $resultmail[$c]["mail"][0] .  '">' . sprintf (_('Eliminar')) . '</button>';
                                        echo "</td>";
                                        }//end permissions >2
                                  echo '</tr>';


                          }
			echo '</tbody>';
			echo '</table>';
            }

?>

            </div><!--ineer-->
        </div><!--col-sm-9-->
<!-- Modal -->
<div class="bd-example">
  <div class="modal fade" id="mailModal" tabindex="-1" role="dialog" aria-labelledby="mailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
           <h4 class="modal-title" id="mailModalLabel"></h4>
        </div>
        <div class="modal-body" id="modal-body">
        </div>
      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->

<?php } ?>
          </div> <!--class="col-sm-12"-->
	</div><!--row-->
<?php
?>
  </section>
</article>
<?php
	ldap_close($ldapconn);   
	require_once('footer.php');?>

