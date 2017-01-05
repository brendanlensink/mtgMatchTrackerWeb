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

  public static function ParseMatch($userId, $input) {
    // The only pieces we need to actually submit a match are a matchId, a userId and some games?
    if(array_key_exists('matchId', $input) && array_key_exists(0, $input) && array_key_exists(1, $input) ) {
      // There's got to be a better way to do this, but start collecting all the match info from the json
      $matchId = $input['matchId'];
      $eventName = array_key_exists('eventName', $input) ? $input['eventName'] : null;
      $datetime = array_key_exists('datetime', $input) ? $input['datetime'] : null;
      $rel = array_key_exists('rel', $input) ? $input['rel'] : null;
      $format = array_key_exists('format', $input) ? $input['format'] : null;
      $myDeck = array_key_exists('myDeck', $input) ? $input['myDeck'] : null;
      $theirDeck = array_key_exists('theirDeck', $input) ? $input['theirDeck'] : null;

      // I'm just going to copy/paste the db insert statements here from the create match/game functions so i can use
      // one big ass transaction

      try {
        $db = new DB();
        $conn = $db->GetConnection();
        $conn->beginTransaction();

        // First try and make the match
        $msg = Match::CreateMatchWithDB(
            $conn, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck);

        if(!is_numeric($msg)) { return $msg;}

        // Then loop thru and do the games
        for ($i=0;$i<3;$i++) {
          if(array_key_exists($i, $input) && array_key_exists('game', $input[$i])) {
            $game = $input[$i]['game'];
            $start = array_key_exists('start', $input) ? $input['start'] : null;
            $result = array_key_exists('start', $input) ? $input['start'] : null;
            $myHand = array_key_exists('myHand', $input) ? $input['myHand'] : null;
            $theirHand = array_key_exists('theirHand', $input) ? $input['theirHand'] : null;
            $notes = array_key_exists('notes', $input) ? $input['notes'] : null;
            try {
              $msg = Game::CreateGameWithDB($conn, $matchId, $game, $start, $result, $myHand, $theirHand, $notes);
              if(!is_numeric($msg)) { return $msg;}
            }catch(Exception $ex) {
              return $msg;
            }
          } else {
            if($i!=2) {
              return "No game number supplied for a game";
            }
          }
        }

        // If all that works out, commit and return that we did it
        $conn->commit();
      }catch( Exception $ex ){
        return $ex;
      }

      return $msg;
    }else {
      // If we don't have a match id I think wejust bail.
      return "No MatchID supplied";
    }
  }

  public static function CreateMatchWithDB($conn, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck) {
    $msg = "";

    try {
      $stmt = $conn->prepare("INSERT INTO matches(matchId, userId, eventName, datetime, rel, format, myDeck, theirDeck) VALUES(:matchId, :userId, :eventName, :datetime, :rel, :format, :myDeck, :theirDeck)");
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

  public static function CreateMatch($matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck) {
    $msg = "";

    try {
      $db = new DB();
      $conn = $db->GetConnection();
      return Match::CreateMatchWithDB(
          $conn, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck);
    }catch(Exception $ex) {
      return "Unable to create match: ".$ex;
    }
  }
}
 ?>
