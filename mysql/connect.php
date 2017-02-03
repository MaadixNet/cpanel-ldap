<?php
class Database
{
    private $username = "maddish";
     private $pass = "turuli";

     
    private $host = "localhost";
    public $conn;

    public function dbConnection($username,$pass)
    {
      $user=$this->username;
      $pass=$this->pass;
     
      $this->conn = null;    
        try
        {
            $this->conn = new PDO("mysql:host=$this->host",$user, $pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     
        }
    catch(PDOException $exception)
        {
            echo "Connection error: " . $exception->getMessage();
        }
         
        return $this->conn;
    }
}


class MysqlCreator{

  private $username = "maddish";
  private $pass = "turuli";

  private $conn;
  
  public function __construct()
  {
    $username=$this->username;
    $pass=$this->pass;
    $database = new Database();
    $db = $database->dbConnection($username,$pass);
    $this->conn = $db;
  }

    public function createUser($user,$password)
    {
      try
      {       
        $created=$this->conn->exec("
                CREATE USER '$user'@'localhost' IDENTIFIED BY '$password';
                GRANT CREATE ON *.* TO '$user'@'localhost';
                FLUSH PRIVILEGES;"); 
    
       /* $new_conn= new PDO("mysql:host=" . $this->host,$user,$password);
        $new_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $new_conn->exec("CREATE DATABASE `$db`;");*/
      }
      catch(PDOException $ex)
      {
        echo $ex->getMessage();
      }

    if ($created) {
      $result='true';
      $message='Usuario creado correctamente';
    } else {
      $result='false';
      $message='El Usuario no se ha podido crerar';
    }


        return array('message' => $message,
                      'result' => $result
                    );
  }      

  function show_databases(){

    try
    {
        $stmt=$this->conn->prepare("select schema_name from information_schema.schemata;");
        $stmt->execute();
        $dbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //$databases=array();
        //array_push($db[0],$databases); 
      }
    catch(PDOException $ex)
    {
      echo $ex->getMessage();
    }
    return $dbs;
  }
}


class MysqlnewUser{


  private $conn;
  
  public function __construct($username,$pass)
  {
    $user=$this->username;
    $pass=$this->pass;
    $database = new Database();
    $db = $database->dbConnection($user,$pass);
    $this->conn = $db;
  }

    public function createDb($db)
    {   

      try
      { 
        $this->conn->exec("CREATE DATABASE $db;");
      }   
      catch(PDOException $ex)
      {   
        echo $ex->getMessage();

      }  
    }
}
?>
