<?php 
session_start();
require_once 'classes/class.ldap.php';
require_once 'classes/class.psql_query.php';
$Ldap= new LDAP();
$sqlDomains= new Domains();
$mailman_domains= $sqlDomains->getDomains();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

require_once('header.php');
//connect and BInd
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';

$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
$permissions= $_SESSION["login"]["level"];
if ($ldapconn && $permissions==10){
	$ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"]  ,$psw); 
        $ldaptree=LDAP_BASE;
        $filter="(&(vd=*)(accountActive=TRUE))";
        $attributes_vd=array("vd");
        // Get all domains with email activated
        $sr =ldap_search($ldapconn,$ldaptree, $filter,$attributes_vd);
        $activeMailDomains = ldap_get_entries($ldapconn, $sr);
      }


require_once('sidebar.php');

/*
$conn = pg_pconnect("dbname=mailman");
if (!$conn) {
      echo "An error occurred.\n";
          exit;
}

$result = pg_query($conn, "SELECT * FROM domain");
if (!$result) {
      echo "An error occurred.\n";
          exit;
}

$mailman_domains = pg_fetch_all($result);
 */
//print_r($mailman_domains); 

?>
<div id="admin-content" class="content">
    <?php if($message) echo $message;?>
    <div class="row">

	<div class="inner" id="maincol">

    <?php 
    if($mailman_domains){
      /* If there are some domains in mailman database
       * check that same domain has not been activated 
       * to be used as email domain. Using same domain
       * for lists and for normail email can be a problem.
       */
      // get a fullist of domain from ldap whit the email activated
      ?>
        <table id="domains">
        <thead>
        <tr>
            <th>Dominio</th>
            <th>Descripción</th>
            <th>DNS</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>

<?php

    foreach ($mailman_domains as $domain) {
       if( !empty($activeMailDomains) && array_search($domain["mail_host"], array_column(array_column($activeMailDomains, 'vd'),0)) !== false){

        $domain_status=  sprintf(_("Si usas este dominio para listas de correo puedes experimentar problemas. Ya se está utilizando para correo electrónico. Por favor utiliza otro o deshabilita el servidor de corro para este dominio"));

      } else {
        $domain_status=$statok;
      }
        echo "<tr>";
        echo "<td>";
        echo $domain["mail_host"];
        echo "</td>";
        echo "<td>";
        echo $domain["description"];
        echo "</td>";
        echo "<td class='center'>";
        echo "<a href='editdns.php?domain=" . $domain["mail_host"] ."'>Ver</a>";
        echo "</td>";
        echo "<td class='center domainstatus' data-domain='" . $domain["mail_host"] . "'>";
        echo $domain_status;
        echo "</td>";
        echo "</tr>";

    }
  
?>
        </tbody>
    </table>
  </div><!--ineer-->
<?php } else {

printf(_("<h4>No hay ningún dominio activado para listas de correo.</h4>
          <h4>Puedes activar dominios desde la aplicación Mailman <a target=\"_blank\" href=\"/mailman\"><button type='button' class='btn btn-pill-right btn-primary'>Añadir dominios para listas de correo</button></a></h4>"));
          printf(_("<h5>En caso de dudas puedes consultar las instrucciones en esta página: <a href=\"https://docs.maadix.net/mailman/\" target=\"_blank\">https://docs.maadix.net/mailman/</a></h5>"));

      }?>
<!-- Modal -->
<div class="bd-example">
  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="exampleModalLabel"></h4>
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
  require_once('footer.php');?>
