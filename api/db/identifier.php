<?php
/**
 * identifier.php
 * Class used to handle user identifiers
 *
 *  @author Brendan Lensink
 *  @version 1.0
 */

require_once $CONFIG['root'].'db/db.php';

/**
 *  Class in charge of handling user account objects
 */
class Identifier {

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Instance Data                                                                                                    //
	//                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private $firstWord;
  private $secondWord;
  private $deviceID;

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Getters and Setters                                                                                              //
	//                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


  /**
  * Default constructor for an identifier
  *
  * @param string $firstWord The first word in the secret code
  * @param string $secondWord The second word in the secret code
  * @param string $deviceID The user's device ID
  */
	public function __construct( $firstWord, $secondWord, $deviceID ) {
		$this->firstWord = $firstWord;
		$this->secondWord = $secondWord;
		$this->deviceID = $deviceID;
	}

  /**
  * Get the device id
  *
  * @return The device id
  */
	public function getDeviceID() {
		return $this->deviceID;
	}

  /**
  * Get the identifier code linked to the device ID
  *
  * @return The identifier code
  */
	public function getCode()	{
		return $this->firstWord . " " . $this->secondWord;
	}

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Public Methods                                                                                                   //
  //                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Static Methods                                                                                                   //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
   *  Generate a set of secret codes from a device ID
   *
   *  @param string $deviceID The device id to hash
   *
   *  @return An array with the first word at the first index and the second word at the second
   */
  private static function GenerateCodes($deviceID) {
    $signedCRC = crc32($deviceID);
    $unsigned = sprintf("%u\n", $signedCRC);
    $firstInt = floor($unsigned/100000);
    $secondInt = $unsigned%100000;

    $file = file('helper/words.txt');
    $firstWord = $file[$firstInt%5471];
    $firstWord = str_replace("\r\n", "", $firstWord);
    $secondWord = $file[$secondInt%5471];
    $secondWord = str_replace("\r\n", "", $secondWord);

    return array($firstWord, $secondWord);
  }

  /**
  * Populates an identifier object with a database row
  *
  * @param array $row The database row's contents
  *
  * @return An identifier object
  */
  private static function PopulateIdentifier( $row )	{
    $firstWord = $row['firstWord'];
    $secondWord = $row['secondWord'];
    $deviceID = $row['deviceID'];

	  $newIdentifier = new Identifier($firstWord, $secondWord, $deviceID);
		return $newIdentifier;
  }

  /**
  * Attempts to retrieve an identifier from the database by the user's device ID
  *
  * @param int $deviceID The id to look for
  *
  * @return The identifer if we found one or null if we didn't
  */
  public static function GetIdenfifierByDeviceID($deviceID) {
  	try {
  		$db = new DB();
  		$con = $db->GetConnection();

  		$stmt = $con->prepare( "SELECT * FROM identifier WHERE deviceID=:deviceID" );
  		$stmt->bindValue( 'deviceID', $deviceID, PDO::PARAM_STR );
  		$stmt->execute();

  		$result = $stmt->fetch();

  		if ( $result ) {
  			return Identifier::PopulateIdentifier($result);
  		}
  		return null;
  	} catch(Exception $ex) {
  		return "Unable to retrieve identifier ".$deviceID.": ".$ex;
  	}
  }

  /**
  * Get a device id from the database based on the secret words
  *
  * @param string $firstWord The first word in the secret code
  * @param string $secondWord The second word in the secret code
  *
  * @return True if successful, false if not
  */
  public static function GetDeviceIDByIdentifier($firstWord, $secondWord) {
    try {
      $db = new DB();
      $conn = $db->GetConnection();

      $stmt = $conn->prepare("SELECT * FROM identifier WHERE firstWord=:firstWord AND secondWord=:secondWord");
      $stmt->bindValue('firstWord', $firstWord, PDO::PARAM_STR);
      $stmt->bindValue('secondWord', $secondWord, PDO::PARAM_STR);
      $stmt->execute();
      $result = $stmt->fetch();

      if($result) {
        return Identifier::PopulateIdentifier($result);
      }
      return false;
    } catch(Exception $ex) {
  		return "Unable to retrieve deviceID ".$ex;
  	}
  }

  /**
  * Attempt to save a new identifier to the database
  *
  * @param string $firstWord The first word in the secret code
  * @param string $secondWord The second word in the secret code
  * @param string $deviceID The user's device ID
  *
  * @return True if successful, false if not
  */
	public static function CreateIdentifier($deviceID) {
    // First we need to actually make the code from the device ID
    $codes = Identifier::GenerateCodes($deviceID);
    $firstWord = $codes[0];
    $secondWord = $codes[1];

		try {
			$db = new DB();
			$conn = $db->GetConnection();

      // Check to make sure there isn't already an account with that email
			if( Identifier::GetDeviceIDByIdentifier($firstWord, $secondWord) != null ) {
				// TODO: Reroll creation
				return $firstWord." ".$secondWord;
			}

			// Store the identifier.
			$stmt = $conn->prepare("INSERT INTO identifier(firstWord, secondWord, deviceID)".
        " VALUES(:firstWord,:secondWord,:deviceID)");
      $stmt->bindValue('firstWord', $firstWord, PDO::PARAM_STR);
      $stmt->bindValue('secondWord', $secondWord, PDO::PARAM_STR);
      $stmt->bindValue('deviceID', $deviceID, PDO::PARAM_STR);
			$stmt->execute();

			return $firstWord." ".$secondWord;
		}	catch(Exception $ex) {
			return -1;
		}
	}
}
