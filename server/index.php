<?php
require_once  __DIR__.'/../classes/class.ldap.php';
require_once __DIR__.'/../site-config.php';
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Pragma: no-cache");
        session_start();
        require_once  __DIR__.'/../classes/class.ldap.php';
        require_once __DIR__.'/../site-config.php';
        $Ldap= new LDAP();
        if($Ldap->is_logged_in()){
          $modules_dir = dirname(__FILE__) . '/modules/shell_files/';
          $module = escapeshellcmd($_GET['module']);

          echo shell_exec( $modules_dir . $module . '.sh' );
        }
