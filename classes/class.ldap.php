<?php
//require_once __DIR__.('/../site-config.php');
require_once __DIR__.('/../functions.php');
class LDAP{
 
    private $server = "ldap://" . LDAP_HOST_NAME ;
    private $port = LDAP_PORT;
    //private $basedn = BINDDN ;
    //private $lddn=SUFFIX;
    public $connection;
    public $bind;

    public function connect(){
        $this->connection=null; 
        $this->connection = ldap_connect($this->server,$this->port);  // must be a valid LDAP server!
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option( $this->connection, LDAP_OPT_REFERRALS, 0); 
        // PHP Reference says there is no control of connection status in OpenLDAP 2.x.x
        // So we'll use binding function to check connection status.
	 	return $this->connection;
	 
    }
 
    function bind($connection,$dn, $psw){
        $bind = ldap_bind($connection,$dn, $psw);
        return $bind;

 
    } 
    
    /*function addVpnObject ($ldapconn){
        //Only admin can add vpn accounts. Check level
        if($_SESSION["login"]["level"] == '10'){				
            $ldapbind=$this->bind($ldapconn, BINDDN ,$_SESSION["login"]["password"]);
            $adddn=LDAP_VPN;
            $info['objectclass'][0]='organizationalUnit';
            $info['objectclass'][1]='top';
            ldap_add($ldapconn, $adddn, $info);
        }
      }
     */
    //Add ou=senderemail firts time if not exixt . check it in notificaciones.php
    function addSenderObject ($ldapconn){
        //Only admin can add vpn accounts. Check level
        if($_SESSION["login"]["level"] == '10'){
          $password=$this->decrypt_psw();
          $ldapbind=$this->bind($ldapconn, BINDDN ,$password);
          $adddn='ou=sendermail,' . SUFFIX;
          $info['objectclass'][0]='organizationalUnit';
          $info['objectclass'][1]='top';
          $info['objectclass'][2]='metaInfo';
          ldap_add($ldapconn, $adddn, $info);
        }
    }

    function search($ldapconn,$searchdn, $filter){
          $sr = ldap_search($ldapconn, $searchdn, $filter );
 
          if ($sr) {
              $info = ldap_get_entries($ldapconn, $sr);
              return $info; 
        } else {
              return false; 
        }
    }   
    function addRecord($connection, $adddn, $record){
        $addProcess = ldap_add(
        $connection, 
        $adddn, 
        $record);
        return $addProcess; 
    }
 
    function modifyRecord($connection, $modifydn, $record){
        $modifyProcess = ldap_modify(
        $connection,
        $modifydn,
        $record);
        if($modifyProcess){
            //check if ==1
            $result="true";
            $message= "
            <div class='alert alert-success'>
            <button class='close' data-dismiss='alert'>&times;</button>
            <strong>" . sprintf(_('Cambio registrado con éxito')) ."<strong> 
            </div>
            <hr><br>";
        } else {
            $result="false";
            $message= "
            <div class='alert alert-error'>
                <button class='close' data-dismiss='alert'>&times;</button>
                <strong>" . sprintf(_('Error')) ."</strong> 
              </div>
            <hr><br>";
        }
        //return  $message;
        return array('message' => $message,
                      'result' => $result
                    );

    }
 
