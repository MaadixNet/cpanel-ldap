<?php 
session_start();
require_once 'classes/class.psql_query.php';
require_once('header.php');
$errorttpe="";
$message="";
$statok='<i class="fa fa-check-circle-o icon checkok"></i>';
$loading='<span class="loading"></span>';
require_once 'classes/class.psql_query.php';
$sqlDomains= new Domains();
$mailman_domains= $sqlDomains->getDomains();
if ($ldapconn && $permissions==10){
        $ldaptree=LDAP_BASE;
        $filter="(&(vd=*)(accountActive=TRUE))";
        $attributes_vd=array("vd");
        // Get all domains with email activated
        $sr =ldap_search($ldapconn,$ldaptree, $filter,$attributes_vd);
        $activeMailDomains = ldap_get_entries($ldapconn, $sr);
      }


require_once('sidebar.php');


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

        $domain_status=  sprintf(_("Si usas este dominio para listas de correo puedes experimentar problemas. Ya se está utilizando para correo electrónico. Por favor utiliza otro o deshabilita el servidor de correo para este dominio"));

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
        echo "<a href='editdns.php?domain=" . $domain["mail_host"] ."'>" . sprintf(_('Ver')) . "</a>";
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
      echo '<h4>';
      printf(_("No hay ningún dominio activado para listas de correo."));
      echo '</h4>';
      echo '<h4>';
      printf(_("Puedes activar dominios desde la aplicación Mailman "));
      echo '<a target="_blank" href="/mailman"><button type="button" class="btn btn-pill-right btn-primary">' . sprintf(_("Añadir dominios para listas de correo")) . '</button></a>';
      echo '</h4>';
      echo '<h5>';
      printf(_("En caso de dudas puedes consultar las instrucciones en esta página: "));
      echo '<a href=\"https://docs.maadix.net/mailman/\" target=\"_blank\">https://docs.maadix.net/mailman/</a></h5>';

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
