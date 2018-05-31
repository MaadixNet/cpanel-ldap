<?php 

session_start();
$message='';
$app=$_GET['app'];

require_once 'classes/class.ldap.php';
  require_once('header.php');
if ($ldapconn && $ldapbind){

  //Get  current app
    $filter="(ou=". $app . ")";
    $serv_target= $Ldap->search($ldapconn, LDAP_SERVICES ,$filter);
   // $serv_disabled= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=disabled)(type=installed))');
     

//Modifiy sender email account 
  if(isset($_POST['update-app-conf'])){
    //Check DNS
    $entry["cn"] =$modifydn; 
    ldap_mod_replace($ldapconn,'ou=sendermail,' . SUFFIX, $entry);
  }
  
  require_once('sidebar.php');

  
  $fqdn=trim(shell_exec('hostname -f'));

}?>
<article class="content forms-page">
    <div class="title-block">
      <?php
      /* TODO: use the name from the api, not the group name
      *  And maybe get the image too
       */
      ?>
      <h3 class="title"> <?php printf(_("Editar configuración para %s"),$app);?></h3>
    </div>
    <section class="section">
        <div class="row">

          <div class="col-sm-12">
            <div class="inner"i id="maincol">
              <?php
              echo $message; 
              echo '<pre>';
              print_r($serv_target);
              echo '</pre>';
              sprintf(_("Actualmente, el dominio de la aplicación es")) . '<br>
              <div class="box-placeholder">'. $serv_target[0]['domain']['status'] .'</div>';

                printf(_('Puedes cambiar esta configuración y elegir otro dominio'));
                echo '<br>';
                printf(_('Para cambiar este valor, elige un correo electrónico disponible en el listado y haz clic en Guardar'));?>

                <form autocomplete="off" action="" method="POST" class="form-signin standard">
                <hr>
                <?php
                echo 'print here the form. use same methods as in services-available';
              ;?>

              <br>
              <hr>
              <input type="submit" name="update-app-conf" value="<?php printf(_('Guardar'));?>" class="btn btn-small btn-primary" />
            </form>
            <?php 
           //end if domain not =  0?>
        </div><!--inner-->

      </div><!--col-sm-12-->
  </div><!--row-->
</section>
</article>
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');
