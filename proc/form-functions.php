<?php
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';
$Ldap = new LDAP();

if(!$Ldap->is_logged_in())
{
  $Ldap->redirect('login.php');
}
$ldapconn=$Ldap->connect();
if ($ldapconn){
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$_SESSION["login"]["password"]);
}
$ldaptree    = 'ou=People,' . SUFFIX;
$filter="(&(objectClass=person)(type=root))";
$rootuser=$Ldap->search($ldapconn,$ldaptree, $filter);
$rootusername=$rootuser[0]["uid"][0];
//This is current hashed password as stored in DIT
//We need to store in a variable, in case something
//fails and we need to go back to previous password
$rootuserpass=$rootuser[0]["userpassword"][0];
$permissions=$_SESSION["login"]["level"];
$error='';
//Modifiy Passord
$modifydn=$_SESSION["login"]["dn"];
$currentpsw=$_SESSION["login"]["password"];
$pass1=$_POST['newpsw'];
$pass2=$_POST['newpsw2'];
$oldpsw=$_POST['oldpsw'];
$rootpswinput=$_POST['rootpsw'];
if ($oldpsw != $currentpsw){
  $error="1";
}
if($pass1 != $pass2 ){
  $error="2";
}
if (!function_exists("ssh2_connect")) die("function ssh2_connect doesn't exist");
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
$url='../profile.php?error=' .$error; 
$Ldap->redirect($url);
}

