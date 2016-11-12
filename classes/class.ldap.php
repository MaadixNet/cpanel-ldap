<?php
require_once __DIR__.('/../site-config.php');
class LDAP{
 
    private $server = "ldap://" . LDAP_HOST_NAME ;
    private $port = "389";
    private $basedn = BINDDN ;
	private $lddn=SUFFIX;
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
		//$bind = ldap_bind($connection, BINDDN ,$_SESSION["userSessionpsw"]);
		$bind = ldap_bind($connection,$dn, $psw);
		return $bind;

 
    } 
	//Add ou=vnp firts time if not exixt . check it in vpn.php
	function addVpnObject ($ldapconn){
				//Only admin can add vpn accounts. Check level
				if($_SESSION["login"]["level"] == '10'){				
                $ldapbind=$this->bind($ldapconn, BINDDN ,$_SESSION["login"]["password"]);
                $adddn='ou=vpn,' . SUFFIX;
                $info['objectclass'][0]='organizationalUnit';
                $info['objectclass'][1]='top';
                ldap_add($ldapconn, $adddn, $info);
				}
	}
    //Add ou=senderemail firts time if not exixt . check it in notificaciones.php
    function addSenderObject ($ldapconn){
                //Only admin can add vpn accounts. Check level
                if($_SESSION["login"]["level"] == '10'){
                $ldapbind=$this->bind($ldapconn, BINDDN ,$_SESSION["login"]["password"]);
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
        $modifyProcess = ldap_modify($connection, $modifydn, $record);
        if($modifyProcess){
			echo "
                <div class='alert alert-success'>
                    <button class='close' data-dismiss='alert'>&times;</button>
                        <strong>cambio registrado correctamente</strong> 
                </div>
                ";

            echo '<hr><br>';
        } else {
            echo  "
            <div class='alert alert-error'>
                <button class='close' data-dismiss='alert'>&times;</button>
                <strong>Lo sentimos!  ha habido un errorr. Los cambios no se han podido guardar</strong> 
              </div>
              "; 
            echo '<hr><br>';
        }
    }
 
   function deleteRecord($connection, $dn, $recursive = false){
 
        if($recursive == false){

            return(ldap_delete($connection, $dn));
        } else {
 
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
            // Delete top dn

            return(ldap_delete($connection, $dn));
        }
    }
 
    function close($connection){
        echo '<hr><br>';
        echo "Closing connection";
        ldap_close($connection);
    }

	##User Session functions######
	#############################
	
	function login($login_username,$login_upassword){

			$login_username = $_POST['username'];
			$login_password = $_POST['password'];



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

			/*$ldaprdn=$this->lddn;
			$bind = @ldap_bind($this->connection, 'cn='. $username. ',' . $ldaprdn, $login_password);

			if ($bind) {
					$_SESSION['userSession'] = $login_username;
					$_SESSION['userSessionpsw'] = $login_password;
					return true;
			ldap_close($this->connection);
			}
			*/
        $r = ldap_bind($this->connection, $proposed["dn"], $login_password);

        if ($r)
        {
            $_SESSION["login"]["dn"] = strtolower($proposed["dn"]);
            $_SESSION["phamm"]["domain"] = strtolower($proposed["domain"]);
            $_SESSION["login"]["username"] = strtolower($proposed["login_username"]);

            $_SESSION["login"]["level"] = $proposed["level"];
            $_SESSION["login"]["password"] = $login_password; // @todo crypt it

            return true;
        }

        return false;

	}

        function add_sftp_user($newuser,$password){
        $ldaptree    = 'ou=People,' . SUFFIX;
        $filter="(&(objectClass=person)(uid=*))";
        //First we check if username is available, including system users, outside ldap Directory using getent
        $cmnd="getent passwd " .$newuser;
        $userexist=exec($cmnd);
        if($userexist) {
           $message=  "
        
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong> El usuario ". $newuser ." ya existe en el sistema. Por favor escoge otro nombre</strong> 
          </div>
          ";
          //We check syntax for usename
        } elseif(!check_syntax ('account',$newuser, $length="2")) {

           $message=  "
        
          <div class='alert alert-error'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>'" . $newuser ."' no es un nombre de usuario válido. El nombre tiene que tener mínimo dos carácteres y solo puede contener cifras y/o números. Los carácteres especiales y los espacios no están admitidos</strong> 
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
          $info['cn']=$newuser;
          $info['uid']=$newuser;
          $info['sn']=$newuser;
          $info['userpassword']=ldap_password_hash($password,'md5crypt');
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
          $netxuid_number=$this->search($this->connection,'cn=uidNext,'. SUFFIX, '(&(objectClass=uidNext)(uidnumber=*))');
          $uidNext=($netxuid_number)? $netxuid_number[0]['uidnumber'][0]:NULL;
          if($uidNext){
            //First delete uidNumber attribute from Directory
            $entry['uidnumber']=array();
            $success=ldap_mod_del($this->connection,'cn=uidNext,'. SUFFIX,$entry);
            if($success){
              //Only if deletion was succesfully we go on. Otherwise somebody else coud use same uid
              //We set next uidNumber to an incremente value by 1
              $insertuid=$uidNext+1;
              $entry['uidnumber']=(int)$insertuid;
              $success=ldap_mod_add($this->connection,'cn=uidNext,'. SUFFIX,$entry);
              //1003 is the sftpusers group which is chrooted in their home
              $sftifroupid='1003';
              //first we crate group
              $info['uidnumber']=(int)$uidNext;
              $info['gidnumber']=(int)$sftifroupid;
              $info['homedirectory']='/home/sftpusers/' . $newuser;
              $info['gecos']=$newuser . ',,,';
              //$addgroup=$this->addRecord($this->connection,$adddngroup,$group);
              $addUser=$this->addRecord($this->connection, $adddn, $info);
            }

          } else { //No uidNumber found. We cannot add user
            $errorttpe = 'Probablemente alguien estaba añdadiendo un usuario en el mismo instante y se ha bloqueado tu acción para evitar conflictos en el sistema. Por favor vuelve a intentarlo';
          }

        if ($addUser){
          return array('result' => true,
         'message'=> "
          <div class='alert alert-success'>
          <button class='close' data-dismiss='alert'>&times;</button>
          <strong>Cuenta añadida con éxito para el usuario " . $newuser . "</strong> 
          </div>"
          );
        } else {
                 $errorttpe  = (ldap_errno($this->connection)==68)?"El usuario " . $newuser . " ya existe": "";
        $message=  "
        <div class='alert alert-error'>
        <button class='close' data-dismiss='alert'>&times;</button>
        <strong>Ha habido un error. " . $errorttpe ." </strong> 
        </div>
        ";
        return array('result' => false,
          'message' => $message
        );
        }
    } //End if user exist in getent passwd
        //echo $message;
        return $addUser;
}


    function is_logged_in()
	{
		/*	if(isset($_SESSION['userSession']))
			{
					return true;
			}
		*/

        if (isset($_SESSION["login"]["dn"]))
            return true;
        else
            return false;

	}

	function redirect($url)
	{
			header("Location: $url");
	}
	function logout()
	{
			session_destroy();
			//$_SESSION['userSession'] = false;
		 	//$_SESSION['userSessionpsw'] = false;
	}

 
}
//END LDAP CLASS
function ssha_hash_password($password) // SSHA with random 4-character salt
	 {
	  $salt = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',4)),0,4);
	  return '{SSHA}' . base64_encode(sha1( $password.$salt, TRUE ). $salt);
	   }


