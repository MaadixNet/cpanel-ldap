<?php 
session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

//connect and BInd
$errorttpe=$error="";
$message=$dns_result="";
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
}
//Add new domain
if(isset($_POST['adddomain'])){
    $password=$_POST['pswd1'];
    $password2=$_POST['pswd2'];
    if ($password && $password === $password2){
      $_POST['pswd1'] = $_POST['pswd2'] = $password = ldap_password_hash($password,'ssha');
    } else {
      $error = true;
    } 
    $sanitsed_data= sanitizeData($_POST);
    $fqdn=trim(shell_exec('hostname -f'));
    $sanitsed_data= sanitizeData($_POST);
    $webmaster=$sanitsed_data['seluser'][0]['value'];
    if($webmaster=='newuser'){
      //$webmaster=$_POST['new_username'];
            /*$password = $_POST['webmaster_password'];
            $add_user=$Ldap->add_sftp_user($webmaster,$password);
            $message=$add_user['message'];*/
        $ldaptree    = LDAP_PEOPLE;
        $groupinfo = posix_getgrnam("sftpusers");
        $grid=$groupinfo["gid"];
        $entryus=array();
        $newuser=$sanitsed_data['username'][0]['value'];
        $first_name=(isset($sanitsed_data['firstname'][0]['value']))?$sanitsed_data['firstname'][0]['value']:$newuser;
        $second_name=(isset($sanitsed_data['surname'][0]['value']))?$sanitsed_data['surname'][0]['value']:$newuser;
        $user_email=$sanitsed_data['usermail'][0]['value'];
          $entryus['gidnumber']=(int)$grid;
          $entryus['loginshell']='/bin/bash';
          $entryus['homedirectory']='/home/sftpusers/' . $newuser;
          $entryus['authorizedservice'][0]='sshd';
        if (isset($_POST['vpn'])){
          $entryus['authorizedservice'][1]='openvpn';
          //$entry['homedirectory']='none';
        }
          $entryus['uid']=$newuser;
          $entryus['cn']=(!empty($first_name))?$first_name:$newuser;
          $entryus['sn']=(!empty($second_name))?$second_name:$newuser;
          $entryus['mail']=$user_email;
          $entryus['userpassword']=$password;
          $add_user=$Ldap->add_user($newuser,$entryus);
          if($add_user['result'] == 1){
            // If we could create the new user assign it as adminid
            // else we will assign the default user as admin
            $webmaster=$newuser;
            $usererro="";
          } else {
            $wemaster=$Ldap->get_sudo_user();
            $usererror = sprintf (_("El usuario %s no se ha podido crear.Se ha establecido el usuario por defecto %s como administrador del dominio"),$newuser,$wemaster);
          }

          if (isset($_POST["sendinstruction"]) && $add_user)$Ldap->send_vpn_instructions($user_email,$newuser);
          $message=$add_user['message'];
    }
    $values = $_POST["values"];
    $domain_new = $_POST["domain_new"];

    # Check if DNS are ok for this server
    $check_dns=check_domain_dns($domain_new);
    $dns_result=$check_dns["message"];

    $syntax= check_syntax ('domain',$domain_new);
    
    #$password = $_POST["password"];
    $rand_sr = create_password();
    $password = ldap_password_hash($rand_sr,'ssha');
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
    $entrypm["userPassword"]  =ldap_password_hash($password,'ssha');
    $entrypm["maildrop"] = "postmaster";
    $entrypm["accountActive"]     = (isset($_POST["mailactive"]))?"TRUE":"FALSE";
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
    } elseif ($domain_new==$fqdn){
      $errorttpe="El dominio " . $domain_new . " no se puede añadir, ya que es el nombre de tu servidor y ya está creado";
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
    <strong>Dominio " . $domain_new ." añadido correctamente.<br>"
    . $dns_result . "</strong>
    </div>
        ";

       //We have to create the .ini file for rainloop for this domain
       //if rainloop is installed in /var/www/rainloop/data/_data_/_default_/domains/
      if ($Ldap->check_installed_service('rainloop')){
          $filename ='/var/www/rainloop/data/_data_/_default_/domains/'. $domain_new .'.ini';
          if (!file_exists( $filename)){
            $ini_file= fopen($filename, "w") or die("Unable to open file!");
            $fqdn=trim(shell_exec('hostname -f'));
            $content='imap_host = "' . $fqdn . '"
imap_port = 143
imap_secure = "TLS"
imap_short_login = Off
sieve_use = Off
sieve_allow_raw = Off
sieve_host = ""
sieve_port = 4190
sieve_secure = "None"
smtp_host = "' . $fqdn . '"
smtp_port = 25
smtp_secure = "TLS"
smtp_short_login = Off
smtp_auth = On
smtp_php_mail = Off
white_list = ""';

              fwrite($ini_file, $content);
              fclose($ini_file);
          }
        }
      } else {
          $errorttpe    = (ldap_errno($ldapconn)==68)?"El dominio " . $domain_new . " ya existe": $errorttpe;
          $message .=  "
    <div class='alert alert-danger'>
    <button class='close' data-dismiss='alert'>&times;</button>
    <strong>Ha habido un error. " . $errorttpe ." </strong> 
    </div>
    ";
      }
  
}
require_once('header.php');
require_once('sidebar.php');
?>

