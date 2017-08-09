<?php
require_once("class.psql_connection.php");
class Domains 
{

  private $con;
  public function __construct()
  {
    
    //en $this->con tenemos la conexión con la bd pruebas
    $this->con = new Database();

  }
  
  
  
  //obtenemos usuarios de una tabla con postgreSql
  public function getDomains()
  {

    try{
      
      $query = $this->con->prepare('SELECT * FROM domain');
      $query->execute();
      $this->con->close_con();
      return $query->fetchAll();
    
    } catch(PDOException $e) {

          echo  $e->getMessage(); 

      }

  }

}