function ldap_password_hash($password_clear,$enc_type)
{
	$salt = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',4)),0,4);
    $enc_type = strtolower($enc_type);

    switch($enc_type)
    {
    case 'crypt':
        $password_hash = '{CRYPT}'.crypt($password_clear);
        break;

    case 'md5':
    $password_hash = '';
        $md5_hash = md5($password_clear);
    for ( $i = 0; $i < 32; $i += 2 )
        $password_hash .= chr( hexdec( $md5_hash{ $i + 1 } ) + hexdec( $md5_hash{ $i } ) * 16 );
    $password_hash = '{MD5}'.base64_encode($password_hash);
        break;
	case 'ssha':
#	$password_hash =  '{SSHA}' . base64_encode(sha1( $password_clear.$salt, TRUE ). $salt);	
    //$password_hash =  '{SSHA}' . base64_encode(sha1( $password_clear.$salt, TRUE ). $salt);   
            if (function_exists('mhash') && function_exists('mhash_keygen_s2k')) {
                mt_srand((double)microtime()*1000000);
                $salt = mhash_keygen_s2k(MHASH_SHA1,$password_clear,substr(pack('h*',md5(mt_rand())),0,8),4);
                $password_hash = sprintf('{SSHA}%s',base64_encode(mhash(MHASH_SHA1,$password_clear.$salt).$salt));

            } else {
                error(_('Your PHP install does not have the mhash() or mhash_keygen_s2k() function. Cannot do S2K hashes.'),'error','index.php');
            }   

            break;

	case 'md5crypt':
		if (! defined('CRYPT_MD5') || CRYPT_MD5 == 0)
			error(_('Your system crypt library does not support md5crypt encryption.'),'error','index.php');

		$password_hash = sprintf('{CRYPT}%s',crypt($password_clear,'$1$'.$salt));

            break;

    case 'clear':
        $password_hash = $password_clear;
        break;

    default:
        $password_hash = '{CRYPT}'.crypt($password_clear);
        break;
    }

    return $password_hash;
}
/**
 * FROM phpldapadmin code
 * Used to generate a random salt for crypt-style passwords. Salt strings are used
 * to make pre-built hash cracking dictionaries difficult to use as the hash algorithm uses
 * not only the user's password but also a randomly generated string. The string is
 * stored as the first N characters of the hash for reference of hashing algorithms later.
 *
 * @param int The length of the salt string to generate.
 * @return string The generated salt string.
 */
