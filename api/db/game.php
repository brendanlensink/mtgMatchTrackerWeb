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

  public function __construct($matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
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
    $matchId = $row['matchId'];
    $game = $row['game'];
    $start = $row['start'];
    $result = $row['result'];
    $myHand = $row['myHand'];
    $theirHand = $row['theirHand'];
    $notes = $row['notes'];

    $newGame = new Game($matchId, $game, $start, $result, $myHand, $theirHand, $notes);
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

  public static function ParseGame($matchId, $userId, $input) {
    // If we don't have a matchId and a game then we're SOL
    if(array_key_exists('game', $input)) {
      $game = $input['game'];
      $start = array_key_exists('start', $input) ? $input['start'] : null;
      $result = array_key_exists('start', $input) ? $input['start'] : null;
      $myHand = array_key_exists('myHand', $input) ? $input['myHand'] : null;
      $theirHand = array_key_exists('theirHand', $input) ? $input['theirHand'] : null;
      $notes = array_key_exists('notes', $input) ? $input['notes'] : null;

      $newGame = Game::CreateGame($matchId, $game, $start, $result, $myHand, $theirHand, $notes);

      return $newGame;
    }

    return "Game not supplied";
  }

  public static function CreateGameWithDB($conn, $matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
    $msg = "";

    try {
      $stmt = $conn->prepare("INSERT INTO game(matchId, game, start, result, myHand, theirHand, notes) VALUES(:matchId, :game, :start, :result, :myHand, :theirHand, :notes)");
      $stmt->bindValue(":matchId", $matchId, PDO::PARAM_STR);
      $stmt->bindValue(":game", $game, PDO::PARAM_INT);
      $stmt->bindValue(":start", $start, PDO::PARAM_INT);
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

  public static function CreateGame($matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
    $msg = "";

    try {
      $db = new DB();
      $conn = $db->GetConnection();
      return Game::CreateGameWithDB($conn, $matchId, $game, $start, $result, $myHand, $theirHand, $notes);
    } catch(Exception $ex) {
      return "Unable to create game: ".$ex;
    }
  }
}
