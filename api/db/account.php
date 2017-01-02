<?php
/**
* account.php
* Class to manage account objects on the server.
*/

require_once $CONFIG['root'].'\db\db.php';

class Account {

  /**
  * Instance Data
  */

  private $id;
  private $email;
  private $pwhash;
  private $creation_date;

  /**
  * Getters and Setters
  */

  //Getters and setters.
	public function __construct( $id, $email, $pwhash, $creation_date ) {
		$this->id = $id;
		$this->email = $email;
		$this->pwhash = $pwhash;
		$this->creation_date = $creation_date;
	}

	public function getID() {
		return $this->id;
	}

	public function getUsername()	{
		return $this->username;
	}

	public function getEmail()	{
		return $this->email;
	}

	public function getPwHash()	{
		return $this->pwhash;
	}

	public function getCreationDate()	{
		return $this->creation_date;
	}

  /**
  * Public Methods
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

  /**
  * Static Methods
  */

  private static function PopulateAccount( $row )	{
  	$id = $row['id'];
  	$email = $row['email'];
  	$pwhash = $row['pwhash'];
  	$creation_date = $row['creation_date'];

		$newAccount = new Account( $id, $email, $pwhash, $creation_date );

		return $newAccount;
  }

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

	public static function CreateAccount( $username, $email, $password ) {
		$msg = "";

		try {
			$db = new DB();
			$conn = $db->GetConn();

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
			$stmt = $conn->prepare("INSERT INTO account(username, email, pwhash) VALUES(:username, :email, :pwhash )");
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
