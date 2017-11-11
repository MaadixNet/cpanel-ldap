<?php 

session_start();
require_once 'classes/class.ldap.php';
$Ldap= new LDAP();

$current_page=basename(__FILE__);
$Ldap->check_login_or_redirect($current_page);

$message='';
require_once('header.php');
//connect and BInd
$ldapconn=$Ldap->connect();
$psw=$Ldap->decrypt_psw();
if ($ldapconn){
  $ldapbind=$Ldap->bind($ldapconn,$_SESSION["login"]["dn"],$psw); 

  #TODO: Check user level to show and allow differents permissions
  #Level 10= admin : can read and manage all accounts
  #Level 4 postmaster (domain administrator) can read and edit all accounts related to his domain excluded VPN
  #level 2 : simple user. Can read and edit only his own data (basically email related)
  #need LDAP ACL to be set
  #

  $permissions= $_SESSION["login"]["level"];
  $ldaptree    = LDAP_PEOPLE;
  $filtersudo="(&(objectClass=person)(uid=*)(gidnumber=27))";

  
  //Check if a custom mail has been set
  if ($ldapbind) {
    $resultsudo=$Ldap->search($ldapconn,$ldaptree, $filtersudo);
    $username = $resultsudo[0]["uid"][0];
  }
  

}
require_once 'sidebar.php';
?>

<div id="admin-content" class="content">
  <article class="content dashboard-page">
      <section class="section">

        <div class="row">
          <div class="col-sm-12">
            <div class="inner" id="maincol">
            <?php
            echo '<h3> Mysql - PhpMyAdmin</h3>';
            printf(_("En este servidor tienes instalada una interfaz gráfica para poder administrar bases de datos MySQL.<br>
            Se trata de una aplicación externa al panel de control y que requiere otra autentificación para acceder a ella.<br><br>
            Para reforzar la seguridad de tu sistema y tus bases de datos, esta aplicación está protegida con una doble contraseña. Sólo los usuarios que tengan activado el acceso SFTP y el usuario %s (usuario por defecto del sistema) pueden acceder a esta interfaz.<br><br>
             Para la primera autentificación, que se muestra con una ventana emergente, tendrás que insertar las credenciales de un usuario válido. Puedes utilizar las credenciales del usuario %s, o bien de un usuario que hayas creado y al que hayas activado el acceso SFTP.<br> 
            Una vez efectuada satisfactoriamente esta autentificación, encontrarás el interfaz de la aplicación phpMyAdmin, que solicitará un usuario MySQL. Por defecto existe un único usuario MySQL cuyo nombre es root y cuya contraseña has recibido por correo electrónico al activar el servidor.<br><br>
           Es buena práctica crear un usuario MySQL diferente por cada base de datos y otorgarle permisos únicamente sobre una y no todas las bases de datos que tengas creadas.<br><br>
            Tanto las bases de datos como los usuarios MySQL y sus respectivas contraseñas se pueden crear y administrar desde phpMyAdmin. <br>
            Por defecto solamente el usuario root de MySQL tiene los privilegios necesarios para crear nuevas bases de datos, nuevos usuarios y otorgar permisos a cada uno de ellos.
            <br><br>
            Puedes encontrar aquí la <a href='https://www.phpmyadmin.net/docs/' target='_blank' title='Phpmyadmin documentation'>documentación oficial para el uso de la aplicación</a>"),$username, $username);?>
            <br><br>
            <a class="btn btn-primary" href="/phpmyadmin" target="_blank">Ir a la aplicación</a>
            </div><!--ineer-->
          </div><!--col-sm-8-->
        </div><!--row-->
      </div><!--admin-content-->
    </section>
  </article>

<?php
ldap_close($ldapconn);   
require_once('footer.php');?>
