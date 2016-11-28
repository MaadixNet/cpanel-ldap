<?php
//session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';

$error='0';
//$urltoken=$_GET['token']?$_GET['token']:'';
//get data from file written in recover.php
$data=file_get_contents('/tmp/checkfile.txt');
$array=json_decode($data, true);
//print_r($array);
//get right data  from file:
//token
//username
//date (user will only have 15 min, then token end code will expire
// Code to be manually inserted by user
$when=$array['when'];
$code=$array['code'];
$token=$array['token'];
$username=$array['username'];
$psw1=(isset($_POST['password1']))?$_POST['password1']:"";
$psw2=(isset($_POST['password2']))?$_POST['password2']:"";
$usernameinput = (isset($_POST['username']))?$_POST['username']:"";
$usercode=(isset($_POST['usercode']))?$_POST['usercode']:"";
$urltoken=(isset($_POST['token']))?$_POST['token']:"";
    # If there are some POST empty value  show generic error
  if (!empty($psw1) && !empty($psw2) && !empty($usernameinput) && !empty($usercode) && !empty($urltoken)) {
    # Check if 2 inserted password matches
    if ($psw1 != $psw2){
      $error="1";
    }
    # Check if admin userneame match (from created file)
    if($usernameinput != $username){
        $error="2";
    }
    # Check if inserted code matches (from created file)
    if ($usercode != $code){
        $error="3";
    }
    $now=date("Y-m-d H:i:s");
    // if difference between now ad given time is more than  900 s (15 min) return error
    if (strtotime($now) >= strtotime($when)+900){
        $error="4";
    }
    if ($urltoken !=$token){
      $error="5";
    }
  }
  else
  {
    $error="6";

  }
    # No errors, so proceed with password reset

        if( $error=="0" )
    {
    //  $user_home->redirect('index.php');
    //  Go on with script
    //Create hashed psw
     $hashedpsw=ldap_password_hash($psw2,'ssha');
     $hashedpswesc=escapeshellarg($hashedpsw);
     //Get dn value from site-config.php
     $readdn="cn=".$usernameinput.",".SUFFIX;
     $readdnesc=escapeshellarg($readdn);
     //Change ldap root password
      $delete="0";
      $delete=escapeshellarg(delete);
     $changepswd= shell_exec(" sudo ./update-ps.sh $hashedpswesc $readdnesc $delete 2>&1 >> /tmp/upsateerrors.txt");
     if (!$changepswd) {
           echo "Error in command";
           $url='../reset.php';
     } else {
           $Ldap = new LDAP(); 
           $ldapconn=$Ldap->connect();
           if ($ldapconn){
             $ldapbind=$Ldap->bind($ldapconn,$readdn,$psw2);
           }

           $info['userpassword'][0]=$hashedpsw;
           $change_success=ldap_mod_replace($ldapconn, $readdn, $info );
           //Here we have to restore cn=config pswd to previous value
           //$get $oldhashedpswd from dnconfig.txt
           $olddata=file_get_contents('/tmp/dnconfig.txt');
           preg_match('/^olcRootPW:[^\r\n]*/m', $olddata, $matches);
           $str=$matches[0];
           $newstr=explode(": ",$str);
           $oldpsw=$newstr[1];
           $oldpsw=escapeshellarg($oldpsw);
            $delete="1";
            $delete=escapeshellarg(delete);
           $changepswd= shell_exec(" sudo ./update-ps.sh $hashedpswesc $readdnesc $delete 2>&1 >> /tmp/upsateerrors.txt");
           //
           //
           //$changepswd= exec("./update-ps.sh $oldhashedpsw $readdn",$output, $return);
           ldap_close($ldapconn);
           $url='../login.php?status=ok';


     }
     //
    }
    else
    {
        $url='../reset.php?token='.$urltoken.'&error=' .$error;

    }

header("Location: $url");
/*if (!function_exists("ssh2_connect")) die("function ssh2_connect doesn't exist");
if(!($con = ssh2_connect("localhost", 22))){
  $error="5"; 
} else {
    // try to authenticate with username root, password secretpassword
  if(!ssh2_auth_password($con, $rootusername ,$rootpswinput )) {
      $error="3";
  }
}
if($pass1==$pass2 && $currentpsw==$_POST["oldpsw"] && $error!="3"){
  $hashedpsw=ldap_password_hash($_POST['newpsw2'],'ssha');
  $info['userpassword'][0]=$hashedpsw;
  $change_success=ldap_mod_replace($ldapconn, $modifydn, $info );
  if ($change_success){
    $readdn=READDN;
    //ldap admin has not this attribute.
    if($permissions  != '10') $info['shadowlastchange'][0] = floor(time()/86400);
    # The magic command all in one line. Inspired by this tutorial
    # https://www.digitalocean.com/community/tutorials/how-to-change-account-passwords-on-an-openldap-server
    # if we only change password from the Normal DIT , the old one will still work.
    # We have to change the password also in the Config DIT
    # The best order to do that is:
    # 1 - change password in Normal DIT (preserving the old one in variable
    # 2 - if everything was ok, go on and change password in config DIT
    # 3 - @TODO: If this operation is succesfully, we have finished. If not we must go back to previous pswd in normal DIT
    # $command="export HISTIGNORE='*echo*'; echo '$rootpswinput' |sudo -S ldapsearch -H ldapi:// -LLL -Q -Y EXTERNAL -b 'cn=config' '(olcRootDN=$readdn)' dn olcRootDN olcRootPW | tee /tmp/dnconfig.txt && awk '/^dn\: olcDatabase/' /tmp/dnconfig.txt > /tmp/dnconfig.ldif && echo 'changetype: modify\nreplace: olcRootPW\nolcRootPW: $hashedpsw' >> /tmp/dnconfig.ldif && sudo -S ldapmodify -H ldapi:// -Y EXTERNAL -f /tmp/dnconfig.ldif 2> /tmp/dnconfig.error";
    $command="sudo ldapsearch -H ldapi:// -LLL -Q -Y EXTERNAL -b 'cn=config' '(olcRootDN=$readdn)' dn olcRootDN olcRootPW > /tmp/dnconfig.txt && awk '/^dn\: olcDatabase/' /tmp/dnconfig.txt > /tmp/dnconfig.ldif && echo 'changetype: modify\nreplace: olcRootPW\nolcRootPW: $hashedpsw' >> /tmp/dnconfig.ldif && sudo ldapmodify -H ldapi:// -Y EXTERNAL -f /tmp/dnconfig.ldif 2> /tmp/dnconfig.error";
    // execute  command
    if (!($stream = ssh2_exec($con, $command ))) {
      $error="4";
    }
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    stream_set_blocking($stream, true);
    $errors=stream_get_contents($errorStream);
    //check bash errrors en write to file
    if($errors){
      ssh2_exec($con, "echo $errors >> /tmp/ssherrors.txt");
      $errorfile = fopen("/tmp/ssherrors.txt", "w") or die("Unable to open file!");
      fwrite($errorfile, $errors);
      fclose($errorfile);
      //undo change in DIT, so we have just one password
      $info['userpassword'][0]=$rootuserpass;
      ldap_mod_replace($ldapconn, $modifydn, $info ); 
      $error=4;
      $url='../profile.php?error=' .$error;
      $Ldap->redirect($url);
    }
    fclose($stream);
  }         
  if($change_success != FALSE &&  $stream && $error=="" & !$errors) {
    $Ldap->redirect('../newlogin.php');
  } 
}
 
else
{
$url='../reset.php?error=' .$error; 
$Ldap->redirect($url);
}
*/
