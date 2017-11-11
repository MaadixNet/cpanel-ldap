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
                $vacation= (isset($_POST["vacationactive"]))?"TRUE":"FALSE";
                $entry["vacationactive"]=$vacation;
                $entry["mailautoreply"] = $email.'.autoreply';
                $entry["vacationinfo"]= (isset($_POST["vacationinfo"]))?$_POST["vacationinfo"]:'Automatic Response. Out of office';
                $entry["otherTransport"]= 'gnarwl:'.$domain;

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

	if ($ldapbind) {
            $result=$Ldap->search($ldapconn,$binddn, $filter);
        }
require_once('header.php');
require_once('sidebar.php');
}?>

<div id="admin-content" class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="inner" id="maincol">
                <?php
/*
            echo '<pre>';
            var_dump ($result);
            echo '</pre>';
*/
                echo $message; 
                $forward = $result[0]["forwardactive"][0];
                //if Forward is activated set the checkbox as checked
                //and show hidden field with  maildrop which will be
                //a required field

                $checked = ($forward=="TRUE")?'checked="checked"':'';
                //$required = ($forward=="TRUE")?'required':'';
                $maildrop=(isset($result[0]["maildrop"][0]))?$result[0]["maildrop"][0]:'';
                $vacation = (isset($result[0]["vacationactive"][0]))? $result[0]["vacationactive"][0]:'';
                $vacationmessage = (isset($result[0]["vacationinfo"][0]))?$result[0]["vacationinfo"][0]:'';
                $vacation_checked = ($vacation=="TRUE")?'checked="checked"':'';
                echo '<div class="title-block">';
                echo '<h3>' . sprintf (_("Editar cuenta de correo %s") , $email) .  '</h3>';
                echo '<span class="sparkline bar" data-type="bar"></span>
                      <h3 class="subtitle">' 
                      . sprintf(_("Los titulares de una cuenta de correo electrónico pueden resetear su contraseña entrando en el panel de control y utilizando como nombre de usuario su dirección de correo.")). 
                      
                  '</h3></div>';
                ?>
             <div class="col-sm-6">
                <div class="card card card-block">

                    <form role="form" autocomplete="off" action="" method="POST" class="form-signin">
                        <div class="form-group">
                          <label for="pswd1"><?php printf(_("Nueva contraseña"));?></label>
                          <div id="pswcheck"></div>
                          <input class="form-control boxed" id="pswd1" type="password" name="pswd1" readonly />
                        </div>

                        <div class="form-group">
                          <label class="control-label" for="pswd2"><?php printf(_("Repetir la nueva contraseña"));?></label>
                          <input class="form-control boxed"  id="pswd2" type="password" name="pswd2" />
                          <div id="pswresult"></div>          
                        </div>

                        <div class="form-group"> 
                          <label class="control-label" for="givenname"><?php echo  sprintf(_("Nombre"));?></label>
                          <input class="form-control boxed" id="givenname" name="givenname" type="text" value="<?php echo $result[0]['givenname'][0];?>" required />
                        </div>

                        <div class="form-group">
                          <label class="control-label" for="surname"><?php printf(_("Apellidos"));?></label>
                          <input class="form-control boxed" id="surname" type="text" name="surname" value="<?php echo $result[0]["sn"][0];?>" required />
                        </div>


                        <div class="form-group"> 
                          <h4><?php printf(_("Reenvío automático"));?></h4>
                          <div> <label>
                          <input class="checkbox" type="checkbox" name="forward" id="forward" <?php echo $checked;?>>
                          <span><?php printf(_("Activar o desactivar el reenvío automático a otra cuenta" ));?></span>
                          </label> </div>
                        </div>

      
                        <div id="hidden">
                          <div class="form-group">
                            <label for="maildrop"><?php printf(_("Cuenta de destino para el reenvío automático"));?></label>
                            <p><?php printf(_("Puedes reenviar los correos electrónicos entrantes a una o más cuentas de correo alternativas. Si quieres que se reenvíen a múltiples cuentas, separa cada una de ellas con una coma (usuario1@example.com,usuario2@example.com). Recuerda que si quieres seguir recibiendo una copia de los correos entrantes en tu cuenta actual %s, también tendrás que incluirla en el listado."),$email);?></p>
                            <input class="form-control boxed" id="maildrop" class="usermail" type="mail" name="maildrop" value="<?php echo $maildrop;?>"  />
                            <div id="emailresult"></div>
                          </div>
                        </div>

                        <div class="form-group">
                          <h4><?php printf(_("Respuesta automática"));?></h4>
                          <div> <label>
                          <input class="checkbox" type="checkbox" name="vacationactive" id="vacationactive" <?php echo $vacation_checked;?>>
                          <span><?php printf(_("Activar o desactivar la respuesta  automática" ));?></span>
                          </label> </div>
                        </div>
                        <div id="hiddenreply">
                          <div class="form-group">
                            <label for="vacationinfo"><?php printf(_("Mensaje de respuesta automática"));?></label>
                            <p><?php printf(_("Inserta el texto del mensaje de respuesta automática."),$email);?></p>
                            <textarea class="form-control boxed" id="vacationinfo" class="usermail" type="textarea"  name="vacationinfo" value="<?php echo $vacationmessage;?>" rows="6" cols="50"><?php echo $vacationmessage; ?></textarea>
                            <div id="emailresult"></div>
                          </div>
                        </div>

                        <br>
                        <hr>
                        <input type="hidden" name="domain" value="<?php echo $domain;?>" />
                        <input type="submit" name="editmail" value="Guardar" class="btn btn-small btn-primary" />
                    </form>
                </div>
              </div>
              <div class="col-sm-6">
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
	require_once('footer.php');
