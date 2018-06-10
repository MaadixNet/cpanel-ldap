<?php 

session_start();
$message='';
$app=$_GET['app'];

require_once 'classes/class.ldap.php';
require_once('header.php');
/*TODO:
* Check if app is installed and activated
* if not, show blank page or error message
*/

$status = $Ldap->getpuppetstatus();
if ($ldapconn && $ldapbind){
  // Get current release info
   $release_info = $Ldap->getreleaseinfo('release');
  /*
   * Get available groups in the release
   * Extract dependecies from the api, to make a comparision
   * with LDAP.
   * User can only update thes data, not other such us version
   */
  $obj = $release_info['groups'];
  $service_data=array();
  $depsfields=array();
  foreach ($obj as $serv_data => $val)
    {
      if ( $val['id'] == $app ){
        $deps=$val['dependencies'];
        $service_data=$val;
      }
   }
  /*
   * Get  current app data from LDAP
   * Checking if it is enabled and activavted
   *
   */


    //$filter="(ou=". $app . ")";
    $filter = '(&(objectClass=organizationalUnit)(ou=' . $app .')(status=enabled)(type=available))';
    $serv_target= $Ldap->search($ldapconn, LDAP_SERVICES ,$filter);
     

//Modifiy sender email account 
  if(isset($_POST['confirmChage'])){
    //Check DNS
    if (isset($_POST['inputDep'])) $inputDep = $_POST['inputDep'];
    if (isset($_POST['inputDep'])) $inputDepOld = $_POST['inputDep'];

    foreach ($inputDep as $key => $value){

        foreach ($value as $dep => $val){
  // Get all the fields from ldap
          //ou=domain,ou=rocketchat,ou=groups,dc=example,dc=tld
          $entrydn='ou='. $dep .',ou=' . $key .',' . LDAP_SERVICES;
          //Modify domain object
          $info = array();
          $info['status'] = $val;
          $changeValues = ldap_mod_replace($ldapconn,$entrydn,$info);
        }

      }

        if ($changeValues){
            $Ldap->lock_cpanel_puppet_master();

        }
  }

  
  require_once('sidebar.php');

  
  $fqdn=trim(shell_exec('hostname -f'));
  $filter = '(&(objectClass=organizationalUnit)(ou=*)(!(ou=' . $app .')))';
  // Get all the fields from ldap
  $resultapp= $Ldap->search($ldapconn,'ou='. $app . ','. LDAP_SERVICES,$filter);  
}?>
<article class="content forms-page">
    <div class="title-block">
      <?php
      /* TODO: use the name from the api, not the group name
      *  And maybe get the image too
       */
      ?>
      <h3 class=i"title"> <?php printf(_("Configuración de la aplicación %s"),$service_data['name']);?></h3>
    </div>
    <section class="section">
        <div class="row">

          <div class="col-sm-12">
            <div class="inner" id="maincol">

              <?php
                if ($serv_target["count"] < 1) {                                                                                   
                  printf(_("La aplicación %s no está activada y no se pueden editar las configuraciones"), $service_data['name']);

                } else {
                  /*
                  * Create array with all dependencies with an inpput field
                  */

                  $depsfields=array();
                  foreach ($deps as $value) {
                    $is_input_field=get_dependencies_properties($value);
                    if($is_input_field)$depsfields[]=get_dependencies_properties($value);
                  }
                  if (!$depsfields) {
                      printf(_("La aplicación %s no tiene ningún parámetro de instalación editable"),$service_data['name'] );        

                  } else {
?>
                  <form id="updateDependencies" autocomplete="off" action="" method="POST" class="form-signin standard">
                    <div class="form-elements" id="form-elements">
                      <input type="hidden" name="application" value="<?php echo $app;?>"  />
                      <?php
                      echo $message;
                      echo '<p>';
                      printf(_("En esta página puedes consultar y modificar los valores de configuración para la aplicación %s,"),$service_data['name'] );
                      echo '</p>';

                    foreach($depsfields as $field) {
                      $filter="(ou=". $field['id'] .")";
                      $dn="ou=".  $field['id'] .",ou=". $app . ", " . LDAP_SERVICES;
                      $ldap_dep_object = $Ldap->search($ldapconn, $dn ,$filter);
                      $dep_value=$ldap_dep_object[0]['status'][0];
                      echo '<div class="form-group">';
                      echo '<label for="' . $field['label'] . '">' . $field['label']. '</label>';
                      echo '<br>';
                      echo '<p>' . $field['description'] . '</p>';
                      echo '<div id="error-' . $field['id'] .  '" class="errorrecords"></div>';
                       
                      echo '<input class="form-control boxed" id="' . $field['id'] . '" name="inputDep['.$app.']['. $field['id']. ']" data-dependency="'.$field['id'] .'" type="' .$field["type"] . '" data-oldvalue="' . $dep_value . '" value="' . $dep_value . '" required />';
                      echo '<input id="old-' . $field['id'] . '" name="inputDepOld['.$app.']['. $field['id']. ']" type="hidden" value="' . $dep_value . '" required />';
                      echo '</div>';
                }
                echo '<button id="checkFieldsUpdate" type="button"class="btn btn-sm btn-primary">' .sprintf(_('Guardar')) .'</button>';

              ?>
                  <br>
                  <hr>
                   </div><!--form-element-->        
                  <div id="lastConfirm" class="lastConfirm hide">
                   <?php  printf(_("Estás a punto de cambiar la configuración para la aplicación %s"),$service_data['name']);
                   echo '<br>';
                   printf(_("El proceso durará unos minutos durante los cuales el Panel de Control quedará inactivo. Todos los usuarios que tengan una sesión activa serán forzados a salir y redireccionados a una página en la que se mostrará el estado de la operación. Cuando el proceso termine se activará el formulario para volver a acceder."));?> 
                    <hr>
                    <input type="submit" id="confirmChage" name="confirmChage" value="<?php printf(_('Continuar'));?>" class="btn btn-lg btn-primary" />
                    <button id="checkBack" type="button"class="btn btn-sm btn-secondary"><?php printf(_('Volver'));?></button>
                </div><!--lastConfirm-->
            </form>
            <?php 
            } //end if $depsfields
          }
           //end if !serv_target?>
        </div><!--inner-->
      </div><!--col-sm-12-->
  </div><!--row-->
</section>
</article>
<?php
  ldap_close($ldapconn);   
  require_once('footer.php');
?>
