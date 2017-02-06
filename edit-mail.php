<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
$message='';
$permissions=$_SESSION["login"]["level"];
//connect and BInd
$email=(isset($_GET["mail"]))?$_GET["mail"]:'';
$domain=end(explode('@', $email));
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
		$binddn='vd=' . $domain . ',' . LDAP_BASE;
		$filter="(mail=" . $email . ")";
	break;
        case "4" :
                //$binddn='vd=gadix.net,o=hosting,dc=example,dc=tld';
		$who=$_SESSION["phamm"]["domain"];
		$filter="(mail=" . $email .")";
                $binddn='vd=' . $who . ',o=hosting,dc=example,dc=tld';
		
	break;
	case "2":
		$email= $_SESSION['login']["username"];
                $domain=end(explode('@', $email));
		$binddn=$_SESSION["login"]["dn"];
		$filter="(mail=" . $email . ")";
	break;

	default:
	break;
	endswitch;
 

	//Modify  mail accounts
	if (isset($_POST['editmail'])){


                $entry = array();
                $psw1=$_POST['pswd1'];
                $psw2=$_POST['pswd2'];
              

                if ((!empty($psw1)) && (!empty($psw2)) && ($psw2==$psw1) ) {
                      $newpass=ldap_password_hash($psw2, 'ssha');
                      $entry['userpassword']=$newpass;
//                      $entry['shadowlastchange'] = floor(time()/86400);
                      $redirect=1;
                }


		$entry["lastchange"] = time();
                $forward=(isset($_POST["forward"]))?"TRUE":"FALSE";
                $entry["forwardactive"]=$forward;
                $entry["maildrop"]=($forward=="TRUE")?$_POST["maildrop"]:"";
		$entry["sn"] = $_POST["surname"];
		$entry["givenname"] = $_POST["givenname"];
                $entry["cn"] = $_POST["givenname"] .'&nbsp;'. $_POST["surname"];
		$maildn='mail=' . $email .',vd=' . $domain .','. LDAP_BASE;
		$mod = $Ldap->modifyRecord($ldapconn,$maildn,$entry);
                $message=$mod["message"];
                $mod_success=$mod["result"];
                if($mod_success=="true"  && $permissions==2 &&  $redirect==1){
                  $Ldap->logout();
                  $Ldap->redirect('login.php?chpsw=ok');
                }

	}

//delete user
        /*
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
			<strong>Ha ocurrido un error. La cuenta no se ha podido eliminar>/strong>
			</div>';
			}	
		}
        */
	if ($ldapbind) {
            $result=$Ldap->search($ldapconn,$binddn, $filter);
            /*echo '<pre>';
            var_dump ($result);
            echo '</pre>';*/
        }
require_once('header.php');
}?>

<div id="admin-content" class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="inner" id="maincol">
              <div class="col-sm-9">
                <?php
                echo $message; 
                $forward = $result[0]["forwardactive"][0];
                //if Forward is activated set the checkbox as checked
                //and show hidden field with  maildrop which will be
                //a required field

                $checked = ($forward=="TRUE")?'checked="checked"':'';
                //$required = ($forward=="TRUE")?'required':'';
                $maildrop=(isset($result[0]["maildrop"][0]))?$result[0]["maildrop"][0]:'';
                echo '<h4>' . sprintf (_("Editar cuenta de correo %s") , $email) .  '</h4>';?>
                <div class="clear"></div>

                    <form autocomplete="off" action="" method="POST" class="form-signin">
                        <hr>
                        <label for="pswd1"><h4><?php printf(_("Nueva contraseña"));?></h4></label>
                        <div id="pswcheck"></div>
                        <input id="pswd1" type="password" name="pswd1" readonly />

                        <label for="pswd2"><h4><?php printf(_("Repetir nueva contraseña"));?></h4></label>
                        <input id="pswd2" type="password" name="pswd2" />
                        <div id="pswresult"></div>          
                        
                        <label for="givenname"><h4><?php echo  sprintf(_("Nombre"));?></h4></label>
                        <input id="givenname" name="givenname" type="text" value="<?php echo $result[0]['givenname'][0];?>" required />

                        <label for="surname"><h4><?php printf(_("Apellidos"));?></h4></label>
                        <input id="surname" type="text" name="surname" value="<?php echo $result[0]["sn"][0];?>" required />
                      
                        <h4><?php printf(_("Reenvío automático"));?></h4>
                        <input type="checkbox" name="forward" id="forward" <?php echo $checked;?> />
                        <label class="togglehidden" for="forward">&nbsp;</label>

                        <div id="hidden">
                          <label for="maildrop"><h4><?php printf(_("Cuenta de destino para reenvío automático"));?></h4></label>
                          <input id="maildrop" class="usermail" type="mail" name="maildrop" value="<?php echo $maildrop;?>"  />
                          <div id="emailresult"></div>
                        </div>

                        <br>
                        <hr>
                        <input type="hidden" name="domain" value="<?php echo $domain;?>" />
                        <input type="submit" name="editmail" value="Guardar" class="btn btn-small btn-primary" />
                  </form>
              </div>
              <div class="col-sm-3">
                <?php print_mail_client_settings($email,$domain);?>
              </div>
            </div><!--ineer-->
        </div><!--col-sm-8-->
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

	</div><!--row-->
<?php
?>
</div><!--admin-content-->
<?php
	ldap_close($ldapconn);   
	require_once('footer.php');?>

