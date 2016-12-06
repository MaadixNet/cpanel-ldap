<?php
/**
 * Created by Maddish
 *
 * function file called by reset.php
 * Perform data validation
 * checking username, email, token and code
 *
 * If everything matches create a .ldif file
 * with new parameters .
 *
 * A cron job will check for tasks to be performed
 *
 *
 */
session_start();
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
$psw1=(isset($_POST['pswd1']))?$_POST['pswd1']:"";
$psw2=(isset($_POST['pswd2']))?$_POST['pswd2']:"";
$usernameinput = (isset($_POST['user']))?$_POST['user']:"";
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

     //Get dn value from site-config.php
     $readdn="cn=".$usernameinput.",".SUFFIX;
      
      //Create ldif file to be used from cron script that will update password

      $checkfile = fopen("/tmp/update-" .$urltoken .".ldif", "w") or die("Unable to open file!");
      $txt="dn: ". $readdn .  PHP_EOL .
            "changetype: modify" . PHP_EOL .
            "replace: userpassword" . PHP_EOL .
            "userpassword: " . $hashedpsw;

      $newjob=fwrite($checkfile, $txt);
      fclose($checkfile); 
      if (!$newjob) $error="6";
  }
if ($error=="0")
  $url = '../processing.php';
else
  $url = '../reset.php?token=' . $urltoken .'&error='. $error;

header("Location: $url");
