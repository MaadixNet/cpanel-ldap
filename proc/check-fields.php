<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Check required fields of groups
 *
 */
session_start();
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';

//post data
////maybe don't need this
//Errors can be stores in ubique vlaue totalerrors
$error=0;
$errormsg='';
$totalerrors=0;
//We  are receiving an array from  customscript.js
$fields=$_POST;
$domain=$_POST['domain'];
$keys=[];
$return_value=array();
foreach($_POST as $key=>$value){
    switch($key){
      case 'domain':
      //do domain check
      //This checr returns an array to be used in customscript.js
      // and populate the hidden inputs with user inserted values
      include __DIR__.'/../includes/domain-check-install.php';
      break;
      case 'email';
      //no check needed. input field domain does the check
      //just sanitize maybe
      $return_value['inputs'][]=array('fieldValue' => $value, 'fieldId'=> $key);
      //This never happens. save this code in case we need to add checks to email fields
      if (1==2){
        $totalerrors++;
        $return_value['errors'][] = array('error' =>$error , 'msg' => $errormsg, 'fieldValue' => $value, 'fieldId'=> $key);
      } 
      break;
      default:
      //This is for text fields
      //Dont' know what we nwwd
      //just return the string
      $return_value['inputs'][]=array('fieldValue' => $value, 'fieldId'=> $key);
      //This never happens. save this code in case we need to add checks to text fields
      if (1==2){
        $totalerrors++;
        $return_value['errors'][] = array('error' =>$error , 'msg' => $errormsg, 'fieldValue' => $value, 'fieldId'=> $key);
      }

      break;
    }   
}
$return_value['totErros']= $totalerrors;
if ($totalerrors >0) {
  $return_value['formError']='<span class="has-error">'. sprintf(_('Algún valor no es correcto. Corrige los campos marcados con error' )). '</span>';
}
$result = json_encode($return_value);
echo $result;
