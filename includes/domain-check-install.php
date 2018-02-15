<?php
if ($domain){
  /*check domains in o=hosting
  / TODO: write a function that checks if a domain is already present in o=hosting
  // Instead of using following code
  */

  $Ldap= new LDAP();
  $current_page=basename(__FILE__);
  $Ldap->check_login_or_redirect($current_page);
  $ldapconn=$Ldap->connect();
  $psw=$Ldap->decrypt_psw();
  if ($ldapconn){
      $binddn=LDAP_BASE;
      $filter="(vd=*)";
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw);
      $vds=$Ldap->search($ldapconn,$binddn, $filter);
  }

  // Get this servernam
  $fqdn=trim(shell_exec('hostname -f'));

  //Start checking in this is a valid domain
  //check domain is not main fqdn
    $validdomain=applySanitize("domain",$domain);
    //Domain has invalid format
    if(empty($validdomain["value"])){
      $totalerrors++;
      $error=1;
      $errormsg=$validdomain["message"];
    } else if ($domain == $fqdn){
        $totalerrors++;
        $error=2;
        $errormsg.=sprintf(_("El dominio %s está reservado para el sistema y no se puede usar para la aplicación."), $domain);
        $errormsg.='<br />';
        $errormsg.= sprintf(_("Debes de introducir un nombre de dominio o subdominio diferente al de tu servidor."));

    } else if( !empty($vds) && array_search($domain, array_column(array_column($vds, 'vd'),0)) !== false){

        $totalerrors++;
        $error=3;
        $errormsg=sprintf(_("El dominio %s ya está dado de alta como dominio web o mail."), $domain);
        $errormsg.='<br />';
        $errormsg.= sprintf(_("Si quieres utilizarlo para esta aplicación deberás primero eliminarlo desde el listado de dominios"));
        //Some other applicaction may ne using same domain. This is not allowed
    } else if($Ldap->is_domain_in_use($domain)) {
      
        $totalerrors++;
        $error=4;
        $errormsg=sprintf(_("El dominio %s ya está asignado a otra aplicación."), $domain);

    } else {
    //Get this server IP address
    $server_ipaddr=$_SERVER["SERVER_ADDR"];
    //check DNS for each domain
    //printf(_("Comprobación de DNS de dominios requeridos"));
        $server_ipaddr=$_SERVER["SERVER_ADDR"];
        $resultA=dns_get_record ( $domain,  DNS_A + DNS_CNAME );
        $ipA = $resultA[0]['ip'];
        if ($resultA[0]['ip']==$server_ipaddr){
            $error=0;
            $errormsg='';
        } else {
            $totalerrors++;
            $error=5;
            $errormsg=sprintf(_("La configuración de los DNS para el dominio %s es incorrecta. El registro A para este dominio debe apuntar a la IP %s."), $domain, $server_ipaddr);
            $errormsg.='<br />';
            $errormsg.= sprintf(_("Corrige este valor en el panel de administración de tu proveedor de dominio"));

      }   

    } //End cases for doamins
}//End count domains 

// Build the form inside the MOdal window.
// The submit button 
if ($totalerrors>0){
  $errormsg='<span class="has-error">'.  $errormsg . '</span>';
  $return_value['errors'][] = array('error' =>$error , 'msg' => $errormsg, 'fieldValue' => $value, 'fieldId'=> $key);
}
$return_value['inputs'][]= array('fieldValue' => $domain, 'fieldId'=> $key);

