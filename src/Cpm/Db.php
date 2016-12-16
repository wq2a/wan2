<?php
 

# Composer's autoload takes care of loading our classes in vendor 
/*use Doctrine\Common\ClassLoader;
#require_once __DIR__.'/../vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php';

//$classLoader = new Doctrine\Common\ClassLoader('Doctrine', __DIR__.'/../vendor/doctrine');
//$classLoader->register();
*/
namespace Cpm;
require_once __DIR__.'/../../vendor/autoload.php'; 
class Db {

  function sendResponse($data, $statusCode = 200, $encode = true)
  {
      return $data; // if want to send json to client comment this out 

      /* header("HTTP/1.1". " ". $statusCode );    
      header("Content-Type:application/json; charset=utf-8");
      
      if ($encode) { $data = json_encode($data); }
      //rx_error_log("Sending response: $data"); 
      echo $data;
      exit(0);
      */
  }

  /* Execute a query and return results in fetch format 
   */
  function dbQuery($db,$sql , $args = array(), $fetchFormat = \PDO::FETCH_ASSOC ) {
    $results = [];
    try {
      /*$pdo = dbPdo();
      $sth = $pdo->prepare($sql);
      $result = $sth->execute($args); 
      */
      $conn = $this->dbConn($db);
      $results = $conn->fetchAll($sql,$args);
     
    }
    catch(PDOException  $e ){
      rx_error_log( "Error: ".$e, 3 ,'php-errors.log');
      die( "Error: ".$e);
    }
    
    return $results;
  }

  // db conn
  function dbConn($db = 'cpmv') {
    // Using doctrine::dbal

    
    $config = new \Doctrine\DBAL\Configuration();
    // if have pdo can just add that to params 
    $params = array( 'pdo' => $this->dbPdo($db));
      // Detailed params to make a pdo 
      /*
      'dbname' => 'rxpmi',
      'user' => 'pmiuser',
      'password' => 'obamarocks',
      'host' => 'localhost',
      'driver' => 'pdo_mysql',
      'charset' => 'UTF-8'
      */
      
    $conn = \Doctrine\DBAL\DriverManager::getConnection($params, $config);
    return $conn;
  }

  function dbPdo($db = 'cpmv') {
    $user = 'test';
    $pass = 'pass';
    $server = 'localhost';
    $dsn = "mysql:host=".$server . ";dbname=" . $db . ";charset=utf8";
    try {
      $pdo = new \PDO($dsn, $user,$pass);
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      // For mysql to return utf8 characters correctly you must call this after connecting
      $pdo->query("SET NAMES 'utf8'");
      return $pdo; 
    } catch(PDOException $e) {
      die('Could not connect to the database:<br/>' . $e);
      rx_error_log('Could not connect to the database:<br/>' . $e, 3, 'php-errors.log');
    }

  }
  function rx_error_log($msg)
  {
    /*if (is_array($msg) ) {
      foreach ($msg as $m ) {
        error_log( print_r($m,1) ."\n", 3, 'php-errors.log'); 
      }
    }
    */
      error_log(print_r($msg,1) . "\n", 3, 'php-errors.log');
    
  }
  
}
?>