function random_salt($length) {

    $possible = '0123456789'.
        'abcdefghijklmnopqrstuvwxyz'.
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
        './';
    $str = '';
    mt_srand((double)microtime() * 1000000);

    while (strlen($str) < $length)
        $str .= substr($possible,(rand()%strlen($possible)),1);

    return $str;
}

/**
* Various syntax check (IP address, domain, email address...)
*
* @author Alessandro De Zorzi <adezorzi@rhx.it>
* @todo Check if IP 0 < number <255
*
* @param string $type The kind of data
* @param string $arg The value
* @param int $length The min length of string
* @todo name
* @return bool
**/

function check_syntax ($type,$arg,$length="0")
{
    if (strlen($arg) < $length)
    {
        return false;
    }

    // IP Address
    if ($type == 'ip')
    {
        if (!preg_match ("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $arg))
        {   
            return FALSE;
        }

        /*$numbers = explode('.',$arg);

        foreach ($numbers as $number)
        {
            if ($number > 255)
            return FALSE;
        } */

        else
        {   
            return TRUE;
        }
    }
   // DOMAIN
    elseif ($type == 'domain')
    {
        if (!preg_match("/^([0-9a-z][0-9a-z-]+\.)+[a-z]{2,7}$/i", $arg))
        {   
            return FALSE;
        }

        else
        {
            return TRUE;
        }
    }

 // ALIAS and ACCOUNT
    elseif ($type == 'account')
    {
        if (!preg_match("/^[\._a-z0-9-]+$/i", $arg))
        {
            return FALSE;
        }

        else
        {
            return TRUE;
        }
    }

    // Password
    elseif ($type == 'password')
    {
        if (!preg_match("/^[\._a-z0-9-]+$/i", $arg))
            return false;

        return true;
    }

    // Email
    elseif ($type == 'email')
    {
        if (!preg_match("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$", $arg))
            return false;

        return true;

    }

    // Name
    elseif ($type == 'name')
    {
        return true;
    }
}

?>