   function deleteRecord($connection, $dn, $recursive = false){
 
        if($recursive != false){
        
 
            // Search for child entries        
            $sr = ldap_list($connection, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($connection, $sr);
 
            for($i=0;$i<$info['count'];$i++){
                // Recursive delete child entries - using myldap_delete to recursive deletion
                $result = $this->deleteRecord($connection, $info[$i]['dn'], $recursive);
                if(!$result){
                    // return status code if deletion fails.
                    return($result);
                }
            }
        }
            // Delete top dn

        $delUser=ldap_delete($connection, $dn);

        if ($delUser){

          $result=true;
          $message="
          <div class='alert alert-success'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>". sprintf (_('Cuenta eliminada con éxito')) . "</strong> 
          </div>";

        } else {

          $result=false;
          $message=  "
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>". sprintf (_('Error')) . "</strong> 
          </div>
          ";

        }
        //echo $message;
        return array('result' => $result,
                    'message' => $message
                            );

    }
 
    function close($connection){
        echo '<hr><br>';
        echo "Closing connection";
        ldap_close($connection);
    }

	##User Session functions######
        # Get fro Phamm code
        # With encryption for pasword addeed#
	#############################
	
	function login($login_username,$login_password){

        // @todo $proposed will be real DN and level
        if (strpos($login_username, '=') && strpos($login_username, ','))
        {
            $proposed["dn"] = $login_username;
            $proposed["login_username"] = $login_username;
        }

        elseif (strpos($login_username, '@'))
        {
            $login = explode ('@',$login_username);

            // Postmaster
            if ( $login[0] == PHAMM_DOMAIN_ADMIN_NAME )
            {
                $proposed["dn"] = 'cn='.$login[0].',vd='.$login[1].','.LDAP_BASE;
                $proposed["level"] = 4;
                $proposed["domain"] = $login[1];
                $proposed["login_username"] = $login_username;
            }

            // User
            else
            {
                $proposed["dn"] = 'mail='.$login_username.',vd='.$login[1].','.LDAP_BASE;
                $proposed["level"] = 2;
                $proposed["domain"] = $login[1];
                $proposed["login_username"] = $login_username;
            }
        }

        // Manager
        elseif ('cn='.$login_username.','.SUFFIX == BINDDN)
        {
            $proposed["dn"] = 'cn='.$login_username.','.SUFFIX;
            $proposed["level"] = 10;
            $proposed["domain"] = '';
            $proposed["login_username"] = $login_username;
        }

        // Login user without @DOMAIN
        elseif (defined('DEFAULT_DOMAIN'))
        {
            $proposed["dn"] = 'mail='.$login_username.'@'.DEFAULT_DOMAIN.',vd='.DEFAULT_DOMAIN.','.LDAP_BASE;
            $proposed["level"] = 2;
            $proposed["domain"] = DEFAULT_DOMAIN;
            $proposed["login_username"] = $login_username.'@'.DEFAULT_DOMAIN;
        }

        // Postmaster without postmaster@
        else
        {
            $proposed["dn"] = 'cn=postmaster,vd='.$login_username.','.LDAP_BASE;
            $proposed["level"] = 4;
            $proposed["domain"] = $login_username;
            $proposed["login_username"] = 'postmaster@'.$login_username;
        }

        $r = ldap_bind($this->connection, $proposed["dn"], $login_password);

        if ($r)
        {
            $_SESSION["login"]["dn"] = strtolower($proposed["dn"]);
            $_SESSION["phamm"]["domain"] = strtolower($proposed["domain"]);
            $_SESSION["login"]["username"] = strtolower($proposed["login_username"]);

            $_SESSION["login"]["level"] = $proposed["level"];
            $_SESSION["login"]["password"] = $login_password; // @todo crypt it

            # Create Key for encrypt password
            $key = OneTimePadCreate ($length=100);

            # Encrypt password and store in browser as cookie
            $enc_pass=OneTimePadEncrypt ($login_password, $key);
  
            # Save cookie with encrypted passord and key in session
            sqsetcookie('usec', $enc_pass);
            $_SESSION["login"]["key"] = $key;
 
            # Check if is first login
            if ($_SESSION["login"]["level"]==10){

              $filter="(&(objectClass=extensibleObject)(cn=$login_username))";

              $admin_data=$this->search($this->connection,SUFFIX,$filter);
              $status=$admin_data[0]["status"][0];
              $_SESSION["login"]["status"] = $status;
            } else {
              $_SESSION["login"]["status"] = 'active';

            }

            return true;
        }

        return false;

	}
        # Add users to ldap . They won't be in sudoers
        # and will be in a sftpusers group, jailed in
        # chroot own home directory
        # @param $newuser - The username
        # @param $password - The user password 
        # @param $grid - The group id (will be sftpusers

        function add_sftp_user($newuser,$password,$user_email){
        $groupinfo = posix_getgrnam("sftpusers");
        $grid=$groupinfo["gid"];
        $ldaptree    = 'ou=sshd,ou=People,' . SUFFIX;
        $filter="(&(objectClass=person)(uid=*))";
        //First we check if username is available, including system users, outside ldap Directory using getent
        $cmnd="getent passwd " .$newuser;
        $userexist=exec($cmnd);

        if($userexist) {
            $result=false;
            $message=  "
          
            <div class='alert alert-error'>
            <button class='close' data-dismiss='alert'>&times;</button>
            <strong> El usuario ". $newuser ." ya existe en el sistema. Por favor escoge otro nombre</strong> 
            </div>
            ";

          //We check syntax for usename
        } elseif(!check_syntax ('account',$newuser, $length="2")) {

            $result=false;
            $message=  "
          
            <div class='alert alert-error'>
            <button class='close' data-dismiss='alert'>&times;</button>
            <strong>'" . $newuser ."' no es un nombre de usuario válido. El nombre tiene que tener mínimo dos carácteres y solo puede contener cifras y/o números. Los carácteres especiales y los espacios no están admitidos</strong> 
            </div>
          ";
        } elseif (!check_syntax ('email', $user_email)) {
                      $result=false;
                                 $message=  "
          
            <div class='alert alert-error'>
            <button class='close' data-dismiss='alert'>&times;</button>
            <strong>'" . $user_email ."' no es un email válido.</strong>
            </div>
          ";
         
        } else {

          $adddn='uid='. $newuser . ',' . $ldaptree;
          $info=array();
          $info['objectclass'][0]='person';
          $info['objectclass'][1]='organizationalPerson';
          $info['objectclass'][2]='inetOrgPerson';
          $info['objectclass'][3]='posixAccount';
          $info['objectclass'][4]='top';
          $info['objectclass'][5]='shadowAccount';
          $info['objectclass'][6]='authorizedServiceObject';
          $info['authorizedservice']='sshd';
          $info['cn']=$newuser;
          $info['mail']=$user_email;
          $info['uid']=$newuser;
          $info['sn']=$newuser;
          $info['userpassword']=ldap_password_hash($password,'ssha');
          $info['shadowlastchange'] = floor(time()/86400);
          ## “shadowMax”: days after which password must be changed
          ## For now we just set it as longer than a human life.
          ## Then we will see if we want to include this function
          $info['shadowmax']='99999';
          ## “shadowWarning”: days before password is to expire that user is warned
          $info['shadowwarning']='7';
          $info['loginshell']='/bin/bash';

          ## Check Netxuid number to sssign to new user
          ## for that we use a fake autoincrement system:
          ## cn=uidNext,dc=example,dc=tld May have attribute uidNumber or not
          #  If attribute is present and has a value we assign it to a variable and delete it in order to avoid
          # other process to use same value
          # When we finish with new user creation we set back the uidNumber attribute to the stored value + 1 
          $netxuid_number=$this->search($this->connection,$ldaptree, '(&(objectClass=uidNext)(uidnumber=*))');
          $uidNext=($netxuid_number)? $netxuid_number[0]['uidnumber'][0]:NULL;
          if($uidNext){
            //First delete uidNumber attribute from Directory
            $entry['uidnumber']=array();
            $success=ldap_mod_del($this->connection,'cn=uidNext,'.$ldaptree,$entry);
            if($success){
              //Only if deletion was succesfully we go on. Otherwise somebody else coud use same uid
              //We set next uidNumber to an incremente value by 1
              $insertuid=$uidNext+1;
              $entry['uidnumber']=(int)$insertuid;
              $success=ldap_mod_add($this->connection,'cn=uidNext,'.$ldaptree,$entry);
              $info['uidnumber']=(int)$uidNext;
              $info['gidnumber']=(int)$grid;
              $info['homedirectory']='/home/sftpusers/' . $newuser;
              $info['gecos']=$newuser . ',,,';
              //$addgroup=$this->addRecord($this->connection,$adddngroup,$group);
              $addUser=$this->addRecord($this->connection, $adddn, $info);
              var_dump ($info);
            }

          } else { //No uidNumber found. We cannot add user
            $result=false;
            $errorttpe = 'Probablemente alguien estaba añdadiendo un usuario en el mismo instante y se ha bloqueado tu acción para evitar conflictos en el sistema. Por favor vuelve a intentarlo';
          }

        if ($addUser){

          $result=true;
          $message="
          <div class='alert alert-success'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>". sprintf ('Account %s successfully added', $newuser) . "</strong> 
          </div>";

        } else {

          $errorttpe  = (ldap_errno($this->connection)==68)?"El usuario " . $newuser . " ya existe": "";
          $result=false;
          $message=  "
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>Ha habido un error. " . $errorttpe ." </strong> 
          </div>
          ";

        }
    } //End if user exist in getent passwd
        //echo $message;
        return array('result' => $result,
                    'message' => $message
                            );
}

    function add_user($newuser,$entry){
        $ldaptree    = LDAP_PEOPLE;
        $filter="(&(objectClass=person)(uid=*))";
          $adddn='uid='. $newuser . ',' . $ldaptree;
          $info=array();
          $info['objectclass'][0]='person';
          $info['objectclass'][1]='organizationalPerson';
          $info['objectclass'][2]='inetOrgPerson';
          $info['objectclass'][3]='posixAccount';
          $info['objectclass'][4]='top';
          $info['objectclass'][5]='shadowAccount';
          $info['objectclass'][6]='authorizedServiceObject';
          /*$info['authorizedservice']='sshd';
          $info['cn']=$newuser;
          $info['mail']=$user_email;
          $info['uid']=$newuser;
          $info['sn']=$newuser;
          $info['userpassword']=ldap_password_hash($password,'ssha');
          */
          $info['shadowlastchange'] = floor(time()/86400);
          ## “shadowMax”: days after which password must be changed
          ## For now we just set it as longer than a human life.
          ## Then we will see if we want to include this function
          $info['shadowmax']='99999';
          ## “shadowWarning”: days before password is to expire that user is warned
          $info['shadowwarning']='7';
          //$info['loginshell']='/bin/bash';

          ## Check Netxuid number to sssign to new user
          ## for that we use a fake autoincrement system:
          ## cn=uidNext,dc=example,dc=tld May have attribute uidNumber or not
          #  If attribute is present and has a value we assign it to a variable and delete it in order to avoid
          # other process to use same value
          # When we finish with new user creation we set back the uidNumber attribute to the stored value + 1 
          $netxuid_number=$this->search($this->connection,$ldaptree, '(&(objectClass=uidNext)(uidnumber=*))');
          $uidNext=($netxuid_number)? $netxuid_number[0]['uidnumber'][0]:NULL;
          if($uidNext){
            //First delete uidNumber attribute from Directory
            $index['uidnumber']=array();
            $success=ldap_mod_del($this->connection,'cn=uidNext,'.$ldaptree,$index);

            if($success){
              //Only if deletion was succesfully we go on. Otherwise somebody else coud use same uid
              //We set next uidNumber to an incremente value by 1
              $insertuid=$uidNext+1;
              $index['uidnumber']=(int)$insertuid;
              $success=ldap_mod_add($this->connection,'cn=uidNext,'.$ldaptree,$index);

              # If is sshd user assign sfptusers' group
              if (in_array('sshd',$info)){
              $info['gidnumber']=(int)$grid;
              # Esle assign uidnumber as group
              } else  {
              $info['gidnumber']=(int)$insertuid;
              }
              $info['uidnumber']=(int)$insertuid;
              //$info['homedirectory']='/home/sftpusers/' . $newuser;
              $info['gecos']=$newuser . ',,,';
              //Merge values into array
              $entry2 = array_merge($info,$entry);
              
              //$addgroup=$this->addRecord($this->connection,$adddngroup,$group);
              $addUser=$this->addRecord($this->connection, $adddn, $entry2);
            }

          } else { //No uidNumber found. We cannot add user
            $result=false;
            $errorttpe = 'Probablemente alguien estaba añdadiendo un usuario en el mismo instante y se ha bloqueado tu acción para evitar conflictos en el sistema. Por favor vuelve a intentarlo';
          }

        if ($addUser){

          $result=true;
          $message="
          <div class='alert alert-success'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>". sprintf ('Usuario %s añadido con éxito', $newuser) . "</strong> 
          </div>";

        } else {

          $errorttpe  = (ldap_errno($this->connection)==68)?"El usuario " . $newuser . " ya existe": "";
          $result=false;
          $message=  "
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>Ha habido un error. " . $errorttpe ." </strong> 
          </div>
          ";

        }
        //echo $message;
        return array('result' => $result,
                    'message' => $message
       );
}
  function show_path(){
          echo $foldername='VPN-'.$_SERVER['SERVER_ADDR'];
          echo  $filesdir=dirname(__DIR__).'/files';
          echo  $folderpath=$filesdir.'/'.$foldername;
}

  function check_installed_service($service) {
        $serv_enabled = $this->search($this->connection, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled))');
        if(array_search($service, array_column(array_column($serv_enabled, 'ou'),0)) !== false){
          return true;
        } else {
          return false;
        }

  }       

