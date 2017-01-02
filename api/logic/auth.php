<?php
/**
* auth.php
*
* Account authentication functions.
*/

require_once($CONFIG['root'].'\logic\emailAddressValidator.php');
require_once($CONFIG['root'].'\db\account.php');

class Auth {
	////////////////////////////////////////////////////////////////////////////
	// PUBLIC METHODS														  //
	////////////////////////////////////////////////////////////////////////////

	//Check that an email is valid.
	public static function CheckEmail( $email ) {
		$validator = new EmailAddressValidator();
		return $validator->check_email_address( $email );
	}

	//Check that a password is valid
	public static function CheckPassword( $password ) {
		if( strlen( $password ) < 8 )
			return "Password is too short.";
		if( strlen( $password ) > 100 )
			return "Password is too long.";

		return "valid";
	}

	public static function CheckPasswordHash( $password, $hash ) {
		return password_verify( $password, $hash );
	}

	//Hash a provided password.
	public static function HashPassword( $password ) {
		if( strlen( $password) < 8 )
			return false;
		if( strlen( $password) > 100 )
			return false;

		return password_hash( $password, PASSWORD_DEFAULT );
	}

	//Sign in a user.
	public static function Login( $email, $password ) {
		//First check that the email is valid.
		$emailValid = Auth::CheckEmail( $email );
		if ( !$emailValid )
			return "Invalid email address";

		//Next check that the password is valid
		$passwordValid = Auth::CheckPassword( $password );
		if ( $passwordValid != "valid"  )
			return "Invalid password";

		//Now check that that the user exists in our database.
		$user = Account::GetAccountByEmail( $email );

		if ( $user == null )
			return "Account doesn't exist";

		//Compare our supplied password hash to the one we stored in the database.
		if(!$user->CheckPasswordHash( $password ) )
			return "Username or password is incorrect";
		else {
			return $user->getID();
		}
	}
}

?>
