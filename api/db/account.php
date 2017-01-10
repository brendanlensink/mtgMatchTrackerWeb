<?php
/**
 * account.php
 * Account class that handles account auth and creation.
 *
 *  @author Brendan Lensink
 *  @version 1.0
 */

require_once $CONFIG['root'].'db/db.php';

/**
 *  Class in charge of handling user account objects
 */
class Account {

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Instance Data                                                                                                    //
	//                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


  private $id;
  private $email;
  private $pwhash;
  private $creation_date;

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Getters and Setters                                                                                              //
	//                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


  /**
  * Default constructor for an account
  *
  * @param int $id The account id
  * @param string $email The account email
  * @param string $pwhash The account's password hash
  * @param string $creation_date The account creation date
  */
	public function __construct( $id, $email, $pwhash, $creation_date ) {
		$this->id = $id;
		$this->email = $email;
		$this->pwhash = $pwhash;
		$this->creation_date = $creation_date;
	}

  /**
  * Get the account id
  *
  * @return The account id
  */
	public function getID() {
		return $this->id;
	}

  /**
  * Get the account email
  *
  * @return The account email
  */
	public function getEmail()	{
		return $this->email;
	}

  /**
  * Get the account password hash
  *
  * @return The account password hash
  */
	public function getPwHash()	{
		return $this->pwhash;
	}

  /**
  * Get the account creation date
  *
  * @return The account creation date
  */
	public function getCreationDate()	{
		return $this->creation_date;
	}

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Public Methods                                                                                                   //
  //                                                                                                                  //
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
  * Compares the provided password hash with the one stored in the database.
  *
  * @param string $password The hashed password provided by the user
  *
  * @return True if the passwords match, false if not
  */
  public function CheckPasswordHash( $password ) {
		try {
			$db = new DB();
			$conn = $db->GetConnection();

			$stmt = $conn->prepare("SELECT pwhash FROM account WHERE id=:uid");
			$stmt->bindValue(':uid', $this->id, PDO::PARAM_INT);
			$stmt->execute();

			$result = $stmt->fetch();

			if( $result ) {
				return Auth::CheckPasswordHash( $password, $result['pwhash'] );
			}
		} catch(Exception $ex) {
			return false;
		}
	}

	public function UpdateUsername() {
		//TODO
	}

	public function UpdatePwhash() {
		//TODO
	}

	public function Delete() {
		//TODO
	}

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                  //
	// Static Methods                                                                                                   //
	//                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
  * Populates an account object with a database row
  *
  * @param array $row The database row's contents
  *
  * @return An account object
  */
  private static function PopulateAccount( $row )	{
  	$id = $row['id'];
  	$email = $row['email'];
  	$pwhash = $row['pwhash'];
  	$creation_date = $row['creation_date'];

		$newAccount = new Account( $id, $email, $pwhash, $creation_date );
		return $newAccount;
  }

  /**
  * Attempts to retrieve an account from the database by the account's id
  *
  * @param int $id The id to look for
  *
  * @return The account if we found one or null if we didn't
  */
  private static function GetAccountByID( $id ) {
  	try {
  		$db = new DB();
  		$con = $db->GetConn();

  		$stmt = $con->prepare( "SELECT * FROM account WHERE id=:id" );
  		$stmt->bindValue( 'id', $id, PDO::PARAM_INT );
  		$stmt->execute();

  		$result = $stmt->fetch();

  		if ( $result ) {
  			return Account::PopulateAccount( $result );
  		}

  		return null;
  	} catch(Exception $ex) {
  		return "Unable to retrieve account ".$id.": ".$ex;
  	}
  }

  /**
  * Attempts to retrieve an account from the database by the account's email
  *
  * @param string $email The id to look for
  *
  * @return The account if we found one or null if we didn't
  */
  public static function GetAccountByEmail( $email ) {
  	try {
  		$db = new DB();
  		$conn = $db->GetConnection();

			$stmt = $conn->prepare( "SELECT * FROM account WHERE email=:uemail" );
			$stmt->bindValue( 'uemail', $email, PDO::PARAM_STR );
			$stmt->execute();

			$result = $stmt->fetch();

			if ( $result ) {
				return Account::PopulateAccount( $result );
			}

			return null;
		} catch(Exception $ex) {
			return "Unable to retrieve account ".$id.": ".$ex;
		}
	}

  /**
  * Attempt to save a new account to the database
  *
  * @param string $email The account email
  * @param string $password The unhashed password
  *
  * @return The account id if successful or an error message
  */
	public static function CreateAccount($email, $password) {
		$msg = "";

		try {
			$db = new DB();
			$conn = $db->GetConn();

      // Check to make sure there isn't already an account with that email
			if( Account::GetAccountByEmail( $email ) != null ) {
				$msg = "Account with that email already exists.";
				goto finish_commit;
			}

			//Validate the password.
			$hsh = Auth::HashPassword( $password );

			if( !$hsh ) {
				$msg = "Password too short.";
				goto finish_commit;
			}

			//Create the account.
			$stmt = $conn->prepare("INSERT INTO account(email, pwhash) VALUES(:username, :email, :pwhash )");
			$stmt->bindValue(":email", $email, PDO::PARAM_STR);
			$stmt->bindValue(":pwhash", $hsh, PDO::PARAM_STR);

			$stmt->execute();

			$msg = $conn->lastInsertID();

			finish_commit:
				return $msg;
		}	catch(Exception $ex) {
			return "Unable to create account: ".$ex;
		}
	}
}
