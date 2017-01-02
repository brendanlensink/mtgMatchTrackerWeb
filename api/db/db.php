<?php
/**
* db.php
* Manages the connection to the database
*/

class DB {
  private $db = null;

  /**
  * Connects to the local database and maintains the connection.
  */
  public function DB() {
    global $CONFIG;

    try {
      $this->db = new PDO("mysql:host=".$CONFIG["db"]["host"].";dbname=".$CONFIG["db"]["name"],$CONFIG["db"]["user"],$CONFIG["db"]["pass"]);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(Exception $ex) {
      return "Error selecting account database: ".$ex;
    }
  }

  //Returns the current database connection isntance.
  public function GetConnection() {
    return $this->db;
  }
}
?>
