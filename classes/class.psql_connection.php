<?php 
/* A class to start a connnection with Mailman  postgree database
* It will be an anonymous connection without user and password
* Onlu domains query are allowed with anonymous connectios
*/


class Database extends PDO
{
 
  //nombre base de datos
  private $dbname = "mailman";

  /*
  //nombre servidor
  private $host = "localhost";
  //nombre usuarios base de datos
  private $user = "";
  //password usuario
  private $pass = 123456;
  //puerto postgreSql
  private $port = 5432;
   */
  private $dbh;
 
  //creamos la conexión a la base de datos prueba
  public function __construct() 
  {
      try {
 
          $this->dbh = parent::__construct("pgsql:dbname=$this->dbname");
 
      } catch(PDOException $e) {
 
          echo  $e->getMessage(); 
 
      }
 
  }
 
  //función para cerrar una conexión pdo
  public function close_con() 
  {
 
      $this->dbh = null; 
 
  }
 
}
