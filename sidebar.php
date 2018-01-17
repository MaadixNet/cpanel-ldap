<?php
?>
                <aside class="sidebar">
                    <div class="sidebar-container">
                        <div class="sidebar-header">
                            <div class="brand">
                                <div class="logo"> <img src="images/logo_maadix_white.png" tilte="MaadiX Home" alt="MaadiX Panel" /> </div> MAADIX</div>
                            </div>
                      <?php if( ($_SESSION["login"]["status"] == "active") && $permissions >2 ){?>
                        <nav class="menu">
                            <ul class="nav metismenu" id="sidebar-menu">
                               <?php if($permissions==10){?>
                                <li class="home">
                                  <a href=""> <i class="fa fa-home"></i><?php printf(_("Sistema"));?><i class="fa arrow"></i> </a>
                                  <ul>
                                    <li class="dashboard">
                                      <a href="/<?php echo BASE_PATH;?>/#/basic-info"> <i class="fa fa-home"></i> <?php printf(_("Detalles"));?></a>
                                    </li>
                               
                                    <li><a href="/<?php echo BASE_PATH;?>/check-updates.php"> <i class="fa fa-refresh"></i> <?php printf(_("Actualizar"));echo $has_updates;?></a>
                                    </li>
   
                                    <li>
                                      <a href="/<?php echo BASE_PATH;?>/reboot.php"> <i class="fa fa-power-off"></i> <?php printf(_("Reinicar")); echo $need_reboot;?></a>
                                    </li>
                                  </ul>
  
                                <li>
                                <a href=""> <i class="fa fa-th-large"></i><?php printf(_("Mis aplicaciones"));?><i class="fa arrow"></i> </a>
                                    <ul>
                                    <li> <a href="/<?php echo BASE_PATH;?>/services.php">
                                          <?php printf(_("Ver todas"));?>
                                                        </a> </li>
                              <?php if( !empty($serv_installed) && array_search('owncloud', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>

                                <li>
                                  <a target="_blank" href="/owncloud"><i class="fa fa-cloud"></i> <?php printf(_("Owncloud"));?></a>
                                </li>
                              <?php }?>
                             <?php if( !empty($serv_installed) && array_search('phpmyadmin', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                              <li>
                                <a href=""> <i class="fa fa-list-alt"></i> Mysql <i class="fa arrow"></i> </a>
                                <ul>
                                  <li><a href="https://docs.maadix.net/mysql/" target="_blank"><?php printf(_("Instrucciones"));?></a></li>
                                  <li><a target="_blank" href="/phpmyadmin"><?php printf(_("phpMyAdmin"));?></a></li>
                                </ul>
                              </li>
                              <?php }?>
                             <?php if( !empty($serv_installed) && array_search('etherpad', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                              <li>
                                <a href=""> <i class="fa fa-list-alt"></i> Etherpad <i class="fa arrow"></i> </a>
                                <ul>
                                  <li><a href="https://docs.maadix.net/etherpad/" target="_blank"><?php printf(_("Instrucciones"));?></a></li>
                                  <li><a target="_blank" href="/etherpad/admin/"><?php printf(_("Etherpad admin"));?></a></li>
                                  <li><a target="_blank" href="/etherpad/"><?php printf(_("Etherpad app"));?></a></li>
                                </ul>
                              </li>
                              <?php }?>
                              <?php if( !empty($serv_installed) && array_search('rainloop', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>

                                <li>
                                  <a target="_blank" href="/rainloop"><i class="fa fa-envelope-o"></i> <?php printf(_("Webmail"));?></a>
                                </li>
                              <?php }?> 
                             <?php if( !empty($serv_installed) && array_search('mailman', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                              <li>
                                <a href=""> <i class="fa fa-mail-reply-all"></i> Mailman<i class="fa arrow"></i> </a>
                                <ul>
                                  <li><a href="https://docs.maadix.net/mailman/" target="_blank"><?php printf(_("Instrucciones"));?></a></li>
                                  <li><a  href="/<?php echo BASE_PATH;?>/mailman-domains.php"><?php printf(_("Dominios de la lista"));?></a></li>
                                  <li><a target="_blank" href="/mailman"><?php printf(_("Ir a la aplicación"));?></a></li>
                                </ul>
                              </li>
                              <?php }?> 
                             <?php if( !empty($serv_installed) && array_search('rocketchat', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                              <li>
                                <a href=""> <i class="fa fa-random"></i> Rocketchat<i class="fa arrow"></i> </a>
                                <ul>
                                  <li><a href="https://rocket.chat/docs/" target="_blank"><?php printf(_("Documetación"));?></a></li>
                                  <li><a target="_blank" href="/rocketchat"><?php printf(_("Ir a la aplicación"));?></a></li>
                                </ul>
                              </li>
                              <?php }?>

                                    </ul>
                                <li><a href="/<?php echo BASE_PATH;?>/service-available.php"> <i class="fa fa-dashboard"></i> <?php printf(_("Instalar aplicaciones"));?></a>
                                </li>
                                <li>
                                <a href=""> <i class="fa fa-globe"></i><?php printf (_("Dominios"));?> <i class="fa arrow"></i> </a>
                                <ul>
                                    <li><a href="/<?php echo BASE_PATH;?>/view-domains.php"><?php printf(_("Ver Dominios"));?></a></li>
                                    <li><a href="/<?php echo BASE_PATH;?>/add-domain.php"><?php printf(_("Añadir Dominio"));?></a></li>
                                    <li><a href="/<?php echo BASE_PATH;?>/domain-instruccions.php"><?php printf(_("Instrucciones"));?></a></li>
                                </ul>
                                </li>
                                <?php }?>
                                <?php if((!empty($serv_installed) && array_search('mail', array_column(array_column($serv_installed, 'ou'),0)) !== false) || $permissions >2 ){?>
                                <li>
                                <a href=""> <i class="fa fa-envelope-o"></i> <?php printf (_("Correo"));?><i class="fa arrow"></i> </a>
                                    <ul>
                                     <li><a href="/<?php echo BASE_PATH;?>/mails.php"><?php printf(_("Cuentas de correo"));?></a></li>
                                     <?php if( !empty($serv_installed) &&  array_search('rainloop', array_column(array_column($serv_installed, 'ou'),0)) !== false){?>
                                      <li><a href="/rainloop" target="_blank"><?php printf(_("Webmail"));?></a></li>
                                     <?php } ?>

                                    </ul>
                                </li>
                                <?php } ?>
                                <?php if ($permissions >= 10) {?>
                                <li>
                                  <a href=""><i class="fa fa-users"></i> <?php printf(_("Usuarios"));?> <i class="fa arrow"></i> </a>
                                  <ul>
                                    <li><a href="/<?php echo BASE_PATH;?>/usuarios.php"><?php printf(_("Usuarios ordinarios"));?></a></li>
                                    <li><a href="/<?php echo BASE_PATH;?>/edit-supuser.php"><?php printf(_("Superusuario"));?></a></li>
                                    <li><a href="/<?php echo BASE_PATH;?>/view-postmasters.php"><?php printf(_("Postmasters"));?></a></li>
                                  </ul>
                                </li> 
                                  <li><a href="/<?php echo BASE_PATH;?>/notificaciones.php"><i class="fa fa-mail-forward"></i><?php printf(_("Notificaciones"));?></a></li>
                                <?php } ?>

                                <li>
                                    <a href=""> <i class="fa fa-book"></i><?php printf(_("Documentación"));?><i class="fa arrow"></i> </a>
                                    <ul>
                                      <li><a href="https://docs.maadix.net/" target="_blank"><?php printf(_("Panel de control"));?></a></li>

                                      <li><a href="https://doc.owncloud.org/server/latest/ownCloud_User_Manual.pdf" target="_blank"><?php printf(_("Owncloud"));?></a></li>

                                    </ul>
                                </li>

                          </ul>

                        </nav>
                    <?php }?>
                    </div>
                    <footer class="sidebar-footer">
                        <ul class="nav metismenu" id="customize-menu">
                             <!-- <li>
                                  <?php
                                  /* 
                                  require_once 'classes/class.locale.php';
                                  $locale = new CpanelLocale();

                                  echo $locale->locale_select();
                                 */
                                  ?>


                              </li>-->

                            <li>
                                <ul>
                                    <li class="customize">
                                        <div class="customize-item">
                                            <div class="row customize-header">
                                                <div class="col-xs-4"> </div>
                                                <div class="col-xs-4"> <label class="title">fixed</label> </div>
                                                <div class="col-xs-4"> <label class="title">static</label> </div>
                                            </div>
                                            <div class="row hidden-md-down">
                                                <div class="col-xs-4"> <label class="title">Sidebar:</label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="sidebarPosition" value="sidebar-fixed" >
                                                        <span></span>
                                                    </label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="sidebarPosition" value="">
                                                        <span></span>
                                                    </label> </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-4"> <label class="title">Header:</label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="headerPosition" value="header-fixed">
                                                        <span></span>
                                                    </label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="headerPosition" value="">
                                                        <span></span>
                                                    </label> </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-4"> <label class="title">Footer:</label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="footerPosition" value="footer-fixed">
                                                        <span></span>
                                                    </label> </div>
                                                <div class="col-xs-4"> <label>
                                                        <input class="radio" type="radio" name="footerPosition" value="">
                                                        <span></span>
                                                    </label> </div>
                                            </div>
                                        </div>
                                        <div class="customize-item">
                                            <ul class="customize-colors">
                                                <li> <span class="color-item color-red" data-theme="red"></span> </li>
                                                <li> <span class="color-item color-orange" data-theme="orange"></span> </li>
                                                <li> <span class="color-item color-green active" data-theme=""></span> </li>
                                                <li> <span class="color-item color-seagreen" data-theme="seagreen"></span> </li>
                                                <li> <span class="color-item color-blue" data-theme="blue"></span> </li>
                                                <li> <span class="color-item color-purple" data-theme="purple"></span> </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                                <a href=""> <i class="fa fa-cog"></i> Customize </a>
                            </li>
                        </ul>
                    </footer>
                </aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div id=loading class="bd-example">
  <div class="modal modal-transparent" id="loadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
         <img id="loading-image" src="images/loading-spinner.gif" alt="Loading..." />

      </div><!--modal-content-->
    </div><!--modal-dialog-->
  </div><!--exampleModal-->
</div><!--bd-example-->
