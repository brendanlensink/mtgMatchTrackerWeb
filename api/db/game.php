<?php
/**
* game.php
* Class to manage game objects on the server.
*/

require_once $CONFIG['root'].'\db\db.php';

/**
 * Class to manage game objects
 */
class Game {

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Instance Data                                                                                                    //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private $matchId;
  private $game;
  private $start;
  private $result;
  private $myHand;
  private $theirHand;
  private $notes;

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Getters and Setters                                                                                              //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
   * Default game object constructor
   *
   * @param int $matchId The match id
   * @param int $game The game number
   * @param bool $start The game start
   * @param bool $result The game result
   * @param int $myHand User's starting hand value
   * @param int $theirHand Opp's starting hand value
   * @param string $notes Notes taken for the game
   */
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
   *  Turn the game object into an array so we can json_encode it
   *
   *  @return The game object as an array
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

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Static Methods                                                                                                   //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
  * Populates a game object with a database row
  *
  * @param array $row The database row's contents
  *
  * @return A game object
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

  /**
   * Get all of the games for a given match id
   *
   * @param int $matchId The match id of the games we're looking for
   *
   * @return An array of the game objects or an error message
   */
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

  /**
   * Parse the input from a post request and save the game objects provided to the database
   *
   * @param int $matchId The matchId for the games
   * @param int $userId The userId for the games
   * @param array $input The rest of the game objects
   *
   * @return The game object if successful
   */
  public static function ParseGame($matchId, $userId, $input) {
    // If we don't have a matchId and a game then we're SOL
    if(array_key_exists('game', $input)) {
      // We need to check what other game data we were provided and sub any missing pieces out with null
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

  /**
  * Save a game object to the database with a given database connection
  *
  * @param object $conn The connection to the database
  * @param int $matchId The match id
  * @param int $game The game number
  * @param bool $start The game start
  * @param bool $result The game result
  * @param int $myHand User's starting hand value
  * @param int $theirHand Opp's starting hand value
  * @param string $notes Notes taken for the game
  *
  * @return The game id if successful or an error message
  */
  public static function CreateGameWithDB($conn, $matchId, $game, $start, $result, $myHand, $theirHand, $notes) {
    $msg = "";

    try {
      $stmt = $conn->prepare("INSERT INTO game(matchId, game, start, result, myHand, theirHand, notes) VALUES".
        "(:matchId, :game, :start, :result, :myHand, :theirHand, :notes)");
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

  /**
  * Save a game object to the database
  *
  * @param int $matchId The match id
  * @param int $game The game number
  * @param bool $start The game start
  * @param bool $result The game result
  * @param int $myHand User's starting hand value
  * @param int $theirHand Opp's starting hand value
  * @param string $notes Notes taken for the game
  *
  * @return The game id if successful or an error message
  */
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