  function send_vpn_instructions($to,$username) {
        {
          //Get email sender option for notifications

            $mailsenderou= $this->search($this->connection,'ou=sendermail,' . SUFFIX,'(&(objectClass=organizationalUnit)(objectClass=metaInfo))');
            $fqdn=trim(shell_exec('hostname -f'));
            $from = ($mailsenderou[0]["cn"][0])?$mailsenderou[0]["cn"][0]: 'www-data@'.$fqdn;
            $subject='Cuenta VPN activada';
            $ipaddr=$_SERVER['SERVER_ADDR'];
            $foldername='VPN-'.$_SERVER['SERVER_ADDR'];
            //here we have the config files without ip
            $code_filesdir=dirname(__DIR__).'/files';
            $code_folderpath=$code_filesdir.'/'.$foldername;
            //Here we make a copy to create zip folder with custom data (IP)
            if(!is_dir('/tmp/vpn_files'))mkdir('/tmp/vpn_files');
            $filesdir='/tmp/vpn_files';
            if(!is_dir($filesdir.'/'.$foldername))mkdir($filesdir.'/'.$foldername);
            $folderpath=$filesdir.'/'.$foldername;

            if (file_exists($folderpath.'zip')) {
              $attachments=$folderpath.'.zip';
            }
            else
            {
              //Check if a ca.crt is available
              if (file_exists("/etc/openvpn/" . $fqdn . "/keys/ca.crt")){

                shell_exec("cat /etc/openvpn/" . $fqdn . "/keys/ca.crt > $filesdir/ca.crt");
                $ca=$filesdir. '/ca.crt';

              } else {

                $error=1;
                $message="no hay ca.crt";

              }

              #TODO for  mac. If it's the same so a foreach
              $src=$code_filesdir.'/vpn_config';

              //create directory and copy generic config files
              //If files already exists is ok to overwrite them
              //slash before command calls it without alias which may be -i

              //shell_exec("find $src/ -type d -exec cp $ca {} \;");
              shell_exec("\cp -r -f $src/* $folderpath");
              shell_exec("find $folderpath/ -type d -exec cp $ca {} \;");
              $addip_text='remote ' . $_SERVER['SERVER_ADDR'];
              $filesnames=array($folderpath.'/linux/vpn.conf',$folderpath.'/windows/vpn.ovpn', $folderpath.'/android/android-client.ovpn');
              foreach ($filesnames as $configfile){

                if (is_writable($configfile)) {
                  if (!$handle = fopen($configfile, 'a')) {
                   // echo "Cannot open file ($configfile)";
                    exit;
                  }

                  if (fwrite($handle, $addip_text) === FALSE ) {
                   //   echo "Cannot write to file ($configfile)";
                    exit;
                  }
                  fclose($handle);
                  } else {
                   //   echo "The file $configfile not writable";
                  }

                }
                shell_exec("cd $filesdir && zip -r $foldername.zip $foldername");
                $attachments=$filesdir.'/'.$foldername .'.zip';
          }

          $body="
          Buenos días,<br><br>
          El administrador de " . $_SERVER['SERVER_NAME'] . " ha activado una cuenta VPN para ti<br>
          <b>Usuario: </b> " . $username ."<br>
          <b>Contraseña:</b> <em>Solicitala al administrador - por razones de seguridad no se envian contraseñas por correo electrónico</em>
          <br>
          <br>
          Por favor, desacrga el archivo adjunto y sigue las instrucciones para tu sistema operativo que encontrarás en: 
          <br><br>
          <a href='http://docs.maadix.net/vpn'>http://docs.maadix.net/vpn/</a>";

        $action=send_mail($from,$to,$body,$subject,$attachments);
        }
}


