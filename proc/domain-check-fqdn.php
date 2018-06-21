<?php
session_start();
$host_domain=$_POST['domain'];
$customlogmail = $_POST['customlogmail'];
$return_string='';
$errormsg='';
$totalerrors=0;
if ($host_domain){
  /*check domains in o=hosting
  / TODO: write a function that checks if a domain is already present in o=hosting
  // Instead of using following code
  */
  require_once __DIR__.('/../classes/class.ldap.php');
  $Ldap= new LDAP();
  $ldapconn=$Ldap->connect();
  $psw=$Ldap->decrypt_psw();
  if ($ldapconn){
      $binddn=LDAP_BASE;
      $filter="(vd=*)";
      $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw);
      $vds=$Ldap->search($ldapconn,$binddn, $filter);
  }

  // Get this servernam
//  $fqdn=trim(shell_exec('hostname -f'));

  //Start checking in this is a valid domain
  //check domain is not main fqdn
    $validdomain=applySanitize("domain",$host_domain);
    $hostname = trim(shell_exec('hostname'));
    $fqdn=trim(shell_exec('hostname -f'));
    $domain = $hostname.'.' . $host_domain;

    
    //Domain has invalid format
    if(empty($validdomain["value"])){
      $totalerrors++;
      $error=1;
      $errormsg=$validdomain["message"];
    } else if ($domain == $fqdn){
        $totalerrors++;
        $error=2;
        $errormsg.= sprintf(_("No hay cambios. el dominio que has insertado es igual al que está en uso. Debes de introducir un nombre de dominio diferente al actual."));

    } else if( !empty($vds) && array_search($domain, array_column(array_column($vds, 'vd'),0)) !== false){

        $totalerrors++;
        $error=3;
        $errormsg=sprintf(_("El dominio %s ya está dado de alta como dominio web o mail."), $domain);
        $errormsg.='<br />';
        $errormsg.= sprintf(_("Si quieres utilizarlo como nombre de servidor, deberás primero eliminarlo desde el listado de dominios"));
        //Some other applicaction may ne using same domain. This is not allowed
    } else if($Ldap->is_domain_in_use($domain)) {
      
        $totalerrors++;
        $error=4;
        $errormsg=sprintf(_("El dominio %s ya está asignado a otra aplicación."), $domain);

    } else { 
        // Check all dns records
        $server_ipaddr=$_SERVER["SERVER_ADDR"];
        $resultA=dns_get_record ( $domain,  DNS_A );
        $resultMX=dns_get_record ( $domain,  DNS_MX );
        $resultNS = dns_get_record($domain,  DNS_NS );
        $resultTXT = dns_get_record($domain,  DNS_TXT );
       // $spf_record = ($resultTXT && $resultTXT[$c]['entries'][0])?$resultTXT[$c]['entries'][0]:false;
        $command= "dig default._domainkey." . $domain ." TXT | grep -o -P \"(?<=TXT).*\"";
        $resultDKIM = shell_exec($command); 
        $resultDKIM = str_replace('\\', '', $resultDKIM);


        /*
        * Check A records
        * DNS errors have vlaue $error>=5
        * So we can manage different error message strngs
        */

        $ipA = ($resultA[0]['ip'])?$resultA[0]['ip']:'';
        if ($ipA==$server_ipaddr){
            $error=0;
            $errormsg='';
        } else {
            $totalerrors++;
            $error=5;
            $errormsg=sprintf(_("El registro A para el dominio %s debe apuntar a la IP %s"), $domain, $server_ipaddr);
            $errormsg.='<br />';

        }   
        /*
        * Check MX records
        */

        if (!$resultMX){
            $totalerrors++;
            $errormsg.= sprintf(_('No se ha encontrado ningún registro MX'));
            $errormsg.='<br />';
            $error=6;
        } else {
          $i=1;
          foreach($resultMX as $value){
            if($value['target'] != $domain){
              $totalerrors++;
              $errormsg.= sprintf(_('Valor incorrecto para el registro MX %s'),$value['target']);              
              $errormsg.='<br />';
              $error=7;
              $i++;
            }
          } //end foreach mx as value
        } //endi if $resultMX

        /*
        * Check SPF records
        */

        $c=0;
        $spf_present=0;
        $correct_spf = "v=spf1 a mx ~all";
        if (!$resultTXT){
          $totalerrors++;
          $errormsg.= sprintf(_("No se ha encontrado ningún registro SPF"));
          $errormsg.='<br />';
          $error=8;
        } else {
          foreach($resultTXT as $txtvalue){
          // Get the txt record string
            //Only check spf records
            $spf_record = $resultTXT[$c]['entries'][0];
            if (strpos($spf_record, 'v=spf') !== false) {
                $current_spf = ($txtvalue && $txtvalue['host'])?$txtvalue['host']:'';  
                if ($spf_record != $correct_spf ){
                  $totalerrors++;
                  $errormsg.= sprintf(_("El registro SPF %s es incorrecto"), $spf_record);
                  $errormsg.='<br />'; 
                  $error=8;
                  $c++;
                } //end if spf=correct_spf
            } // end if spf is present in string
          } //end foreach spf txt reocrds
        } // end if txt found

    } //End checking all dns records
} else { //End if domain
  $totalerrors++;
  $error=1;
  $errormsg = sprintf(_("El campo dominio es obligatrio"));
}
// Build the form inside the MOdal window.
// The submit button 
if ($totalerrors>0){
  //$errormsg='<span class="has-error">'.  $errormsg . '</span>';
  //$return_value['errors'][] = array('error' =>$error , 'msg' => $errormsg, 'fieldValue' => $value);
  if ($error>=5) {
    $return_string = sprintf(_("La configuración de los DNS para el dominio %s es incorrecta."),$domain);
    $return_string .= '<br><br>';
  }
  $return_string .= $errormsg;
  $return_string .= '<br>';
  $return_string .= sprintf(_("Corrige los errores para poder seguir"));
  $return_string .= '<br>';
  $return_string .= " <div class='modal-footer'><button type='button' class='btn btn-primary' data-dismiss='modal'>" . sprintf (_("Cancelar")) . "</button></div>";
} else {

  /* Alert user taht all the deactived groups will be activated
   */
  //get array with aal deactvated groups
  $serv_disabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=disabled)(type=installed))');
  $return_string='
             <form id="fieldset" method="POST" action="">
            <div class="form-group">' .
              sprintf(_("Este proceso durará unso minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso termine se activará el formulario para volver a acceder.")) . 
              '</div><br>
              <div class="modal-footer">
              <input type="hidden" name="domain" value="' . $host_domain .'" />
              <input type="hidden" name="logmailctive" value="' . $customlogmail .'" />
              <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">'. sprintf (_("Cancelar")) . '</button>
              <input type="submit" class="btn btn-primary btn-sm" id="changenameserver" name="changenameserver" value="' . sprintf(_("Continuar")) .'"
              <div class="fields-info" id="fields-info">
              </div>
            </div>

          </form>';

}
echo $return_string;
