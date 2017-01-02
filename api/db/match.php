<?php
/**
* match.php
* Class to manage match objects on the server.
*/

require_once $CONFIG['root'].'\db\db.php';
require_once $CONFIG['root'].'\db\game.php';

class Match {

  /**
  * Instance Data
  */

  private $id;
  private $matchId;
  private $userId;
  private $eventName;
  private $datetime;
  private $rel;
  private $format;
  private $myDeck;
  private $theirDeck;
  private $games;

  /**
  * Getters and Setters
  */

  public function GetMatchId() {
    return $this->matchId;
  }

  public function AddGames($gameArray) {
    $this->games = $gameArray;
  }

  public function MakeArray() {
    $returnArray = array(
      "matchId" => $this->matchId,
      "userId" => $this->userId,
      "eventName" => $this->eventName,
      "datetime" => $this->datetime,
      "rel" => $this->rel,
      "format" => $this->format,
      "myDeck" => $this->myDeck,
      "theirDeck" => $this->theirDeck
    );

    foreach ($this->games as $game) {
      array_push($returnArray, $game->MakeArray());
    }

    return $returnArray;
  }

  public function __construct(
      $id, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck, $games) {
    $this->id = $id;
    $this->matchId = $matchId;
    $this->userId = $userId;
    $this->eventName = $eventName;
    $this->datetime = $datetime;
    $this->rel = $rel;
    $this->format = $format;
    $this->myDeck = $myDeck;
    $this->theirDeck = $theirDeck;
    $this->games = $games;
  }

  /**
  * Static Methods
  */

  private static function PopulateMatch($row) {
    $id = $row['id'];
    $matchId = $row['matchId'];
    $userId = $row['userId'];
    $eventName = $row['eventName'];
    $datetime = $row['datetime'];
    $rel = $row['rel'];
    $format = $row['format'];
    $myDeck = $row['myDeck'];
    $theirDeck = $row['theirDeck'];

    $newMatch = new Match($id, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck, array());
    return $newMatch;
  }

  public static function GetMatchById($matchId) {
    try {
      $db = new DB();
      $con = $db->GetConnection();

      $stmt = $con->prepare("SELECT * FROM matches as m, game as g".
        " WHERE m.matchId = g.matchId AND m.matchId = :matchId");
      $stmt->bindValue('matchId', $matchId, PDO::PARAM_INT);
      $stmt->execute();

      $result = $stmt->fetch();

      if($result) {
        $match = Match::PopulateMatch($result);
        $match->AddGames(Game::GetGamesForMatch($match->GetMatchId()));
        return $match;
      }

      return null;
    }catch(Exception $ex) {
      return 'Unable to retrieve match: '.$matchId.", ".$ex;
    }
  }

  public static function GetAllMatches($userId) {
    $matches = array();

    try {
      $db = new DB();
      $con = $db->GetConnection();

      $stmt = $con->prepare("SELECT * FROM matches WHERE userId=:userId");
      $stmt->bindValue( ':userId', $userId, PDO::PARAM_INT);
      $stmt->execute();

      while($row = $stmt->fetch()) {
        $match = Match::PopulateMatch($row);
        $match->AddGames(Game::GetGamesForMatch($match->GetMatchId()));
        array_push($matches, $match);
      }

      return $matches;
    }catch(Exception $ex) {
      return "Unable to retrieve matches: ".$ex;
    }
  }

  public static function CreateMatch(
      $id, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck) {
    $msg = "";

    try {
      $db = new DB();
      $conn = $db->GetConnection();

      $stmt = $conn->prepare("INSERT INTO match ".
        "VALUES(:matchId, :userId, :eventName, :datetime, :rel, :format, :myDeck, :theirDeck)");
      $stmt->bindValue(":matchId", $matchId, PDO::PARAM_STR);
      $stmt->bindValue(":userId", $userId, PDO::PARAM_STR);
      $stmt->bindValue(":eventName", $eventName, PDO::PARAM_STR);
      $stmt->bindValue(":datetime", $datetime, PDO::PARAM_INT);
      $stmt->bindValue(":rel", $rel, PDO::PARAM_STR);
      $stmt->bindValue(":format", $format, PDO::PARAM_STR);
      $stmt->bindValue(":myDeck", $myDeck, PDO::PARAM_STR);
      $stmt->bindValue(":theirDeck", $myDeck, PDO::PARAM_STR);
      $stmt->execute();

      $msg = $conn->lastInsertID();
      return $msg;
    } catch(Exception $ex) {
      return "Unable to create match: ".$ex;
    }
  }
}
 ?>