  function get_sudo_user(){
    $filter="(&(objectClass=person)(uid=*)(gidnumber=27))";
    $sudouser=$this->search($this->connection,LDAP_PEOPLE, $filter);
    return $sudouser[0]['uid'][0];
   } 





  function decrypt_psw () {

      $key=$_SESSION["login"]["key"];
      $encpsw=$_COOKIE['usec'];
      $psw=OneTimePadDecrypt ($encpsw, $key);
      return $psw;

  } 





    function is_logged_in()
	{


          if ((isset($_SESSION["login"]["dn"]) && (isset( $_SESSION["login"]["status"]) && $_SESSION["login"]["status"] == "active") && $_SESSION["login"]["level"]==10) || (isset($_SESSION["login"]["dn"]) && $_SESSION["login"]["level"] < 10) )
          {

              return true;
          
          }
          
           elseif (isset($_SESSION["login"]["dn"]) && $_SESSION["login"]["level"]==10  &&  $_SESSION["login"]["status"] != "active")

          {
          
          $this->redirect('activate.php');

          }

          else

          { 
              return false;

          }
        }

	function redirect($url)
	{
			header("refresh:5;Location: $url");
	}
	function logout()
	{
            session_destroy();
            //$_SESSION['userSession'] = false;
            //$_SESSION['userSessionpsw'] = false;
            
            # Remove usec cookie
            setcookie('usec', '', time()-3600);


	}

 
}
//END LDAP CLASS

?>
