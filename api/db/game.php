<?php
/**
* game.php
* Class to manage game objects on the server.
*/

require_once $CONFIG['root'].'\db\db.php';

class Game {

  /**
  * Instance Data
  */

  private $id;
  private $matchId;
  private $game;
  private $start;
  private $result;
  private $myHand;
  private $theirHand;
  private $notes;

  /**
  * Getters and Setters
  */

  public function MakeArray() {
    return array(
      "matchId" => $this->matchId,
      "game" => $this->game,
      "start" => $this->start,
      "result" => $this->result,
      "myHand" => $this->myHand,
      "theirHand" => $this->theirHand,
      "notes" => $this->notes
    );
  }

  public function __construct($id, $matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
    $this->id = $id;
    $this->matchId = $matchId;
    $this->game = $game;
    $this->start = $start;
    $this->result = $result;
    $this->myHand = $myHand;
    $this->theirHand = $theirHand;
    $this->notes = $notes;
  }

  /**
  * Static Methods
  */

  private static function PopulateGame($row) {
    $id = $row['id'];
    $matchId = $row['matchId'];
    $game = $row['game'];
    $start = $row['start'];
    $result = $row['result'];
    $myHand = $row['myHand'];
    $theirHand = $row['theirHand'];
    $notes = $row['notes'];

    $newGame = new Game($id, $matchId, $game, $start, $result, $myHand, $theirHand, $notes);
    return $newGame;
  }

  public static function GetGamesForMatch($matchId) {
    $games = array();

    try {
      $db = new DB();
      $con = $db->GetConnection();

      $stmt = $con->prepare("SELECT * FROM game WHERE matchId=:matchId");
      $stmt->bindValue( ':matchId', $matchId, PDO::PARAM_INT);
      $stmt->execute();

      while($row = $stmt->fetch()) {
        array_push($games, Game::PopulateGame($row));
      }

      return $games;
    }catch(Exception $ex) {
      return "Unable to retrieve matches: ".$ex;
    }
  }

  public static function CreateGame($id, $matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
    $msg = "";

    try {
      $db = new DB();
      $conn = $db->GetConnection();

      $stmt = $conn->prepare("INSERT INTO game VALUES(:matchId, :game, :start, :result, :myHand, :theirHand, :notes)");
      $stmt->bindValue(":matchId", $matchId, PDO::PARAM_STR);
      $stmt->bindValue(":game", $eventName, PDO::PARAM_INT);
      $stmt->bindValue(":start", $datetime, PDO::PARAM_INT);
      $stmt->bindValue(":result", $result, PDO::PARAM_INT);
      $stmt->bindValue(":myHand", $myHand, PDO::PARAM_INT);
      $stmt->bindValue(":theirHand", $theirHand, PDO::PARAM_INT);
      $stmt->bindValue(":notes", $notes, PDO::PARAM_STR);
      $stmt->execute();

      $msg = $conn->lastInsertID();
      return $msg;
    } catch(Exception $ex) {
      return "Unable to create game: ".$ex;
    }
  }
}