<article class="content forms-page">
  <div class="title-block">
    <h3 class="title"> <?php printf(_("Añadir Dominio"));?> </h3>
      <p class="title-description"> <?php printf(_("Activa dominios o subdominios para este servidor."));?></p>
  </div>
  <div class="subtitle-block">
  <h3 class="subtitle"> <?php printf(_(" Activar un dominio en este panel creará la configuración necesaria para:"));?></h3>
    <p><?php printf(_("<ul> <li> Crear cuentas de correo electrónico</li> <li> Alojar contenido visble visitando el dominio con un navegador </li><li> Crear certificado SSL para navegación segura (https) </li> </ul>
    Recuerda que para un correcto funcionamiento de tus dominios en el servidor es necesario que configures correctamente los DNS desde el panel de configuración de tu proveedor de dominio (<a href='/" . BASE_PATH ."/domain-instruccions.php'>+ info</a>). "));?> </p>
  </div>
<section class="section">
<div id="admin-content" class="row sameheight-container">
    <?php if($message) echo $message;?>
    <?php if($_SESSION["login"]["level"] == '10'){//Only admin can add Domains 
    ?>
  <div class="card card-block">
    <form role="form" autocomplete="off" action="" method="POST" class="form-signin standard jquery-check">
        <div class="form-group">
          <label for="domain"><?php printf (_("Nombre de Dominio"))?> </label><p class=""> <?php printf (_("Inserta un nombre de dominio válido (o un subdominio). Para los dominios activados podrás crear aplicaciones web que serán disponibles desde cualquier navegador vistando <em>https://tudominio.com</em>. El certificado SSL que activa el protocolo seguro https se activará automáticamente si la  configuración de DNS es la correcta . <a href='https://docs.maadix.net/dominios/'>Saber más</a> "))?></p>
          <input class="form-control" id="domain_new" type="text" name="domain_new" required />
        </div>

        <?php
              $mailtitle = sprintf(_("Activar servidor de correo para este dominio"));
              $mailmessage = sprintf(_("Activa esta casilla si quieres que el correo electrónico para este dominio sea gestionado por este servidor. Si el correo está gestionado por otro servidor , por ejemplo el mismo proveedor de dominio, deja esta casilla desactivada. Podrás cambiar esta opción en cualquier momento desde la página de edición del dominio"), $fqdn);
              $checkbox =  sprintf(_("Activar"));
        ?>
        <label><?php echo $mailtitle;?></label>
        <p><?php echo $mailmessage;?></p>
        <div> <label>
     
        <input name="mailactive" id="mailactive" class="checkbox" type="checkbox" >
        <span><?php echo $checkbox ;?></span>
        </label> </div>


        <div class="form-group">
          <label for="webmaster"><?php printf (_("Webmaster (Administrador sito web)"));?> </label>

          <p class=""><?php printf (_("Por cada dominio que actives en este panel se creará una carpeta con su mismo nombre en /var/www/html/ en la que puedes subir tu aplicación web.<p>
<p>
El Webmaster tendrá permisos para crear, borrar o modificar archivos dentro de la carpeta /var/www/html/<em>example.com</em>/, donde podrá crear la aplicación web. Este usuario solo tendrá acceso a las carpetas de los dominios por los que ha sido nombrado webmaster y a su carpeta personal. En ningún caso podrá acceder ni ver el resto de archivos y carpetas en el servidor.</p>


<p>Recomendamos encarecidamente que crees un usuario Webmaster, sobretodo si quieres otorgar a alguien el acceso para que trabaje sobre la web, aplicación o contenidos de la carpeta /var/www/html/example.com/, y que nunca compartas el acceso como SuperUsuario, cuyos privilegios son ilimitados en el sistema."));?> </p>
           <?php 
            $ldaptree    = LDAP_PEOPLE;
            $filter="(&(objectClass=person)(uid=*)(authorizedService=sshd)(!(gidnumber=27)))";
            $filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";
            $allusers=$Ldap->search($ldapconn,$ldaptree, $filter);
            $sudouser=$Ldap->search($ldapconn,$ldaptree, $filtersudo);
            // This is the default user which has sudo and will be the default owner
            // in case no webmaster is selected or created
            $sudo_username=$sudouser[0]["uid"][0];
            //default webmaster will be user with sudo (uid 10000);
            echo '<div class="form-group">';
            echo '<label for="seluser">' . sprintf (_("Asignar administrador web")) . '</label>';
            echo '<div class="clear"></div>';
            echo '<select id="seluser" name="seluser" class="form-control" required>';
            //echo '<option value="' . $sudo_username .'">Seleccionar Administrador web</option>';
            echo '<option value="" disabled selected="selected">' . sprintf(_("Asignar Webmaster")) .'</option>';
            echo '<option value="newuser">Crear nuevo usuario</option>';
            for ($c=0; $c<$allusers["count"]; $c++) {
              $usernames = $allusers[$c]["uid"][0];
              echo '<option value="' . $allusers[$c]["uid"][0] .'">' . $allusers[$c]["uid"][0] . '</option>';
            }
            echo '<option value="' . $sudo_username .'">' . $sudo_username .' - SuperUsuario</option>';
            echo '</select>';
            echo '</div>';
            echo '<br>';
          
            echo '<div class="newuser" id="new_user">';
            echo '<div class="form-group">
              <label for="username">' . sprintf(_("Nombre de usuario")) .' *</label>
              <input class="form-control" id="username" type="text" name="username" /><div id="result"></div>
            </div>
            <div class="form-group">
              <label for="firstname">'. sprintf(_("Primer nombre (Opcional)")) .'</label>
              <input class="form-control" id="firstname" type="text" name="firstname" />
            </div>
            <div class="form-group">
              <label for="surname">' .  sprintf(_("Apellidos (Opcional)")) . '</label>
              <input class="form-control" id="surname" type="text" name="surname" />
            </div>
            <div class="form-group">
              <label for="usermail">' . sprintf(_("Correo electrónico")) .'* </label>
              <p class="">Puedes introducir un correo electrónico externo o elegir una entre las cuentas creadas en el servidor</p>
                <input class="form-control col-sm-4 usermail" id="usermail" type="mail" name="usermail" /> ';
            $resultmail = $Ldap->search($ldapconn,LDAP_BASE,'(&(objectClass=VirtualMailAccount)(!(cn=postmaster))(!(mail=abuse@*)))');
            $mailcount = $resultmail["count"];
            if($mailcount>0) {
                    echo '<select id="selmail" class="">';
                    echo '<option value="">' . sprintf(_("Seleccionar cuenta existente")) . '</option>';
                    for ($c=0; $c<$resultmail["count"]; $c++) {
                          echo '<option value="' . $resultmail[$c]["mail"][0] .'">' . $resultmail[$c]["mail"][0] . '</option>';
                    }
                   echo '</select>';
            }?>
            
            <div id="emailresult"></div>
            <div class="clearfix"></div>
            <hr>
            <div class="form-group">
            <label for="pswd1"><?php printf(_("Contraseña"));?> *</label>
            <div id="pswcheck"></div>
            <input class="form-control" id="pswd1" type="password" name="pswd1" autocomplete="off" readonly />
            </div>
            <div class="form-group">
            <label for="pswd2"><?php printf(_("Confirma contraseña"));?> *</label>
            <input class="form-control" id="pswd2" type="password" name="pswd2"  />
            <div id="pswresult"></div>
            </div>
            <hr>

            </div>
            </div>

            <div class="clear"></div>
            <input type="hidden" name="values[domain][maxmail]" value="100">
            <input type="hidden" name="values[domain][maxalias]" value="100">
            <input type="hidden" name="values[domain][maxquota]" value="100">
            <input type="hidden" name="values[domain][accountactive]" value="TRUE">
            <input type="hidden" name="values[domain][editav]" value="TRUE">
            <input type="hidden" name="values[domain][delete]" value="FALSE">
            <input type="hidden" name="values[mail][editaccounts]" value="TRUE">
            <input type="hidden" name="values[domain][postfixtransport]" value="maildrop:">
            <input type="submit" name="adddomain" value="Guardar" class="btn btn-small btn-primary" />

            </form>
        <?php } ?>
    </div><!--card card-block sameheight-item--!>
  </div><!--admin-content-->
  </section>
</article>
<?php 
  ldap_close($ldapconn);
  require_once('footer.php');?>
