<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();
$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);
require_once('header.php');
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 
}

//Only admin can see this page
if ($permissions==2){
    $Ldap->redirect('404.php');
}

//Gett all installed services
if ($ldapbind) { 
  $serv_installed= $Ldap->search($ldapconn, LDAP_SERVICES ,'(&(objectClass=organizationalUnit)(status=enabled))');
}
// var_dump($serv_enabled);
    

// Get current language and swith url
/*if ($_SESSION["language"]=="en_GB"){ 
  $url = 'https://maadix.net/en/services-available.php';
} else {
  $url = 'https://maadix.net/services-available.php';  
}
 */
$url = "https://maadix.net/service.json";
//echo file_get_contents($url);
require_once('sidebar.php');
$json = file_get_contents($url);
$obj = json_decode($json, true);
?>


            <?php
            //echo "<h2>" . sprintf(_("Servicios disponibles")) . "</h2>";
            //echo $obj->Apps->Mysql->title;
            echo '<pre>';
            //print_r($obj);
            echo '</pre>';
            echo $obj[0]["name"];
?>

<article class="content cards-page">
            <div class="title-block">
                <h3 class="title"> <?php printf(_("Aplicaciones Disponibles"));?> </h3>
                <p class="title-description"> <?php printf(_("La instalación automática de las aplicaciones no está todavía disponible. Si quieres añadir una o más de estas aplicaciones ponte en contacto con el team de Maadix en: contact@maadix.net"));?> </p>
            </div>
                 <section class="section">
                        <div class="row ">
                       <?php $c = 0;?> 
                       <?php foreach ($obj as $service_data ) {
                          if ( $c % 3 == 0 ){; ?>
                           <div class="clearfix visible-xs"></div>
                          <?php }
                          ?>
                            <?php if( !empty($serv_installed) && array_search($service_data['id'], array_column(array_column($serv_installed, 'ou'),0)) == false){?>
                            <div class="col-xl-4">
                                <div class="card ">
                                    <div class="card-block">
                                        <!-- Nav tabs -->
                                        <div class="card-title-block">
                                            <h3 class="title"><?php echo  $service_data['title'];?></h3>
                                        </div>
                                        <ul class="nav nav-tabs nav-tabs-bordered">
                                            <li class="nav-item"> <a href="#home-<?php echo $c;?>" class="nav-link active" data-target="#home-<?php echo $c;?>" data-toggle="tab" aria-controls="home-<?php echo $c;?>" role="tab">App</a> </li>
                                            <li class="nav-item"> <a href="#desc-<?php echo $c;?>" class="nav-link" data-target="#desc-<?php echo $c;?>" aria-controls="desc-<?php echo $c;?>" data-toggle="tab" role="tab"><?php printf(_("Descripción"));?></a> </li>

                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabs-bordered">
                                            <div class="tab-pane fade in active" id="home-<?php echo $c;?>">
                                                <h4></h4>
                                                <p><div class='img service-img'><img src="<?php echo $service_data['img'];?> " /></div></p>
                                            </div>
                                            <div class="tab-pane fade" id="desc-<?php echo $c;?>">
                                                <h4><?php echo $service_data['name'];?></h4>
                                                <p><?php echo $service_data['description'];?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.card-block -->
                                </div>
                                <!-- /.card -->
                            </div>
                            <!-- /.col-xl-4 -->
                          <?php $c++;
                            }
                          };?>
                        </div>
                    </section>

</article>


<?php
  ldap_close($ldapconn);   
  require_once('footer.php');?>


