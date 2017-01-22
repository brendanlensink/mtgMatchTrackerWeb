<?php
/**
* match.php
* Class to manage match objects on the server.
*/

require_once $CONFIG['root'].'db/db.php';
require_once $CONFIG['root'].'db/game.php';

/**
 * Class to handle match objects
 */
class Match {

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  //                                                                                                                  //
	// Instance Data                                                                                                    //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private $matchId;
  private $userId;
  private $eventName;
  private $datetime;
  private $rel;
  private $format;
  private $myDeck;
  private $theirDeck;
  private $games;

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Getters and Setters                                                                                              //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
   * Default match constructor
   *
   * @param int $matchId The match id
   * @param int $userId The id of the user the match belongs to
   * @param string $eventName The name of the match event
   * @param int $datetime The time of the match
   * @param string $rel The REL of the match
   * @param string $format The format the match was played in
   * @param string $myDeck The user's deck played
   * @param string $theirDeck The opp's deck played
   * @param array $games An array of the game objects played
   */
  public function __construct(
      $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck, $games) {
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
   * Get the match id
   *
   * @return The match id
   */
  public function GetMatchId() {
    return $this->matchId;
  }

  /**
   * Add a set of games to the match object
   *
   * @param array $gameArray The games to add
   */
  public function AddGames($gameArray) {
    $this->games = $gameArray;
  }

  /**
   *  Turn the match object into an array so we can json_encode it
   *
   *  @return The game object as an array
   */
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

    // We also need to encode and tack on the game objects
    foreach ($this->games as $game) {
      array_push($returnArray, $game->MakeArray());
    }

    return $returnArray;
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Static Methods                                                                                                   //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
  * Populates a match object with a database row
  *
  * @param array $row The database row's contents
  *
  * @return An match object
  */
  private static function PopulateMatch($row) {
    $matchId = $row['matchId'];
    $userId = $row['userId'];
    $eventName = $row['eventName'];
    $datetime = $row['datetime'];
    $rel = $row['rel'];
    $format = $row['format'];
    $myDeck = $row['myDeck'];
    $theirDeck = $row['theirDeck'];

    $newMatch = new Match($matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck, array());
    return $newMatch;
  }

  /**
   * Get all the matches for a given user
   *
   * @param int $userId The id of the user
   *
   * @return An array of the games as array_diff_uassoc or an error message
   */
  public static function GetAllMatches($userID) {
    $matchesFromDB = Match::GetAllMatchesByUserID($userID);
    $matchArray = array();

    foreach($matchesFromDB as $match) {
      array_push($matchArray, $match->MakeArray());
    }

    return $matchArray;
  }

  /**
  * Attempts to retrieve a match from the database by its id
  *
  * @param int $id The id to look for
  *
  * @return The match if we found one or null if we didn't
  */
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

  /**
   * Get all the matches for a given user
   *
   * @param int $userId The id of the user
   *
   * @return An array of the games as game objects or an error message
   */
  public static function GetAllMatchesByUserID($userId) {
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

  /**
   * Parse the input from a post request and save the match object provided to the database
   *
   * @param int $userId The userId for the games
   * @param array $input The rest of the game objects
   *
   * @return The match object if successful
   */
  public static function ParseMatch($userId, $input) {
    // The only pieces we need to actually submit a match are a matchId, a userId and some games?
    if(array_key_exists('matchId', $input) && array_key_exists(0, $input) && array_key_exists(1, $input) ) {
      // We need to check what other match data we were provided and sub any missing pieces out with null
      $matchId = $input['matchId'];
      $eventName = array_key_exists('eventName', $input) ? $input['eventName'] : null;
      $datetime = array_key_exists('datetime', $input) ? $input['datetime'] : null;
      $rel = array_key_exists('rel', $input) ? $input['rel'] : null;
      $format = array_key_exists('format', $input) ? $input['format'] : null;
      $myDeck = array_key_exists('myDeck', $input) ? $input['myDeck'] : null;
      $theirDeck = array_key_exists('theirDeck', $input) ? $input['theirDeck'] : null;

      // Now we're going to save the match and all of its games in one big old transaction
      try {
        $db = new DB();
        $conn = $db->GetConnection();
        $conn->beginTransaction();

        // First try and make the match
        $msg = Match::CreateMatchWithDB(
            $conn, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck);

        // If the match was created successfully, $msg will be its id and we can move on, if not return the error
        if(!is_numeric($msg)) { return $msg;}

        // Then loop thru and do the games
        for ($i=0;$i<3;$i++) {
          if(array_key_exists($i, $input) && array_key_exists('game', $input[$i])) {
            $game = $input[$i]['game'];
            $start = array_key_exists('start', $input[$i]) ? $input[$i]['start'] : null;
            $result = array_key_exists('start', $input[$i]) ? $input[$i]['result'] : null;
            $myHand = array_key_exists('myHand', $input[$i]) ? $input[$i]['myHand'] : null;
            $theirHand = array_key_exists('theirHand', $input[$i]) ? $input[$i]['theirHand'] : null;
            $notes = array_key_exists('notes', $input[$i]) ? $input[$i]['notes'] : null;
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

  /**
  * Save a match object to the database with a given database connection
  *
  * @param object $conn The connection to the database
  * @param int $matchId The match id
  * @param int $userId The id of the user the match belongs to
  * @param string $eventName The name of the match event
  * @param int $datetime The time of the match
  * @param string $rel The REL of the match
  * @param string $format The format the match was played in
  * @param string $myDeck The user's deck played
  * @param string $theirDeck The opp's deck played
  * @param array $games An array of the game objects played
  *
  * @return The match id if successful or an error message
  */
  public static function CreateMatchWithDB(
      $conn, $matchId, $userId, $eventName, $datetime, $rel, $format, $myDeck, $theirDeck) {
    $msg = "";

    try {
      $stmt = $conn->prepare("INSERT INTO matches(matchId,userId,eventName,datetime,rel,format,myDeck,theirDeck)".
        " VALUES(:matchId, :userId, :eventName, :datetime, :rel, :format, :myDeck, :theirDeck)");
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

  /**
  * Save a match object to the database
  *
  * @param int $matchId The match id
  * @param int $userId The id of the user the match belongs to
  * @param string $eventName The name of the match event
  * @param int $datetime The time of the match
  * @param string $rel The REL of the match
  * @param string $format The format the match was played in
  * @param string $myDeck The user's deck played
  * @param string $theirDeck The opp's deck played
  * @param array $games An array of the game objects played
  *
  * @return The match id if successful or an error message
  */
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
