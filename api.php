<?php

header("Cache-Control: no-cache, must-revalidate");
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST');

// HTTPResponseCodes
abstract class HTTPCodes {
  const OK = 200;
  const RECORD_CREATED = 201;
  const NO_CONTENT = 204;
  const BAD_REQUEST = 400;
  const METHOD_NOT_ALLOWED = 405;
  const INTERNAL_SERVER_ERROR = 500;
}

class Api {
  // Connection to the database
  private $conn = null;

  public function __construct(string $host, string $db_name, string $user, string $password)
  {
    try {
      $this->conn = new PDO('mysql:host=' . $host . ';dbname=' . $db_name, $user, $password);
    } catch (PDOException $e) {
      $this->set_http_code(HTTPCodes::INTERNAL_SERVER_ERROR);
    }
  }

  // set response code function
  public function set_http_code(int $httpCode) 
  {
    http_response_code($httpCode);
  }

  // Exectute sql statement
  public function execute_sql_statement(string $sql, array $data = array()): PDOStatement
  {
    $conn = $this->conn;
    $stmt = $conn->prepare($sql, $data);
    $stmt->execute($data);
    return $stmt;
  }

  private function write_data(int $http_code, array $data)
  {
    $this->set_http_code($http_code);
    echo json_encode($data);
  }

  // Handle request
  public function handle_request() 
  {
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'POST':
        $this->handle_post();
        break;
      case 'GET':
        $this->handle_get();
        break;
      default:
        $this->set_http_code(HTTPCodes::METHOD_NOT_ALLOWED);
    }
  }
 
  // POST

  // Handle Post -> check if source and target okay - if yes call post_message and output record and ID, else bad request
  public function handle_post()
  {
    $source = $_POST['source'];
    $target = $_POST['target'];
    $message = $_POST['message'];
    $conn = $this->conn;

    // Check that source and target are alphanumeric and 4-32 characters long
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $source) && strlen($source) >=4 && strlen($source) <= 32 && preg_match('/^[a-zA-Z0-9_-]+$/', $source) && strlen($source) >=4 && strlen($source) <= 32) {
      $this->post_message($source, $target, $message);
      $data = array('id' => $conn->lastInsertID());
      $this->write_data(HTTPCodes::RECORD_CREATED, $data);
    } else {
      $this->set_http_code(HTTPCodes::BAD_REQUEST);
    }    
  }

  private function post_message(string $source, string $target, string $message)
  {
    $sql = "INSERT INTO message (message_source, message_target, message_body) VALUES (?, ?, ?)";
    $this->execute_sql_statement($sql, array($source, $target, $message));
  }
  



  // Get

  public function handle_get() 
  {
    if (isset($_GET['source'])) {
      $source = $_GET['source'];
    }

    if (isset($_GET['target'])) {
      $target = $_GET['target'];
    }

    if (isset($_GET['source']) && !isset($_GET['target'])) {
        $sql = "SELECT * FROM message WHERE message_source=?";
        $stmt = $this->execute_sql_statement($sql, array($source));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!isset($_GET['source']) && isset($_GET['target'])) {
        $sql = "SELECT * FROM message WHERE message_target=?";
        $stmt = $this->execute_sql_statement($sql, array($target));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($_GET['source']) && isset($_GET['target'])) {
        $sql = "SELECT * FROM message WHERE message_source=? AND message_target=?";
        $stmt = $this->execute_sql_statement($sql, array($source, $target));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
        $this->set_http_code(HTTPCodes::BAD_REQUEST);
    }

    

    if (!isset($data)) {
      $this->set_http_code(HTTPCodes::NO_CONTENT);
    } else {
      $this->write_data(HTTPCodes::OK, $data);
    }
  }

  public function __destruct()
  {
    $this->conn = null;
  }

  
}

$api = new Api('localhost', 'CI527', 'root', '');

$api->handle_request();



